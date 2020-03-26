const Twit = require('twit');
const Middleware = require('./middleware');
const testLogger = require('./test-mode-logger');

/**
 * Broadcasts messages over Twitter
 */
class TwitterBroadcaster extends Middleware {

    /**
     *
     * @param {SettingsManager} settingsManager - a settings manager instance
     * @param {boolean} testMode - are we in test mode and should log instead
     */
    constructor(settingsManager, testMode) {
        super();

        this._settingsManager = settingsManager;
        this._testMode = testMode;

        // start off with the global settings
        const { consumerKey, consumerSecret, accessToken, accessTokenSecret } = this._settingsManager.getGlobal().twitter;
        this._twit = new Twit({
            consumer_key: consumerKey,
            consumer_secret: consumerSecret,
            access_token: accessToken,
            access_token_secret: accessTokenSecret
        });
    }

    /**
     * Broadcast the given data, routing through our middleware
     * @param {object} data - data to use for broadcasting
     * @param {?object} initialOut - used to supply an initial output with empty body
     */
    broadcast(data, initialOut = {body: ""}) {
        this.go(data, initialOut, async function(input, output) {
            if (this._testMode !== true) {

                const authed = await this.setSiteAuth(input.site_id);
                if (!authed) {
                    this.setGlobalAuth();
                    return;
                }

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

                this.setGlobalAuth();

            } else {
                testLogger('TWITTER', output.body);
            }
        }.bind(this));
    }

    setGlobalAuth() {
        const settings = this._settingsManager.getGlobal();
        const { consumerKey, consumerSecret, accessToken, accessTokenSecret } = settings.twitter;
        this._twit.setAuth({
            consumer_key: consumerKey,
            consumer_secret: consumerSecret,
            access_token: accessToken,
            access_token_secret: accessTokenSecret
        });
    }

    async setSiteAuth(siteId) {
        const settings = await this._settingsManager.getForSite(siteId);
        const { enabled, consumerKey, consumerSecret, accessToken, accessTokenSecret } = settings.twitter;
        if (!enabled || !accessToken || !accessTokenSecret) {
            return false;
        }

        this._twit.setAuth({
            consumer_key: consumerKey,
            consumer_secret: consumerSecret,
            access_token: accessToken,
            access_token_secret: accessTokenSecret
        });

        return true;
    }
}

module.exports = TwitterBroadcaster;