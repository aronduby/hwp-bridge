const EventEmitter = require('events');
const eventListeners = require('./events');

class GameEmitter extends EventEmitter {

    constructor() {
        super();

        this.bindEventListeners(eventListeners);
    }

    /**
     * Binds all of the events loaded from the event listeners
     *
     * @param {object<string, function>} listeners - object where keys are string events names with listeners for value
     */
    bindEventListeners(listeners) {
        Object.keys(listeners).forEach(key => {
            this.on(key, listeners[key]);
        });
    }


    /**
     * Gets passed to the game as the emitter
     *
     * @param {GameData} gameData - data about the game
     * @param {string} key - the event that was triggers
     * @param {array} args - array of arguments passed through
     */
    trigger(gameData, key, args) {
        const send = [gameData, ...args];
        this.emit(key, ...send);
    }
}

module.exports = GameEmitter;