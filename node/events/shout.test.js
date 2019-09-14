const shout = require('./shout').event;

test('it shouts', function() {
    const was = 'Hello world!';
    const {msg} = shout({}, was);

    expect(msg).toBe(was);
});