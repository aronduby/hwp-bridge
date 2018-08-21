<?php
require_once '../define.php';

$token = md5(uniqid(rand(), true));

if (file_put_contents(TOKEN_PATH, $token) !== false) {
    print $token . "\n";
} else {
    print 'could not write token, check permissions';
}
