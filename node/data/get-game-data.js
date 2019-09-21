const describeStats = require('./describe-stats');
const loadPlayers = require('./load-players');

/**
 * Get existing or new data for the given game
 * @param {Pool} pool
 * @param {int} gameId
 * @returns {Promise} <GameData, Error>
 */
function getGameData(pool, gameId) {
    return new Promise((resolve, reject) => {
        // need to (potentially) do multiple queries so get the connection
        pool.getConnection((err, connection) => {
            if (err) {
                connection.release();
                throw err;
            }

            getExistingDump(connection, gameId)
                .then((gameData) => {
                    resolve(gameData);
                    connection.release();
                })
                .catch(() => {
                    createNew(connection, gameId)
                        .then((gameData) => {
                            resolve(gameData);
                            connection.release();
                        })
                        .catch((err) => {
                            reject(err);
                            connection.release();
                        });
                });
        })
    });
}

/**
 * Resovles with existing dump data, or rejects
 * @param connection
 * @param gameId
 * @returns {Promise} <GameData>
 */
function getExistingDump(connection, gameId) {
    return new Promise((resolve, reject) => {

        const sql = "SELECT * FROM game_stat_dumps WHERE game_id = ?";
        const params = [gameId];

        connection.query(sql, params, (err, result) => {
            if(err || result.length === 0 || result[0].json == null){
                if (err) console.error(err);
                reject(err);
                return;
            }

            resolve(JSON.parse(result[0].json));
        });
    });
}

/**
 * Queries all of the data to create new GameData
 * @param connection
 * @param gameId
 * @returns {Promise} <GameData, Error>
 */
function createNew(connection, gameId) {
    return Promise.all([
        gameAndPlayerData(connection, gameId),
        describeStats(connection._pool)
    ])
        .then(([{game, players}, statFields]) => {
            const data = {
                game_id: game.game_id,
                site_id: game.site_id,
                season_id: game.season_id,
                version: '1.1',
                us: 'Hudsonville', // TODO -- not hardcoded
                opponent: game.opponent,
                title: game.title,
                team: game.team,
                status: 'start',
                quarters_played: 0,
                stats: {},
                goalie: '',
                advantage_conversion: [
                    { drawn: 0, converted: 0 },
                    { drawn: 0, converted: 0 }
                ],
                kickouts: [
                    [],
                    []
                ],
                boxscore: [
                    [{}],
                    [{}]
                ],
                score: [0, 0]
            };
            
            const statsObj = statFields.reduce((acc, field) => {
                acc[field] = 0;
                return acc;
            }, {});

            players.forEach((p) => {
                data.stats[p.name_key] = {...p, ...statsObj}
            });

            resolve(data);
        });
}

/**
 * @param connection
 * @param gameId
 * @returns {Promise} <{game, players}, Error>
 */
function gameAndPlayerData(connection, gameId) {
    return new Promise((resolve, reject) => {
        const sql = "SELECT id AS game_id, site_id, season_id, opponent, team, title_append AS title FROM games WHERE id = ?";
        const params = [gameId];

        connection.query(sql, params, (err, results) => {
            if (err) {
                throw err;
            }

            const game = results[0];
            loadPlayers(connection._pool, game.season_id, game.team)
                .then((players) => {
                    resolve({game, players});
                }, (err) => reject(err));
        });
    });
}


module.exports = getGameData;