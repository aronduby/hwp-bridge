<?php
session_start();

set_time_limit(0);
ini_set('display_errors', '1');

define('MYSQL_DATETIME_FORMAT', 'Y-m-d H:i:s');
define('INPUT_DATETIME_FORMAT', 'Y-m-d\TH:i');
define('INPUT_DATE_FORMAT', 'Y-m-d');
define('USER_DATETIME_FORMAT', 'n/j/y g:ia');
define('USER_DATE_FORMAT', 'n/j/y');
define('USER_DATETIME_FORMAT_LONG', 'M jS, Y \a\t g:ia');
define('USER_DATE_FORMAT_LONG', 'M jS, Y');
define('FALLBACK_IMG_SRC', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNsbKy0AgAEvQG3xtuP6AAAAABJRU5ErkJggg==');

const MEDIA_SOURCE_SHUTTERFLY = 'App\\Services\\MediaServices\\ShutterflyMediaService';
const MEDIA_SOURCE_CLOUDINARY = 'App\\Services\\MediaServices\\CloudinaryMediaService';

require __DIR__ . '/define.php';
require __DIR__ . '/vendor/autoload.php';

try {
    $register = new Register();
    $register->dbh = PDODB::getInstance();

    $domain = isCli() ? getFromCli('domain') : Site::parseHost($_SERVER['HTTP_HOST']);
    $site = new Site($domain, $register);
    $register->site = $site;

    $season = new Season(isset($_SESSION['season_id']) ? $_SESSION['season_id'] : false, $register);
    $register->season = $season;

} catch (Exception $e) {
    print 'could not find that site and/or season';
    // print_p($e);
    exit;
}

require_once SITE_DEFINES_PATH . '/' . $site->domain . '.php';

function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

function uncaughtExceptionHandler(Throwable $exception){
    /*
    $handlers = ob_list_handlers();
    while(!empty($handlers)){
        ob_end_clean();
        $handlers = ob_list_handlers();
    }
    */

    $msg = "Uncaught Exception Handler:  Uncaught exception '%s' with message '%s' in %s:%s\nStack trace:\n%s\n  thrown in %s on line %s";
    $msg = sprintf(
        $msg,
        get_class($exception),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString(),
        $exception->getFile(),
        $exception->getLine()
    );

    //include 'hdr.php';
    //print '<article id="error_screen"><h1>Sorry...</h1><p>Looks like something crashed. Our developers have been notified. Please try another page.</p>';
    print '<pre>'.$msg.'</pre>';
    //else
    //	mail(DEV_EMAIL, 'HistoryGR Uncaught Exception', $msg);
    //print '</article>';
    //include 'ftr.php';
}

set_error_handler('exception_error_handler');
set_exception_handler('uncaughtExceptionHandler');

if (!isCli() && !Auth::authenticated() && $_SERVER['PHP_SELF'] !== '/login.php') {
    header('Location: login.php');
    die();
}

Config::setDbh(PDODB::getInstance());
Config::setSite($site);

function isCli() {
    return php_sapi_name() === 'cli' || php_sapi_name() === 'phpdbg';
}

function getFromCli($option) {
    $searchFor = '--' . $option . '=';

    $args = $_SERVER['argv'];
    foreach($args as $arg) {
        if(startsWith($arg, $searchFor)) {
            $value = explode('=', $arg);
            $value = array_pop($value);
            return $value;
        }
    }

    return null;
}

function print_p($value, $exit = false) {
	if (!DEBUG)
		return true;

	$backtrace = debug_backtrace();

	echo '<div style="border: 2px dotted red; background-color: #fbffd6; display: block; padding: 4px;">';
		echo '<b>Line: </b>'.$backtrace[0]['line'].'<br>';
		echo '<b>File: </b> '.$backtrace[0]['file'].'<br>';
		
		if(is_array($value) || is_object($value)) {
			echo '<pre>'.print_r($value, true).'</pre>';
		
		} elseif(is_string($value)){
			echo '<pre>'.$value.'</pre>';

		} else {
			echo '<pre>';
			var_dump($value);
			echo '</pre>';
		}
	echo '</div>';

	if($exit === true)
		die();

}


function teamToName($team){
	switch($team){
		case 'V':
			return 'Varsity';
		case 'JV':
			return 'Junior Varsity';
		case 'STAFF':
			return 'Staff';
	}
}


// 'blah blah blah blah blah blah blah' becomes 'blah blah...'
function excerptAndHighlight($text, $word=NULL, $radius=50, $highlight_begin='<strong>', $highlight_end='</strong>') {
	if (!$word) {
		if(strlen($text)>$radius*2)
			return restoreTags(substr($text, 0, strpos($text,' ',$radius*2))."...");
		else
			return $text;
	} else {
		$word = trim($word);
		$word_pos = stripos($text, $word);
		if ($word_pos !== false) {
			if ($word_pos-$radius <= 0)
				$begin_pos = 0;
			else 
				$begin_pos = strpos($text,' ',max(0,$word_pos-$radius))+1;
			$after_pos = strpos($text,' ',min(strlen($text), $word_pos+strlen($word)+$radius))
				or $after_pos = strlen($text);

			$excerpt = '';
			if ($begin_pos>0) $excerpt .= '...';
			$excerpt .= substr($text, $begin_pos, $word_pos-$begin_pos);
			$excerpt .= $highlight_begin.substr($text, $word_pos, strlen($word)).$highlight_end;
			$excerpt .= substr($text, $word_pos+strlen($word), $after_pos-($word_pos+strlen($word)));
			if ($after_pos<strlen($text)) $excerpt .= '...';

			return restoreTags($excerpt);
		} else {
			return $text;
		}
	}
}

//===================================================================================//
// Original PHP code by Chirp Internet: www.chirp.com.au // Please acknowledge use of this code by including this header.

// Used in newsDisplay function - restores unmatched html tags that were truncated
function restoreTags($input) {

// addition 7-20 AD
// if input doesn't start with a p tag, add it
if(strpos($input, '<p>')!== 0)
	$input = '<p>'.$input;

 $opened = $closed = array(); // tally opened and closed tags in order

	if(preg_match_all("/<(\/?[a-z]+)>/i", $input, $matches)) {
		 foreach($matches[1] as $tag) {
			if(preg_match("/^[a-z]+$/i", $tag, $regs)) {
				 $opened[] = $regs[0];
			} elseif(preg_match("/^\/([a-z]+)$/i", $tag, $regs)) {
				 $closed[] = $regs[1];
			}
		 }
	}
	// use closing tags to cancel out opened tags
	if($closed) {
		foreach($opened as $idx => $tag) {
		 foreach($closed as $idx2 => $tag2) {
			if($tag2 == $tag) {
				unset($opened[$idx]);
				 unset($closed[$idx2]);
				 break;
			 }
			}
		}
	}
	// close tags that are still open
	if($opened) {
		$tagstoclose = array_reverse($opened);
		 foreach($tagstoclose as $tag)
			$input .= "</$tag>";
	}
	return $input;
}


function startsWith($haystack, $needles)
{
    foreach ((array) $needles as $needle) {
        if ($needle !== '' && substr($haystack, 0, strlen($needle)) === (string) $needle) {
            return true;
        }
    }

    return false;
}

function exceptionToArray(\Throwable $e) {
    return [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTrace(),
        'traceAsString' => $e->getTraceAsString(),
        'previous' => $e->getPrevious() ? exceptionToArray($e->getPrevious()) : ''
    ];
}