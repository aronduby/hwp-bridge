/**
 *	Starts off the import functionality.
 *	This sends a bunch of serial requests (with a delay) for the json data for all of the tagged images for a player tag.
 *	It sends all of that information over to the HWP server which writes it to the server for use in other parser
 *	scripts, ie. `shutterfly-tagged.php`
 *
 *  Must be on the tags page, home > tags ()
 *  Home > {player} was tagged in these {count} photos link
 *
 *  Requirements:
 *  	generate a server token from the bridge server `parsers/shutterfly-token.php`
 *
 */

// paste token from bridge cli parsers/shutterfly-token.php
const token = 'PASTE_TOKEN_HERE';

// grab the shtuterfly site name as the subdomain
const shutterflySite = window.location.host.split('.')[0];

// array of the string content for all the tags
const tags = [];

const tagIDs = [...document.querySelectorAll('.tm-sitetags-list a')]
	.map(el => {
		const match = el.getAttribute('onclick').match(/"(\w\.\d+)"/);
		if (!match) {
			return false;
		}
		return match[1];
	})
	.filter(el => !!el);

tagIDs
	.reduce((chain, tag) => {
		// this is basically a sneaky way to do serial ajax calls with sleeps in between
		// pretty sneaky past me, well done
		return chain.then(() => {
			return loadTags(tag)
				.then(str => tags.push(str))
				.then(() => new Promise((resolve) => {
					setTimeout(resolve, 500)
				}));
		})
	}, Promise.resolve())
	.then(() => {
		// post all of the tags to hwp bridge
		const data = new FormData();
		data.append('token', token);

		tags.forEach(tagStr => {
			data.append('tags[]', tagStr);
		});

		fetch('https://admin.hudsonvillewaterpolo.com/shutterfly-post-tags.php', {
			method: 'POST',
			body: data
		})
			.then(rsp => {
				console.log(rsp);
			})
			.catch(err => {
				console.error(err);
			});
	});

// loads all of the tagged image data for a given tag
function loadTags(tag) {
	const fd = new FormData();

	fd.append('tag', tag);
	fd.append('cache', '[object Object]');
	fd.append('startIndex', '0');
	fd.append('size', '-1');
	fd.append('pageSize', '-1');
	fd.append('page', 'eagleswaterpolo2018/_/tags');
	fd.append('nodeId', '3');
	fd.append('layout', 'ManagementTags');
	fd.append('version', '0');
	fd.append('format', 'js');
	fd.append('t', Shr.AR.t);
	fd.append('h', Shr.AR.h);

	return fetch(`https://cmd.shutterfly.com/commands/tagsmanagement/gettags?site=${shutterflySite}&`, {
		method: 'POST',
		body: fd,
		credentials: 'include'
	})
		.then(rsp => rsp.text());
}