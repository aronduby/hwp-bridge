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

let game, gameEmitter, mockEvents;

// create a new game at the beginning
beforeAll(() => {
    gameEmitter = new GameEmitter();
    // it auto-binds the actual events, so undo that here
    // then add mocks for all of them
    gameEmitter.removeAllListeners();
    mockEvents = Object.keys(events).reduce((acc, key) => {
        acc[key] = jest.fn();
        return acc;
    }, {});
    gameEmitter.bindEventListeners(mockEvents);
    gameFactory.setEmitter(gameEmitter.trigger.bind(gameEmitter));


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

describe('events are emitted', () => {
    test.each([
        ['carded', ['Josh', 'yellow'], null],
        ['final', [], null],
        ['fiveMeterCalled', [nameKeys.Chandler, '5', true], null],
        ['fiveMeterDrawn', [nameKeys.Ian, nameKeys.Chandler, true], null],
        ['goalAllowed', ['5'], null],
        ['kickout', [nameKeys.Chandler], null],
        ['setQuartersPlayed', [1], null],
        ['shootOutThem', ['5', false], null],
        ['shootOutUs', [nameKeys.Chandler, true], null],
        ['shot', [nameKeys.Ian, true, nameKeys.Chandler], null],
        ['shout', ['Hello World!'], null],
        ['sprint', [nameKeys.Eli, true], null],
        ['timeout', [true, {seconds: 3}], ['Hudsonville', {seconds: 3}]],
    ])('%s', (e, args, rsp) => {
        rsp = rsp || args;

        game[e].apply(game, args);
        rsp = [game.data, ...rsp];

        expect(mockEvents[e]).toBeCalledTimes(1);
        expect(mockEvents[e]).toBeCalledWith(...rsp);
    });
});