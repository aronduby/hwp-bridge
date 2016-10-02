<?php
require 'common.php';
require SITE_PATH.'/classes/Config.php';
require 'includes/Logger.php';
require 'includes/PDODB.php';
require 'includes/JSON.php'; // using this instead of json_decode because the format is techincally js so parsing fails normal json_decode

header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

$dbh = PDODB::getInstance();
Config::setDbh($dbh);
Logger::setDbh($dbh);
$log_type = 'SHUTTERFLY_ALBUMS_PARSE';

// should be be running?
if(!isset($argv[1]) || $argv[1] !== 'skip-check'){
	$run = strtoupper(Config::get('RUN_SHUTTERFLY'));
	$run = $run == 'T' || $run == 'TRUE' || $run == '1';
	if($run != true){
		Logger::log($log_type, 'Told not to run shutterfly import.');
		die();
	}
}

$dbh = PDODB::getInstance();
$season_id = $dbh->query("SELECT season_id FROM season WHERE current=1")->fetch(PDO::FETCH_COLUMN);
if($season_id == false){ 
	Logger::log($log_type, 'Cant find the current season', true);
	die();
}

# LOGIN AS ME TO SET THE COOKIES FOR THE NEXT REQUEST
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

		//WARNING: this would prevent curl from detecting a 'man in the middle' attack
		// but since I'm using a throw away password it's cool
		CURLOPT_SSL_VERIFYHOST => 0,
		CURLOPT_SSL_VERIFYPEER => 0 
	);
	curl_setopt_array($ch, $opts);

	// grab URL and pass it to the browser
	curl_exec($ch);

} catch(Exception $e){

	// UPDATE THE LOG TABLE
	Logger::log($log_type, 'Login Failed - curl#'.curl_erro($ch).':'.curl_error($ch), true);
	curl_close($ch);
	die();
}

# COOKIES SHOULD BE SET NOW, GRAB THE CONTENT

