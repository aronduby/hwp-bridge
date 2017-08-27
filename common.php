<?php
set_time_limit(0);
ini_set('display_errors', '1');

define('MYSQL_DATETIME_FORMAT', 'Y-m-d H:i:s');
define('INPUT_DATETIME_FORMAT', 'Y-m-d\TH:i');
define('INPUT_DATE_FORMAT', 'Y-m-d');

require __DIR__ . '/define.php';
require __DIR__ . '/vendor/autoload.php';


function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

function uncaughtExceptionHandler(Exception $exception){
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

session_start();

$facebook = new Facebook(array(
  'appId'  => '286321628055151',
  'secret' => 'a8e9a0c5d1cc9a17a4e9dde0d776bd0f',
  'cookie' => TRUE,
  'domain' => BASE_HREF
));

try {
  $me = $facebook->api('/me');
} catch (FacebookApiException $e) {
  $me = NULL;
}

// print_p($_COOKIE);

$season = new Season(isset($_COOKIE['season_id']) ? $_COOKIE['season_id'] : false, PDODB::getInstance());
Config::setDbh(PDODB::getInstance());





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
?>