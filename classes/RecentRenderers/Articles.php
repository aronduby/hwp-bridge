<?php

namespace RecentRenderers;

class Articles extends Renderer{

	public $articles = [];

	public function setup(){
		$ids = json_decode($this->content);
		foreach($ids as $id){
			$a = new \Article($id, $this->dbh);
			$this->articles[] = $a;
		}
		
		$count = count($this->articles);
		$this->title = 'Imported '.$count.' New Article'.($count > 1 ? 's' : '');		
	}

	// 'blah blah blah blah blah blah blah' becomes 'blah blah...'
	private function excerptAndHighlight($text, $word=NULL, $radius=50, $highlight_begin='<strong>', $highlight_end='</strong>') {
		if (!$word) {
			if(strlen($text)>$radius*2)
				return $this->restoreTags(substr($text, 0, strpos($text,' ',$radius*2))."...");
			else
				return $text;
		} else {
			$word = trim($word);
			$word_pos = stripos($text, $word);
			if ($word_pos !== false) {
				if ($word_pos-$radius <= 0)
					$begin_pos = 0;
				else 
					$begin_pos = strpos($text,' ',max(0,$word_pos-$radius))+1;
				$after_pos = strpos($text,' ',min(strlen($text), $word_pos+strlen($word)+$radius))
					or $after_pos = strlen($text);

				if ($begin_pos>0) $excerpt .= '...';
				$excerpt .= substr($text, $begin_pos, $word_pos-$begin_pos);
				$excerpt .= $highlight_begin.substr($text, $word_pos, strlen($word)).$highlight_end;
				$excerpt .= substr($text, $word_pos+strlen($word), $after_pos-($word_pos+strlen($word)));
				if ($after_pos<strlen($text)) $excerpt .= '...';

				return $this->restoreTags($excerpt);
			} else {
				return $text;
			}
		}
	}

	//===================================================================================//
	// Original PHP code by Chirp Internet: www.chirp.com.au // Please acknowledge use of this code by including this header.

	// Used in newsDisplay function - restores unmatched html tags that were truncated
	private function restoreTags($input) {

	// addition 7-20 AD
	// if input doesn't start with a p tag, add it
	if(strpos($input, '<p>')!== 0)
		$input = '<p>'.$input;

	 $opened = $closed = array(); // tally opened and closed tags in order

		if(preg_match_all("/<(\/?[a-z]+)>/i", $input, $matches)) {
			 foreach($matches[1] as $tag) {
				if(preg_match("/^[a-z]+$/i", $tag, $regs)) {
					 $opened[] = $regs[0];
				} elseif(preg_match("/^\/([a-z]+)$/i", $tag, $regs)) {
					 $closed[] = $regs[1];
				}
			 }
		}
		// use closing tags to cancel out opened tags
		if($closed) {
			foreach($opened as $idx => $tag) {
			 foreach($closed as $idx2 => $tag2) {
				if($tag2 == $tag) {
					unset($opened[$idx]);
					 unset($closed[$idx2]);
					 break;
				 }
				}
			}
		}
		// close tags that are still open
		if($opened) {
			$tagstoclose = array_reverse($opened);
			 foreach($tagstoclose as $tag)
				$input .= "</$tag>";
		}
		return $input;
	}

}

?>