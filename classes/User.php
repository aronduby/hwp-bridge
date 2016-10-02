<?php

class User {

	public $user_id = 0;
	public $email;
	public $name;
	public $email_verified;
	public $verification_method;
	public $verification_key;
	public $auto_checkin;
	
	public $logged_in = false;
	public $integrations = [];
	public $error;

	private $PasswordLib;
	private $db_fields = ['user_id', 'email', 'name', 'email_verified', 'verification_method', 'verification_key', 'auto_checkin'];


	public function __construct($id = null){
		if($id === null){
			if(isset($_COOKIE['id_hash'])){
				$this->loginFromCookie($_COOKIE['id_hash']);
			}
		} else {
			$this->user_id = $id;
			$this->getUserData();
		}
	}

	public function __sleep(){
		$this->PasswordLib = null;
		
		$return = array_merge(["logged_in", "integrations"], $this->db_fields);
		return $return;
	}

	public function save(){
		$dbh = PDODB::getInstance();
		
		$sql = "INSERT INTO user 
		SET 
			user_id = :user_id, 
			email = :email, 
			name = :name,
			email_verified = :email_verified, 
			verification_method = :verification_method, 
			verification_key = :verification_key,
			auto_checkin = :auto_checkin
		ON DUPLICATE KEY UPDATE
			user_id = LAST_INSERT_ID(user_id), 
			email = VALUES(email),
			name = VALUES(name),
			email_verified = VALUES(email_verified), 
			verification_method = VALUES(verification_method), 
			verification_key = VALUES(verification_key),
			auto_checkin = VALUES(auto_checkin)";
		
		$data = array_intersect_key( get_object_vars($this), array_combine($this->db_fields, array_fill(0, count($this->db_fields), 'hi')) );
		if($data['user_id'] == 0) $data['user_id'] = null;

		//try{
			$insert_stmt = $dbh->prepare($sql);
			$insert_stmt->execute($data);
			$this->user_id = $dbh->lastInsertId();
			return true;

		//} catch(PDOException $e){
		//	$this->error = $e->getMessage();
		//	return false;
		//}
	}

	public function registerUser($email, $password, $name = false, $remember_me = false){
		$dbh = PDODB::getInstance();
		$this->getPasswordLib();

		$password = $this->PasswordLib->createPasswordHash($password);
		
		$sql = "INSERT INTO user SET email=".$dbh->quote($email).", password=".$dbh->quote($password);
		if($name !== false)
			$sql .= ", name=".$dbh->quote($name);
		
		try{		
			$user_id = $dbh->exec($sql);
			if($user_id != false){
				$this->user_id = $dbh->lastInsertId();
				$this->logged_in = true;
				$this->getUserData();

				if($remember_me !== false)
					$this->saveLoginCookie();

				return true;
			} else {
				throw new Exception('Could not add you at this time, please try again later');
			}

		} catch(PDOException $e){
			switch($e->errorInfo[1]){
				case '1062':
					$this->error = 'An account already exists with that email. Did you mean to <a href="login" title="login">login</a>?';
					break;

				default:
					$this->error = $e->getMessage();
					break;
			}
			return false;
		}		
	}

	public function setNewPassword($password){
		$dbh = PDODB::getInstance();
		$this->getPasswordLib();

		$password = $this->PasswordLib->createPasswordHash($password);
		
		$sql = "UPDATE user SET password = ".$dbh->quote($password)." WHERE user_id = ".intval($this->user_id);		
		return (bool)$dbh->exec($sql);
	}

	public function loginFromRegistry($email, $password, $remember_me = false){		
		$dbh = PDODB::getInstance();
		$this->getPasswordLib();

		$sql = "SELECT user_id, password FROM user WHERE email=".$dbh->quote($email);
		$data = $dbh->query($sql)->fetch(PDO::FETCH_OBJ);

		$matched = $this->PasswordLib->verifyPasswordHash($password, $data->password);
		
		if($matched !== false){
			$this->user_id = $data->user_id;
			$this->logged_in = true;
			$this->getUserData();

			if($remember_me !== false)
				$this->saveLoginCookie();

			return true;

		} else {
			$this->error = 'User not found with that username/password combination.';
			return false;
		}	
	}

