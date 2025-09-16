const Middleware = require('./middleware');
const testLogger = require('./test-mode-logger');
const { initializeApp, cert } = require('firebase-admin/app');
const { getMessaging } = require('firebase-admin/messaging');
const util = require("util");
class FCMBroadcaster extends Middleware {

    /**
     * Uses FCM to do web push of updates
     *
     * @param {SettingsManager} settingsManager
     * @param {boolean} testMode
     */
    constructor(settingsManager, testMode) {
        super();

        this._settingsManager = settingsManager;
        this._testMode = testMode;

        const serviceAccount = require(settingsManager.globalSettings.fcm.credentialsPath);
        this._app = initializeApp({
            credential: cert(serviceAccount)
        })
    }

    /**
     * Broadcast the given data, routing through our middleware
     *
     * @param {object} data - data to use for broadcasting
     * @param {?object} initialOut - used to supply an initial output with empty body
     */
    broadcast(data, initialOut = {body: ""}) {
        this.go(data, initialOut, async function(input, output) {
            if (!this._settingsManager.globalSettings.fcm.enabled) {
                console.log('fcm not enabled');
                return;
            }

            const topic = util.format(this._settingsManager.globalSettings.fcm.siteTopic, data.site_id);
            const analyticsLabel = `${topic}.game.${data.game_id}`;
            const message = {
                topic: topic,
                // data can only contain strings, so lets JSON encode it
                data: {
                    liveScoringData: JSON.stringify(data),
                    notification: JSON.stringify({
                        title: 'Scoring Update',
                        body: output.body,
                        // enabling the parameters below will make notifications replace the previous one
                        // the renotify options makes it still play the sound
                        // keeping these off for until I get feedback about it
                        // update: have gotten feedback - keep them off, parents want to see everything so they don't
                        // miss anything their kid might have done, even if it means lots of notifications
                        // tag: 'scoring',
                        // renotify: true,
                    })
                },
                // this is required to show up in the reporting since we're going to a web client
                fcmOptions: {
                    analyticsLabel: analyticsLabel,
                }
            };

            if (this._testMode !== true || this._settingsManager.globalSettings.dev) {
                getMessaging().send(message, this._testMode === true)
                    .then(response => {
                        console.log('FCM: Successfully sent message', response);
                    })
                    .catch(err => {
                        console.log('FCM: Error sending message', err);
                    });
            } else {
                testLogger(`FCM`, JSON.stringify(message));
            }
        }.bind(this));
    }
}

module.exports = FCMBroadcaster;