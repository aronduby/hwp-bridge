const carded = require('./carded');

test('it formats correctly', () => {
    const data = carded({}, "Josh", "yellow");
    expect(data.msg).toBe("A yellow card for Josh");
});