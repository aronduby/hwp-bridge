<?php
require '../common.php';

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Formatter\HtmlFormatter;

$log_type = 'SHUTTERFLY_ALBUMS_PARSE';

// create a log channel
$log = new Logger($log_type);
$formatter = new DumpFormatter();
$file_handler = new RotatingFileHandler(BRIDGE_PATH . '/parsers/logs/shutterfly-albums.html', 7, Logger::DEBUG);
$file_handler->setFormatter($formatter);
$log->pushHandler($file_handler);

$line_formatter = new Monolog\Formatter\LineFormatter("%message% %context% %extra%");
$error_log_handler = new ErrorLogHandler(ErrorLogHandler::SAPI, Logger::NOTICE);
$error_log_handler->setFormatter($line_formatter);
$log->pushHandler($error_log_handler);

header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

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
if (!array_key_exists('skip-check', $cli_opts)) {
    $run = strtoupper(Config::get('RUN_SHUTTERFLY'));
    $run = $run == 'T' || $run == 'TRUE' || $run == '1';
    if ($run != true) {
        $log->addNotice('Told not to run shutterfly album import.');
        die();
    }
}

$dbh = PDODB::getInstance();
$season_id = $dbh->query("SELECT id FROM seasons WHERE current=1")->fetch(PDO::FETCH_COLUMN);
if ($season_id == false) {
    $log->addError('Cant find the current season');
    die();
}

# LOGIN AS ME TO SET THE COOKIES FOR THE NEXT REQUEST
$log->addNotice('logging in');
try {
    $ch = curl_init();
    $opts = [
        CURLOPT_URL => "https://www.shutterfly.com/nonVisualSignin/start.sfly",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'av' => 0,
            'cid' => 'SHARE3SSUHL',
            'userName' => "aron.duby@gmail.com",
            'password' => "rukidding?",
            're' => "http://site.shutterfly.com/commands/dialogresult",
            'rememberUserName' => "on",
            'scid' => "8AZsmblm0Zs2V9",
            't' => time()
        ],
        CURLOPT_COOKIEJAR => 'shutterfly-cookies.txt',

        //WARNING: this would prevent curl from detecting a 'man in the middle' attack
        // but since I'm using a throw away password it's cool
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0
    ];
    curl_setopt_array($ch, $opts);

    // grab URL and pass it to the browser
    curl_exec($ch);

} catch (Exception $e) {

    // UPDATE THE LOG TABLE
    $log->addCritical('login failed', ['curl_errno' => curl_errno($ch), 'curl_error' => curl_error($ch)]);
    curl_close($ch);
    die();
}

# COOKIES SHOULD BE SET NOW, GRAB THE CONTENT

