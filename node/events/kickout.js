const nameAndNumber = require('../utils/player-name-and-number');
const ordinal = require('../utils/ordinal');

module.exports = function kickout(game, player) {
    const p = game.stats[player];

    return {
        msg: `${game.us} kick-out on ${nameAndNumber(p)}, his ${ordinal(p.kickouts)}`
    };
};