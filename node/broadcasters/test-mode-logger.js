var fs = require('fs');

module.exports = function(prefix, msg) {
    fs.appendFile(
        'broadcast-log.txt',
        prefix + ':('+msg.length+') ' + msg + "\n",
        function (err) {
            if (err) throw err;
        }
    );
};