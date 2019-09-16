const eventListeners = require('./events');

/**
 * @class
 */
class GameEmitter {

    constructor() {

        this.listeners = {};
        this.broadcaster = null;

        this.bindEventListeners(eventListeners);
    }

    /**
     * Binds all of the events loaded from the event listeners
     *
     * @param {object<string, function>} listeners - object where keys are string events names with listeners for value
     */
    bindEventListeners(listeners) {
        this.listeners = listeners;
    }

    /**
     * Removes all of the currently bound listeners
     */
    removeAllListeners() {
        this.listeners = {};
    }

    /**
     * Sets the broadcaster function that is called after an event is processed
     * @param {GameEmitter~broadcaster} fn - the broadcaster for the events
     */
    setBroadcaster(fn) {
        this.broadcaster = fn;
    }

    /**
     * Gets passed to the game as the emitter
     *
     * @param {GameData} gameData - data about the game
     * @param {string} key - the event that was triggers
     * @param {array} args - array of arguments passed through
     */
    emit(gameData, key, args) {
        if (this.listeners.hasOwnProperty(key)) {
            const send = [gameData, ...args];
            const rsp = this.listeners[key].apply(null, send);

            if (rsp && this.broadcaster) {
                this.broadcaster(gameData, rsp);
            }
        }
    }
}

/**
 * Broadcaster callback
 * @callback GameEmitter~broadcaster
 * @param {GameData} gameData - the data for the emitting game
 * @param {object} data
 * @param {string} data.msg - message from the event function
 */

module.exports = GameEmitter;