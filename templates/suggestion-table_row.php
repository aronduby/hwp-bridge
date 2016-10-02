<tr class="suggestion">
	<td class="vote"><a data-suggestion_id="<?php echo $this->suggestion_id ?>"><span>vote up</span></a></td>
	<td class="content">
		<p class="title"><?php echo $this->title ?></p>
		<?php echo strlen($this->description)>0 ? '<p class="description">'.nl2br($this->description).'</p>' : '' ?>
	</td>
</tr>