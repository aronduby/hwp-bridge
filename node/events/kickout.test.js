const kickout = require('./kickout');

const game = Object.freeze({
    us: 'Hudsonville',
    stats: {
        'PatrickTutt': {
            number: '7',
            first_name: 'Patrick',
            last_name: 'Tutt',
            kickouts: 1
        }
    }
});

test('formatting', () => {
    let {msg} = kickout(game, 'PatrickTutt');

    expect(msg).toBe('Hudsonville kick-out on #7 Patrick Tutt, his 1st');
});