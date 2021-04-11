<?php
/**
 * Imports the photos from json data in parsers/tmp/tags.json
 *
 * On the server, Generate and supply the token as necessary from parsers/shutterfly-token.php *
 * In the browser, tag the photos on the shutterfly site (use snippets as described to make this way better)
 * In the browser, run the `tagging--post-tags` snippet to post the data over to write the tags.json file
 * On the server, run parsers/shutterfly--tagged.php to import the photos
 * On the server, run parsers/shutterfly-albums.php to import the albums
 * In the browser, hwp admin assign any new albums to a game/tournament
 */
require '../common.php';

// bump the memory size up to prevent errors
ini_set('memory_limit', '500M');

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
Config::setSite($site);

// grab options from the cli
$short_opts = '';
$long_opts = [
    'skip-check', // don't check the settings to see if we should be running, needed for running manually outside of cron
    'skip-twitter', // don't post to twitter
    'skip-recent', // don't post to homepage as recent
    'dry-run' // just gather information and log it, don't actually import things
];
$cli_opts = getopt($short_opts, $long_opts);
$log->addNotice('proceeding with options', $cli_opts);

// if dry run also turn off twitter and recent
$DRY_RUN = array_key_exists('dry-run', $cli_opts);
if ($DRY_RUN) {
    $cli_opts['skip-twitter'] = true;
    $cli_opts['skip-recent'] = true;
}

$SKIP_TWITTER = array_key_exists('skip-twitter', $cli_opts);
$SKIP_RECENT = array_key_exists('skip-recent', $cli_opts);

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
$site_id = (int) $site->id;
$season_id = (int) $season->id;
$tagFilePath = BRIDGE_PATH.'/parsers/tmp/'.$site->domain.'.tags.json';

$log->info('Site', [$site]);
$log->info('Season', [$season]);
$log->info('tag path', [$tagFilePath]);

$tagged_data = file_get_contents($tagFilePath);
if ($tagged_data === false) {
    $log->addCritical('Could not get file contents');
    die();
}
$tagged_data = json_decode($tagged_data);
$log->debug('Tagged Data', [$tagged_data]);

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
$players_by_tag = [];

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

    $tags = explode('|', $p->shutterfly_tag);
    foreach ($tags as $tag) {
        $players_by_tag[$tag] = $p;
    }
}

$log->debug('players by tag', $players_by_tag);

$pics_to_import = [];

foreach($tagged_data as $playerData) {
    $log->addDebug('handling parsed data for tag ' . $playerData->tagIdentity->id, [$playerData]);

    if (isset($playerData->contentTags) && count($playerData->contentTags) > 0) {
        $tag = $playerData->tagIdentity->id;

        $player = $players_by_tag[$tag];

        foreach($playerData->contentTags as $pic) {
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
    site_id = :site_id, 
    season_id = :season_id,
    shutterfly_id = :shutterfly_id,
    file = :filename,
    width = :width, 
    height = :height,
    created_at = :created_at 
");
$add_photo_stmt->bindValue(':site', $site_id, PDO::PARAM_INT);
$add_photo_stmt->bindValue(':season_id', $season_id, PDO::PARAM_INT);
$add_photo_stmt->bindParam(':shutterfly_id', $shutterfly_id);
$add_photo_stmt->bindParam(':filename', $filename);
$add_photo_stmt->bindParam(':width', $width);
$add_photo_stmt->bindParam(':height', $height);
$add_photo_stmt->bindParam(':created_at', $created_at);

// insert player to photo relationship
$add_ptp_stmt = $dbh->prepare("
  INSERT INTO photo_player 
    (site_id, season_id, player_id, photo_id)
  SELECT
    :site_id AS site_id,
    :season_id AS season_id,
    :player_id AS player_id,    
    id AS photo_id
  FROM
    photos
  WHERE
    shutterfly_id = :shutterfly_id
");
$add_ptp_stmt->bindValue(':site_id', $site_id, PDO::PARAM_INT);
$add_ptp_stmt->bindValue(':season_id', $season_id, PDO::PARAM_INT);
$add_ptp_stmt->bindParam(':player_id', $player_id, PDO::PARAM_INT);
$add_ptp_stmt->bindParam(':shutterfly_id', $shutterfly_id);


// handle images which might have already been imported but have updated tags
$log->addNotice('updating tags and removing imgs already in the db');
foreach($pics_to_import as $shutterfly_id => $photo_data){

    $found_stmt->execute();
    $found = $found_stmt->fetch();

    // if photo found remove and reinsert all of that player to photo relationships
    if($found != '0'){
        if (!$DRY_RUN) {
            $delete_ptp_stmt->execute();
            foreach($photo_data['player_ids'] as $player_id){
                $add_ptp_stmt->execute();
            }
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

if ($DRY_RUN) {
    $log->addNotice(count($keys).' photos to import');
    $log->addNotice('dry run enabled, stopping before actual import');
    die();
}

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
            use ($dbh, $img_req_base, $photo_path, $thumb_path, &$new_photos, $filename, $shutterfly_id, $photo_data, $log, $log_type, $add_photo_stmt, $add_ptp_stmt, $site_id, $season_id)
            {
                $img_data = '';

                $response->on('data', function($data) use(&$img_data){
                    $img_data .= $data;
                });

                $response->on('end',
                    function ($err)
                    use (&$img_data, $dbh, $img_req_base, $photo_path, $thumb_path, &$new_photos, $filename, $shutterfly_id, $photo_data, $log, $log_type, $add_photo_stmt, $add_ptp_stmt, $site_id, $season_id)
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
                                ':site_id' => $site_id,
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
                                        ':site_id' => $site_id,
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

if (count($new_photos) > 0) {
    if(!$SKIP_RECENT){
        $dbh->exec("
          INSERT INTO recent SET 
            site_id = ".$site_id.", 
            season_id = ".$season_id.", 
            renderer = 'PHOTOS', 
            content=".$dbh->quote(json_encode($new_photos)).",
            created_at = NOW(),
            updated_at = NOW()
		");
    } else {
        $log->addNotice('skipping recent insert');
    }

    if(!$SKIP_TWITTER){
        $store = new OAuth\Store\HardCoded();
        $twitter = new \OAuth\Service\Twitter($store);
        $twitter->setDebug(true);

        try{
            $twitter->statuses_update(['status' => 'We just imported '.count($new_photos).' new photos, check them out at http://HudsonvilleWaterPolo.com' ]);
        } catch(Exception $e){
            $debugger = $twitter->getDebugger();
            $log->addError('Shutterfly tweet fail', [$debugger]);
        }
    } else {
        $log->addNotice('skipping tweet');
    }
}

$log->addNotice('Deleting tag file', [unlink($tagFilePath)]);