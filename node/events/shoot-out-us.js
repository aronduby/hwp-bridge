const nameAndNumber = require('../utils/player-name-and-number');

module.exports = function shootOutUs(game, player, made) {
    const data = {};
    const p = game.stats[player];

    switch (made) {
        case true:
        case 'made':
            data.msg = `${game.us} Goal! ${nameAndNumber(p)}`;
            break;

        case false:
        case 'blocked':
            data.msg = `${nameAndNumber(p)} shot is blocked`;
            break;

        case 'missed':
        default:
            data.msg = `${nameAndNumber(p)} shot is no good`;
            break;
    }

    return data;
};