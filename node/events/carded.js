function carded(game, who, color) {
    return {
        msg: `A ${color} card for ${who}`
    };
}

module.exports = {
    event: carded,
    symbol: Symbol('carded')
};