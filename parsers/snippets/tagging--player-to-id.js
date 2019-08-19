/**
 *  Logs a table of the player name and shutterfly tag id
 *
 *  Must be on the tags page, home > tags ()
 *  Home > {player} was tagged in these {count} photos link
 *
 *  Just a utility so you can split screen and enter tags easily
 */

const map = [...document.querySelectorAll('.tm-sitetags-list a')]
	.map(el => {
		const match = el.getAttribute('onclick').match(/"(\w\.\d+)"/);
		if (!match) {
			return false;
		}

		const name = [...el.childNodes].find(node => node.nodeType === node.TEXT_NODE).textContent.trim();
		const tag = match[1];
		return [name, tag];
	})
	.filter(el => !!el);

console.table(map);