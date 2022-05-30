'use strict';

/* Directives */


angular.module('myApp.directives', [])
	.directive('hwpPlayerName', function(){
		return {
			restrict: 'AE',
			scope:{
				player: '='
			},
			templateUrl: 'partials/directives/player-name.html'
		}
	})

	.directive('hwpFieldStats', function(){
		return {
			restrict: 'E',
			scope: true, 
			templateUrl: 'partials/directives/field-stats.html'
		};
	})
	.directive('hwpTotalFieldStats', function() {
		return {
			restrict: 'E',
			scope: {
				player: '=',
				disabled: '=',
				calculateTotals: '&'
			},
			templateUrl: 'partials/directives/field-stats.html'
		}
	})

	.directive('hwpGoalieStats', function(){
		return {
			restrict: 'E',
			scope: {
				player: '='
			},
			templateUrl: 'partials/directives/goalie-stats.html'
		}
	})

	.directive('autofocus', ['$timeout', function($timeout) {
		return {
			restrict: 'A',
			link : function($scope, $element) {
				$timeout(function() {
					$element[0].focus();
				});
			}
		}
	}]);