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
        if (empty($authInfo['api_key'])) {
            return;
        }
        if (empty($authInfo['shared_secret'])) {
            return;
        }
        $netId = empty($authInfo['net_id']) ? null : $authInfo['net_id'];
        $nonce = self::_getNonce($authInfo['api_key'], $authInfo['shared_secret'], $netId);
        $http->request['header']['Authorization'] = $nonce;
    }

    protected static function _getNonce($key, $sharedSecret, $netId = null)
    {
        $ch = curl_init();
        $nonceUrl = 'https://ws.byu.edu/authentication/services/rest/v1/hmac/nonce/';
        if (!empty($netId)) {
            curl_setopt($ch, CURLOPT_URL, $nonceUrl . $key . '/' . urlencode(trim($netId)));
        } else  {
            curl_setopt($ch, CURLOPT_URL, $nonceUrl . $key);
        }
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