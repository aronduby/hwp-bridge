<?php /** @noinspection ALL */

class Tournament
{

    use Outputable;

    public $id;
    public $season_id;
    public $title;
    public $location_id;
    public $team;
    public $start;
    public $end;
    public $result;
    public $note;
    public $album_id;

    public $location;
    public $games;

    private $register;
    private $dbh;
    private $site;

    public static function getOptionsForSelect(Register $register)
    {
        $dbh = $register->dbh;
        $sql = "
            SELECT 
                id, 
               CONCAT(team,' - ',IFNULL(title, 'Tournament'),' on ',DATE_FORMAT(start,'%m/%e')) AS title 
            FROM 
                 tournaments 
            WHERE
                site_id=".intval($register->site->id)."
                AND season_id=".intval($register->season->id)." 
            ORDER BY 
                title";
        $stmt = $dbh->query($sql);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function __construct($id = null, Register $register)
    {
        $this->register = $register;
        $this->dbh = $register->dbh;
        $this->site = $register->site;

        if ($id !== null) {
            $stmt = $this->dbh->query("SELECT * FROM tournaments WHERE id=" . intval($id) . " AND site_id = " . intval($this->site->id));
            $stmt->setFetchMode(PDO::FETCH_INTO, $this);
            $stmt->fetch();
        }

        $this->start = new DateTime($this->start);
        $this->end = new DateTime($this->end);

        if (strlen($this->title) == 0)
            $this->title = 'Tournament';

        $this->location = new Location($this->location_id, $this->register);
    }

    public function getGames()
    {
        if (!isset($this->games)) {
            $sql = "SELECT * FROM games WHERE site_id = " . intval($this->site->id) . " AND  tournament_id=" . intval($this->id);
            $stmt = $this->dbh->query($sql);
            $stmt->setFetchMode(PDO::FETCH_CLASS, 'Game', [null, $this->register]);

            $this->games = $stmt->fetchAll();
        }

        return $this->games;
    }

    public function hasStats()
    {
        $sql = "SELECT 
			COUNT(*) 
		FROM 
			stats 
		WHERE 
           site_id = " . intval($site->id) . " AND 
			game_id IN (
				SELECT id FROM games WHERE site_id = " . intval($site->id) . " AND tournament_id=" . intval($this->id) . "
			)";
        // print_p($sql);
        return (bool)$this->dbh->query($sql)->fetch(PDO::FETCH_COLUMN);
    }

    public function getPhotoAlbum()
    {
        if (isset($this->album_id))
            return new PhotoAlbum($this->album_id, $this->register);
        else
            return false;
    }
}

?>