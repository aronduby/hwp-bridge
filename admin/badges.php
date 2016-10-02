<?php
require '../common.php';
if(!isset($_COOKIE['in'])){
	header("Location: login.php");;
	die();
}

$badges = Badge::getAll(PDODB::getInstance());
?>
<!DOCTYPE html> 
<html> 
	<head> 
	<title>Admin - Hudsonville Water Polo</title> 
	<meta name="viewport" content="width=device-width, initial-scale=1"> 
	<link rel="stylesheet" href="http://code.jquery.com/mobile/1.1.1/jquery.mobile-1.1.1.min.css" />
	<script src="http://code.jquery.com/jquery-1.7.1.min.js"></script>
	<script src="http://code.jquery.com/mobile/1.1.1/jquery.mobile-1.1.1.min.js"></script>
	
</head> 
<body> 

<div data-role="page" data-theme="b">

	<div data-role="header" data-theme="b">
		<a href="index.php" title="back" data-icon="home" data-iconpos="notext" data-direction="reverse">back</a>
		<a href="addedit-badge.php" title="add badge" class="ui-btn-right" data-icon="plus" data-iconpos="notext">add</a>
		<h1>Badge List</h1>
	</div><!-- /header -->

	<div data-role="content">	
		<?php
		$cur_display_order = false;
		if($badges !==false && count($badges) > 0){
			?>
			<ul data-role="listview" data-theme="d" data-divider-theme="d">
				<?php
				foreach($badges as $b){
					if($b->display_order != $cur_display_order){
						print '<li data-role="list-divider"></li>';
					}
					$cur_display_order = $b->display_order;
					?>
					<li><a href="addedit-badge.php?badge_id=<?=$b->id ?>" title="edit badge">
						<img src="<?= PUBLIC_HREF ?>/badges/<?= $b->image ?>" />
						<h3><?= $b->title ?></h3>
						<p><?= excerptAndHighlight($b->description) ?></p>
					</a></li>
					<?php
				}
				?>
			</ul>
			<?php
		} else {
			print '<p>No badges found</p>';
		}
		?>
	</div><!-- /content -->

</div><!-- /page -->

</body>
</html>