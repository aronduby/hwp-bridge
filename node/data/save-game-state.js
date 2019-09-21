/**
 * Inserts (or updates) the JSON serialized data to the db
 * @param {Pool} pool
 * @param {GameData} gameData
 */
function saveGameState(pool, gameData) {
    return new Promise((resolve, reject) => {

        const sql = "INSERT INTO game_stat_dumps SET site_id = ?, game_id = ?, json = ? ON DUPLICATE KEY UPDATE json = VALUES(json)";
        const params = [gameData.site_id, gameData.game_id, JSON.stringify(gameData)];

        pool.query(sql, params, (err, result, fields) => {
           if (err) {
               reject(err);
               return;
           }

           resolve(true);
        });
    });
}

module.exports = saveGameState;