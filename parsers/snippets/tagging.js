/**
 *  Makes tagging photos suck a little bit less
 *  Removes the overlay blocking good view of the photo, adds keyboard shortcuts.
 *
 *  Run this after opening a photo for tagging.
 *
 *  You'll need the output from `tagging -- assign teams` for the team based options to work
 *  Copy/paste the output from that script in the area marked below
 *
 *  Shortcuts: Alt + ...
 *      n = next photo
 *      p = previous photo
 *      a = tag all people currently displayed in the dropdown
 *      j = tag all the jv players (see tagging--assign teams)
 *      v = tag all varsity players (see tagging--asign teams)
 *      s = tag all the seniors (see tagging--asign teams)
 *
 */

let inited = false;

function init() {
	// delete the background
	const bg = document.getElementById('dlg-background');
	bg && bg.remove();

	// left align
	// const doc = document.getElementById('document');
	// doc.style.marginLeft = 0;

	const input = document.querySelector('#tagging-ac input');
	const acList = document.querySelector('#tagging-ac ul');
	const taggingList = document.querySelector('#tagging-list ul');

	// BEGIN OUTPUT FROM `tagging--assign-teams`
	const jv = ['Bryce Laning', 'Cooper Randall', 'Declan Edema', 'Ethan Dennis', 'Gabe Karel', 'Henry Booker', 'Jack Roehling', 'Jacob Ellis', 'Joseph Maldonado', 'Liam Kuiper', 'Nathan Porter', 'Ryan VanderVeen', 'Will Schuiteman'];
	const v = ['Adam Simon', 'Andy Lobbezoo', 'Brandon Vansprange', 'Caden Meines', 'Chandler Jones', 'Colin McDowell', 'Collin Kline', 'Dallas Grandy', 'David Karel', 'Elijah Boonstra', 'Ethan Holwerda', 'Ethan Springer', 'Gabe Boonstra', 'Gabe Ecenbarger', 'Ian Worst', 'John Dirkse', 'Jordan B', 'Josh A', 'Matt Lawrence', 'Micah Bayle', 'Nate Chalmers', 'Nate Tuttle', 'Parker Molewyk', 'Patrick Tutt', 'Sam Myaard', 'Tyler Bayle', 'Wesley Obetts'];
	const seniors = ['Adam Simon', 'Brandon Vansprange', 'Caden Meines', 'Collin Kline', 'David Karel', 'Ethan Springer', 'Gabe Boonstra', 'Matt Lawrence', 'Nate Tuttle'];
	// END OUTPUT FROM `tagging--assign teams`


	input.addEventListener('keydown', (e) => {
		if (e.altKey) {
			switch (e.key) {
				// alt + n = next photo
				case "n":
					Shr.Dialogs.AddTags.onNextClick();
					break;
				// alt + p = previous photo
				case "p":
					Shr.Dialogs.AddTags.onPrevClick();
					break;
				// alt + a = all people in dropdown
				case "a":
					[...acList.querySelectorAll('li')]
						.filter(el => el.style.display === 'block')
						.forEach(el => {
							el.click();
						});
					break;
				// alt + j = all jv players
				case "j":
					[...taggingList.querySelectorAll('li')]
						.filter(el => jv.includes(el.textContent))
						.forEach(el => el.click());
					break;
				// alt + v = all v players
				case "v":
					[...taggingList.querySelectorAll('li')]
						.filter(el => v.includes(el.textContent))
						.forEach(el => el.click());
					break;
				// alt + s = all seniors
				case "s":
					[...taggingList.querySelectorAll('li')]
						.filter(el => seniors.includes(el.textContent))
						.forEach(el => el.click());
					break;

				// alt + z = untag everyone that is tagged
				case "z":
					[...taggingList.querySelectorAll('li.tag-checked')]
						.forEach(el => el.click());
					break;
			}
		}
	});

	inited = true;
}

document.addEventListener('click', (e) => {
	if (
		!inited
		&& e.path.includes(document.getElementById('pic-add-tag'))
	) {
		setTimeout(init, 1000);
	}
});