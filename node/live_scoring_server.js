require('console-ten').init(console);
var Q = require('q'),
	extend = require('util')._extend,
	settings = require('./settings'),
	fs = require('fs'),
	testModeLogger = require('./broadcasters/test-mode-logger');

var controller_connected = false,
	updates = [],
	test_mode = false;

if(process.argv[2] === 'test')
	test_mode = true;

console.log('Test Mode:', test_mode);


// DATABASE
var mysql = require('mysql'),
	db_config = settings.mysql,
	db_connection = mysql.createConnection(db_config);

db_connection.on('error', function(err) {
	if (!err.fatal) {
		return;
	}

	if (err.code !== 'PROTOCOL_CONNECTION_LOST') {
		throw err;
	}

	console.log('Re-connecting lost connection to mysql server: ' + err.stack);

	db_connection = mysql.createConnection(db_connection.config);
	handleDisconnect(db_connection);
	db_connection.connect();
});


// BROADCASTERS
var mids = require('./middleware');

var TwitterBroadcaster = new (require('./broadcasters/twitter'))(settings.twitter, test_mode);
TwitterBroadcaster
	.use(mids.isDefined)
	.use(mids.messageWithScore)
	.use(mids.prefixJV);

var dbConnector = function() { return mysql.createConnection(db_config); };
var TwilioBroadcaster = new (require('./broadcasters/twilio'))(settings.twilio, dbConnector, test_mode);
TwilioBroadcaster
	.use(mids.isDefined)
	.use(mids.messageWithScore)
	.use(mids.prefixJV);

// todo - make socket broadcaster at some point

// GAME
var g = require('./game.js'),
	game = new g.Game(),
	broadcast;

// TODO -- setup event emitters for the game using the ./events folder
// game._addListener('sprint', );
// game._addListener('shot', );
// game._addListener('goalAllowed', );
// game._addListener('fiveMeterDrawn', );
// game._addListener('fiveMeterCalled', );
// game._addListener('kickout', );
// game._addListener('shootOutUs', );
// game._addListener('shootOutThem', );
// game._addListener('setQuartersPlayed', );
// game._addListener('timeout', );
// game._addListener('carded', );
// game._addListener('shout', );
// game._addListener('final', );

// SOCKETS
var https = require('https');
var secureServer = https.createServer({
	key: fs.readFileSync(settings.ssl.key),
	cert: fs.readFileSync(settings.ssl.cert)
});
var io = require('socket.io').listen(secureServer,{
	'close timeout': 3600, // 60 minutes to re-open a closed connection
	'browser client minification': true,
	'browser client etag': true,
	'browser client gzip': true
});
// 7656 = polo
secureServer.listen(7656, "0.0.0.0");


