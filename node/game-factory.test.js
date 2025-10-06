const LockedError = require('./errors/lockedError');
const UnopenedError = require('./errors/unopenedError');
const dataHandler = require('./data/__mocks__')();

let gameFactory, game, mockEmit, mockUpdateManager;

// create a new game for each test
beforeEach(async (done) => {
    mockEmit = jest.fn();
    const mockEmitter = {
        emit: mockEmit
    };

    mockUpdateManager = {
        get: jest.fn().mockReturnValue([]),
        clear: jest.fn()
    };

    gameFactory = require('./game-factory')(dataHandler, mockEmitter, mockUpdateManager);
    game = await gameFactory.open(1, 1);
    done();
});

describe('factory methods', () => {

    describe('open', () => {
        it('adds the proper data to the active games', () => {
            const activeData = gameFactory.activeGames[1];
            expect(activeData.gameId).toBe(1);
            expect(activeData.siteId).toBe(1);
            expect(activeData.owner).toBe(1);
        });

        it('rejects if a different owner tries to open an existing game', async () => {
            expect.assertions(2);
            try {
                await gameFactory.open(1, 2);
            } catch (err) {
                expect(err).toBeInstanceOf(LockedError);
                expect(err.owner).toBe(1);
            }
        });

        it('lets someone steal control of an existing game', async () => {
            await gameFactory.open(1, 2, true);
            expect(gameFactory.activeGames[1].owner).toBe(2);
        });
    });

    describe('finalize', () => {
        it(`throws an unopened error if it hasn't already been opened`, async () => {
            try {
                await gameFactory.finalize(2, 1);
            } catch (err) {
                expect(err).toBeInstanceOf(UnopenedError);
            }
        });

        it(`throws a locked error if its the wrong owner`, async () => {
            try {
                await gameFactory.finalize(1, 2);
            } catch (err) {
                expect(err).toBeInstanceOf(LockedError);
                expect(err.owner).toBe(1);
            }
        });

        it('calls the finalizeGameData handler', async () => {
            // temporarily mock the finalizeGameData method
            const orgMethod = dataHandler.finalizeGameData;
            const mockedSaver = jest.fn();
            mockedSaver.mockReturnValue(Promise.resolve(true));
            dataHandler.finalizeGameData = mockedSaver;

            await gameFactory.finalize(1, 1);

            expect(mockUpdateManager.get).toBeCalledTimes(1);
            expect(mockUpdateManager.get).toBeCalledWith(1);
            expect(mockUpdateManager.clear).toBeCalledTimes(1);
            expect(mockUpdateManager.clear).toBeCalledWith(1);

            expect(mockedSaver).toBeCalledTimes(1);
            expect(mockedSaver.mock.calls[0][1]).toEqual([]);

            // reset the finalizeGameData method
            dataHandler.finalizeGameData = orgMethod;
        });

        it(`removes the game from the active data`, async () => {
            await gameFactory.finalize(1, 1);

            expect(gameFactory.activeGames[1]).toBeUndefined();
        });
    });
});

test('emit doesnt lock the data', async () => {
    game = await gameFactory.open(1, 1);

    const status = 'quarter';
    game.setStatus(status);

    expect(game.data.status).toBe(status);
    expect(mockEmit).toBeCalledTimes(1);
    expect(mockEmit).toBeCalledWith(game.data, 'setStatus', [status]);

    game = await gameFactory.open(1, 1);
    const newStatus = 'final';
    game.setStatus(newStatus);

    expect(game.data.status).toBe(newStatus);
    expect(mockEmit).toBeCalledTimes(2);
    expect(mockEmit).toBeCalledWith(game.data, 'setStatus', [newStatus]);
});