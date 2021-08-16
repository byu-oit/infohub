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
		$response = $this->_get("byuapi/persons/v3/?net_ids={$netid}&field_sets=basic%2Cemployee_summary%2Cgroup_memberships&contexts=contact");
		if (!$response || !$response->isOk()) {
			return [];
		}
		$data = json_decode($response->body());
		if (empty($data->values[0]->basic->net_id->value)) {
			return [];
		}
		Cache::write($cacheKey, $data->values[0]);
		return $data->values[0];
		
	}

	public function personalSummaryByBYUId($byuIdRaw) {
		$byuId = urldecode(trim($byuIdRaw));
		$cacheKey = "personSummaryBYUId_{$byuId}";
        $summary = Cache::read($cacheKey);
		if (!empty($summary)) {
			return $summary;
		}
		$response = $this->_get("byuapi/persons/v3/{$byuId}?field_sets=basic%2Cemployee_summary%2Cgroup_memberships&contexts=contact");
		if (!$response || !$response->isOk()) {
			return [];
		}
		$data = json_decode($response->body());
		if (empty($data->basic->net_id->value)) {
			return [];
		}
		Cache::write($cacheKey, $data);
		return $data;
	}

	public function isGROGroupMemberAny($netid, ...$groups) {
		if (empty($netid) || empty($groups)) {
			return false;
		}
		$person = $this->personalSummary($netid);
		if(empty($person) || $person->group_memberships->metadata->collection_size == 0){
			return false;
		}
		$groupMembership = $person->group_memberships;
		for($i=0; $i < $person->group_memberships->metadata->collection_size; $i++) {
			$groupId = $groupMembership->values[$i]->group_id->value;
			foreach ($groups as $group) {
				if($groupId == $group) {
					return true;
				}
			}
		}
		return false;
	}

	public function directorySearch($queryString, $length = 5) {
        $queryString = preg_replace('/ /', '%20', $queryString);
		$response = $this->_get("byuapi/persons/v3/?search_context=person_lookup&search_text={$queryString}&field_sets=basic%2Cemployee_summary%2Cgroup_memberships");
		$data = json_decode($response);

        if (!$data || !$response->isOk()) {
            return [];
        }
		
		$data = $data;

		$arrResults = array_filter($data->values, function ($person) {
			return !empty($person->basic->person_id->value);
		});
        $arrResults = array_slice($arrResults, 0, $length);

        return $arrResults;
	}

	public function supervisorLookup($netidRaw){
		$selfInfo = $this->personalSummary($netidRaw);
		if (empty($selfInfo) || empty($selfInfo->employee_summary->reports_to_byu_id->value)) {
			return [];
		}
		$data = $this->personalSummaryByBYUId($selfInfo->employee_summary->reports_to_byu_id->value);
		$supervisor = new stdClass();
		$supervisor->name = empty($data->basic->preferred_name->value) ? '' : $data->basic->preferred_name->value;
		$supervisor->net_id = empty($data->basic->net_id->value) ? '' : $data->basic->net_id->value;
		for($i = 0; $i < sizeof($data->phones->values); $i++) {
			if($data->phones->values[$i]->work_flag->value) {
				$supervisor->phone = $data->phones->values[$i]->phone_number->value;
				break;
			}
		}
		for($i = 0; $i < sizeof($data->email_addresses->values); $i++) {
			if($data->email_addresses->values[$i]->email_address_type->value == "PERSONAL") {
				$psEmailPersonal = $data->email_addresses->values[$i]->email_address->value;
			} else if($data->email_addresses->values[$i]->email_address_type->value == "WORK") {
				$psWorkEmail = $data->email_addresses->values[$i]->email_address->value;
			}
		}
		if(isset($psWorkEmail)) {
			$supervisor->email = $psWorkEmail;
		} else if(isset($psEmailPersonal)){
			$supervisor->email = $psEmailPersonal;
		}
		$supervisor->job_title = empty($data->employee_summary->job_code->description) ? '' : $data->employee_summary->job_code->description;
		$supervisor->department = empty($data->employee_summary->department->value) ? '' : $data->employee_summary->department->value;
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

	public function oracleColumns($db = null, $schema = null, $table = null) {
		$url = 'domains/infohub/infohub-utils/v1/columns';
		if (!empty($schema)) {
			$url .= "/{$schema}";
		}
		if (!empty($table)) {
			$url .= "/{$table}";
		}
		if (!empty($db)) {
			if ($db === 'DWPRD') {
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
		if (!empty($table) && !isset($resp[$schema][$table])) {
			return [];
		}

		foreach ($resp[$schema] as $tableName => $columns) {
			if (substr($tableName, 0, 4) === "BIN$") {
				unset($resp[$schema][$tableName]);
				continue;
			}
			$resp[$schema][$tableName] = array_values(array_filter($resp[$schema][$tableName], function($column) {
				return !(substr($column, 0, 4) === "SYS_");
			}));
		}

		if (isset($table)) {
			return $resp[$schema][$table];
		} else {
			return $resp[$schema];
		}
	}

	protected function _get($url) {
		$config = $this->getDataSource()->config;
		$http = new HttpSocket();
		$http->configAuth('ByuApi', $config);
		return $http->get("https://{$config['host']}/{$url}");
	}
}
