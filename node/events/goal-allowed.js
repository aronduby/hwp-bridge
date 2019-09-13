module.exports = function goalAllowed(game, number){
    return {
        msg: `${game.opponent} Goal${number ? ` by #${number}` : ''}`
    };
};