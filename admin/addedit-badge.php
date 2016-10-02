<?php
require '../common.php';
if(!isset($_COOKIE['in'])){
	header("Location: login.php");;
	die();
}

if(!empty($_POST)){

	try{
		if(!empty($_FILES) && $_FILES['image']['error'] !== 4){
			$upload = Upload::factory(PUBLIC_PATH . '/badges');
			$upload->file($_FILES['image']);
			$upload->set_max_file_size(1); // in mb
			$upload->set_allowed_mime_types(array('image/png'));

			$upload->check();
			$upload->set_filename($upload->file['original_filename']);

			$results = $upload->upload();

			if(!count($results['errors'])){
				$_POST['image'] = $results['filename'];
			} else {
				throw new Exception('Could not save file, please try again later.');
			}
		}

		$dbh = PDODB::getInstance();

		$sql = "
			INSERT INTO badges SET
				id = :badge_id,
				title = :title,
				image = :image,
				description = :description,
				display_order = :display_order,
				created_at = NOW()
			ON DUPLICATE KEY UPDATE
				title = VALUES(title),
				image = VALUES(image),
				description = VALUES(description),
				display_order = VALUES(display_order),
				updated_at = NOW(),
				id = LAST_INSERT_ID(id)
		";

		$insert_update_stmt = $dbh->prepare($sql);
		$insert_update_stmt->bindValue(':badge_id', $_POST['badge_id'], PDO::PARAM_INT);
		$insert_update_stmt->bindValue(':title', $_POST['title'], PDO::PARAM_STR);
		$insert_update_stmt->bindValue(':image', $_POST['image'], PDO::PARAM_STR);
		$insert_update_stmt->bindValue(':description', $_POST['description'], PDO::PARAM_STR);
		$insert_update_stmt->bindValue(':display_order', $_POST['display_order']);

		$inserted = $insert_update_stmt->execute();
		$badge_id = $dbh->lastInsertId();

		if($inserted){
			// set it as a season badge?
			if($_POST['team_has_badge']){
				$stmt = $dbh->prepare("INSERT INTO badge_season (season_id, badge_id) VALUES (:season_id, :badge_id) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
				$stmt->bindValue(':season_id', $season->id, PDO::PARAM_INT);
				$stmt->bindValue(':badge_id', $badge_id, PDO::PARAM_INT);
				$stmt->execute();
			}

			$dbh->exec("DELETE FROM badge_player WHERE season_id = ".$dbh->quote($season->id)." AND badge_id = ".$dbh->quote($badge_id));
			if(isset($_POST['player_ids_with_badge']) && count($_POST['player_ids_with_badge'])){
				$stmt = $dbh->prepare('
					INSERT INTO badge_player SET 
						player_id = :player_id, 
						badge_id = :badge_id, 
						season_id = :season_id, 
						site_id = 1
				');
				$stmt->bindValue(':badge_id', $badge_id, PDO::PARAM_INT);
				$stmt->bindValue(':season_id', $season->id, PDO::PARAM_INT);
				$stmt->bindParam(':player_id', $player_id, PDO::PARAM_INT);

				foreach($_POST['player_ids_with_badge'] as $player_id){
					$stmt->execute();
				}
			}

			header("Location: badges.php");
			die();
		} else {
			throw new Exception('Could not save badge in database');
		}	
	
	} catch(Exception $e){
		$form_errors = $e->getMessage();
		$badge = new Badge(isset($_POST['badge_id']) ? $_POST['badge_id'] : null, PDODB::getInstance());
		$team_has_badge = $badge->checkSeason($season->id);
		$players = $season->getPlayers();
		$player_ids_with_badge = isset($_POST['player_ids_with_badge']) ? $_POST['player_ids_with_badge'] : [];
	}

} else {
	$badge = new Badge(isset($_GET['badge_id']) ? $_GET['badge_id'] : null, PDODB::getInstance());
	$team_has_badge = $badge->checkSeason($season->id);
	$players = $season->getPlayers();
	$player_ids_with_badge = [];
	foreach($badge->getPlayers($season->id) as $p){
		$player_ids_with_badge[] = $p->id;
	}
}
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
	<link rel="stylesheet" href="css/jquery-mobile-overrides.css" />

	<div data-role="header" data-theme="b">
		<a href="index.php" data-rel="back" title="back" data-icon="back" data-iconpos="notext" data-direction="reverse">back</a>
		<h1>Edit <?php echo $badge->title ?></h1>
	</div><!-- /header -->

	<div data-role="content">	
		<?php
		// print_p($player_ids_with_badge);
		// print_p($players);

		if(isset($form_errors)){
			print '<div data-role="content" data-theme="e">';
				print $form_errors;
			print '</div>';
		}
		?>
		<form action="addedit-badge.php<?= isset($_GET['badge_id']) ? '?badge_id='.$_GET['badge_id'] : ''?>" method="POST" data-ajax="false" autocomplete="off" enctype="multipart/form-data">
			<input type="hidden" name="badge_id" value="<?php echo $badge->id ?>" />
			<input type="hidden" name="season_id" value="<?php echo $season->id?>" />
			<input type="hidden" name="image" value="<?php echo $badge->image ?>" />

			<div data-role="header" data-theme="e"> 
				<h2>About the Badge</h2> 
			</div>

			<ul data-role="listview">
				<li data-role="fieldcontain">
					<label for="image">Image:</label>
					<?php
					if($badge->image != null){
						print '<img src="'.PUBLIC_HREF.'/badges/'.$badge->image.'" alt="badge image" />';
					}
					?>
					<input type="file" name="image" id="image" />
				</li>
				<li data-role="fieldcontain">
					<label for="title">Title:</label>
					<input type="text" name="title" id="title" placeholder="title" value="<?= $badge->title ?>" />
				</li>
				<li data-role="fieldcontain">
					<label for="description">Description:</label>
					<textarea name="description" id="description" placeholder="description (optional)"><?= $badge->description ?></textarea>
				</li>
				<li data-role="fieldcontain">
					<label for="display_order">Display Order:</label>
					<input type="number" name="display_order" id="display_order" placeholder="display order" value="<?= $badge->display_order ?>" />
				</li>
			</ul>

			<div data-role="header" data-theme="e"> 
				<h2>Apply To</h2> 
			</div>

			<ul data-role="listview">
				<li data-role="fieldcontain">
					<label for="team_has_badge">Team Has Badge:</label>
		        	<select name="team_has_badge" id="team_has_badge" data-role="slider" data-theme="d" data-track-theme="d">
		        		<option value="0" <?= $team_has_badge == false ? 'selected' : '' ?>>No</option>
		        		<option value="1" <?= $team_has_badge == true ? 'selected' : '' ?>>Yes</option>
		        	</select>
				</li>
				<li data-role="fieldcontain">
					<fieldset data-role="controlgroup">
						   <legend>Players with Badge:</legend>
						   <?php
						   foreach($players as $p){
						   		?>
						   		<input 
						   			type="checkbox" 
						   			name="player_ids_with_badge[]" 
						   			id="player_id_with_badge-<?= $p->id ?>"
						   			value="<?= $p->id ?>"
						   			<?= (in_array($p->id, $player_ids_with_badge) ? 'checked' : '') ?>
						   			data-theme="d" 						   			
						   		/>
						   		<label for="player_id_with_badge-<?= $p->id ?>" data-theme="d"><?= $p->name ?></label>
						   		<?php
						   }
						   ?>
					    </fieldset>
				</li>
			</ul>

			<div data-role="footer" class="ui-bar" data-theme="a">
				<button type="submit" data-theme="b">Save</button>
			</div>

		</form>
	</div><!-- /content -->

</div><!-- /page -->

</body>
</html>