const shot = require('./shot');

const game = {
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
            goals: 4
        },
        'MicahBayle': {
            number: '5',
            first_name: 'Micah',
            last_name: 'Bayle',
        },
        'AndyLobbezoo': {
            number: '12',
            first_name: 'Andy',
            last_name: 'Lobbezoo'
        }
    }
};

beforeEach(() => {
    game.kickouts_drawn_by = [];
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