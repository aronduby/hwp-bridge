module.exports = function(input, output, next) {
    output.body = input.msg + ' -- ' + input.score[0] + ' - ' + input.score[1];

    next();
};