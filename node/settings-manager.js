/**
 * @typedef {object} CacheData
 * @property {int} cached - timestamp of when it was last checked for file updates
 * @property {string} domain - the domain/file name for the site's setting file
 * @property {object} settings - the settings object
 */

const path = require('path');
const fs = require('fs');

// time before checking for file updates, in milliseconds
const CACHE_TIME = 5 * 60 * 1000; // 300,000;

class SettingsManager {

    /**
     *
     * @param db - mysql db pool
     * @param {object} settings
     */
    constructor(db, settings) {
        this.db = db;
        this.globalSettings = settings;

        this._siteSettingsPath = settings.siteSettingsPath;

        /**
         * @type {Map<int, CacheData>}
         * @private
         */
        this._siteSettingsMap = new Map();
    }


    /**
     * Gets just the global settings, nothing site specific
     * @returns {object}
     */
    getGlobal() {
        return this.globalSettings;
    }

    /**
     * Gets settings specific to the given site, overlays the
     *
     * @param {int} siteId
     * @returns {object}
     */
    async getForSite(siteId) {
        let cachedData;
        const now = +(new Date());

        if (this._siteSettingsMap.has(siteId)) {
            cachedData = this._siteSettingsMap.get(siteId);

            // should we check the file ts?
            if (cachedData.cached + CACHE_TIME < now) {
                let fileTs = this._getFileTimeStamp(cachedData.domain);
                // if the file has been updated reload it
                if (fileTs > cachedData.cached) {
                    cachedData.settings = await this._loadFile(cachedData.domain);
                }

                cachedData.cached = now;
                this._siteSettingsMap.set(siteId, cachedData);
            }
        } else {
            // we don't know about the site, so check the db
            const domain = await this._getDomainFromDb(siteId);
            const newData = await this._loadFile(domain);
            cachedData = {};
            cachedData.cached = now;
            cachedData.settings = newData;
            cachedData.domain = domain;
            this._siteSettingsMap.set(siteId, cachedData);
        }

        return {... this.globalSettings, ...cachedData.settings};
    }

    /**
     * Gets the domain name for the given id
     *
     * @param siteId
     * @returns {Promise<string>}
     * @private
     */
    async _getDomainFromDb(siteId) {
        return new Promise((resolve, reject) => {

            const sql = "SELECT domain FROM sites WHERE id = ?";
            const params = [siteId];

            this.db.query(sql, params, (err, result) => {
                if(err || result.length === 0 || result[0].domain == null){
                    if (err) console.error(err);
                    reject(err);
                    return;
                }

                resolve(result[0].domain);
            });
        });
    }

    /**
     * Get's the file path for the given domain
     *
     * @param domain
     * @returns {string}
     * @private
     */
    _getFilePath(domain) {
        return this._siteSettingsPath + path.sep + domain + '.json';
    }

    /**
     *
     * @param domain
     * @returns {number} timestamp of when the file was last updated
     * @private
     */
    _getFileTimeStamp(domain) {
        const filePath = this._getFilePath(domain);
        const stats = fs.statSync(filePath);
        return stats.mtimeMs;
    }

    /**
     * Loads the settings for the given domain
     * @param {string} domain
     * @returns {Promise<object>}
     * @private
     */
    async _loadFile(domain) {
        return new Promise((resolve, reject) => {
            const filePath = this._getFilePath(domain);
            fs.readFile(filePath, 'utf8', (err, data) => {
                if (err) reject(err);
                resolve(JSON.parse(data));
            });
        });
    }
}

module.exports = SettingsManager;