/**
 *	@Deprecated
 *	Used to be used to make sure we can hit our server through insecure content, but we have SSL now so we're good.
 *	This is still useful for making sure things are setup right, so I'm bringing it back
 */
fetch('https://admin.hudsonvillewaterpolo.com/test.php', {mode: 'cors'})
	.then((rsp) => rsp.text().then(t => console.log('good to go', t)))
	.catch((e) => {
		console.error(e);
	});