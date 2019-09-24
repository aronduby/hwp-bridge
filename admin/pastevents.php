<?php
require '../common.php';

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
		$type = ucwords($next->type);
		$next = new $type($next->id, PDODB::getInstance());
		$past_by_team[$next->team][] = $next;

		$i++;
		//if($i == 10)
		//	break;
	}

} else {
	$past_by_team = false;
}

require '_pre.php';
?>

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

<?php require '_post.php'; ?>