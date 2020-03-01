const Twit = require('twit');
const Middleware = require('./middleware');
const testLogger = require('./test-mode-logger');

/**
 * Broadcasts messages over Twitter
 */
class TwitterBroadcaster extends Middleware {

    /**
     *
     * @param {object} settings - settings for twitter
     * @param {string} settings.consumerKey - the app's twitter consumer key
     * @param {string} settings.consumerSecret - the app's twitter consumer secret
     * @param {string} settings.accessToken - the user's twitter access token
     * @param {string} settings.accessTokenSecret - the user's twitter access token secret
     * @param {boolean} testMode - are we in test mode and should log instead
     */
    constructor(settings, testMode) {
        super();

        this._testMode = testMode;

        this._twit = new Twit({
            consumer_key: settings.consumerKey,
            consumer_secret: settings.consumerSecret,
            access_token: settings.accessToken,
            access_token_secret: settings.accessTokenSecret
        });
    }

    /**
     * Broadcast the given data, routing through our middleware
     * @param {object} data - data to use for broadcasting
     * @param {?object} initialOut - used to supply an initial output with empty body
     */
    broadcast(data, initialOut = {body: ""}) {
        this.go(data, initialOut, function(input, output) {
            if (this._testMode !== true) {
                this._twit.post(
                    'statuses/update',
                    { status: output.body },
                    function(err, reply){
                        if(err) {
                            console.log(err);
                            return;
                        }

                        input.twitter_id = reply.id_str;
                        console.log('Sent tweet successfully');
                    }
                );
            } else {
                testLogger('TWITTER', output.body);
            }
        }.bind(this));
    }
}

module.exports = TwitterBroadcaster;