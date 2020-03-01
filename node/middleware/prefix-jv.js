module.exports = function(input, output, next) {
    if (input.team === 'JV') {
        output.body = '(JV) ' + output.body;
    }
    next();
};