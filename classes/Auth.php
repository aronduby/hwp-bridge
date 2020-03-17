<?php

use \Firebase\JWT\JWT;

class Auth
{

    /**
     * The key in session that houses the token
     */
    const TOKEN_KEY = 'hwp:token';

    /**
     * The key in session that houses the claims
     */
    const CLAIMS_KEY = 'claims';

    /**
     * Check if the current session is authenticated
     *
     * @return bool
     */
    public static function authenticated() : bool {
        return array_key_exists(self::TOKEN_KEY, $_SESSION) && !empty($_SESSION[self::TOKEN_KEY]);
    }

    /**
     * Returns the token for this session
     *
     * @return string
     */
    public static function token() : string {
        return $_SESSION[self::TOKEN_KEY];
    }

    /**
     * Gets the token claims from session
     * @return object
     */
    public static function claims() : object {
        return $_SESSION[self::CLAIMS_KEY];
    }

    /**
     * Log in with username and password
     *
     * @param string $email
     * @param string $password
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws AuthException
     */
    public static function login(string $email, string $password) : bool {
        $client = new GuzzleHttp\Client();
        $rsp = $client->request('POST', AUTH_ENDPOINT, [
            'form_params' => [
                'username' => $email,
                'password' => $password,
                'grant_type' => 'password',
                'client_id' => AUTH_CLIENT_ID,
                'client_secret' => AUTH_CLIENT_SECRET,
                'scope' => '*'
            ],
            // debug servers don't verify since we're using self-signed
            'verify' => SERVER_ROLE !== 'DEV'
        ]);

        $body = $rsp->getBody();
        $json = json_decode($body);

        if (!$json->access_token) {
            throw new AuthException('Could not find token in response');
        }

        $publicKey = file_get_contents(AUTH_PUBLIC_KEY_PATH);
        $decoded = JWT::decode($json->access_token, $publicKey, ['RS256']);

        $_SESSION[self::TOKEN_KEY] = $json->access_token;
        $_SESSION[self::CLAIMS_KEY] = $decoded;

        return true;
    }

    public static function logout() : void {
        unset($_SESSION[self::TOKEN_KEY]);
        unset($_SESSION[self::CLAIMS_KEY]);
    }

}