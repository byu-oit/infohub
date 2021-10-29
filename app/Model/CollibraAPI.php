<?php

App::uses('HttpSocket', 'Network/Http');
App::uses('Model', 'Model');
App::uses('CakeText', 'Utility');
App::uses('CakeSession', 'Model/Datasource');

class CollibraAPI extends Model {
	public $useTable = false;
	public $useDbConfig = 'collibra';
	private $code;
	private $info;
	public $errors = [];
	private $requestTries = 0;

	private $settings;
	private $_rolesCache = [];

	private static function cmp($a, $b){
		return strcmp($a->name, $b->name);
	}

	private function prepData($postData) {
		$postString = http_build_query($postData);
		return preg_replace("/%5B[0-9]*%5D/", "", $postString);
	}

	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		$this->settings = $this->getDataSource()->config;
	}

	/**
	 *
	 * @return HttpSocket
	 */
	protected function client() {
		if (empty($this->_client)) {
			$config = [];
			$cookies = CakeSession::read('Collibra.cookies');
			if (!empty($cookies)) {
				$config['request']['cookies'] = $cookies;
			}
			$this->_client = new HttpSocket($config);
			$this->_client->configAuth('Basic', $this->settings['username'], $this->settings['password']);
			$this->_client->configProxy(Configure::read('proxy'));
		}
		return $this->_client;
	}

	public function get($url, $options = []) {
		$response = $this->client()->get($this->settings['url'] . $url);
		if (!empty($response) && !empty($response->cookies)) {
			$this->_updateSessionCookies();
		}
		if (!empty($options['raw'])) {
			return $response;
		}
		if (!$response->isOk()) {
			$this->errors[] = $response->body();
			return false;
		}
		if (!empty($options['json'])) {
			return json_decode($response->body());
		}
		return $response->body();
	}

	public function post($url, $data = [], $options = [], $isFullUrl = false) {
		$postUrl = $isFullUrl ? $url : ($this->settings['url'] . $url);
		$response = $this->client()->post($postUrl, $data, $options);
		if (!empty($response) && !empty($response->cookies)) {
			$this->_updateSessionCookies();
		}
		return $response;
	}

	public function postJSON($url, $data, $options = [], $isFullUrl = false) {
		$options['header']['Content-Type'] = 'application/json';
		return $this->post($url, $data, $options, $isFullUrl);
	}

	public function delete($url = NULL, $cascade = true) {
		return $this->client()->delete($this->settings['url'] . $url);
	}

	public function deleteJSON($url = NULL, $data = []) {
		$options['header']['Accept'] = 'application/json';
		return $this->client()->delete($this->settings['url'] . $url, $data, $options);
	}

	public function dataTable($config) {
		$response = $this->postJSON("output/data_table", json_encode($config));
		if (!($response && $response->isOk())) {
			return null;
		}
		return @json_decode($response->body());
	}

	public function fullDataTable($config) {
		$config['TableViewConfig']['displayStart'] = 0;
		$data = [];
		while (true) {
			$chunk = $this->dataTable($config);
			if (empty($chunk->aaData)) {
				return $data;
			}
			$data = array_merge($data, $chunk->aaData);
			if (count($data) == $chunk->iTotalDisplayRecords) {
				return $data;
			}
			$config['TableViewConfig']['displayStart'] += intval($chunk->iTotalRecords);
		}
	}
	protected function buildTableConfig($config) {
		$output = [];
		foreach((array)$config as $resourceName => $resource) {
			$filters = [];
			foreach($resource as $field => $filter) {
				if (is_int($field)) {
					$field = $filter;
					$filter = null;
				}
				if (is_array($filter)) { //Group, not filter
					$groupColumns = ['name' => $field, 'Columns' => []];
					$groupResources = [];
					foreach ($filter as $groupField) {
						//no subgroups or subfilter right now
						$fieldName = "{$field}{$groupField}";
						$groupColumns['Columns'][] = ['Column' => ['fieldName' => $fieldName]];
						$groupResources[$groupField]['name'] = $fieldName;
					}
					$output['Columns'][] = ['Group' => $groupColumns];
					$output['Resources'][$resourceName][$field]  = $groupResources;
					continue;
				}
				$fieldName = "{$resourceName}{$field}";
				$output['Columns'][] = ['Column' => ['fieldName' => $fieldName]];
				$output['Resources'][$resourceName][$field]['name'] = $fieldName;
				if ($filter) {
					$filters[] = ['Field' => ['name' => $fieldName, 'operator' => 'EQUALS', 'value' => $filter]];
				}
			}
			if (!empty($filters)) {
				$output['Resources'][$resourceName]['Filter']['AND'] = $filters;
			}
		}
		return ['TableViewConfig' => $output];
	}

	public function updateUserFromByu($netId) {
		$byuInfo = ClassRegistry::init('BYUAPI')->personalSummary($netId);
		if (empty($byuInfo)) {
			$this->errors[] = "BYU info not found for Net ID {$netId}";
			return false;
		}
		$collibraInfo = $this->userRecordFromUsername($netId);
		if (empty($collibraInfo)) {
			$this->errors[] = "Collibra info not found for Net ID {$netId}";
			return false;
		}
		$this->errors[] = "This is no longer supported";
		return false;

		if (!empty($byuInfo->contact_information->work_email_address)) {
			if (empty($collibraInfo->UserEmailaddress) || html_entity_decode($collibraInfo->UserEmailaddress) != $byuInfo->contact_information->work_email_address) {
				$this->updateUser($collibraInfo->UserId, [
					'firstName' => $collibraInfo->UserFirstName,
					'lastName' => $collibraInfo->UserLastName,
					'email' => $byuInfo->contact_information->work_email_address,
					'gender' => $collibraInfo->UserGender
				]);
			}
		}

		if (!empty($byuInfo->contact_information->work_phone)) {
			$byuPhone = $byuInfo->contact_information->work_phone;
			$match = in_array($byuPhone, array_column($collibraInfo->Phone, 'PhonePhonenumber'));	// 'PhonePhonenumber' is not a typo
			if (!$match) {
				$this->updateUserPhone($collibraInfo->UserId, $byuPhone, empty($collibraInfo->Phone[0]->PhoneId) ? null : $collibraInfo->Phone[0]->PhoneId);
			}
		}

		$byuPhoto = ClassRegistry::init('Photo')->get($netId);
		if (empty($byuPhoto)) {
			return true;
		}
		$photoNotFound = ClassRegistry::init('Photo')->get('thisisnotarealnetidquackslikeaduckblahblahblah');
		if (!empty($photoNotFound['body']) && $photoNotFound['body'] == $byuPhoto['body']) {
			//BYU photo does not actually exist: is the same as placeholder
			return true;
		}
		$collibraPhoto = $this->photo($collibraInfo->UserId);
		if (empty($collibraPhoto)) {
			return true;
		}
		if ($collibraPhoto['body'] != $byuPhoto['body']) {
			$this->photo($collibraInfo->UserId, $byuPhoto);
		}

		return true;
	}

	public function updateUser($userResourceId, $data) {
		$requiredFields = ['firstName', 'lastName', 'email', 'gender'];
		$passedFields = array_keys($data);
		$missingFields = array_diff($requiredFields, $passedFields);
		if (!empty($missingFields)) {
			$this->errors[] = 'Missing required fields: ' . implode(', ', $missingFields);
			return false; //missing some required fields
		}
		$response = $this->post("user/{$userResourceId}", $data);
	}

	public function updateUserPhone($userResourceId, $phone, $existingPhoneResourceId = null) {
		$path = "user/{$userResourceId}/phone";
		if (!empty($existingPhoneResourceId)) {
			$path .= "/$existingPhoneResourceId";
		}
		$response = $this->post($path, ['phoneNumber' => $phone, 'phoneType' => 'WORK']);
	}

	public function userRecordFromUsername($username) {
		$tableConfig = $this->buildTableConfig(['User' => ['Id', 'UserName' => $username, 'FirstName', 'LastName', 'Emailaddress', 'Gender', 'Phone' => ['Id', 'Phonenumber', 'PhoneType']]]);
		$data = $this->dataTable($tableConfig);
		if (empty($data->iTotalRecords) || $data->iTotalRecords != 1) {
			return null;
		}
		return empty($data->aaData[0]) ? null : $data->aaData[0];
	}

	public function userResourceFromUsername($username) {
		$user = $this->userRecordFromUsername($username);
		return empty($user->UserId) ? null : $user->UserId;
	}

	public function getUserData() {
		$tableConfig = ['TableViewConfig' => [
			'Columns' => [
				['Column' => ['fieldName' => 'userrid']],
				['Column' => ['fieldName' => 'userenabled']],
				['Column' => ['fieldName' => 'userfirstname']],
				['Group' => [
					'name' => 'groupname',
					'Columns' => [
						['Column' => ['fieldName' => 'groupgroupname']],
						['Column' => ['fieldName' => 'grouprid']]]]],
				['Column' => ['fieldName' => 'userlastname']],
				['Column' => ['fieldName' => 'emailemailaddress']],
				['Group' => [
					'name' => 'phonenumber',
					'Columns' => [
						['Column' => ['fieldName' => 'phonephonenumber']],
						['Column' => ['fieldName' => 'phonerid']]]]],
				['Column' => ['fieldName' => 'useractivated']],
				['Column' => ['fieldName' => 'isuserldap']],
				['Group' => [
					'name' => 'Rolef86d1d3abc2e4beeb17fe0e9985d5afb',
					'Columns' => [
						['Column' => ['label' => 'Custodian User ID', 'fieldName' => 'userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid']],
						['Column' => ['label' => 'Custodian Gender', 'fieldName' => 'userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender']],
						['Column' => ['label' => 'Custodian First Name', 'fieldName' => 'userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn']],
						['Column' => ['label' => 'Custodian Last Name', 'fieldName' => 'userRolef86d1d3abc2e4beeb17fe0e9985d5afbln']]]]],
				['Group' => [
					'name' => 'Rolef86d1d3abc2e4beeb17fe0e9985d5afbg',
					'Columns' => [
						['Column' => ['label' => 'Custodian Group ID', 'fieldName' => 'groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid']],
						['Column' => ['label' => 'Custodian Group Name', 'fieldName' => 'groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn']]]]],
				['Group' => [
					'name' => 'Role8a0a6c89106c4adb9936f09f29b747ac',
					'Columns' => [
						['Column' => ['label' => 'Steward User ID', 'fieldName' => 'userRole8a0a6c89106c4adb9936f09f29b747acrid']],
						['Column' => ['label' => 'Steward Gender', 'fieldName' => 'userRole8a0a6c89106c4adb9936f09f29b747acgender']],
						['Column' => ['label' => 'Steward First Name', 'fieldName' => 'userRole8a0a6c89106c4adb9936f09f29b747acfn']],
						['Column' => ['label' => 'Steward Last Name', 'fieldName' => 'userRole8a0a6c89106c4adb9936f09f29b747acln']]]]],
				['Group' => [
					'name' => 'Role8a0a6c89106c4adb9936f09f29b747acg',
					'Columns' => [
						['Column' => ['label' => 'Steward Group ID', 'fieldName' => 'groupRole8a0a6c89106c4adb9936f09f29b747acgrid']],
						['Column' => ['label' => 'Steward Group Name', 'fieldName' => 'groupRole8a0a6c89106c4adb9936f09f29b747acggn']]]]]],
			'Resources' => [
				'User' => [
					'Enabled' => ['name' => 'userenabled'],
					'UserName' => ['name' => 'userusername'],
					'FirstName' => ['name' => 'userfirstname'],
					'LastName' => ['name' => 'userlastname'],
					'Emailaddress' => ['name' => 'emailemailaddress'],
					'Phone' => [
						'Phonenumber' => ['name' => 'phonephonenumber'],
						'Id' => ['name' => 'phonerid']],
					'Group' => [
						'Groupname' => ['name' => 'groupgroupname'],
						'Id' => ['name' => 'grouprid']],
					'Activated' => ['name' => 'useractivated'],
					'LDAPUser' => ['name' => 'isuserldap'],
					'Id' => ['name' => 'userrid'],
					'Member' => [[
						'User' => [
							'Gender' => ['name' => 'userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender'],
							'FirstName' => ['name' => 'userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn'],
							'Id' => ['name' => 'userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid'],
							'LastName' => ['name' => 'userRolef86d1d3abc2e4beeb17fe0e9985d5afbln']],
						'Role' => [
							'name' => 'Rolef86d1d3abc2e4beeb17fe0e9985d5afb',
							'Signifier' => ['hidden' => 'true', 'name' => 'Rolef86d1d3abc2e4beeb17fe0e9985d5afbsig'],
							'Id' => ['hidden' => 'true', 'name' => 'roleRolef86d1d3abc2e4beeb17fe0e9985d5afbrid']],
						'roleId' => Configure::read('Collibra.role.custodian')],
					[
						'Role' => [
							'Signifier' => ['hidden' => 'true', 'name' => 'Rolef86d1d3abc2e4beeb17fe0e9985d5afbg'],
							'Id' => ['hidden' => 'true', 'name' => 'roleRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid']],
						'Group' => [
							'GroupName' => ['name' => 'groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn'],
							'Id' => ['name' => 'groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid']],
							'roleId' => Configure::read('Collibra.role.custodian')],
					[
						'User' => [
							'Gender' => ['name' => 'userRole8a0a6c89106c4adb9936f09f29b747acgender'],
							'FirstName' => ['name' => 'userRole8a0a6c89106c4adb9936f09f29b747acfn'],
							'Id' => ['name' => 'userRole8a0a6c89106c4adb9936f09f29b747acrid'],
							'LastName' => ['name' => 'userRole8a0a6c89106c4adb9936f09f29b747acln']],
						'Role' => [
							'name' => 'Role8a0a6c89106c4adb9936f09f29b747ac',
							'Signifier' => ['hidden' => 'true', 'name' => 'Role8a0a6c89106c4adb9936f09f29b747acsig'],
							'Id' => ['hidden' => 'true', 'name' => 'roleRole8a0a6c89106c4adb9936f09f29b747acrid']],
						'roleId' => Configure::read('Collibra.role.steward')],
					[
						'Role' => [
							'Signifier' => ['hidden' => 'true', 'name' => 'Role8a0a6c89106c4adb9936f09f29b747acg'],
							'Id' => ['hidden' => 'true', 'name' => 'roleRole8a0a6c89106c4adb9936f09f29b747acgrid']],
						'Group' => [
							'GroupName' => ['name' => 'groupRole8a0a6c89106c4adb9936f09f29b747acggn'],
							'Id' => ['name' => 'groupRole8a0a6c89106c4adb9936f09f29b747acgrid']],
						'roleId' => Configure::read('Collibra.role.steward')]],
					'Filter' => [
						'AND' => [[
							'OR' => [[
								'Field' => [
									'name' => 'userRole8a0a6c89106c4adb9936f09f29b747acrid',
									'operator' => 'NOT_NULL']],
							[
								'Field' => [
									'name' => 'userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid',
									'operator' => 'NOT_NULL']]]],
						[
							'AND' => [[
								'Field' => ['name' => 'userenabled',
									'operator' => 'EQUALS',
									'value' => 'true']]]]]]]],
			'Order' => [[
				'Field' => [
					'name' => 'userlasttname',
					'order' => 'ASC']]],
			'displayStart' => 0,
			'displayLength' => 1000]];

		$results = $this->fullDataTable($tableConfig);
		return $results;
	}

	public function userList($limit = 20, $offset = 0) {
		$config = $this->buildTableConfig(['User' => ['Id', 'UserName', 'FirstName', 'LastName', 'Emailaddress']]);
		$config['TableViewConfig']['Resources']['User']['Order'][] = [
			'Field' => [
				'name' => 'UserFirstName',
				'order' => 'ASC']];
		$config['TableViewConfig']['displayStart'] = $offset;
		$config['TableViewConfig']['displayLength'] = $limit;
		return $this->dataTable($config);
	}
	public function photo($userResourceId, $update = null, $requestedSize = null) {
		if (!empty($update)) {
			$type = explode(';', $update['type'])[0];
			$typeSplit = explode('/', $type);
			$extension = (count($typeSplit) > 1) ? $typeSplit[1] : $type;
			$fileId = $this->uploadFile($update['body'], "newphoto.{$extension}");
			if (empty($fileId)) {
				return null;
			}
			$response = $this->post("user/{$userResourceId}/avatar", ['file' => $fileId]);
			return ($response && $response->isOk());
		}
		$size = intval($requestedSize);
		if ($size == 0) {
			$size = 300;
		}
		$photo = $this->get("user/{$userResourceId}/avatar?width={$size}&height={$size}", ['raw' => true]);
		if (!($photo && $photo->isOk())) {
			return null;
		}
		return [
			'type' => $photo->getHeader('Content-Type'),
			'body' => $photo->body];
	}

	public function uploadFile($rawData, $filename = null) {
		$boundary = 'CakePHPBoundary' . str_replace('-', '', CakeText::uuid());
		if (empty($filename)) {
			$filename = 'uploadfile';
		}
		$body = "--{$boundary}\r\nContent-Disposition: form-data; name=\"{$filename}\"; filename=\"{$filename}\"\r\n\r\n{$rawData}\r\n--{$boundary}--";
		/* @var $response HttpSocketResponse */
		$response = $this->post(
				"file",
				$body,
				['header' => [
					'Content-Type' => "multipart/form-data; boundary={$boundary}"]]);
		if (!($response && $response->isOk())) {
			return null;
		}
		$files = @json_decode($response->body());
		if (empty($files->file[0])) {
			echo "NOPE";
			return null;
		}
		return $files->file[0];
	}

	public function getApiHosts() {
		$hosts = [];
		$hostsRaw = $this->get('community/' . Configure::read('Collibra.community.api') . '/sub-communities', ['json' => true]);
		if (empty($hostsRaw->communityReference)) {
			return $hosts;
		}
		foreach ($hostsRaw->communityReference as $host) {
			//if (empty($host->name) || !preg_match('/^[a-zA-Z][a-zA-Z0-9\.]*$/', $host->name) || $host->name === 'SAML') {
			if (empty($host->name) || $host->name === 'SAML') {
				continue;
			}
			$hosts[] = $host->name;
		}
		return $hosts;
	}

	public function getHostApis($hostCommunityId) {
		$tableConfig = ['TableViewConfig' => [
			'Columns' => [
				['Column' => ['fieldName' => 'id']],
				['Column' => ['fieldName' => 'name']],
				['Column' => ['fieldName' => 'statusId']],
				['Column' => ['fieldName' => 'status']]],
			'Resources' => [
				'Term' => [
					'Id' => ['name' => 'id'],
					'Signifier' => ['name' => 'name'],
					'Status' => [
						'Id' => ['name' => 'statusId'],
						'Signifier' => ['name' => 'status']],
					'Vocabulary' => [
						'Community' => [
							'Id' => ['name' => 'communityId']]],
					'ConceptType' => [
						'Id' => ['name' => 'assetTypeId']],
					'Filter' => [
						'AND' => [
							['Field' => [
								'name' => 'communityId',
								'operator' => 'EQUALS',
								'value' => $hostCommunityId]],
							['Field' => [
								'name' => 'assetTypeId',
								'operator' => 'EQUALS',
								'value' => Configure::read('Collibra.type.api')]]]]]]]];
		$results = $this->fullDataTable($tableConfig);
		return $results;
	}

	public function getApiObject($host, $path) {
		$hostCommunity = $this->findTypeByName('community', $host);
		if (empty($hostCommunity->resourceId)) {
			return null;
		}
		$vocabulary = $this->findTypeByName('vocabulary', $path, ['parent' => $hostCommunity->resourceId]);
		if (empty($vocabulary->resourceId)) {
			return null;
		}
		$query = ['TableViewConfig' => [
			'Columns' => [
				['Column' => ['fieldName' => 'id']],
				['Column' => ['fieldName' => 'name']],
				['Column' => ['fieldName' => 'statusId']],
				['Column' => ['fieldName' => 'status']],
				['Column' => ['fieldName' => 'authorizedByFieldset']],
				['Column' => ['fieldName' => 'usageNotes']],
				['Group' => [
					'name' => 'dataSharingRequests',
					'Columns' => [
						['Column' => ['fieldName' => 'dsrId']],
						['Column' => ['fieldName' => 'dsrName']],
						['Column' => ['fieldName' => 'dsrStatus']],
						['Column' => ['fieldName' => 'dsrStatusId']]]]]],
			'Resources' => [
				'Term' => [
					'Id' => ['name' => 'id'],
					'Signifier' => ['name' => 'name'],
					'Status' => [
						'Id' => ['name' => 'statusId'],
						'Signifier' => ['name' => 'status']],
					'StringAttribute' => [[
							'Value' => ['name' => 'usageNotes'],
							'labelId' => Configure::read('Collibra.attribute.usageNotes')]],
					'BooleanAttribute' => [
						'Value' => ['name' => 'authorizedByFieldset'],
						'labelId' => Configure::read('Collibra.attribute.authorizedByFieldset')],
					'Relation' => [[
						'typeId' => Configure::read('Collibra.relationship.DSRtoNecessaryAPI'),
						'type' => 'TARGET',
						'Source' => [
							'Id' => ['name' => 'dsrId'],
							'Signifier' => ['name' => 'dsrName'],
							'Status' => [
								'Id' => ['name' => 'dsrStatusId'],
								'Signifier' => ['name' => 'dsrStatus']]]]],
					'Vocabulary' => [
						'Id' => ['name' => 'vocabId']],
					'ConceptType' => [
						'Id' => ['name' => 'assetTypeId']],
					'Filter' => [
						'AND' => [
							['Field' => [
								'name' => 'vocabId',
								'operator' => 'EQUALS',
								'value' => $vocabulary->resourceId]],
							['Field' => [
								'name' => 'assetTypeId',
								'operator' => 'EQUALS',
								'value' => Configure::read('Collibra.type.api')]]]]]]]];
		$result = $this->fullDataTable($query);
		return $result[0];
	}

	public function getApiFields($host, $path, $treeStructure = false) {
		$hostCommunity = $this->findTypeByName('community', $host);
		if (empty($hostCommunity->resourceId)) {
			return null;
		}
		$vocabulary = $this->findTypeByName('vocabulary', $path, ['parent' => $hostCommunity->resourceId]);
		if (empty($vocabulary->resourceId)) {
			return null;
		}
		$termsQuery = [
			'TableViewConfig' => [
				'Columns' => [
					['Column' => ['fieldName' => 'id']],
					['Column' => ['fieldName' => 'name']],
					['Column' => ['fieldName' => 'assetType']],
					['Group' => [
						'name' => 'businessTerm',
						'Columns' => [
							['Column' => ['fieldName' => 'termCommunityId']],
							['Column' => ['fieldName' => 'termCommunityName']],
							['Column' => ['fieldName' => 'termVocabularyId']],
							['Column' => ['fieldName' => 'termVocabularyName']],
							['Column' => ['fieldName' => 'termClassification']],
							['Column' => ['fieldName' => 'approvalStatus']],
							['Column' => ['fieldName' => 'termId']],
							['Column' => ['fieldName' => 'termDescription']],
							['Column' => ['fieldName' => 'termRelationId']],
							['Column' => ['fieldName' => 'term']]]]]],
				'Resources' => [
					'Term' => [
						'Id' => ['name' => 'id'],
						'Signifier' => ['name' => 'name'],
						'ConceptType' => [
							'Signifier' => ['name' => 'assetType'],
							'Id' => ['name' => 'assetTypeId']],
						'Relation' => [[ /* Yes, intentional [[ there */
							'typeId' => Configure::read('Collibra.relationship.termToDataAsset'),
							'type' => 'TARGET',
							'Id' => ['name' => 'termRelationId'],
							'Source' => [
								'Id' => ['name' => 'termId'],
								'Status' => [
									'signifier' => ['name' => 'approvalStatus']],
								'StringAttribute' => [[
									'Value' => ['name' => 'termDescription'],
									'labelId' => Configure::read('Collibra.attribute.definition')]],
								'SingleValueListAttribute' => [[
									'Value' => ['name' => 'termClassification'],
									'labelId' => Configure::read('Collibra.attribute.classification')]],
								'Vocabulary' => [
									'Id' => ['name' => 'termVocabularyId'],
									'Name' => ['name' => 'termVocabularyName'],
									'Community' => [
										'Id' => ['name' => 'termCommunityId'],
										'Name' => ['name' => 'termCommunityName']]],
								'Signifier' => ['name' => 'term']]]],
						'Vocabulary' => [
							'Id' => ['name' => 'domainId']],
						'Filter' => [
							'AND' => [
								['OR' => [
									['Field' => [
										'name' => 'assetTypeId',
										'operator' => 'EQUALS',
										'value' => Configure::read('Collibra.type.field')]],
									['Field' => [
										'name' => 'assetTypeId',
										'operator' => 'EQUALS',
										'value' => Configure::read('Collibra.type.fieldSet')]]]],
								['Field' => [
									'name' => 'domainId',
									'operator' => 'EQUALS',
									'value' => $vocabulary->resourceId]]]],
						'Order' => [[ /* Yes, intentional [[ there */
							'Field' => [
								'name' => 'name',
								'order' => 'ASC']]]]]]];
		$terms = $this->fullDataTable($termsQuery);

		if ($treeStructure) {
			$sortedTerms = [];
			foreach ($terms as $term) {
				$term->descendantFields = [];
				$sortedTerms[$term->name] = $term;
			}

			$sortedTerms = array_reverse($sortedTerms);

			foreach ($sortedTerms as $name => $term) {
				$term->descendantFields = array_reverse($term->descendantFields);

				$pathArray = explode('.', $name);
				if (count($pathArray) > 1) {
					array_pop($pathArray);
					$fieldsetPath = implode('.', $pathArray);
					array_push($sortedTerms[$fieldsetPath]->descendantFields, $term);

					unset($sortedTerms[$name]);
				}
			}

			return array_reverse($sortedTerms);
		}

		return $terms;
	}

	public function getDatabaseSchemas($databaseName) {
		$tableConfig = ['TableViewConfig' => [
			'Columns' => [
				['Column' => ['fieldName' => 'name']]],
			'Resources' => [
				'Vocabulary' => [
					'Name' => ['name' => 'name'],
					'Meta' => ['name' => 'isMeta'],
					'Community' => [
						'Name' => ['name' => 'databaseName']],
					'Filter' => [
						'AND' => [[
							'Field' => [
								'name' => 'databaseName',
								'operator' => 'EQUALS',
								'value' => $databaseName]],
						[
							'Field' => [
								'name' => 'isMeta',
								'operator' => 'EQUALS',
								'value' => 'false']]]]]]]];

		$results = $this->fullDataTable($tableConfig);
		return $results;
	}

	public function getSchemaTables($databaseName, $schemaName) {
		$tableConfig = ['TableViewConfig' => [
			'Columns' => [
				['Column' => ['fieldName' => 'schemaName']],
				['Column' => ['fieldName' => 'schemaId']],
				['Column' => ['fieldName' => 'databaseName']],
				['Column' => ['fieldName' => 'databaseId']],
				['Group' => [
					'name' => 'tables',
					'Columns' => [
						['Column' => ['fieldName' => 'tableName']],
						['Column' => ['fieldName' => 'tableId']],
						['Column' => ['fieldName' => 'tableDescription']]]]]],
			'Resources' => [
				'Term' => [
					'Id' => ['name' => 'schemaId'],
					'Signifier' => ['name' => 'schemaName'],
					'Vocabulary' => [
						'Community' => [
							'Id' => ['name' => 'databaseId'],
							'Name' => ['name' => 'databaseName'],
							'ParentCommunity' => [
								'Id' => ['name' => 'dataWarehouseId']]]],
					'Relation' => [
						'typeId' => Configure::read('Collibra.relationship.schemaToTable'),
						'type' => 'SOURCE',
						'Target' => [
							'Id' => ['name' => 'tableId'],
							'Signifier' => ['name' => 'tableName'],
							'StringAttribute' => [[
								'Value' => ['name' => 'tableDescription'],
								'labelId' => Configure::read('Collibra.attribute.description')]]]],
					'Filter' => [
						'AND' => [[
							'Field' => [
								'name' => 'schemaName',
								'operator' => 'EQUALS',
								'value' => $schemaName]],
						[
							'Field' => [
								'name' => 'databaseName',
								'operator' => 'EQUALS',
								'value' => $databaseName]],
						[
							'Field' => [
								'name' => 'dataWarehouseId',
								'operator' => 'EQUALS',
								'value' => Configure::read('Collibra.community.dataWarehouse')]]]]]]]];

		$results = $this->fullDataTable($tableConfig);
		return $results[0];
	}

	public function getTableObject($databaseName, $tableName) {
		$tableConfig = ['TableViewConfig' => [
			'Columns' => [
				['Column' => ['fieldName' => 'id']],
				['Column' => ['fieldName' => 'name']],
				['Column' => ['fieldName' => 'description']],
				['Column' => ['fieldName' => 'schemaId']],
				['Column' => ['fieldName' => 'schemaName']],
				['Column' => ['fieldName' => 'databaseId']],
				['Column' => ['fieldName' => 'databaseName']],
				['Column' => ['fieldName' => 'tableAltered']],
				['Column' => ['fieldName' => 'tableAlteredAttrId']],
				['Group' => [
					'name' => 'dataSharingRequests',
					'Columns' => [
						['Column' => ['fieldName' => 'dsrId']],
						['Column' => ['fieldName' => 'dsrName']],
						['Column' => ['fieldName' => 'dsrStatus']],
						['Column' => ['fieldName' => 'dsrStatusId']]]]]],
			'Resources' => [
				'Term' => [
					'Id' => ['name' => 'id'],
					'Signifier' => ['name' => 'name'],
					'StringAttribute' => [[
						'labelId' => Configure::read('Collibra.attribute.description'),
						'Value' => ['name' => 'description']]],
					'BooleanAttribute' => [[
						'labelId' => Configure::read('Collibra.attribute.tableAltered'),
						'Value' => ['name' => 'tableAltered'],
						'Id' => ['name' => 'tableAlteredAttrId']]],
					'Vocabulary' => [
						'Community' => [
							'Id' => ['name' => 'databaseId'],
							'Name' => ['name' => 'databaseName']]],
					'Relation' => [[
						'typeId' => Configure::read('Collibra.relationship.schemaToTable'),
						'type' => 'TARGET',
						'Source' => [
							'Id' => ['name' => 'schemaId'],
							'Signifier' => ['name' => 'schemaName']]],
					[
						'typeId' => Configure::read('Collibra.relationship.DSRtoNecessaryTable'),
						'type' => 'TARGET',
						'Source' => [
							'Id' => ['name' => 'dsrId'],
							'Signifier' => ['name' => 'dsrName'],
							'Status' => [
								'Id' => ['name' => 'dsrStatusId'],
								'Signifier' => ['name' => 'dsrStatus']]]]],
					'Filter' => [
						'AND' => [[
							'Field' => [
								'name' => 'name',
								'operator' => 'EQUALS',
								'value' => $tableName]],
						[
							'Field' => [
								'name' => 'databaseName',
								'operator' => 'EQUALS',
								'value' => $databaseName]]]]]]]];

		$results = $this->fullDataTable($tableConfig);
		if (isset($results[0])) {
			return $results[0];
		}
		return [];
	}

	public function getTableColumns($databaseName, $tableName) {
		$tableConfig = ['TableViewConfig' => [
			'Columns' => [
				['Column' => ['fieldName' => 'columnName']],
				['Column' => ['fieldName' => 'columnId']],
				['Column' => ['fieldName' => 'columnDescription']],
				['Column' => ['fieldName' => 'usageNotes']],
				['Group' => [
					'name' => 'businessTerm',
					'Columns' => [
						['Column' => ['fieldName' => 'termCommunityId']],
						['Column' => ['fieldName' => 'termCommunityName']],
						['Column' => ['fieldName' => 'termVocabularyId']],
						['Column' => ['fieldName' => 'termVocabularyName']],
						['Column' => ['fieldName' => 'termClassification']],
						['Column' => ['fieldName' => 'approvalStatus']],
						['Column' => ['fieldName' => 'termId']],
						['Column' => ['fieldName' => 'termDescription']],
						['Column' => ['fieldName' => 'termRelationId']],
						['Column' => ['fieldName' => 'term']]]]]],
			'Resources' => [
				'Term' => [
					'Id' => ['name' => 'columnId'],
					'Signifier' => ['name' => 'columnName'],
					'StringAttribute' => [[
						'Value' => ['name' => 'columnDescription'],
						'labelId' => Configure::read('Collibra.attribute.description')]],
					'StringAttribute' => [[
							'Value' => ['name' => 'usageNotes'],
							'labelId' => Configure::read('Collibra.attribute.usageNotes')]],
					'Vocabulary' => [
						'Community' => [
							'Name' => ['name' => 'databaseName']]],
					'Relation' => [[
						'typeId' => Configure::read('Collibra.relationship.termToDataAsset'),
						'type' => 'TARGET',
						'Id' => ['name' => 'termRelationId'],
						'Source' => [
							'Id' => ['name' => 'termId'],
							'Signifier' => ['name' => 'term'],
							'Vocabulary' => [
								'Id' => ['name' => 'termVocabularyId'],
								'Name' => ['name' => 'termVocabularyName'],
								'Community' => [
									'Id' => ['name' => 'termCommunityId'],
									'Name' => ['name' => 'termCommunityName']]],
							'Status' => [
								'signifier' => ['name' => 'approvalStatus']],
							'StringAttribute' => [[
								'Value' => ['name' => 'termDescription'],
								'labelId' => Configure::read('Collibra.attribute.definition')]],
							'SingleValueListAttribute' => [[
								'Value' => ['name' => 'termClassification'],
								'labelId' => Configure::read('Collibra.attribute.classification')]]]],
					[
						'typeId' => Configure::read('Collibra.relationship.columnToTable'),
						'type' => 'SOURCE',
						'Target' => [
							'Signifier' => ['name' => 'tableName']]]],
					'Filter' => [
						'AND' => [[
							'Field' => [
								'name' => 'tableName',
								'operator' => 'EQUALS',
								'value' => $tableName]],
						[
							'Field' => [
								'name' => 'databaseName',
								'operator' => 'EQUALS',
								'value' => $databaseName]]]]]]]];

		$results = $this->fullDataTable($tableConfig);
		usort($results, function($a, $b) {
			return strcmp($a->columnName, $b->columnName);
		});
		return $results;
	}

	public function getSamlResponses() {
		$tableConfig = ['TableViewConfig' => [
			'Columns' => [
				['Column' => ['fieldName' => 'responseId']],
				['Column' => ['fieldName' => 'responseName']]],
			'Resources' => [
				'Term' => [
					'Id' => ['name' => 'responseId'],
					'Signifier' => ['name' => 'responseName'],
					'ConceptType' => [
						'Id' => ['name' => 'conceptTypeId']],
					'Vocabulary' => [
						'Community' => [
							'Id' => ['name' => 'communityId'],
							'Name' => ['name' => 'databaseName']]],
					'Filter' => [
						'AND' => [[
							'Field' => [
								'name' => 'communityId',
								'operator' => 'EQUALS',
								'value' => Configure::read('Collibra.community.saml')]],
						[
							'Field' => [
								'name' => 'conceptTypeId',
								'operator' => 'EQUALS',
								'value' => Configure::read('Collibra.type.samlResponse')]]]]]]]];

		$results = $this->fullDataTable($tableConfig);
		return $results;
	}

	public function getSamlResponseObject($responseName) {
		$tableConfig = ['TableViewConfig' => [
			'Columns' => [
				['Column' => ['fieldName' => 'id']],
				['Column' => ['fieldName' => 'name']],
				['Column' => ['fieldName' => 'description']],
				['Column' => ['fieldName' => 'vocabId']]],
				['Column' => ['fieldName' => 'usageNotes']],
			'Resources' => [
				'Term' => [
					'Id' => ['name' => 'id'],
					'Signifier' => ['name' => 'name'],
					'StringAttribute' => [[
						'labelId' => Configure::read('Collibra.attribute.description'),
						'Value' => ['name' => 'description']]],
					'StringAttribute' => [[
						'Value' => ['name' => 'usageNotes'],
						'labelId' => Configure::read('Collibra.attribute.usageNotes')]],
					'ConceptType' => [
						'Id' => ['name' => 'conceptTypeId']],
					'Vocabulary' => [
						'Id' => ['name' => 'vocabId']],
					'Filter' => [
						'AND' => [[
							'Field' => [
								'name' => 'name',
								'operator' => 'EQUALS',
								'value' => $responseName]],
						[
							'Field' => [
								'name' => 'conceptTypeId',
								'operator' => 'EQUALS',
								'value' => Configure::read('Collibra.type.samlResponse')]]]]]]]];

		$results = $this->fullDataTable($tableConfig);
		if (isset($results[0])) {
			return $results[0];
		}
		return [];
	}

	public function getSamlResponseFields($responseName) {
		$tableConfig = ['TableViewConfig' => [
			'Columns' => [
				['Column' => ['fieldName' => 'fieldName']],
				['Column' => ['fieldName' => 'fieldId']],
				['Column' => ['fieldName' => 'fieldDescription']],
				['Group' => [
					'name' => 'businessTerm',
					'Columns' => [
						['Column' => ['fieldName' => 'termCommunityId']],
						['Column' => ['fieldName' => 'termCommunityName']],
						['Column' => ['fieldName' => 'termVocabularyId']],
						['Column' => ['fieldName' => 'termVocabularyName']],
						['Column' => ['fieldName' => 'termClassification']],
						['Column' => ['fieldName' => 'approvalStatus']],
						['Column' => ['fieldName' => 'termId']],
						['Column' => ['fieldName' => 'termDescription']],
						['Column' => ['fieldName' => 'termRelationId']],
						['Column' => ['fieldName' => 'term']]]]]],
			'Resources' => [
				'Term' => [
					'Id' => ['name' => 'fieldId'],
					'Signifier' => ['name' => 'fieldName'],
					'StringAttribute' => [[
						'Value' => ['name' => 'fieldDescription'],
						'labelId' => Configure::read('Collibra.attribute.description')]],
					'Relation' => [[
						'typeId' => Configure::read('Collibra.relationship.termToDataAsset'),
						'type' => 'TARGET',
						'Id' => ['name' => 'termRelationId'],
						'Source' => [
							'Id' => ['name' => 'termId'],
							'Signifier' => ['name' => 'term'],
							'Vocabulary' => [
								'Id' => ['name' => 'termVocabularyId'],
								'Name' => ['name' => 'termVocabularyName'],
								'Community' => [
									'Id' => ['name' => 'termCommunityId'],
									'Name' => ['name' => 'termCommunityName']]],
							'Status' => [
								'signifier' => ['name' => 'approvalStatus']],
							'StringAttribute' => [[
								'Value' => ['name' => 'termDescription'],
								'labelId' => Configure::read('Collibra.attribute.definition')]],
							'SingleValueListAttribute' => [[
								'Value' => ['name' => 'termClassification'],
								'labelId' => Configure::read('Collibra.attribute.classification')]]]],
					[
						'typeId' => Configure::read('Collibra.relationship.fieldToSaml'),
						'type' => 'TARGET',
						'Source' => [
							'Signifier' => ['name' => 'responseName']]]],
					'Filter' => [
						'AND' => [[
							'Field' => [
								'name' => 'responseName',
								'operator' => 'EQUALS',
								'value' => $responseName]]]]]]]];

		$results = $this->fullDataTable($tableConfig);
		usort($results, function($a, $b) {
			return strcmp($a->fieldName, $b->fieldName);
		});
		return $results;
	}

	public function getDremioDatasets() {
		$tableConfig = ['TableViewConfig' => [
			'Columns' => [
				['Column' => ['fieldName' => 'datasetId']],
				['Column' => ['fieldName' => 'datasetName']],
				['Column' => ['fieldName' => 'statusId']],
				['Column' => ['fieldName' => 'datasetVocabId']]],
			'Resources' => [
				'Term' => [
					'Id' => ['name' => 'datasetId'],
					'Signifier' => ['name' => 'datasetName'],
					'Status' => [
						'Id' => ['name' => 'statusId'],
						'Signifier' => ['name' => 'status']],
					'ConceptType' => [
						'Id' => ['name' => 'assetTypeId']],
					'Vocabulary' => [
						'Id' => ['name' => 'datasetVocabId'],
						'Community' => [
							'Id' => ['name' => 'communityId']]],
					'Filter' => [
						'AND' => [[
							'Field' => [
								'name' => 'assetTypeId',
								'operator' => 'EQUALS',
								'value' => Configure::read('Collibra.type.dataset')]],
						[
							'Field' => [
								'name' => 'communityId',
								'operator' => 'EQUALS',
								'value' => Configure::read('Collibra.community.virtualDatasets')]]]]]]]];

		$results = $this->fullDataTable($tableConfig);
		return $results;
	}

	public function getDremioDatasetDetails($datasetId, $full = false, $nameLookup = false) {
		$tableConfig = ['TableViewConfig' => [
			'Columns' => [
				['Column' => ['fieldName' => 'datasetId']],
				['Column' => ['fieldName' => 'datasetName']],
				['Column' => ['fieldName' => 'usageNotes']],
				['Group' => [
					'name' => 'tables',
					'Columns' => [
						['Column' => ['fieldName' => 'tableId']],
						['Column' => ['fieldName' => 'tableName']],
						['Column' => ['fieldName' => 'tableRelId']]]]]],
			'Resources' => [
				'Term' => [
					'Id' => ['name' => 'datasetId'],
					'Signifier' => ['name' => 'datasetName'],
					'ConceptType' => [
						'Id' => ['name' => 'assetTypeId']],
					'StringAttribute' => [[
						'Value' => ['name' => 'usageNotes'],
						'labelId' => Configure::read('Collibra.attribute.usageNotes')]],
					'Relation' => [[
						'typeId' => Configure::read('Collibra.relationship.datasetToVirtualTable'),
						'type' => 'SOURCE',
						'Id' => ['name' => 'tableRelId'],
						'Target' => [
							'Id' => ['name' => 'tableId'],
							'Signifier' => ['name' => 'tableName']]]],
					'Filter' => [
						'AND' => [[
							'Field' => [
								'name' => 'datasetId',
								'operator' => 'EQUALS',
								'value' => $datasetId]],
						[
							'Field' => [
								'name' => 'assetTypeId',
								'operator' => 'EQUALS',
								'value' => Configure::read('Collibra.type.dataset')]]]]]]]];

		if ($nameLookup) $tableConfig['TableViewConfig']['Resources']['Term']['Filter']['AND'][0]['Field']['name'] = 'datasetName';

		$results = $this->fullDataTable($tableConfig);

		if (!$full) {
			return $results;
		} else {
			for ($i = 0; $i < sizeof($results[0]->tables); $i++) {
				$results[0]->tables[$i] = $this->getVirtualTable($results[0]->tables[$i]->tableId);
				$results[0]->tables[$i]->columns = $this->getVirtualTableColumns($results[0]->tables[$i]->id);
			}
			return $results[0];
		}
	}

	public function getVirtualTable($tableId) {
		$tableConfig = ['TableViewConfig' => [
			'Columns' => [
				['Column' => ['fieldName' => 'id']],
				['Column' => ['fieldName' => 'name']],
				['Column' => ['fieldName' => 'description']],
				['Column' => ['fieldName' => 'folderId']],
				['Column' => ['fieldName' => 'folderName']],
				['Column' => ['fieldName' => 'spaceId']],
				['Column' => ['fieldName' => 'spaceName']]],
			'Resources' => [
				'Term' => [
					'Id' => ['name' => 'id'],
					'Signifier' => ['name' => 'name'],
					'StringAttribute' => [[
						'labelId' => Configure::read('Collibra.attribute.description'),
						'Value' => ['name' => 'description']]],
					'Relation' => [[
						'typeId' => Configure::read('Collibra.relationship.folderToVirtualTable'),
						'type' => 'TARGET',
						'Source' => [
							'Id' => ['name' => 'folderId'],
							'Signifier' => ['name' => 'folderName']]],
					[
						'typeId' => Configure::read('Collibra.relationship.spaceToVirtualTable'),
						'type' => 'TARGET',
						'Source' => [
							'Id' => ['name' => 'spaceId'],
							'Signifier' => ['name' => 'spaceName']]]],
					'Filter' => [
						'AND' => [[
							'Field' => [
								'name' => 'id',
								'operator' => 'EQUALS',
								'value' => $tableId]]]]]]]];

		$results = $this->fullDataTable($tableConfig);
		return $results[0];
	}

	public function getVirtualTableColumns($tableId) {
		$tableConfig = ['TableViewConfig' => [
			'Columns' => [
				['Column' => ['fieldName' => 'columnId']],
				['Column' => ['fieldName' => 'columnName']],
				['Column' => ['fieldName' => 'columnDescription']],
				['Group' => [
					'name' => 'businessTerm',
					'Columns' => [
						['Column' => ['fieldName' => 'termCommunityId']],
						['Column' => ['fieldName' => 'termCommunityName']],
						['Column' => ['fieldName' => 'termVocabularyId']],
						['Column' => ['fieldName' => 'termVocabularyName']],
						['Column' => ['fieldName' => 'termClassification']],
						['Column' => ['fieldName' => 'approvalStatus']],
						['Column' => ['fieldName' => 'termId']],
						['Column' => ['fieldName' => 'termDescription']],
						['Column' => ['fieldName' => 'termRelationId']],
						['Column' => ['fieldName' => 'term']]]]]],
			'Resources' => [
				'Term' => [
					'Id' => ['name' => 'columnId'],
					'Signifier' => ['name' => 'columnName'],
					'StringAttribute' => [[
						'Value' => ['name' => 'columnDescription'],
						'labelId' => Configure::read('Collibra.attribute.description')]],
					'Relation' => [[
						'typeId' => Configure::read('Collibra.relationship.termToDataAsset'),
						'type' => 'TARGET',
						'Id' => ['name' => 'termRelationId'],
						'Source' => [
							'Id' => ['name' => 'termId'],
							'Signifier' => ['name' => 'term'],
							'Vocabulary' => [
								'Id' => ['name' => 'termVocabularyId'],
								'Name' => ['name' => 'termVocabularyName'],
								'Community' => [
									'Id' => ['name' => 'termCommunityId'],
									'Name' => ['name' => 'termCommunityName']]],
							'Status' => [
								'signifier' => ['name' => 'approvalStatus']],
							'StringAttribute' => [[
								'Value' => ['name' => 'termDescription'],
								'labelId' => Configure::read('Collibra.attribute.definition')]],
							'SingleValueListAttribute' => [[
								'Value' => ['name' => 'termClassification'],
								'labelId' => Configure::read('Collibra.attribute.classification')]]]],
					[
						'typeId' => Configure::read('Collibra.relationship.columnToVirtualTable'),
						'type' => 'SOURCE',
						'Target' => [
							'Id' => ['name' => 'tableId']]]],
					'Filter' => [
						'AND' => [[
							'Field' => [
								'name' => 'tableId',
								'operator' => 'EQUALS',
								'value' => $tableId]]]]]]]];

		$columns = $this->fullDataTable($tableConfig);
		usort($columns, function($a, $b) {
			return strcmp($a->columnName, $b->columnName);
		});
		return $columns;
	}

	public function getBusinessTermDetails($arrQueuedBusinessTerms) {
		if (empty($arrQueuedBusinessTerms)) {
			return [];
		}

		$tableConfig = ['TableViewConfig' => [
			'Columns' => [
				['Column' => ['fieldName' => 'createdOn']],
				['Column' => ['fieldName' => 'termrid']],
				['Column' => ['fieldName' => 'termsignifier']],
				['Column' => ['fieldName' => 'concept']],
				['Column' => ['fieldName' => 'statusname']],
				['Column' => ['fieldName' => 'statusrid']],
				['Column' => ['fieldName' => 'communityname']],
				['Column' => ['fieldName' => 'commrid']],
				['Column' => ['fieldName' => 'domainname']],
				['Column' => ['fieldName' => 'domainrid']],
				['Column' => ['fieldName' => 'concepttypename']],
				['Column' => ['fieldName' => 'concepttyperid']]],
			'Resources' => [
				'Term' => [
					'CreatedOn' => ['name' => 'createdOn'],
					'Id' => ['name' => 'termrid'],
					'Signifier' => ['name' => 'termsignifier'],
					'BooleanAttribute' => [[
						'Value' => ['name' => 'concept'],
						'labelId' => Configure::read('Collibra.attribute.concept')]],
					'Status' => [
						'Signifier' => ['name' => 'statusname'],
						'Id' => ['name' => 'statusrid']],
					'Vocabulary' => [
						'Community' => [
							'Name' => ['name' => 'communityname'],
							'Id' => ['name' => 'commrid']],
						'Name' => ['name' => 'domainname'],
						'Id' => ['name' => 'domainrid']],
					'ConceptType' => [[
						'Signifier' => ['name' => 'concepttypename'],
						'Id' => ['name' => 'concepttyperid']]],
					'Filter' => [
						'AND' => [[
							'OR' => []]]],
					'Order' => [[
						'Field' => [
							'name' => 'termsignifier',
							'order' => 'ASC']]]]],
			'displayStart' => 0,
			'displayLength' => 100]];

		foreach ($arrQueuedBusinessTerms as $termId => $term) {
			array_push(
				$tableConfig['TableViewConfig']['Resources']['Term']['Filter']['AND'][0]['OR'],
				['Field' => [
					'name' => 'termrid',
					'operator' => 'EQUALS',
					'value' => $termId]]
				);
		}

		$termResp = $this->fullDataTable($tableConfig);
		return $termResp;
	}

	public function searchTerms($query, $limit = 10, $communities = []) {
		if (empty($communities)) {
			$communities = [Configure::read('Collibra.community.byu'), Configure::read('Collibra.community.dataGovernanceCouncil')];
		}
		if (substr($query, -1) !== '*') {
			$query .= '*';
		}
		$request = [
			'query' => $query,
			'filter' => [
				'community' => $communities,
				'category' => ['TE'],
				'type' => [
					'asset' => [
						'00000000-0000-0000-0000-000000011001',
						Configure::read('Collibra.type.synonym')]],
				'includeMeta' => true],
			'fields' => ['name', 'attributes'],
			'order' => [
				'by' => 'score',
				'sort' => 'desc'],
			'limit' => $limit,
			'offset' => 0,
			'highlight' => false,
			'relativeUrl' => true,
			'withParents' => true
		];
		if(!Configure::read('allowUnapprovedTerms')){
			$request['filter']['status'] = '00000000-0000-0000-0000-000000005009';
		}

		$resp = $this->postJSON('search', json_encode($request));
		return json_decode($resp);
	}

	public function searchOnPreviouslyMatchedFields($query) {
		$queryCommaAfter = $query.',';
		$queryCommaBefore = ', '.$query;
		$queryCommaSurrounded = ', '.$query.',';

		$tableConfig = ['TableViewConfig' => [
			'Columns' => [
				['Column' => ['fieldName' => 'id']],
				['Column' => ['fieldName' => 'name']],
				['Column' => ['fieldName' => 'definition']],
				['Column' => ['fieldName' => 'vocabName']]],
			'Resources' => [
				'Term' => [
					'Id' => ['name' => 'id'],
					'Signifier' => ['name' => 'name'],
					'StringAttribute' => [[
						'Value' => ['name' => 'previouslyMatchedFieldNames'],
						'labelId' => Configure::read('Collibra.attribute.previouslyMatchedFieldNames')],
					[
						'Value' => ['name' => 'definition'],
						'labelId' => Configure::read('Collibra.attribute.definition')]],
					'Vocabulary' => [
						'Name' => ['name' => 'vocabName']],
					'Filter' => [
						'AND' => [
							['OR' => [
								['Field' => [
									'name' => 'previouslyMatchedFieldNames',
									'operator' => 'EQUALS',
									'value' => $query]],
								['Field' => [
									'name' => 'previouslyMatchedFieldNames',
									'operator' => 'STARTS_WITH',
									'value' => $queryCommaAfter]],
								['Field' => [
									'name' => 'previouslyMatchedFieldNames',
									'operator' => 'ENDS_WITH',
									'value' => $queryCommaBefore]],
								['Field' => [
									'name' => 'previouslyMatchedFieldNames',
									'operator' => 'INCLUDES',
									'value' => $queryCommaSurrounded]]]]]]]],
			'displayLength' => 10]];

		$results = $this->fullDataTable($tableConfig);
		return $results;
	}

	public function getTerms($vocabularyId = null, $passedOptions = []) {
		$defaults = [
			'sortField' => 'termsignifier',
			'sortOrder' => 'ASC',
			'start' => 0,
			'length' => 1000];
		$options = array_merge($defaults, $passedOptions);

		$tableConfig = ["TableViewConfig" => [
			"Columns" => [
				["Column" => ["fieldName" => "termrid"]],
				["Column" => ["fieldName" => "termsignifier"]],
				["Column" => ["fieldName" => "standardFieldName"]],
				["Column" => ["fieldName" => "description"]],
				["Column" => ["fieldName" => "descriptiveExample"]],
				["Column" => ["fieldName" => "lastModified"]],
				["Column" => ["fieldName" => "domainrid"]],
				["Column" => ["fieldName" => "domainname"]],
				["Column" => ["fieldName" => "concept"]],
				["Column" => ["fieldName" => "classification"]],
				["Column" => ["fieldName" => "commrid"]],
				["Column" => ["fieldName" => "communityname"]],
				["Column" => ["fieldName" => "statusname"]],
				["Group" => [
					"Columns" => [
						["Column" => ["fieldName" => "userRole00000000000000000000000000005016fn"]],
						["Column" => ["fieldName" => "userRole00000000000000000000000000005016ln"]]],
					"name" => "Role00000000000000000000000000005016"]],
				["Group" => [
					"name" => "synonym_for",
					"Columns" => [
						["Column" => ["fieldName" => "synonymname"]],
						["Column" => ["fieldName" => "synonymrelid"]],
						["Column" => ["fieldName" => "synonymid"]]]]],
				["Group" => [
					"name" => "representing_apifields",
					"Columns" => [
						["Column" => ["fieldName" => "apifield"]],
						["Column" => ["fieldName" => "fieldrid"]]]]],
				["Column" => ["fieldName" => "notes"]]],
			"Resources" => [
				"Term" => [
					"Id" => ["name" => "termrid"],
					"Signifier" => ["name" => "termsignifier"],
					"StringAttribute" => [[
						"Value" => ["name" => "standardFieldName"],
						"labelId" => Configure::read('Collibra.attribute.standardFieldName')],
					[
						"Value" => ["name" => "description"],
						"labelId" => Configure::read('Collibra.attribute.definition')],
					[
						"Value" => ["name" => "descriptiveExample"],
						"labelId" => Configure::read('Collibra.attribute.descriptiveExample')],
					[
						"Value" => ["name" => "notes"],
						"labelId" => Configure::read('Collibra.attribute.notes')]],
					"LastModified" => ["name" => "lastModified"],
					"BooleanAttribute" => [[
						"Value" => ["name" => "concept"],
						"labelId" => Configure::read('Collibra.attribute.concept')]],
					"SingleValueListAttribute" => [[
						"Value" => ["name" => "classification"],
						"labelId" => Configure::read('Collibra.attribute.classification')]],
					"Status" => [
						"Signifier" => ["name" => "statusname"]],
					"Vocabulary" => [
						"Community" => [
							"Name" => ["name" => "communityname"],
							"Id" => ["name" => "commrid"]],
						"Id" => ["name" => "domainrid"],
						"Name" => ["name" => "domainname"]],
					"ConceptType" => [[
						"Signifier" => ["name" => "concepttypename"]]],
					"Member" => [[
						"User" => [
							"FirstName" => ["name" => "userRole00000000000000000000000000005016fn"],
							"LastName" => ["name" => "userRole00000000000000000000000000005016ln"]],
						"Role" => ["name" => "Role00000000000000000000000000005016"],
						"roleId" => "00000000-0000-0000-0000-000000005016"]],
					"Relation" => [[
						"typeId" => Configure::read('Collibra.relationship.termToSynonym'),
						"type" => "TARGET",
						"Id" => ["name" => "synonymrelid"],
						"Source" => [
							"Id" => ["name" => "synonymid"],
							"Signifier" => ["name" => "synonymname"]]],
					[
						"typeId" => Configure::read('Collibra.relationship.termToDataAsset'),
						"type" => "SOURCE",
						"Target" => [
							"Signifier" => ["name" => "apifield"],
							"Id" => ["name" => "fieldrid"]]]],
					"Filter" => [
						"AND" => [
							["OR" => [
								["Field" => [
									"name" => "concepttypename",
									"operator" => "INCLUDES",
									"value" => "Business Term"]],
								["Field" => [
									"name" => "concepttypename",
									"operator" => "INCLUDES",
									"value" => "Synonym"]]]]]],
					"Order" => [
						["Field" => [
							"name" => $options['sortField'],
							"order" => $options['sortOrder']]]]]],
			"displayStart" => $options['start'],
			"displayLength" => $options['length']]];
		if (!empty($vocabularyId)) {
			$tableConfig['TableViewConfig']['Resources']['Term']['Filter']['AND'][] = [
				'AND' =>
					[['Field' => [
						'name' => 'domainrid',
						'operator' => 'EQUALS',
						'value' => $vocabularyId]]]];
		}
		if (!empty($options['additionalFilters'])) {
			foreach($options['additionalFilters'] as $filter) {
				if (!empty($filter)) {
					$tableConfig['TableViewConfig']['Resources']['Term']['Filter']['AND'][] = $filter;
				}
			}
		}
		if(!Configure::read('allowUnapprovedTerms')){
			$tableConfig['TableViewConfig']['Resources']['Term']['Filter']['AND'][] = [
				['Field' => [
					'name' => 'statusname',
					'operator' => 'EQUALS',
					'value' => 'Accepted']]];
		}

		return $this->dataTable($tableConfig);
	}

	public function getTermDefinition($termId) {
		$termInfo = $this->get("term/{$termId}", ['json' => true]);
		$definitionAttributeId = Configure::read('Collibra.attribute.definition');
		if (!empty($termInfo->attributeReferences->attributeReference)) {
			foreach ($termInfo->attributeReferences->attributeReference as $attribute) {
				if ($attribute->labelReference->resourceId == $definitionAttributeId) {
					return $attribute->value;
				}
			}
		}
		return null;
	}

	public function importSwagger($swagger) {
		$hostCommunity = $this->findTypeByName('community', $swagger['host']);
		if (empty($hostCommunity->resourceId)) {
			$this->errors[] = "Host \"{$swagger['host']}\" does not exist in Collibra";
			return false;
		}

		$vocabulary = $this->get("vocabulary/name/full?name={$swagger['basePath']}/{$swagger['version']}", ['json' => true])->vocabulary;
		$existentTerms = [];
		if (!empty($vocabulary)) {
			$vocabularyId = $vocabulary[0]->resourceId;
			$createdVocabulary = false;
			$vocabularyTerms = $this->getApiFields($swagger['host'], "{$swagger['basePath']}/{$swagger['version']}");
			foreach ($vocabularyTerms as $term) {
				$existentTerms[$term->id] = $term->name;
			}
		}
		else {
			$vocabularyId = $this->createVocabulary("{$swagger['basePath']}/{$swagger['version']}", $hostCommunity->resourceId);
			$createdVocabulary = true;
			if (empty($vocabularyId)) {
				$this->errors[] = "Unable to create vocabulary \"{$swagger['basePath']}/{$swagger['version']}\" in community \"{$swagger['host']}\"";
				return false;
			}
		}

		if (isset($swagger['destructiveUpdate'])) {
			$toDeleteIds = [];
			foreach ($existentTerms as $exId => $exName) {
				if (!in_array($exName, array_column($swagger['elements'], 'name'))) {
					array_push($toDeleteIds, $exId);
					unset($existentTerms[$exId]);
				}
			}
			$this->deleteJSON('term/remove/async', $this->prepData(['resource' => $toDeleteIds]));
		}

		$fields = [];
		$fieldSets = [];
		$elements = [];
		foreach ($swagger['elements'] as $element) {
			if (empty($element['name'])) {
				continue;
			}
			$name = trim($element['name']);
			if (empty($name)) {
				continue;
			}
			$elements[$name] = $element;
			if (in_array($name, $existentTerms)) {
				continue;
			}
			if (!empty($element['type']) && $element['type'] == 'fieldset') {
				$fieldSets[$name] = $name;
			} else {
				$fields[$name] = $name;
			}
		}
		//Create API object
		if ($createdVocabulary) {
			$apiResult = $this->addTermsToVocabulary($vocabularyId, Configure::read('Collibra.type.api'), ["{$swagger['basePath']}/{$swagger['version']}"]);
			if (empty($apiResult) || !$apiResult->isOk()) {
				$this->errors[] = "Error creating an object representing \"{$swagger['basePath']}/{$swagger['version']}\"";
				if ($createdVocabulary) $this->deleteVocabulary($vocabularyId);
				return false;
			}
			$apiResult = json_decode($apiResult);
			$BYUAPI = ClassRegistry::init('BYUAPI');
			$linksResponse = $BYUAPI->deepLinks($swagger['basePath'].'/'.$swagger['version']);
			if (!empty($linksResponse)) {
				$postData['label'] = Configure::read('Collibra.attribute.apiStoreLink');
				$postData['value'] = '<a class="link" href="'.$linksResponse['link'].'" target="_blank">'.$linksResponse['link'].'</a>';
				$postString = http_build_query($postData);
				$resp = $this->post('term/'.$apiResult->termReference[0]->resourceId.'/attributes', $postString);
			}
			if ($swagger['authorizedByFieldset']) {
				$postData['label'] = Configure::read('Collibra.attribute.authorizedByFieldset');
				$postData['value'] = 'true';
				$postString = http_build_query($postData);
				$resp = $this->post('term/'.$apiResult->termReference[0]->resourceId.'/attributes', $postString);
			}
		}
		//Add fields
		if (!empty($fields)) {
			$fieldsResult = $this->addTermsToVocabulary($vocabularyId, Configure::read('Collibra.type.field'), $fields);
			if (empty($fieldsResult) || !$fieldsResult->isOk()) {
				$this->errors[] = "Error adding fields to \"{$swagger['basePath']}/{$swagger['version']}\"";
				if ($createdVocabulary) $this->deleteVocabulary($vocabularyId);
				return false;
			}
		}
		if (!empty($fieldSets)) {
			$fieldSetsResult = $this->addTermsToVocabulary($vocabularyId, Configure::read('Collibra.type.fieldSet'), $fieldSets);
			if (empty($fieldSetsResult) || !$fieldSetsResult->isOk()) {
				$this->errors[] = "Error adding fieldSets to \"{$swagger['basePath']}/{$swagger['version']}\"";
				if ($createdVocabulary) $this->deleteVocabulary($vocabularyId);
				return false;
			}
		}

		//Link created terms to selected Business Terms
		$wfPostData = [
			'relationTypeId' => Configure::read('Collibra.relationship.termToDataAsset'),
			'source' => [],
			'target' => []
		];
		foreach (['fieldsResult', 'fieldSetsResult'] as $result) {
			if (empty(${$result})) {
				continue;
			}
			$terms = json_decode(${$result}->body());
			if (empty($terms->termReference)) {
				continue;
			}
			foreach ($terms->termReference as $term) {
				if (empty($term->signifier) || empty($term->resourceId) || empty($elements[$term->signifier]['business_term'])) {
					continue;
				}
				array_push($wfPostData['source'], $elements[$term->signifier]['business_term']);
				array_push($wfPostData['target'], $term->resourceId);
			}
		}
		//Link already existent terms to Business Terms, if selected
		foreach ($existentTerms as $id => $signifier) {
			if (empty($elements[$signifier]['business_term'])) {
				continue;
			}
			array_push($wfPostData['source'], $elements[$signifier]['business_term']);
			array_push($wfPostData['target'], $id);
		}

		$resp = $this->post('workflow/'.Configure::read('Collibra.workflow.createBusinessTermRelations').'/start', $this->prepData($wfPostData));

		return true;
	}

	public function addTermsToVocabulary($vocabularyId, $conceptType, $terms) {
		if (empty($terms)) {
			return true;
		}
		$query = http_build_query(['vocabulary' => $vocabularyId, 'conceptType' => $conceptType]);
		//For array data, PHP's http_build_query creates query/POST string in a format Collibra doesn't like,
		//so we're manually building the remaining POST string
		foreach ($terms as $term) {
			if (empty($term)) {
				continue;
			}
			$query .= '&signifier=' . urlencode($term);
		}
		return $this->post('term/multiple', $query);
	}

	public function updateBusinessTermLinks($fields) {
		$wfPostData = [
			'relationTypeId' => Configure::read('Collibra.relationship.termToDataAsset'),
			'source' => [],
			'target' => []
		];
		foreach ($fields as $field) {
			if (empty($field['id'])) {
				continue;
			}
			if (isset($field['new'])) {
				$glossary = !empty($field['propGlossary']) ? $field['propGlossary'] : Configure::read('Collibra.vocabulary.newBusinessTerms');
				$resp = $this->post('term', [
					'vocabulary' => $glossary,
					'signifier' => $field['propName'],
					'conceptType' => Configure::read('Collibra.type.term')
				]);
				$resp = json_decode($resp);

				$termId = $resp->resourceId;
				$field['business_term'] = $termId;

				$postString = http_build_query([
					'label' => Configure::read('Collibra.attribute.definition'),
					'value' => empty($field['propDefinition']) ? '(Definition pending.)' : $field['propDefinition']
				]);
				$resp = $this->post('term/'.$termId.'/attributes', $postString);
			}
			if (isset($field['previous_business_term'])) {
				if ($field['previous_business_term'] == $field['business_term']) {
					continue;
				}
				$this->delete("relation/{$field['previous_business_term_relation']}");
			}
			if (empty($field['business_term'])) {
				continue;
			}
			array_push($wfPostData['source'], $field['business_term']);
			array_push($wfPostData['target'], $field['id']);
		}

		if (empty($wfPostData['source']) && empty($wfPostData['target'])) {
			return true;
		}

		$resp = $this->post('workflow/'.Configure::read('Collibra.workflow.createBusinessTermRelations').'/start', $this->prepData($wfPostData));

		return true;
	}

	public function findTypeByName($type, $name, $options = []) {
		$query = ['searchName' => $name];
		if (array_key_exists('parent', $options)) {
			if (!empty($options['parent'])) {
				$query['community'] = $options['parent'];
			}
		} else {
			//default restricted to API community
			//override to no parent by passing option ['parent' => false]
			$query['community'] = Configure::read('Collibra.community.api');
		}

		$key = $type;
		$url = $type . '/find';
		if (empty($options['full'])) {
			$key .= 'Reference';
		} else {
			$url .= '/full';
		}

		$search = $this->get($url . '?' . http_build_query($query), ['json' => true]);
		if (empty($search) || empty($search->{$key})) {
			return null;
		}

		$match = @array_column($search->{$key}, null, 'name')[$name];
		if (!$match && $type == 'vocabulary') {
			//Slightly looser matching, ignoring leading or trailing "/" character
			foreach ($search->{$key} as $item) {
				if (!empty($item->name) && trim($item->name, "\t\r\n\0\x0B/") == trim($name, "\t\r\n\0\x0B/")) {
					$match = $item;
					break;
				}
			}
		}
		return $match;
	}

	public function createVocabulary($name, $communityId) {
		$success = $this->post('vocabulary', [
			'name' => $name,
			'community' => $communityId,
			'type' => Configure::read('Collibra.dataAssetDomainTypeId')
		]);
		if (empty($success)) {
			return false;
		}
		if (!$success->isOk()) {
			if (strpos($success->body(), 'already exists') !== false) {
				$this->errors[] = "Vocabulary \"{$name}\" already exists";
			}
			return false;
		}

		$response = json_decode($success->body());
		return (empty($response) || empty($response->resourceId)) ? false : $response->resourceId;
	}

	public function deleteVocabulary($vocabularyId) {
		$response = $this->post(
			'vocabulary/remove/async',
			['resource' => $vocabularyId],
			['method' => 'DELETE']);
	}

	public function getSubcommunity($community) {
		$fullUrl = substr($this->settings['url'], 0, -7) . '2.0/outputModule/export/json';
		$results = $this->postJSON(
			$fullUrl,
			'{"TableViewConfig":{"Columns":[{"Community":{"name":"Subcommunities","Columns":[{"Column":{"fieldName":"subcommunityid"}},{"Column":{"fieldName":"subcommunity"}},{"Column":{"fieldName":"hasNonMetaChildren"}}]}}],"Resources":{"Community":{"Id":{"name":"subcommunityid"},"Name":{"name":"subcommunity"},"Meta":{"name":"issubmeta"},"hasNonMetaChildren":{"name":"hasNonMetaChildren"},"ParentCommunity":{"Id":{"name":"parentCommunityId"}},"Filter":{"AND":[{"AND":[{"Field":{"name":"issubmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"parentCommunityId","operator":"EQUALS","value":"'.$community.'"}}]}]},"Order":[{"Field":{"name":"subcommunity","order":"ASC"}}]}},"displayStart":0,"displayLength":100,"generalConceptId":"'.Configure::read('Collibra.community.byu').'"}}',
			[],
			true
		);
		return $results;
	}

	public function getCommunityVocabularies($community) {
		$fullUrl = substr($this->settings['url'], 0, -7) . '2.0/outputModule/export/json';
		$results = $this->postJSON(
			$fullUrl,
			'{"TableViewConfig":{"Columns":[{"Vocabulary":{"name":"Vocabularies","Columns":[{"Column":{"fieldName":"vocabulary"}},{"Column":{"fieldName":"vocabularyid"}}]}}],"Resources":{"Vocabulary":{"Name":{"name":"vocabulary"},"Id":{"name":"vocabularyid"},"Meta":{"name":"isvocmeta"},"Community":{"Id":{"name":"vocabularyParentCommunityId"}},"Filter":{"AND":[{"AND":[{"Field":{"name":"isvocmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"vocabularyParentCommunityId","operator":"EQUALS","value":"'.$community.'"}}]},{"AND":[{"Field":{"name":"vocabulary","operator":"INCLUDES","value":"Glossary"}}]}]},"Order":[{"Field":{"name":"vocabulary","order":"ASC"}}]}},"displayStart":0,"displayLength":100,"generalConceptId":"'.Configure::read('Collibra.community.byu').'"}}',
			[],
			true
		);
		return $results;
	}


	public function getAllCommunities() {
		$fullUrl = substr($this->settings['url'], 0, -7) . '2.0/outputModule/export/json';
		$resp = $this->postJSON(
			$fullUrl,
			'{"TableViewConfig":{"Columns":[{"Community":{"name":"Subcommunities","Columns":[{"Column":{"fieldName":"subcommunityid"}},{"Column":{"fieldName":"subcommunity"}},{"Column":{"fieldName":"parentCommunityId"}},{"Column":{"fieldName":"hasNonMetaChildren"}},{"Column":{"fieldName":"description"}},{"Group":{"Columns":[{"Column":{"label":"Custodian User ID","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},{"Column":{"label":"Custodian Gender","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"}},{"Column":{"label":"Custodian First Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"}},{"Column":{"label":"Custodian Last Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb"}},{"Group":{"Columns":[{"Column":{"label":"Custodian Group ID","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},{"Column":{"label":"Custodian Group Name","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acrid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acgender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acfn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acln"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747ac"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acg"}},{"Group":{"Columns":[{"Column":{"label":"Trustee User ID","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411rid"}},{"Column":{"label":"Trustee Gender","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411gender"}},{"Column":{"label":"Trustee First Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411fn"}},{"Column":{"label":"Trustee Last Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411ln"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411"}},{"Group":{"Columns":[{"Column":{"label":"Trustee Group ID","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411grid"}},{"Column":{"label":"Trustee Group Name","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411ggn"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411g"}}]}}],"Resources":{"Community":{"Id":{"name":"subcommunityid"},"Name":{"name":"subcommunity"},"Meta":{"name":"issubmeta"},"hasNonMetaChildren":{"name":"hasNonMetaChildren"},"Description":{"name":"description"},"ParentCommunity":{"Id":{"name":"parentCommunityId"}},"Member":[{"User":{"Gender":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"},"FirstName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"},"Id":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"},"LastName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbsig"},"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb","Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},"roleId":"' . Configure::read('Collibra.role.custodian') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"},"Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"Group":{"GroupName":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"},"Id":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"roleId":"' . Configure::read('Collibra.role.custodian') . '"},{"User":{"Gender":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acgender"},"FirstName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acfn"},"Id":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acrid"},"LastName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acln"}},"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acsig"},"name":"Role8a0a6c89106c4adb9936f09f29b747ac","Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acrid"}},"roleId":"' . Configure::read('Collibra.role.steward') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acg"},"Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"Group":{"GroupName":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"},"Id":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"roleId":"' . Configure::read('Collibra.role.steward') . '"},{"User":{"Gender":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411gender"},"FirstName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411fn"},"Id":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411rid"},"LastName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411ln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411sig"},"name":"Rolefb97305e00c0459a84cbb3f5ea62d411","Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411rid"}},"roleId":"' . Configure::read('Collibra.role.trustee') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411g"},"Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411grid"}},"Group":{"GroupName":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411ggn"},"Id":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411grid"}},"roleId":"' . Configure::read('Collibra.role.trustee') . '"}],"Filter":{"AND":[{"AND":[{"Field":{"name":"issubmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"parentCommunityId","operator":"NOT_NULL"}}]}]},"Order":[{"Field":{"name":"subcommunity","order":"ASC"}}]}},"displayStart":0,"displayLength":500}}',
			[],
			true
		);
		$json = json_decode($resp);
		return $json->aaData;
	}

	public function getAllVocabularies() {
		$fullUrl = substr($this->settings['url'], 0, -7) . '2.0/outputModule/export/json';
		$resp = $this->postJSON(
			$fullUrl,
			'{"TableViewConfig":{"Columns":[{"Vocabulary":{"name":"Vocabularies","Columns":[{"Column":{"fieldName":"vocabulary"}},{"Column":{"fieldName":"vocabularyid"}},{"Column":{"fieldName":"vocabularyParentCommunityId"}},{"Group":{"Columns":[{"Column":{"label":"Custodian User ID","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXrid"}},{"Column":{"label":"Custodian Gender","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXgender"}},{"Column":{"label":"Custodian First Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXfn"}},{"Column":{"label":"Custodian Last Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXln"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Custodian Group ID","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXrid"}},{"Column":{"label":"Custodian Group Name","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXgn"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXrid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXgender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXfn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXln"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXrid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXgn"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Trustee User ID","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXrid"}},{"Column":{"label":"Trustee Gender","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXgender"}},{"Column":{"label":"Trustee First Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXfn"}},{"Column":{"label":"Trustee Last Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXln"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Trustee Group ID","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXrid"}},{"Column":{"label":"Trustee Group Name","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXgn"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIX"}}]}}],"Resources":{"Vocabulary":{"Name":{"name":"vocabulary"},"Id":{"name":"vocabularyid"},"Meta":{"name":"isvocmeta"},"Community":{"Id":{"name":"vocabularyParentCommunityId"}},"Member":[{"User":{"Gender":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXgender"},"FirstName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXfn"},"Id":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXrid"},"LastName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXsig"},"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIX","Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.custodian') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXgn"},"Id":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.custodian') . '"},{"User":{"Gender":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXgender"},"FirstName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXfn"},"Id":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXrid"},"LastName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXsig"},"name":"Role8a0a6c89106c4adb9936f09f29b747acVOCSUFFIX","Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.steward') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXgn"},"Id":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.steward') . '"},{"User":{"Gender":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXgender"},"FirstName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXfn"},"Id":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXrid"},"LastName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXsig"},"name":"Rolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIX","Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.trustee') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXgn"},"Id":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.trustee') . '"}],"Filter":{"AND":[{"AND":[{"Field":{"name":"isvocmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"vocabularyParentCommunityId","operator":"NOT_NULL"}}]}]},"Order":[{"Field":{"name":"vocabulary","order":"ASC"}}]}},"displayStart":0,"displayLength":500}}',
			[],
			true
		);
		$json = json_decode($resp);
		return $json->aaData;
	}

	public function getAllGlossaries() {
		$tableConfig = ['TableViewConfig' => [
			'Columns' => [
				['Column' => ['fieldName' => 'glossaryName']],
				['Column' => ['fieldName' => 'glossaryId']]],
			'Resources' => [
				'Vocabulary' => [
					'Name' => ['name' => 'glossaryName'],
					'Id' => ['name' => 'glossaryId'],
					'VocabularyType' => [
						'Id' => ['name' => 'domainTypeId']
					],
					'Filter' => [
						'AND' => [[
							'Field' => [
								'name' => 'domainTypeId',
								'operator' => 'EQUALS',
								'value' => Configure::read('Collibra.glossaryTypeId')]]]]]]]];

		$results = $this->fullDataTable($tableConfig);
		foreach ($results as $result) {
			if (strpos($result->glossaryName, ' Glossary') !== FALSE) {
				$result->glossaryName = substr($result->glossaryName, 0, strpos($result->glossaryName, ' Glossary'));
			}
		}
		usort($results, function($a, $b) {
			return strcmp($a->glossaryName, $b->glossaryName);
		});
		return $results;
	}

	public function searchStandardLabel($term) {
		$query = [
			'query' => $term,
			'filter' => [
				'category' => ['TE'],
				'type' => [
					'asset' => [Configure::read('Collibra.businessTermTypeId')]],
				'community' => [Configure::read('Collibra.community.byu')]],
			'fields' => [Configure::read('Collibra.attribute.standardFieldName')],
			'order' => [
				'by' => 'score',
				'sort' => 'desc'],
			'offset' => 0,
			'limit' => 20,
			'highlight' => false,
			'relativeUrl' => false];
		$response = $this->postJSON('search', json_encode($query));
		if (empty($response)) {
			return false;
		}
		if (!$response->isOk()) {
			$this->errors[] = $response->body();
			return false;
		}
		$results = json_decode($response->body());
		if (empty($results->results)) {
			return [];
		}
		return $results->results;
	}

	public function getDevelopmentShopDetails($developmentShopName = '', $exact = true) {
		$allShops = empty($developmentShopName);
		$tableConfig = ['TableViewConfig' => [
			'Columns' => [
				['Column' => ['fieldName' => 'id']],
				['Column' => ['fieldName' => 'name']],
				['Group' => [
					'name' => 'applications',
					'Columns' => [
						['Column' => ['fieldName' => 'appId']],
						['Column' => ['fieldName' => 'appName']],
						['Column' => ['fieldName' => 'appDescription']],
						['Column' => ['fieldName' => 'applicationIdentity']]]]]],
			'Resources' => [
				'Term' => [
					'Id' => ['name' => 'id'],
					'Signifier' => ['name' => 'name'],
					'Vocabulary' => [
						'Id' => ['name' => 'vocabularyId']],
					'Relation' => [
						'typeId' => Configure::read('Collibra.relationship.developmentShopToApplicationOrProject'),
						'type' => 'SOURCE',
						'Target' => [
							'Id' => ['name' => 'appId'],
							'Signifier' => ['name' => 'appName'],
							'StringAttribute' => [[
								'Value' => ['name' => 'applicationIdentity'],
								'labelId' => Configure::read('Collibra.attribute.applicationIdentity')],
							[
								'Value' => ['name' => 'appDescription'],
								'labelId' => Configure::read('Collibra.attribute.description')]]]],
					'Filter' => [
						'AND' => [
							['Field' => [
								'name' => 'vocabularyId',
								'operator' => 'EQUALS',
								'value' => Configure::read('Collibra.vocabulary.developmentShops')]]]]]]]];

		if (!$allShops) {
			$nameOperator = $exact ? 'EQUALS' : 'CONTAINS';
			array_push($tableConfig['TableViewConfig']['Resources']['Term']['Filter']['AND'],
					['Field' => [
						'name' => 'name',
						'operator' => $nameOperator,
						'value' => $developmentShopName]]);
		}

		$results = $this->fullDataTable($tableConfig);
		foreach ($results as $i => $devShop) {
			foreach ($devShop->applications as $j => $app) {
				$results[$i]->applications[$j]->applicationIdentity = strip_tags($app->applicationIdentity);
			}
		}
		return $results;
	}

	public function getResponsibilities($resourceId) {
		if (!empty($this->_rolesCache[$resourceId])) {
			return $this->_rolesCache[$resourceId];
		}
		$members = $this->get("member/find/all?resource={$resourceId}", ['json' => true]);
		if (empty($members->memberReference)) {
			return [];
		}
		$output = [];
		foreach ($members->memberReference as $member) {
			$output[$member->role->signifier][] = ($member->group == 1) ? $member->ownerGroup : $member->ownerUser;
		}
		$this->_rolesCache[$resourceId] = $output;
		return $output;
	}

	public function checkForDSRDraft($netId) {
		$tableConfig = ['TableViewConfig' => [
			'Columns' => [
				['Column' => ['fieldName' => 'id']]],
			'Resources' => [
				'Term' => [
					'Id' => ['name' => 'id'],
					'Signifier' => ['name' => 'draftname'],
					'Filter' => [
						'AND' => [[
							'Field' => [
								'name' => 'draftname',
								'operator' => 'EQUALS',
								'value' => "DRAFT-{$netId}"]]]]]]]];
		$results = $this->fullDataTable($tableConfig);
		return $results;
	}

	public function getRequestDetails($assetId, $dsr = true) {
		$policyRelTypeId = $dsr ? Configure::read('Collibra.relationship.DSRtoPolicy') : Configure::read('Collibra.relationship.DSAtoPolicy');

		$tableConfig = ['TableViewConfig' => [
			'Columns' => [
				['Column' => ['fieldName' => 'id']],
				['Column' => ['fieldName' => 'assetName']],
				['Column' => ['fieldName' => 'statusName']],
				['Column' => ['fieldName' => 'statusId']],
				['Column' => ['fieldName' => 'vocabularyId']],
				['Column' => ['fieldName' => 'communityId']],
				['Column' => ['fieldName' => 'developmentShop']],
				['Column' => ['fieldName' => 'conceptTypeId']],
				['Column' => ['fieldName' => 'createdOn']],
				['Group' => [
					'name' => 'attributes',
					'Columns' => [
						['Column' => ['fieldName' => 'attrSignifier']],
						['Column' => ['fieldName' => 'attrValue']],
						['Column' => ['fieldName' => 'attrResourceId']],
						['Column' => ['fieldName' => 'attrTypeId']]]]],

				['Group' => [
					'name' => 'requestedTerms',
					'Columns' => [
						['Column' => ['fieldName' => 'reqTermId']],
						['Column' => ['fieldName' => 'reqTermSignifier']],
						['Column' => ['fieldName' => 'reqTermRelationId']],
						['Column' => ['fieldName' => 'reqTermVocabId']],
						['Column' => ['fieldName' => 'reqTermVocabName']],
						['Column' => ['fieldName' => 'reqTermCommId']],
						['Column' => ['fieldName' => 'reqTermCommName']],
						['Column' => ['fieldName' => 'reqTermConceptTypeId']],
						['Column' => ['fieldName' => 'reqTermConceptTypeName']]]]],

				['Group' => [
					'name' => 'additionallyIncludedTerms',
					'Columns' => [
						['Column' => ['fieldName' => 'addTermId']],
						['Column' => ['fieldName' => 'addTermSignifier']],
						['Column' => ['fieldName' => 'addTermRelationId']],
						['Column' => ['fieldName' => 'addTermVocabId']],
						['Column' => ['fieldName' => 'addTermVocabName']],
						['Column' => ['fieldName' => 'addTermCommId']],
						['Column' => ['fieldName' => 'addTermCommName']],
						['Column' => ['fieldName' => 'addTermConceptTypeId']],
						['Column' => ['fieldName' => 'addTermConceptTypeName']]]]],

				['Group' => [
					'name' => 'requestedDataAssets',
					'Columns' => [
						['Column' => ['fieldName' => 'reqDataId']],
						['Column' => ['fieldName' => 'reqDataSignifier']],
						['Column' => ['fieldName' => 'reqDataRelationId']],
						['Column' => ['fieldName' => 'reqDataBusinessTermId']],
						['Column' => ['fieldName' => 'reqDataVocabId']],
						['Column' => ['fieldName' => 'reqDataVocabName']],
						['Column' => ['fieldName' => 'reqDataCommId']],
						['Column' => ['fieldName' => 'reqDataCommName']],
						['Column' => ['fieldName' => 'reqDataConceptTypeId']],
						['Column' => ['fieldName' => 'reqDataConceptTypeName']]]]],

				['Group' => [
					'name' => 'additionallyIncludedDataAssets',
					'Columns' => [
						['Column' => ['fieldName' => 'addDataId']],
						['Column' => ['fieldName' => 'addDataSignifier']],
						['Column' => ['fieldName' => 'addDataRelationId']],
						['Column' => ['fieldName' => 'addDataBusinessTermId']],
						['Column' => ['fieldName' => 'addDataVocabId']],
						['Column' => ['fieldName' => 'addDataVocabName']],
						['Column' => ['fieldName' => 'addDataCommId']],
						['Column' => ['fieldName' => 'addDataCommName']],
						['Column' => ['fieldName' => 'addDataConceptTypeId']],
						['Column' => ['fieldName' => 'addDataConceptTypeName']]]]],

				['Group' => [
					'name' => 'necessaryApis',
					'Columns' => [
						['Column' => ['fieldName' => 'apiId']],
						['Column' => ['fieldName' => 'apiName']],
						['Column' => ['fieldName' => 'apiVocabId']],
						['Column' => ['fieldName' => 'apiVocabName']],
						['Column' => ['fieldName' => 'apiCommId']],
						['Column' => ['fieldName' => 'apiCommName']],
						['Column' => ['fieldName' => 'apiAuthorizedByFieldset']],
						['Column' => ['fieldName' => 'apiRelationId']]]]],

				['Group' => [
					'name' => 'necessaryTables',
					'Columns' => [
						['Column' => ['fieldName' => 'tableId']],
						['Column' => ['fieldName' => 'tableName']],
						['Column' => ['fieldName' => 'tableVocabId']],
						['Column' => ['fieldName' => 'tableVocabName']],
						['Column' => ['fieldName' => 'tableCommId']],
						['Column' => ['fieldName' => 'tableCommName']],
						['Column' => ['fieldName' => 'tableRelationId']]]]],

				['Group' => [
					'name' => 'necessaryVirtualTables',
					'Columns' => [
						['Column' => ['fieldName' => 'virTableId']],
						['Column' => ['fieldName' => 'virTableName']],
						['Column' => ['fieldName' => 'virTableVocabId']],
						['Column' => ['fieldName' => 'virTableVocabName']],
						['Column' => ['fieldName' => 'virTableCommId']],
						['Column' => ['fieldName' => 'virTableCommName']],
						['Column' => ['fieldName' => 'virTableRelationId']]]]],

				['Group' => [
					'name' => 'necessarySamlResponses',
					'Columns' => [
						['Column' => ['fieldName' => 'responseId']],
						['Column' => ['fieldName' => 'responseName']],
						['Column' => ['fieldName' => 'responseVocabId']],
						['Column' => ['fieldName' => 'responseVocabName']],
						['Column' => ['fieldName' => 'responseCommId']],
						['Column' => ['fieldName' => 'responseCommName']],
						['Column' => ['fieldName' => 'responseRelationId']]]]],

				['Group' => [
					'name' => 'policies',
					'Columns' => [
						['Column' => ['fieldName' => 'policyId']],
						['Column' => ['fieldName' => 'policyName']],
						['Column' => ['fieldName' => 'policyDescription']],
						['Column' => ['fieldName' => 'policyDescriptionId']],
						['Column' => ['fieldName' => 'policyRelationId']]]]]],

			'Resources' => [
				'Term' => [
					'Id' => ['name' => 'id'],
					'Signifier' => ['name' => 'assetName'],
					'CreatedOn' => ['name' => 'createdOn'],
					'Status' => [
						'Signifier' => ['name' => 'statusName'],
						'Id' => ['name' => 'statusId']],
					'Attribute' => [
						'Value' => ['name' => 'attrValue'],
						'Id' => ['name' => 'attrResourceId'],
						'AttributeType' => [
							'Signifier' => ['name' => 'attrSignifier'],
							'Id' => ['name' => 'attrTypeId']]],
					'ConceptType' => [
						'Id' => ['name' => 'conceptTypeId']],
					'Relation' => [[
						'typeId' => $policyRelTypeId,
						'Id' => ['name' => 'policyRelationId'],
						'type' => 'SOURCE',
						'Target' => [
							'Id' => ['name' => 'policyId'],
							'Signifier' => ['name' => 'policyName'],
							'StringAttribute' => [[
								'Id' => ['name' => 'policyDescriptionId'],
								'Value' => ['name' => 'policyDescription'],
								'labelId' => Configure::read('Collibra.attribute.description')]]]]],
					'Vocabulary' => [
						'Id' => ['name' => 'vocabularyId'],
						'Community' => [
							'Id' => ['name' => 'communityId']]],
					'Filter' => [
						'AND' => [
							['Field' => [
								'name' => 'id',
								'operator' => 'EQUALS',
								'value' => $assetId]]]]]],
			'displayStart' => 0,
			'displayLength' => -1]];

		if ($dsr) {
			array_push($tableConfig['TableViewConfig']['Columns'],
				['Group' => [
					'name' => 'dsas',
					'Columns' => [
						['Column' => ['fieldName' => 'dsaId']],
						['Column' => ['fieldName' => 'dsaSignifier']],
						['Column' => ['fieldName' => 'dsaStatus']],
						['Column' => ['fieldName' => 'dsaVocabularyId']],
						['Column' => ['fieldName' => 'dsaVocabularyName']],
						['Column' => ['fieldName' => 'dsaCommunityId']],
						['Column' => ['fieldName' => 'dsaCommunityName']]]]]);

			array_push($tableConfig['TableViewConfig']['Resources']['Term']['Relation'],
				[
					'typeId' => Configure::read('Collibra.relationship.DSAtoDSR'),
					'type' => 'TARGET',
					'Source' => [
						'Id' => ['name' => 'dsaId'],
						'Signifier' => ['name' => 'dsaSignifier'],
						'Status' => [
							'Signifier' => ['name' => 'dsaStatus']],
						'Vocabulary' => [
							'Id' => ['name' => 'dsaVocabularyId'],
							'Name' => ['name' => 'dsaVocabularyName'],
							'Community' => [
								'Id' => ['name' => 'dsaCommunityId'],
								'Name' => ['name' => 'dsaCommunityName']]]]],

				[
					'typeId' => Configure::read('Collibra.relationship.DSRtoRequestedTerm'),
					'Id' => ['name' => 'reqTermRelationId'],
					'type' => 'SOURCE',
					'Target' => [
						'Id' => ['name' => 'reqTermId'],
						'Signifier' => ['name' => 'reqTermSignifier'],
						'Vocabulary' => [
							'Id' => ['name' => 'reqTermVocabId'],
							'Name' => ['name' => 'reqTermVocabName'],
							'Community' => [
								'Id' => ['name' => 'reqTermCommId'],
								'Name' => ['name' => 'reqTermCommName']]],
						'ConceptType' => [
							'Id' => ['name' => 'reqTermConceptTypeId'],
							'Signifier' => ['name' => 'reqTermConceptTypeName']]]],

				[
					'typeId' => Configure::read('Collibra.relationship.DSRtoAdditionallyIncludedTerm'),
					'Id' => ['name' => 'addTermRelationId'],
					'type' => 'SOURCE',
					'Target' => [
						'Id' => ['name' => 'addTermId'],
						'Signifier' => ['name' => 'addTermSignifier'],
						'Vocabulary' => [
							'Id' => ['name' => 'addTermVocabId'],
							'Name' => ['name' => 'addTermVocabName'],
							'Community' => [
								'Id' => ['name' => 'addTermCommId'],
								'Name' => ['name' => 'addTermCommName']]],
						'ConceptType' => [
							'Id' => ['name' => 'addTermConceptTypeId'],
							'Signifier' => ['name' => 'addTermConceptTypeName']]]],

				[
					'typeId' => Configure::read('Collibra.relationship.DSRtoRequestedDataAsset'),
					'Id' => ['name' => 'reqDataRelationId'],
					'type' => 'SOURCE',
					'Target' => [
						'Id' => ['name' => 'reqDataId'],
						'Signifier' => ['name' => 'reqDataSignifier'],
						'Vocabulary' => [
							'Id' => ['name' => 'reqDataVocabId'],
							'Name' => ['name' => 'reqDataVocabName'],
							'Community' => [
								'Id' => ['name' => 'reqDataCommId'],
								'Name' => ['name' => 'reqDataCommName']]],
						'ConceptType' => [
							'Id' => ['name' => 'reqDataConceptTypeId'],
							'Signifier' => ['name' => 'reqDataConceptTypeName']],
						'Relation' => [[
							'typeId' => Configure::read('Collibra.relationship.termToDataAsset'),
							'type' => 'TARGET',
							'Source' => [
								'Id' => ['name' => 'reqDataBusinessTermId']]]]]],

				[
					'typeId' => Configure::read('Collibra.relationship.DSRtoAdditionallyIncludedDataAsset'),
					'Id' => ['name' => 'addDataRelationId'],
					'type' => 'SOURCE',
					'Target' => [
						'Id' => ['name' => 'addDataId'],
						'Signifier' => ['name' => 'addDataSignifier'],
						'Vocabulary' => [
							'Id' => ['name' => 'addDataVocabId'],
							'Name' => ['name' => 'addDataVocabName'],
							'Community' => [
								'Id' => ['name' => 'addDataCommId'],
								'Name' => ['name' => 'addDataCommName']]],
						'ConceptType' => [
							'Id' => ['name' => 'addDataConceptTypeId'],
							'Signifier' => ['name' => 'addDataConceptTypeName']],
						'Relation' => [[
							'typeId' => Configure::read('Collibra.relationship.termToDataAsset'),
							'type' => 'TARGET',
							'Source' => [
								'Id' => ['name' => 'addDataBusinessTermId']]]]]],

				[
					'typeId' => Configure::read('Collibra.relationship.DSRtoNecessaryAPI'),
					'Id' => ['name' => 'apiRelationId'],
					'type' => 'SOURCE',
					'Target' => [
						'Id' => ['name' => 'apiId'],
						'Signifier' => ['name' => 'apiName'],
						'BooleanAttribute' => [
							'Value' => ['name' => 'apiAuthorizedByFieldset'],
							'labelId' => Configure::read('Collibra.attribute.authorizedByFieldset')],
						'Vocabulary' => [
							'Id' => ['name' => 'apiVocabId'],
							'Name' => ['name' => 'apiVocabName'],
							'Community' => [
								'Id' => ['name' => 'apiCommId'],
								'Name' => ['name' => 'apiCommName']]]]],

				[
					'typeId' => Configure::read('Collibra.relationship.DSRtoNecessaryTable'),
					'Id' => ['name' => 'tableRelationId'],
					'type' => 'SOURCE',
					'Target' => [
						'Id' => ['name' => 'tableId'],
						'Signifier' => ['name' => 'tableName'],
						'Vocabulary' => [
							'Id' => ['name' => 'tableVocabId'],
							'Name' => ['name' => 'tableVocabName'],
							'Community' => [
								'Id' => ['name' => 'tableCommId'],
								'Name' => ['name' => 'tableCommName']]]]],

				[
					'typeId' => Configure::read('Collibra.relationship.DSRtoNecessaryVirtualTable'),
					'Id' => ['name' => 'virTableRelationId'],
					'type' => 'SOURCE',
					'Target' => [
						'Id' => ['name' => 'virTableId'],
						'Signifier' => ['name' => 'virTableName'],
						'Vocabulary' => [
							'Id' => ['name' => 'virTableVocabId'],
							'Name' => ['name' => 'virTableVocabName'],
							'Community' => [
								'Id' => ['name' => 'virTableCommId'],
								'Name' => ['name' => 'virTableCommName']]]]],

				[
					'typeId' => Configure::read('Collibra.relationship.DSRtoNecessarySAML'),
					'Id' => ['name' => 'responseRelationId'],
					'type' => 'SOURCE',
					'Target' => [
						'Id' => ['name' => 'responseId'],
						'Signifier' => ['name' => 'responseName'],
						'Vocabulary' => [
							'Id' => ['name' => 'responseVocabId'],
							'Name' => ['name' => 'responseVocabName'],
							'Community' => [
								'Id' => ['name' => 'responseCommId'],
								'Name' => ['name' => 'responseCommName']]]]],
				[
					'typeId' => Configure::read('Collibra.relationship.applicationOrProjectToDSR'),
					'type' => 'TARGET',
					'Source' => [
						'Relation' => [[
							'typeId' => Configure::read('Collibra.relationship.developmentShopToApplicationOrProject'),
							'type' => 'TARGET',
							'Source' => [
								'Signifier' => ['name' => 'developmentShop']]]]]]);
		} else {
			array_push($tableConfig['TableViewConfig']['Columns'],
				['Column' => ['fieldName' => 'parentId']],
				['Column' => ['fieldName' => 'parentName']],
				['Column' => ['fieldName' => 'parentVocabularyId']],
				['Column' => ['fieldName' => 'parentStatus']]);

			array_push($tableConfig['TableViewConfig']['Resources']['Term']['Relation'],
				[
					'typeId' => Configure::read('Collibra.relationship.DSAtoDSR'),
					'type' => 'SOURCE',
					'Target' => [
						'Id' => ['name' => 'parentId'],
						'Signifier' => ['name' => 'parentName'],
						'Vocabulary' => [
							'Id' => ['name' => 'parentVocabularyId']],
						'Status' => [
							'Signifier' => ['name' => 'parentStatus']],

						'Relation' => [[
							'typeId' => Configure::read('Collibra.relationship.DSRtoRequestedTerm'),
							'Id' => ['name' => 'reqTermRelationId'],
							'type' => 'SOURCE',
							'Target' => [
								'Id' => ['name' => 'reqTermId'],
								'Signifier' => ['name' => 'reqTermSignifier'],
								'Vocabulary' => [
									'Id' => ['name' => 'reqTermVocabId'],
									'Name' => ['name' => 'reqTermVocabName'],
									'Community' => [
										'Id' => ['name' => 'reqTermCommId'],
										'Name' => ['name' => 'reqTermCommName']]],
								'ConceptType' => [
									'Id' => ['name' => 'reqTermConceptTypeId'],
									'Signifier' => ['name' => 'reqTermConceptTypeName']]]],

						[
							'typeId' => Configure::read('Collibra.relationship.DSRtoAdditionallyIncludedTerm'),
							'Id' => ['name' => 'addTermRelationId'],
							'type' => 'SOURCE',
							'Target' => [
								'Id' => ['name' => 'addTermId'],
								'Signifier' => ['name' => 'addTermSignifier'],
								'Vocabulary' => [
									'Id' => ['name' => 'addTermVocabId'],
									'Name' => ['name' => 'addTermVocabName'],
									'Community' => [
										'Id' => ['name' => 'addTermCommId'],
										'Name' => ['name' => 'addTermCommName']]],
								'ConceptType' => [
									'Id' => ['name' => 'addTermConceptTypeId'],
									'Signifier' => ['name' => 'addTermConceptTypeName']]]],

						[
							'typeId' => Configure::read('Collibra.relationship.DSRtoRequestedDataAsset'),
							'Id' => ['name' => 'reqDataRelationId'],
							'type' => 'SOURCE',
							'Target' => [
								'Id' => ['name' => 'reqDataId'],
								'Signifier' => ['name' => 'reqDataSignifier'],
								'Vocabulary' => [
									'Id' => ['name' => 'reqDataVocabId'],
									'Name' => ['name' => 'reqDataVocabName'],
									'Community' => [
										'Id' => ['name' => 'reqDataCommId'],
										'Name' => ['name' => 'reqDataCommName']]],
								'ConceptType' => [
									'Id' => ['name' => 'reqDataConceptTypeId'],
									'Signifier' => ['name' => 'reqDataConceptTypeName']],
								'Relation' => [[
									'typeId' => Configure::read('Collibra.relationship.termToDataAsset'),
									'type' => 'TARGET',
									'Source' => [
										'Id' => ['name' => 'reqDataBusinessTermId']]]]]],

						[
							'typeId' => Configure::read('Collibra.relationship.DSRtoAdditionallyIncludedDataAsset'),
							'Id' => ['name' => 'addDataRelationId'],
							'type' => 'SOURCE',
							'Target' => [
								'Id' => ['name' => 'addDataId'],
								'Signifier' => ['name' => 'addDataSignifier'],
								'Vocabulary' => [
									'Id' => ['name' => 'addDataVocabId'],
									'Name' => ['name' => 'addDataVocabName'],
									'Community' => [
										'Id' => ['name' => 'addDataCommId'],
										'Name' => ['name' => 'addDataCommName']]],
								'ConceptType' => [
									'Id' => ['name' => 'addDataConceptTypeId'],
									'Signifier' => ['name' => 'addDataConceptTypeName']],
								'Relation' => [[
									'typeId' => Configure::read('Collibra.relationship.termToDataAsset'),
									'type' => 'TARGET',
									'Source' => [
										'Id' => ['name' => 'addDataBusinessTermId']]]]]],

						[
							'typeId' => Configure::read('Collibra.relationship.DSRtoNecessaryAPI'),
							'Id' => ['name' => 'apiRelationId'],
							'type' => 'SOURCE',
							'Target' => [
								'Id' => ['name' => 'apiId'],
								'Signifier' => ['name' => 'apiName'],
								'BooleanAttribute' => [
									'Value' => ['name' => 'apiAuthorizedByFieldset'],
									'labelId' => Configure::read('Collibra.attribute.authorizedByFieldset')],
								'Vocabulary' => [
									'Id' => ['name' => 'apiVocabId'],
									'Name' => ['name' => 'apiVocabName'],
									'Community' => [
										'Id' => ['name' => 'apiCommId'],
										'Name' => ['name' => 'apiCommName']]]]],

						[
							'typeId' => Configure::read('Collibra.relationship.DSRtoNecessaryTable'),
							'Id' => ['name' => 'tableRelationId'],
							'type' => 'SOURCE',
							'Target' => [
								'Id' => ['name' => 'tableId'],
								'Signifier' => ['name' => 'tableName'],
								'Vocabulary' => [
									'Id' => ['name' => 'tableVocabId'],
									'Name' => ['name' => 'tableVocabName'],
									'Community' => [
										'Id' => ['name' => 'tableCommId'],
										'Name' => ['name' => 'tableCommName']]]]],

						[
							'typeId' => Configure::read('Collibra.relationship.DSRtoNecessaryVirtualTable'),
							'Id' => ['name' => 'virTableRelationId'],
							'type' => 'SOURCE',
							'Target' => [
								'Id' => ['name' => 'virTableId'],
								'Signifier' => ['name' => 'virTableName'],
								'Vocabulary' => [
									'Id' => ['name' => 'virTableVocabId'],
									'Name' => ['name' => 'virTableVocabName'],
									'Community' => [
										'Id' => ['name' => 'virTableCommId'],
										'Name' => ['name' => 'virTableCommName']]]]],

						[
							'typeId' => Configure::read('Collibra.relationship.DSRtoNecessarySAML'),
							'Id' => ['name' => 'responseRelationId'],
							'type' => 'SOURCE',
							'Target' => [
								'Id' => ['name' => 'responseId'],
								'Signifier' => ['name' => 'responseName'],
								'Vocabulary' => [
									'Id' => ['name' => 'responseVocabId'],
									'Name' => ['name' => 'responseVocabName'],
									'Community' => [
										'Id' => ['name' => 'responseCommId'],
										'Name' => ['name' => 'responseCommName']]]]],

						[
							'typeId' => Configure::read('Collibra.relationship.applicationOrProjectToDSR'),
							'type' => 'TARGET',
							'Source' => [
								'Relation' => [
									'typeId' => Configure::read('Collibra.relationship.developmentShopToApplicationOrProject'),
									'type' => 'TARGET',
									'Source' => [
										'Signifier' => ['name' => 'developmentShop']]]]]]]]);
		}

		$results = $this->fullDataTable($tableConfig);
		if (!empty($results)) {
			$arrNewAttr = [];
			$arrCollaborators = [];
			foreach ($results[0]->attributes as $attr) {
				if ($attr->attrSignifier === 'Requester Net Id') {					
					$person = ClassRegistry::init('BYUAPI')->personalSummary($attr->attrValue);
					unset($person->links, $person->metadata, $person->group_memberships, $person->phones, $person->addresses, $person->family_phones);
					$person->attrInfo = $attr;
					array_push($arrCollaborators, $person);
					continue;
				}
				$arrNewAttr[$attr->attrSignifier] = $attr;
			}
			$results[0]->collaborators = $arrCollaborators;
			$results[0]->attributes = $arrNewAttr;
			return $results[0];
		}
		return [];
	}

	public function getAttributes($assetId) {
		$tableConfig = ['TableViewConfig' => [
			'Columns' => [
				['Group' => [
					'name' => 'attributes',
					'Columns' => [
						['Column' => ['fieldName' => 'attrSignifier']],
						['Column' => ['fieldName' => 'attrValue']],
						['Column' => ['fieldName' => 'attrResourceId']],
						['Column' => ['fieldName' => 'attrTypeId']]]]]],
			'Resources' => [
				'Term' => [
					'Id' => ['name' => 'assetId'],
					'Attribute' => [
						'Value' => ['name' => 'attrValue'],
						'Id' => ['name' => 'attrResourceId'],
						'AttributeType' => [
							'Signifier' => ['name' => 'attrSignifier'],
							'Id' => ['name' => 'attrTypeId']]],
					'Filter' => [
						'AND' => [
							['Field' => [
								'name' => 'assetId',
								'operator' => 'EQUALS',
								'value' => $assetId]]]]]],
			'displayStart' => 0,
			'displayLength' => -1]];

		$results = $this->fullDataTable($tableConfig);
		if (!empty($results)) {
			$arrNewAttr = [];
			$arrCollaborators = [];
			foreach ($results[0]->attributes as $attr) {
				if ($attr->attrSignifier === 'Requester Net Id') {
					$person = ClassRegistry::init('BYUAPI')->personalSummary($attr->attrValue);
					unset($person->links, $person->metadata, $person->group_memberships, $person->phones, $person->addresses, $person->family_phones);
					$person->attrInfo = $attr;
					array_push($arrCollaborators, $person);
					continue;
				}
				$arrNewAttr[$attr->attrSignifier] = $attr;
			}
			return [$arrNewAttr, $arrCollaborators];
		}
		return [null, null];
	}

	public function getPolicies() {
		$tableConfig = ['TableViewConfig' => [
			'Columns' => [
				['Column' => ['fieldName' => 'id']],
				['Column' => ['fieldName' => 'policyName']],
				['Column' => ['fieldName' => 'body']],
				['Column' => ['fieldName' => 'inclusionScenario']]],
			'Resources' => [
				'Term' => [
					'Id' => ['name' => 'id'],
					'Signifier' => ['name' => 'policyName'],
					'StringAttribute' => [[
						'Value' => ['name' => 'body'],
						'labelId' => Configure::read('Collibra.attribute.description')],
					[
						'Value' => ['name' => 'inclusionScenario'],
						'labelId' => Configure::read('Collibra.attribute.inclusionScenario')]],
					'Vocabulary' => [
						'Id' => ['name' => 'vocabId']],
					'Filter' => [
						'AND' => [[
							'Field' => [
								'name' => 'vocabId',
								'operator' => 'EQUALS',
								'value' => Configure::read('Collibra.vocabulary.infoGovPolicies')]]]]]],
			'displayStart' => 0,
			'displayLength' => -1]];

		$policies = $this->fullDataTable($tableConfig);
		return $policies;
	}

	protected function _updateSessionCookies() {
		$config = $this->client()->config;
		if (empty($config['request']['cookies'])) {
			CakeSession::delete('Collibra.cookies');
		} else {
			CakeSession::write('Collibra.cookies', $config['request']['cookies']);
		}
	}

	public function request($options=[]){
		$ch = curl_init();
		$url = $this->settings['url'].$options['url'];
		$params = isset($options['params'])?$options['params']:'';

		if(isset($options['post'])){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		}else{
			if($params!='') $url .= '?'.$params;
		}

		if(isset($options['json'])){
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Content-Type: application/json',
				'Content-Length: ' . strlen($params)]
			);
		}

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $this->settings['username'].":".$this->settings['password']);
		$response = curl_exec($ch);

		$this->code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$this->info = curl_getinfo($ch);
		$this->errors[] = curl_error($ch);

		curl_close($ch);

		/*if($this->code != '200' && $this->code != '201'){
			echo 'cURL ERROR:<br>'.
				'username: '. $this->settings['username'].'<br>'.
				'code: '. $this->code.'<br>'.
				'info: '. print_r($this->info).'<br>'.
				'error: '. implode('<br>', $this->errors) .'<br>';
			//exit;
			echo $url.'<br>';
		}*/
		return $response;
	}
}
