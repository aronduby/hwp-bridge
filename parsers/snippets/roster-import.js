/**
 *  Import copy/pasted CSV roster data
 *  Make sure you have headers as the first row, and they match below
 *
 *  page: {site}.shutterfly.com/roster and then click the add players button
 *  requires: loader
 *
 *  headers: Number,First Name,Last Name,Grade,Email,Parent 1,Parent 1 Email,Parent 2,Parent 2 Email
 *
 */

// replace this content with the copy/pasted csv content
const CSV = `Number,First Name,Last Name,Grade,Email,Parent 1,Parent 1 Email,Parent 2,Parent 2 Email
`;

// csv => dom
const fieldMap = new Map([
	["First Name", "firstName"],
	["Last Name", "lastName"],
	["Parent 1", "parent1.name"],
	["Parent 1 Email", "parent1.email"],
	["Parent 2", "parent2.name"],
	["Parent 2 Email", "parent2.email"]
]);

load.js('https://cdnjs.cloudflare.com/ajax/libs/PapaParse/4.6.0/papaparse.min.js')
	.then(() => {
		const result = Papa.parse(CSV, {header: true, trimHeaders: true});

		// drop out if errors
		if (result.errors.length) {
			console.error(result.errors);
			return fase;
		}

		// make sure we have enough rows for data
		// page starts with 4
		const dataLen = result.data.length;
		if (dataLen > 4) {
			Shr.RosterBatchAdd._0.onAddMorePlayers(dataLen - 4);
		}

		// now lets loop
		const inputRows = document.querySelectorAll('.ba-row');
		result.data.forEach((player, idx) => {
			const row = inputRows[idx];

			fieldMap.forEach((domName, csvProp) => {
				const input = row.querySelector(`[name="${domName}"]`);
				input.value = player[csvProp];
			});
		});
	});