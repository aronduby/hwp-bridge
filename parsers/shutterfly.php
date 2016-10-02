<?php
require '../common.php';

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Formatter\HtmlFormatter;
$log_type = 'SHUTTERFLY_PARSE';

// create a log channel
$log = new Logger($log_type);
$formatter = new DumpFormatter();
$file_handler = new RotatingFileHandler(BRIDGE_PATH . '/parsers/logs/shutterfly.html', 7, Logger::DEBUG);
$file_handler->setFormatter($formatter);
$log->pushHandler($file_handler);

$line_formatter = new Monolog\Formatter\LineFormatter("%message% %context% %extra%");
$error_log_handler = new ErrorLogHandler(ErrorLogHandler::SAPI, Logger::NOTICE);
$error_log_handler->setFormatter($line_formatter);
$log->pushHandler($error_log_handler);


$dbh = PDODB::getInstance();
Config::setDbh($dbh);

// grab options from the cli
$short_opts = '';
$long_opts = [
	'skip-check',
	'skip-twitter',
	'skip-recent'
];
$cli_opts = getopt($short_opts, $long_opts);
$log->addNotice('proceeding with options', $cli_opts);

// should be be running?
if(!array_key_exists('skip-check', $cli_opts)){
	$run = strtoupper(Config::get('RUN_SHUTTERFLY'));
	$run = $run == 'T' || $run == 'TRUE' || $run == '1';
	if($run != true){
		$log->addNotice('Told not to run shutterfly.');
		die();
	}
}



$dbh = PDODB::getInstance();
$season_id = $dbh->query("SELECT id FROM seasons WHERE current=1")->fetch(PDO::FETCH_COLUMN);
if($season_id == false){ 
	$log->addError('Cant find the current season');
	die();
}

header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

# LOGIN AS ME TO SET THE COOKIES FOR THE NEXT REQUEST
$log->addNotice('logging in');
try{
	$ch = curl_init();
	$opts = array(
		CURLOPT_URL => "https://www.shutterfly.com/nonVisualSignin/start.sfly",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => array(
			'av' => 0,
			'cid' => 'SHARE3SSUHL',
			'userName' => "aron.duby@gmail.com",
			'password' => "rukidding?",
			're' => "http://site.shutterfly.com/commands/dialogresult",
			'rememberUserName' => "on",
			'scid' => "8AZsmblm0Zs2V9",
			't' => time()
		),
		CURLOPT_COOKIEJAR => 'shutterfly-cookies.txt',
		CURLOPT_COOKIEFILE => 'shutterfly-cookies.txt',
		// CURLOPT_PROXY => '127.0.0.1:8888',

		//WARNING: this would prevent curl from detecting a 'man in the middle' attack
		// but since I'm using a throw away password it's cool
		CURLOPT_SSL_VERIFYHOST => 0,
		CURLOPT_SSL_VERIFYPEER => 0 
	);
	curl_setopt_array($ch, $opts);

	// grab URL and pass it to the browser
	$rsp = curl_exec($ch);

} catch(Exception $e){

	// UPDATE THE LOG TABLE
	$log->addCritical('login failed', ['curl_erro'=>curl_erro($ch), 'curl_error'=>curl_error($ch)]);
	curl_close($ch);
	die();
}

# COOKIES SHOULD BE SET NOW, GRAB THE CONTENT
// grab all of the players in this season
$player_stmt = $dbh->query("
	SELECT 
		p.id, pts.player_id, pts.shutterfly_tag, p.first_name, p.last_name 
	FROM 
		player_season pts 
		LEFT JOIN players p ON(pts.player_id = p.id)
	WHERE 
		pts.season_id=".$season_id."
    "
);
$player_stmt->setFetchMode(PDO::FETCH_OBJ);
$players = $player_stmt->fetchAll();

