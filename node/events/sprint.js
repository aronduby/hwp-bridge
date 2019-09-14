const nameAndNumber = require('../utils/player-name-and-number');
const ordinal = require('../utils/ordinal');

function sprint(game, player, won) {
    const data = {};

    data.msg = 'Start of ';
    switch (game.quarters_played) {
        case 0:
            data.msg += `${game.us} vs ${game.opponent}`;
            break;
        case 1:
            data.msg += 'the 2nd';
            break;
        case 2:
            data.msg += 'the 3rd';
            break;
        case 3:
            data.msg += 'the 4th';
            break;

        // overtime
        default:
            data.msg += `the ${ordinal(game.quarters_played - 3)} OT`;
            break;
    }
    data.msg += ' -- Sprint Won By ';

    if (won === false) {
        data.msg += game.opponent;
    } else {
        data.msg += `${game.us}'s ${nameAndNumber(game.stats[player])}`;
    }

    return data;
}


module.exports = {
    event: sprint,
    symbol: Symbol('sprint')
};