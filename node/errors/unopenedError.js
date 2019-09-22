class UnopenedError extends Error {
    constructor(msg) {
        super(msg);
    }
}

module.exports = UnopenedError;