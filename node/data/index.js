const describeStats = require('./describe-stats');
const finalizeGameData = require('./finalize-game-data');
const getGameData = require('./get-game-data');
const loadPlayers = require('./load-players');
const saveGameState = require('./save-game-state');


/**
 * @param {Pool} pool - a mysql connection pool
 * @returns {{string: function<Promise<any, Error>>}}
 */
function dataHandler(pool) {
    return {
        describeStats: () => describeStats(pool),
        finalizeGameData: (gameData, updates) => finalizeGameData(pool, gameData, updates),
        getGameData: (gameId) => getGameData(pool, gameId),
        loadPlayers: (seasonId, team) => loadPlayers(pool, seasonId, team),
        saveGameState: (gameData) => saveGameState(pool, gameData)
    }
}

module.exports = dataHandler;