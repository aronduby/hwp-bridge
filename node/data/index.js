const describeStats = require('./describe-stats');
const finalizeGameData = require('./finalize-game-data');
const getGameData = require('./get-game-data');
const playerDataMethods = require('./player-data');
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
        loadPlayers: (seasonId, team) => playerDataMethods.loadPlayers(pool, seasonId, team),

        /**
         *
         * @param playerSeasonId
         * @param data
         * @return {Promise<boolean, Error>}
         */
        updatePlayerSeason: (playerSeasonId, data) => playerDataMethods.updatePlayerSeason(pool, playerSeasonId, data),

        /**
         *
         * @param {GameData} gameData
         * @returns {Promise<boolean, Error>}
         */
        saveGameState: (gameData) => saveGameState(pool, gameData)
    }
}

module.exports = dataHandler;