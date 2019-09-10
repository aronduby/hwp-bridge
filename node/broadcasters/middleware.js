var Middleware = function () {};

Middleware.prototype.use = function (fn) {
    this.go = function (stack) {
        return function (input, output, next) {
            stack.call(null, input, output, function () {
                fn.call(null, input, output, function() {
                    next(input, output);
                });
            });
        }
    }(this.go);

    return this;
};

Middleware.prototype.go = function (input, output, next) {
    next(input, output);
};

module.exports = Middleware;

/**
var middleware = new Middleware();

middleware.use(function (input, output, next) {
    input.i++;
    output.body += ' ' + input.i;
    setTimeout(function () {
        next(input, output);
    }, 10);
});

middleware.use(function (input, output, next) {
    input.i++;
    output.body += ' ' + input.i;
    setTimeout(function () {
        next(input, output);
    }, 10);
});

var i = {i: 0};
var o = {body: "Hello World"};
middleware.go(i, o, function (input, output) {
    console.log(input);
    console.log(output);
});

 */