angular.module("templates", []).run(["$templateCache", function($templateCache) {$templateCache.put("partials/game-start.html","<ng-include src=\"\'partials/includes/header.html\'\"></ng-include>\r\n\r\n<h2>Set the Goalie</h2>\r\n\r\n<select \r\n    ng-model=\"game.goalie\"\r\n    ng-options=\"p.name_key as p|capAndName for p in game.stats|toArray|orderBy:\'number\'\"\r\n></select>\r\n\r\n");
$templateCache.put("partials/directives/field-stats.html","<div class=\"btn-group scroll-x\">\r\n	\r\n	<button class=\"btn btn-info\" ng-click=\"shotBy(player.name_key)\" ng-disabled=\"isKickedOut(player)\">Shots <span class=\"badge\">{{player.goals}}/{{player.shots}}</span></button>\r\n\r\n	<button class=\"btn btn-info\" ng-click=\"game.kickout(player.name_key)\"  ng-disabled=\"isKickedOut(player)\" ng-class=\"{\'btn-warning\': player.kickouts==2, \'btn-danger\': player.kickouts>=3}\">Ko <span class=\"badge\">{{player.kickouts}}</span></button>\r\n	\r\n	<button class=\"btn btn-info\" ng-click=\"game.kickoutDrawn(player.name_key)\"  ng-disabled=\"isKickedOut(player)\">KoD <span class=\"badge\">{{player.kickouts_drawn}}</span></button>\r\n	\r\n	<button class=\"btn btn-info\" ng-click=\"game.steal(player.name_key)\"  ng-disabled=\"isKickedOut(player)\">Steals <span class=\"badge\">{{player.steals}}</span></button>\r\n	\r\n	<button class=\"btn btn-info\" ng-click=\"game.turnover(player.name_key)\"  ng-disabled=\"isKickedOut(player)\">Tos <span class=\"badge\">{{player.turnovers}}</span></button>\r\n\r\n	<button class=\"btn btn-info\" ng-click=\"game.block(player.name_key)\"  ng-disabled=\"isKickedOut(player)\">Blocks <span class=\"badge\">{{player.blocks}}</span></button>\r\n\r\n	<button class=\"btn btn-info\" ng-click=\"fiveMeterCalledOn(player.name_key)\" ng-disabled=\"isKickedOut(player)\">5mC<span class=\"badge\">{{player.five_meters_called}}</span></button>\r\n	\r\n	<button class=\"btn btn-info\" ng-click=\"fiveMeterDrawnBy(player.name_key)\" ng-disabled=\"isKickedOut(player)\">5mD<span class=\"badge\">{{player.five_meters_drawn}}</span></button>\r\n	\r\n	<button class=\"btn btn-info\" disabled>S/T <span class=\"badge\">{{player.steals / player.turn_overs | number:2}}</span></button>\r\n\r\n	<button class=\"btn btn-info\" disabled>5mT/M<span class=\"badge\">{{player.five_meters_made}}/{{player.five_meters_taken}}</span></button>\r\n\r\n	<button class=\"btn btn-info\" disabled>Assists <span class=\"badge\">{{player.assists}}</span></button>\r\n\r\n	<button class=\"btn btn-info\" disabled>Sprints <span class=\"badge\">{{player.sprints_won}}/{{player.sprints_taken}}</span></button>\r\n</div>");
$templateCache.put("partials/directives/goalie-stats.html","<strong class=\"player-name\" hwp-player-name player=\"player\"></strong>\r\n<div class=\"btn-group\">\r\n	<button class=\"btn btn-info\" ng-click=\"$parent.game.save()\">Saves <span class=\"badge\">{{player.saves}}</span></button>\r\n	<button class=\"btn btn-info\" ng-click=\"$parent.goalAllowed()\">G-A <span class=\"badge\">{{player.goals_allowed}}</span></button>\r\n	<button class=\"btn btn-info\" disabled>S/G-A <span class=\"badge\">{{player.saves / player.goals_allowed | number:2}}</span></button>\r\n	<button class=\"btn btn-info\" disabled>5M-TO <span class=\"badge\">{{player.five_meters_taken_on}}</span></button>\r\n	<button class=\"btn btn-info\" disabled>5M-B <span class=\"badge\">{{player.five_meters_blocked}}</span></button>\r\n	<button class=\"btn btn-info\" disabled>5M-A <span class=\"badge\">{{player.five_meters_allowed}}</span></button>\r\n</div>");
$templateCache.put("partials/directives/player-name.html","<span class=\"cap-number\">#{{player.number}}</span>\r\n<span class=\"full-name\">{{player.first_name}} {{player.last_name}}</span>\r\n<span class=\"fi-last\">{{player.first_name[0]}}. {{player.last_name}}</span>");
$templateCache.put("partials/includes/boxscore.html","<table class=\"box-score\">\r\n    <tr>\r\n        <td ng-repeat=\"quarter_score in boxscore[0] track by $index\" ng-class=\"{\'won\': quarter_score > boxscore[1][$index] }\"> {{ quarter_score }} </td>\r\n    </tr>\r\n    <tr>\r\n        <td ng-repeat=\"quarter_score in boxscore[1] track by $index\" ng-class=\"{\'won\': quarter_score > boxscore[0][$index] }\"> {{ quarter_score }} </td>\r\n    </tr>\r\n</table>");
$templateCache.put("partials/includes/header.html","<header class=\"page-header\">\r\n    <button ng-controller=\"HistoryCtrl\" class=\"btn btn-info pull-left\" ng-click=\"undo()\"><i class=\"glyphicon glyphicon-warning-sign\"></i></button>\r\n	<button ng-controller=\"ShoutCtrl\" class=\"btn btn-info pull-right\" ng-click=\"shout()\"><i class=\"glyphicon glyphicon-bullhorn\"></i></button>\r\n	<h1>vs {{game.opponent}}</h1>\r\n	<nav>\r\n		<ul>\r\n			<li ng-class=\"{active: game.status==\'start\'}\"><a ng-href=\"#/game/{{game.game_id}}/\" title=\"start\">Start</a></li>\r\n			<li ng-class=\"{active: game.status==\'inplay\'}\"><a ng-href=\"#/game/{{game.game_id}}/inplay\" title=\"in play\">In Play</a></li>\r\n			<li ng-class=\"{active: game.status==\'quarter\'}\"><a ng-href=\"#/game/{{game.game_id}}/quarter\" title=\"quarter\">Quarter</a></li>\r\n			<li ng-class=\"{active: game.status==\'final\'}\"><a ng-href=\"#/game/{{game.game_id}}/final\" title=\"final\">Final</a></li>\r\n			<li ng-class=\"{active: game.status==\'shootout\', disabled: game.status!=\'shootout\'}\"><a ng-href=\"#/game/{{game.game_id}}/shootout\" title=\"shoot out\">Shoot Out</a></li>\r\n		</ul>\r\n	</nav>\r\n</header>");
$templateCache.put("partials/modals/history.html","<div class=\"modal-container\">\r\n	<header class=\"modal-header\">\r\n		<button type=\"button\" class=\"close\" aria-hidden=\"true\" ng-click=\"cancel()\">&times;</button>\r\n		<h4 class=\"modal-title\">Undo</h4>\r\n	</header>\r\n	<section class=\"modal-body\">\r\n		<ol class=\"history\">\r\n			<li ng-repeat=\"save in history.storage.saves | limitTo:5\" ng-click=\"revert($index + 1)\">\r\n				<span class=\"func\">{{ save.func }}</span>\r\n				<span class=\"args\">{{ save.args.join(\', \') }}</span>\r\n			</li>\r\n		</ol>\r\n	</section>\r\n    <footer class=\"modal-footer\">\r\n        <small>Latest on top, undoing wipes out any actions taken after that point so undoing to 4 also wipes out 1-3</small>\r\n    </footer>\r\n</div>");
$templateCache.put("partials/modals/made-missed-blocked.html","<div class=\"modal-container\">\r\n	<header class=\"modal-header\">\r\n		<button type=\"button\" class=\"close\" aria-hidden=\"true\" ng-click=\"cancel()\">&times;</button>\r\n		<h4 class=\"modal-title\">{{title}}</h4>\r\n	</header>\r\n	<section class=\"modal-body\">\r\n		<center class=\"btn-group btn-group-block btn-group-large\">\r\n			<button class=\"btn btn-large btn-success\" ng-click=\"made()\">Made</button>\r\n			<button class=\"btn btn-large btn-warning\" ng-click=\"missed()\">Missed</button>\r\n			<button class=\"btn btn-large btn-danger\" ng-click=\"blocked()\">Blocked</button>\r\n		</center>\r\n	</section>\r\n</div>");
$templateCache.put("partials/modals/number-input.html","<div class=\"modal-container number-input\">\r\n	<header class=\"modal-header\">\r\n		<button type=\"button\" class=\"close\" aria-hidden=\"true\" ng-click=\"cancel()\">&times;</button>\r\n		<h4 class=\"modal-title\">{{title}}</h4>\r\n	</header>\r\n	<section class=\"modal-body\">\r\n\r\n		<label>{{ title }}:</label>\r\n		<div class=\"form-inline\">\r\n			<input \r\n				type=\"tel\" \r\n				ng-model=\"number\"\r\n				class=\"form-control minutes input-large\"\r\n				max=\"99\"\r\n				maxlength=\"2\"\r\n				placeholder=\"enter a number\"\r\n				tabindex=\"1\"\r\n				autofocus\r\n			 />\r\n		</div>\r\n		\r\n	</section>\r\n	<footer class=\"modal-footer\">\r\n		<button class=\"btn btn-success btn-large\" ng-click=\"submit(number)\">Submit</button>\r\n		<button class=\"btn btn-link\" ng-click=\"cancel()\">cancel</button>\r\n	</section>\r\n	</footer>\r\n</div>");
$templateCache.put("partials/modals/player-list.html","<div class=\"modal-container\">\r\n	<header class=\"modal-header\">\r\n		<button type=\"button\" class=\"close\" aria-hidden=\"true\" ng-click=\"cancel()\">&times;</button>\r\n		<h4 class=\"modal-title\">{{title}}</h4>\r\n	</header>\r\n	<section class=\"modal-body\">\r\n		<ul class=\"unstyled players\">\r\n			<li ng-click=\"select(false)\">No One</li>\r\n			<li ng-repeat=\"player in players | toArray | orderBy:order_by:reverse\" ng-if=\"player.name_key != skip\" ng-click=\"select(player.name_key)\">\r\n				{{player | capAndName}}\r\n			</li>\r\n		</ul>\r\n	</section>\r\n	<footer class=\"modal-footer\">\r\n		<div class=\"btn-group btn-group-block btn-group-large\">\r\n			<button class=\"btn btn-info\" ng-model=\"order_by\" btn-radio=\"\'number_sort\'\" ng-click=\"reverse=!reverse\">#</button>\r\n			<button class=\"btn btn-info\" ng-model=\"order_by\" btn-radio=\"\'first_name\'\" ng-click=\"reverse=!reverse\">Fn</button>\r\n			<button class=\"btn btn-info\" ng-model=\"order_by\" btn-radio=\"\'last_name\'\" ng-click=\"reverse=!reverse\">Ln</button>\r\n		</div>\r\n	</footer>\r\n</div>");
$templateCache.put("partials/modals/shout.html","<div class=\"modal-container shout\">\r\n	<header class=\"modal-header\">\r\n		<button type=\"button\" class=\"close\" aria-hidden=\"true\" ng-click=\"cancel()\">&times;</button>\r\n		<h4 class=\"modal-title\">Shout</h4>\r\n	</header>\r\n	<section class=\"modal-body\">\r\n\r\n		<textarea name=\"msg\" ng-model=\"msg\" ng-minlength=\"10\" placeholder=\"your message here\" required autofocus></textarea>\r\n		\r\n	</section>\r\n	<footer class=\"modal-footer\">\r\n		<button class=\"btn btn-success btn-large\" ng-click=\"shout(msg)\">Shout It</button>\r\n		<button class=\"btn btn-link\" ng-click=\"cancel()\">cancel</button>\r\n	</section>\r\n	</footer>\r\n</div>");
$templateCache.put("partials/modals/timeout.html","<div class=\"modal-container timeout\">\r\n	<header class=\"modal-header\">\r\n		<button type=\"button\" class=\"close\" aria-hidden=\"true\" ng-click=\"cancel()\">&times;</button>\r\n		<h4 class=\"modal-title\">{{title}}</h4>\r\n	</header>\r\n	<section class=\"modal-body\">\r\n\r\n		<label>Time left in quarter:</label>\r\n		<div class=\"form-inline\">\r\n			<input \r\n				type=\"tel\" \r\n				ng-model=\"minutes\" \r\n				class=\"form-control minutes\" \r\n				max=\"7\" \r\n				maxlength=\"1\" \r\n				placeholder=\"minutes\"\r\n				tabindex=\"1\"\r\n				autofocus\r\n			 /> : <input \r\n			 	type=\"tel\" \r\n			 	ng-model=\"seconds\" \r\n			 	class=\"form-control seconds\" \r\n			 	max=\"59\" \r\n			 	maxlength=\"2\" \r\n			 	placeholder=\"seconds\" \r\n			 	tabindex=\"2\" \r\n			 />\r\n		</div>\r\n		\r\n	</section>\r\n	<footer class=\"modal-footer\">\r\n		<button class=\"btn btn-success btn-large\" ng-click=\"submit(minutes, seconds)\">Time Out</button>\r\n		<button class=\"btn btn-link\" ng-click=\"cancel()\">cancel</button>\r\n	</section>\r\n	</footer>\r\n</div>");
$templateCache.put("partials/modals/yes-no.html","<div class=\"modal-container\">\r\n	<header class=\"modal-header\">\r\n		<button type=\"button\" class=\"close\" aria-hidden=\"true\" ng-click=\"cancel()\">&times;</button>\r\n		<h4 class=\"modal-title\">{{title}}</h4>\r\n	</header>\r\n	<section class=\"modal-body\">\r\n		<center class=\"btn-group btn-group-block btn-group-large\">\r\n			<button class=\"btn btn-large btn-success\" ng-click=\"yes()\">Yes</button>\r\n			<button class=\"btn btn-large btn-default\" ng-click=\"no()\">No</button>\r\n		</center>\r\n	</section>\r\n</div>");
$templateCache.put("partials/views/game-final.html","<div cg-busy=\"finalTracker\"></div>\r\n\r\n<ng-include src=\"\'partials/includes/header.html\'\"></ng-include>\r\n\r\n<section class=\"final split\">\r\n	<header><h2>Game Over</h2></header>\r\n	<section>\r\n		<div><button class=\"btn btn-warning btn-large btn-block\" ng-disabled=\"game.quarters_played < 3\" ng-click=\"postFinal();\"><i class=\"glyphicon glyphicon-ok-circle\"></i> Final</button></div>\r\n	</section>\r\n</section>\r\n\r\n\r\n<section class=\"overtime split\">\r\n	<header><h2>Overtime</h2></header>\r\n	<section>\r\n		<div><button class=\"btn btn-warning btn-large btn-block\" ng-disabled=\"game.quarters_played != 3\" ng-click=\"game.setQuartersPlayed(4)\"><i class=\"glyphicon glyphicon-ok-circle\"></i> Overtime 1</button></div>\r\n		<div><button class=\"btn btn-warning btn-large btn-block\" ng-disabled=\"game.quarters_played != 4\" ng-click=\"game.setQuartersPlayed(5)\"><i class=\"glyphicon glyphicon-ok-circle\"></i> Overtime 2</button></div>\r\n		<div><button class=\"btn btn-danger btn-large btn-block\" ng-disabled=\"game.quarters_played < 3\" ng-click=\"shootout()\"><i class=\"glyphicon glyphicon-ok-circle glyphicon\"></i> Shoot Out!</button></div>\r\n	</section>\r\n</section>\r\n\r\n<section class=\"\">\r\n	<header>\r\n		<h2>Box Score</h2>\r\n	</header>\r\n	<ng-include src=\"\'partials/includes/boxscore.html\'\" ng-controller=\"boxScoreCtrl\"></ng-include>\r\n</section>\r\n\r\n<section class=\"spint\">\r\n	<header>\r\n		<div class=\"btn-group btn-group-sm pull-right\">\r\n			<button class=\"btn btn-info\" ng-model=\"order_by\" btn-radio=\"\'number_sort\'\" ng-click=\"reverse=!reverse\">#</button>\r\n			<button class=\"btn btn-info\" ng-model=\"order_by\" btn-radio=\"\'first_name\'\" ng-click=\"reverse=!reverse\">Fn</button>\r\n			<button class=\"btn btn-info\" ng-model=\"order_by\" btn-radio=\"\'last_name\'\" ng-click=\"reverse=!reverse\">Ln</button>\r\n		</div>\r\n		<h2>Overtime Spint Taken By</h2>\r\n	</header>\r\n	<section>\r\n		<ul class=\"players\">\r\n			<li ng-repeat=\"player in game.stats | toArray | orderBy:order_by:reverse\" ng-click=\"sprintTakenBy(player.name_key)\">\r\n				<strong class=\"player-title\">{{ player | capAndName }}</strong>\r\n			</li>\r\n		</ul>\r\n	</section>\r\n</section>\r\n\r\n\r\n");
$templateCache.put("partials/views/game-inplay.html","<ng-include src=\"\'partials/includes/header.html\'\"></ng-include>\r\n\r\n<section class=\"scores split\">\r\n	<header><h2>Current Score</h2></header>\r\n	<section>\r\n		<section>\r\n			<header><h3>Hudsonville</h3></header>\r\n			<section>\r\n				<h4>{{game.score[0]}}</h4>\r\n			</section>\r\n		</section>\r\n		<section>\r\n			<header><h3>{{game.opponent}}</h3></header>\r\n			<section>\r\n				<h4>{{game.score[1]}}</h4>\r\n			</section>\r\n		</section>\r\n	</section>\r\n</section>\r\n\r\n<ng-include src=\"\'partials/includes/boxscore.html\'\" ng-controller=\"boxScoreCtrl\"></ng-include>\r\n\r\n<section class=\"goalie\">\r\n	<header>\r\n		<button class=\"btn btn-info pull-right\" ng-click=\"changeGoalie()\"><i class=\"glyphicon glyphicon-retweet\"></i></button>\r\n		<h2>Goalie: <hwp-player-name player=\"game.stats[game.goalie]\"></hwp-player-name></h2>\r\n	</header>\r\n	<section>\r\n		<ul class=\"stats\">\r\n			<li><hwp-goalie-stats player=\"game.stats[game.goalie]\"></hwp-goalie-stats>\r\n		</ul>\r\n	</section>\r\n</section>\r\n\r\n<section class=\"field\">\r\n	<header>\r\n		<div class=\"btn-group btn-group-sm pull-right\">\r\n			<button class=\"btn btn-info\" ng-model=\"order_by\" btn-radio=\"\'number_sort\'\" ng-click=\"reverse=!reverse\">#</button>\r\n			<button class=\"btn btn-info\" ng-model=\"order_by\" btn-radio=\"\'first_name\'\" ng-click=\"reverse=!reverse\">Fn</button>\r\n			<button class=\"btn btn-info\" ng-model=\"order_by\" btn-radio=\"\'last_name\'\" ng-click=\"reverse=!reverse\">Ln</button>\r\n		</div>\r\n		<h2>Field</h2>\r\n	</header>\r\n	<section>\r\n		<ul class=\"names\">\r\n			<li ng-repeat=\"player in game.stats | toArray | orderBy:order_by:reverse\" ng-class=\"{muted: isPlayerKickedOut(player), \'text-warning\': player.kickouts==2, \'text-error\': player.kickouts>=3}\">\r\n				<strong class=\"player-name\" hwp-player-name player=\"player\"></strong>\r\n			</li>\r\n		</ul><ul class=\"stats\">\r\n			<li ng-repeat=\"player in game.stats | toArray | orderBy:order_by:reverse\" ng-class=\"{muted: isPlayerKickedOut(player), \'text-warning\': player.kickouts==2, \'text-error\': player.kickouts>=3}\">\r\n				<hwp-field-stats player=\"player\"></hwp-field-stats>\r\n			</li>\r\n		</ul>\r\n	</section>\r\n</section>\r\n\r\n\r\n<section class=\"timeouts split\">\r\n	<header><h2>Time Outs</h2></header>\r\n	<section>\r\n		<section>\r\n			<header><h3>Hudsonville</h3></header>\r\n			<section>\r\n				<button class=\"btn btn-block btn-large btn-info\" ng-click=\"timeout(true)\">Timeout</button>\r\n			</section>\r\n		</section>\r\n		<section>\r\n			<header><h3>{{game.opponent}}</h3></header>\r\n			<section>\r\n				<button class=\"btn btn-block btn-large btn-info\" ng-click=\"timeout(false)\">Timeout</button>\r\n			</section>\r\n		</section>\r\n	</section>\r\n</section>\r\n\r\n\r\n<section class=\"carded split\">\r\n	<header><h2>Carded</h2></header>\r\n	<section>\r\n		<section>\r\n			<header><h3>Hudsonville</h3></header>\r\n			<section>\r\n				<ul>\r\n					<li ng-repeat=\"who in [\'Josh\',\'Jordan\',\'Travis\']\">\r\n						<div class=\"btn-group btn-group-block btn-group-large\">\r\n							<button class=\"btn btn-warning\" ng-click=\"game.carded(who,\'yellow\')\">{{who}}</button>\r\n							<button class=\"btn btn-danger\" ng-click=\"game.carded(who,\'red\')\">{{who}}</button>\r\n					</li>\r\n				</ul>\r\n			</section>\r\n		</section>\r\n		<section>\r\n			<header><h3>{{game.opponent}}</h3></header>\r\n			<section>\r\n				<ul>\r\n					<li>\r\n						<div class=\"btn-group btn-group-block btn-group-large\">\r\n							<button class=\"btn btn-warning\" ng-click=\"game.carded(game.opponent,\'yellow\')\">{{game.opponent}}</button>\r\n							<button class=\"btn btn-danger\" ng-click=\"game.carded(game.opponent,\'red\')\">{{game.opponent}}</button>\r\n						</div>\r\n					</li>\r\n				</ul>\r\n			</section>\r\n		</section>\r\n	</section>\r\n</section>\r\n\r\n<section class=\"advantages-converted split\">\r\n	<header><h2>Advantages Converted</h2></header>\r\n	<section>\r\n		<section>\r\n			<header><h3>Hudsonville</h3></header>\r\n			<section>\r\n				{{game.advantage_conversion[0].converted}} / {{game.advantage_conversion[0].drawn}}\r\n			</section>\r\n		</section>\r\n\r\n		<section>\r\n			<header><h3>{{game.opponent}}</h3></header>\r\n			<section>\r\n				{{game.advantage_conversion[1].converted}} / {{game.advantage_conversion[1].drawn}}\r\n			</section>\r\n		</section>\r\n	</section>\r\n</section>\r\n\r\n\r\n<section class=\"kickouts\" ng-show=\"game.kickouts[0].length > 0 || game.kickouts[1].length > 0\">\r\n	<h4>Kickouts</h4>\r\n	<section class=\"us\" ng-show=\"game.kickouts[0].length > 0\">\r\n		<h5>Hudsonville:</h5>\r\n		<ul>\r\n			<li ng-repeat=\"name_key in game.kickouts[0]\" ng-click=\"game.kickoutOver(name_key)\">{{game.stats[name_key] | capAndName}}</li>\r\n		</ul>\r\n	</section>\r\n	<section class=\"them\" ng-show=\"game.kickouts[1].length > 0\">\r\n		<h5>{{game.opponent}}:</h5>\r\n		<ul>\r\n			<li ng-repeat=\"name_key in game.kickouts[1] track by $index\" ng-click=\"game.kickoutOver(false)\">Player {{$index + 1}}</li>\r\n		</ul>\r\n	</section>\r\n</section>\r\n\r\n<section class=\"kickouts kickouts-fixed\" ng-show=\"game.kickouts[0].length > 0 || game.kickouts[1].length > 0\">\r\n	<h4>Kickouts</h4>\r\n	<section class=\"us\" ng-show=\"game.kickouts[0].length > 0\">\r\n		<h5>Hudsonville:</h5>\r\n		<ul>\r\n			<li ng-repeat=\"name_key in game.kickouts[0]\" ng-click=\"game.kickoutOver(name_key)\">{{game.stats[name_key] | capAndName}}</li>\r\n		</ul>\r\n	</section>\r\n	<section class=\"them\" ng-show=\"game.kickouts[1].length > 0\">\r\n		<h5>{{game.opponent}}:</h5>\r\n		<ul>\r\n			<li ng-repeat=\"name_key in game.kickouts[1] track by $index\" ng-click=\"game.kickoutOver(false)\">Player {{$index + 1}}</li>\r\n		</ul>\r\n	</section>\r\n</section>");
$templateCache.put("partials/views/game-quarter.html","<ng-include src=\"\'partials/includes/header.html\'\"></ng-include>\r\n\r\n<section class=\"quarters-played split\">\r\n	<header><h2>Quarters</h2></header>\r\n	<section>\r\n		<div><button class=\"btn btn-warning btn-large btn-block\" ng-disabled=\"game.quarters_played > 0\" ng-click=\"game.setQuartersPlayed(1)\"><i class=\"glyphicon glyphicon-ok-circle\"></i> 1st Quarter</button></div>\r\n		<div><button class=\"btn btn-warning btn-large btn-block\" ng-disabled=\"game.quarters_played > 1\" ng-click=\"game.setQuartersPlayed(2)\"><i class=\"glyphicon glyphicon-ok-circle\"></i> 2nd Quarter</button></div>\r\n		<div><button class=\"btn btn-warning btn-large btn-block\" ng-disabled=\"game.quarters_played > 2\" ng-click=\"game.setQuartersPlayed(3)\"><i class=\"glyphicon glyphicon-ok-circle\"></i> 3rd Quarter</button></div>\r\n	</section>\r\n</section>\r\n\r\n<section class=\"\">\r\n	<header>\r\n		<h2>Box Score</h2>\r\n	</header>\r\n	<ng-include src=\"\'partials/includes/boxscore.html\'\" ng-controller=\"boxScoreCtrl\"></ng-include>\r\n</section>\r\n\r\n<section class=\"sprint\">\r\n	<header>\r\n		<div class=\"btn-group btn-group-sm pull-right\">\r\n			<button class=\"btn btn-info\" ng-model=\"order_by\" btn-radio=\"\'number_sort\'\" ng-click=\"reverse=!reverse\">#</button>\r\n			<button class=\"btn btn-info\" ng-model=\"order_by\" btn-radio=\"\'first_name\'\" ng-click=\"reverse=!reverse\">Fn</button>\r\n			<button class=\"btn btn-info\" ng-model=\"order_by\" btn-radio=\"\'last_name\'\" ng-click=\"reverse=!reverse\">Ln</button>\r\n		</div>\r\n		<h2>Spint Taken By</h2>\r\n	</header>\r\n	<section>\r\n		<ul class=\"players\">\r\n			<li ng-repeat=\"player in game.stats | toArray | orderBy:order_by:reverse\" ng-click=\"sprintTakenBy(player.name_key)\">\r\n				<strong class=\"player-title\">{{ player | capAndName }}</strong>\r\n			</li>\r\n		</ul>\r\n	</section>\r\n</section>\r\n\r\n");
$templateCache.put("partials/views/game-shootout.html","<ng-include src=\"\'partials/includes/header.html\'\"></ng-include>\r\n\r\n<section class=\"scores split\">\r\n	<header><h2>Current Score</h2></header>\r\n	<section>\r\n		<section>\r\n			<header><h3>Hudsonville</h3></header>\r\n			<section>\r\n				<h4>{{game.score[0]}}</h4>\r\n			</section>\r\n		</section>\r\n		<section>\r\n			<header><h3>{{game.opponent}}</h3></header>\r\n			<section>\r\n				<h4>{{game.score[1]}}</h4>\r\n			</section>\r\n		</section>\r\n	</section>\r\n</section>\r\n\r\n<ng-include src=\"\'partials/includes/boxscore.html\'\" ng-controller=\"boxScoreCtrl\"></ng-include>\r\n\r\n<section class=\"shootout-opponent split\">\r\n	<header><h2>{{game.opponent}}</h2></header>\r\n	<section>\r\n		<div><button class=\"btn btn-large btn-success btn-block\" ng-click=\"shotThem(\'made\')\">Made</button></div>\r\n		<div><button class=\"btn btn-large btn-warning btn-block\" ng-click=\"shotThem(\'missed\')\">Missed</button></div>\r\n		<div><button class=\"btn btn-large btn-danger btn-block\" ng-click=\"shotThem(\'blocked\')\">Blocked</button></div>\r\n	</section>\r\n</section>\r\n\r\n<section>\r\n	<header>\r\n		<div class=\"btn-group btn-group-sm pull-right\">\r\n			<button class=\"btn btn-info\" ng-model=\"order_by\" btn-radio=\"\'number_sort\'\" ng-click=\"reverse=!reverse\">#</button>\r\n			<button class=\"btn btn-info\" ng-model=\"order_by\" btn-radio=\"\'first_name\'\" ng-click=\"reverse=!reverse\">Fn</button>\r\n			<button class=\"btn btn-info\" ng-model=\"order_by\" btn-radio=\"\'last_name\'\" ng-click=\"reverse=!reverse\">Ln</button>\r\n		</div>\r\n		<h2>Hudsonville</h2>\r\n	</header>\r\n	<section>\r\n		<ul class=\"players\">\r\n			<li ng-repeat=\"player in game.stats | toArray | orderBy:order_by:reverse\" ng-if=\"player.name_key != game.goalie\" ng-click=\"shotUs(player.name_key)\" ng-class=\"{\r\n				disabled: player.shoot_out_taken > 0,\r\n				\'success\': player.shoot_out_made > 0,\r\n				\'error\': (player.shoot_out_made == 0 && player.shoot_out_taken > 0)\r\n			}\">\r\n				<strong class=\"player-name\" hwp-player-name player=\"player\"></strong>\r\n				<i ng-class=\"{\r\n					\'glyphicon glyphicon-ok-sign\': player.shoot_out_made > 0, \r\n					\'glyphicon glyphicon-remove-sign\': (player.shoot_out_made == 0 && player.shoot_out_taken > 0)\r\n				}\"></i>\r\n			</li>\r\n		</ul>\r\n	</section>\r\n</section>");
$templateCache.put("partials/views/game-start.html","<ng-include src=\"\'partials/includes/header.html\'\"></ng-include>\r\n\r\n<section class=\"set-goalie\">\r\n	<header><h2>Set the Goalie</h2></header>\r\n	<section>\r\n		<select \r\n			ng-model=\"game.goalie\"\r\n			ng-options=\"p.name_key as p|capAndName for p in game.stats|toArray|orderBy:\'number_sort\'\"\r\n			ng-change=\"game.changeGoalie(game.goalie)\"\r\n		></select>\r\n	</section>\r\n</section>\r\n\r\n<section class=\"sprint\">\r\n	<header>\r\n		<div class=\"btn-group btn-group-sm pull-right\">\r\n			<button class=\"btn btn-info\" ng-model=\"order_by\" btn-radio=\"\'number_sort\'\" ng-click=\"reverse=!reverse\">#</button>\r\n			<button class=\"btn btn-info\" ng-model=\"order_by\" btn-radio=\"\'first_name\'\" ng-click=\"reverse=!reverse\">Fn</button>\r\n			<button class=\"btn btn-info\" ng-model=\"order_by\" btn-radio=\"\'last_name\'\" ng-click=\"reverse=!reverse\">Ln</button>\r\n		</div>\r\n		<h2>Spint Taken By</h2>\r\n	</header>\r\n	<section>\r\n		<ul class=\"players\">\r\n			<li ng-repeat=\"player in game.stats | toArray | orderBy:order_by:reverse\" ng-click=\"sprintTakenBy(player.name_key)\">\r\n				<strong class=\"player-name\" hwp-player-name player=\"player\"></strong>\r\n			</li>\r\n		</ul>\r\n	</section>\r\n</section>");
$templateCache.put("partials/views/nope.html","<p>You forgot <code>game/game_id</code> in the url</p>");}]);