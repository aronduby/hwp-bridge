/**
 * @typedef BroadcastData
 * @type {object}
 * @property {string} body - the body of the message
 * @property {int} ts - current timestamp in seconds (not js milliseconds)
 * @property {array<int>} score - the score of the game
 * @property {int} game_id
 * @property {int} site_id
 * @property {string} title
 * @property {string} opponent
 * @property {string} us
 * @property {'V','JV'} team
 */

require('console-ten').init(console);

const fs = require('fs');
const settings = require('./settings');
const jwtAuth = require('socketio-jwt-auth');

const testMode = process.argv[2] === 'test';
console.log('Test Mode:', testMode);

// DATABASE CONNECTION POOL
const db = require('./db')(settings.mysql);
const dataHandler = require('./data')(db);

// BROADCASTERS
const mids = require('./middleware');

const TwitterBroadcaster = new (require('./broadcasters/twitter'))(settings.twitter, testMode);
TwitterBroadcaster
	.use(mids.isDefined)
	.use(mids.messageWithScore)
	.use(mids.prefixJV);

const TwilioBroadcaster = new (require('./broadcasters/twilio'))(settings.twilio, db, testMode);
TwilioBroadcaster
	.use(mids.isDefined)
	.use(mids.messageWithScore)
	.use(mids.prefixJV);

const SocketBroadcaster = new (require('./broadcasters/socket'))(testMode);
SocketBroadcaster
	.use(mids.isDefined)
	.use(mids.messageWithScore)
	.use(mids.prefixJV);

/**
 * Glue the emitter to the broadcasters
 * @param {GameData} gameData
 * @param {BroadcastData} data
 */
function sendToBroadcasters(gameData, data) {
	data.ts = Math.round(+new Date()/1000);

	// don't just set it the games score because of prototypical inheritance
	// every update will end up having the final score
	data.score = [ gameData.score[0], gameData.score[1] ];

	data.game_id = gameData.game_id;
	data.site_id = gameData.site_id;
	data.title = gameData.title;
	data.opponent = gameData.opponent;
	data.team = gameData.team;

	TwitterBroadcaster.broadcast(data);
	TwilioBroadcaster.broadcast(data);
	SocketBroadcaster.broadcast(data);
}

// GAME
const GameEmitter = require('./game-emitter');
let gameEmitter = new GameEmitter();
gameEmitter.setBroadcaster(sendToBroadcasters);
const gameFactory = require('./game-factory')(dataHandler, gameEmitter);

// SOCKETS
const https = require('https');
const secureServer = https.createServer({
	key: fs.readFileSync(settings.ssl.key),
	cert: fs.readFileSync(settings.ssl.cert)
});
const io = require('socket.io').listen(secureServer,{
	'close timeout': 3600, // 60 minutes to re-open a closed connection
	'browser client minification': true,
	'browser client etag': true,
	'browser client gzip': true
});
// 7656 = polo
secureServer.listen(7656, "0.0.0.0");

