/**
 * @typedef {object} PlayerStats
 * @property {string} name_key - player's name key, usually FirstnameLastname
 * @property {string} first_name -player's first name
 * @property {string} last_name - player's last name
 * @property {string} number - player's cap number
 * @property {array<'V','JV'>} team - player's team
 * @property {int} number_sort - sortable number
 * @property {int} game_id - the id of the game this belongs to
 * @property {int} season_id - the season of the game this belongs to
 * @property {int} player_id - the player's id
 * @property {int} site_id - the site's id for tenanting
 * @property {int} updated_at - last time this was updated in the db
 * @property {int} created_at - when it was created in the db
 * @property {int} advantage_goals_allowed -
 * @property {int} advantage_goals -
 * @property {int} shoot_out_allowed -
 * @property {int} shoot_out_blocked -
 * @property {int} shoot_out_taken_on -
 * @property {int} shoot_out_made -
 * @property {int} shoot_out_taken -
 * @property {int} five_meters_allowed -
 * @property {int} five_meters_blocked -
 * @property {int} five_meters_taken_on -
 * @property {int} five_meters_called -
 * @property {int} five_meters_made -
 * @property {int} five_meters_taken -
 * @property {int} five_meters_drawn -
 * @property {int} sprints_won -
 * @property {int} sprints_taken -
 * @property {int} goals_allowed -
 * @property {int} saves -
 * @property {int} kickouts -
 * @property {int} kickouts_drawn -
 * @property {int} blocks -
 * @property {int} turnovers -
 * @property {int} steals -
 * @property {int} assists -
 * @property {int} shots -
 * @property {int} goals -
 */

/**
 * @typedef {object} AdvantageConversion
 * @property {int} drawn - 0, number of advantages drawn
 * @property {int} converted - 0, number of advantages converted
 */

/**
 * @typedef {object} GameData
 * @property {int} game_id - the id field for the game
 * @property {int} season_id - id field for the season
 * @property {int} site_id - id field for the site
 * @property {?string} version - @deprecated - '1.1', string that describes the version of the format, not used anymore
 * @property {string} us - 'Hudsonville', the name of our team
 * @property {?string} opponent - the name of the opposing team
 * @property {?string} title - title of the game, if null will default to `Game against ${opponent}`
 * @property {?'V','JV'} team - string of which team is playing
 * @property {?'start'|'quarter'|'final'|'shootout'} status - status of the game, used in menus and the like
 * @property {int} quarters_played - 0, how many quarters have been played? basically 0-based index of quarter
 * @property {object<string, PlayerStats>} stats - dictionary of player stats with key of name_key
 * @property {?string} goalie - name_key of who's in goal
 * @property {array<{AdvantageConversion}>} advantage_conversion - 0 is us, 1 is opponent
 * @property {array<array<string>>} kickouts - array of strings of people kicked out, 0 is us, 1 is opponent
 * @property {array<string>} kickouts_drawn_by - array of our players who have a kickout currently drawn
 * @property {array<array<object.<string, int>>>} boxscore - array representing box scores, 0 is us, 1 is opponent, next level is quarters which contain nameKeys string and goal count val
 * @property {array<int>} score - current score, 0 is us, 1 is opponent
 */

/**
 * @typedef ActiveGameData
 * @type {object}
 * @param {string|int} gameId - the id for the game
 * @param {string|int} siteId - the id for the site the game belongs to
 * @param {string} owner - the owning user, (who opened the game)
 * @param {Game} game - technically the game proxy
 */

const LockedError = require('./errors/lockedError');
const UnopenedError = require('./errors/unopenedError');


