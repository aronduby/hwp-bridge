const shot = require('./shot');

let game = {};

beforeEach(() => {
    game = {
        us: 'Hudsonville',
        opponent: 'Rockford',
        kickouts: [
            [],
            []
        ],
        kickouts_drawn_by: [],
        stats: {
            'IanWorst': {
                number: '3',
                first_name: 'Ian',
                last_name: 'Worst',
                goals: 4,
                pronouns: 'he'
            },
            'MicahBayle': {
                number: '5',
                first_name: 'Micah',
                last_name: 'Bayle',
                goals: 1,
                pronouns: 'she'
            },
            'AndyLobbezoo': {
                number: '12',
                first_name: 'Andy',
                last_name: 'Lobbezoo',
                goals: 1,
                pronouns: 'they'
            }
        }
    };
});

test('not made returns false', () => {
    expect(shot(game, 'IanWorst', false)).toBe(false);
});

test('unassisted', () => {
    let {msg} = shot(game, 'IanWorst', true, false);
    expect(msg).toBe(`Hudsonville Goal! #3 Ian Worst, his 4th`);
});

test('assisted', () => {
    let {msg} = shot(game, 'IanWorst', true, 'MicahBayle');
    expect(msg).toBe(`Hudsonville Goal! #3 Ian Worst, his 4th, with the assist by #5 Micah Bayle`);
});

test('advantage', () => {
    game.kickouts[1] = ['1'];
    game.kickouts_drawn_by = ['AndyLobbezoo'];
    let {msg} = shot(game, 'IanWorst', true, false);
    expect(msg).toBe(`Hudsonville Goal off a 6 on 5! #3 Ian Worst scoring his 4th. Advantage drawn by #12 Andy Lobbezoo`);
});

test('advantage and assist', () => {
    game.kickouts[1] = ['1'];
    game.kickouts_drawn_by = ['AndyLobbezoo'];
    let {msg} = shot(game, 'IanWorst', true, 'MicahBayle');
    expect(msg).toBe(`Hudsonville Goal off a 6 on 5! #3 Ian Worst scoring his 4th, with the assist by #5 Micah Bayle. Advantage drawn by #12 Andy Lobbezoo`);
});

test('multiple advantages by a single person', () => {
    game.kickouts[1] = ['1', '2', '3'];
    game.kickouts_drawn_by = ['AndyLobbezoo', 'AndyLobbezoo', 'AndyLobbezoo'];
    let {msg} = shot(game, 'IanWorst', true, 'MicahBayle');
    expect(msg).toBe(`Hudsonville Goal off a 6 on 3! #3 Ian Worst scoring his 4th, with the assist by #5 Micah Bayle. Advantages drawn by #12 Andy Lobbezoo`);
});

test('multiple advantages by multiple people', () => {
    game.kickouts[1] = ['1', '2', '3'];
    game.kickouts_drawn_by = ['AndyLobbezoo', 'IanWorst', 'MicahBayle'];
    let {msg} = shot(game, 'IanWorst', true, 'MicahBayle');
    expect(msg).toBe(`Hudsonville Goal off a 6 on 3! #3 Ian Worst scoring his 4th, with the assist by #5 Micah Bayle. Advantages drawn by #12 Andy Lobbezoo, #3 Ian Worst, and #5 Micah Bayle`);
});

test('pronouns', () => {
    let {msg: he} = shot(game, 'IanWorst', true, false);
    expect(he).toBe(`Hudsonville Goal! #3 Ian Worst, his 4th`);

    let {msg: she} = shot(game, 'MicahBayle', true, false);
    expect(she).toBe(`Hudsonville Goal! #5 Micah Bayle, her 1st`);

    let {msg: they} = shot(game, 'AndyLobbezoo', true, false);
    expect(they).toBe(`Hudsonville Goal! #12 Andy Lobbezoo, their 1st`);
});