module.exports = function final(game) {
    let result;

    if (game.score[0] > game.score[1]) {
        result = 'DEFEATS';
    } else if (game.score[0] === game.score[1]) {
        result = 'TIES';
    } else {
        result = 'LOSES TO';
    }

    return {
        msg: `Final Result - ${game.us} ${result} ${game.opponent}`
    };
};