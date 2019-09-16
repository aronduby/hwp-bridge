const GameEmitter = require('./game-emitter');
const gameFactory = require('./game-factory');
const events = require('./events');

const nameKeys = {
    Eli: 'EliBoonstra',
    Chandler: 'ChandlerJones',
    Ian: 'IanWorst',
    Henry: 'HenryBooker'
};

const players = [
    {
        name_key: nameKeys.Eli,
        first_name: 'Eli',
        last_name: 'Boonstra',
        number: "1",
        number_sort: 1
    },
    {
        name_key: nameKeys.Chandler,
        first_name: 'Chandler',
        last_name: 'Jones',
        number: "2",
        number_sort: 2
    },
    {
        name_key: nameKeys.Ian,
        first_name: 'Ian',
        last_name: 'Worst',
        number: "3",
        number_sort: 3
    }
];

const baseData = {
    "name_key": "",
    "first_name": "",
    "last_name": "",
    "number": "",
    "team": ["V"],
    "number_sort": 0,
    "updated_at": 0,
    "created_at": 0,
    "advantage_goals_allowed": 0,
    "advantage_goals": 0,
    "shoot_out_allowed": 0,
    "shoot_out_blocked": 0,
    "shoot_out_taken_on": 0,
    "shoot_out_made": 0,
    "shoot_out_taken": 0,
    "five_meters_allowed": 0,
    "five_meters_blocked": 0,
    "five_meters_taken_on": 0,
    "five_meters_called": 0,
    "five_meters_made": 0,
    "five_meters_taken": 0,
    "five_meters_drawn": 0,
    "sprints_won": 0,
    "sprints_taken": 0,
    "goals_allowed": 0,
    "saves": 0,
    "kickouts": 0,
    "kickouts_drawn": 0,
    "blocks": 0,
    "turnovers": 0,
    "steals": 0,
    "assists": 0,
    "shots": 0,
    "goals": 0,
    "game_id": 0,
    "season_id": 0,
    "player_id": 0,
    "site_id": 0,
    "id": 0,
};

let game, gameEmitter, mockEvents, mockBroadcaster;

// create a new game at the beginning
beforeAll(() => {
    gameEmitter = new GameEmitter();
    gameFactory.setEmitter(gameEmitter.emit.bind(gameEmitter));

    game = gameFactory.get(1);
    game.data = {
        game_id: 1,
        season_id: 10,
        site_id: 1,
        version: '1.1',
        us: 'Hudsonville',
        opponent: 'Rockford',
        title: null,
        team: null,
        status: null,
        quarters_played: 0,
        stats: {},
        goalie: null,
        advantage_conversion: [
            {drawn: 0, converted: 0},
            {drawn: 0, converted: 0}
        ],
        kickouts: [[], []],
        kickouts_drawn_by: [],
        boxscore: [[{}], [{}]],
        score: [0, 0]
    };
    game.data.stats = players.reduce((acc, player) => {
        acc[player.name_key] = {
            ...player,
            ...baseData
        };

        return acc;
    }, {});
    game.data.goalie = nameKeys.Eli;
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
            game[e].apply(game, args);
            rsp = [game.data, ...rsp];
        });

        it('should emit the events', () => {
            expect(mockEvents[e]).toBeCalledTimes(1);
            expect(mockEvents[e]).toBeCalledWith(...rsp);
        });

        it('should called broadcaster based on the return value', () => {
            if (mockEvents[e].mock.results[0].value !== false) {
                expect(mockBroadcaster).toBeCalledTimes(1);
            } else {
                expect(mockBroadcaster).not.toBeCalled();
            }
        });
    });
});