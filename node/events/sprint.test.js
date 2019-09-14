const sprint = require('./sprint').event;

const game = {
    us: 'Hudsonville',
    opponent: 'Rockford',
    quarters_played: 0,
    stats: {
        'WesObetts': {
            number: '9',
            first_name: 'Wes',
            last_name: 'Obetts',
        }
    }
};

beforeEach(() => {
    game.quarters_played = 0;
});

test('win', function () {
    let {msg} = sprint(game, 'WesObetts', true);
    expect(msg).toBe(`Start of Hudsonville vs Rockford -- Sprint Won By Hudsonville's #9 Wes Obetts`);
});

test('loss', function() {
    let {msg} = sprint(game, 'WesObetts', false);
    expect(msg).toBe(`Start of Hudsonville vs Rockford -- Sprint Won By Rockford`);
});

test('start of game', function() {
    game.quarters_played = 0;
    let {msg} = sprint(game, 'WesObetts', true);
    expect(msg).toBe(`Start of Hudsonville vs Rockford -- Sprint Won By Hudsonville's #9 Wes Obetts`);
});

test('second quarter', function() {
    game.quarters_played = 1;
    let {msg} = sprint(game, 'WesObetts', true);
    expect(msg).toBe(`Start of the 2nd -- Sprint Won By Hudsonville's #9 Wes Obetts`);
});

test('third quarter', function() {
    game.quarters_played = 2;
    let {msg} = sprint(game, 'WesObetts', true);
    expect(msg).toBe(`Start of the 3rd -- Sprint Won By Hudsonville's #9 Wes Obetts`);
});

test('fourth quarter', function() {
    game.quarters_played = 3;
    let {msg} = sprint(game, 'WesObetts', true);
    expect(msg).toBe(`Start of the 4th -- Sprint Won By Hudsonville's #9 Wes Obetts`);
});

test('overtimes', function() {
    const qs = [
        [4, '1st'],
        [5, '2nd'],
        [8, '5th']
    ];

    qs.forEach(([q, title]) => {
        game.quarters_played = q;
        let {msg} = sprint(game, 'WesObetts', true);
        expect(msg).toBe(`Start of the ${title} OT -- Sprint Won By Hudsonville's #9 Wes Obetts`);
    });
});
