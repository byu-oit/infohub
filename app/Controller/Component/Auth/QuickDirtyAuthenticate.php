<?php

App::uses('BaseAuthenticate', 'Controller/Component/Auth');
App::uses('Security', 'Utility');
App::import('Vendor', 'Crypt/AES');

class QuickDirtyAuthenticate extends BaseAuthenticate {
	public function authenticate(CakeRequest $request, CakeResponse $response) {
		//No permanent login allowed with this methond, only one at a time
		return false;
	}

	public function unauthenticated(CakeRequest $request, CakeResponse $response) {
		if (empty($request->query['api_key'])) {
			return false;
		}

		$aes = new Crypt_AES();
		$aes->setKey(Configure::read('Security.salt'));
		$binaryString = base64_decode(strtr($request->query['api_key'], '-.', '+/'));
		$username = $aes->decrypt($binaryString);
		if (empty($username)) {
			return false;
		}
		return true;
	}
}