const mysql = require('mysql');

/**
 * const db = require('./db')(settings.mysql);
 * db.query('select * from thing', () => {});
 *
 * @param {object} config - mysql pool options: https://github.com/mysqljs/mysql#pool-options
 * @returns {Pool} - mysql connection pool: https://github.com/mysqljs/mysql#pooling-connections
 */
module.exports = function(config) {
    return mysql.createPool(config);
};