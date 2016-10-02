'use strict';

// Declare app level module which depends on filters, and services
angular.module('myApp', [
	'templates',
	'ngRoute',
	'myApp.filters',
	'myApp.services',
	'myApp.directives',
	'myApp.controllers',
	'ui.bootstrap',
	'ngAnimate',
	'cgBusy',
	'ngStorage'
]).
config(['$routeProvider', function($routeProvider) {

	$routeProvider
		.when('/', {
			templateUrl: 'partials/views/nope.html'
		})
		.when('/game/:game_id', {
			templateUrl: 'partials/views/game-start.html',
			controller: 'startCtrl',
			resolve: {
				gameData: gameDataDefer
			}
		})
		.when('/game/:game_id/start', {
			templateUrl: 'partials/views/game-start.html',
			controller: 'startCtrl',
			resolve: {
				gameData: gameDataDefer
			}
		})
		.when('/game/:game_id/inplay', {
			templateUrl: 'partials/views/game-inplay.html',
			controller: 'inPlayCtrl',
			resolve: {
				gameData: gameDataDefer
			}	
		})
		.when('/game/:game_id/quarter', {
			templateUrl: 'partials/views/game-quarter.html',
			controller: 'quarterCtrl',
			resolve:{
				gameData: gameDataDefer
			}
		})
		.when('/game/:game_id/final', {
			templateUrl: 'partials/views/game-final.html',
			controller: 'finalCtrl',
			resolve:{
				gameData: gameDataDefer
			}
		})
		.when('/game/:game_id/shootout', {
			templateUrl: 'partials/views/game-shootout.html',
			controller: 'shootOutCtrl',
			resolve:{
				gameData: gameDataDefer
			}
		});


	$routeProvider.otherwise({redirectTo: '/'});

}])
.run(['socket', 'history', 'localCopy', 'game', function(socket, history, localCopy, game){

    game.setHistory(history);
    game.setSocket(socket);
	game.setLocalCopy(localCopy);

}]);

var gameDataDefer = function($route, $q, game){
	if(game.loaded == false){
		var defered = $q.defer();
		game.loadData($route.current.params.game_id, defered);
		return defered.promise;
	} else {
		return true;
	}
}