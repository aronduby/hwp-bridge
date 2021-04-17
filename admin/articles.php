<?php
/**
 * @var Register $register
 */

require '../common.php';

$articles = Article::getAll($register);

require '_pre.php';
?>

<div data-role="page" data-theme="b">

	<div data-role="header" data-theme="b">
		<a href="index.php" title="back" data-icon="home" data-iconpos="notext" data-direction="reverse">back</a>
		<a href="addedit-article.php" title="add article" class="ui-btn-right" data-icon="plus" data-iconpos="notext">add</a>
		<h1>Article List</h1>
	</div><!-- /header -->

	<div data-role="content">	
		<?php
        include '_alerts.php';

		$cur_display_order = false;
		if ($articles !== false && count($articles) > 0) {
			?>
			<ul data-role="listview" data-theme="d" data-divider-theme="d" data-split-theme="d" data-split-icon="arrow-r">
				<li data-role="list-divider"></li>
				<?php
				foreach($articles as $a) {
					?>
					<li>
						<a href="addedit-article.php?article_id=<?=$a->id ?>" title="edit article">
							<img class="cover" src="<?= $a->photo ?>" onerror="this.src='<?= FALLBACK_IMG_SRC ?>';" />
							<h3><?= $a->title ?></h3>
							<p><?= $a->published->format(USER_DATETIME_FORMAT) ?></p>
						</a>
						<a href="<?= $a->url ?>" target="_blank" title="view article">view article</a>
					</li>
					<?php
				}
				?>
			</ul>
			<?php
		} else {
			print '<p>No articles found</p>';
		}
		?>
	</div><!-- /content -->

</div><!-- /page -->

<?php require '_post.php'; ?>