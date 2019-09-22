class LockedError extends Error {
    constructor(msg, owner) {
        super(msg);

        this.owner = owner;
    }
}

module.exports = LockedError;