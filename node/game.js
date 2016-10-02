exports.Game = Game;

function Game(game_id){
	this.game_id = game_id;
	this.loaded = false;
	this._listeners = {};
}
Game.prototype = {
	game_id: 0,
	version: '1.1',
	opponent: null,
	title: null,
	team: null,
	status: null,
	quarters_played: 0,
	stats: {},
	goalie: null,
	advantage_conversion:[
		{ drawn:0, converted: 0 },
		{ drawn:0, converted: 0 }
	],
	kickouts: [[],[]],
	boxscore:[[{}],[{}]],
	score: [0,0],

	_takeData: function(data){
		// loop through and set the data
		for(var i in data){
			if(i in this){
				if(i[0] != '_')
					this[i] = data[i];
			}
		}

		if(this.title == '' && this.opponent != null)
			this.title = 'Game against '+this.opponent;

		this.loaded = true;
	},

	_push: function(evt, args){
		// console.info(func, args);
		if(evt in this._listeners){
			for(var i in this._listeners[evt]){
				this._listeners[evt][i].apply(this, Array.prototype.slice.call(args) );
			}
		}
	},

	_addListener: function(evt, func){
		if(evt in this._listeners == false){
			this._listeners[evt] = [];
		}
		this._listeners[evt].push(func);
	},

	addToBoxScore: function(us, player){
		var idx = us ? 0 : 1;

		if(this.boxscore[idx][this.quarters_played][player] == undefined){
			this.boxscore[idx][this.quarters_played][player] =  0;
		}
		this.boxscore[idx][this.quarters_played][player]++;
	},

	final: function(){
		this._push('final', arguments);
	},

	shot: function(player, made, assisted_by){
		this.stats[player].shots++;
		if(made){
			this.stats[player].goals++;
			if(assisted_by){
				this.stats[assisted_by].assists++;
			}
			this.score[0]++;

			this.addToBoxScore(true, player);

			if(this.kickouts[1].length > 0){
				this.advantage_conversion[0].converted++;
			}
		}

		this._push('shot', arguments);

		if(made){
			this.kickouts = [[],[]];
		}
	},

	steal: function(player){
		this.stats[player].steals++;
		this._push('steal', arguments);
	},

	turnover: function(player){
		this.stats[player].turn_overs++;	
		this._push('turnover', arguments);
	},

	block: function(player){
		this.stats[player].blocks++;
		this._push('block', arguments);
	},

	kickout: function(player){
		this.stats[player].kickouts++;
		this.kickouts[0].push(player);
		
		this.advantage_conversion[1].drawn++;
		this._push('kickout', arguments);
	},

	kickoutDrawn: function(player){
		this.stats[player].kickouts_drawn++;
		this.kickouts[1].push(1);
		
		this.advantage_conversion[0].drawn++;
		this._push('kickoutDrawn', arguments);
	},

	kickoutOver: function(player){
		if(player === false){
			this.kickouts[1].pop();
		} else {
			var i = this.kickouts[0].indexOf(player);
			this.kickouts[0].splice(i, 1);
		}
		this._push('kickoutOver', arguments);
	},

	save: function(){
		this.stats[this.goalie].saves++;
		this._push('save', arguments);
	},

	goalAllowed: function(number){
		this.stats[this.goalie].goals_allowed++;
		this.score[1]++;

		this.addToBoxScore(false, number);

		if(this.kickouts[0].length > 0){
			this.advantage_conversion[1].converted++;
		}

		this._push('goalAllowed', arguments);
	},

	sprint: function(player, won){
		this.stats[player].sprints_taken++;
		if(won){
			this.stats[player].sprints_won++;
		}
		this._push('sprint', arguments);
	},

	fiveMeterDrawn: function(drawn_by, taken_by, made){
		this.stats[drawn_by].five_meters_drawn++;
		this.stats[taken_by].five_meters_taken++;
		this.stats[taken_by].shots++;
		if(made === true || made === 'made'){
			this.stats[taken_by].five_meters_made++;
			this.stats[taken_by].goals++;
			this.score[0]++;

			this.addToBoxScore(true, taken_by);
		}
		this._push('fiveMeterDrawn', arguments);
	},

	fiveMeterCalled: function(player, taken_by, made){
		this.stats[player].five_meters_called++;
		this.stats[this.goalie].five_meters_taken_on++;

		switch(made){
			case true:
			case 'made':
				this.score[1]++;
				this.stats[this.goalie].goals_allowed++;
				this.stats[this.goalie].five_meters_allowed++;

				this.addToBoxScore(false, taken_by);

				break;

			case false:
			case 'blocked':
				this.stats[this.goalie].five_meters_blocked++;
				this.stats[this.goalie].saves++;	
				break;

			case 'missed':
				// don't actually need to do anything stat wise
				break;
		}
		this._push('fiveMeterCalled', arguments);
	},

	shootOutUs: function(taken_by, made){
		this.stats[taken_by].shots++;
		this.stats[taken_by].shoot_out_taken++;
		switch(made){
			case true:
			case 'made':
				this.score[0]++;
				this.stats[taken_by].goals++;
				this.stats[taken_by].shoot_out_made++;
				this.addToBoxScore(true, taken_by);
				break;

			case false:
			case 'blocked':
			case 'missed':
				break;
		}

		this._push('shootOutUs', arguments);
	},

	shootOutThem: function(taken_by, made){
		this.stats[this.goalie].shoot_out_taken_on++;
		switch(made){
			case true:
			case 'made':
				this.score[1]++;
				this.stats[this.goalie].goals_allowed++;
				this.stats[this.goalie].shoot_out_allowed++;
				this.addToBoxScore(false, taken_by);
				break;

			case false:
			case 'blocked':
				this.stats[this.goalie].shoot_out_blocked++;
				this.stats[this.goalie].saves++;
				break;

			case 'missed':
				break;
		}

		this._push('shootOutThem', arguments);
	},

	changeGoalie: function(new_goalie){
		this.goalie = new_goalie;
		this._push('changeGoalie', arguments);
	},

	setStatus: function(new_status){
		this.status = new_status;
		this._push('setStatus', arguments);
	},

	setQuartersPlayed: function(quarters){
		this.quarters_played = quarters;

		// since we're adding the ability to go straight to shoot out
		// but other things require shootout to be quarter 6
		// we have to make sure we have the in between box scores
		while(this.boxscore[0].length < quarters + 1){
			this.boxscore[0].push({});
			this.boxscore[1].push({});
		}

		this._push('setQuartersPlayed', arguments);
	},

	/*
	 * 	aren't tracking but push the info still
	*/ 
	timeout: function(us, time){
		var team = '';
		if(us === true)
			team = 'Hudsonville';
		else
			team = this.opponent;

		this._push('timeout', [team, time]);
	},

	carded: function(recipient, color){
		this._push('carded', arguments);
	},

	shout: function(msg){
		this._push('shout', arguments);
	}




};