<?php
App::uses('HttpSocket', 'Network/Http');
App::uses('Model', 'Model');

class BYUDirectoryAPI extends Model {
	public $useTable = false;
	public $useDbConfig = 'byuApi';

	public function directorySearch($queryString, $length = 5) {
        $queryString = preg_replace('/ /', '%20', $queryString);
		$response = $this->_get("domains/legacy/identity/person/directorylookup2.1/v1/{$queryString}");
		$data = json_decode($response);

        if (!$data || isset($data->PersonLookupService->errors)) {
            return array();
        }

		$arrResults = array_filter($data->PersonLookupService->response->information, function ($person) {
			return !empty($person->net_id);
		});
        $arrResults = array_slice($arrResults, 0, $length);
		
        return $arrResults;
	}

	protected function _get($url) {
		$config = $this->getDataSource()->config;
		$http = new HttpSocket();
		$http->configAuth('ByuApi', $config);
		return $http->get("https://{$config['host']}/{$url}");
	}
}
