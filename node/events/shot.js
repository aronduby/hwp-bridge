const ordinal = require('../utils/ordinal');
const oxford = require('../utils/oxford');
const nameAndNumber = require('../utils/player-name-and-number');

function shot(game, player, made, assist) {
    if (made === true) {
        const data = {};

        const shooter = game.stats[player];
        const assisted = assist ? game.stats[assist] : false;

        data.msg = `${game.us} Goal`;

        // advantage?
        if (game.kickouts[0].length !== game.kickouts[1].length) {
            data.msg += ` off a ${(6 - game.kickouts[0].length)} on ${(6 - game.kickouts[1].length)}! ${nameAndNumber(shooter)} scoring his ${ordinal(shooter.goals)}`;
        } else {
            data.msg += `! ${nameAndNumber(shooter)}, his ${ordinal(shooter.goals)}`;
        }

        // assist?
        if (assisted !== false) {
            data.msg += `, with the assist by ${nameAndNumber(assisted)}`;
        }

        if (game.kickouts_drawn_by.length) {
            const drawnStrings = [];
            new Set(game.kickouts_drawn_by)
                .forEach((nameKey) => {
                    drawnStrings.push(nameAndNumber(game.stats[nameKey]));
                });

            data.msg += `. Advantage${game.kickouts_drawn_by.length > 1 ? 's' : ''} drawn by ${oxford(drawnStrings, 'and')}`;
        }

        return data;
    }

    return false;
}


module.exports = {
    event: shot,
    symbol: Symbol('shot')
};