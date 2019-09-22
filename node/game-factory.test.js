const LockedError = require('./errors/lockedError');
const UnopenedError = require('./errors/unopenedError');
const dataHandler = require('./data/__mocks__')();
const nameKeys = dataHandler.nameKeys;
const players = dataHandler.players;
const baseData = dataHandler.baseData;

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

describe('game methods', () => {

    test('add to box score', () => {
        game.addToBoxScore(true, nameKeys.Ian);
        game.addToBoxScore(false, '1');

        expect(game.boxscore[0][0][nameKeys.Ian]).toBe(1);
        expect(game.boxscore[1][0]['1']).toBe(1);

        game.quarters_played = 1;
        game.boxscore[0][1] = {};
        game.boxscore[1][1] = {};

        game.addToBoxScore(true, nameKeys.Ian);
        game.addToBoxScore(true, nameKeys.Ian);
        expect(game.boxscore[0][1][nameKeys.Ian]).toBe(2);
    });

    test('final just emits', () => {
        game.final();

        expect(mockEmit).toBeCalledTimes(1);
        expect(mockEmit).toBeCalledWith(game.data, 'final', []);
    });

    describe('shot', () => {
        let originalResetKickouts;

        beforeAll(() => {
            originalResetKickouts = game.resetKickouts;
        });

        beforeEach(() => {
            game.resetKickouts = jest.fn();
        });

        afterAll(() => {
            game.resetKickouts = originalResetKickouts;
        });

        describe('made', () => {
            beforeEach(() => {
                game.shot(nameKeys.Ian, true);
            });

            it('increments player shots', () => {
                expect(game.stats[nameKeys.Ian].shots).toBe(1);
            });
            it('increments player goals', () => {
                expect(game.stats[nameKeys.Ian].goals).toBe(1);
            });
            it('increments the box score', () => {
                expect(game.boxscore[0][0][nameKeys.Ian]).toBe(1);
            });
            it('increments the score', () => {
                expect(game.score[0]).toBe(1);
            });
            it('resets kickouts', () => {
                expect(game.resetKickouts).toBeCalledTimes(1);
            });
            it('calls emit', () => {
                expect(mockEmit).toBeCalledTimes(1);
                expect(mockEmit).toBeCalledWith(game.data, 'shot', [nameKeys.Ian, true]);
            });
        });

        describe('missed', () => {
            beforeEach(() => {
                game.shot(nameKeys.Ian, false);
            });

            it('increments player shots', () => {
                expect(game.stats[nameKeys.Ian].shots).toBe(1);
            });
            it('doesnt increment player goals', () => {
                expect(game.stats[nameKeys.Ian].goals).toBe(0);
            });
            it('doesnt reset kickouts', () => {
                expect(game.resetKickouts.mock.calls.length).toBe(0);
            });
            it('calls emit', () => {
                expect(mockEmit).toBeCalledTimes(1);
                expect(mockEmit).toBeCalledWith(game.data, 'shot', [nameKeys.Ian, false]);
            });
        });

        describe('assisted', () => {
            beforeEach(() => {
                game.shot(nameKeys.Ian, true, nameKeys.Chandler);
            });

            it('increments player shots', () => {
                expect(game.stats[nameKeys.Ian].shots).toBe(1);
            });
            it('increments player goals', () => {
                expect(game.stats[nameKeys.Ian].goals).toBe(1);
            });
            it('increments the box score', () => {
                expect(game.boxscore[0][0][nameKeys.Ian]).toBe(1);
            });
            it('increments the score', () => {
                expect(game.score[0]).toBe(1);
            });
            it('resets kickouts', () => {
                expect(game.resetKickouts).toBeCalledTimes(1);
            });
            it('increments assists', () => {
                expect(game.stats[nameKeys.Chandler].assists).toBe(1);
            });
            it('calls emit', () => {
                expect(mockEmit).toBeCalledTimes(1);
                expect(mockEmit).toBeCalledWith(game.data, 'shot', [nameKeys.Ian, true, nameKeys.Chandler]);
            });
        });

        describe('advantage', () => {
            beforeEach(() => {
                game.kickouts[1] = ['1'];
                game.kickouts_drawn_by = [nameKeys.Ian];
                game.advantage_conversion[0].drawn = 1;
                game.shot(nameKeys.Ian, true);
            });

            it('increments advantage goals', () => {
                expect(game.stats[nameKeys.Ian].advantage_goals).toBe(1);
            });
            it('increments player shots', () => {
                expect(game.stats[nameKeys.Ian].shots).toBe(1);
            });
            it('increments player goals', () => {
                expect(game.stats[nameKeys.Ian].goals).toBe(1);
            });
            it('increments the box score', () => {
                expect(game.boxscore[0][0][nameKeys.Ian]).toBe(1);
            });
            it('increments the score', () => {
                expect(game.score[0]).toBe(1);
            });
            it('increments the advantage converted', () => {
                expect(game.advantage_conversion[0].converted).toBe(1);
            });
            it('resets kickouts', () => {
                expect(game.resetKickouts).toBeCalledTimes(1);
            });
            it('calls emit', () => {
                expect(mockEmit).toBeCalledTimes(1);
                expect(mockEmit).toBeCalledWith(game.data, 'shot', [nameKeys.Ian, true]);
            });
        });

    });

    test('steal', () => {
        game.steal(nameKeys.Chandler);
        game.steal(nameKeys.Chandler);
        expect(game.stats[nameKeys.Chandler].steals).toBe(2);
        expect(mockEmit).toBeCalledTimes(2);
        expect(mockEmit).toBeCalledWith(game.data, 'steal', [nameKeys.Chandler]);
    });

    test('turnover', () => {
        game.turnover(nameKeys.Chandler);
        game.turnover(nameKeys.Chandler);
        expect(game.stats[nameKeys.Chandler].turnovers).toBe(2);
        expect(mockEmit).toBeCalledTimes(2);
        expect(mockEmit).toBeCalledWith(game.data, 'turnover', [nameKeys.Chandler]);
    });

    test('block', () => {
        game.block(nameKeys.Chandler);
        game.block(nameKeys.Chandler);
        expect(game.stats[nameKeys.Chandler].blocks).toBe(2);
        expect(mockEmit).toBeCalledTimes(2);
        expect(mockEmit).toBeCalledWith(game.data, 'block', [nameKeys.Chandler]);
    });

    describe('kickout', () => {
        beforeEach(() => {
            game.kickout(nameKeys.Chandler);
        });

        it('increments player kickouts', () => {
            expect(game.stats[nameKeys.Chandler].kickouts).toBe(1);
        });
        it('adds to out kickouts', () => {
            expect(game.kickouts[0].length).toBe(1);
            expect(game.kickouts[0][0]).toBe(nameKeys.Chandler);
        });
        it('adds to the opponents advantages drawn', () => {
            expect(game.advantage_conversion[1].drawn).toBe(1);
        });
        it('calls emit', () => {
            expect(mockEmit).toBeCalledTimes(1);
            expect(mockEmit).toBeCalledWith(game.data, 'kickout', [nameKeys.Chandler]);
        });
    });

    describe('kickoutDrawn', () => {
        beforeEach(() => {
            game.kickoutDrawn(nameKeys.Ian);
        });

        it('increments player kickouts drawn', () => {
            expect(game.stats[nameKeys.Ian].kickouts_drawn).toBe(1);
        });
        it('adds to the game kickouts', () => {
            expect(game.kickouts[1].length).toBe(1);
            expect(game.kickouts[1][0]).toBe(1);
        });
        it('adds to the kickouts drawn by', () => {
            expect(game.kickouts_drawn_by.length).toBe(1);
            expect(game.kickouts_drawn_by[0]).toBe(nameKeys.Ian);
        });
        it('increments the games advantages drawn', () => {
            expect(game.advantage_conversion[0].drawn).toBe(1);
        });
        it('calls emit', () => {
            expect(mockEmit).toBeCalledTimes(1);
            expect(mockEmit).toBeCalledWith(game.data, 'kickoutDrawn', [nameKeys.Ian]);
        });
    });

    describe('kickoutOver', () => {
        test('over for us', () => {
            game.kickouts[0] = [nameKeys.Ian, nameKeys.Chandler];
            game.kickoutOver(nameKeys.Ian);

            expect(game.kickouts[0].length).toBe(1);
            expect(game.kickouts[0][0]).toBe(nameKeys.Chandler);
            expect(mockEmit).toBeCalledTimes(1);
            expect(mockEmit).toBeCalledWith(game.data, 'kickoutOver', [nameKeys.Ian]);
        });

        test('over for them', () => {
            game.kickouts[1] = [1, 2];
            game.kickoutOver(false);

            expect(game.kickouts[1].length).toBe(1);
            expect(game.kickouts[1][0]).toBe(2);
            expect(mockEmit).toBeCalledTimes(1);
            expect(mockEmit).toBeCalledWith(game.data, 'kickoutOver', [false]);
        });
    });

    test('resetKickouts', () => {
        game.kickouts = [[nameKeys.Ian, nameKeys.Chandler], [1, 2]];
        game.kickouts_drawn_by = [nameKeys.Eli];
        game.resetKickouts();

        expect(game.kickouts[0].length).toBe(0);
        expect(game.kickouts[1].length).toBe(0);
        expect(game.kickouts_drawn_by.length).toBe(0);
    });

    test('save', () => {
        game.save();

        expect(game.stats[nameKeys.Eli].saves).toBe(1);
        expect(mockEmit).toBeCalledTimes(1);
        expect(mockEmit).toBeCalledWith(game.data, 'save', []);
    });

    describe('goalAllowed', () => {
        describe('normal goal', () => {
            beforeEach(() => {
                game.goalAllowed('5');
            });

            it('increments the goalies stat', () => {
                expect(game.stats[nameKeys.Eli].goals_allowed).toBe(1);
            });
            it('increments the score', () => {
                expect(game.score[1]).toBe(1);
            });
            it('emits', () => {
                expect(mockEmit).toBeCalledTimes(1);
                expect(mockEmit).toBeCalledWith(game.data, 'goalAllowed', ['5']);
            })
        });

        describe('advantage goal', () => {
            beforeEach(() => {
                game.kickouts[0] = [nameKeys.Chandler];
                game.advantage_conversion[0].drawn = 1;
                game.goalAllowed('5');
            });

            it('increments the goalies stat', () => {
                expect(game.stats[nameKeys.Eli].advantage_goals_allowed).toBe(1);
            });
            it('increments their advantage conversion', () => {
                expect(game.advantage_conversion[1].converted).toBe(1);
            });
        });
    });

    describe('sprint', () => {
        test('won', () => {
            game.sprint(nameKeys.Eli, true);

            expect(game.stats[nameKeys.Eli].sprints_taken).toBe(1);
            expect(game.stats[nameKeys.Eli].sprints_won).toBe(1);
            expect(mockEmit).toBeCalledTimes(1);
            expect(mockEmit).toBeCalledWith(game.data, 'sprint', [nameKeys.Eli, true]);
        });

        test('lost', () => {
            game.sprint(nameKeys.Eli, false);

            expect(game.stats[nameKeys.Eli].sprints_taken).toBe(1);
            expect(game.stats[nameKeys.Eli].sprints_won).toBe(0);
            expect(mockEmit).toBeCalledTimes(1);
            expect(mockEmit).toBeCalledWith(game.data, 'sprint', [nameKeys.Eli, false]);
        });
    });

    describe('fiveMeterDrawn', () => {
        describe.each([
            [true, nameKeys.Ian, nameKeys.Chandler],
            [false, nameKeys.Ian, nameKeys.Chandler],
            ['made', nameKeys.Ian, nameKeys.Chandler],
            ['missed', nameKeys.Ian, nameKeys.Chandler],
            ['blocked', nameKeys.Ian, nameKeys.Chandler]
        ])('%s', (made, drawnBy, takenBy) => {
            beforeEach(() => {
                game.fiveMeterDrawn(drawnBy, takenBy, made);
            });

            it('increments drawn', () => {
                expect(game.stats[drawnBy].five_meters_drawn).toBe(1);
            });
            it('increments taken', () => {
                expect(game.stats[takenBy].five_meters_taken).toBe(1);
                expect(game.stats[takenBy].shots).toBe(1);
            });

            if (made === true || made === 'made') {
                it('increments made', () => {
                    expect(game.stats[takenBy].five_meters_made).toBe(1);
                    expect(game.stats[takenBy].goals).toBe(1);
                });
                it('increments score', () => {
                    expect(game.score[0]).toBe(1);
                })
            }

            it('emits', () => {
                expect(mockEmit).toBeCalledTimes(1);
                expect(mockEmit).toBeCalledWith(game.data, 'fiveMeterDrawn', [drawnBy, takenBy, made]);
            });
        });
    });

    describe('fiveMeterCalled', () => {
        describe.each([
            [true, nameKeys.Eli, '5'],
            [false, nameKeys.Eli, '5'],
            ['made', nameKeys.Eli, '5'],
            ['missed', nameKeys.Eli, '5'],
            ['blocked', nameKeys.Eli, '5'],
        ])('%s', (made, player, takenBy) => {
            beforeEach(() => {
                game.fiveMeterCalled(player, takenBy, made);
            });

            it('increments called', () => {
                expect(game.stats[player].five_meters_called).toBe(1);
            });
            it('increments player kickouts', () => {
                expect(game.stats[player].kickouts).toBe(1);
            });
            it('increments goalie five meter taken on', () => {
                expect(game.stats[nameKeys.Eli].five_meters_taken_on).toBe(1);
            });

            switch(made) {
                case true:
                case 'made':
                    it('increments the score', () => {
                        expect(game.score[1]).toBe(1);
                    });
                    it('increments goals allowed', () => {
                        expect(game.stats[nameKeys.Eli].goals_allowed).toBe(1);
                    });
                    it('increments five meters allowed', () => {
                        expect(game.stats[nameKeys.Eli].five_meters_allowed).toBe(1);
                    });
                    break;

                case false:
                case 'blocked':
                    it('increments blocks', () => {
                        expect(game.stats[nameKeys.Eli].five_meters_blocked).toBe(1);
                    });
                    it('increments saves', () => {
                        expect(game.stats[nameKeys.Eli].saves).toBe(1);
                    });
            }

            it('emits', () => {
                expect(mockEmit).toBeCalledTimes(1);
                expect(mockEmit).toBeCalledWith(game.data, 'fiveMeterCalled', [player, takenBy, made]);
            });
        });
    });

    describe('shootOutUs', () => {
        describe.each([
            [true, nameKeys.Chandler],
            [false, nameKeys.Chandler],
            ['made', nameKeys.Chandler],
            ['blocked', nameKeys.Chandler],
            ['missed', nameKeys.Chandler]
        ])('%s', (made, takenBy) => {
            beforeEach(() => {
                game.shootOutUs(takenBy, made);
            });

            it('increments shots', () => {
                expect(game.stats[nameKeys.Chandler].shots).toBe(1);
            });
            it('increments shootout taken', () => {
                expect(game.stats[nameKeys.Chandler].shoot_out_taken).toBe(1);
            });

            if(made === true || made ==='made') {
                it('increments the score', () => {
                    expect(game.score[0]).toBe(1);
                });
                it('increments player goals', () => {
                    expect(game.stats[nameKeys.Chandler].goals).toBe(1);
                });
                it('increments shoot out made', () => {
                    expect(game.stats[nameKeys.Chandler].shoot_out_made).toBe(1);
                });
            }

            it('emits', () => {
                expect(mockEmit).toBeCalledTimes(1);
                expect(mockEmit).toBeCalledWith(game.data, 'shootOutUs', [takenBy, made]);
            });
        });
    });

    describe('shootOutThem', () => {
        describe.each([
            [true, '5'],
            [false, '5'],
            ['made', '5'],
            ['blocked', '5'],
            ['missed', '5'],
        ])('%s', (made, takenBy) => {
            beforeEach(() => {
                game.shootOutThem(takenBy, made);
            });

            it('increments goalie taken on', () => {
                expect(game.stats[nameKeys.Eli].shoot_out_taken_on).toBe(1);
            });

            if(made === true || made ==='made') {
                it('increments the score', () => {
                    expect(game.score[1]).toBe(1);
                });
                it('increments goals allowed', () => {
                    expect(game.stats[nameKeys.Eli].goals_allowed).toBe(1);
                });
                it('increments shoot out allowed', () => {
                    expect(game.stats[nameKeys.Eli].shoot_out_allowed).toBe(1);
                });
            } else if (made === false || made === 'blocked') {
                it('increments saves', () => {
                    expect(game.stats[nameKeys.Eli].saves).toBe(1);
                });
                it('increments shoot out blocked', () => {
                    expect(game.stats[nameKeys.Eli].shoot_out_blocked).toBe(1);
                });
            }

            it('emits', () => {
                expect(mockEmit).toBeCalledTimes(1);
                expect(mockEmit).toBeCalledWith(game.data, 'shootOutThem', [takenBy, made]);
            });
        });
    });

    test('changeGoalie', () => {
        game.changeGoalie(nameKeys.Ian);

        expect(game.goalie).toBe(nameKeys.Ian);
        expect(mockEmit).toBeCalledTimes(1);
        expect(mockEmit).toBeCalledWith(game.data, 'changeGoalie', [nameKeys.Ian]);
    });

    test('setStatus', () => {
        const status = 'quarter';
        game.setStatus(status);

        expect(game.status).toBe(status);
        expect(mockEmit).toBeCalledTimes(1);
        expect(mockEmit).toBeCalledWith(game.data, 'setStatus', [status]);
    });

    describe('setQuarterPlayed', () => {
        beforeEach(() => {
            game.setQuartersPlayed(4);
        });

        it('should update the data', () => {
            expect(game.quarters_played).toBe(4);
        });

        it('should add blank boxscores', () => {
            expect(game.boxscore[0].length).toBe(5);
            expect(game.boxscore[1].length).toBe(5);
        });

        it('emits', () => {
            expect(mockEmit).toBeCalledTimes(1);
            expect(mockEmit).toBeCalledWith(game.data, 'setQuartersPlayed', [4]);
        })

    });

    describe('timeout', () => {
        describe.each([
            [true, {minues: 3, seconds: 16}],
            [false, {minues: 3, seconds: 16}]
        ])('us: %s', (us, time) => {
            it('emit', () => {
                game.timeout(us, time);

                let team = us ? game.us : game.opponent;
                expect(mockEmit).toBeCalledTimes(1);
                expect(mockEmit).toBeCalledWith(game.data, 'timeout', [team, time]);
            });
        });
    });

    test('carded', () => {
        const recipient = 'Freddie';
        const color = 'Yellow';

        game.carded(recipient, color);
        expect(mockEmit).toBeCalledTimes(1);
        expect(mockEmit).toBeCalledWith(game.data, 'carded', [recipient, color]);
    });

    test('shout', () => {
        const msg = 'Hello World!';

        game.shout(msg);
        expect(mockEmit).toBeCalledTimes(1);
        expect(mockEmit).toBeCalledWith(game.data, 'shout', [msg]);
    });

    describe('updatePlayers', () => {
        beforeEach(() => {
            const henry = {
                name_key: nameKeys.Henry,
                first_name: 'Henry',
                last_name: 'Booker',
                number: '13',
                number_sort: 13
            };

            const eli = players[0];

            game.updatePlayers([henry], [eli]);
        });

        it('adds new players', () => {
            expect(game.stats).toHaveProperty(nameKeys.Henry);
        });

        it('adds new players with all the data', () => {
            expect(Object.keys(game.stats[nameKeys.Henry]).length).toBeGreaterThanOrEqual(Object.keys(baseData).length);
        });

        it('removes players', () => {
            expect(game.stats).not.toHaveProperty(nameKeys.Eli);
        });
    });
});