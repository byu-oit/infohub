<?php
App::uses('Model', 'Model');

class DremioAPI extends Model {
	public $useTable = false;
	public $useDbConfig = 'dremioApi';

	public function catalog($path = '') {
		$url = empty($path) ? 'catalog' : 'catalog/by-path/'.$path;
		$data = $this->_get($url);

		if (isset($data->errorMessage)) {
			return [];
		}

		return $data;
	}

	protected function _get($url) {
		$config = $this->getDataSource()->config;
		$token = self::_getToken($config);
		return "Hello ".$token;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $config['url'].$url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization:_dremio'.$token]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$respRaw = curl_exec($ch);
		curl_close($ch);

		return json_decode($respRaw);
	}

	protected static function _getToken($authInfo) {
		$tokenInfo = CakeSession::read('DremioApiToken');
		if (empty($tokenInfo)) {
			$tokenInfo = self::_generateToken($authInfo);
			if (empty($tokenInfo)) { //Nope, still empty
				return null;
			}
		}

		// If we're less than 5 minutes from token expiration,
		// then go ahead and force regeneration
		if ($tokenInfo->expires - time() < 300) {
			CakeSession::delete('DremioApiToken');
			$tokenInfo = self::_generateToken($authInfo);
			if (empty($tokenInfo)) {
				return null;
			}
		}
		return $tokenInfo->token;
	}

	protected static function _generateToken($authInfo) {
		if (empty($authInfo['username']) || empty($authInfo['password'])) {
			return null;
		}
		$payload = json_encode([
			'userName' => $authInfo['username'],
			'password' => $authInfo['password']
		]);
		$ch = curl_init();
		// We're intentionally calling a previous API version; the Dremio
		// API uses this past version for authenticating
		curl_setopt($ch, CURLOPT_URL, 'https://dremio.byu.edu/apiv2/login');
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);

		// There's a known issue with Dremio's API that causes the first
		// authentication call in a session to fail. Here we call the
		// endpoint twice to get the token on the second call.
		$throwaway = curl_exec($ch);
		$tokenRaw = curl_exec($ch);
		curl_close($ch);

		$tokenInfo = @json_decode($tokenRaw);
		if (empty($tokenInfo->token)) {
			return null;
		}
		CakeSession::write('DremioApiToken', $tokenInfo);
		return $tokenInfo;
	}
}
