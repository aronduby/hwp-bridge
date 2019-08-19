/**
 * 	Runs a simple ajax request to make sure we can allow insecure content
 * 	This will error, but if it errors with a CORS error that's fine
 * 	ssl/insecure errors you have to click the shield and allow insecure content.
 *
 * 	Once we get the CORS error its safe to run tagging--post-tags
 *
 * 	Once hwp and bridge is on ssl this will no longer be necessary
 */
fetch('http://admin.hudsonvillewaterpolo.com/test.php')
	.then(() => console.log('good to go'))
	.catch((e) => {
		console.log(`make sure this isn't the insecure error`)
	});