// grab all of the photos for the players (so we know if we should be importing or not)
$photo_stmt = $dbh->prepare("
	SELECT 
		p.shutterfly_id 
	FROM 
		photo_player pp 
		JOIN photos p ON(pp.photo_id = p.id) 
	WHERE 
		pp.player_id=:pid 
		AND pp.season_id=:season_id
");
$photo_stmt->bindValue(':season_id', $season_id);
$photo_stmt->bindParam(':pid', $player_id);
$photo_stmt->setFetchMode(PDO::FETCH_COLUMN, 0);

foreach($players as $p){
	$player_id = $p->id;
	$photo_stmt->execute();
	$p->photos = $photo_stmt->fetchAll();
}


// curl variables
$url = 'http://cmd.shutterfly.com/commands/tagsmanagement/gettags?site='.Config::get('SHUTTERFLY_SITE');
$pics_to_import = array(); // see below for structure

foreach($players as $player){
	$tags = $player->shutterfly_tag;
	$tags = explode('|', $tags);
	$log->addDebug($player->first_name.' '.$player->last_name.' photos', $player->photos);

	foreach($tags as $tag){
		$log->addNotice('fetching tags for #'.$player->id.': '.$player->first_name.' '.$player->last_name.' ('.$tag.')');
		try{
			$opts = array(
				CURLOPT_URL => $url.'&tag='.$tag,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => [
					'size' => '-1',
					'pageSize' => '-1'
				],
				CURLOPT_COOKIEFILE => 'shutterfly-cookies.txt',
				CURLOPT_COOKIEJAR => 'shutterfly-cookies.txt',
				// CURLOPT_PROXY => '127.0.0.1:8888'
			);
			curl_setopt_array($ch, $opts);
			$rsp = curl_exec($ch);

		} catch(Exception $e){
			// UPDATE THE LOG TABLE
			$log->addError('Fetching tags failed: '.$url.$tag, ['curl_erro'=>curl_erro($ch), 'curl_error'=>curl_error($ch)]);
			curl_close($ch);
			die();
		}

		if($rsp){
			$json = new ServicesJSON();
			$value = $json->decode($rsp);
			$log->addDebug('parsed response for '.$tag.': '.$url, [$value]);
			
			if(isset($value->result->section->contentTags) && count($value->result->section->contentTags)>0){
				foreach($value->result->section->contentTags as $pic){
					$filename = 's' . $season_id . '-p' . $pic->item->nodeId;
					$shutterfly_id = $pic->item->pictureId;
					$shutterfly_id[35] = 5; // biggest publicly available 800px
					$created = $pic->created;

					// if the photo_id isn't in this players photos, add it to the import list
					if(!in_array($shutterfly_id, $player->photos)){
						if(array_key_exists($shutterfly_id, $pics_to_import) && is_array($pics_to_import[$shutterfly_id])){
							if(!in_array($player->id, $pics_to_import[$shutterfly_id]['player_ids'])){
								$pics_to_import[$shutterfly_id]['player_ids'][] = $player->id;
							}
						} else {
							$pics_to_import[$shutterfly_id] = array(
								'shutterfly_id' => $shutterfly_id,
								'player_ids' => array( $player->id ),
								'created_at' => date(MYSQL_DATETIME_FORMAT, $created),
                                'filename' => $filename
							);
						}
					}
				}
			}
		}
	}

} // end player tag loop
curl_close($ch);

$log->addDebug('pics to import before check', $pics_to_import);

# NOW WE HAVE AN ARRAY OF PHOTOS TO GRAB AND SAVE

# PDO STATEMENTS FOR BELOW
// check the database for the photo
$found_stmt = $dbh->prepare("SELECT COUNT(*) AS found FROM photos WHERE shutterfly_id=:shutterfly_id");
$found_stmt->bindParam(':shutterfly_id', $shutterfly_id);
$found_stmt->setFetchMode(PDO::FETCH_COLUMN, 0);

// delete player to photo relationship
// this will catch adding new tags as well as removing ones that were removed in shutterfly
$delete_ptp_stmt = $dbh->prepare("
  DELETE photo_player
  FROM photo_player
  JOIN photos ON photo_player.photo_id = photos.id
  WHERE photos.shutterfly_id = :shutterfly_id
");
$delete_ptp_stmt->bindParam(':shutterfly_id', $shutterfly_id);

// add new photo
$add_photo_stmt = $dbh->prepare("
  INSERT INTO photos SET 
    site_id = 1, 
    season_id = :season_id,
    shutterfly_id = :shutterfly_id,
    file = :filename,
    width = :width, 
    height = :height,
    created_at = :created_at 
");
$add_photo_stmt->bindValue(':season_id', $season_id);
$add_photo_stmt->bindParam(':shutterfly_id', $shutterfly_id);
$add_photo_stmt->bindParam(':filename', $filename);
$add_photo_stmt->bindParam(':width', $width);
$add_photo_stmt->bindParam(':height', $height);
$add_photo_stmt->bindParam(':created_at', $created_at);

// insert player to photo relationship
$add_ptp_stmt = $dbh->prepare("
  INSERT IGNORE INTO photo_player 
    (site_id, player_id, season_id, photo_id)
  SELECT
    1 AS site_id,
    :player_id AS player_id,
    :season_id AS season_id,
    id AS photo_id
  FROM
    photos
  WHERE
    shutterfly_id = :shutterfly_id
");
$add_ptp_stmt->bindParam(':player_id', $player_id);
$add_ptp_stmt->bindValue(':season_id', $season_id);
$add_ptp_stmt->bindParam(':shutterfly_id', $shutterfly_id);


// handle images which might have already been imported but have updated tags
$log->addNotice('updating tags and removing imgs already in the db');
// var_dump($pics_to_import);
foreach($pics_to_import as $shutterfly_id => $photo_data){

	$found_stmt->execute();
	$found = $found_stmt->fetch();

	// if photo found remove and reinsert all of that player to photo relationships
	if($found != '0'){
		$delete_ptp_stmt->execute();
		foreach($photo_data['player_ids'] as $player_id){
			$add_ptp_stmt->execute();
		}

		unset($pics_to_import[$shutterfly_id]);
	}
}

$log->addDebug('pics to import post db check', (array)$pics_to_import);

// now we fetch and save any images that are left
$img_req_base = "http://im1.shutterfly.com/procgtaserv/";
$photo_path = PHOTO_PATH .'/';
$thumb_path = $photo_path . 'thumbs/';
$new_photos = [];

$keys = array_keys($pics_to_import);
$batch_size = 10;
$batches = array_chunk($keys, $batch_size);
$batches_count = count($batches);


# async proxy through socks tor
$loop = React\EventLoop\Factory::create();
$dnsResolverFactory = new React\Dns\Resolver\Factory();
$dns = $dnsResolverFactory->createCached('8.8.8.8', $loop);
$factory = new Socks\Factory($loop, $dns);
$client = $factory->createClient('127.0.0.1', 9050);
$client->setResolveLocal(false);
$httpclient = $client->createHttpClient();


$log->addNotice(count($keys).' photos to import');
foreach($batches as $bi => $batch){

	$log->addNotice('running batch '.($bi + 1).' of '.$batches_count);

	foreach($batch as $shutterfly_id){

		$photo_data = $pics_to_import[$shutterfly_id];
		$filename = $photo_data['filename'].'.jpg';

		$request = $httpclient->request('GET', $img_req_base.$shutterfly_id , array('user-agent'=>'Custom/1.0'));
		$request->on('response', 
			function (React\HttpClient\Response $response) 
			use ($dbh, $img_req_base, $photo_path, $thumb_path, &$new_photos, $filename, $shutterfly_id, $photo_data, $log, $log_type, $add_photo_stmt, $add_ptp_stmt, $season_id)
		{

			$img_data = '';

			$response->on('data', function($data) use(&$img_data){
				$img_data .= $data;
			});

			$response->on('end', 
				function ($err) 
				use (&$img_data, $dbh, $img_req_base, $photo_path, $thumb_path, &$new_photos, $filename, $shutterfly_id, $photo_data, $log, $log_type, $add_photo_stmt, $add_ptp_stmt, $season_id)
			{
				try{
					if($err)
						throw new Exception($err);

					$img = imagecreatefromstring($img_data);
					if(!$img){
						throw new Exception('Could not load: '.$img_req_base.$shutterfly_id);
					}
					$width = imagesx($img);
					$height = imagesy($img);
					imageinterlace($img, 1);
					if(imagejpeg($img, $photo_path.$filename) == false)
						throw new Exception('could not save full photo "'.$filename.'" to "'.$photo_path.'"');

					$log->addNotice("saved ".$photo_path.$filename);

					# THUMBNAIL
					$thumb_width = 200;
					$thumb_height = 200;
					$ratio_orig = $width/$height;
					if($thumb_width / $thumb_height > $ratio_orig) {
					   $thumb_width = $thumb_height *$ratio_orig;
					} else {
					   $thumb_height = $thumb_width / $ratio_orig;
					}

					$thumb = imagecreatetruecolor($thumb_width, $thumb_height);
					imagecopyresampled($thumb, $img, 0, 0, 0, 0, $thumb_width, $thumb_height, $width, $height);
					if(imagejpeg($thumb, $thumb_path.$filename) == false)
						throw new Exception('could not save thumbnail for photo "'.$filename.'" to "'.$thumb_path.'"');

					$log->addNotice("saved ".$thumb_path.$filename);

					imagedestroy($img);
					imagedestroy($thumb);
					$img = false;
					$thumb = false;

					# UPDATE THE DATABASE
					$added = $add_photo_stmt->execute([
                        ':season_id' => $season_id,
                        ':shutterfly_id' => $photo_data['shutterfly_id'],
                        ':filename' => $photo_data['filename'], // don't use $filename since it has the extension
                        ':width' => $width,
                        ':height' => $height,
                        ':created_at' => $photo_data['created_at'],
					]);
                    $photo_id = $dbh->lastInsertId();
					if( $added ){
						foreach($photo_data['player_ids'] as $player_id){
							$add_ptp_stmt->execute([
								':player_id' => $player_id,
								':shutterfly_id' => $shutterfly_id,
								':season_id' => $season_id
							]);
						}
						$new_photos[] = $photo_id;
					}

				} catch(Exception $e){
					if(isset($img) && $img !== false)
						imagedestroy($img);
					if(isset($thumb) && $thumb !== false)
						imagedestroy($thumb);

					$log->addError($e->getMessage(), (array)$e);
				}
			});
		});

		$request->end();
	}

	$loop->run();
}

// update the log and recent table
$log->addNotice("Shutterfly import success: ".count($new_photos)." photos imported");

if(count($new_photos)>0){
	if(!array_key_exists('skip-recent', $cli_opts)){
		$dbh->exec("
          INSERT INTO recent SET 
            site_id = 1, 
            season_id = ".$season_id.", 
            renderer = 'PHOTOS', 
            content=".$dbh->quote(json_encode($new_photos)).",
            created_at = NOW(),
            updated_at = NOW()
		");
	} else {
		$log->addNotice('skipping recent insert');
	}

	if(!array_key_exists('skip-twitter', $cli_opts)){
		$store = new OAuth\Store\HardCoded();
		$twitter = new \OAuth\Service\Twitter($store);
		$twitter->setDebug(true);

		try{

			$twitter->statuses_update(['status' => 'We just imported '.count($new_photos).' new photos, check them out at http://HudsonvilleWaterPolo.com' ]);

		} catch(Exception $e){
			$debugger = $twitter->getDebugger();
			$log->addError('Shutterfly tweet fail', $debugger);
		}
	} else {
		$log->addNotice('skipping tweet');
	}
}

?>