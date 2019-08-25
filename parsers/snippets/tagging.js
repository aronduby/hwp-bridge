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
	const jv = ['Aiden McDowell', 'Ben Harris', 'Brendan LaFrenier', 'Bryce Laning', 'Casey Fields', 'Cooper Randall', 'Declan Edema', 'Dylan VanderJagt', 'Ethan Dennis', 'Gabe Karel', 'Gabe Karel', 'Henry Booker', 'Jack Bradley', 'Jack Roehling', 'Jacob Ellis', 'Joseph Maldonado', 'Matt Karel', 'Matthew Welmerink', 'Nathan Porter', 'Nolan Marx', 'Sam Lobbezoo', 'Tyler Chalmers', 'Will Schuiteman'];
	const v = ['Andy Lobbezoo', 'Chandler Jones', 'Colin McDowell', 'Dallas Grandy', 'Elijah Boonstra', 'Ethan Holwerda', 'Gabe Ecenbarger', 'Ian Worst', 'John Dirkse', 'Micah Bayle', 'Nate Chalmers', 'Parker Molewyk', 'Patrick Tutt', 'Sam Myaard', 'Tyler Bayle', 'Wesley Obetts'];
	const seniors = ['Andy Lobbezoo', 'Chandler Jones', 'Dallas Grandy', 'Ian Worst', 'John Dirkse', 'Joseph Maldonado', 'Parker Molewyk', 'Patrick Tutt', 'Sam Myaard', 'Wesley Obetts'];
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
				// alt + z = undo, uncheck checked people
				case 'z':
					[...taggingList.querySelectorAll('.tag-checked')]
						.forEach(el => el.click());
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