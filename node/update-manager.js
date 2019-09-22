class UpdateManager {

    constructor() {
        /**
         * @property {Map<int, BroadcastData[]>} _updateMap
         */
        this._updateMap = new Map();
    }

    /**
     * Adds the update to the proper queue
     * @param {BroadcastData} update
     */
    add(update) {
        const {game_id} = update;
        if (!this._updateMap.has(game_id)) {
            this._updateMap.set(game_id, []);
        }

        const updates = this._updateMap.get(game_id);
        updates.push(update);
        this._updateMap.set(game_id, updates);
    }

    /**
     * Gets the queued updates for the specified game, or empty array
     * @param gameId
     * @returns {BroadcastData[] | []}
     */
    get(gameId) {
        const updates = this._updateMap.get(gameId);
        return updates ? updates : [];
    }

    /**
     * Removes the queue of updates for the given game
     * @param gameId
     * @returns {boolean} - true if it existed and has been removed, false if it didn't exist in the first place
     */
    clear(gameId) {
        return this._updateMap.delete(gameId);
    }

    /**
     * Pass through for the Map objects forEach
     * @param {forEachCallback} fn
     */
    forEach(fn) {
        this._updateMap.forEach(fn);
    }
}

/**
 * @callback UpdateManager~forEachCallback
 * @param {BroadcastData[]} value - the queued updates
 * @param {int} key - the game id of the updates
 * @param {Map<int, BroadcastData[]>} map - the map item
 * @returns void
 */

module.exports = UpdateManager;