module.exports = {
	mysql: {
		host: 'localhost',
		user: 'hwp',
		password: 'poiuy098',
		database: 'hwp',
		connectionLimit: 10
	},
	artisanPath: 'D:/web/hwp/artisan',
	ssl: {
		"key": "path/to/ssl.key",
		"cert": "path/to/ssl.crt",
		"ca": "path/to/sub.class1.server.ca.pem"
	},
	twilio: {
		'enabled': false,
		'sid': 'TWILIO_SID_HERE',
		'token': 'TWILIO_TOKEN_HERE',
		'from': '+TWILIO_OUTBOUND_NUMBER'
	},
	twitter: {
		enabled: 'false',
		consumerKey: 'TWITTER-CONSUMER-KEY',
		consumerSecret: 'TWITTER-CONSUMER-SECRET',
		accessToken: 'TWITTER-ACCESS-TOKEN',
		accessTokenSecret: 'TWITTER-ACCESS-TOKEN-SECRET'
	},
	jwtAuth: {
		publicKey: 'path/to/key/setup/on/hwp',
		algorithm: 'RS256'
	},
	siteSettingsPath: "/web/hwp/storage/sites"
};