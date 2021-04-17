<?php /** @noinspection SqlResolve */

class Article
{

    use Outputable;

    public $id;
    public $title;
    public $url;
    public $photo;
    public $description;
    public $published;
    public $mentions = [];

    public $dbh;
    private $register;
    private $site;

    public static function getAll(Register $register)
    {
        $dbh = $register->dbh;

        $siteId = intval($register->site->id);
        $seasonId = intval($register->season->id);
        $sql = <<<SQL
SELECT 
       id, title, url, photo, description, published 
FROM
     articles 
WHERE
    site_id = $siteId
    AND season_id = $seasonId
ORDER BY
    published DESC
SQL;

        $stmt = $dbh->query($sql);
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'Article', [null, $register]);
        return $stmt->fetchAll();
    }

    public static function findByUrl(string $url, Register $register) {
        $dbh = $register->dbh;

        $siteId = intval($register->site->id);
        $seasonId = intval($register->season->id);
        $sql = <<<SQL
SELECT 
       id, title, url, photo, description, published 
FROM
     articles 
WHERE
    site_id = $siteId
    AND season_id = $seasonId
    AND url = {$dbh->quote($url)}
ORDER BY
    published DESC
SQL;

        $stmt = $dbh->query($sql);
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'Article', [null, $register]);
        return $stmt->fetch();
    }

    public function __construct($article_id = null, Register $register)
    {
        $this->register = $register;
        $this->dbh = $register->dbh;
        $this->site = $register->site;

        if ($article_id !== null) {
            $stmt = $this->dbh->query("SELECT id, title, url, photo, description, published FROM articles WHERE site_id = " . intval($this->site->id) . " AND id=" . intval($article_id));
            $stmt->setFetchMode(PDO::FETCH_INTO, $this);
            if (!$stmt->fetch()) {
                throw new Exception('Article Not Found');
            }
        }

        $this->published = DateTime::createFromFormat(MYSQL_DATETIME_FORMAT, $this->published);

        $this->mentions = $this->getMentions();
    }

    private function getMentions()
    {
        $stmt = $this->dbh->query("SELECT player_id, highlight FROM article_player WHERE article_id=" . $this->id);
        $mentions = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $mentions[$r['player_id']] = [
                'player' => new Player($r['player_id'], $this->register),
                'highlight' => $r['highlight']
            ];
        }

        return $mentions;
    }


}

?>