try {

    # GET ALL OF THE ALBUMS
    $url = 'https://cmd.shutterfly.com/commands/pictures/getitems?site=' . Config::get('SHUTTERFLY_SITE') . '&';
    $opts = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'cache' => "[object Object]",
            'format' => "js",
            // 'h' => "ay2j9w5mRHiiy2N8LcCabtbKba1s=",
            'layout' => "ManagementAlbums",
            'nodeId' => "14",
            'page' => Config::get('SHUTTERFLY_SITE') . "/pictures",
            'pageSize' => "-1",
            'size' => "-1",
            'startIndex' => "0",
            't' => time(),
            'version' => "1385146657"
        ],
        CURLOPT_COOKIEJAR => 'shutterfly-cookies.txt',
        //WARNING: this would prevent curl from detecting a 'man in the middle' attack
        // but since I'm using a throw away password it's cool
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0
    ];
    curl_setopt_array($ch, $opts);
    $rsp = curl_exec($ch);
    curl_close($ch);

    $json = new ServicesJSON();
    $value = $json->decode($rsp);

    // prepare our queries
    $dbh = PDODB::getInstance();

    // insert/update albums
    // delete all the photos in photo_to_album
    // insert photos into photo_to_album
    $album_select_stmt = $dbh->prepare('SELECT updated_at FROM albums WHERE shutterfly_id = :shutterfly_id');
    $album_select_stmt->bindParam(':shutterfly_id', $shutterfly_id);
    $album_select_stmt->setFetchMode(PDO::FETCH_COLUMN, 0);

    $album_update_stmt = $dbh->prepare('
		INSERT INTO 
            albums 
            (site_id, season_id, cover_id, shutterfly_id, title, created_at, updated_at)
            SELECT
                supplied.site_id, supplied.season_id, photos.id AS cover_id, supplied.shutterfly_id, supplied.title, supplied.created_at, supplied.updated_at
            FROM
                (
                    SELECT
                        1 AS site_id,
                        :season_id AS season_id,
                        :shutterfly_id AS shutterfly_id,
                        :title AS title,
                        :created_at AS created_at,
                        :updated_at AS updated_at,
                        :cover_shutterfly_id AS cover_shutterfly_id
                ) supplied
                LEFT JOIN photos ON(supplied.cover_shutterfly_id = photos.shutterfly_id)
        ON DUPLICATE KEY UPDATE
            site_id = 1,
            season_id = VALUES(season_id),
            cover_id = VALUES(cover_id),
            shutterfly_id = VALUES(shutterfly_id),
            title = VALUES(title),
            created_at = VALUES(created_at),
            updated_at = VALUES(updated_at),
            id = LAST_INSERT_ID(albums.id)
	');

    $unglue_stmt = $dbh->prepare('DELETE FROM album_photo WHERE album_id = :album_id');
    $unglue_stmt->bindParam(':album_id', $album_id);

    $albums_to_update = [];
    foreach ($value->result->section->groups as $album) {
        $shutterfly_id = $album->shutterflyId;

        $log->addNotice('parsing album data #' . $album->nodeId);
        $album_select_stmt->execute();
        $last_modified = $album_select_stmt->fetch();
        if ($last_modified === false || strtotime($last_modified) < $album->modified) {

            $log->addDebug('album data', [$album]);
            // match the big size from photo import
            $cover_shutterfly_id = $album->coverPicture->shutterflyId;
            $cover_shutterfly_id[35] = 5;

            // update the album info
            $data = [
                'season_id' => $season_id,
                'shutterfly_id' => $shutterfly_id,
                'title' => $album->title,
                'created_at' => date(MYSQL_DATETIME_FORMAT, $album->created),
                'updated_at' => date(MYSQL_DATETIME_FORMAT, $album->modified),
                'cover_shutterfly_id' => $cover_shutterfly_id
            ];
            try {
                $album_update_stmt->execute($data);
                $album_id = $dbh->lastInsertId();
                $albums_to_update[$album_id] = $album->nodeId;

                // unglue all the photos
                try {
                    $unglue_stmt->execute();
                } catch (PDOException $e) {
                    $e->last_stmt = $unglue_stmt;
                    throw $e;
                }
            } catch (PDOException $e) {
                $e->last_stmt = $album_update_stmt;
                throw $e;
            }
        }
    }


    # GET ALL PHOTOS FOR ANY UPDATED ALBUMS
    $url = 'https://cmd.shutterfly.com/commands/pictures/getitems?site=' . Config::get('SHUTTERFLY_SITE') . '&';
    $opts = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'cache' => "[object Object]",
            'format' => "js",
            // 'h' => "a7V19MiYbD3Y+vdeROuPec2zlmd8=",
            'layout' => "ManagementAlbumPictures",
            'nodeId' => null,
            'page' => Config::get('SHUTTERFLY_SITE') . "/pictures",
            'pageSize' => "-1",
            'size' => "-1",
            'startIndex' => "0",
            't' => time(),
            'version' => "1385146657"
        ],
        CURLOPT_COOKIEJAR => 'shutterfly-cookies.txt',
        //WARNING: this would prevent curl from detecting a 'man in the middle' attack
        // but since I'm using a throw away password it's cool
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0
    ];

    $photos_linked = 0;
    foreach ($albums_to_update as $album_id => $node_id) {
        $log->addNotice('fetching items for #' . $album_id . ':' . $node_id);

        $ch = curl_init();
        $opts[CURLOPT_POSTFIELDS]['nodeId'] = $node_id;
        curl_setopt_array($ch, $opts);
        $rsp = curl_exec($ch);
        if (!$rsp)
            throw new Exception();

        curl_close($ch);

        $json = new ServicesJSON();
        $value = $json->decode($rsp);
        $photo_shutterfly_ids = [];

        foreach ($value->result->section->items as $photo) {
            $photo_shutterfly_id = $photo->shutterflyId;
            // match the big size from photo import
            $photo_shutterfly_id[35] = 5;
            $photo_shutterfly_ids[] = $photo_shutterfly_id;
        }

        $in = str_repeat('?,', count($photo_shutterfly_ids) - 1) . '?';
        $sql = "INSERT INTO album_photo (site_id, album_id, photo_id)
                SELECT
                  1 AS site_id,
                  ? AS album_id,
                  p.id AS photo_id
                FROM
                  photos p 
                WHERE
                  p.shutterfly_id IN ($in)
            ";
        $glue_stmt = $dbh->prepare($sql);

        try {
            $glue_stmt->execute(array_merge([$album_id], $photo_shutterfly_ids));
            $photos_linked += $glue_stmt->rowCount();
        } catch (PDOException $e) {
            $e->last_stmt = $glue_stmt;
            throw $e;
        }
    }

    $log->addNotice('Imported/updated ' . count($albums_to_update) . ' album(s) and linked ' . $photos_linked . ' photos');

} catch (PDOException $e) {

    ob_start();
    $e->last_stmt->debugDumpParams();
    $ddp = ob_get_clean();
    $log->addError($e->getMessage() . ': ' . $ddp);
    die();

} catch (Exception $e) {

    // UPDATE THE LOG TABLE
    // $log->addError('Fetch Photos Failed - curl '.curl_errno($ch).':'.curl_error($ch));
    // curl_close($ch);
    $log->addError('Fetch Photos Failed', [$e]);
    die();
}


?>