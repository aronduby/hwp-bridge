const shootOutUs = require('./shoot-out-us');

const game = {
    us: 'Hudsonville',
    stats: {
        'PatrickTutt': {
            number: '7',
            first_name: 'Patrick',
            last_name: 'Tutt'
        }
    }
};

test('true', function() {
   const {msg} = shootOutUs(game, 'PatrickTutt', true);
   expect(msg).toBe('Hudsonville Goal! #7 Patrick Tutt');
});

test('made', function() {
    const {msg} = shootOutUs(game, 'PatrickTutt', 'made');
    expect(msg).toBe('Hudsonville Goal! #7 Patrick Tutt');
});

test('false', function() {
    const {msg} = shootOutUs(game, 'PatrickTutt', false);
    expect(msg).toBe('#7 Patrick Tutt shot is blocked');
});

test('blocked', function() {
    const {msg} = shootOutUs(game, 'PatrickTutt', 'blocked');
    expect(msg).toBe('#7 Patrick Tutt shot is blocked');
});

test('missed', function() {
    const {msg} = shootOutUs(game, 'PatrickTutt', 'missed');
    expect(msg).toBe('#7 Patrick Tutt shot is no good');
});
