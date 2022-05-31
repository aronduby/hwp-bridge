module.exports = function forceInt(intIsh) {
    if (!Number.isInteger(intIsh)) {
        return parseInt(intIsh, 10);
    } else {
        return intIsh;
    }
}