/**
 * make a list in the Oxford comma style (eg "a, b, c, and d")
 * Examples with conjunction "and":
 * ["a"] -> "a"
 * ["a", "b"] -> "a and b"
 * ["a", "b", "c"] -> "a, b, and c"
 */
module.exports = function oxford(arr, conjunction, ifEmpty) {
    const l = arr.length;
    if (!l) return ifEmpty;
    if (l < 2) return arr[0];
    if (l < 3) return arr.join(` ${conjunction} `);
    arr = arr.slice();
    arr[l - 1] = `${conjunction} ${arr[l - 1]}`;
    return arr.join(", ");
};