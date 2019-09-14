const fiveMeterDrawn = require('./five-meter-drawn').event;

const game = {
    us: "Hudsonville",
    opponent: "Rockford",
    stats: {
        'JohnDirkse': {
            number: '13',
            first_name: 'John',
            last_name: 'Dirkse'
        },
        'ChandlerJones': {
            number: '2',
            first_name: 'Chandler',
            last_name: 'Jones'
        }
    }
};

test('missed returns false', () => {
    expect(fiveMeterDrawn(game, 'JohnDirkse', 'ChandlerJones', false));
    expect(fiveMeterDrawn(game, 'JohnDirkse', 'ChandlerJones', 'missed'));
    expect(fiveMeterDrawn(game, 'JohnDirkse', 'ChandlerJones', 'blocked'));
});

test('drawn by self formats right', () => {
    const expected = `Hudsonville Goal! #13 John Dirkse on a 5 meter shot they drew`;
    let data;

    data = fiveMeterDrawn(game, 'JohnDirkse', 'JohnDirkse', true);
    expect(data.msg).toBe(expected);

    data = fiveMeterDrawn(game, 'JohnDirkse', 'JohnDirkse', 'made');
    expect(data.msg).toBe(expected);
});

test('drawn by other formats right', () => {
    const expected = `Hudsonville Goal! #2 Chandler Jones on a 5 meter shot drawn by #13 John Dirkse`;
    let data;

    data = fiveMeterDrawn(game, 'JohnDirkse', 'ChandlerJones', true);
    expect(data.msg).toBe(expected);

    data = fiveMeterDrawn(game, 'JohnDirkse', 'ChandlerJones', 'made');
    expect(data.msg).toBe(expected);
});