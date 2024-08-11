<?php
/**
 * NOTE! Make sure you disable ajax navigation to this page otherwise both pages won't load which will break the
 * folder selection. You do this by adding data-ajax="false" property on the links
 *
 * @noinspection SqlResolve
 * @var Register $register
 * @var Season $season
 * @var Site $site
 */

use JsonSchema\Constraints\Constraint;

require '../common.php';

// make sure this matches the provider options
$mediaServiceOptions = [
    MEDIA_SOURCE_SHUTTERFLY => 'Shutterfly',
    MEDIA_SOURCE_CLOUDINARY => 'Cloudinary',
];

if (!empty($_POST)) {

	// confirm the settings schema
    $validator = new JsonSchema\Validator();
    $settings = (object) $_POST['settings']['cloudinary'];
    $schema = json_decode(file_get_contents(SCHEMA_PATH.'/cloudinary.json'));

	$validator->validate($settings, $schema, Constraint::CHECK_MODE_COERCE_TYPES);
    $valid = $validator->isValid();

	if (!$valid) {
		$form_errors = '<p>Settings validation failed:</p><ul>';
        foreach ($validator->getErrors() as $error) {
            $form_errors .= sprintf("<li><strong>%s</strong>: %s</li>", $error['property'], $error['message']);
        }
        $form_errors .= '</ul>';

        // give it the season again, but populated with the post values
		$editSeason = new Season($_GET['season_id'] ?? null, $register);
		foreach ($_POST as $k => $v) {
			if (property_exists($editSeason, $k)) {
				$editSeason->$k = $v;
			}
		}

		// send the settings back too
		$editSettings = $settings;

	}
	else {
		// passed schema validation, do saves now
        try {
            $dbh = PDODB::getInstance();

            // Make sure we limit this it available values
            $mediaService = $_POST['media_service'];
            if (!in_array($mediaService, array_keys($mediaServiceOptions))) {
                $mediaService = null;
            }

            $sql = "
				INSERT INTO seasons SET
					id = :season_id,
	                site_id = :site_id,
					title = :title,
					short_title = :short_title,
					current = :current,
					ranking = :ranking,
					ranking_tie = :ranking_tie,
					ranking_title = :ranking_title,			                        
	                ranking_updated = NOW(),
	                media_service = :media_service,
					created_at = NOW()
				ON DUPLICATE KEY UPDATE
	                site_id = VALUES(site_id),
					title = VALUES(title),
					short_title = VALUES(short_title),
					current = VALUES(current),
					ranking = VALUES(ranking),
					ranking_tie = VALUES(ranking_tie),
	                ranking_title = VALUES(ranking_title),
	                ranking_updated = NOW(),
	                media_service = VALUES(media_service),
					updated_at = NOW(),
					id = LAST_INSERT_ID(id)
			";

            $insert_update_stmt = $dbh->prepare($sql);
            $insert_update_stmt->bindValue(':season_id', $_POST['season_id'], PDO::PARAM_INT);
            $insert_update_stmt->bindValue(':site_id', $site->id, PDO::PARAM_INT);
            $insert_update_stmt->bindValue(':title', $_POST['title'], PDO::PARAM_STR);
            $insert_update_stmt->bindValue(':short_title', $_POST['short_title'], PDO::PARAM_STR);
            $insert_update_stmt->bindValue(':current', $_POST['current'], PDO::PARAM_BOOL);
            $insert_update_stmt->bindValue(':ranking', $_POST['ranking'], PDO::PARAM_INT);
            $insert_update_stmt->bindValue(':ranking_tie', $_POST['ranking_tie'], PDO::PARAM_BOOL);
            $insert_update_stmt->bindValue(':ranking_title', empty($_POST['ranking_title']) ? null : $_POST['ranking_title']);
            $insert_update_stmt->bindValue(':media_service', $mediaService);
            $inserted = $insert_update_stmt->execute();
            $season_id = $dbh->lastInsertId();

            if ($inserted) {
                // if it's current, unset all the other current seasons for this site
                if ($_POST['current'] == true) {
                    $clearSql = "UPDATE seasons SET current = 0 WHERE current = 1 AND site_id = :site_id AND id != :season_id";

                    $clearStmt = $dbh->prepare($clearSql);
                    $clearStmt->bindValue(':site_id', $site->id, PDO::PARAM_INT);
                    $clearStmt->bindValue(':season_id', $season_id, PDO::PARAM_INT);

                    if (!$clearStmt->execute()) {
                        throw new Exception('Season was saved, but other seasons are still marked as current. Please manually edit those seasons');
                    }
                }

				// save the settings
	            $savedSeason = new Season($season_id, $register);
				if (!$savedSeason->saveSettings(['cloudinary' => $settings])) {
					throw new Exception('Season was saved, but could not save the settings. Please try again shortly.');
				}

                header("Location: seasons.php");
                die();
            } else {
                throw new Exception('Could not save season in database');
            }

        } catch (Exception $e) {
            $form_errors = $e->getMessage();
            $editSeason = new Season($_POST['season_id'] ?? null, $register);
        }
    }

} else {
    $editSeason = new Season($_GET['season_id'] ?? null, $register);
}

