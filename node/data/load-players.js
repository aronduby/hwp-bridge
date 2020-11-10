/**
 * @typedef {object} PlayerData
 * @property {string} name_key
 * @property {string} first_name
 * @property {string} last_name
 * @property {string} number
 * @property {int} number_sort
 * @property {'V', 'JV'} team
 */

/**
 * Load a list of players for a season and optionally team
 *
 * @param {Pool} pool - mysql pool connection
 * @param {int} seasonId - the season id to lookup
 * @param {'V','JV'} team - optional, the team to use
 * @returns {Promise} <array<PlayerData>, Error>
 */
function loadPlayers(pool, seasonId, team) {
    return new Promise((resolve, reject) => {
        let sql, params;

        if (team) {
            sql = "SELECT p.name_key, p.first_name, p.last_name, p.pronouns, pts.number, pts.team FROM player_season pts JOIN players p ON(pts.player_id = p.id) WHERE pts.season_id = ? AND FIND_IN_SET (?, team)";
            params = [seasonId, team];
        } else {
            sql = "SELECT p.name_key, p.first_name, p.last_name, p.pronouns, pts.number, pts.team FROM player_season pts JOIN players p ON(pts.player_id = p.id) WHERE pts.season_id = ?";
            params = [seasonId];
        }

        pool.query(sql, params, (err, result) => {
            if (err) {
                throw err;
            }

            const playerList = result.map((r) => {
                return {
                    ...r,
                    team: r.team.split(','),
                    number_sort: parseInt(r.number, 10)
                };
            });

            resolve(playerList);
        });
    });
}

module.exports = loadPlayers;