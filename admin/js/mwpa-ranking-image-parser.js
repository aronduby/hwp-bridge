class MWPARankingImageParser {

    constructor() {
        this.startY = 232;
        this.sliceHeight = 85;
        this.slicePadding = 30;
        this.sliceCount = 10;

        this.teamNameHacks = new Map();
        this.teamNameHacks.set('Ann Arbor Pioneera', 'Ann Arbor Pioneer');
        this.teamNameHacks.set('Ann Arbor Pioneer M', 'Ann Arbor Pioneer');
        this.teamNameHacks.set('East Grand Rapids E', 'East Grand Rapids');
        this.teamNameHacks.set('East Grand Rapids ]e', 'East Grand Rapids');

        // needs to match the regex we use in the parser
        this.pointHacks = {
            'P': '',
            'I': 1,
            'D': 0
        };

        this.baseCanvas = document.createElement('canvas');
        this.sliceCanvas = document.createElement('canvas');
        this.sliceCanvas.height = this.sliceHeight + this.slicePadding * 2;
    }

    parse(url) {
        if (!url) {
            url = prompt('Enter the URL to the ranking image');
            if (!url) {
                return false;
            }
        }

        return this.loadBase(url)
            .then(() => this.parseSlices())
            .catch((err) => {
                console.error(err);
                alert(`Sorry, something happened and we couldn't parse that image`);
            });
    }

    parseSlices() {
        const tasks = [];
        for (let i = 0; i < this.sliceCount; i++) {
            tasks.push(() => this.generateSlice(i));
        }

        return tasks.reduce((promiseChain, currentTask) => {
            return promiseChain.then(chainResults =>
                currentTask().then(currentResult =>
                    [ ...chainResults, currentResult ]
                )
            );
        }, Promise.resolve([]))
    }

    loadBase(url) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.addEventListener('load', () => {
                this.baseCanvas.width = img.width;
                this.baseCanvas.height = img.height;
                this.baseCanvas.getContext('2d').drawImage(img, 0, 0);

                this.sliceCanvas.width = img.width;
                resolve();
            });
            img.addEventListener('error', (e) => reject(e));
            img.src = url;
        })
    }

    generateSlice(sliceIdx) {
        const sliceCtx = this.sliceCanvas.getContext('2d');
        const y = this.startY + (this.sliceHeight * sliceIdx);

        sliceCtx.fillStyle = "black";
        sliceCtx.fillRect(0, 0, this.sliceCanvas.width, this.sliceCanvas.height);

        sliceCtx.drawImage(this.baseCanvas,
            0, y, this.baseCanvas.width, this.sliceHeight,
            0, this.slicePadding, this.baseCanvas.width, this.sliceHeight
        );

        const data = this.sliceCanvas.toDataURL();
        return this.parseImageData((fd) => {
            fd.append("base64Image", data);
        })
            .then(result => this.mapParsedResults(result, sliceIdx));
    }

    parseImageData(imageWriter) {
        const headers = new Headers();
        headers.append("apikey", '7118b3ef2488957');

        const fd = new FormData();
        fd.append("language", "eng");
        fd.append("isOverlayRequired", "true");
        fd.append("filetype", "png");
        fd.append("OCREngine", "2");
        fd.append("isTable", "true");
        fd.append("scale", "true");
        imageWriter(fd);

        const requestOptions = {
            method: 'POST',
            headers: headers,
            body: fd,
            redirect: 'follow'
        };

        return fetch("https://api.ocr.space/parse/image", requestOptions)
            .then(response => response.json())
            .catch(error => console.error(error));
    }

    mapParsedResults(result, idx) {
        console.log('MAPPING', result, idx);
        const rankFallback = ++idx;

        const lines = result.ParsedResults?.[0].TextOverlay.Lines.slice();

        if (lines.length === 0) {
            return {
                rank: rankFallback,
                team: null,
                points: null
            };

        } else if (lines.length <= 2) {
            // if there's <= 2, only use the first one as team name
            // attempt a regex for the parts, use idx for ranking fallback

            const str = lines[0].LineText;
            const regex = /^(?<rank>\d{1,2}) (?<team>[\w ]+) (?<points>\d{2,3})/;
            const matches = str.match(regex);

            if (matches) {
                return {
                    rank: parseInt(matches.groups.rank, 10),
                    team: this.formatTeamName(matches.groups.team),
                    points: this.formatPoints(matches.groups.points)
                };
            } else {
                return {
                    rank: rankFallback,
                    team: this.formatTeamName(str),
                    points: null
                };
            }

        } else {
            // if there's more than 2...
            //  rank - regex \d{1,2} or fallback to index
            //  points - regex \d{3,4} and then get just the numbers for points
            //  team - of all the lines that are left, use the first one

            // find rank
            const rankRegex = /^(?<tie>T-)?(?<rank>\d{1,2})$/;
            let rank = rankFallback;
            const rankIdx = lines.findIndex(line => rankRegex.test(line.LineText));
            if (rankIdx >= 0) {
                const matches = lines[rankIdx].LineText.match(rankRegex);
                rank = parseInt(matches.groups.rank, 10);
                // remove it from the lines
                lines.splice(rankIdx, 1);
            }

            // find the points
            // this one tends to have additional noise after the points
            const pointsRegex = /^(\d{2,3})/;
            let points = null;
            const pointsIdx = lines.findIndex(line => pointsRegex.test(line.LineText));
            if (pointsIdx >= 0) {
                const matches = lines[pointsIdx].LineText.match(pointsRegex);
                points = this.formatPoints(matches[0]);
                lines.splice(pointsIdx, 1);
            }

            // if its still null, check for the hacks
            if (points === null) {
                const pointHackRegex = /^[\dID]{2,5}P?$/; // needs to match what we have in the hacks object
                let mightBePoints = lines.find(line => pointHackRegex.test(line.LineText));
                if (mightBePoints) {
                    mightBePoints = Object.keys(this.pointHacks).reduce((working, k) => {
                        const v = this.pointHacks[k];
                        return working.replaceAll(k, v);
                    }, mightBePoints.LineText);

                    mightBePoints = parseInt(mightBePoints, 10);
                    if (!Number.isNaN(mightBePoints)) {
                        points = mightBePoints;
                    }
                }
            }

            // now team is first of whatever is left
            const mightBeTeam = lines.filter(line => line.LineText.length > 2);
            let team = this.formatTeamName(mightBeTeam.shift().LineText);

            // if we don't have points, but do have a team, see if the points are in team
            if (points === null && team.length) {
                const pointsInTeamRegex = / (\d{2,3}) .+$/;
                const matches = team.match(pointsInTeamRegex);
                if (matches) {
                    team = this.formatTeamName(team.replace(matches[0], '').toUpperCase());
                    points = this.formatPoints(matches[1]);
                }
            }

            return { rank, points, team };
        }
    }

    formatTeamName(str) {
        return this.mapTeamName(this.titleCase(str));
    }

    formatPoints(str) {
        let int = parseInt(str, 10);
        if (!Number.isNaN(int)) {
            return int;
        } else {
            return null;
        }
    }

    titleCase(str) {
        return str.toLowerCase()
            .split(' ')
            .map((s) => s.charAt(0).toUpperCase() + s.substring(1))
            .join(' ');
    }

    mapTeamName(str) {
        return this.teamNameHacks.has(str)
            ? this.teamNameHacks.get(str)
            : str;
    }

}