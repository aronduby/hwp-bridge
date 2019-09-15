const shootOutThem = require('./shoot-out-them');

const game = {
    us: "Hudsonville",
    opponent: "Rockford",
    goalie: 'ElijahBoonstra',
    stats: {
        'ElijahBoonstra': {
            number: '1',
            first_name: 'Elijah',
            last_name: 'Boonstra'
        }
    }
};

test('true', function() {
   const {msg} = shootOutThem(game, '5', true);
   expect(msg).toBe('Rockford Goal, #5');
});

test('made', function() {
    const {msg} = shootOutThem(game, '5', 'made');
    expect(msg).toBe('Rockford Goal, #5');
});

test('false', function() {
    const {msg} = shootOutThem(game, '5', false);
    expect(msg).toBe('Rockford shot by #5 BLOCKED by #1 Elijah Boonstra');
});

test('blocked', function() {
    const {msg} = shootOutThem(game, '5', 'blocked');
    expect(msg).toBe('Rockford shot by #5 BLOCKED by #1 Elijah Boonstra');
});

test('missed', function() {
    const {msg} = shootOutThem(game, '5', 'missed');
    expect(msg).toBe('Rockford shot by #5 missed');
});
