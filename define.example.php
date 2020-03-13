<?php
define('DB_TYPE','mysql');
define('DB_SERVER','localhost');
define('DB_USER','hwp');
define('DB_PASSWD','PASSWD');
define('DB_NAME','hwp');

define('SERVER_ROLE', 'DEV');
define('DEBUG', true);

define('ROOT_PATH', 'PATH/');
define('MASTER_PATH', 'PATH/TO/hwp');
define('BRIDGE_PATH', 'PATH/TO/hwp-bridge');
define('PUBLIC_PATH', 'PATH/TO/hwp/public');
define('PHOTO_PATH', 'PATH/TO/hwp-photos');
define('ARTISAN_PATH', 'PATH/TO/hwp/artisan');
define('TEMPLATE_PATH', BRIDGE_PATH.'/templates/');
define('TOKEN_PATH', BRIDGE_PATH.'/parsers/tmp/token');
define('JSON_PATH', BRIDGE_PATH.'/parsers/tmp/tags.json');
define('SITE_DEFINES_PATH', 'PATH/TO/hwp-bridge/site-defines');

define('MANDRILL_API_KEY', 'XXXXX');

define('TWITTER_CONSUMER_KEY', 'XXXXX');
define('TWITTER_CONSUMER_SECRET', 'XXXXX');

define('AUTH_CLIENT_ID', 'XXXXX');
define('AUTH_CLIENT_SECRET', 'XXXXX');
define('AUTH_PUBLIC_KEY_PATH', 'XXXXX');
define('AUTH_PRIVATE_KEY_PATH', 'XXXXX');

/**
 *
 *  The following fields should be defined in a domain specific file - define.{domain}.php
 *
define('BASE_HREF', 'https://admin.hudsonvillewaterpolo.local');
define('PUBLIC_HREF', 'https://www.hudsonvillewaterpolo.local');
define('PHOTO_BASE_HREF', 'https://photos.hudsonvillewaterpolo.local');
define('THUMB_BASE_HREF', 'https://photos.hudsonvillewaterpolo.local/thumbs');

define('TWITTER_TOKEN', 'XXXXX');
define('TWITTER_TOKEN_SECRET', 'XXXXX');

define('AUTH_ENDPOINT', 'XXXXX');
 *
 */