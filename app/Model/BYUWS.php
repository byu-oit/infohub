<?php
App::uses('HttpSocket', 'Network/Http');
App::uses('Model', 'Model');

class BYUWS extends Model {
	private $pwsURL = 'https://ws.byu.edu/rest/v2.0/identity/person/PRO/personSummary.cgi/';
	private $supervisorURL = 'https://api.byu.edu/rest/v1/apikey/PeopleSoft_HR_REST_Get/Y_REST.v1?sn=EMPLOYEE_SUPERVISOR_LOOKUP&oprid=';

	public $useTable = false;
	public $useDbConfig = 'byuApi';
	private $summaryCache = array();


	public function personalSummary($netidRaw){
		$netid = urlencode(trim($netidRaw));
		if (array_key_exists($netid, $this->summaryCache)) {
			return $this->summaryCache[$netid];
		}

		$config = $this->getDataSource()->config;
		$http = new HttpSocket();
		$http->configAuth('ByuApi', $config);
		$response = $http->get($this->pwsURL . $netid);
		if (!$response || !$response->isOk()) {
			return array();
		}

		$data = json_decode($response->body());
		if (empty($data->PersonSummaryService->response)) {
			return array();
		}

		$this->summaryCache[$netid] = $data->PersonSummaryService->response;
		return $this->summaryCache[$netid];
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
}