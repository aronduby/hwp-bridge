<?php
require '../common.php';
if(isset($_COOKIE['in'])){
	header("Location: index.php");;
	die();
}

if(!empty($_POST)){
	// check username and password against our known info
	// eventually might expand to database
	if(strtoupper($_POST['username']) == ADMIN_USERNAME && $_POST['password'] == ADMIN_PASSWORD){
		setcookie('in','1',strtotime('+1 day', time()));
		header("Location: index.php");;
		die();
	} else {
		$form_errors = 'Unknown username/password combination. Please try again.';
	}
}
?>

<!DOCTYPE html> 
<html> 
	<head> 
	<title>Admin - Hudsonville Water Polo</title> 
	<meta name="viewport" content="width=device-width, initial-scale=1"> 
	<link rel="stylesheet" href="http://code.jquery.com/mobile/1.1.1/jquery.mobile-1.1.1.min.css" />
	<script src="http://code.jquery.com/jquery-1.7.1.min.js"></script>
	<script src="http://code.jquery.com/mobile/1.1.1/jquery.mobile-1.1.1.min.js"></script>
	
</head> 
<body> 

<div data-role="page" data-theme="b">

	<div data-role="header" data-theme="b">
		<h1>Please Login</h1>
	</div><!-- /header -->

	<div data-role="content">
		
		<p>Please login to manage games. If you don't have a log-in and think you should contact <a href="mailto:aron.duby@gmail.com" title="duby">Duby</a>.</p>

		<?php
		if(isset($form_errors)){
			print '<div data-role="content" data-theme="e">';
				print $form_errors;
			print '</div>';
		}
		?>

		<form action="login.php" method="POST">
			<ul data-role="listview" data-inset="true">
				<li data-role="fieldcontain" class="ui-hide-label">
					<label for="username">Username:</label>
		        	<input type="text" name="username" id="username" placeholder="username" value="" />
				</li>
				<li data-role="fieldcontain" class="ui-hide-label">
					<label for="password">Password:</label>
		        	<input type="password" name="password" id="password" placeholder="password" value="" />
				</li>
				<li data-role="fieldcontain" class="ui-hide-label">
					<button type="submit">Login</button>
				</li>

			</ul>
		</form>
	</div><!-- /content -->

</div><!-- /page -->

</body>
</html>