const fiveMeterCalled = require('./five-meter-called');

const game = Object.freeze({
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
});

test('formats made correctly', () => {
    const calledOn = null;
    const takenBy = '5';
    const expected = 'Rockford Goal, #5, off a 5 meter';
    let data;

    data = fiveMeterCalled(game, calledOn, takenBy, true);
    expect(data.msg).toBe(expected);

    data = fiveMeterCalled(game, calledOn, takenBy, 'made');
    expect(data.msg).toBe(expected);
});

test('formats blocked correctly', () => {
    const calledOn = null;
    const takenBy = '5';
    const expected = `#1 Elijah Boonstra with a HUGE 5 meter block on Rockford's #5`;
    let data;

    data = fiveMeterCalled(game, calledOn, takenBy, false);
    expect(data.msg).toBe(expected);

    data = fiveMeterCalled(game, calledOn, takenBy, 'blocked');
    expect(data.msg).toBe(expected);
});

test('it returns false for missed', function() {
   expect(fiveMeterCalled(game, null, '5', 'missed'))
       .toBe(false)
});