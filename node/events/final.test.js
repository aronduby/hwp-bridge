const final = require('./final');

const game = Object.freeze({
    us: "Hudsonville",
    opponent: "Rockford"
});

test('formats wins', function() {
    const data = final({
        ...game,
        score: [21, 3]
    });

    expect(data.msg).toBe("Final Result - Hudsonville DEFEATS Rockford");
});

test('formats loses', function() {
    const data = final({
        ...game,
        score: [3, 21]
    });

    expect(data.msg).toBe("Final Result - Hudsonville LOSES TO Rockford");
});

test('formats ties', function() {
    const data = final({
        ...game,
        score: [3, 3]
    });

    expect(data.msg).toBe("Final Result - Hudsonville TIES Rockford");
});