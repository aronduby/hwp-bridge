const Middleware = require('./middleware');
const testLogger = require('./test-mode-logger');

/**
 * @class SocketBroadcaster
 * @property {boolean} _testMode - are we in test mode and should log instead of emit
 * @property {?{of: function, emit: function}} _socketServer - socketIO server instance
 * @property {Map<int, string>} _namespaceMap - map of site_id > namespace string
 */
class SocketBroadcaster extends Middleware {

    /**
     *
     * @param {boolean} testMode - are we in test mode
     */
    constructor(testMode) {
        super();

        this._testMode = testMode;
        this._socketServer = null;
        this._namespaceMap = new Map();
    }

    /**
     * Sets the socket server to broadcast over
     * @param {{of: function, emit: function}} server - socket.io server instance
     */
    setSocketServer(server) {
        this._socketServer = server;
    }

    /**
     * Adds the site > namespace mapping
     * @param {int} siteId - the id of the site
     * @param {string} nsp - the string namespace on the server
     */
    addNamespace(siteId, nsp) {
        if (!this._namespaceMap.has(siteId)) {
            this._namespaceMap.set(siteId, nsp);
        }
    }

    /**
     * Broadcast the given data, routing through our middleware
     * @param {object} data - data to use for broadcasting
     * @param {int} data.site_id - the siteId to use for the nsp lookup
     * @param {?object} initialOut - used to supply an initial output with empty body
     */
    broadcast(data, initialOut = {body: ""}) {
        this.go(data, initialOut, function(input, output) {
            const nsp = this._namespaceMap.get(data.site_id);
            if (typeof nsp === 'undefined') {
                console.error(`Mapped namespace doesn't exist of site: ${data.site_id}`);
                return;
            }

            if (this._testMode !== true) {
                if (typeof this._socketServer === 'undefined') {
                    console.error(`Socket server has yet to be set`);
                    return;
                }

                this._socketServer.of(nsp).emit(output.body);
            } else {
                testLogger(`SOCKETS: ${nsp}`, output.body);
            }
        }.bind(this));
    }

}

module.exports = SocketBroadcaster;