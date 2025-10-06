const Game = require('./game');
const LockedError = require('./errors/lockedError');
const UnopenedError = require('./errors/unopenedError');
const { forceInt } = require("./utils");

/**
 * @typedef ActiveGameData
 * @type {object}
 * @param {string|int} gameId - the id for the game
 * @param {string|int} siteId - the id for the site the game belongs to
 * @param {string} owner - the owning user, (who opened the game)
 * @param {Game} game - technically the game proxy
 */


// Export the factory methods
module.exports = function(dataHandler, emitter, updateManager) {
    // noinspection JSUnusedGlobalSymbols
    return {
        /**
         * @property {Record<string|int, ActiveGameData>}
         */
        activeGames: {},

        open: async function (gameId, ownerId, stealLock) {
            gameId = forceInt(gameId);

            if (!this.activeGames[gameId]) {
                const data = await dataHandler.getGameData(gameId);
                const g = new Game(gameId, emitter, data);

                this.activeGames[gameId] = {
                    gameId: gameId,
                    siteId: data.site_id,
                    owner: ownerId,
                    game: g
                };

                return g;
            } else {
                const activeData = this.activeGames[gameId];
                if (ownerId !== activeData.owner) {
                    if (stealLock) {
                        activeData.owner = ownerId;
                    } else {
                        throw new LockedError('Game opened by other user', activeData.owner);
                    }
                }

                return this.activeGames[gameId].game;
            }
        },

        finalize: async function(gameId, userId) {
            gameId = forceInt(gameId);

            if (!this.activeGames[gameId]) {
                throw new UnopenedError();
            }

            if (this.activeGames[gameId].owner !== userId) {
                throw new LockedError('Trying to finalize a locked game', this.activeGames[gameId].owner);
            }

            const game = this.activeGames[gameId].game;
            const updates = updateManager.get(gameId);
            const saved = await dataHandler.finalizeGameData(game.data, updates);

            delete this.activeGames[gameId];
            updateManager.clear(gameId);
            return saved;
        },

        get: function(gameId, userId) {
            gameId = forceInt(gameId);

            if (!this.activeGames[gameId]) {
                throw new UnopenedError();
            }

            if (this.activeGames[gameId].owner !== userId) {
                throw new LockedError(this.activeGames[gameId].owner);
            }

            return this.activeGames[gameId].game;
        },

        getReadOnly: function(gameId) {
            gameId = forceInt(gameId);

            if (!this.activeGames[gameId]) {
                throw new UnopenedError();
            }

            return Object.freeze({...this.activeGames[gameId].game.data});
        }
    };
};
