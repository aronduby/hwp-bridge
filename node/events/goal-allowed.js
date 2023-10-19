module.exports = function goalAllowed(game, number){
    const data = {
        msg: `${game.opponent}`
    };

    if (game.kickouts[0].length !== game.kickouts[1].length) {
        data.msg += ` ${(6 - game.kickouts[1].length)} on ${(6 - game.kickouts[0].length)}`
    }

    data.msg += ` Goal${number ? ` by #${number}` : ''}`;

    return data;
};