// Export the factory methods
module.exports = function(dataHandler, emitter, updateManager) {
    return {
        /**
         * @property {object.<string|int, ActiveGameData>}
         */
        activeGames: {},

        open: async function (gameId, ownerId, stealLock) {
            if (!this.activeGames[gameId]) {
                const g = new Game(gameId, emitter);
                const proxy = new Proxy(g, gameDataHandler);

                const data = await dataHandler.getGameData(gameId);
                g.data = {...g.data, ...data};

                this.activeGames[gameId] = {
                    gameId: gameId,
                    siteId: data.site_id,
                    owner: ownerId,
                    game: proxy
                };

                return proxy;
            } else {
                const activeData = this.activeGames[gameId];
                if (ownerId !== activeData.owner) {
                    if (stealLock) {
                        activeData.owner = ownerId;
                    } else {
                        throw new LockedError('Game opened by other user', activeData.owner);
                    }
                }

                return this.activeGames[gameId].game;
            }
        },

        finalize: async function(gameId, userId) {
            if (!this.activeGames[gameId]) {
                throw new UnopenedError();
            }

            if (this.activeGames[gameId].owner !== userId) {
                throw new LockedError('Trying to finalize a locked game', this.activeGames[gameId].owner);
            }

            const game = this.activeGames[gameId].game;
            const updates = updateManager.get(gameId);
            const saved = await dataHandler.finalizeGameData(game.data, updates);

            delete this.activeGames[gameId];
            updateManager.clear(gameId);
            return true;
        },

        get: function(gameId, userId) {
            if (!this.activeGames[gameId]) {
                throw new UnopenedError();
            }

            if (this.activeGames[gameId].owner !== userId) {
                throw new LockedError(this.activeGames[gameId].owner);
            }

            return this.activeGames[gameId].game;
        },

        getReadOnly: function(gameId) {
            if (!this.activeGames[gameId]) {
                throw new UnopenedError();
            }

            return Object.freeze(this.activeGames[gameId].game.data);
        }
    };
};

// proxies calls to game.data so its easier to work with
const gameDataHandler = {
    get: function (target, prop, receiver) {
        if (target.data.hasOwnProperty(prop)) {
            return target.data[prop];
        } else {
            return Reflect.get(...arguments);
        }
    },
    set: function (target, prop, val, receiver) {
        if (target.data.hasOwnProperty(prop)) {
            target.data[prop] = val;
        } else {
            return Reflect.set(...arguments);
        }
    }
};

function Game(game_id, emitter) {
    this.game_id = this.data.game_id = game_id;
    this.loaded = false;
    this.emitter = emitter;
}

