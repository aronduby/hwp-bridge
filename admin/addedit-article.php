<?php
/** @noinspection SqlResolve */
/**
 * @var Register $register
 * @var Season $season
 * @var Site $site
 */

use Symfony\Component\DomCrawler\Crawler;

require '../common.php';

$pageTitle = 'Edit Article';
$allPlayers = $season->getPlayers();
$requires = [];
$to = new StdClass();
$switchedToExisting = false;

$setupEditArticle = function ($article) use ($site, $season, &$pageTitle, &$allPlayers, &$requires, &$to) {
    $pageTitle = 'Edit ' . $article->title;
    $requires[] = 'edit';

    $to = clone $article;
    $to->article_id = $article->id;
    $to->photos = [];
    if ($to->photo) {
        $to->photos[] = $to->photo;
    }
};

if (!empty($_POST)) {
    $dbh = PDODB::getInstance();

	try {
		$dbh->beginTransaction();

        // insert the article
        $articleInsertSql = <<<SQL
INSERT INTO articles SET
	id = :id,
	site_id = :site_id,
	season_id = :season_id,
	title = :title,
	url = :url,
	photo = :photo,
	description = :description,
	published = :published,
	created_at = NOW(),
	updated_at = NOW()
ON DUPLICATE KEY UPDATE
	id = VALUES(id),
	site_id = VALUES(site_id),
	season_id = VALUES(season_id),
	title = VALUES(title),
	url = VALUES(url),
	photo = VALUES(photo),
	description = VALUES(description),
	published = VALUES(published),
	updated_at = NOW()
SQL;
        $articleInsertStmt = $dbh->prepare($articleInsertSql);
        $articleInsertStmt->bindValue(':id', $_POST['article_id'] ?: null, PDO::PARAM_INT);
		$articleInsertStmt->bindValue(':site_id', $site->id, PDO::PARAM_INT);
		$articleInsertStmt->bindValue(':season_id', $season->id, PDO::PARAM_INT);
		$articleInsertStmt->bindValue(':title', $_POST['title']);
		$articleInsertStmt->bindValue(':url', $_POST['url']);
		$articleInsertStmt->bindValue(':photo', $_POST['photo']);
		$articleInsertStmt->bindValue(':description', $_POST['description']);
		$articleInsertStmt->bindValue(':published', $_POST['published']);

		$articleInsertStmt->execute();
		$articleId = $dbh->lastInsertId();

		// delete all of the mentions in the db
        $mentionsDeleteSql = "DELETE FROM article_player WHERE article_id = ".$articleId;
        $dbh->exec($mentionsDeleteSql);

        // save all of the posted mentions
        $mentionsInsertSql = <<<SQL
INSERT INTO article_player SET
	site_id = :site_id,
	player_id = :player_id,
	season_id = :season_id,
	article_id = :article_id,
	highlight = :highlight,
	created_at = NOW(),
	updated_at = NOW()
ON DUPLICATE KEY UPDATE 	
	site_id = VALUES(site_id),
	player_id = VALUES(player_id),
	season_id = VALUES(season_id),
	article_id = VALUES(article_id),
	highlight = VALUES(highlight),
	updated_at = NOW()
SQL;
        $mentionsInsertStmt = $dbh->prepare($mentionsInsertSql);
		$mentionsInsertStmt->bindValue(':site_id', $site->id, PDO::PARAM_INT);
		$mentionsInsertStmt->bindValue(':season_id', $season->id, PDO::PARAM_INT);
		$mentionsInsertStmt->bindValue(':article_id', $articleId, PDO::PARAM_INT);
		$mentionsInsertStmt->bindParam(':player_id', $pid, PDO::PARAM_INT);
		$mentionsInsertStmt->bindParam(':highlight', $highlighted);

		if (count($_POST['mentions'])) {
			$playersById = [];
			foreach($allPlayers as $player) {
				$playersById[$player->id] = $player;
			}

            foreach ($_POST['mentions'] as $pid => $mention) {
            	if (!$mention)
            		continue;

            	$name = $playersById[$pid]->name;
				$highlighted = '<p>'.str_replace($name, '<strong>'.$name.'</strong>', $mention).'</p>';
				$mentionsInsertStmt->execute();
            }
		}

		$dbh->commit();

		$isNew = $_POST['article_id'] != $articleId;
		if ($isNew) {
            // trigger article event/notification
			$path = 'php '.ARTISAN_PATH.' events:manual-article-import '. $articleId;
            exec($path);
        }

        header("Location: articles.php");
        die();

	} catch (\Exception $e) {
		$dbh->rollback();
        $form_errors = $e->getMessage();

		$pageTitle = 'Import Article';
		$requires = ['edit'];

		$to->article_id = $_POST['article_id'];
		$to->title = $_POST['title'];
		$to->description = $_POST['description'];
		$to->photo = $_POST['photo'];
		$to->photos = [ $_POST['photo'] ];
		$to->published = new DateTime($_POST['published']);
		$to->url = $_POST['url'];

		$to->mentions = [];
		foreach($_POST['mentions'] as $pid => $mention) {
			if (!$mention) continue;
			$to->mentions[$pid] = [
				'highlight' => $mention
			];

		}
	}

}
elseif (array_key_exists('article_id', $_GET) && $_GET['article_id']) {

	$article = new Article(isset($_GET['article_id']) ? $_GET['article_id'] : null, $register);
    $setupEditArticle($article);

}
elseif (array_key_exists('url', $_GET) && $_GET['url']) {

    $pageTitle = 'Import Article';

	try {

		$to->importUrl = $_GET['url'];
        // $content = file_get_contents($_GET['url']);
        // if (!$content) {
        // 	throw new Exception('Could not load content from that url. Check your entry and try again');
        // }

        $httpClient = new GuzzleHttp\Client();
        $response = $httpClient->get($_GET['url']);
		$content = $response->getBody()->__toString();
        $ogConsumer = new \Fusonic\OpenGraph\Consumer();
		$og = $ogConsumer->loadHtml($content, $_GET['url']);

        // check for the url in the DB and load it with a notice if it exists
		$existing = Article::findByUrl($og->url, $register);
		if ($existing) {
			$switchedToExisting = true;
			$setupEditArticle($existing);

		} else {
            $requires = ['url', 'edit'];
            $allPlayers = $season->getPlayers();

            // <editor-fold desc="OG Data">
            $to->article_id = null;
            $to->url = $og->url;
            $to->title = $og->title;
            $to->description = $og->description;

            $to->photos = array_map(function($img) {
                return strlen($img->secureUrl) ? $img->secureUrl : $img->url;
            }, $og->images);

            $to->photo = count($to->photos) ? $to->photos[0] : '';

            $to->published = $og->updatedTime ?: new DateTime();
            // </editor-fold>

            // <editor-fold desc="Mentions">
            $to->mentions = [];

            $nameCrawler = new Crawler($content);
            foreach($allPlayers as $player) {

                $playerText = $nameCrawler->filterXPath('//*[contains(text(), "'. $player->name .'")]')
                    // make sure its not in a script tag (looking at you mlive)
                    ->reduce(function(Crawler $node, $i) {
                        return $node->nodeName() !== 'script';
                    });

                if ($playerText->count()) {
                    $to->mentions[$player->id] = [
                        // 'player' => $player,
                        'highlight' => excerptAndHighlight($playerText->text(), $player->name),
                    ];
                }
            }
            // </editor-fold>
		}

	} catch (Exception $e) {
		$form_errors = $e->getMessage();
		$requires = ['url'];

		$to->importUrl = $_GET['url'];
	}

}
else {
    $pageTitle = 'Import Article';
    $requires[] = 'url';
    $to->importUrl = '';
}

require '_pre.php';
?>

	<div data-role="page" data-theme="b" id="page--addedit-article" class="add-existing-players">
		<link rel="stylesheet" href="css/jquery-mobile-overrides.css"/>

		<div data-role="header" data-theme="b">
			<a href="index.php" data-rel="back" title="back" data-icon="back" data-iconpos="notext">back</a>
			<h1><?= $pageTitle ?></h1>
		</div><!-- /header -->

		<div data-role="content">
            <?php
            include '_form-errors.php';

            if ($switchedToExisting) {
            	?>
	            <div data-role="content" data-theme="e" class="ui-shadow ui-alert">
		            That article is already in the system. Here's the edit screen.
	            </div>
	            <?php
            }

            foreach($requires as $file) {
            	$path = './addedit-article-'.$file.'.php';
            	require($path);
            }
            ?>
		</div><!-- /content -->

	</div><!-- /page -->

<?php require '_post.php'; ?>