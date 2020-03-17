<?php /** @noinspection SqlResolve */

class Site
{
    public $id;
    public $domain;
    public $created_at;
    public $updated_at;

    public static $ngrok = false;

    private $register;
    private $dbh;

    public static function parseHost(string $host) : string {
        // admin.hudsonvillewaterpolo.com
        // admin.boys.hudsonvillewaterpolo.com
        // admin.girls.hudsonvillewaterpolo.com
        //
        $host = explode('.', $host);
        $domain = $host[ count($host) - 2];

        if (count($host) >= 3) {
            $sub = $host[ count($host) - 3];
            if ($sub !== 'admin') {
                $domain = $sub .'.'. $domain;
            }
        }

        if (strpos($domain, 'ngrok') !== false) {
            $domain = 'girls.hudsonvillewaterpolo';
            self::$ngrok = implode('.', $host);
        }

        return $domain;
    }

    public function __construct(string $domain, Register $register) {
        $this->register = $register;
        $this->dbh = $register->dbh;

        $sql = "SELECT * FROM sites WHERE domain = " .$this->dbh->quote($domain);
        $stmt = $this->dbh->query($sql);
        $stmt->setFetchMode(PDO::FETCH_INTO, $this);
        if(!$stmt->fetch()){
            throw new Exception('No Site Found');
        }
    }

    /**
     * Gets all of the players, regardless of season
     *
     * @return array
     */
    public function getAllPlayers() {
        $stmt = $this->dbh->prepare("SELECT * FROM players WHERE site_id = :site_id");
        $stmt->bindValue(':site_id', $this->id, PDO::PARAM_INT);
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'Player', [null, $this->register]);

        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get's the settings data from site storage
     */
    public function getSettings() {
        $file = SETTINGS_PATH.'/'.$this->domain.'.json';
        $siteSettings = file_get_contents($file);
        return json_decode($siteSettings);
    }

    /**
     * @param $settings
     * @return false|int
     */
    public function saveSettings($settings) {
        return file_put_contents(SETTINGS_PATH.'/'.$this->domain.'.json', json_encode($settings));
    }

}