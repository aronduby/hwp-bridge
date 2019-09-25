const GameEmitter = require('./game-emitter');
const events = require('./events');
const dataHandler = require('./data/__mocks__')();
const nameKeys = dataHandler.nameKeys;

let game, gameEmitter, mockEvents, mockBroadcaster;

// create a new game at the beginning
beforeAll(async (done) => {
    gameEmitter = new GameEmitter();
    const gameFactory = require('./game-factory')(dataHandler, gameEmitter);
    game = await gameFactory.open(1, 1);
    done();
});

const cases = [
    ['carded', ['Josh', 'yellow'], null, true],
    ['final', [], null, true],
    ['fiveMeterCalled', [nameKeys.Chandler, '5', true], null, true],
    ['fiveMeterDrawn', [nameKeys.Ian, nameKeys.Chandler, true], null, true],
    ['goalAllowed', ['5'], null, true],
    ['kickout', [nameKeys.Chandler], null, true],
    ['setQuartersPlayed', [1], null, true],
    ['shootOutThem', ['5', false], null, true],
    ['shootOutUs', [nameKeys.Chandler, true], null, true],
    ['shot', [nameKeys.Ian, true, nameKeys.Chandler], null, true],
    ['shot', [nameKeys.Ian, false], null, false],
    ['shout', ['Hello World!'], null, true],
    ['sprint', [nameKeys.Eli, true], null, true],
    ['timeout', [true, {seconds: 3}], ['Hudsonville', {seconds: 3}], true],
];

describe('events are emitted', () => {
    describe.each(cases)('%s', (e, args, rsp, mockedReturn) => {

        beforeEach(() => {
            // it auto-binds the actual events, so undo that here
            // then add mocks for all of them
            mockEvents = Object.keys(events).reduce((acc, key) => {
                acc[key] = jest.fn();
                return acc;
            }, {});
            gameEmitter.removeAllListeners();
            gameEmitter.bindEventListeners(mockEvents);

            mockBroadcaster = jest.fn();
            gameEmitter.setBroadcaster(mockBroadcaster);

            mockEvents[e].mockReturnValue(mockedReturn);

            rsp = rsp || args;
            game.resetKickouts();
            game[e].apply(game, args);
            // rsp = [game.data, ...rsp];
        });

        it('should emit the events', () => {
            expect(mockEvents[e]).toBeCalledTimes(1);
            expect(mockEvents[e]).toBeCalledWith(game.data, ...(rsp||args));
        });

        it('should called broadcaster based on the return value', () => {
            if (mockEvents[e].mock.results[0].value !== false) {
                expect(mockBroadcaster).toBeCalledTimes(1);
                expect(mockBroadcaster.mock.calls[0][0]).toEqual(game.data);
            } else {
                expect(mockBroadcaster).not.toBeCalled();
            }
        });
    });
});