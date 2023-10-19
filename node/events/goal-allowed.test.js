const goalAllowed = require('./goal-allowed');

let game = {};

beforeEach(() => {
    game = Object.freeze({
        us: "Hudsonville",
        opponent: "Rockford",
        kickouts: [
            [],
            []
        ]
    });
});

test('no number formats correctly', () => {
    let {msg} = goalAllowed(game);

    expect(msg).toBe('Rockford Goal');
});

test('with number formats correctly', () => {
    let {msg} = goalAllowed(game, 5);

    expect(msg).toBe('Rockford Goal by #5');
});

test('advantage with a number', () => {
    game.kickouts[0] = ['AndyLobbezoo'];
    const { msg } = goalAllowed(game, 5);

    expect(msg).toBe('Rockford 6 on 5 Goal by #5');
});

test('multiple advantages with a number', () => {
    game.kickouts[0] = ['AndyLobbezoo', 'IanWorst', 'MicahBayle'];
    const { msg } = goalAllowed(game, 5);

    expect(msg).toBe('Rockford 6 on 3 Goal by #5');
});

test('advantage no number', () => {
    game.kickouts[0] = ['AndyLobbezoo'];
    const { msg } = goalAllowed(game);

    expect(msg).toBe('Rockford 6 on 5 Goal');
});