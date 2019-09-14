const nameAndNumber = require('../utils/player-name-and-number');

function fiveMeterCalled(game, called_on, taken_by, made) {
    const data = {};

    if (made === true || made === 'made') {
        data.msg = `${game.opponent} Goal, #${taken_by}, off a 5 meter`;
    } else if (made === false || made === 'blocked') {
        const goalie = game.stats[game.goalie];
        data.msg = `${nameAndNumber(goalie)} with a HUGE 5 meter block on ${game.opponent}'s #${taken_by}`;
    } else {
        return false;
    }

    return data;
}


module.exports = {
    event: fiveMeterCalled,
    symbol: Symbol('fiveMeterCalled')
};