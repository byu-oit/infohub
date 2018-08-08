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
			return [];
		}

		$data = json_decode($response->body());
		if (empty($data->PersonSummaryService->response->identifiers->net_id)) {
			return [];
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
            return [];
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
			return [];
		}

		$data = $this->personalSummary($selfInfo->employee_information->reportsToId);
		$supervisor = new stdClass();
		$supervisor->name = empty($data->names->preferred_name) ? '' : $data->names->preferred_name;
		$supervisor->net_id = empty($data->identifiers->net_id) ? '' : $data->identifiers->net_id;
		$supervisor->phone = empty($data->contact_information->work_phone) ? '' : $data->contact_information->work_phone;
		if (!empty($data->contact_information->work_email_address)) {
			$supervisor->email = $data->contact_information->work_email_address;
		} else {
			$supervisor->email = empty($data->contact_information->email_address) ? '' : $data->contact_information->email_address;
		}
		$supervisor->job_title = empty($data->employee_information->job_title) ? '' : $data->employee_information->job_title;
		$supervisor->department = empty($data->employee_information->department) ? '' : $data->employee_information->department;
		return $supervisor;
	}

	public function deepLinks($basePathRaw){
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

	public function oracleColumns($schema = null, $table = null, $db = null) {
		$url = 'domains/infohub/infohub-utils/v1/columns';
		if (!empty($schema)) {
			$url .= "/{$schema}";
		}
		if (!empty($table)) {
			$url .= "/{$table}";
		}
		if (!empty($db)) {
			if ($db === 'CESPRD' || $db === 'DWPRD') {
				$url .= "?db=default";
			} else {
				$url .= "?db={$db}";
			}
		}

		$response = $this->_get($url);
		if (!$response || !$response->isOk()) {
			return [];
		}

		$resp = json_decode($response->body(), true);
		if (!isset($resp[$schema][$table])) {
			return [];
		}
		$columns = array_filter($resp[$schema][$table], function($column) {
			return !(substr($column, 0, 4) === "SYS_");
		});
		return array_values($columns);
	}

	protected function _get($url) {
		$config = $this->getDataSource()->config;
		$http = new HttpSocket();
		$http->configAuth('ByuApi', $config);
		return $http->get("https://{$config['host']}/{$url}");
	}
}
