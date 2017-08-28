<?php
App::uses('HttpSocket', 'Network/Http');
App::uses('Model', 'Model');

class BYUAPI extends Model {
	public $useTable = false;
	public $useDbConfig = 'byuApi';

	public function personalSummary($netidRaw){
		$netid = urlencode(trim($netidRaw));
		$cacheKey = "personsummary_{$netid}";
        $summary = Cache::read($cacheKey);
		if (!empty($summary)) {
			return $summary;
		}

		$response = $this->_get("domains/legacy/identity/person/PRO/personsummary/v1/{$netid}");
		if (!$response || !$response->isOk()) {
			return array();
		}

		$data = json_decode($response->body());
		if (empty($data->PersonSummaryService->response->identifiers->net_id)) {
			return array();
		}

		Cache::write($cacheKey, $data->PersonSummaryService->response);
		return $data->PersonSummaryService->response;
	}

	public function isGROGroupMember($netid, $group) {
		if (empty($netid) || empty($group)) {
			return false;
		}

		$response = $this->_get("domains/legacy/identity/access/ismember/v1/{$group}/{$netid}");
		$data = json_decode($response);

		if (!isset($data->{'isMember Service'}->response->isMember) || !$data->{'isMember Service'}->response->isMember) {
			return false;
		}
		return true;
	}

	public function directorySearch($queryString, $length = 5) {
        $queryString = preg_replace('/ /', '%20', $queryString);
		$response = $this->_get("domains/legacy/identity/person/directorylookup2.1/v1/{$queryString}");
		$data = json_decode($response);

        if (!$data || isset($data->PersonLookupService->errors)) {
            return array();
        }

		$arrResults = array_filter($data->PersonLookupService->response->information, function ($person) {
			return !empty($person->person_id);
		});
        $arrResults = array_slice($arrResults, 0, $length);

        return $arrResults;
	}

	public function supervisorLookup($netidRaw){
		$selfInfo = $this->personalSummary($netidRaw);
		if (empty($selfInfo) || empty($selfInfo->employee_information->reportsToId)) {
			return array();
		}

		$data = $this->personalSummary($selfInfo->employee_information->reportsToId);
		$supervisor = new stdClass();
		$supervisor->name = empty($data->names->preferred_name) ? '' : $data->names->preferred_name;
		$supervisor->net_id = empty($data->identifiers->net_id) ? '' : $data->identifiers->net_id;
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