	public function loginFromIntegration($integration_user_id, $integration_id){
		$dbh = PDODB::getInstance();

		$sql = "SELECT
			user_id
		FROM
			user_to_integration
		WHERE
			id=".$dbh->quote($integration_user_id)."
			AND integration_id=".intval($integration_id);

		$user_id = $dbh->query($sql)->fetch(PDO::FETCH_COLUMN);
		if($user_id !== false){
			$this->user_id = $user_id;
			$this->logged_in = true;
			$this->getUserData();

			$this->saveLoginCookie();

			return true;

		} else {
			$this->error = 'User not found for that integration';
			return false;
		}
	}

	public function loginFromCookie($val){
		$dbh = PDODB::getInstance();

		$sql = "SELECT 
			u.user_id 
		FROM 
			user_login_cookie ulc 
			LEFT JOIN user u USING(user_id) 
		WHERE 
			ulc.id_hash=".$dbh->quote($val)." 
			AND ulc.expires > '".date('Y-m-d G:i:s', time())."'";

		$user_id = $dbh->query($sql)->fetch(PDO::FETCH_COLUMN);
		if($user_id !== false){
			$this->user_id = $user_id;
			$this->logged_in = true;
			$this->getUserData();
			$this->saveLoginCookie();

			return true;

		} else {
			$this->error = 'Saved login is no longer valid.';
			return false;
		}
	}

	public function setVerificationKey(){
		$dbh = PDODB::getInstance();
		
		$token = $this->generateUniqueToken(32, 'user', 'verification_key');
		if($dbh->exec("UPDATE user SET verification_key=".$dbh->quote($token)." WHERE user_id=".intval($this->user_id)) !== false)
			return $token;
		else
			return $false;
	}

	public function getUserIdForVerificationKey($key){
		$dbh = PDODB::getInstance();

		return $dbh->query("SELECT user_id FROM user WHERE verification_key = ".$dbh->quote($key))->fetch(PDO::FETCH_COLUMN);
	}

	public function getUserData(){
		$dbh = PDODB::getInstance();

		$sql = "SELECT ".implode(', ', $this->db_fields)." FROM user WHERE user_id=".intval($this->user_id);
		$stmt = $dbh->query($sql);
		$stmt->setFetchMode(PDO::FETCH_INTO, $this);
		$stmt->fetch();

		$stmt = $dbh->query("SELECT integration_id, auto_checkin, id FROM user_to_integration WHERE user_id=".intval($this->user_id));
		while($r = $stmt->fetch(PDO::FETCH_OBJ)){
			$this->integrations[$r->integration_id] = $r;
		}
	}

	public function checkIn(Stop $stop){
		$dbh = PDODB::getInstance();

		$rows = $dbh->exec("INSERT INTO checkin SET user_id=".intval($this->user_id).", stop_id=".intval($stop->stop_id));
		if($rows !== false){
			return $dbh->lastInsertId();
		} else {
			return false;
		}
	}

	private function saveLoginCookie(){
		$dbh = PDODB::getInstance();
		$this->getPasswordLib();
		
		$hash = $this->generateUniqueToken(32, 'user_login_cookie', 'id_hash', true);
		$expires = date('Y-m-d G:i:s', strtotime('+1 week'));
		$dbh->exec("REPLACE INTO user_login_cookie SET user_id=".intval($this->user_id).", id_hash=".$dbh->quote($hash).", expires='".$expires."'");

		setcookie('id_hash', $hash, strtotime($expires));
	}

	private function getPasswordLib(){
		if(!$this->PasswordLib instanceof \PasswordLib\PasswordLib){
			require_once SITE_ROOT.'/classes/PasswordLib.phar';
			$this->PasswordLib = new \PasswordLib\PasswordLib;
		}
	}

	private function generateUniqueToken($length, $tbl, $fld, $hash = false){
		$dbh = PDODB::getInstance();
		$this->getPasswordLib();

		$unique_stmt = $dbh->prepare("SELECT COUNT(*) FROM ".$tbl." WHERE ".$fld."=:token");
		$unique_stmt->bindParam(':token', $token);
		$unique_stmt->setFetchMode(PDO::FETCH_COLUMN, 0);

		$keep_going = true;
		while($keep_going === true){
			$token = $this->PasswordLib->getRandomToken($length);
			if($hash === true)
				$token = $this->PasswordLib->createPasswordHash($token);
			
			$unique_stmt->execute();
			$total = $unique_stmt->fetch();
			if($total == '0'){
				$keep_going = false;
			}
		}

		// make sure there aren't ./
		$token = str_replace(['.','/'], '0', $token);

		return $token;
	}

}

?>