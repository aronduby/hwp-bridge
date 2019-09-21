/**
 * Describes the fields for the stats table
 * @param {Pool} pool
 * @returns {Promise<array<string>, Error>} resolves with an array of field names, rejects with error
 */
function describeStats(pool) {
    return new Promise((resolve, reject) => {
        pool.query("DESCRIBE stats", function(err, results) {
            if(err) {
                reject(err);
                return
            }

            resolve(results.map(r => r.Field));
        });
    });
}

module.exports = describeStats;