<?php


class Rank
{

    public ?int $id = null;
    public int $site_id;
    public int $season_id;
    public int $rank;
    public string $team;
    public bool $tied;
    public bool $self;
    public float $points;

    protected ?Register $register = null;
    protected ?PDODB $dbh = null;
    protected ?Site $site = null;

    /**
     * Ranking constructor.
     * @param int $id
     * @param Register $register
     */
    public function __construct(?int $id = null, Register $register)
    {
        $this->register = $register;
        $this->dbh = $register->dbh;
        $this->site = $register->site;

        if ($id != false) {
            $stmt = $this->dbh->query("SELECT id, site_id, season_id, ranking_id, rank, team, tied, self, points FROM ranks WHERE site_id = " . intval($this->site->id) . " AND id=" . intval($id));
            $stmt->setFetchMode(PDO::FETCH_INTO, $this);
            if (!$stmt->fetch()) {
                throw new Exception('Ranking Not Found');
            }
        }
    }


}
