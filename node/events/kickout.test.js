const kickout = require('./kickout');

const game = Object.freeze({
    us: 'Hudsonville',
    stats: {
        'PatrickTutt': {
            number: '7',
            first_name: 'Patrick',
            last_name: 'Tutt',
            kickouts: 1,
            pronouns: 'he'
        },
        'EthanDennis': {
            number: '15',
            first_name: 'Ethan',
            last_name: 'Dennis',
            kickouts: 1,
            pronouns: 'they'
        },
        'ClaireTuttle': {
            number: '6',
            first_name: 'Claire',
            last_name: 'Tuttle',
            kickouts: 1,
            pronouns: 'she'
        }
    }
});

test('formatting', () => {
    let {msg} = kickout(game, 'PatrickTutt');
    expect(msg).toBe('Hudsonville kick-out on #7 Patrick Tutt, his 1st');
});

test('pronouns', () => {
    let {msg: msgHe} = kickout(game, 'PatrickTutt');
    expect(msgHe).toBe('Hudsonville kick-out on #7 Patrick Tutt, his 1st');

    let {msg: msgThey} = kickout(game, 'EthanDennis');
    expect(msgThey).toBe('Hudsonville kick-out on #15 Ethan Dennis, their 1st');

    let {msg: msgShe} = kickout(game, 'ClaireTuttle');
    expect(msgShe).toBe('Hudsonville kick-out on #6 Claire Tuttle, her 1st');
});