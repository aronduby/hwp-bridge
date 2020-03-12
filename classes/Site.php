<?php

class Site
{
    public $id;
    public $domain;
    public $created_at;
    public $updated_at;

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

        if ($domain === 'ngrok') {
            $domain = 'hudsonvillewaterpolo';
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

}