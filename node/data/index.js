const describeStats = require('./describe-stats');
const finalizeGameData = require('./finalize-game-data');
const getGameData = require('./get-game-data');
const loadPlayers = require('./load-players');
const saveGameState = require('./save-game-state');


/**
 * @param {Pool} pool - a mysql connection pool
 */
function dataHandler(pool) {
    return {
        /**
         *
         * @returns {Promise<string[], Error>}
         */
        describeStats: () => describeStats(pool),

        /**
         *
         * @param {GameData} gameData
         * @param {array<object>} updates
         * @returns {Promise<boolean, Error>}
         */
        finalizeGameData: (gameData, updates) => finalizeGameData(pool, gameData, updates),

        /**
         *
         * @param gameId
         * @returns {Promise<GameData, Error>}
         */
        getGameData: (gameId) => getGameData(pool, gameId),

        /**
         *
         * @param seasonId
         * @param {'V','JV'}team
         * @returns {Promise<PlayerData[], Error>}
         */
        loadPlayers: (seasonId, team) => loadPlayers(pool, seasonId, team),

        /**
         *
         * @param {GameData} gameData
         * @returns {Promise<boolean, Error>}
         */
        saveGameState: (gameData) => saveGameState(pool, gameData)
    }
}

module.exports = dataHandler;