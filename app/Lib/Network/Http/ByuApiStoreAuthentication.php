<?php

App::uses('CakeSession', 'Model/Datasource');

class ByuApiStoreAuthentication {

/**
 * Authentication
 *
 * @param HttpSocket $http
 * @param array $authInfo
 * @return void
 */
    public static function authentication(HttpSocket $http, &$authInfo) {
		$token = self::_getBearerToken($authInfo);
		if (empty($token)) {
			return;
		}
		$http->request['header']['Authorization'] = "Bearer {$token}";
    }

	protected static function _getBearerToken($authInfo) {
		$tokenInfo = CakeSession::read('ByuApiBearerToken');
		if (empty($tokenInfo)) {
			$tokenInfo = self::_generateBearerToken($authInfo);
			if (empty($tokenInfo)) { //Nope, still empty
				return null;
			}
		}

		//If we're less than 5 minutes from token expiration, then go ahead and
		//force regeneration
		if ($tokenInfo->expire_time - time() < 300) {
			$tokenInfo = self::_regenerateBearerToken($authInfo);
			if (empty($tokenInfo)) {
				return null;
			}
		}
		return $tokenInfo->access_token;
	}

	protected static function _generateBearerToken($authInfo) {
		if (empty($authInfo['key']) || empty($authInfo['secret'])) {
			return null;
		}
        $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://api.byu.edu/token');
		curl_setopt($ch, CURLOPT_USERPWD, "{$authInfo['key']}:{$authInfo['secret']}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');

        $tokenRaw = curl_exec($ch);
        curl_close($ch);
        $tokenInfo = @json_decode($tokenRaw);
		if (empty($tokenInfo->access_token)) {
			return null;
		}

		$tokenInfo->expire_time = time() + intval($tokenInfo->expires_in);
		CakeSession::write('ByuApiBearerToken', $tokenInfo);

		return $tokenInfo;
	}

	protected static function _regenerateBearerToken($authInfo) {
		CakeSession::delete('ByuApiBearerToken');
		if (empty($authInfo['key']) || empty($authInfo['secret'])) {
			return null;
		}
        $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://api.byu.edu/token/revoke');
		curl_setopt($ch, CURLOPT_USERPWD, "{$authInfo['key']}:{$authInfo['secret']}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');

        $revokeRaw = curl_exec($ch);
        curl_close($ch);

		return self::_generateBearerToken($authInfo);
	}
}