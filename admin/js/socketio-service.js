'use strict';

var angularSocketIO = angular.module('socketioModule', ['ng']);

/*
 *	Set the address we should connect to here
*/
angularSocketIO.constant('addr', 'https://'+window.location.hostname+':7656');

/*
 *	The actual factory
*/
angularSocketIO.service('socketio', [
	'$rootScope', 
	'addr', 
	function($rootScope, addr) {
		var firstconnect = true,
			socket = null;

		function connect(){
			if(firstconnect === true){
				socket = io.connect(addr,{
					'sync disconnect on unload': false,
					'secure': true
				});

				socket.on('connect', function(){
					socket.emit('amIController', '', function(data){
						if(data !== true)
							socket.emit('IAmController');
					});
				});

				firstconnect = false;
			} else {
				socket.socket.reconnect();
			}
		}

		connect();

		// do not overwrite $emit, its used by socket.io internally
		socket._emit = function() {
			var args = Array.prototype.slice.call(arguments);
			if(args.length<=0)
				return;
			var responseHandler = args[args.length-1];
			if(angular.isFunction(responseHandler)) {
				args[args.length-1] = function() {
					var args = arguments;
					$rootScope.$apply(function() {
						responseHandler.apply(null, args);
					});
				}
			}
			socket.emit.apply(socket, args);
		}

		socket._on = function(e, handler) {
			socket.on(e, function() {
				var args = arguments;
				$rootScope.$apply(function() {
					handler.apply(null, args);
				});
			});
		}

		return socket;
	}
]);