<?php /** @noinspection SqlResolve */

class Article
{

    use Outputable;

    public ?int $id = null;
    public string $title;
    public string $url;
    public Photo|null $photo = null;
    public string $description;
    public ?string $published = null;
    public array $mentions = [];

    public ?Register $register = null;
    private ?PDODB $dbh = null;
    private ?Site $site = null;

    public static function getAll(Register $register): array
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

    public static function findByUrl(string $url, Register $register): ?Article {
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

    public function __construct(?int $article_id = null, Register $register)
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

    private function getMentions(): array
    {
        if ($this->id === null) return [];
        
        $stmt = $this->dbh->query("SELECT player_id, highlight FROM article_player WHERE article_id=" . $this->id);
        $mentions = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $mentions[$r['player_id']] = [
                'player' => new Player((int)$r['player_id'], $this->register),
                'highlight' => $r['highlight']
            ];
        }

        return $mentions;
    }


}
