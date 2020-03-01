'use strict';

/* Controllers */

angular.module('myApp.controllers', [])
  .controller('startCtrl', ['$scope', 'game', '$modal', '$location', function ($scope, game, $modal, $location) {
    game.setStatus('start');
    $scope.game = game;

    $scope.order_by = 'number_sort';

    $scope.sprintTakenBy = function (sprint_by) {
      if ($scope.game.goalie === '') {
        alert('SET GOALIE!');
        return false;
      }

      var yesNoInstance = $modal.open({
        templateUrl: 'partials/modals/yes-no.html',
        controller: YesNoModalCtrl,
        resolve: {
          title: function () {
            return 'Did ' + $scope.game.stats[sprint_by].first_name + ' win the sprint?';
          },
        }
      });

      yesNoInstance.result
        .then(function (result) {
          game.sprint(sprint_by, result);
          $location.path('/game/' + game.game_id + '/inplay');
        });
    };
  }])

  .controller('boxScoreCtrl', ['$scope', '$filter', 'game', function ($scope, $filter, game) {
    $scope.boxscore = [[], []];
    $scope.$watch('game.boxscore', function () {
      for (var quarter = 0; quarter < Math.max(4, game.quarters_played + 1); quarter++) {
        for (var team = 0; team < 2; team++) {
          var score = $filter('toArray')(game.boxscore[team][quarter], true);
          if (score.length) {
            score = $filter('sum')(score);
          } else {
            if (game.quarters_played >= quarter) {
              score = 0;
            } else {
              score = null;
            }
          }

          $scope.boxscore[team][quarter] = score;
        }
      }
    }, true);
  }])

  .controller('inPlayCtrl', ['$scope', 'game', '$modal', function ($scope, game, $modal) {
    game.status = 'inplay';
    $scope.game = game;

    $scope.order_by = 'number_sort';

    $scope.changeGoalie = function () {
      var playerListInstance = $modal.open({
        templateUrl: 'partials/modals/player-list.html',
        controller: PlayerListModalCtrl,
        resolve: {
          title: function () {
            return 'New Goalie:';
          },
          players: function () {
            return $scope.game.stats;
          },
          skip: function () {
            return $scope.game.goalie;
          },
          order_by: function () {
            return $scope.order_by;
          }
        }
      });

      playerListInstance.result
        .then(function (new_goalie) {
          game.changeGoalie(new_goalie);
        });
    };

    $scope.isKickedOut = function (player) {
      return game.kickouts[0].indexOf(player.name_key) >= 0;
    };

    // Shot/Goal
    $scope.shotBy = function (shot_by) {
      var yesNoInstance = $modal.open({
        templateUrl: 'partials/modals/yes-no.html',
        controller: YesNoModalCtrl,
        resolve: {
          title: function () {
            return 'Did ' + $scope.game.stats[shot_by].first_name + ' make the shot?';
          },
        }
      });

      yesNoInstance.result
        .then(function (result) {
          if (result == true) {
            var playerListInstance = $modal.open({
              templateUrl: 'partials/modals/player-list.html',
              controller: PlayerListModalCtrl,
              resolve: {
                title: function () {
                  return 'With the assist:';
                },
                players: function () {
                  return $scope.game.stats;
                },
                skip: function () {
                  return shot_by;
                },
                order_by: function () {
                  return $scope.order_by;
                }
              }
            });

            playerListInstance.result
              .then(function (assist_by) {
                game.shot(shot_by, true, assist_by);
              }, function () {
                game.shot(shot_by, true, false);
              });

          } else {
            game.shot(shot_by, false);
          }

        });
    };

    // Goal by the other team
    $scope.goalAllowed = function () {
      var numberInstance = $modal.open({
        templateUrl: 'partials/modals/number-input.html',
        controller: NumberInputCtrl,
        resolve: {
          title: function () {
            return 'Cap Number';
          }
        }
      });

      numberInstance.result
        .then(function (number) {
          game.goalAllowed(number);
        });
    };

    // 5 Meters
    $scope.fiveMeterCalledOn = function (called_on) {
      var numberInstance = $modal.open({
        templateUrl: 'partials/modals/number-input.html',
        controller: NumberInputCtrl,
        resolve: {
          title: function () {
            return 'Who took the shot for ' + $scope.game.opponent + '?';
          }
        }
      });

      numberInstance.result
        .then(function (taken_by) {
          var MMBInstance = $modal.open({
            templateUrl: 'partials/modals/made-missed-blocked.html',
            controller: MMBModalCtrl,
            resolve: {
              title: function () {
                return 'Did #' + taken_by + ' make the shot?';
              }
            }
          });

          MMBInstance.result
            .then(function (made) {
              game.fiveMeterCalled(called_on, taken_by, made);
            });
        });
    };

    $scope.fiveMeterDrawnBy = function (drawn_by) {
      var playerListInstance = $modal.open({
        templateUrl: 'partials/modals/player-list.html',
        controller: PlayerListModalCtrl,
        resolve: {
          title: function () {
            return 'Shot Taken By:';
          },
          players: function () {
            return $scope.game.stats;
          },
          skip: function () {
            return null
          },
          order_by: function () {
            return $scope.order_by;
          }
        }
      });

      playerListInstance.result
        .then(function (taken_by) {
          var MMBInstance = $modal.open({
            templateUrl: 'partials/modals/made-missed-blocked.html',
            controller: MMBModalCtrl,
            resolve: {
              title: function () {
                return 'Did ' + $scope.game.stats[taken_by].first_name + ' make the shot?';
              },
            }
          });

          MMBInstance.result
            .then(function (made) {
              game.fiveMeterDrawn(drawn_by, taken_by, made);
            });

        });
    };

    // Timeouts
    $scope.timeout = function (us) {
      var TimeOutInstance = $modal.open({
        templateUrl: 'partials/modals/timeout.html',
        controller: TimeOutCtrl,
        resolve: {
          title: function () {
            return (us ? 'Hudsonville' : $scope.game.opponent) + ' Timeout'
          },
        }
      });

      TimeOutInstance.result
        .then(function (data) {
          $scope.game.timeout(us, data);
        });
    }
  }])

  .controller('quarterCtrl', ['$scope', 'game', '$modal', '$location', function ($scope, game, $modal, $location) {
    game.setStatus('quarter');
    $scope.game = game;
    $scope.order_by = 'number_sort';

    $scope.sprintTakenBy = function (sprint_by) {
      var yesNoInstance = $modal.open({
        templateUrl: 'partials/modals/yes-no.html',
        controller: YesNoModalCtrl,
        resolve: {
          title: function () {
            return 'Did ' + $scope.game.stats[sprint_by].first_name + ' win the sprint?';
          },
        }
      });

      yesNoInstance.result
        .then(function (result) {
          game.sprint(sprint_by, result);
          $location.path('/game/' + game.game_id + '/inplay');
        });
    };
  }])

  .controller('finalCtrl', ['$scope', '$modal', '$location', '$window', 'game', 'history', function ($scope, $modal, $location, $window, game, history) {
    game.setStatus('final');
    $scope.game = game;

    $scope.order_by = 'number_sort';
    $scope.finalTracker;

    // Final Submit
    $scope.postFinal = function () {
      // show a loader
      var defered = game.final();
      $scope.finalTracker = defered;
      defered.then(
        function (data) {
          history.clear();
          $window.location.replace(window.location.protocol + '//' + window.location.host + '/events.php');
        }, function (error) {
          $window.alert('Error posting final');
          console.error(error);
          // hide the loader
        }
      );
    };

    $scope.sprintTakenBy = function (sprint_by) {
      var yesNoInstance = $modal.open({
        templateUrl: 'partials/modals/yes-no.html',
        controller: YesNoModalCtrl,
        resolve: {
          title: function () {
            return 'Did ' + $scope.game.stats[sprint_by].first_name + ' win the sprint?';
          },
        }
      });

      yesNoInstance.result
        .then(function (result) {
          game.sprint(sprint_by, result);
          $location.path('/game/' + game.game_id + '/inplay');
        });
    };

    // Shoot Out
    $scope.shootout = function () {
      game.setQuartersPlayed(6);
      game.setStatus('shootout'); // set now to enable the tab and push
      $location.path('/game/' + game.game_id + '/shootout');
    };
  }])

  .controller('shootOutCtrl', ['$scope', 'game', '$modal', '$location', function ($scope, game, $modal, $location) {
    // don't set the game status it's already handled
    $scope.game = game;

    $scope.order_by = 'number_sort';

    $scope.shotThem = function (result) {
      var numberInstance = $modal.open({
        templateUrl: 'partials/modals/number-input.html',
        controller: NumberInputCtrl,
        resolve: {
          title: function () {
            return 'Who took the shot for ' + $scope.game.opponent + '?';
          }
        }
      });

      numberInstance.result
        .then(function (taken_by) {
          game.shootOutThem(taken_by, result);
        });
    };

    $scope.shotUs = function (taken_by) {
      var MMBInstance = $modal.open({
        templateUrl: 'partials/modals/made-missed-blocked.html',
        controller: MMBModalCtrl,
        resolve: {
          title: function () {
            return 'Did ' + $scope.game.stats[taken_by].first_name + ' make the shot?';
          },
        }
      });

      MMBInstance.result
        .then(function (result) {
          game.shootOutUs(taken_by, result);
        });
    };
  }])

  .controller('SocketStatusCtrl', ['$scope', '$timeout', '$q', 'socket', 'fakeSocket', 'game', 'IAmController',
  function ($scope, $timeout, $q, socket, fakeSocket, game, IAmController) {

    $scope.status = false;
    $scope.msg = false;
    $scope.current_attempt = 0;

    function processQueue(socket, queue) {
      var deferred = $q.defer();

      function handleQueue(queue) {
        var item = queue.shift();

        if (item) {
          var cb = function (err) {
            if (err == null) {
              handleQueue(queue);
            } else {
              deferred.reject(err);
            }
          };

          // replace the existing callback (last item) with our new one
          item.pop();
          item.push(cb);

          socket.emit.apply(socket, item);

        } else {
          deferred.resolve();
        }
      }

      handleQueue(queue);

      return deferred.promise;
    }

    IAmController.$on('true', function () {
      console.log('IAMCONTROLLER!');

      var queued_updates = fakeSocket.getUpdates();
      if (queued_updates.length) {

        // there's so queued updates, meaning we lost connection so we have to do some sending
        // but first make sure the server has grabbed the data from the database in case it has restarted
        socket.emit('openGame', game.game_id, function (err) {
          if (err != null) {
            alert('Error getting game data');
            console.error(err);
            return false;
          }

          console.log('UPDATES', queued_updates);
          processQueue(socket, queued_updates)
            .then(function () {
              $scope.msg = 'Updates sent';

              $timeout(function () {
                $scope.status = false;
                $scope.msg = false;
              }, 5000);
            })
            .catch(function (err) {
              throw err;
            })
            .finally(function () {
              console.log('done with queue');
            });
        });
      }
    });

    socket
      .on('disconnect', function () {
        $scope.status = 'reconnecting';
        $scope.msg = 'Disconnected, attempting to reconnect';

        game.setSocket(fakeSocket);
      })
      .on('reconnect', function (attempt_number) {
        $scope.status = 'reconnected';
        $scope.msg = 'Reconnected, sending queued updates';
        $scope.attempt_number = 0;

        game.setSocket(socket);
      })
      .on('reconnecting', function (attempt_number) {
        $scope.current_attempt = attempt_number;
      })
      .on('reconnect_failed', function () {
        $scope.status = 'disconnected';
        $scope.msg = 'Reconnection failed! This data will be out of date. Please refresh the page to try again';
      });
  }])

  .controller('ShoutCtrl', ['$scope', '$modal', 'game', function ($scope, $modal, game) {

    var ShoutModalCtrl = function ($scope, $modalInstance) {
      $scope.msg = '';

      $scope.shout = function (msg) {
        $modalInstance.close(msg);
      };

      $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
      };
    };

    // Shout
    $scope.shout = function () {
      var ShoutInstance = $modal.open({
        templateUrl: 'partials/modals/shout.html',
        controller: ShoutModalCtrl
      });

      ShoutInstance.result
        .then(function (msg) {
          game.shout(msg);
        });
    };
  }])

  .controller('HistoryCtrl', ['$scope', '$modal', 'game', 'history', 'localCopy', 'socket', function ($scope, $modal, game, history, localCopy, socket) {

    var HistoryModalCtrl = function ($scope, $modalInstance, history) {
      $scope.history = history;

      $scope.revert = function (idx) {
        $modalInstance.close(idx);
      };

      $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
      };
    };

    $scope.history = history;

    $scope.undo = function () {
      var ModalInstance = $modal.open({
        templateUrl: 'partials/modals/history.html',
        controller: HistoryModalCtrl,
        history: history
      });

      ModalInstance.result
        .then(function (idx) {
          // have to double parse to undo the escaping used by storage to save everything as json
          var data = JSON.parse(JSON.parse(history.storage.saves[idx].data));
          game.takeData(data);

          // clear history back to idx
          history.storage.saves.splice(0, idx);
          localCopy.save(data);

          // send data to the server for taking
          socket.emit('undo', data, ()=>{});
        });
    }
  }])

  .controller('playersCtrl', ['$scope', 'game', 'allPlayers', function($scope, game, allPlayers) {
    $scope.game = game;

    $scope.order_by = 'number_sort';
    $scope.reverse = false;

    $scope.currentPlayers = Object.values(game.stats)
      .map((player) => ({
        first_name: player.first_name,
        last_name: player.last_name,
        name_key: player.name_key,
        number: player.number,
        number_sort: player.number_sort,
        status: 0,
        orgStatus: 0
      }))
      .reduce((acc, p) => {
        acc[p.name_key] = p;
        return acc;
      }, {});

    $scope.addablePlayers = allPlayers.reduce((acc, player) => {
      acc.push(
          ...player.team.map((team) => ({
              first_name: player.first_name,
              last_name: player.last_name,
              name_key: player.name_key,
              number: player.number,
              number_sort: player.number_sort,
              team: team,
              inCurrent: $scope.currentPlayers.hasOwnProperty(player.name_key)
          }))
      );

      return acc;
    }, []);

    $scope.delete = function(player, idx) {
      if (player.status === -1) {
        player.status = player.orgStatus;
      } else {
        player.status = -1;
      }
    };

    $scope.addPlayer = function() {
      const cloned = {
        ...$scope.playerToAdd,
        status: 1,
        orgStatus: 1
      };

      $scope.currentPlayers[cloned.name_key] = cloned;
      $scope.addablePlayers
          .find(p => p.name_key === cloned.name_key)
          .inCurrent = true;

      $scope.playerToAdd = null;
    };

    $scope.save = function() {
      const toAdd = Object.values($scope.currentPlayers)
          .filter(p => p.status === 1);

      // filters out players that were added then removed
      const toRemove = Object.values($scope.currentPlayers)
          .filter(p => p.status === -1 && p.orgStatus === 0);

      if (toAdd.length || toRemove.length) {
		  game.updatePlayers(toAdd, toRemove)
			  .then(() => {
				  // toast or something, do I have toast?
				  window.history.back();
			  });
      } else {
        window.history.back();
      }
    }

  }])
