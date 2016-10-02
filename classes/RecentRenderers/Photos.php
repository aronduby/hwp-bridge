<?php

namespace RecentRenderers;

class Photos extends Renderer{

	public $max_thumbnails = 16;	

	public $photos = [];

	public function setup(){
		$ids = json_decode($this->content);
		foreach($ids as $id){
			$this->photos[] = new \Photo($id, $this->dbh);
		}
		
		$count = count($this->photos);
		$this->title = 'Imported '.$count.' New Photo'.($count > 1 ? 's' : '');		
	}

}

?>