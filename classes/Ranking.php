<?php


class Ranking
{

    public ?int $id = null;
    public int $site_id;
    public int $season_id;
    public int|null $week = null;
    public ?string $start = null;
    public ?string $end = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public array|null $ranks = null;

    protected ?Register $register = null;
    protected ?PDODB $dbh = null;
    protected ?Site $site = null;

    public static function getAll(Register $register): array
    {
        $dbh = $register->dbh;

        $siteId = intval($register->site->id);
        $seasonId = intval($register->season->id);
        $sql = <<<SQL
SELECT 
       id 
FROM
     rankings
WHERE
    site_id = $siteId
    AND season_id = $seasonId
ORDER BY
    week DESC
SQL;

        $stmt = $dbh->query($sql);
        $rankings = [];
        foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $rankings[] = new Ranking((int)$id, $register);
        }

        return $rankings;
    }

    public static function getLatest(Register $register): ?Ranking
    {
        $dbh = $register->dbh;

        $siteId = intval($register->site->id);
        $seasonId = intval($register->season->id);
        $sql = <<<SQL
SELECT 
    id, site_id, season_id, week, start, end, created_at, updated_at 
FROM 
     rankings 
WHERE 
    site_id = $siteId 
    AND season_id = $seasonId
ORDER BY
    week DESC
LIMIT 1
SQL;

        $stmt = $dbh->query($sql);
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'Ranking', [false, $register]);
        return $stmt->fetch();
    }


    /**
     * Ranking constructor.
     * @param int $id
     * @param Register $register
     */
    public function __construct(int|false|null $id = false, Register $register)
    {
        $this->register = $register;
        $this->dbh = $register->dbh;
        $this->site = $register->site;

        if ($id !== false) {
            $stmt = $this->dbh->query("SELECT id, site_id, season_id, week, start, end, created_at, updated_at FROM rankings WHERE site_id = " . intval($this->site->id) . " AND id=" . intval($id));
            $stmt->setFetchMode(PDO::FETCH_INTO, $this);
            if (!$stmt->fetch()) {
                throw new Exception('Ranking Not Found');
            }
        }

        if ($this->id) {
            $this->ranks = $this->getRanks();
        }

        if ($this->start !== null) {
            $this->start = DateTime::createFromFormat(MYSQL_DATETIME_FORMAT, $this->start.' 00:00:00');
        }
        if ($this->end !== null) {
            $this->end = DateTime::createFromFormat(MYSQL_DATETIME_FORMAT, $this->end.' 00:00:00');
        } else {
            $this->end = (new DateTime())->add(new DateInterval('P7D'));
        }

        if ($this->created_at !== null) {
            $this->created_at = DateTime::createFromFormat(MYSQL_DATETIME_FORMAT, $this->created_at);
        } else {
            $this->created_at = new DateTime();
        }
        if ($this->updated_at !== null) {
            $this->updated_at = DateTime::createFromFormat(MYSQL_DATETIME_FORMAT, $this->updated_at);
        } else {
            $this->updated_at = new DateTime();
        }
    }

    public function getRanks(): array
    {
        $dbh = $this->register->dbh;

        $sql = <<<SQL
SELECT 
    id, site_id, season_id, ranking_id, rank, team, tied, self, points
FROM
    ranks 
WHERE
    ranking_id = $this->id
ORDER BY
    rank ASC
SQL;

        $stmt = $dbh->query($sql);
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'Rank', [false, $this->register]);
        return $stmt->fetchAll();
    }


}