require '_pre.php';
?>

<div data-role="page" data-theme="b" id="page--addedit-season">
	<link rel="stylesheet" href="css/jquery-mobile-overrides.css" />

	<div data-role="header" data-theme="b">
		<a href="index.php" data-rel="back" title="back" data-icon="back" data-iconpos="notext" data-direction="reverse">back</a>
		<h1>Edit <?php echo $editSeason->title ?></h1>
	</div><!-- /header -->

	<div data-role="content">	
		<?php
		include '_form-errors.php';
		?>
		<form action="addedit-season.php<?= isset($_GET['season_id']) ? '?season_id='.$_GET['season_id'] : ''?>" method="POST" data-ajax="false" autocomplete="off" enctype="multipart/form-data">
			<input type="hidden" name="season_id" value="<?php echo $editSeason->id?>" />

			<ul data-role="listview">
				<li data-role="fieldcontain">
					<label for="title">Title:</label>
					<input type="text" name="title" id="title" placeholder="title" value="<?= $editSeason->title ?>" />
					<p class="helper-text ui-li-desc">usually full school year, ie 2019/2020</p>
				</li>
				<li data-role="fieldcontain">
					<label for="short_title">Short Title:</label>
					<input type="text" name="short_title" id="short_title" placeholder="short title" value="<?= $editSeason->short_title ?>" />
					<p class="helper-text ui-li-desc">usually the last two of the year of the start of season, ie 19</p>
				</li>
				<li data-role="fieldcontain">
					<label for="current">Current Season:</label>
					<select name="current" id="current" data-role="slider" data-theme="d" data-track-theme="d">
						<option value="0" <?php echo $editSeason->current == false ? 'selected="selected"' : '' ?>>No</option>
						<option value="1" <?php echo $editSeason->current == true ? 'selected="selected"' : '' ?>>Yes</option>
					</select>
				</li>

				<li role="list-divider" data-theme="c">Ranking</li>

				<li data-role="fieldcontain">
					<label for="ranking">Ranking:</label>
					<input type="number" name="ranking" id="ranking" placeholder="ranking" value="<?= $editSeason->ranking ?>" />
					<p class="helper-text ui-li-desc">auto-updated by ranking parser</p>
				</li>

				<li data-role="fieldcontain">
					<label for="ranking_tie">Ranking Is Tied:</label>
					<select name="ranking_tie" id="ranking_tie" data-role="slider" data-theme="d" data-track-theme="d">
						<option value="0" <?php echo $editSeason->ranking_tie === 0 ? 'selected="selected"' : '' ?>>No</option>
						<option value="1" <?php echo $editSeason->ranking_tie === 1 ? 'selected="selected"' : '' ?>>Yes</option>
					</select>
					<p class="helper-text ui-li-desc">auto-updated by ranking parser</p>
				</li>

				<li role="list-divider" data-theme="c">Ranking Override</li>

				<li data-role="fieldcontain">
					<label for="ranking_title">Title:</label>
					<input type="text" name="ranking_title" id="ranking_title" placeholder="ranking title" value="<?= $editSeason->ranking_title ?>" />
					<p class="helper-text ui-li-desc">manually set the ranking title, overriding the ranking parser values above</p>
				</li>

				<li role="list-divider" data-theme="c">Season Settings</li>

				<li data-role="fieldcontain">
					<label for="media_service">Media Provider:</label>
					<select name="media_service" id="media_service" data-theme="d">
						<?php
						foreach($mediaServiceOptions as $val => $label) {
							?><option value="<?= $val ?>" <?= $editSeason->media_service === $val ? 'selected="selected"' : '' ?> data-key="<?= strtolower($label) ?>"><?= $label ?><?php
						}
						?>
					</select>
				</li>
			</ul>

			<ul data-role="listview" id="mediaService-settings"></ul>

			<ul data-role="listview">
				<li data-role="fieldcontain">
					<button type="submit">Save</button>
				</li>
			</ul>

			<template class="mediaService-settings" data-service="cloudinary">
                <?php
                $seasonSettings = $editSeason->getSettings()->cloudinary ?? null;
                $siteSettings = $site->getSettings()->cloudinary ?? null;
                $settings = $editSettings ?? $seasonSettings ?? $siteSettings ?? new StdClass();
                $folder = $seasonSettings->root_folder ?? false;
                ?>
				<li data-role="fieldcontain">
					<label for="cloud_name">Cloud Name:</label>
					<input type="text" name="settings[cloudinary][cloud_name]" id="cloud_name" placeholder="cloud name" value="<?= $settings->cloud_name ?? null ?>" />
					<p class="helper-text ui-li-desc">also called a product environment</p>
				</li>
				<li data-role="fieldcontain">
					<label for="api_key">API Key:</label>
					<input type="text" name="settings[cloudinary][api_key]" id="api_key" placeholder="api key" value="<?= $settings->api_key ?? null ?>" />
					<p class="helper-text ui-li-desc">found under Settings > Access Keys</p>
				</li>
				<li data-role="fieldcontain">
					<label for="api_secret">API Secret:</label>
					<input type="text" name="settings[cloudinary][api_secret]" id="api_secret" placeholder="api secret" value="<?= $settings->api_secret ?? null ?>" />
					<p class="helper-text ui-li-desc">found under Settings > Access Keys</p>
				</li>
				<li data-role="fieldcontain">
					<label for="root-folder-btn">Season Root Folder:</label>
					<input
						type="button"
						id="root-folder-btn"
						data-inline="true"
						data-theme="<?= $folder ? 'c' : 'e' ?>"
						data-icon="<?= $folder ? 'refresh' : 'alert' ?>"
						data-iconpos="right"
						value="<?= $folder ?: 'no folder selected' ?>"
						data-clickhandler="cloudinary_selectRootFolder"
					/>

					<input type="hidden" name="settings[cloudinary][root_folder]" id="root-folder-input" value="<?= $folder ?>" />
				</li>
			</template>

		</form>
	</div><!-- /content -->

	<a id="cloudinary-folderListing-trigger" href="#cloudinary-folderListing" data-rel="dialog" style="visibility: hidden">I'm here to trigger clicks</a>

	<style>
        #cloudinary-folderListing .ui-li[data-theme="d"] {
            font-weight: normal;
            padding-left: 10px;
        }
	</style>

	<script>
        $('#page--addedit-season').live('pageinit', function(event) {

            const handlers = {
                cloudinary_selectRootFolder
            };

            function updateMediaServiceSettings() {
                const serviceSelect = document.getElementById('media_service');
                const settingsHolder = document.getElementById('mediaService-settings');

                const service = serviceSelect.options[serviceSelect.selectedIndex].dataset.key;
                const selector = `template.mediaService-settings[data-service="${service}"]`;
                const serviceSettings = document.querySelector(selector);

                settingsHolder.innerHTML = serviceSettings ? serviceSettings.innerHTML : '';
                $(settingsHolder).listview('refresh');
                $('#page--addedit-season').trigger('create');

                // bind anything with a data-clickhandler
                [...settingsHolder.querySelectorAll('[data-clickhandler]')].forEach(el => {
                    el.addEventListener('click', (e) => {
                        handlers[el.dataset.clickhandler](e);
                    });
                });
            }

            async function cloudinary_getFolders() {
                const cloud_name = document.getElementById('cloud_name').value;
                const api_key = document.getElementById('api_key').value;
                const api_secret = document.getElementById('api_secret').value;

                const fd = new FormData();
                fd.set('cloud_name', cloud_name);
                fd.set('api_key', api_key);
                fd.set('api_secret', api_secret);

                const rsp = await fetch('cloudinary/folder-list.php', {
                    method: 'POST',
                    mode: "cors",
                    credentials: "same-origin",
                    body: fd
                });

                return rsp.json();
            }

            async function cloudinary_openSelectionDialog(folders) {
                function listItemMarkup(folder, theme) {
                    return `<li
					data-theme="${theme}"
					data-path="${folder.path}"
				>
					<a href="#">${folder.path}</a>
				</li>`;
                }

                return new Promise((resolve, reject) => {
                    const listMarkUp = folders.reduce((acc, folder) => {
                        acc += listItemMarkup(folder, 'c');

                        if (folder.subs) {
                            acc += folder.subs.reduce((subAcc, sub) => {
                                return subAcc += listItemMarkup(sub, 'd');
                            }, '');
                        }

                        return acc;
                    }, '');

                    const listing = document.getElementById('cloudinary-folderListing').querySelector('[data-role="listview"]');
                    listing.innerHTML = listMarkUp;
                    try {
                        // this will error the first time, but is needed every other time
                        $(listing).listview('refresh');
                    } catch (e) {
                        // yeah, we know
                    }

                    function pickedFolder(e) {
                        resolve(e.currentTarget.dataset.path);

                        $('#cloudinary-folderListing').dialog('close');
                        $(listing).off('click', pickedFolder);
                    }

                    $(listing).on('click', '[data-path]', pickedFolder);


                    $(`#cloudinary-folderListing-trigger`).trigger('click');
                });
            }

            async function cloudinary_selectRootFolder(e) {
                $.mobile.showPageLoadingMsg();

                const folders = await cloudinary_getFolders();

                $.mobile.hidePageLoadingMsg();

                const selectedPath = await cloudinary_openSelectionDialog(folders);

                let newTheme, newIcon, newValue, newLabel;

                if (selectedPath) {
                    newTheme = 'c';
                    newIcon = 'refresh';
                    newValue = selectedPath;
                    newLabel = selectedPath;
                } else {
                    newTheme = 'e';
                    newIcon = 'alert';
                    newValue = null;
                    newLabel = 'no folder selected';
                }

                const uiButton = e.target.parentElement;
                const currentTheme = uiButton.dataset.theme;
                const currentIcon = uiButton.dataset.icon;

                uiButton.dataset.theme = newTheme;
                ['up', 'hover', 'down'].forEach((state) => {
                    uiButton.classList.replace(`ui-btn-${state}-${currentTheme}`, `ui-btn-${state}-${newTheme}`);
                });

                uiButton.dataset.icon = newIcon;
                uiButton.querySelector('.ui-icon').classList.replace(`ui-icon-${currentIcon}`, `ui-icon-${newIcon}`);

                const btn = uiButton.querySelector('input[type="button"]');
                const btnText = uiButton.querySelector('.ui-btn-text');
                const input = document.getElementById('root-folder-input');

                btn.value = btnText.textContent = newLabel;
                input.value = newValue;
            }

            updateMediaServiceSettings();
            $('#media_service').bind('change', updateMediaServiceSettings);

        })
	</script>
</div><!-- /page -->

<!-- second page for doing cloudinary folder listing -->
<div data-role="page" data-theme="b" id="cloudinary-folderListing">
	<div data-role="header"><h1>Choose a Folder</h1></div>
	<div data-role="content">
		<ul data-role="listview">
			<li>hello world</li>
		</ul>
	</div>
</div>

<?php require '_post.php'; ?>