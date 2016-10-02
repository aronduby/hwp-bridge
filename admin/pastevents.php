<?php
require '../common.php';
if(!isset($_COOKIE['in'])){
	header("Location: login.php");;
	die();
}

$past_by_team = [
	'V' => [],
	'JV' => [] 
];

$schedule = new Schedule($season->id, PDODB::getInstance());
$schedule->reverse();
$filtered = $schedule->getIterator();
$past = new \ScheduleFilter\DateRange($filtered, null, date('Y-m-d G:i:s', time()));
//$past = new \ScheduleFilter\Type($filtered, 'Game');
$past->rewind();
if($past->valid()){

	$i=0;
	foreach($past as $next){
		$next = new $next->type($next->id, PDODB::getInstance());
		$past_by_team[$next->team][] = $next;

		$i++;
		//if($i == 10)
		//	break;
	}

} else {
	$past_by_team = false;
}

?>

<!DOCTYPE html> 
<html> 
	<head> 
	<title>Past Events - Admin - Hudsonville Water Polo</title> 
	<meta name="viewport" content="width=device-width, initial-scale=1"> 
	<link rel="stylesheet" href="http://code.jquery.com/mobile/1.1.1/jquery.mobile-1.1.1.min.css" />
	<script src="http://code.jquery.com/jquery-1.7.1.min.js"></script>
	<script src="http://code.jquery.com/mobile/1.1.1/jquery.mobile-1.1.1.min.js"></script>
	
</head> 
<body> 

<div data-role="page" data-theme="b">

	<div data-role="header" data-theme="b">
		<a href="index.php" title="back" data-icon="home" data-iconpos="notext" data-direction="reverse">back</a>
		<a href="add-event-type-chooser.php" title="add event" class="ui-btn-right" data-icon="plus" data-iconpos="notext" data-rel="dialog">add</a>
		<h1>Past Events</h1>
	</div><!-- /header -->

	<div data-role="content">	
		<?php
		if($past_by_team !==false){

			print '<ul data-role="listview" data-theme="d" data-divider-theme="d" data-split-theme="d" data-split-icon="gear">';
			foreach($past_by_team as $team => $events){
				print '<li data-role="list-divider">'.($team=='V' ? 'Varsity' : $team).'</li>';
				foreach($events as $e){
					print $e->output('mobile-listing-past');
				}
			}
			print '</ul>';

		} else {
			print '<p>No past events found</p>';
		}
		?>
	</div><!-- /content -->

</div><!-- /page -->

</body>
</html>