Game.prototype = {
    /** @type GameData */
    data: {
        game_id: 0,
        season_id: 0,
        site_id: 0,
        version: '1.1',
        us: 'Hudsonville', // TODO -- not hardcoded at some point
        opponent: null,
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
    },

    emit: function (method, ...args) {
        this.emitter.emit(Object.freeze(this.data), method, args);
    },

	/**
	 * Adds a goal to the boxscore data
	 *
	 * @param {boolean} us - was it us that scored?
	 * @param {string} player - nameKey for the player
	 */
	addToBoxScore: function (us, player) {
        const idx = us ? 0 : 1;
        let data = this.data;

        if (!data.boxscore[idx][data.quarters_played].hasOwnProperty(player)) {
            data.boxscore[idx][data.quarters_played][player] = 0;
        }

        data.boxscore[idx][data.quarters_played][player]++;
    },

	/**
	 * The end of the game
	 */
	final: function () {
        this.emit('final', ...arguments);
    },

	/**
	 *
	 * @param {string} player - nameKey of the player who took the shot
	 * @param {boolean} made - did the shot go in?
	 * @param {string|null} assisted_by - nameKey of the player who got the assist, or falsey for no assist
	 */
	shot: function (player, made, assisted_by) {
		const data = this.data;

        data.stats[player].shots++;
        if (made) {
            data.stats[player].goals++;
            if (assisted_by) {
                data.stats[assisted_by].assists++;
            }
            data.score[0]++;

            this.addToBoxScore(true, player);

            if (data.kickouts[1].length > 0) {
                data.advantage_conversion[0].converted++;
                data.stats[player].advantage_goals++;
            }
        }

        this.emit('shot', ...arguments);

        if (made) {
            this.resetKickouts();
        }
    },

	/**
	 *
	 * @param {string} player - name key of the player who got the steal
	 */
	steal: function (player) {
        this.data.stats[player].steals++;
        this.emit('steal', ...arguments);
    },

	/**
	 *
	 * @param {string} player - name key of the player who turned the ball over
	 */
	turnover: function (player) {
        this.data.stats[player].turnovers++;
        this.emit('turnover', ...arguments);
    },

	/**
	 * field block (not goalie, see save)
	 * @param {string} player - name key of the player who got a field block
	 */
	block: function (player) {
        this.data.stats[player].blocks++;
        this.emit('block', ...arguments);
    },

	/**
	 * Player was kicked out
	 * @param {string} player - name key
	 */
	kickout: function (player) {
        this.data.stats[player].kickouts++;
        this.data.kickouts[0].push(player);

        this.data.advantage_conversion[1].drawn++;
        this.emit('kickout', ...arguments);
    },

	/**
	 * Player drew a kick out
	 * @param {string} player - name key
	 */
	kickoutDrawn: function (player) {
        this.data.stats[player].kickouts_drawn++;
        this.data.kickouts[1].push(1);
        this.data.kickouts_drawn_by.push(player);

        this.data.advantage_conversion[0].drawn++;
        this.emit('kickoutDrawn', ...arguments);
    },

	/**
	 * Kickout ended
	 * @param {string|false} player - name key if its one of our players returning, false if its opposing
	 */
	kickoutOver: function (player) {
        if (player === false) {
            this.data.kickouts[1].shift();
            this.data.kickouts_drawn_by.shift();
        } else {
            var i = this.data.kickouts[0].indexOf(player);
            this.data.kickouts[0].splice(i, 1);
        }
        this.emit('kickoutOver', ...arguments);
    },

	/**
	 * Resets all of the kickouts
	 */
	resetKickouts: function () {
        this.data.kickouts = [[], []];
        this.data.kickouts_drawn_by = [];
    },

	/**
	 * Goalie save
	 */
	save: function () {
        this.data.stats[this.data.goalie].saves++;
        this.emit('save', ...arguments);
    },

	/**
	 * Goal allowed (other team scored)
	 * @param {string} number - who scored it from the other team
	 */
	goalAllowed: function (number) {
		const data = this.data;

        data.stats[data.goalie].goals_allowed++;
        data.score[1]++;

        this.addToBoxScore(false, number);

        if (data.kickouts[0].length > 0) {
            data.advantage_conversion[1].converted++;
            data.stats[data.goalie].advantage_goals_allowed++;
        }

        this.emit('goalAllowed', ...arguments);

        this.resetKickouts();
    },

	/**
	 * Sprint was taken
	 * @param {string} player - name key of who took the sprint
	 * @param {boolean} won - did player win?
	 */
	sprint: function (player, won) {
        this.data.stats[player].sprints_taken++;
        if (won) {
            this.data.stats[player].sprints_won++;
        }
        this.emit('sprint', ...arguments);
    },

	/**
	 * Our player drew a 5 meter call
	 * @param {string} drawn_by - name key of the player who drew the call
	 * @param {string} taken_by - name key of the player that took the shot
	 * @param {boolean|'made'|'blocked'|'missed'} made - true or 'made' if they made it; false, 'blocked', or 'missed' if they didn't
	 */
	fiveMeterDrawn: function (drawn_by, taken_by, made) {
		const data = this.data;

        data.stats[drawn_by].five_meters_drawn++;
        data.stats[taken_by].five_meters_taken++;
        data.stats[taken_by].shots++;
        if (made === true || made === 'made') {
            data.stats[taken_by].five_meters_made++;
            data.stats[taken_by].goals++;
            data.score[0]++;

            this.addToBoxScore(true, taken_by);
        }
        this.emit('fiveMeterDrawn', ...arguments);
    },

	/**
	 * Our player was called for a 5 meter
	 * @param {string} called_on - name key of the player that it was called on
	 * @param {string} taken_by - opponent's player that took the shot
	 * @param {boolean|'made'|'blocked'|'missed'} made - true or 'made' if they scored; false or 'blocked' if the goalie stopped it, 'missed' if they missed
	 */
	fiveMeterCalled: function (called_on, taken_by, made) {
		const data = this.data;

        data.stats[called_on].five_meters_called++;
        data.stats[called_on].kickouts++;
        data.stats[data.goalie].five_meters_taken_on++;

        switch (made) {
            case true:
            case 'made':
                data.score[1]++;
                data.stats[data.goalie].goals_allowed++;
                data.stats[data.goalie].five_meters_allowed++;

                this.addToBoxScore(false, taken_by);

                break;

            case false:
            case 'blocked':
                data.stats[data.goalie].five_meters_blocked++;
                data.stats[data.goalie].saves++;
                break;

            case 'missed':
                // don't actually need to do anything stat wise
                break;
        }

        this.emit('fiveMeterCalled', ...arguments);
    },

	/**
	 * We took a shoot out shot on them
	 * @param {string} taken_by - name key of our player that took the shot
	 * @param {boolean|'made'|'missed'|'block'} made - true or 'made' if they made it; false, 'blocked', or 'missed' if they didn't
	 */
	shootOutUs: function (taken_by, made) {
		const data = this.data;

        data.stats[taken_by].shots++;
        data.stats[taken_by].shoot_out_taken++;
        switch (made) {
            case true:
            case 'made':
                data.score[0]++;
                data.stats[taken_by].goals++;
                data.stats[taken_by].shoot_out_made++;
                this.addToBoxScore(true, taken_by);
                break;

            case false:
            case 'blocked':
            case 'missed':
                break;
        }

        this.emit('shootOutUs', ...arguments);
    },

	/**
	 * They took a shoot out shot on us
	 * @param {string} taken_by - who shot from the other team
	 * @param {boolean|'made'|'missed'|'blocked'} made - true or 'made' if they scored; false or 'blocked' if our goalie stopped it, 'missed' if they missed
	 */
	shootOutThem: function (taken_by, made) {
		const data = this.data;

        data.stats[data.goalie].shoot_out_taken_on++;
        switch (made) {
            case true:
            case 'made':
                data.score[1]++;
                data.stats[data.goalie].goals_allowed++;
                data.stats[data.goalie].shoot_out_allowed++;
                this.addToBoxScore(false, taken_by);
                break;

            case false:
            case 'blocked':
                data.stats[data.goalie].shoot_out_blocked++;
                data.stats[data.goalie].saves++;
                break;

            case 'missed':
                break;
        }

        this.emit('shootOutThem', ...arguments);
    },

	/**
	 * New goalie started
	 * @param {string} new_goalie - name key
	 */
	changeGoalie: function (new_goalie) {
        this.data.goalie = new_goalie;
        this.emit('changeGoalie', ...arguments);
    },

	/**
	 * Sets the status of the game, used for the menus
	 * @param {'start'|'quarter'|'final'|'shootout'} new_status
	 */
	setStatus: function (new_status) {
        this.data.status = new_status;
        this.emit('setStatus', ...arguments);
    },

    /**
     * Sets how many quarters we have played
     * @param {int} quarters - how many quarters have been played, past tense so its basically 0 indexed
     */
    setQuartersPlayed: function (quarters) {
        this.data.quarters_played = quarters;

        // since we're adding the ability to go straight to shoot out
        // but other things require shootout to be quarter 6
        // we have to make sure we have the in between box scores
        while (this.data.boxscore[0].length < quarters + 1) {
            this.data.boxscore[0].push({});
            this.data.boxscore[1].push({});
        }

        this.emit('setQuartersPlayed', ...arguments);
    },

    /**
     *
     * No data functionality with the remaining methods
     *
     */

    /**
     * Timeout was called
     * @param {boolean} us - did we call it?
     * @param {object} time - what time was it called
     * @param {int} time.minutes - the numbers of minutes left in the current quarter
     * @param {int} time.seconds - the numbers of seconds left in the current quarter
     */
    timeout: function (us, time) {
        const team = us ? this.data.us : this.data.opponent;
        this.emit('timeout', team, time);
    },

    /**
     * Someone was carded
     * @param {string} recipient - who received the card
     * @param {string} color - the color of the card (generally red or yellow but could technically be anything)
     */
    carded: function (recipient, color) {
        this.emit('carded', ...arguments);
    },

    /**
     * Just for broadcasting a message that is in the official record
     * @param {string} msg - dont get too wordy
     */
    shout: function (msg) {
        this.emit('shout', ...arguments);
    },

    /**
     * On the fly updating of what players are at the game
     * @param {array<PlayerStats>} add - array of subset of PlayerStat data (minimum of name_key)  of players to add to the game
     * @param {array<PlayerStats>} remove - array of subset of PlayerStat data (minimum of name_key) of players to remove from the game
     */
    updatePlayers(add, remove) {

        /**
         * get the keys of the first object in this.data.stats
         * @type {string[]}
         */
        const statKeys = Object.keys(
            this.data.stats[
                Object.keys(this.data.stats)[0]
            ]
        );


        add.forEach((player) => {
            this.data.stats[player.name_key] = statKeys.reduce((acc, statKey) => {
                acc[statKey] = player[statKey] || 0;
                return acc;
            }, {});
        });

        // this will orphan some total stats (ie goals)
        remove.forEach(player => {
            delete this.data.stats[player.name_key];
        });
    }
};