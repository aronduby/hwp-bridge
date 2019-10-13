class LockedError extends Error {
    constructor(msg, owner) {
        super(msg);

        this.type = 'LockedError';
        this.owner = owner;
    }
}

module.exports = LockedError;