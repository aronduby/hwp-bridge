/**
 * Describes the fields for the stats table
 * @param {Pool} pool
 * @returns {Promise} <array<string>, Error> array of field names, rejects with error
 */
function describeStats(pool) {
    return new Promise((resolve, reject) => {
        pool.query("DESCRIBE stats", function(err, results) {
            if(err) {
                throw err;
            }

            /**
             * @var {array<string>} fields
             */
            const fields = results.map(r => r.Field)
                .filter(f =>
                    !f.endsWith('_id') // note one of our join fields
                    && f !== 'id' // not the pk
                    && !f.endsWith('_at') // not a timestamp
                );

            resolve(fields);
        });
    });
}

module.exports = describeStats;