io.sockets.on('connection', function(socket){
	console.log("Connection " + socket.id + " accepted.");

	broadcast = function(data){
		data.ts = Math.round(+new Date()/1000);
		// don't just set it the games score because of prototypical inheritence
		// every update will end up having the final score
		data.score = [ game.score[0], game.score[1] ];
		
		// add these in from the current game
		data.game_id = game.game_id;
		data.title = game.title;
		data.opponent = game.opponent;
		data.team = game.team;

		updates.push(data);

		// push to other items
		if(test_mode){
			testModeLogger('SOCKETS', data.msg);
		} else {
			socket.broadcast.emit('update', data);
		}

		TwitterBroadcaster.broadcast(data);
		TwilioBroadcaster.broadcast(data);
	};

	describeStats = function(db_connection) {
        var stat_description_defer = Q.defer();

        db_connection.query("DESCRIBE stats", function(err, result){
            if(err){
                stat_description_defer.reject(err);
                return false;
            }
            stat_description_defer.resolve(result);
        });

        return stat_description_defer;
	};

	loadPlayers = function(db_connection, season_id, team) {
		var player_defer = Q.defer(),
			sql, params;

		if (team) {
			sql = "SELECT p.name_key, p.first_name, p.last_name, pts.number, pts.team FROM player_season pts JOIN players p ON(pts.player_id = p.id) WHERE pts.season_id = ? AND FIND_IN_SET(?, team)";
			params = [season_id, team];
		} else {
			sql = "SELECT p.name_key, p.first_name, p.last_name, pts.number, pts.team FROM player_season pts JOIN players p ON(pts.player_id = p.id) WHERE pts.season_id = ?";
			params = [season_id];
		}

        db_connection.query(sql, params, function(err, result){
            if(err){
                player_defer.reject(err);
                return false;
            }

            for(var i in result) {
            	var p = result[i];
            	p.team = p.team.split(',');
				p.number_sort = parseInt(p.number, 10);
			}

            player_defer.resolve(result);
        });

        return player_defer;
	};

	// not controller, but we have a controller, send the last update
	if(!socket.is_controller && controller_connected){
		console.log("Client connected and controller "+(controller_connected===true ? 'is' : 'is not')+" connected");
		socket.emit('controller_connected');
		// socket.emit('update', updates[updates.length - 1]);
		socket.emit('update', updates);
	}

	socket.on('disconnect', function(){
		if(socket.is_controller){
			console.log("Controller disconnected");
			socket.broadcast.emit('controller_disconnected');
			controller_connected = false;
		}
	});

	// CUSTOM EVENTS
	socket.on('IAmController', function(cb){
		socket.is_controller = true;
		console.log('Controller Connected');
		socket.broadcast.emit('controller_connected');
		controller_connected = true;
		cb()
	});

	socket.on('amIController', function(fn){
		fn(socket.is_controller);
	});

	socket.on('getGameData', function(game_id, cb){
		console.log('getGameData', game_id);

		var db_connection = mysql.createConnection(db_config),
			existing_defer = Q.defer();

		db_connection.query("SELECT * FROM game_stat_dumps WHERE game_id = ?", [game_id], function(err, result){
			if(err || result.length == 0 || result[0].json == null){
				existing_defer.reject(err);
				return false;
			}

			var data = JSON.parse(result[0].json);
			console.log('taking existing data');
			game._takeData(data);
			cb(null, data);
			existing_defer.resolve();
		});

		// if there's no existing game stat data, create from scratch
		existing_defer.promise.fail(function(){
			var game_defer = Q.defer(),
				player_defer = Q.defer(),
				stat_description_defer;
			
			// game data
			db_connection.query('SELECT id AS game_id, site_id, season_id, opponent, team, title_append AS title FROM games WHERE id = ?', [game_id], function(err, game){
				if(err){
					game_defer.reject(err);
					return false;
				}

				game = game[0];
				game_defer.resolve(game);

				// player data
				loadPlayers(db_connection, game.season_id, game.team).promise
					.then(function(players) {
						player_defer.resolve(players)
					})
					.fail(function(err) {
						player_defer.reject(err);
					});
			});

			// stat describe
            stat_description_defer = describeStats(db_connection);

			Q.all([ game_defer.promise, player_defer.promise, stat_description_defer.promise])
			.spread(function(game_data, players, stat_description){
				var data = {};

				data.game_id = game_data.game_id;
				data.site_id = game_data.site_id;
				data.season_id = game_data.season_id;
				data.version = '1.1';
				data.opponent = game_data.opponent;
				data.title = game_data.title;
				data.team = game_data.team;
				data.status = 'start';
				data.quarters_played = 0;
				data.stats = {};
				data.goalie = '';
				data.advantage_conversion = [
					{ drawn: 0, converted: 0 },
					{ drawn: 0, converted: 0 }
				];
				data.kickouts = [[],[]];
				data.boxscore = [[{}], [{}]];
				data.score = [0,0];

				var stats = {};
				for(var i in stat_description){
					stats[stat_description[i].Field] = 0;
				}

				for(var i in players){
					var p = players[i];

					p.number_sort = parseInt(p.number, 10);
					data.stats[p.name_key] = extend(p, stats);
				}

				console.log('creating new data');
				game._takeData(data);
				cb(null, data);

			}).fail(function(error){
				cb(error.message);
				console.log(error.stack);
			})
			.finally(function() {
				db_connection.end();
			});
		});
	});

	socket.on('getPlayers', function(season_id, cb) {
		var db_connection = mysql.createConnection(db_config);
		loadPlayers(db_connection, season_id, null)
			.promise
			.then(function(players) {
				cb(null, players);
			})
			.fail(function(err) {
				cb(err.message);
			})
			.finally(function() {
				db_connection.end();
			});
	});

	socket.on('update', function(func, args, cb){
		if(socket.is_controller){
			try{
				console.log("Controller sent update", func, args);
				if(func in game && func[0]!='_') {
					game[func].apply(game, args);
				}
			} catch(e){
				console.log('>>> caught error, beginning stack:');
				console.log('   ' + e.stack);
				console.log('>>> end of error stack');
				cb(e.message);
			}


			// json encode and save the game to the database
			var tmp = extend({}, game);
			delete tmp._listeners;
			var db_connection = mysql.createConnection(db_config);
			db_connection.query("INSERT INTO game_stat_dumps SET site_id = ?, game_id = ?, json = ? ON DUPLICATE KEY UPDATE json = VALUES(json)", [game.site_id, game.game_id, JSON.stringify(tmp)], function(err, result){
				if(err){ console.log(err); return false; }
				console.log('Saved json to db');
				db_connection.end();
				cb(null, true);
			});
		}
	});

	socket.on('undo', function(data){
		if(socket.is_controller){
			console.log("Controller sent undo");
			game._takeData(data);

			var db_connection = mysql.createConnection(db_config);
			db_connection.query("UPDATE game_stat_dumps SET json = ? WHERE game_id = ?", [JSON.stringify(data), game.game_id], function(err, result){
				if(err){ console.log(err); return false; }
				console.log('Saved json to db');
				db_connection.end();
			});
		}
	});

	socket.on('final', function(data, cb){
		console.log('FINAL');
		if(socket.is_controller){
			console.log("Controller sent final");
			socket.broadcast.emit('final');

			var db_connection = mysql.createConnection(db_config),
				updates_defer = Q.defer(),
				game_defer = Q.defer(),
				stats_defer = Q.defer(),
				recent_defer = Q.defer();

			// push the updates to the database
			db_connection.query("INSERT INTO game_update_dumps SET site_id = ?, game_id = ?, json = ? ON DUPLICATE KEY UPDATE json = VALUES(json)", [game.site_id, game.game_id, JSON.stringify(updates)], function(err, result){
				if(err){
					console.log(err);
					updates_defer.reject(err);
					return false;
				}

				console.log('Saved updates to database', result);
				updates_defer.resolve();
			});

			// save the game data
			var tmp = extend({}, game);
			delete tmp._listeners;
			db_connection.query("UPDATE games SET score_us = ?, score_them = ? WHERE id = ?", [game.score[0], game.score[1], game.game_id], function(err, result){
				if(err){
					console.log(err);
					game_defer.reject(err);
					return false;
				}

				console.log('Saved Score in database', result);
				game_defer.resolve();
			});

			// save the stats data
			db_connection.query("UPDATE game_stat_dumps SET json = ? WHERE game_id = ?", [JSON.stringify(tmp), game.game_id], function (err, result) {
				if(err){
					console.log(err);
					stats_defer.reject(err);
					return false;
				}
				console.log('Saved stats dump to database', result);

				// save stats from game
				// instead of doubling up, just spawn the php cli command
				var log = function(data){
					console.log('' + data);
				};

				// TODO - update with settings
				var spawn = require('child_process').spawn,
					child = spawn('php', [settings.artisanPath, 'scoring:save-stats', game.game_id]);

				child.stdout.on('data', log);
				child.stderr.on('data', log);

				stats_defer.resolve();
			});

			// insert into recent
			db_connection.query("INSERT INTO recent SET site_id = ?, season_id = ?, renderer = 'game', content = ?, created_at = NOW(), updated_at = NOW()", [game.site_id, game.season_id, '['+game.game_id+']'], function(err, result){
				if(err){
					console.log(err);
					recent_defer.reject(err);
					return false;
				}

				console.log('Inserted Recent in database', result);
				recent_defer.resolve();
			});

			updates = []; // reset the updates after it's been finalized

			Q.all([updates_defer, game_defer, stats_defer, recent_defer])
				.finally(function() {
					cb(null, true);
					controller_connected = false;
					socket.disconnect();
					db_connection.end();
				});

		} else {
			cb({msg: "Not a controller"});
		}
	});

	socket.on('echo', function(data){
		console.log(data);
	});

	socket.on('error', function(e){
		socket.emit('handleerror', e);
		console.log(e);
	});

});

function playerNameAndNumber(p) {
	return `#${p.number} ${p.first_name} ${p.last_name}`;
}

function getOrdinal(n) {
   var s=["th","st","nd","rd"],
       v=n%100;
   return n+(s[(v-20)%10]||s[v]||s[0]);
}

// make a list in the Oxford comma style (eg "a, b, c, and d")
// Examples with conjunction "and":
// ["a"] -> "a"
// ["a", "b"] -> "a and b"
// ["a", "b", "c"] -> "a, b, and c"
function oxford(arr, conjunction, ifEmpty){
	var l = arr.length;
	if (!l) return ifEmpty;
	if (l<2) return arr[0];
	if (l<3) return arr.join(` ${conjunction} `);
	arr = arr.slice();
	arr[l-1] = `${conjunction} ${arr[l-1]}`;
	return arr.join(", ");
}