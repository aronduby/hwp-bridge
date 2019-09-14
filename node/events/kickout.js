const nameAndNumber = require('../utils/player-name-and-number');
const ordinal = require('../utils/ordinal');

function kickout(game, player) {
    const p = game.stats[player];

    return {
        msg: `${game.us} kick-out on ${nameAndNumber(p)}, his ${ordinal(p.kickouts)}`
    };
}


module.exports = {
    event: kickout,
    symbol: Symbol('kickout')
};