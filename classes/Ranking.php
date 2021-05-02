<?php


class Ranking
{

    public $id;
    public $site_id;
    public $season_id;
    public $week;
    public $start;
    public $end;
    public $created_at;
    public $updated_at;

    public $ranks = [];

    protected $register;
    protected $dbh;
    protected $site;

    public static function getAll(Register $register)
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
            $rankings[] = new Ranking($id, $register);
        }

        return $rankings;
    }

    public static function getLatest(Register $register)
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
    public function __construct($id = false, Register $register)
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

        $this->start = $this->start ? DateTime::createFromFormat(MYSQL_DATETIME_FORMAT, $this->start.' 00:00:00') : new DateTime();
        $this->end = $this->end ? DateTime::createFromFormat(MYSQL_DATETIME_FORMAT, $this->end.' 00:00:00') : (new DateTime())->add(new DateInterval('P7D'));

        $this->created_at = $this->created_at ? DateTime::createFromFormat(MYSQL_DATETIME_FORMAT, $this->created_at) : new DateTime();
        $this->updated_at = $this->updated_at ? DateTime::createFromFormat(MYSQL_DATETIME_FORMAT, $this->updated_at) : new DateTime();
    }

    public function getRanks()
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