// <editor-fold desc="Socket Dynamic Namespace">
io.of((name, query, next) => {
	// make our namespace the name it was given from client (location.hostname) without www or admin
	// apparently that doesn't matter here, so do it on the client ...
	// https://github.com/socketio/socket.io/issues/3489
	// const ns = name.replace(/^\/((www|admin)\.)?/, '');
	// next(null, `/${ns}`);
	next(null, name);
})
// </editor-fold>
// <editor-fold desc="JWT Authentication">
.use(jwtAuth.authenticate(
	{
		secret: settings.jwtAuth.secret,		// required, used to verify the token's signature
		algorithm: settings.jwtAuth.algorithm,  // optional, default to be HS256
		succeedWithoutToken: true				// allow anonymous connections
	}, function(payload, done) {

		// if we have token payload set the user as whatever data it contains
		if (payload && payload.sub) {
			return done(null, payload);
		}

		return done();
	}
))
// </editor-fold>
.on('connect', function(socket) {

	/**
	 * @var socket.request.user
	 * @property {boolean} logged_in - is the user logged in (set by jwtAuth)
	 * @property {boolean} admin - is the user an admin (set from the token)
	 * @property {int} sub - the user id (set from the token)
	 * @propert {int} site_id - the id of the site (set from the token)
	 */

	if (socket.request.user.logged_in && socket.request.user.admin) {
		SocketBroadcaster.addNamespace(socket.request.user.site_id, socket.nsp.name);
		socket.join('admin');

		/**
		 * Opening a game for scoring
		 * @param {int|string} gameId - the game we're opening
		 * @param {function} cb - the callback to send the game data through
		 */
		socket.on('openGame', async (gameId, cb) => {
			try {
				const game = await gameFactory.open(gameId, socket.request.user.sub);
				socket.openGameId = game.game_id;
				socket.broadcast.emit('open', game.data);
				cb(null, game.data);
			} catch(err) {
				console.error(err);
				cb(err);
			}
		});

		/**
		 * Open a game, stealing ownership from the current owner
		 * @param {int|string} gameId - the game we're stealing control of
		 * @param {function} cb - the callback to send the game data through
		 */
		socket.on('stealGame', async (gameId, cb) => {
			try {
				const game = await gameFactory.open(gameId, socket.request.user.sub, true);
				socket.openGameId = game.game_id;

				// TODO -- need a way to map from user sub to socket
				[].forEach((socketId) => {
					io.to(`${socketId}`).emit('gameStolen');
				});

				cb(null, game.data);
			} catch(err) {
				console.error(err);
				cb(err);
			}
		});

		/**
		 * Used to edit the games current players
		 * @param {int} seasonId - the season id we're getting the players for
		 * @param {function} cb - the callback to send the players array through
		 */
		socket.on('getPlayers', async (seasonId, cb) => {
			try {
				const players = await dataHandler.loadPlayers(seasonId);
				cb(null, players);
			} catch (err) {
				cb(err);
			}
		});

		/**
		 * This is our main function between the app and here
		 * @param {string} func - the function on game to call
		 * @param {array} args - array of data to use as arguments for the func call
		 * @param {function} cb - the callback to send confirmation back through
		 */
		socket.on('update', async (func, args, cb) => {
			console.log('Controller sent update', func, args);
			try {
				gameFactory.get(socket.openGameId);
				game[func].apply(game, args);
				await dataHandler.saveGameState(game.data);
				cb(null, true);
			} catch (err) {
				console.error(err);
				cb(err);
			}
		});


		/**
		 * "Undo" back to the given state
		 * @param {GameData} data
		 * @param {function} cb
		 */
		socket.on('undo', async (data, cb) => {
			try {
				const game = gameFactory.get(socket.openGameId);
				game.data = data;
				await dataHandler.saveGameState(game.data);
				cb(null, true);
			} catch (err) {
				cb(err);
			}
		});

		/**
		 * Finalize and close the game
		 * @param {function} cb
		 */
		socket.on('final', async (cb) => {
			console.log('FINAL');
			try {
				const saved = await gameFactory.finalize(socket.openGameId);
				socket.broadcast.emit('final', socket.openGameId);
				delete socket.openGameId;

				cb(null, true);
			} catch (err) {
				cb(err);
			}
		});
	}

	// bind anonymous allowed events here

	// check for an open game for this namespace... somehow...
	// might need to have everyone use a jwt to send along site id for everyone
	/* previous code here
	// not controller, but we have a controller, send the last update
	if(!socket.is_controller && controller_connected){
		console.log("Client connected and controller "+(controller_connected===true ? 'is' : 'is not')+" connected");
		socket.emit('controller_connected');
		// socket.emit('update', updates[updates.length - 1]);
		socket.emit('update', updates);
	}
	*/

	socket.on('echo', function(data) {
		console.log(data);
	});

	socket.on('error', function(e) {
		console.log(e);
		socket.emit('handleError', e);
	});
});