try{

	# GET ALL OF THE ALBUMS	
	$url = 'https://cmd.shutterfly.com/commands/pictures/getitems?site='.Config::get('SHUTTERFLY_SITE').'&';
	$opts = array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => array(
			'cache' => "[object Object]",
			'format' => "js",
			// 'h' => "ay2j9w5mRHiiy2N8LcCabtbKba1s=",
			'layout' => "ManagementAlbums",
			'nodeId' => "14",
			'page' => Config::get('SHUTTERFLY_SITE')."/pictures",
			'pageSize' => "-1",
			'size' => "-1",
			'startIndex' => "0",
			't' => time(),
			'version' => "1385146657"
		),
		CURLOPT_COOKIEJAR => 'shutterfly-cookies.txt',
		//WARNING: this would prevent curl from detecting a 'man in the middle' attack
		// but since I'm using a throw away password it's cool
		CURLOPT_SSL_VERIFYHOST => 0,
		CURLOPT_SSL_VERIFYPEER => 0 
	);
	curl_setopt_array($ch, $opts);
	$rsp = curl_exec($ch);
	curl_close($ch);

	$json = new Services_JSON();
	$value = $json->decode($rsp);

	// prepare our queries
	$dbh = PDODB::getInstance();

	// insert/update albums
	// delete all the photos in photo_to_album
	// insert photos into photo_to_album
	$album_select_stmt = $dbh->prepare('SELECT modified FROM photo_album WHERE album_id=:album_id');
	$album_select_stmt->bindParam(':album_id', $album_id);
	$album_select_stmt->setFetchMode(PDO::FETCH_COLUMN, 0);

	$album_update_stmt = $dbh->prepare('
		INSERT INTO 
			photo_album 
		SET
			album_id = :album_id,
			season_id = :season_id,
			title = :title,
			cover_photo_id = :cover_photo_id,
			modified = :modified
		ON DUPLICATE KEY UPDATE
			album_id = VALUES(album_id),
			season_id = VALUES(season_id),
			title = VALUES(title),
			cover_photo_id = VALUES(cover_photo_id),
			modified = VALUES(modified),
			album_id = LAST_INSERT_ID(album_id)
	');

	$unglue_stmt = $dbh->prepare('DELETE FROM photo_to_album WHERE album_id=:album_id');
	$unglue_stmt->bindParam(':album_id', $album_id);

	$albums_to_update = [];
	foreach($value->result->section->groups as $album){
		$album_id = $album->nodeId;

		$album_select_stmt->execute();
		$last_modified = $album_select_stmt->fetch();
		if($last_modified === false || strtotime($last_modified) < $album->modified){
			
			// update the album info
			$data = [
				'album_id' => $album_id,
				'season_id' => $season_id,
				'title' => $album->title,
				'cover_photo_id' => 's'.$season_id.'-p'.$album->coverPicture->nodeId,
				'modified' => date(MYSQL_DATETIME_FORMAT, $album->modified)
			];
			try{
				$album_update_stmt->execute($data);
				$albums_to_update[] = $album_id;
			} catch(PDOException $e){
				$e->last_stmt = $album_update_stmt;
				throw $e;
			}

			// unglue all the photos
			try{
				$unglue_stmt->execute();
			} catch(PDOException $e){
				$e->last_stmt = $unglue_stmt;
				throw $e;
			}
		}
	}


	# GET ALL PHOTOS FOR ANY UPDATED ALBUMS
	$url = 'https://cmd.shutterfly.com/commands/pictures/getitems?site='.Config::get('SHUTTERFLY_SITE').'&';
	$opts = array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => array(
			'cache' => "[object Object]",
			'format' => "js",
			// 'h' => "a7V19MiYbD3Y+vdeROuPec2zlmd8=",
			'layout' => "ManagementAlbumPictures",
			'nodeId' => null,
			'page' => Config::get('SHUTTERFLY_SITE')."/pictures",
			'pageSize' => "-1",
			'size' => "-1",
			'startIndex' => "0",
			't' => time(),
			'version' => "1385146657"
		),
		CURLOPT_COOKIEJAR => 'shutterfly-cookies.txt',
		//WARNING: this would prevent curl from detecting a 'man in the middle' attack
		// but since I'm using a throw away password it's cool
		CURLOPT_SSL_VERIFYHOST => 0,
		CURLOPT_SSL_VERIFYPEER => 0 
	);
	
	$glue_stmt = $dbh->prepare('INSERT INTO photo_to_album SET photo_id=:photo_id, album_id=:album_id');
	$glue_stmt->bindParam(':album_id', $album_id);
	$glue_stmt->bindParam(':photo_id', $photo_id);

	$photos_linked = 0;
	foreach($albums_to_update as $album_id){
		$ch = curl_init();
		$opts[CURLOPT_POSTFIELDS]['nodeId'] = $album_id;
		curl_setopt_array($ch, $opts);
		$rsp = curl_exec($ch);
		if(!$rsp)
			throw new Exception();

		curl_close($ch);

		$json = new Services_JSON();
		$value = $json->decode($rsp);

		foreach($value->result->section->items as $i){
			$photo_id = 's'.$season_id.'-p'.$i->nodeId;
			try{
				$glue_stmt->execute();
				$photos_linked++;
			} catch(PDOException $e){
				$e->last_stmt = $glue_stmt;
				throw $e;
			}
		}
	}

	Logger::log($log_type, 'Imported/updated '.count($albums_to_update).' album(s) and linked '.$photos_linked.' photos');

} catch(PDOException $e){

	ob_start();
	$e->last_stmt->debugDumpParams();
	$ddp = ob_get_clean();
	Logger::log($log_type, $e->getMessage().': '.$ddp);
	die();

} catch(Exception $e){

	// UPDATE THE LOG TABLE
	Logger::log($log_type, 'Fetch Photos Failed - curl '.curl_errno($ch).':'.curl_error($ch));
	curl_close($ch);
	die();
}


?>