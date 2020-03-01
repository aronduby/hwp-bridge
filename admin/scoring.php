<?php
require '../common.php';

// make sure we have the necessary claims
$claims = Auth::claims();
if (
    !property_exists($claims, 'site_id')
    || !property_exists($claims, 'sub')
    || !property_exists($claims, 'admin')
) {
    Auth::logout();
    header('Location: login.php');
    die();
}
?>
<!doctype html>
<html lang="en" ng-app="myApp">
<head>
    <meta charset="utf-8">
    <title>Scoring App</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <link href="bootstrap-custom/css/bootstrap.css" rel="stylesheet"/>
    <link href="lib/angular-busy/dist/angular-busy.min.css" rel="stylesheet"/>
    <link href="css/app.css" rel="stylesheet"/>
    <link href="css/scratch.css" rel="stylesheet"/>

    <script src="<?php echo BASE_HREF ?>:7656/socket.io/socket.io.js"></script>
    <script>
        var token = "<?php echo Auth::token() ?>";
    </script>
</head>
<body>

<div
        id="socket_status"
        class="socket-error alert alert-danger"
        ng-show="status !== false"
        ng-controller="SocketStatusCtrl"
        ng-class="{
				'alert-warning': status=='reconnecting',
				'alert-danger': status=='disconnected',
				'alert-success': status=='reconnected'
			}"
>
    {{msg}}
    <span ng-show="status=='reconnecting' && attempt_number != 0">({{current_attempt}})</span>
</div>


<article ng-view></article>

<!--
<script src="lib/angular/angular.js"></script>
<script src="lib/angular/angular-route.js"></script>
<script src="lib/angular/angular-animate.js"></script>
-->
<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.5/angular.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.5/angular-route.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.5/angular-animate.min.js"></script>

<script src="bootstrap-custom/ui-bootstrap-custom-tpls-0.6.0.js"></script>
<script src="lib/angular-busy/dist/angular-busy.min.js"></script>
<script src="lib/ngStorage/ngStorage.min.js"></script>

<script src="js/templates.js"></script>
<script src="js/app.js"></script>
<!--<script src="js/socketio-service.js"></script>-->
<script src="js/services.js"></script>
<script src="js/controllers.js"></script>
<script src="js/filters.js"></script>
<script src="js/directives.js"></script>
</body>
</html>
