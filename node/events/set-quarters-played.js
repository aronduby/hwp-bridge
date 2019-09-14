const ordinal = require('../utils/ordinal');

function setQuartersPlayed(game, quarters) {
    const data = {};
    let period, result,
        post = '.';

    // noinspection DuplicateCaseLabelJS,FallThroughInSwitchStatementJS
    switch (quarters) {
        // ending a game goes through a separate route, so if we're getting 4 here it means overtime
        case 4:
            post = `. We're going into overtime!`;

        case 1:
        case 2:
        case 3:
        case 4:
            period = ordinal(quarters);
            break;

        // overtime
        default:
            period = `${ordinal(quarters - 4)} OT`;
            break;
    }

    if (game.score[0] > game.score[1]) {
        result = `LEADS`;
    } else if (game.score[0] === game.score[1]) {
        result = `TIED WITH`;
    } else {
        result = `TRAILS`;
    }

    data.msg = `At the end of the ${period} ${game.us} ${result} ${game.opponent}${post}`;


    return data;
}


module.exports = {
    event: setQuartersPlayed,
    symbol: Symbol('setQuartersPlayed')
};