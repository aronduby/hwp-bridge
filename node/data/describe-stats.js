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
            const fields = results.map(r => r.Field);

            resolve(fields);
        });
    });
}

module.exports = describeStats;