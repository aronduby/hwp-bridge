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
		'sid': 'TWILIO_SID_HERE',
		'token': 'TWILIO_TOKEN_HERE',
		'from': '+TWILIO_OUTBOUND_NUMBER'
	},
	twitter: {
		consumer_key: 'TWITTER-CONSUMER-KEY',
		consumer_secret: 'TWITTER-CONSUMER-SECRET',
		access_token: 'TWITTER-ACCESS-TOKEN',
		access_token_secret: 'TWITTER-ACCESS-TOKEN-SECRET'
	},
	jwtAuth: {
		secret: 'JWT_AUTH_SECRET',
		algorithm: 'HS256'
	}
};