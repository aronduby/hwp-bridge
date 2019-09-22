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
        number_sort: 1,
        team: ['V']
    },
    {
        name_key: nameKeys.Chandler,
        first_name: 'Chandler',
        last_name: 'Jones',
        number: "2",
        number_sort: 2,
        team: ['V']
    },
    {
        name_key: nameKeys.Ian,
        first_name: 'Ian',
        last_name: 'Worst',
        number: "3",
        number_sort: 3,
        team: ['V']
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

module.exports = function dataHandler(pool) {
    return {
        nameKeys, players, baseData,

        describeStats: () => {
            return Promise.resolve(Object.keys(baseData));
        },

        finalizeGameData: (gameData, updates) => {
            return Promise.resolve(true);
        },

        getGameData: (gameId) => {
            const data = {
                game_id: gameId,
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
                goalie: nameKeys.Eli,
                advantage_conversion: [
                    {drawn: 0, converted: 0},
                    {drawn: 0, converted: 0}
                ],
                kickouts: [[], []],
                kickouts_drawn_by: [],
                boxscore: [[{}], [{}]],
                score: [0, 0]
            };

            data.stats = players.reduce((acc, player) => {
                acc[player.name_key] = {
                    ...player,
                    ...baseData
                };

                return acc;
            }, {});

            return Promise.resolve(data);
        },

        loadPlayers: (seasonId, team) => {
            return Promise.resolve(players);
        },

        saveGameState: (gameData) => {
            return Promise.resolve(true);
        }
    };
};