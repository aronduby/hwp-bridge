<?php /** @noinspection SqlResolve */

class Article {

	use Outputable;

	public $article_id;
	public $title;
	public $url;
	public $description;
	public $published;
	public $mentions = [];

    public $dbh;
    private $register;
    private $site;

    public function __construct($article_id = null, Register $register){
        $this->register = $register;
		$this->dbh = $register->dbh;
		$this->site = $register->site;

		if($article_id !== null){
			$stmt = $this->dbh->query("SELECT article_id, title, url, description, published FROM article WHERE site_id = ".intval($this->site->id)." AND article_id=".intval($article_id));
			$stmt->setFetchMode(PDO::FETCH_INTO, $this);
			if(!$stmt->fetch()){
				throw new Exception('Article Not Found');
			}
		}

		$this->published = DateTime::createFromFormat(MYSQL_DATETIME_FORMAT, $this->published);

		$this->mentions = $this->getMentions();
	}

	private function getMentions(){
		$stmt = $this->dbh->query("SELECT player_id, highlight FROM player_to_article WHERE article_id=".$this->article_id);
		$mentions = [];
		while($r = $stmt->fetch(PDO::FETCH_ASSOC)){
			$mentions[$r['player_id']] = [
				'player' => new Player($r['player_id'], $this->dbh),
				'highlight' => $r['highlight']
			];
		}
		
		return $mentions;
	}


}

?>