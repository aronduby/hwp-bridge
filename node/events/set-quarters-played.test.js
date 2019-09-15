const setQuartersPlayed = require('./set-quarters-played');

const game = {
    us: 'Hudsonville',
    opponent: 'Rockford',
    score: [22, 3]
};

beforeEach(() => {
    game.score = [22, 3];
});

test('handles score properly', function() {

    let data = setQuartersPlayed(game, 1);
    expect(data.msg).toBe('At the end of the 1st Hudsonville LEADS Rockford.');

    game.score[0] = 3;
    game.score[1] = 3;
    data = setQuartersPlayed(game, 1);
    expect(data.msg).toBe('At the end of the 1st Hudsonville TIED WITH Rockford.');

    game.score[0] = 3;
    game.score[1] = 4;
    data = setQuartersPlayed(game, 1);
    expect(data.msg).toBe('At the end of the 1st Hudsonville TRAILS Rockford.');
});

test('going into overtime', () => {
    game.score = [4,4];
    let data = setQuartersPlayed(game, 4);
    expect(data.msg).toBe(`At the end of the 4th Hudsonville TIED WITH Rockford. We're going into overtime!`);
});

test('additional overtimes', () => {
    let data = setQuartersPlayed(game, 5);
    expect(data.msg).toBe(`At the end of the 1st OT Hudsonville LEADS Rockford.`);

    data = setQuartersPlayed(game, 6);
    expect(data.msg).toBe(`At the end of the 2nd OT Hudsonville LEADS Rockford.`);

    data = setQuartersPlayed(game, 7);
    expect(data.msg).toBe(`At the end of the 3rd OT Hudsonville LEADS Rockford.`);
});