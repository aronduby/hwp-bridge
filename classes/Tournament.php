<?php /** @noinspection ALL */

class Tournament
{

    use Outputable;

    public ?int $id = null;
    public int $season_id;
    public string $title;
    public int $location_id;
    public string $team;
    public ?string $start = null;
    public ?string $end = null;
    public ?string $result = null;
    public ?string $note = null;
    public ?int $album_id = null;

    public Location|null $location = null;
    public array|null $games = null;

    private ?Register $register = null;
    private ?PDODB $dbh = null;
    private ?Site $site = null;

    public static function getOptionsForSelect(Register $register): array
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

    public function __construct(?int $id = null, Register $register)
    {
        $this->register = $register;
        $this->dbh = $register->dbh;
        $this->site = $register->site;

        if ($id !== null) {
            $stmt = $this->dbh->query("SELECT * FROM tournaments WHERE id=" . intval($id) . " AND site_id = " . intval($this->site->id));
            $stmt->setFetchMode(PDO::FETCH_INTO, $this);
            $stmt->fetch();
        }

        if ($this->start !== null) {
            $this->start = new DateTime($this->start);
        }
        if ($this->end !== null) {
            $this->end = new DateTime($this->end);
        }

        if (strlen($this->title) == 0)
            $this->title = 'Tournament';

        $this->location = new Location($this->location_id, $this->register);
    }

    public function getGames(): array|Game{
        if (!isset($this->games)) {
            $sql = "SELECT * FROM games WHERE tournament_id=" . intval($this->id)." ORDER BY start ASC";
            $stmt = $this->dbh->query($sql);
            $stmt->setFetchMode(PDO::FETCH_CLASS, 'Game', [null, $this->register]);

            $this->games = $stmt->fetchAll();
        }

        return $this->games;
    }

    public function hasStats(): bool
    {
        $sql = "SELECT 
			COUNT(*) 
		FROM 
			stats 
		WHERE 
           site_id = " . intval($this->site->id) . " AND 
			game_id IN (
				SELECT id FROM games WHERE site_id = " . intval($this->site->id) . " AND tournament_id=" . intval($this->id) . "
			)";
        // print_p($sql);
        return (bool)$this->dbh->query($sql)->fetch(PDO::FETCH_COLUMN);
    }

    public function getPhotoAlbum(): PhotoAlbum|false
    {
        if (isset($this->album_id))
            return new PhotoAlbum((int)$this->album_id, $this->register);
        else
            return false;
    }
}