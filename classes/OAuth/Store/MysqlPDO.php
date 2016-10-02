<?php

namespace OAuth\Store;

class MysqlPDO extends \OAuth\Store implements \Serializable {

	private $dbh;

	private $db_type;
	private $db_server;
	private $db_name;
	private $db_user;
	private $db_passwd;

	public function __construct($db_type, $db_server, $db_user, $db_passwd, $db_name){
		$this->db_type = $db_type;
		$this->db_server = $db_server;
		$this->db_user = $db_user;
		$this->db_passwd = $db_passwd;
		$this->db_name = $db_name;
	}

	private function connect(){
		try {
			$dsn = $this->db_type.":host=".$this->db_server.";dbname=".$this->db_name;

			$this->dbh = new \PDO( $dsn, $this->db_user, $this->db_passwd);
			$this->dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

		} catch(\PDOException $e){
			print "Error!: " . $e->getMessage() . "\n" ;
			die();
		}
	}

	public function serialize(){
		$this->dbh = null;
		$data = array(
			'user' => $this->user,
			'service' => $this->service,
			'cache' => $this->cache,
			'db' => array(
				'type' => $this->db_type,
				'server' => $this->db_server,
				'name' => $this->db_name,
				'user' => $this->db_user,
				'passwd' => $this->db_passwd
			)
		);

		return serialize($data);
	}

	public function unserialize($data){
		$data = unserialize($data);

		$this->user = $data['user'];
		$this->service = $data['service'];
		$this->cache = $data['cache'];
		$this->db_type = $data['db']['type'];
		$this->db_server = $data['db']['server'];
		$this->db_name = $data['db']['name'];
		$this->db_user = $data['db']['user'];
		$this->db_passwd = $data['db']['passwd'];

		// $this->connect();		
	}

	public function saveToken($token, $secret, $type, $ttl = null, $additional = array()){
		if(!isset($this->dbh)) $this->connect();

		if(!isset($this->user)){
			throw new \OAuth\Exception('You must call setUser for the store before attempting to save any tokens');
		}
		if(!isset($this->service)){
			throw new \OAuth\Exception('You must call setService for the store before attempting to save any tokens');
		}
		
		if(isset($ttl)){
			$ttl = time() + $ttl;
			$ttl_str = date('Y-m-d G:i:s', $ttl);
		} else {
			$ttl_str = null;
		}

		if(count($additional)>0) 
			$additional = json_encode($additional);
		else
			$additional = null;

		
		$sets = "user_id = ".$this->dbh->quote($this->user).", 
			integration_id = ".$this->dbh->quote($this->service).", 
			token = ".$this->dbh->quote($token).", 
			token_secret = ".$this->dbh->quote($secret).", 
			token_type = ".$this->dbh->quote($type).", 
			token_ttl = ".$this->dbh->quote($ttl_str).", 
			additional = ".$this->dbh->quote($additional);

		$sql = "INSERT INTO user_to_integration SET ".$sets." ON DUPLICATE KEY UPDATE ".$sets;
		$this->dbh->exec($sql);

		return $this->createToken($token, $secret, $type, $ttl, $additional);
	}

	public function getTokens($type = null){
		if(!isset($this->dbh)) $this->connect();
		
		if(!isset($this->user)){
			throw new \OAuth\Exception('You must call setUser for the store before attempting to get any tokens');
		}
		if(!isset($this->service)){
			throw new \OAuth\Exception('You must call setService for the store before attempting to get any tokens');
		}

		$sql = "SELECT token, token_secret AS secret, token_type AS type, token_ttl AS ttl, additional FROM user_to_integration WHERE user_id=".$this->dbh->quote($this->user)." AND integration_id=".$this->dbh->quote($this->service);

		if($type === null){
			
			$return = array();
			$stmt = $this->dbh->query($sql);
			while($r = $stmt->fetch(\PDO::FETCH_ASSOC)){
				$return[] = $this->createToken($r['token'], $r['secret'], $r['type'], $r['ttl'], $r['additional']);
			}
			return $return;
		
		} elseif(is_string($type)){
			$sql .= " AND token_type=".$this->dbh->quote($type);

			$r = $this->dbh->query($sql)->fetch(\PDO::FETCH_ASSOC);
			if($r != false){
				return $this->createToken($r['token'], $r['secret'], $r['type'], $r['ttl'], $r['additional']);
			} else {
				return $this->createToken();
			}

		} elseif(is_array($type)){
			$where = array();
			foreach($type as $t){
				$where[] = " token_type = ".$this->dbh->quote($t);
			}
			$sql .= " AND (".implode(" OR ", $where).") LIMIT 1";
			
			$r = $this->dbh->query($sql)->fetch(\PDO::FETCH_ASSOC);
			if($r != false){
				return $this->createToken($r['token'], $r['secret'], $r['type'], $r['ttl'], $r['additional']);
			} else {
				return $this->createToken();
			}

		} else {
			throw new \OAuth\Exception('Argument to getTokens needs to be either null, a string, or an array');
		}
	}

	public function removeTokens($type = null){
		if(!isset($this->dbh)) $this->connect();
		
		if(!isset($this->user)){
			throw new \OAuth\Exception('You must call setUser for the store before attempting to get any tokens');
		}
		if(!isset($this->service)){
			throw new \OAuth\Exception('You must call setService for the store before attempting to get any tokens');
		}

		$sql = "DELETE FROM user_to_integration WHERE user_id=".$this->dbh->quote($this->user)." AND integration_id=".$this->dbh->quote($this->service);
		if($type != null)
			$sql .= " AND token_type=".$this->dbh->quote($type);

		$this->dbh->exec($sql);

	}

	public function saveSerialized($obj){
		if($this->dbh===null) $this->connect();
		
		$obj2 = clone $obj;

		$serial_obj = base64_encode(serialize($obj2));
		$id = md5($this->service.$this->user.uniqid());

		$sql = "INSERT INTO temp_serialized SET 
			id=".$this->dbh->quote($id).", 
			serialized=".$this->dbh->quote($serial_obj);
		$this->dbh->exec($sql);

		return $id;
	}

	public function restoreFromSerialized($id){
		if(!isset($this->dbh)) $this->connect();

		$sql = "SELECT serialized FROM temp_serialized WHERE id=".$this->dbh->quote($id);
		$serial_obj = $this->dbh->query($sql)->fetch(\PDO::FETCH_COLUMN);

		if($serial_obj != false){
			return unserialize(base64_decode($serial_obj));
		} else {
			throw new \OAuth\Exception('Could not restore a state with the information supplied');
		}
	}


	private function createToken($token = false, $secret = false, $type = false, $ttl = false, $additional = array()){
		return new \OAuth\Token($token, $secret, $type, $ttl, $additional);
	}

}

?>