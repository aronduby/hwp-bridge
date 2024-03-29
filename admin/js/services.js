'use strict';

// Setup socket.io as normal
var ns = location.hostname.replace(/^((www|admin)\.)?/, '');
var addr = 'https://' + window.location.hostname + ':7656/'+ns;
var socket = io(addr, {
	'sync disconnect on unload': true,
	'secure': true,
	'query': 'auth_token=' + token,
	'timeout': window.location.hostname.endsWith('.local') ? 60000 : 2000
});


/* Services */
angular.module('myApp.services', [])
// .factory('$exceptionHandler', ['$window', function($window) {
// 	return function(exception, cause) {
// 		// $window.alert('Error happened, view console');
// 		console.error(exception);
// 		// throw exception;
// 	};
// }])
	.service('localCopy', ['$localStorage', function ($localStorage) {

		function LocalCopy($localStorage) {

			this.storage = $localStorage;

			this.save = function (data) {
				this.storage['game-' + data.game_id] = data;
			}

		}

		return new LocalCopy($localStorage);
	}])
	.service('history', ['$localStorage', function ($localStorage) {

		function History($localStorage) {

			this.storage = $localStorage.$default({
				saves: []
			});

			this.save = function (func, args, data) {
				this.storage.saves.unshift({
					'func': func,
					'args': Array.prototype.slice.call(args),
					'data': JSON.stringify(data)
				});

				if (this.storage.saves.length > 6) {
					this.storage.saves.pop();
				}
			};

			this.clear = function () {
				this.storage.saves = [];
			};
		}

		return new History($localStorage);

	}])
	.service('game', ['$q', '$modal', function ($q, $modal) {
		return {
			game_id: 0,
			season_id: 0,
			site_id: 0,
			version: '1.1',
			us: 'Hudsonville', // TODO -- not hardcoded at some point
			opponent: null,
			status: null,
			quarters_played: 0,
			stats: {},
			goalie: null,
			advantage_conversion: [
				{drawn: 0, converted: 0},
				{drawn: 0, converted: 0}
			],
			kickouts: [[], []],
			kickouts_drawn_by: [],
			boxscore: [[{}], [{}]],
			score: [0, 0],

			loaded: false,
			_socket: false,
			_history: false,
			_local_copy: false,

			setHistory: function (history) {
				this._history = history;
			},

			setSocket: function (socket) {
				this._socket = socket;
			},

			setLocalCopy: function (local_copy) {
				this._local_copy = local_copy;
			},

			/**
			 *
			 * @param game_id
			 * @param q
			 * @param dontTakeLoaded - override for when we don't want to take the data from the server
			 * 	this is potentially dangerous because it treats the scoring client as the source of truth instead of the server
			 * 	given that, we should only be using this during reconnect where we have things in place to catch the server up
			 */
			loadData: function (game_id, q, dontTakeLoaded) {
				var self = this;
				this._socket.emit('openGame', game_id, function (err, data) {

					new Promise(async (resolve, reject) => {
						if (err) {
							if (err.type === 'LockedError') {
								// if its a lock error give the option to steal it
								try {
									var stealConfirm = $modal.open({
										templateUrl: 'partials/modals/locked.html',
										controller: YesNoModalCtrl,
										resolve: {
											title: function () {
												return 'I dont matter';
											},
										}
									});

									var result = await stealConfirm.result;
									if (result) {
										self._socket.emit('stealGame', game_id, (err, data) => {
											if (err) {
												reject(err);
												return;
											}

											resolve(data);
										})
									} else {
										reject();
									}
								} catch(e) {
									reject(e);
								}
							} else {
								reject(err);
							}
						} else {
							resolve(data);
						}
					})
						.then((data) => {
							if (dontTakeLoaded !== true) {
								self.takeData(data);
								self.loaded = true;
							}
							q.resolve();
						})
						.catch((err) => {
							console.error(err);
							window.location.replace('events.php');
							q.reject();
							return;
						});
				});
			},

			takeData: function (data) {
				// loop through and set the data
				for (var i in data) {
					if (this.hasOwnProperty(i)) {
						if (i[0] !== '_')
							this[i] = data[i];
					}
				}
			},

			export: function () {
				var d = {},
					flds = [
						'game_id',
						'site_id',
						'season_id',
						'version',
						'us',
						'opponent',
						'status',
						'quarters_played',
						'stats',
						'goalie',
						'advantage_conversion',
						'kickouts',
						'kickouts_drawn_by',
						'boxscore',
						'score'
					];
				for (var i in flds) {
					d[flds[i]] = this[flds[i]];
				}
				return d;
			},

			addToBoxScore: function (us, player) {
				var idx = us ? 0 : 1;

				if (this.boxscore[idx][this.quarters_played][player] == undefined) {
					this.boxscore[idx][this.quarters_played][player] = 0;
				}
				this.boxscore[idx][this.quarters_played][player]++;
			},

			push: function (func, args, cb) {
				console.info(func, args);
				var data = this.export();

				if (cb == null) {
					cb = function (err, saved) {
						if (err) {
							throw err;
						}
					};
				}

				this._local_copy.save(data);
				this._history.save(func, args, JSON.stringify(data));
				this._socket.emit('update', func, Array.prototype.slice.call(args), cb);
			},

			final: function () {
				this.push('final', arguments);
				var self = this;

				return new Promise((resolve, reject) => {
					self._socket.emit('final', function (err, data) {
						if (err) {
							console.log(err);
							reject(err);
						} else {
							self._history.clear();
							resolve(data);
						}
					});
				});
			},

			shot: function (player, made, assisted_by) {
				this.stats[player].shots++;

				if (made) {
					this.stats[player].goals++;
					if (assisted_by) {
						this.stats[assisted_by].assists++;
					}
					this.score[0]++;

					this.addToBoxScore(true, player);

					if (this.kickouts[1].length > 0) {
						this.advantage_conversion[0].converted++;
						this.stats[player].advantage_goals++;
					}
				}

				if (made) {
					this.resetKickouts();
				}
				this.push('shot', arguments);
			},

			steal: function (player) {
				this.stats[player].steals++;
				this.push('steal', arguments);
			},

			turnover: function (player) {
				this.stats[player].turnovers++;
				this.push('turnover', arguments);
			},

			block: function (player) {
				this.stats[player].blocks++;
				this.push('block', arguments);
			},

			kickout: function (player) {
				this.stats[player].kickouts++;
				this.kickouts[0].push(player);

				this.advantage_conversion[1].drawn++;
				this.push('kickout', arguments);
			},

			kickoutDrawn: function (player) {
				this.stats[player].kickouts_drawn++;
				this.kickouts[1].push(1);
				this.kickouts_drawn_by.push(player);

				this.advantage_conversion[0].drawn++;
				this.push('kickoutDrawn', arguments);
			},

			kickoutOver: function (player) {
				if (player === false) {
					this.kickouts[1].shift();
					this.kickouts_drawn_by.shift();
				} else {
					var i = this.kickouts[0].indexOf(player);
					this.kickouts[0].splice(i, 1);
				}
				this.push('kickoutOver', arguments);
			},

			resetKickouts: function () {
				this.kickouts = [[], []];
				this.kickouts_drawn_by = [];
			},

			save: function () {
				this.stats[this.goalie].saves++;
				this.push('save', arguments);
			},

			goalAllowed: function (number) {
				this.stats[this.goalie].goals_allowed++;
				this.score[1]++;

				//box scores
				this.addToBoxScore(false, number);

				if (this.kickouts[0].length > 0) {
					this.advantage_conversion[1].converted++;
					this.stats[this.goalie].advantage_goals_allowed++;
				}

				this.resetKickouts();

				this.push('goalAllowed', arguments);
			},

			sprint: function (player, won) {
				this.stats[player].sprints_taken++;
				if (won) {
					this.stats[player].sprints_won++;
				}
				this.push('sprint', arguments);
			},

			fiveMeterDrawn: function (drawn_by, taken_by, made) {
				this.stats[drawn_by].five_meters_drawn++;
				this.stats[taken_by].five_meters_taken++;
				this.stats[taken_by].shots++;
				if (made === true || made === 'made') {
					this.stats[taken_by].five_meters_made++;
					this.stats[taken_by].goals++;
					this.score[0]++;

					this.addToBoxScore(true, taken_by);
				}
				this.push('fiveMeterDrawn', arguments);
			},

			fiveMeterCalled: function (called_on, taken_by, made) {
				this.stats[called_on].five_meters_called++;
				this.stats[called_on].kickouts++;
				this.stats[this.goalie].five_meters_taken_on++;

				switch (made) {
					case true:
					case 'made':
						this.score[1]++;
						this.stats[this.goalie].goals_allowed++;
						this.stats[this.goalie].five_meters_allowed++;

						this.addToBoxScore(false, taken_by);

						break;

					case false:
					case 'blocked':
						this.stats[this.goalie].five_meters_blocked++;
						this.stats[this.goalie].saves++;
						break;

					case 'missed':
						// don't actually need to do anything stat wise
						break;
				}
				this.push('fiveMeterCalled', arguments);
			},

			shootOutUs: function (taken_by, made) {
				this.stats[taken_by].shots++;
				this.stats[taken_by].shoot_out_taken++;
				switch (made) {
					case true:
					case 'made':
						this.score[0]++;
						this.stats[taken_by].goals++;
						this.stats[taken_by].shoot_out_made++;
						this.addToBoxScore(true, taken_by);
						break;

					case false:
					case 'blocked':
					case 'missed':
						break;
				}

				this.push('shootOutUs', arguments);
			},

			shootOutThem: function (taken_by, made) {
				this.stats[this.goalie].shoot_out_taken_on++;
				switch (made) {
					case true:
					case 'made':
						this.score[1]++;
						this.stats[this.goalie].goals_allowed++;
						this.stats[this.goalie].shoot_out_allowed++;
						this.addToBoxScore(false, taken_by);
						break;

					case false:
					case 'blocked':
						this.stats[this.goalie].shoot_out_blocked++;
						this.stats[this.goalie].saves++;
						break;

					case 'missed':
						break;
				}

				this.push('shootOutThem', arguments);
			},

			changeGoalie: function (new_goalie) {
				this.goalie = new_goalie;
				this.push('changeGoalie', arguments);
			},

			setStatus: function (new_status) {
				this.status = new_status;
				this.push('setStatus', arguments);
			},

			setQuartersPlayed: function (quarters) {
				this.quarters_played = quarters;

				// since we're adding the ability to go straight to shoot out
				// but other things require shootout to be quarter 6
				// we have to make sure we have the in between box scores
				while (this.boxscore[0].length < quarters + 1) {
					this.boxscore[0].push({});
					this.boxscore[1].push({});
				}

				this.push('setQuartersPlayed', arguments);
			},

			/*
			 * 	aren't tracking but push the info still
			*/
			timeout: function (us, time) {
				this.push('timeout', [us, time]);
			},

			carded: function (recipient, color) {
				this.push('carded', arguments);
			},

			shout: function (msg) {
				this.push('shout', arguments);
			},

			updatePlayers: function(add, remove) {
				var d = $q.defer(),
					game = this;

				// do the server first
				this.push('updatePlayers', [add, remove], function(err, rsp) {
					// then do local
					var keys = Object.keys(Object.values(game.stats)[0]);
					add.forEach((player) => {
						game.stats[player.name_key] = keys.reduce((acc, key) => {
							acc[key] = 	player[key] || 0;
							return acc;
						}, {});
					});

					// this will orphan some total stats (ie goals)
					remove.forEach(player => {
						delete game.stats[player.name_key];
					});

					d.resolve(rsp);
				});

				return d.promise;
			}
		};

	}])
	.service('fakeSocket', ['$localStorage', function ($localStorage) {

		function FakeSocket($localStorage) {

			this.storage = $localStorage.$default({
				updates: []
			});

			this.emit = function () {
				var args = Array.prototype.slice.call(arguments);
				this.storage.updates.push(args);
			};

			this.getUpdates = function () {
				return this.storage.updates;
			};

			this.clear = function () {
				this.storage.updates = [];
			};
		}

		return new FakeSocket($localStorage);
	}])
	.service('gameStolen', ['socket', 'game', '$modal', function(socket, game, $modal) {
		socket.on('gameStolen', async (gameId) => {
			if (gameId+'' === game.game_id+'') {
				try {
					const stealConfirm = $modal.open({
						templateUrl: 'partials/modals/stolen.html',
						controller: ($scope, $modalInstance) => {
							$scope.close = $modalInstance.close;
						}
					});

					await stealConfirm.result;

				} catch(err) {}

				window.location.replace('events.php');
			}
		});

		return {};
	}])
	.factory('socket', ['$rootScope', '$window', '$modal', function ($rootScope, $window, $modal) {

		socket.on('error', (err) => {
			console.error(err);
			var errCtrl = function ($scope, $modalInstance) {
				$scope.err = err;

				$scope.logout = function () {
					$window.location.replace($window.location.origin + '/logout.php');
				};

				$scope.cancel = function () {
					$modalInstance.dismiss('cancel');
				};
			};

			var errInstance = $modal.open({
				templateUrl: 'partials/modals/error.html',
				controller: errCtrl,
				backdrop: 'static'
			});
		});

		/*
		 *	Create scoped objects which correspond to controllers scopes
		 *	this allows us to easily remove events for a controllers scope when it gets destroyed
		 */
		var scopes = {};

		function Scope(id) {
			this.id = id;
			this.events = {};
			scopes[id] = this;
		}

		Scope.prototype.on = function (e, handler) {
			if (this.events[e] == undefined) {
				this.events[e] = [];
			}
			var wrapped_handler = wrapHandler(handler);
			this.events[e].push(wrapped_handler);
			addListener(e, wrapped_handler);
			return this;
		};

		Scope.prototype.clear = function () {
			// loop through all of our events and removeListener
			var keys = Object.keys(this.events);
			for (var i = 0; i < keys.length; ++i) {
				var e = keys[i],
					handlers = this.events[e];

				for (var j = 0; j < handlers.length; ++j) {
					socket.removeListener(e, handlers[j]);
				}
			}
		};

		/*
		 *	Since we can remove things now we have to be able to have a reference to the actual function
		 *	since we have to use $rootScope.apply to bring the functions into "Angular Land" we can't just
		 *	use the bare handler, so this function will wrap the supplied handler with the proper Angular
		 *	code and return that function, which can be stored and used with removeListener
		 */
		function wrapHandler(handler) {
			return function () {
				var args = arguments;
				$rootScope.$apply(function () {
					handler.apply(null, args);
				});
			}
		}

		/*
		 *	This actually adds the event listener to the socket. Make sure the handler has already been
		 *	wrapped using the wrapHandler() function above
		 */
		function addListener(e, wrapped_handler) {
			socket.on(e, wrapped_handler);
		}


		/*
		 *	Go between object which actually gets returned
		 */
		var glue = {
			emit: function () {
				var args = Array.prototype.slice.call(arguments);
				if (args.length <= 0)
					return;
				var responseHandler = args[args.length - 1];
				if (angular.isFunction(responseHandler)) {
					args[args.length - 1] = function () {
						var args = arguments;
						$rootScope.$apply(function () {
							responseHandler.apply(null, args);
						});
					}
				}
				socket.emit.apply(socket, args);
				return this;
			},

			on: function (e, handler) {
				addListener(e, wrapHandler(handler));
				return this;
			},

			addScope: function (id) {
				var scope = glue.getScope(id);
				if (scope == false) {
					scope = new Scope(id);
				}
				return scope;
			},

			getScope: function (id) {
				if (scopes[id]) {
					return scopes[id];
				} else {
					return false;
				}
			}
		};

		return glue;
	}]);