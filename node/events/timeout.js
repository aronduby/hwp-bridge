const ordinal = require('../utils/ordinal');

module.exports = function timeout(game, team, time){
    let timeLeft, period;

    if(time.minutes === undefined && time.seconds !== undefined){
        timeLeft = `${time.seconds} second${(time.seconds > 1 ? `s` : ``)}`;
    } else if (time.minutes !== undefined){
        timeLeft = time.minutes;

        if(time.seconds !== undefined){
            timeLeft = `${timeLeft}:${time.seconds}`;
        } else {
            timeLeft = `${timeLeft} minute${(time.minutes > 1 ? `s` : ``)}`;
        }
    }

    if(time.minutes !== undefined || time.seconds !== undefined){
        switch(game.quarters_played){
            case 0:
                period = 'the 1st quarter';
                break;
            case 1:
                period = 'the 2nd quarter';
                break;
            case 2:
                period = 'the 3rd quarter';
                break;
            case 3:
                period = 'regulation play';
                break;

            // overtime
            default:
                period = `the ${ordinal(game.quarters_played - 3)} OT`;
                break;
        }
    }

    return {
        msg: `${team} Time Out, ${timeLeft} left in ${period}`
    };
};