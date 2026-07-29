<?php

class Config {

	private static ?PDO $dbh = null;
	private static ?Site $site = null;

	private static array $data = [];

	public static function setDbh(PDO $dbh): void{
		self::$dbh = $dbh;
	}

	public static function setSite(Site $site): void {
	    self::$site = $site;
    }

	public static function get(string|int|float $key){
		if($key != 'dbh'){
			if(isset(self::$data[$key]))
				return self::$data[$key];
			else {
				self::$data[(string)$key] = self::$dbh->query("SELECT value FROM config WHERE title=".self::$dbh->quote((string)$key))->fetch(PDO::FETCH_COLUMN);
				return self::$data[$key];
			}
		}
	}

	public static function set(string|int|float $key, mixed $val): void{
		self::$data[(string)$key] = $val;
		self::$dbh->query("REPLACE INTO config SET title=".self::$dbh->quote((string)$key).", value=".(is_string($val) ? self::$dbh->quote($val) : $val));
	}

    /**
     * Config constructor.
     * @param PDO $dbh
     * @param Site $site
     */
	public function __construct(PDO $dbh, Site $site)
	{
		self::setDbh($dbh);
		self::setSite($site);
	}

	public function __get(string $name): mixed
	{
		$name = strtoupper($name);
		return self::get((string)$name);
	}

	public function __set(string|int|float $name, mixed $value): void
	{
		$name = strtoupper((string)$name);
		self::set((string)$name, $value);
	}
}
