<?php

class ByuApiAuthentication {

/**
 * Authentication
 *
 * @param HttpSocket $http
 * @param array $authInfo
 * @return void
 */
    public static function authentication(HttpSocket $http, &$authInfo) {
        if (empty($authInfo['user'])) {
            return;
        }
        if (empty($authInfo['pass'])) {
            return;
        }
        $nonce = self::_getNonce($authInfo['user'], $authInfo['pass']);
        $http->request['header']['Authorization'] = $nonce;
    }

    protected static function _getNonce($key, $sharedSecret)
    {
        $ch = curl_init();
        $nonceUrl = 'https://ws.byu.edu/authentication/services/rest/v1/hmac/nonce/';
        curl_setopt($ch, CURLOPT_URL, $nonceUrl . $key);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '');

        $nonceRaw = curl_exec($ch);
        curl_close($ch);
        $nonce = json_decode($nonceRaw, true);
        $nonceValue = $nonce['nonceValue'];
        $nonceKey = $nonce['nonceKey'];
        $hash = base64_encode(hash_hmac('sha512', $nonceValue, $sharedSecret, true));
        return "Nonce-Encoded-API-Key {$key},{$nonceKey},{$hash}";
    }
}