const nameAndNumber = require('../utils/player-name-and-number');
const ordinal = require('../utils/ordinal');
const playerPronouns = require('../utils/pronouns').playerPronouns;

module.exports = function kickout(game, player) {
    const p = game.stats[player];

    return {
        msg: `${game.us} kick-out on ${nameAndNumber(p)}, ${playerPronouns(p, 'possessive')} ${ordinal(p.kickouts)}`
    };
};