<?php
App::uses('HttpSocket', 'Network/Http');
App::uses('Model', 'Model');

class BYUAPI extends Model {
	public $useTable = false;
	public $useDbConfig = 'apiStore';


	public function deepLinks($basePathRaw){
		$config = $this->getDataSource()->config;
		$basePath = urlencode($basePathRaw);
		$response = $this->_get("https://{$config['host']}/domains/api-management/wso2/v1/apis?context={$basePath}");
		if (!$response || !$response->isOk()) {
			return false;
		}

		$data = json_decode($response->body());
		return [
			'name' => empty($data->data[0]->Name) ? null : $data->data[0]->Name,
			'link' => empty($data->data[0]->Links->Store) ? null : $data->data[0]->Links->Store
		];
	}

	protected function _get($url) {
		$config = $this->getDataSource()->config;
		$http = new HttpSocket();
		$http->configAuth('ByuApiStore', $config);
		return $http->get($url);
	}
}