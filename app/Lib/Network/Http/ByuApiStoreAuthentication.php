<?php

class ByuApiStoreAuthentication {

/**
 * Authentication
 *
 * @param HttpSocket $http
 * @param array $authInfo
 * @return void
 */
    public static function authentication(HttpSocket $http, &$authInfo) {
        if (empty($authInfo['bearer'])) {
            return;
        }
		$http->request['header']['Authorization'] = "Bearer {$authInfo['bearer']}";
    }
}