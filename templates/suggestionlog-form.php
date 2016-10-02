<form id="suggestion_form" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
	<input type="hidden" name="user_agent" value="<?php echo $_SERVER['HTTP_USER_AGENT'] ?>" />

	<div class="input required">
		<label for="title">Title</label>
		<input type="text" id="title" name="title" placeholder="required" required="required"/>
		<p class="note">short title for your suggestion</p>
	</div>

	<div class="input optional">
		<label for="description">Description</label>
		<textarea name="description" id="description"></textarea>
		<p class="note">a longer description of your suggestion, optional</p>
	</div>

	<div class="input optional">
		<label for="name">Your Name</label>
		<input type="text" id="name" name="name" />
	</div>

	<div class="input optional">
		<label for="email">Your Email</label>
		<input type="email" id="email" name="email" />
	</div>

	<div class="full_note">
		<p>Your name and email are optional and only used if I need to contact you about your suggestion, won't display on the site.</p>
	</div>

	<div class="submit">
		<input type="submit" value="Suggest" />
	</div>
</form>