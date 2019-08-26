require('console-ten').init(console);
var Q = require('q'),
	extend = require('util')._extend,
	settings = require('./settings'),
	fs = require('fs');


var controller_connected = false,
	updates = [],
	test_mode = false;

if(process.argv[2] == 'test')
	test_mode = true;

console.log('Test Mode:', test_mode);


// MYSQL
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

// GAME
var g = require('./game.js'),
	game = new g.Game(),
	broadcast;

game._addListener('sprint', function(player, won){
	var data = {};
	
	data.msg = 'Start of ';
	switch(this.quarters_played){
		case 0:
			data.msg += 'Hudsonville vs ' + this.opponent;
			break;
		case 1:
			data.msg += 'the 2nd';
			break;
		case 2:
			data.msg += 'the 3rd';
			break;
		case 3:
			data.msg += 'the 4th';
			break;
		case 4:
			data.msg += 'the 1st OT';
			break;
		case 5:
			data.msg += 'the 2nd OT';
			break;
	}
	data.msg += ' -- Sprint Won By ';

	if(won == false){
		data.msg += this.opponent;
	} else {
		var p = this.stats[player];
		data.msg += 'Hudsonville\'s #' + p.number + ' ' + p.first_name + ' ' + p.last_name;
	}

	broadcast(data);
});

game._addListener('shot', function(player, made, assist){
	if(made == true){
		var data = {};

		// no longer used - see goalAllowed
		if(player == false){
			data.msg = this.opponent + ' Goal';
		} else {
			var shooter = this.stats[player];
			var assisted = assist ? this.stats[assist] : false;

			data.msg = 'Hudsonville Goal';

			// advantage?
			if (this.kickouts[0].length !== this.kickouts[1].length) {
				data.msg += ` off a ${(6 - this.kickouts[0].length)} on ${(6 - this.kickouts[1].length)}! ${playerNameAndNumber(shooter)} scoring his ${getOrdinal(shooter.goals)}`;
			} else {
				data.msg += `! ${playerNameAndNumber(shooter)}, his ${getOrdinal(shooter.goals)}`;
			}

			// assist?
			if (assisted != false) {
				data.msg += `, with the assist by ${playerNameAndNumber(assisted)}`;
			}

			if (this.kickouts_drawn_by.length) {
				var drawnStrings = [];
				new Set(this.kickouts_drawn_by)
					.forEach((nameKey) => {
						drawnStrings.push(playerNameAndNumber(this.stats[nameKey]));
					});

				data.msg += `. Advantage${this.kickouts_drawn_by.length > 1 ? 's' : ''} drawn by ${oxford(drawnStrings, 'and')}`;
			}
		}

		broadcast(data);
	}
});

game._addListener('goalAllowed', function(number){
	var data = {
		msg: this.opponent + ' Goal' + (number ? ' by #'+number : '')
	};

	broadcast(data);
});

game._addListener('fiveMeterDrawn', function(drawn_by, taken_by, made){
	if(made == true || made == 'made'){
		var data = {},
			drawn_by = this.stats[drawn_by],
			taken_by = this.stats[taken_by];

		data.msg = 'Hudsonville Goal! -- #'+taken_by.number+' '+taken_by.first_name+' '+taken_by.last_name+' on a 5 meter shot ';
		if(drawn_by.number == taken_by.number){
			data.msg += 'they drew';
		} else {
			data.msg += 'drawn by #'+drawn_by.number+' '+drawn_by.first_name+' '+drawn_by.last_name;
		}

		broadcast(data);
	}
});

game._addListener('fiveMeterCalled', function(called_on, taken_by, made){
	var data = {};

	if(made == true || made == 'made'){
		data.msg = this.opponent+' Goal, #' + taken_by + ', off a 5 meter';
	} else if(made == false || made == 'blocked'){
		var g = this.stats[this.goalie];
		data.msg = '#'+g.number+' '+g.first_name+' '+g.last_name+' with a HUGE 5 meter block on ' + this.opponent + '\'s #' + taken_by;
	} else {
		return true;
	}

	broadcast(data);
});

game._addListener('kickout', function(player){
	var p = this.stats[player],
		data = {};

	data.msg = 'Hudsonville kick-out on #'+p.number+' '+p.first_name+' '+p.last_name+', his '+getOrdinal(p.kickouts);

	broadcast(data);

});

game._addListener('shootOutUs', function(player, made){
	var data = {};

	var p = this.stats[player];
	switch(made){
		case true:
		case 'made':
			data.msg = 'Hudsonville Goal! -- #'+p.number+' '+p.first_name+' '+p.last_name;
			break;

		case false:
		case 'blocked':
			data.msg = '#'+p.number+' '+p.first_name+' '+p.last_name+' shot is blocked';
			break;

		case 'missed':
		default:
			data.msg = '#'+p.number+' '+p.first_name+' '+p.last_name+' shot is no good';
			break;
	}

	broadcast(data);
});

