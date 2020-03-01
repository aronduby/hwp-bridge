const spawn = require('child_process').spawn;
const artisanPath = require('../settings').artisanPath;
const saveGameState = require('./save-game-state');

/**
 * Write the finalized data to the db
 * @param {Pool} pool
 * @param {GameData} gameData
 * @param {array<object>} updates
 * @returns {Promise} - boolean, Error
 */
function finalizeGameData(pool, gameData, updates) {
    return new Promise((resolve, reject) => {
        pool.getConnection((err, connection) => {
           if (err) {
               connection.release();
               throw err;
           }

           Promise.all([
               saveUpdates(connection, gameData, updates),
               saveGameData(connection, gameData),
               saveStatsDump(connection, gameData),
               insertRecent(connection, gameData)
           ])
               .then(
                   () => {
                       resolve(true);
                       connection.release();
                   },
                   (err) => {
                       reject(err);
                       connection.release();
                   }
               );
        });
    });
}

/**
 * Writes the updates to the DB
 * @param {PoolConnection} connection
 * @param {GameData} gameData
 * @param {array<object>} updates
 * @returns {Promise} <boolean, Error>
 */
function saveUpdates(connection, gameData, updates) {
    return new Promise((resolve, reject) => {
        const sql = "INSERT INTO game_update_dumps SET site_id = ?, game_id = ?, json = ? ON DUPLICATE KEY UPDATE json = VALUES(json)";
        const params = [gameData.site_id, gameData.game_id, JSON.stringify(updates)];

        connection.query(sql, params, function(err, result){
            if(err){
                console.log(err);
                throw err;
            }

            console.log('Saved updates to database', result);
            resolve(true);
        });
    });
}

/**
 * Updates the game fields
 * @param {PoolConnection} connection
 * @param {GameData} gameData
 * @returns {Promise} <boolean, Error>
 */
function saveGameData(connection, gameData) {
    return new Promise((resolve, reject) => {
        const sql = "UPDATE games SET score_us = ?, score_them = ? WHERE id = ?";
        const params = [gameData.score[0], gameData.score[1], gameData.game_id];

        connection.query(sql, params, (err, result) => {
            if(err){
                console.log(err);
                throw err;
            }

            console.log('Saved Score in database', result);
            resolve(true);
        });
    });
}

/**
 * Saves the data dump and spawns the artisan command to parse it into stats
 * @param {PoolConnection} connection
 * @param {GameData} gameData
 * @returns {Promise} <boolean, Error>
 */
function saveStatsDump(connection, gameData) {
    return saveGameState(connection._pool, gameData)
        .then(
            () => {
                // spawn the artisan stats saving command
                const log = (data) => console.log('' + data);
                const child = spawn('php', [artisanPath, 'scoring:save-stats', gameData.game_id]);

                child.stdout.on('data', log);
                child.stderr.on('data', log);

                return true;
            },
            (err) => {
                throw err;
            }
        );
}

/**
 * Inserts an entry into the recent feed
 * @param {PoolConnection} connection
 * @param {GameData} gameData
 * @returns {Promise} <boolean, Error>
 */
function insertRecent(connection, gameData) {
    return new Promise((resolve, reject) => {
        const sql = "INSERT INTO recent SET site_id = ?, season_id = ?, renderer = 'game', content = ?, created_at = NOW(), updated_at = NOW()";
        const params = [gameData.site_id, gameData.season_id, `[${gameData.game_id}]`];

        connection.query(sql, params, (err, result) => {
            if(err){
                throw err;
            }

            console.log('Inserted Recent in database', result);
            resolve(true);
        });
    });
}

module.exports = finalizeGameData;