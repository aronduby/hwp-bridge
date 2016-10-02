'use strict';

/* Filters */

angular.module('myApp.filters', [])
	.filter("toArray", function(){
		return function(obj, skipHash) {
			if(skipHash !== true)
				skipHash = false;

			var result = [];
			angular.forEach(obj, function(val, key) {
				if(!(key == '$$hashKey' && skipHash === true)){
					result.push(val);
				}
			});
			return result;
		};
	})

	.filter('sum', function () {
		return function (input, initial) {
			return !angular.isArray(input)
				? input
				: input.reduce(function(prev, curr) {
				return prev + curr;
			}, initial || 0);
		}
	})

	.filter('capAndName', function(){
		return function(player){
			return '#'+player.number+' - '+player.first_name+' '+player.last_name;
		}
	})

	.filter('truncate', function(){
		return function(input, len, append){
			if(len == undefined)
				len = 10;
			if(append == undefined)
				append = '...';

			return input.substr(0,len)+append;
		}
	});
