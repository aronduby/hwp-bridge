function goalAllowed(game, number){
    return {
        msg: `${game.opponent} Goal${number ? ` by #${number}` : ''}`
    };
}


module.exports = {
    event: goalAllowed,
    symbol: Symbol('goalAllowed')
};