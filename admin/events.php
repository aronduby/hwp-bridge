<?php /** @noinspection PhpExpressionResultUnusedInspection */
require '../common.php';

$upcomingLimit = 10;

$schedule = new Schedule($season->id, $register);
$filtered = $schedule->getIterator();

$upcoming = new ScheduleFilter\DateRange($filtered, date('Y-m-d G:i:s', time()));
$upcoming->rewind();

$jv = new ScheduleFilter\Team($upcoming, 'JV');
$jv->rewind();

$varsity = new ScheduleFilter\Team($upcoming, 'V');
$varsity->rewind();

function printItems($iterator, $title, Register $register) {
    print '<li data-role="list-divider">'.$title.'</li>';
    foreach($iterator as $next) {
        $type = ucwords($next->type);
        $next = new $type($next->id, $register);
        print $next->output('mobile-listing');
    }
}

require '_pre.php';
?>

<!-- Upcoming -->
<div data-role="page" data-theme="b" id="soon">
    <?php
    include "_flash.php";
    ?>

	<div data-role="header" data-theme="b">
		<a href="index.php" title="back" data-icon="home" data-iconpos="notext" data-direction="reverse">back</a>
		<a href="add-event-type-chooser.php" title="add event" class="ui-btn-right" data-icon="plus" data-iconpos="notext" data-rel="dialog">add</a>
		<h1>Upcoming Events</h1>
	</div><!-- /header -->

	<div data-role="content">	
		<?php
        include '_alerts.php';

        if ($upcoming->valid()) {
            print '<ul data-role="listview" data-theme="d" data-divider-theme="d" data-split-theme="d" data-split-icon="gear">';

            if ($varsity->valid()) {
                $soon = new LimitIterator($varsity, 0, $upcomingLimit);
                printItems($soon, 'Varsity', $register);
                print $varsity->valid() ? '<li data-icon="false" class="ul-li--view-more"><a href="#all-varsity" title="view all upcoming varsity events">view all</a></li>' : '';
            }

            if ($jv->valid()) {
                $soon = new LimitIterator($jv, 0, $upcomingLimit);
                printItems($soon, 'JV', $register);
                print $jv->valid() ? '<li data-icon="false" class="ul-li--view-more"><a href="#all-jv" title="view all upcoming jv events">view all</a></li>' : '';
            }

           print '</ul>';
        } else {
            print '<p>No upcoming events found</p>';
        }
		?>
	</div><!-- /content -->

</div><!-- /page -->

<!-- All Varsity -->
<div data-role="page" data-theme="b" id="all-varsity">
    <div data-role="header" data-theme="b">
        <a href="#soon" title="soon" data-icon="back" data-iconpos="notext" data-direction="reverse">back</a>
        <a href="add-event-type-chooser.php" title="add event" class="ui-btn-right" data-icon="plus" data-iconpos="notext" data-rel="dialog">add</a>
        <h1>All Upcoming Varsity Events</h1>
    </div><!-- /header -->

    <div data-role="content">
        <?php
        $varsity->rewind();
        if ($varsity->valid()) {
            print '<ul data-role="listview" data-theme="d" data-divider-theme="d" data-split-theme="d" data-split-icon="gear">';
            if ($varsity->valid()) {
                printItems($varsity, 'Varsity', $register);
            }
            print '</ul>';
        } else {
            print '<p>No upcoming varisty events found</p>';
        }
        ?>
    </div><!-- /content -->
</div>

<!-- All JV -->
<div data-role="page" data-theme="b" id="all-jv">
    <div data-role="header" data-theme="b">
        <a href="#soon" title="soon" data-icon="back" data-iconpos="notext" data-direction="reverse">back</a>
        <a href="add-event-type-chooser.php" title="add event" class="ui-btn-right" data-icon="plus" data-iconpos="notext" data-rel="dialog">add</a>
        <h1>All Upcoming JV Events</h1>
    </div><!-- /header -->

    <div data-role="content">
        <?php
        $jv->rewind();
        if ($jv->valid()) {
            print '<ul data-role="listview" data-theme="d" data-divider-theme="d" data-split-theme="d" data-split-icon="gear">';
            if ($jv->valid()) {
                printItems($jv, 'JV', $register);
            }
            print '</ul>';
        } else {
            print '<p>No upcoming jv events found</p>';
        }
        ?>
    </div><!-- /content -->
</div>


<?php require '_post.php'; ?>