<?php

class Config {

	private static $dbh;
	private static $site;

	private static $data = [];

	public static function setDbh(PDO $dbh){
		self::$dbh = $dbh;
	}

	public static function setSite(Site $site) {
	    self::$site = $site;
    }

	public static function get($key){
		if($key != 'dbh'){
			if(isset(self::$data[$key]))
				return self::$data[$key];
			else {
				self::$data[$key] = self::$dbh->query("SELECT value FROM config WHERE title=".self::$dbh->quote($key))->fetch(PDO::FETCH_COLUMN);
				return self::$data[$key];
			}
		}
	}

	public static function set($key, $val){
		self::$data[$key] = $val;
		self::$dbh->query("REPLACE INTO config SET title=".self::$dbh->quote($key).", value=".self::$dbh->quote($val));
	}

    /**
     * Config constructor.
     * @param PDO $dbh
     * @param Site $site
     */
	public function __construct(\PDO $dbh, Site $site)
	{
		self::setDbh($dbh);
		self::setSite($site);
	}

	public function __get($name)
	{
		$name = strtoupper($name);
		return self::get($name);
	}

	function __set($name, $value)
	{
		$name = strtoupper($name);
		self::set($name, $value);
	}
}

?>