game._addListener('shootOutThem', function(number, made){
	var data = {};

	switch(made){
		case true:
		case 'made':
			data.msg = this.opponent+' Goal -- #'+number;
			break;

		case false:
		case 'blocked':
			var g = this.stats[this.goalie];
			data.msg = this.opponent + ' shot by #' + number + ' BLOCKED by #'+g.number+' '+g.first_name+' '+g.last_name;
			break;

		case 'missed':
			data.msg = this.opponent+' shot by #' + number + ' missed';
			break;
	}

	broadcast(data);
});

game._addListener('setQuartersPlayed', function(quarters){
	var data = {},
		post = '';

	data.msg = 'At the end of the';

	switch(quarters){
		case 4:
			post = ". We're going into Overtime!";

		case 1:
		case 2:
		case 3:
		case 4:
			data.msg += ' '+getOrdinal(quarters)+' Hudsonville';
			break;

		case 5:
			data.msg += ' 1st OT Hudsonville';
			break;

		case 6:
			data.msg += ' the 2nd OT Hudsonville';
			post = ". It's a Shoot-Out!"
			break;
	}

	if(this.score[0] > this.score[1]){
		data.msg += ' LEADS';
	} else if(this.score[0] == this.score[1]){
		data.msg += ' TIED WITH';
	} else {
		data.msg += ' TRAILS';
	}

	data.msg += ' '+this.opponent + post;


	broadcast(data);
});


game._addListener('timeout', function(team, time){
	console.log(team, time);
	var msg = team + ' Time Out';
	
	if(time.minutes == undefined && time.seconds != undefined){
		msg += ', '+time.seconds+' second'+(time.seconds > 1 ? 's' : '')+' left in ';
	} else if (time.minutes != undefined){
		msg += ', '+time.minutes;
		if(time.seconds != undefined){
			msg += ':'+time.seconds;
		} else {
			msg += ' minute' + (time.minutes > 1 ? 's' : '');
		}

		msg += ' left in ';
	}

	if(time.minutes != undefined || time.seconds != undefined){
		console.log(this.quarters_played);
		switch(this.quarters_played){
			case 0:
				msg += 'the first quarter';
				break;
			case 1:
				msg += 'the second quarter';
				break;
			case 2:
				msg += 'the third quarter';
				break;
			case 3:
				msg += 'regulation play';
				break;
			case 4:
				msg += 'the first overtime';
				break;
			case 5:
				msg += 'the second overtime';
				break;
		}
	}

	broadcast({msg: msg});
});

game._addListener('carded', function(who, color){
	broadcast({msg: 'A '+color+' card for '+who});
});

game._addListener('shout', function(msg){
	broadcast({'msg': msg});
});


game._addListener('final', function(){
	var data = {};
	data.msg = 'Final Result - Hudsonville';

	if(this.score[0] > this.score[1]){
		data.msg += ' DEFEATS ';
	} else if(this.score[0] == this.score[1]){
		data.msg += ' TIES ';
	} else {
		data.msg += ' LOSES TO ';
	}

	data.msg += ' '+this.opponent;

	broadcast(data);
});




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

		// console.log(updates);

		// push to other items
		if(test_mode){
			fs.appendFile('broadcast-log.txt', 'SOCKETS:('+data.msg.length+') '+data.msg+"\n", null, ()=>{});
		} else {
			socket.broadcast.emit('update', data);
		}
		TwitterController.broadcast(data);
		// TwilioController.broadcast(data);
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

// TWITTER
var Twit = require('twit');
var TwitterController = {
	twit: null,
	init: function(){
		this.twit = new Twit({
			consumer_key: 'GNQo7XCNbQHZy2XW0gCg',
			consumer_secret: 'Ym4xmm4gkfe74kCK0Z9tyLNhjoHqc4Qho142tH81gY',
			access_token: '783299587-TGM7b75OktcXRJAUlXLpClTtfqI3UzCSKxlDgfym',
			access_token_secret: 'Aic90o7VwQGqAMRf2c8OVJ5cafLWx2eLiI5hSs4Phg'
		});
	},
	broadcast: function(data){
		if(data != undefined){
			post_msg = (data.team=='JV' ? '(JV) ' : '') + data.msg + ' -- ' + data.score[0] + ' - ' + data.score[1];
			if(test_mode != true){
				this.twit.post('statuses/update', { status: post_msg }, function(err, reply){
					if(err) {
						console.log(err);
						return;
					}

					data.twitter_id = reply.id_str;
					console.log('Sent tweet successfully');
				});
			} else {
				fs.appendFile('broadcast-log.txt', 'TWITTER:('+post_msg.length+') '+post_msg+"\n", function (err) {
					if (err) throw err;
				});
			}
		}
	}
};
TwitterController.init();


