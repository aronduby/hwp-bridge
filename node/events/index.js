module.exports = require('require-dir')('.', {
    // skip test files
    filter: function (fullPath) {
        return !fullPath.match(/\.test\.js$/);
    },

    // keys are camelCased
    mapKey: function(val, baseName) {
        return baseName.replace(/-([a-z])/g, function (g) { return g[1].toUpperCase(); })
    }
});