/*
 * This is a JavaScript Scratchpad.
 *
 * Enter some JavaScript, then Right Click or choose from the Execute Menu:
 * 1. Run to evaluate the selected text (Ctrl+R),
 * 2. Inspect to bring up an Object Inspector on the result (Ctrl+I), or,
 * 3. Display to insert the result in a comment after the selection. (Ctrl+L)
 */

// array of the string content for all the tags
var token = 'PASTE TOKEN HERE';

var tags = [];

var tagIDs = [...document.querySelectorAll('.tm-sitetags-list a')]
    .map(el => {
        var match = el.getAttribute('onclick').match(/"(\w\.\d+)"/);
        if (!match) {
            return false;
        }
        return match[1];
    })
    .filter(el => !!el);

tagIDs.reduce((chain, tag) => {
    return chain.then(() => {
       return loadTags(tag)
            .then(str => tags.push(str))
            .then(() => new Promise((resolve) => {
                setTimeout(resolve, 500)
            }));
    })    
}, Promise.resolve())
.then(() => {
    // post to the server
    var data = new FormData();
    data.append('token', token);

    tags.forEach(tagStr => {
        data.append('tags[]', tagStr);
    });

    fetch('http://admin.hudsonvillewaterpolo.com/shutterfly-post-tags.php', {
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


function loadTags(tag) {
    var fd = new FormData();

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
    
    return fetch(`https://cmd.shutterfly.com/commands/tagsmanagement/gettags?site=eagleswaterpolo2018&`, {
        method: 'POST',
        body: fd,
        credentials: 'include'
    })
       .then(rsp => rsp.text());   
}