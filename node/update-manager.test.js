const UpdateManager = require('./update-manager');

let updateManager;

beforeEach(() => {
    updateManager = new UpdateManager();
});

it('creates a new queue based on the game id', () => {
    updateManager.add({game_id: 1, body: 'Hello'});
    updateManager.add({game_id: 2, body: 'World'});

    expect([...updateManager._updateMap.keys()].length).toBe(2);
    expect(updateManager._updateMap.get(1).length).toBe(1);
    expect(updateManager._updateMap.get(2).length).toBe(1);
});


it('add new updates to existing queues', () => {
    updateManager.add({game_id: 1, body: 'Hello'});
    updateManager.add({game_id: 1, body: 'World'});

    expect(updateManager._updateMap.get(1).length).toBe(2);
});

it('gets an array of existing updates', () => {
    updateManager.add({game_id: 1, body: 'Hello'});
    updateManager.add({game_id: 1, body: 'World'});

    const updates = updateManager.get(1);
    const str = updates.map((u) => u.body).join(' ');
    expect(updates.length).toBe(2);
    expect(str).toBe('Hello World');
});

it('gets an empty array if the queue doesnt exist', () => {
    expect(updateManager.get(123456)).toEqual([]);
});

it('clears queues', () => {
    updateManager.add({game_id: 1, body: 'Hello'});
    updateManager.add({game_id: 1, body: 'World'});

    updateManager.clear(1);
    expect(updateManager.get(1)).toEqual([]);
});

it('does forEach', () => {
    const a = {game_id: 1, body: 'Hello'};
    const b = {game_id: 1, body: 'World'};
    const c = {game_id: 2, body: 'Other game'};
    const d = {game_id: 3, body: 'And one more'};

    updateManager.add(a);
    updateManager.add(b);
    updateManager.add(c);
    updateManager.add(d);

    const mocked = jest.fn();

    updateManager.forEach(mocked);

    expect(mocked).toBeCalledTimes(3);
    expect(mocked.mock.calls).toEqual([
        [[a, b], 1, updateManager._updateMap],
        [[c], 2, updateManager._updateMap],
        [[d], 3, updateManager._updateMap]
    ]);
});