/**
 *  Assign all of the people that are taggable to the proper team and senior status.
 *  Output logs 3 strings, which are the js arrays of teams, which should be pasted below and in `tagging`.
 *
 *  Run this after the roster has been imported and you have the tagging interface open
 *
 */

// output from this scripts goes here so it can be pre-filled and edited as necessary
const jv = [];
const v = [];
const seniors = [];

const rows = [...document.querySelectorAll('#tagging-list li')]
	.map(el => ({
		name: el.textContent,
		index: el.getAttribute('index')
	}));

const table = document.createElement('table');
table.setAttribute('id', 'hwp-player-table');
table.innerHTML = `
    <tfoot>
        <tr><td colspan="3"><button id="hwp-submit">output</button></td></tr>
    </tfoot>
`;

let tableRows = rows.map(el => {
	let isJV = jv.includes(el.name);
	let isV = v.includes(el.name);
	let isS = seniors.includes(el.name);

	return `
    <tr>
        <th>${el.name}</th>
        <td>
            <select name="hwp-team" data-index="${el.index}" data-name="${el.name}">
                <option></option>
                <option value="jv" ${isJV ? 'selected' : ''}>JV</option>
                <option value="v" ${isV ? 'selected' : ''}>V</option>
            </select>
        </td>
        <td>
            <label>
                <input type="checkbox" name="hwp-senior" data-index="${el.index}" data-name="${el.name}" ${isS ? 'checked' : ''} />
                Senior
            </label>
        </td>
    </tr>`
});
chunkArray(tableRows, Math.ceil(tableRows.length / 4)).forEach(chunk => {
	table.innerHTML += `<tbody>${chunk.join('')}</tbody>`;
});

document.body.appendChild(table);

const ss = document.styleSheets[0];

ss.insertRule(`
#hwp-player-table {
    position: absolute;
    top: 20px;
    left: 20px;
    background: white;
    z-index: 9999;
}`);

ss.insertRule(`
#hwp-player-table td,
#hwp-player-table th {
    padding: .5em
}`);

ss.insertRule(`
#hwp-player-table tr:nth-child(even) {
    background: rgba(0,0,0,.05);
}`);

ss.insertRule(`
#hwp-player-table {
    display: flex;
}`);

ss.insertRule(`
#hwp-player-table tbody {
 border-left: 2px solid rgba(0,0,0,.2);   
}`);



document.getElementById('hwp-submit').addEventListener('click', () => {
	// TEAMS
	const teams = [...document.querySelectorAll('select[name="hwp-team"]')]
		.reduce((acc, sel) => {
			if (sel.value) {
				// acc[sel.value].push(parseInt(sel.dataset.index, 10))
				acc[sel.value].push(sel.dataset.name);
			}

			return acc;
		}, {'jv': [], 'v': []});

	console.log(`const jv = ['${teams.jv.join("', '")}'];`);
	console.log(`const v = ['${teams.v.join("', '")}'];`);

	// SENIORS
	const seniors = [...document.querySelectorAll('input[name="hwp-senior"]')]
		.filter(el => el.checked)
		.map(el => el.dataset.name);

	console.log(`const seniors = ['${seniors.join("', '")}'];`);
});

/**
 * Returns an array with arrays of the given size.
 *
 * @param myArray {Array} array to split
 * @param chunk_size {int} Size of every group
 */
function chunkArray(myArray, chunk_size){
	const arrayLength = myArray.length;
	const tempArray = [];

	for (let index = 0; index < arrayLength; index += chunk_size) {
		const myChunk = myArray.slice(index, index+chunk_size);
		// Do something if you want with the group
		tempArray.push(myChunk);
	}

	return tempArray;
}