// TWILIO
var dateFormat = require('dateformat'),
	twilio = require('twilio');

var TwilioController = {
	client: null,
	db: null,
	init: function(){
		this.client = new twilio.RestClient('ACc9dc1a5ab1834988a0c86f1a131b2a8f', 'dbb4e4faf563282bbd20427b06613c95');
		this.db = db_connection;
	},
	broadcast: function(data){
		if(data != undefined){
			var post_msg = (data.team=='JV' ? '(JV) ' : '') + data.msg + ' -- ' + data.score[0] + ' - ' + data.score[1],
				t = this;

			//console.log((test_mode==true?'NOT ':'')+'sending to twilio:', post_msg);

			if(test_mode != true){
				this.db = mysql.createConnection(db_config);
				this.db.connect();
				sql = 'SELECT phone FROM subscription WHERE game_id=? OR tournament_id IN (SELECT tournament_id FROM game WHERE game_id=?)';
				var query = this.db.query(sql, [data.game_id, data.game_id]);
				query
					.on('error', function(err){
						console.log(err);
					})
					.on('result', function(row){
						console.log(row, post_msg);
						//Send an SMS text message
						t.client.sendSms({
							to: row.phone, // Any number Twilio can deliver to
							from: '+16169657991', // A number you bought from Twilio and can use for outbound communication
							body: post_msg // body of the SMS message
						}, function(err, responseData) { //this function is executed when a response is received from Twilio
							if (!err) {
								console.log("Sent text to " + responseData.to);
							}
						});
					});
				this.db.end();
			} else {
				fs.appendFile('broadcast-log.txt', 'TWILIO:('+post_msg.length+') '+post_msg+"\n", function (err) {
					if (err) throw err;
				});
			}
		}
	},
	incomingCall: function(data){
		var d = Q.defer();

		this.getContent()
		.then(function(data){
			var rsp = new twilio.TwimlResponse();
			rsp.say('Welcome to Hudsonville Water Polo')
				.say(data.msg)
				.gather({
					action: 'http://www.hudsonvillewaterpolo.com/norewrite/twilio-subscribeOrList.php?subscribe_id='+data.subscribe_id,
					finishOnKey:'*',
					numDigits: 1
				}, function() {
					if(data.subscribe_id !== null)
						this.say('Press 1 to get text alerts during that game');
					this.say('Press 2 to hear outcome of the past 5 games');
				});
			d.resolve(rsp);
		}).fail(function(err){
			console.log(err);
		}).done();

		return d.promise;
	},

	getContent: function(){
		var msg = '',
			subscribe_id = null,
			def = Q.defer();

		if(updates.length > 0){
			var latest = updates[updates.length - 1],
				status = '';

			if(latest.score[0] > latest.score[1]){
				status = 'leads';
			} else if( latest.score[0] < latest.score[1]){
				status = 'trails';
			} else {
				status = 'tied with';
			}

			def.resolve({
				msg: 'The current '+latest.title+' Hudsonville '+status+' '+latest.opponent+' '+latest.score[0]+' to '+latest.score[1],
				subscribe_id: latest.game_id
			});
		
		} else {
			this.db = mysql.createConnection(db_config);
			this.db.connect();
			sql = 'SELECT g.game_id, g.title_append, g.start, g.opponent, l.title AS location FROM game g LEFT JOIN location l USING(location_id) WHERE start > NOW() ORDER BY start ASC LIMIT 1';
			var query = this.db.query(sql, function(err, row){
				if(err){
					console.log(query.sql);
					console.log(err);
					def.resolve({
						msg: 'Sorry but we could not get game information from the database at this time, please try again later.',
						subscribe_id: null
					});
				} else {
					if(row.length == 1){
						row = row[0];
						var resolve_with = {
							msg: 'Our next game is '+dateFormat(row.start, 'dddd "the" dS')+' '+row.title_append+' versus '+row.opponent+' at '+row.location+' at '+dateFormat(row.start, 'h:MMtt'),
							subscribe_id: row.game_id
						};
						def.resolve(resolve_with);							
					} else {
						def.resolve({
							msg: 'There are currently no upcoming games in the system',
							subscribe_id: null
						});
					}
				}
			});
			this.db.end();
		}
		return def.promise;
	}
};
TwilioController.init();

// we have to setup a http server for twilio
// this is done here so we can handle live updates when someone calls
// everything else is handled in site/twilio/ since they won't need live updates
// var express = require('express');
// var app = express();
// app.use(express.bodyParser());
// app.post('/incomingCall', function(req, res){
// 	TwilioController.incomingCall(req.body)
// 	.then(function(twrsp){
// 		res.type('text/xml');
//     	res.send(twrsp.toString());
// 	});
// });
// app.listen(2255); // 2255 = call

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