const goalAllowed = require('./goal-allowed');

const game = Object.freeze({
    us: "Hudsonville",
    opponent: "Rockford"
});

test('no number formats correctly', () => {
    let {msg} = goalAllowed(game);

    expect(msg).toBe('Rockford Goal');
});

test('with number formats correctly', () => {
    let {msg} = goalAllowed(game, 5);

    expect(msg).toBe('Rockford Goal by #5');
});