const nameAndNumber = require('../utils/player-name-and-number');

module.exports = function shootOutThem(game, number, made) {
    const data = {};

    switch (made) {
        case true:
        case 'made':
            data.msg = `${game.opponent} Goal, #${number}`;
            break;

        case false:
        case 'blocked':
            const goalie = game.stats[game.goalie];
            data.msg = `${game.opponent} shot by #${number} BLOCKED by ${nameAndNumber(goalie)}`;
            break;

        case 'missed':
            data.msg = `${game.opponent} shot by #${number} missed`;
            break;
    }

    return data;
};