<?php
require '../common.php';

if($userInfo){
	header("Location: index.php");
	die();
}

require '_pre.php';
?>

<div data-role="page" data-theme="b">

	<div data-role="header" data-theme="b">
		<h1>Please Login</h1>
	</div><!-- /header -->

	<div data-role="content">
		
		<p>Please login to manage games. If you don't have a log-in and think you should contact <a href="mailto:aron.duby@gmail.com" title="duby">Duby</a>.</p>

			<ul data-role="listview" data-inset="true">
				<li data-role="fieldcontain" class="ui-hide-label">
                    <a href="login.php" data-role="button">Login</a>
				</li>
			</ul>
	</div><!-- /content -->

</div><!-- /page -->

<?php require '_post.php'; ?>