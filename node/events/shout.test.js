const shout = require('./shout');

test('it shouts', function() {
    const was = 'Hello world!';
    const {msg} = shout({}, was);

    expect(msg).toBe(was);
});