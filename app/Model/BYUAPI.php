<?php
App::uses('HttpSocket', 'Network/Http');
App::uses('Model', 'Model');

class BYUAPI extends Model {
	public $useTable = false;
	public $useDbConfig = 'byuApi';
	private $personSummaryCache = [];

	public function personalSummary($netidRaw){
		$netid = urlencode(trim($netidRaw));
		if (array_key_exists($netid, $this->personSummaryCache)) {
			return $this->personSummaryCache[$netid];
		}

		$response = $this->_get("domains/legacy/identity/person/PRO/personsummary/v1/{$netid}");
		if (!$response || !$response->isOk()) {
			return array();
		}

		$data = json_decode($response->body());
		if (empty($data->PersonSummaryService->response)) {
			return array();
		}

		$this->personSummaryCache[$netid] = $data->PersonSummaryService->response;
		return $this->personSummaryCache[$netid];
	}

	public function supervisorLookup($netidRaw){
		$selfInfo = $this->personalSummary($netidRaw);
		if (empty($selfInfo) || empty($selfInfo->employee_information->reportsToId)) {
			return array();
		}

		$data = $this->personalSummary($selfInfo->employee_information->reportsToId);
		$supervisor = new stdClass();
		$supervisor->name = empty($data->names->preferred_name) ? '' : $data->names->preferred_name;
		$supervisor->phone = empty($data->contact_information->work_phone) ? '' : $data->contact_information->work_phone;
		$supervisor->email = empty($data->contact_information->email) ? '' : $data->contact_information->email;
		$supervisor->job_title = empty($data->employee_information->job_title) ? '' : $data->employee_information->job_title;
		return $supervisor;
	}


	public function deepLinks($basePathRaw){
		$config = $this->getDataSource()->config;
		$basePath = urlencode($basePathRaw);
		$response = $this->_get("domains/api-management/wso2/v1/apis?context={$basePath}");
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
		$http->configAuth('ByuApi', $config);
		return $http->get("https://{$config['host']}/{$url}");
	}
}