const nameAndNumber = require('../utils/player-name-and-number');

module.exports = function fiveMeterDrawn(game, drawn_by_key, taken_by_key, made) {
    if (made === true || made === 'made') {
        const data = {},
            drawnBy = game.stats[drawn_by_key],
            takenBy = game.stats[taken_by_key];

        data.msg = `${game.us} Goal! ${nameAndNumber(takenBy)} on a 5 meter shot`;
        if (drawn_by_key === taken_by_key) {
            data.msg += ' they drew';
        } else {
            data.msg += ` drawn by ${nameAndNumber(drawnBy)}`;
        }

        return data;
    }

    // todo - maybe add a miss? that would suck for the player but is important overall...
    return false;
};