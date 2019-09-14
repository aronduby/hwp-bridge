const timeout = require('./timeout').event;

const game = {
    us: 'Hudsonville',
    opponent: 'Rockford',
    quarters_played: 0,
};

beforeEach(() => {
    game.quarters_played = 0;
});

test('seconds only', function() {
    let {msg} = timeout(game, 'Hudsonville', {seconds: 3});
    expect(msg).toBe('Hudsonville Time Out, 3 seconds left in the 1st quarter');
});

test('second singular', function() {
    let {msg} = timeout(game, 'Hudsonville', {seconds: 1});
    expect(msg).toBe('Hudsonville Time Out, 1 second left in the 1st quarter');
});

test('minutes only', function() {
    let {msg} = timeout(game, 'Hudsonville', {minutes: 3});
    expect(msg).toBe('Hudsonville Time Out, 3 minutes left in the 1st quarter');
});

test('minute singular', function() {
    let {msg} = timeout(game, 'Hudsonville', {minutes: 1});
    expect(msg).toBe('Hudsonville Time Out, 1 minute left in the 1st quarter');
});

test('minutes and seconds', function() {
    let {msg} = timeout(game, 'Hudsonville', {minutes: 3, seconds: 13});
    expect(msg).toBe('Hudsonville Time Out, 3:13 left in the 1st quarter');
});

test('first quarter', function () {
    let {msg} = timeout(game, 'Hudsonville', {minutes: 1});
    expect(msg).toBe('Hudsonville Time Out, 1 minute left in the 1st quarter');
});

test('second quarter', function () {
    game.quarters_played = 1;
    let {msg} = timeout(game, 'Hudsonville', {minutes: 1});
    expect(msg).toBe('Hudsonville Time Out, 1 minute left in the 2nd quarter');
});

test('third quarter', function () {
    game.quarters_played = 2;
    let {msg} = timeout(game, 'Hudsonville', {minutes: 1});
    expect(msg).toBe('Hudsonville Time Out, 1 minute left in the 3rd quarter');
});

test('fourth quarter', function () {
    game.quarters_played = 3;
    let {msg} = timeout(game, 'Hudsonville', {minutes: 1});
    expect(msg).toBe('Hudsonville Time Out, 1 minute left in regulation play');
});

test('overtimes', function() {
    const qs = [
        [4, '1st'],
        [5, '2nd'],
        [8, '5th']
    ];

    qs.forEach(([q, title]) => {
        game.quarters_played = q;
        let {msg} = timeout(game, 'Hudsonville', {minutes: 1});
        expect(msg).toBe(`Hudsonville Time Out, 1 minute left in the ${title} OT`);
    });
});