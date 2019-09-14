function shout(game, msg) {
    return {
        msg: msg
    };
}


module.exports = {
    event: shout,
    symbol: Symbol('shout')
};