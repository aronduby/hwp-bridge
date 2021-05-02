<?php


class Rank
{

    public $id;
    public $site_id;
    public $season_id;
    public $rank;
    public $team;
    public $tied;
    public $self;
    public $points;

    protected $register;
    protected $dbh;
    protected $site;

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
            $stmt = $this->dbh->query("SELECT id, site_id, season_id, ranking_id, rank, team, tied, self, points FROM ranks WHERE site_id = " . intval($this->site->id) . " AND id=" . intval($id));
            $stmt->setFetchMode(PDO::FETCH_INTO, $this);
            if (!$stmt->fetch()) {
                throw new Exception('Ranking Not Found');
            }
        }
    }


}