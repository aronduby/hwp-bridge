var Middleware = require('./middleware');
var testLogger = require('./test-mode-logger');
var twilio = require('twilio');

/**
 * Broadcasts messages over Twilio
 *
 * @extends Middleware
 *
 * @param {object} settings - twilio related settings
 * @param {string} settings.sid - the twilio accounts sid
 * @param {string} settings.token - the twilio account access token
 * @param {string} settings.from - the phone number twilio sends from, needs the + prefix
 * @param {function} dbConnector - function that returns a db connection
 * @param {boolean} testMode - are we in test mode, currently forced to true
 * @constructor
 */
var TwilioBroadcaster = function(settings, dbConnector, testMode) {
    Middleware.call(this);

    this._dbConnector = dbConnector;

    // always test mode for now
    this._testMode = true;

    this._settings = settings;
    this._twilioClient = new twilio.RestClient(settings.sid, settings.token);
};

// Derive from Middleware
TwilioBroadcaster.prototype = Object.create(Middleware.prototype);
TwilioBroadcaster.prototype.constructor = TwilioBroadcaster;

TwilioBroadcaster.prototype.broadcast = function(data) {
    this.go(data, {body: ""}, function(input, output) {
        if (this._testMode !== true) {
            var self = this;
            var db = this._dbConnector();
            var sql = 'SELECT phone FROM subscriptions WHERE game_id=? OR tournament_id IN (SELECT tournament_id FROM game WHERE game_id=?)';

            db.connect();
            var query = db.query(sql, [input.game_id, input.game_id]);
            query.on('error', function(err) { console.log(err); })
                .on('result', function(row) {
                    self._twilioClient.sendSms({
                        to: row.phone, // Any number Twilio can deliver to
                        from: self._settings.from, // A number you bought from Twilio and can use for outbound communication
                        body: output.body // body of the SMS message
                    }, function(err, responseData) { //this function is executed when a response is received from Twilio
                        if (!err) {
                            console.log("Sent text to " + responseData.to);
                        }
                    });
                });
            db.end();
        } else {
            testLogger('TWILIO', output.body);
        }
    }.bind(this));
};

module.exports = TwilioBroadcaster;

/**
 * Former code that dealt with incoming calls from twilio
 * I really don't like how it was done, and we're not going to support it right off the bat
 * but keeping it around for reference for now

 {

    incomingCall: function(data) {
        var d = Q.defer();

        this.getContent()
            .then(function (data) {
                var rsp = new twilio.TwimlResponse();
                rsp.say('Welcome to Hudsonville Water Polo')
                    .say(data.msg)
                    .gather({
                        action: 'http://www.hudsonvillewaterpolo.com/norewrite/twilio-subscribeOrList.php?subscribe_id=' + data.subscribe_id,
                        finishOnKey: '*',
                        numDigits: 1
                    }, function () {
                        if (data.subscribe_id !== null)
                            this.say('Press 1 to get text alerts during that game');
                        this.say('Press 2 to hear outcome of the past 5 games');
                    });
                d.resolve(rsp);
            }).fail(function (err) {
            console.log(err);
        }).done();

        return d.promise;
    },

    getContent: function() {
        var msg = '',
            subscribe_id = null,
            def = Q.defer();

        if (updates.length > 0) {
            var latest = updates[updates.length - 1],
                status = '';

            if (latest.score[0] > latest.score[1]) {
                status = 'leads';
            } else if (latest.score[0] < latest.score[1]) {
                status = 'trails';
            } else {
                status = 'tied with';
            }

            def.resolve({
                msg: 'The current ' + latest.title + ' Hudsonville ' + status + ' ' + latest.opponent + ' ' + latest.score[0] + ' to ' + latest.score[1],
                subscribe_id: latest.game_id
            });

        } else {
            this.db = mysql.createConnection(db_config);
            this.db.connect();
            sql = 'SELECT g.game_id, g.title_append, g.start, g.opponent, l.title AS location FROM game g LEFT JOIN location l USING(location_id) WHERE start > NOW() ORDER BY start ASC LIMIT 1';
            var query = this.db.query(sql, function (err, row) {
                if (err) {
                    console.log(query.sql);
                    console.log(err);
                    def.resolve({
                        msg: 'Sorry but we could not get game information from the database at this time, please try again later.',
                        subscribe_id: null
                    });
                } else {
                    if (row.length == 1) {
                        row = row[0];
                        var resolve_with = {
                            msg: 'Our next game is ' + dateFormat(row.start, 'dddd "the" dS') + ' ' + row.title_append + ' versus ' + row.opponent + ' at ' + row.location + ' at ' + dateFormat(row.start, 'h:MMtt'),
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

}

// we have to setup a http server for twilio
// this is done here so we can handle live updates when someone calls
// everything else is handled in site/twilio/ since they won't need live updates
var express = require('express');
var app = express();
app.use(express.bodyParser());
app.post('/incomingCall', function(req, res){
	TwilioController.incomingCall(req.body)
	.then(function(twrsp){
		res.type('text/xml');
    	res.send(twrsp.toString());
	});
});

app.listen(2255); // 2255 = call
 */