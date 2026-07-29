<?php /** @noinspection SqlResolve */

class User {

	public ?int $user_id = null;
	public string $email;
	public string $name;
	public bool $email_verified;
	public string $verification_method;
	public string $verification_key;
	public bool $auto_checkin;
	
	public bool $logged_in = false;
	public array $integrations = [];
	public ?string $error = null;

	private PasswordLib|null $PasswordLib = null;
	private array $db_fields = ['user_id', 'email', 'name', 'email_verified', 'verification_method', 'verification_key', 'auto_checkin'];


	public function __construct(?int $id = null) {
		if($id === null){
			if(isset($_COOKIE['id_hash'])){
				$this->loginFromCookie($_COOKIE['id_hash']);
			}
		} else {
			$this->user_id = $id;
			$this->getUserData();
		}
	}

	public function __sleep(): array{
		$this->PasswordLib = null;
		
		$return = array_merge(["logged_in", "integrations"], $this->db_fields);
		return $return;
	}

	public function save(): bool{
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

	public function registerUser(string $email, string $password, bool|string|null $name = false, bool $remember_me = false): bool{
		$dbh = PDODB::getInstance();
		$this->getPasswordLib();

		$password = $this->PasswordLib->createPasswordHash($password);
		
		$sql = "INSERT INTO user SET email=".$dbh->quote($email).", password=".$dbh->quote($password);
		if($name !== false && $name !== null)
			$sql .= ", name=".$dbh->quote((string)$name);
		
		try{		
			$user_id = $dbh->exec($sql);
			if($user_id !== false){
				$this->user_id = (int)$dbh->lastInsertId();
				$this->logged_in = true;
				$this->getUserData();

				if($remember_me)
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

	public function setNewPassword(string $password): bool{
		$dbh = PDODB::getInstance();
		$this->getPasswordLib();

		$password = $this->PasswordLib->createPasswordHash($password);
		
		$sql = "UPDATE user SET password = ".$dbh->quote($password)." WHERE user_id = ".intval($this->user_id);		
		return (bool)$dbh->exec($sql);
	}

	public function loginFromRegistry(string $email, string $password, bool $remember_me = false): bool{		
		$dbh = PDODB::getInstance();
		$this->getPasswordLib();

		$sql = "SELECT user_id, password FROM user WHERE email=".$dbh->quote($email);
		$data = $dbh->query($sql)->fetch(PDO::FETCH_OBJ);

		$matched = $this->PasswordLib->verifyPasswordHash($password, $data->password);
		
		if($matched !== false){
			$this->user_id = (int)$data->user_id;
			$this->logged_in = true;
			$this->getUserData();

			if($remember_me)
				$this->saveLoginCookie();

			return true;

		} else {
			$this->error = 'User not found with that username/password combination.';
			return false;
		}	
	}

	public function loginFromIntegration(int $integration_user_id, int $integration_id): bool{
		$dbh = PDODB::getInstance();

		$sql = "SELECT
			user_id
		FROM
			user_to_integration
		WHERE
			id=".$dbh->quote((string)$integration_user_id)."
			AND integration_id=".intval($integration_id);

		$user_id_result = $dbh->query($sql)->fetch(PDO::FETCH_COLUMN);
		if($user_id_result !== false){
			$this->user_id = (int)$user_id_result;
			$this->logged_in = true;
			$this->getUserData();

			$this->saveLoginCookie();

			return true;

		} else {
			$this->error = 'User not found for that integration';
			return false;
		}
	}

	public function loginFromCookie(string $val): bool{
		$dbh = PDODB::getInstance();

		$sql = "SELECT 
			u.user_id 
		FROM 
			user_login_cookie ulc 
			LEFT JOIN user u USING(user_id) 
		WHERE 
			ulc.id_hash=".$dbh->quote($val)." 
			AND ulc.expires > '".date('Y-m-d G:i:s', time())."'";

		$user_id_result = $dbh->query($sql)->fetch(PDO::FETCH_COLUMN);
		if($user_id_result !== false){
			$this->user_id = (int)$user_id_result;
			$this->logged_in = true;
			$this->getUserData();
			$this->saveLoginCookie();

			return true;

		} else {
			$this->error = 'Saved login is no longer valid.';
			return false;
		}
	}

	public function setVerificationKey(): string|false{
		$dbh = PDODB::getInstance();
		
		$token = $this->generateUniqueToken(32, 'user', 'verification_key');
		if($dbh->exec("UPDATE user SET verification_key=".$dbh->quote($token)." WHERE user_id=".intval($this->user_id)) !== false)
			return $token;
		else
			return false;
	}

	public function getUserIdForVerificationKey(string $key): ?int{
		$dbh = PDODB::getInstance();

		return (int)$dbh->query("SELECT user_id FROM user WHERE verification_key = ".$dbh->quote($key))->fetch(PDO::FETCH_COLUMN);
	}

	public function getUserData(): void{
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

	public function checkIn(Stop $stop): int|false{
		$dbh = PDODB::getInstance();

		$rows = $dbh->exec("INSERT INTO checkin SET user_id=".intval($this->user_id).", stop_id=".intval($stop->stop_id));
		if($rows !== false){
			return (int)$dbh->lastInsertId();
		} else {
			return false;
		}
	}

	private function saveLoginCookie(): void{
		$dbh = PDODB::getInstance();
		$this->getPasswordLib();
		
		$hash = $this->generateUniqueToken(32, 'user_login_cookie', 'id_hash', true);
		$expires = date('Y-m-d G:i:s', strtotime('+1 week'));
		$dbh->exec("REPLACE INTO user_login_cookie SET user_id=".intval($this->user_id).", id_hash=".$dbh->quote($hash).", expires='".$expires."'");

		if (isset($this->user_id) && !empty($this->user_id)) {
			$token = $this->generateUniqueToken(32, 'session_token', null, true);
			$dbh->exec("REPLACE INTO session_tokens SET user_id=".intval($this->user_id).", token=".$dbh->quote($token)." AND expires='".$expires."'");
		}

		if (isset($_COOKIE['remember_me'])) {
			$rememberMeToken = $this->generateUniqueToken(32, 'remember_me_token', null, true);
			setcookie('remember_me', $rememberMeToken, strtotime('+1 week'), '/');
			$dbh->exec("INSERT INTO remember_me SET token=".$dbh->quote($rememberMeToken));
		}
	}

	public function getPasswordLib(): PasswordLib {
		if ($this->PasswordLib === null) {
			$this->PasswordLib = new \PasswordLib\PasswordLib();
		}
		return $this->PasswordLib;
	}

	private function generateUniqueToken(int $length, string $tbl, string $fld, bool $hash = false): string{
		$dbh = PDODB::getInstance();
		$this->getPasswordLib();

		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i=0; $i<$length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength-1)];
		}

		return (string)$randomString;
	}

	public function getStop(): ?Stop {
		if(!isset($this->stop_id)){
			$dbh = PDODB::getInstance();
			
			$sql = "SELECT stop_id FROM user_stop WHERE user_id=".intval($this->user_id);
			$result = $dbh->query($sql)->fetch(PDO::FETCH_OBJ);
			
			if($result === false){
				return null;
			}

			$this->stop_id = (int)$result->stop_id;
		}

		return new Stop($this->stop_id);
	}

	public function getStopId(): ?int {
	    return $this->stop_id ?? null;
	}

	public function reset(): bool {
		$dbh = PDODB::getInstance();

		$this->user_id = 0;
		$this->logged_in = false;
		$this->error = '';

		$stmt = $dbh->query("SELECT id FROM user WHERE email=".$dbh->quote($this->email));
		$result = $stmt->fetch(PDO::FETCH_COLUMN);

		if ($result !== false) {
			$this->user_id = (int)$result;
			$this->getUserData();
		}

		return true;
	}

	public function isVerified(): bool {
		return $this->email_verified;
	}

}