;


var YesNoModalCtrl = function ($scope, $modalInstance, title) {
  $scope.title = title;

  $scope.yes = function () {
    $modalInstance.close(true);
  };

  $scope.no = function () {
    $modalInstance.close(false);
  };

  $scope.cancel = function () {
    $modalInstance.dismiss('cancel');
  };
};

var NumberInputCtrl = function ($scope, $modalInstance, title) {
  $scope.title = title;

  $scope.submit = function (number) {
    $modalInstance.close(number);
  };

  $scope.cancel = function () {
    $modalInstance.dismiss('cancel');
  };
};

var PlayerListModalCtrl = function ($scope, $modalInstance, title, players, skip, order_by) {
  $scope.title = title;
  $scope.players = players;
  $scope.skip = skip;
  $scope.order_by = order_by;

  $scope.select = function (name_key) {
    $modalInstance.close(name_key);
  }

  $scope.cancel = function () {
    $modalInstance.dismiss('cancel');
  };
}

var MMBModalCtrl = function ($scope, $modalInstance, title) {
  $scope.title = title;

  $scope.made = function () {
    $modalInstance.close('made');
  };

  $scope.missed = function () {
    $modalInstance.close('missed');
  };

  $scope.blocked = function () {
    $modalInstance.close('blocked');
  }

  $scope.cancel = function () {
    $modalInstance.dismiss('cancel');
  };
};

var TimeOutCtrl = function ($scope, $modalInstance, title) {

  $scope.title = title;

  $scope.submit = function (minutes, seconds) {
    $modalInstance.close({
      minutes: minutes,
      seconds: seconds
    });
  }

  $scope.cancel = function () {
    $modalInstance.dismiss('cancel');
  }

};