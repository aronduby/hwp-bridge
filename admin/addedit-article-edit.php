<?php
/**
 * @var StdClass $to
 * @var Array $allPlayers
 */
?>

<form action="addedit-article.php" method="post" data-ajax="false">
    <input type="hidden" name="article_id" value="<?= $to->article_id ?>" />

    <div data-role="header" data-theme="e">
        <h2>Article Info</h2>
    </div>

    <ul data-role="listview">
        <li data-role="fieldcontain">
            <label for="title">Title:</label>
            <input type="text" name="title" id="title" placeholder="title" value="<?= $to->title ?>" />
        </li>
        <li data-role="fieldcontain">
            <label for="description">Description:</label>
            <textarea name="description" id="description" placeholder="description (optional)"><?= $to->description ?></textarea>
        </li>
        <li data-role="fieldcontain">
            <fieldset data-role="controlgroup">
                <legend>Photos</legend>
	            <?php
	            foreach($to->photos as $src) {
	                ?>
	                <label for="photos-<?= $src ?>">
		                <img src="<?= $src ?>" alt="article image" onerror="this.src='<?= FALLBACK_IMG_SRC ?>';" />
	                </label>
		            <input type="radio"
	                   name="photo"
                       id="photos-<?= $src ?>"
                       value="<?= $src ?>"
                       data-theme="d"
			            <?= $to->photo === $src ? 'checked="checked"' : '' ?> />
	                <?php
	            }
	            ?>
            </fieldset>
        </li>
	    <li data-role="fieldcontain">
		    <label for="published">Published</label>
		    <input type="datetime-local" id="published" name="published" value="<?= $to->published->format(INPUT_DATETIME_FORMAT) ?>" />
	    </li>
	    <li data-role="fieldcontain">
		    <label for="url">URL</label>
		    <input type="url" id="url" name="url" value="<?= $to->url ?>" />
	    </li>
    </ul>

    <div data-role="header" data-theme="e">
        <h2>Mentions</h2>
    </div>

    <ul data-role="listview">
        <?php
        foreach($allPlayers as $player) {
            $pid = $player->id;
            $highlighted = array_key_exists($pid, $to->mentions);
            ?>
		    <li class="player <?= $highlighted ? 'player--checked' : '' ?>">
			    <input
					    type="checkbox"
					    name="mentions[<?= $pid ?>]player_id"
					    id="mentions-<?= $pid ?>-player_id"
					    class="player-selected-cb"
					    value="<?= $pid ?>"
					    data-theme="d"
                    <?= $highlighted ? 'checked' : '' ?>
			    />
			    <label for="mentions-<?= $pid ?>-player_id" data-theme="d"><?= $player->name ?></label>
			    <div data-role="content" class="subfields">
				    <fieldset data-role="controlgroup" class="subfields-mention">
					    <label for="mentions-<?= $pid ?>-highlight">Highlight</label>
					    <textarea
							    name="mentions[<?= $pid ?>]highlight"
							    id="mentions-<?= $pid ?>-highlight"
							    placeholder="highlight"
					    ><?= $highlighted ? strip_tags($to->mentions[$pid]['highlight']) : '' ?></textarea>
				    </fieldset>
			    </div>
		    </li>
            <?php
        }
        ?>

	    <li data-role="fieldcontain" data-theme="a">
		    <button type="submit" data-theme="b">Save</button>
	    </li>
    </ul>

</form>

<!-- make sure this is after the form otherwise styles break -->
<link rel="stylesheet" href="css/add-existing-players.css" />
<script>
    $('#page--addedit-article').live( 'pageinit',function(event){
        $("input.player-selected-cb").bind( "change", function(event, ui) {
            $(this).parents('li.player').toggleClass('player--checked', this.checked);
        });
    });
</script>