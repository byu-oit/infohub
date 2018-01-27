<?php

App::uses('HttpSocket', 'Network/Http');
App::uses('Model', 'Model');
App::uses('String', 'Utility');
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

	public function post($url, $data = [], $options = []) {
		$response = $this->client()->post($this->settings['url'] . $url, $data, $options);
		if (!empty($response) && !empty($response->cookies)) {
			$this->_updateSessionCookies();
		}
		return $response;
	}

	public function postJSON($url, $data, $options = []) {
		$options['header']['Content-Type'] = 'application/json';
		return $this->post($url, $data, $options);
	}

	public function delete($url = NULL, $cascade = true) {
		return $this->client()->delete($this->settings['url'] . $url);
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

		if (!empty($byuInfo->contact_information->email)) {
			if (empty($collibraInfo->UserEmailaddress) || html_entity_decode($collibraInfo->UserEmailaddress) != $byuInfo->contact_information->email) {
				$this->updateUser($collibraInfo->UserId, [
					'firstName' => $collibraInfo->UserFirstName,
					'lastName' => $collibraInfo->UserLastName,
					'email' => $byuInfo->contact_information->email,
					'gender' => $collibraInfo->UserGender
				]);
			}
		}

		if (!empty($byuInfo->contact_information->work_phone)) {
			$byuPhone = $byuInfo->contact_information->work_phone;
			$match = false;
			if (!empty($collibraInfo->Phone)) {
				foreach ($collibraInfo->Phone as $phoneInfo) {
					if ($phoneInfo->PhonePhonenumber == $byuPhone) {
						$match = true;
						break;
					}
				}
			}
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
		$boundary = 'CakePHPBoundary' . str_replace('-', '', String::uuid());
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
			if (empty($host->name) || !preg_match('/^[a-zA-Z][a-zA-Z0-9\.]*$/', $host->name)) {
				continue;
			}
			$hosts[] = $host->name;
		}
		return $hosts;
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
				['Column' => ['fieldName' => 'status']]],
			'Resources' => [
				'Term' => [
					'Id' => ['name' => 'id'],
					'Signifier' => ['name' => 'name'],
					'Status' => ['name' => 'status'],
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

	public function getApiTerms($host, $path) {
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
							'typeId' => Configure::read('Collibra.relationship.termToField'),
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
		return $terms;
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
				['Column' => ['fieldName' => 'Attr00000000000000000000000000000202']],
				['Column' => ['fieldName' => 'Attr00000000000000000000000000000202longExpr']],
				['Column' => ['fieldName' => 'Attr00000000000000000000000000000202rid']],
				['Group' => [
					'Columns' => [
						['Column' => ['label' => 'Steward User ID','fieldName' => 'userRole00000000000000000000000000005016rid']],
						['Column' => ['label' => 'Steward Gender','fieldName' => 'userRole00000000000000000000000000005016gender']],
						['Column' => ['label' => 'Steward First Name','fieldName' => 'userRole00000000000000000000000000005016fn']],
						['Column' => ['label' => 'Steward Last Name','fieldName' => 'userRole00000000000000000000000000005016ln']]
					],
					'name' => 'Role00000000000000000000000000005016']],
				['Group' => [
					'Columns' => [
						['Column' => ['label' => 'Steward Group ID','fieldName' => 'groupRole00000000000000000000000000005016grid']],
						['Column' => ['label' => 'Steward Group Name','fieldName' => 'groupRole00000000000000000000000000005016ggn']]
					],
					'name' => 'Role00000000000000000000000000005016g']],
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
					'StringAttribute' => [[
						'Value' => ['name' => 'Attr00000000000000000000000000000202'],
						'LongExpression' => ['name' => 'Attr00000000000000000000000000000202longExpr'],
						'Id' => ['name' => 'Attr00000000000000000000000000000202rid'],
						'labelId' => Configure::read('Collibra.attribute.definition')]],
					'Member' => [[
						'User' => [
							'Gender' => ['name' => 'userRole00000000000000000000000000005016gender'],
							'FirstName' => ['name' => 'userRole00000000000000000000000000005016fn'],
							'Id' => ['name' => 'userRole00000000000000000000000000005016rid'],
							'LastName' => ['name' => 'userRole00000000000000000000000000005016ln']],
						'Role' => [
							'Signifier' => ['hidden' => 'true','name' => 'Role00000000000000000000000000005016sig'],
							'name' => 'Role00000000000000000000000000005016',
							'Id' => ['hidden' => 'true','name' => 'roleRole00000000000000000000000000005016rid']],
						'roleId' => '00000000-0000-0000-0000-000000005016'],
					[
						'Role' => [
							'Signifier' => ['hidden' => 'true','name' => 'Role00000000000000000000000000005016g'],
							'Id' => ['hidden' => 'true','name' => 'roleRole00000000000000000000000000005016grid']],
						'Group' => [
							'GroupName' => ['name' => 'groupRole00000000000000000000000000005016ggn'],
							'Id' => ['name' => 'groupRole00000000000000000000000000005016grid']],
						'roleId' => '00000000-0000-0000-0000-000000005016']],
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

	public function searchTerms($query, $limit = 10, $community = null) {
		if (empty($community)) {
			$community = Configure::read('Collibra.community.byu');
		}
		if (substr($query, -1) !== '*') {
			$query .= '*';
		}
		$request = [
			'query' => $query,
			'filter' => [
				'community' => [$community],
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
						"typeId" => Configure::read('Collibra.relationship.termToField'),
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
		$existentTerms = array();
		if (!empty($vocabulary)) {
			$vocabularyId = $vocabulary[0]->resourceId;
			foreach ($vocabulary[0]->termReferences->termReference as $term) {
				$existentTerms[$term->resourceId] = $term->signifier;
			}
		}
		else {
			$vocabularyId = $this->createVocabulary("{$swagger['basePath']}/{$swagger['version']}", $hostCommunity->resourceId);
			if (empty($vocabularyId)) {
				$this->errors[] = "Unable to create vocabulary \"{$swagger['basePath']}/{$swagger['version']}\" in community \"{$swagger['host']}\"";
				return false;
			}
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
		if (!in_array("{$swagger['basePath']}/{$swagger['version']}", $existentTerms)) {
			$apiResult = $this->addTermstoVocabulary($vocabularyId, Configure::read('Collibra.type.api'), ["{$swagger['basePath']}/{$swagger['version']}"]);
			if (empty($apiResult) || !$apiResult->isOk()) {
				$this->errors[] = "Error creating an object representing \"{$swagger['basePath']}/{$swagger['version']}\"";
				$this->deleteVocabulary($vocabularyId);
				return false;
			}
		}
		//Add fields
		if (!empty($fields)) {
			$fieldsResult = $this->addTermsToVocabulary($vocabularyId, Configure::read('Collibra.type.field'), $fields);
			if (empty($fieldsResult) || !$fieldsResult->isOk()) {
				$this->errors[] = "Error adding fields to \"{$swagger['basePath']}/{$swagger['version']}\"";
				$this->deleteVocabulary($vocabularyId);
				return false;
			}
		}
		if (!empty($fieldSets)) {
			$fieldSetsResult = $this->addTermsToVocabulary($vocabularyId, Configure::read('Collibra.type.fieldSet'), $fieldSets);
			if (empty($fieldSetsResult) || !$fieldSetsResult->isOk()) {
				$this->errors[] = "Error adding fieldSets to \"{$swagger['basePath']}/{$swagger['version']}\"";
				$this->deleteVocabulary($vocabularyId);
				return false;
			}
		}

		//Link created terms to selected Business Terms
		$relationshipTypeId = Configure::read('Collibra.relationship.termToField');
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
				$this->post("term/{$term->resourceId}/relations", [
					'type' => $relationshipTypeId,
					'target' => $elements[$term->signifier]['business_term'],
					'inverse' => 'true'
				]);
			}
		}
		//Link already existent terms to Business Terms, if selected
		foreach ($existentTerms as $id => $signifier) {
			if (empty($elements[$signifier]['business_term'])) {
				continue;
			}
			$this->post("term/{$id}/relations", [
				'type' => $relationshipTypeId,
				'target' => $elements[$signifier]['business_term'],
				'inverse' => 'true'
			]);
		}
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

	public function updateApiBusinessTermLinks($terms) {
		$relationshipTypeId = Configure::read('Collibra.relationship.termToField');
		foreach ($terms as $term) {
			if (empty($term['id'])) {
				continue;
			}
			if (isset($term['previous_business_term'])) {
				if ($term['previous_business_term'] == $term['business_term']) {
					continue;
				}
				$this->delete("relation/{$term['previous_business_term_relation']}");
			}
			if (empty($term['business_term'])) {
				continue;
			}
			$this->post("term/{$term['id']}/relations", [
				'type' => $relationshipTypeId,
				'target' => $term['business_term'],
				'inverse' => 'true'
			]);
		}
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

		$match = null;
		foreach ($search->{$key} as $item) {
			if (!empty($item->name) && $item->name == $name) {
				$match = $item;
				break;
			}
		}
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

	public function getCommunityData($community) {
		$results = $this->postJSON('output/data_table', '{"TableViewConfig":{"Columns":[{"Community":{"name":"Subcommunities","Columns":[{"Column":{"fieldName":"subcommunityid"}},{"Column":{"fieldName":"subcommunity"}},{"Column":{"fieldName":"hasNonMetaChildren"}}]}},{"Vocabulary":{"name":"Vocabularies","Columns":[{"Column":{"fieldName":"vocabulary"}},{"Column":{"fieldName":"vocabularyid"}}]}}],"Resources":{"Community":{"Id":{"name":"subcommunityid"},"Name":{"name":"subcommunity"},"Meta":{"name":"issubmeta"},"hasNonMetaChildren":{"name":"hasNonMetaChildren"},"ParentCommunity":{"Id":{"name":"parentCommunityId"}},"Filter":{"AND":[{"AND":[{"Field":{"name":"issubmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"parentCommunityId","operator":"EQUALS","value":"'.$community.'"}}]}]},"Order":[{"Field":{"name":"subcommunity","order":"ASC"}}]},"Vocabulary":{"Name":{"name":"vocabulary"},"Id":{"name":"vocabularyid"},"Meta":{"name":"isvocmeta"},"Community":{"Id":{"name":"vocabularyParentCommunityId"}},"Filter":{"AND":[{"AND":[{"Field":{"name":"isvocmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"vocabularyParentCommunityId","operator":"EQUALS","value":"'.$community.'"}}]},{"AND":[{"Field":{"name":"vocabulary","operator":"INCLUDES","value":"Glossary"}}]}]},"Order":[{"Field":{"name":"vocabulary","order":"ASC"}}]}},"displayStart":0,"displayLength":100,"generalConceptId":"'.Configure::read('Collibra.community.byu').'"}}');

		return $results;
	}

	public function getAllCommunities() {
		$resp = $this->postJSON(
				'output/data_table',
				'{"TableViewConfig":{"Columns":[{"Community":{"name":"Subcommunities","Columns":[{"Column":{"fieldName":"subcommunityid"}},{"Column":{"fieldName":"subcommunity"}},{"Column":{"fieldName":"parentCommunityId"}},{"Column":{"fieldName":"hasNonMetaChildren"}},{"Column":{"fieldName":"description"}},{"Group":{"Columns":[{"Column":{"label":"Custodian User ID","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},{"Column":{"label":"Custodian Gender","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"}},{"Column":{"label":"Custodian First Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"}},{"Column":{"label":"Custodian Last Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb"}},{"Group":{"Columns":[{"Column":{"label":"Custodian Group ID","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},{"Column":{"label":"Custodian Group Name","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acrid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acgender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acfn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acln"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747ac"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acg"}},{"Group":{"Columns":[{"Column":{"label":"Trustee User ID","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411rid"}},{"Column":{"label":"Trustee Gender","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411gender"}},{"Column":{"label":"Trustee First Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411fn"}},{"Column":{"label":"Trustee Last Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411ln"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411"}},{"Group":{"Columns":[{"Column":{"label":"Trustee Group ID","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411grid"}},{"Column":{"label":"Trustee Group Name","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411ggn"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411g"}}]}},{"Vocabulary":{"name":"Vocabularies","Columns":[{"Column":{"fieldName":"vocabulary"}},{"Column":{"fieldName":"vocabularyid"}},{"Column":{"fieldName":"vocabularyParentCommunityId"}},{"Group":{"Columns":[{"Column":{"label":"Custodian User ID","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXrid"}},{"Column":{"label":"Custodian Gender","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXgender"}},{"Column":{"label":"Custodian First Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXfn"}},{"Column":{"label":"Custodian Last Name","fieldName":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXln"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Custodian Group ID","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXrid"}},{"Column":{"label":"Custodian Group Name","fieldName":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXgn"}}],"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXrid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXgender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXfn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXln"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXrid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXgn"}}],"name":"Role8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Trustee User ID","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXrid"}},{"Column":{"label":"Trustee Gender","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXgender"}},{"Column":{"label":"Trustee First Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXfn"}},{"Column":{"label":"Trustee Last Name","fieldName":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXln"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Trustee Group ID","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXrid"}},{"Column":{"label":"Trustee Group Name","fieldName":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXgn"}}],"name":"Rolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIX"}}]}}],"Resources":{"Community":{"Id":{"name":"subcommunityid"},"Name":{"name":"subcommunity"},"Meta":{"name":"issubmeta"},"hasNonMetaChildren":{"name":"hasNonMetaChildren"},"Description":{"name":"description"},"ParentCommunity":{"Id":{"name":"parentCommunityId"}},"Member":[{"User":{"Gender":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbgender"},"FirstName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbfn"},"Id":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"},"LastName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbsig"},"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afb","Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbrid"}},"roleId":"' . Configure::read('Collibra.role.custodian') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbg"},"Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"Group":{"GroupName":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbggn"},"Id":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgrid"}},"roleId":"' . Configure::read('Collibra.role.custodian') . '"},{"User":{"Gender":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acgender"},"FirstName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acfn"},"Id":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acrid"},"LastName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acln"}},"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acsig"},"name":"Role8a0a6c89106c4adb9936f09f29b747ac","Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acrid"}},"roleId":"' . Configure::read('Collibra.role.steward') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acg"},"Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"Group":{"GroupName":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acggn"},"Id":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgrid"}},"roleId":"' . Configure::read('Collibra.role.steward') . '"},{"User":{"Gender":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411gender"},"FirstName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411fn"},"Id":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411rid"},"LastName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411ln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411sig"},"name":"Rolefb97305e00c0459a84cbb3f5ea62d411","Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411rid"}},"roleId":"' . Configure::read('Collibra.role.trustee') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411g"},"Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411grid"}},"Group":{"GroupName":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411ggn"},"Id":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411grid"}},"roleId":"' . Configure::read('Collibra.role.trustee') . '"}],"Filter":{"AND":[{"AND":[{"Field":{"name":"issubmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"parentCommunityId","operator":"NOT_NULL"}}]}]},"Order":[{"Field":{"name":"subcommunity","order":"ASC"}}]},"Vocabulary":{"Name":{"name":"vocabulary"},"Id":{"name":"vocabularyid"},"Meta":{"name":"isvocmeta"},"Community":{"Id":{"name":"vocabularyParentCommunityId"}},"Member":[{"User":{"Gender":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXgender"},"FirstName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXfn"},"Id":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXrid"},"LastName":{"name":"userRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXsig"},"name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIX","Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbVOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.custodian') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Rolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXgn"},"Id":{"name":"groupRolef86d1d3abc2e4beeb17fe0e9985d5afbgVOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.custodian') . '"},{"User":{"Gender":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXgender"},"FirstName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXfn"},"Id":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXrid"},"LastName":{"name":"userRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXsig"},"name":"Role8a0a6c89106c4adb9936f09f29b747acVOCSUFFIX","Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acVOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.steward') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Role8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXgn"},"Id":{"name":"groupRole8a0a6c89106c4adb9936f09f29b747acgVOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.steward') . '"},{"User":{"Gender":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXgender"},"FirstName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXfn"},"Id":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXrid"},"LastName":{"name":"userRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXsig"},"name":"Rolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIX","Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411VOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.trustee') . '"},{"Role":{"Signifier":{"hidden":"true","name":"Rolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXgn"},"Id":{"name":"groupRolefb97305e00c0459a84cbb3f5ea62d411gVOCSUFFIXrid"}},"roleId":"' . Configure::read('Collibra.role.trustee') . '"}],"Filter":{"AND":[{"AND":[{"Field":{"name":"isvocmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"vocabularyParentCommunityId","operator":"NOT_NULL"}}]}]},"Order":[{"Field":{"name":"vocabulary","order":"ASC"}}]}},"displayStart":0,"displayLength":500}}'
			);
		$json = json_decode($resp);
		return $json->aaData;
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
		$output = [];
		foreach ($results->results as $result) {
			if (!empty($result->attributes)) {
				foreach ($result->attributes as $attribute) {
					if ($attribute->type == 'Definition') {
						$result->definition = $attribute;
					}
				}
			}
			unset($result->attributes);
		}
		return $results->results;
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

	public function getDataUsages($dsaId) {
		$tableConfig = ['TableViewConfig' => [
			'Columns' => [
				['Column' => ['fieldName' => 'id']],
				['Column' => ['fieldName' => 'vocabularyId']],
				['Column' => ['fieldName' => 'vocabularyName']],
				['Column' => ['fieldName' => 'communityId']],
				['Column' => ['fieldName' => 'communityName']],
				['Column' => ['fieldName' => 'signifier']],
				['Column' => ['fieldName' => 'status']],
				['Group' => [
					'name' => 'policies',
					'Columns' => [
						['Column' => ['fieldName' => 'policyId']],
						['Column' => ['fieldName' => 'policyName']],
						['Column' => ['fieldName' => 'policyDescription']],
						['Column' => ['fieldName' => 'policyDescriptionId']]]]]],
			'Resources' => [
				'Term' => [
					'Id' => ['name' => 'id'],
					'Signifier' => ['name' => 'signifier'],
					'Status' => [
						'Signifier' => ['name' => 'status']],
					'Vocabulary' => [
						'Id' => ['name' => 'vocabularyId'],
						'Name' => ['name' => 'vocabularyName'],
						'Community' => [
							'Id' => ['name' => 'communityId'],
							'Name' => ['name' => 'communityName']]],
					'Relation' => [[
						'typeId' => Configure::read('Collibra.relationship.dataUsageToDSA'),
						'type' => 'SOURCE',
						'Target' => [
							'Id' => ['name' => 'dsaId']],
						'Filter' => [
							'AND' => [[
								'Field' => [
										'name' => 'dsaId',
										'operator' => 'EQUALS',
										'value' => $dsaId]]]]],
					[
						'typeId' => Configure::read('Collibra.relationship.DSAtoPolicy'),
						'type' => 'SOURCE',
						'Target' => [
							'Id' => ['name' => 'policyId'],
							'Signifier' => ['name' => 'policyName'],
							'StringAttribute' => [[
								'Id' => ['name' => 'policyDescriptionId'],
								'Value' => ['name' => 'policyDescription'],
								'labelId' => Configure::read('Collibra.attribute.description')]]]]],
					'Filter' => [
						'AND' => [[
							'Field' => [
									'name' => 'dsaId',
									'operator' => 'EQUALS',
									'value' => $dsaId]]]],
					'Order' => [
						['Field' => [
							'name' => 'signifier',
							'order' => 'ASC']]]]],
			'displayStart' => 0,
			'displayLength' => -1]];
		$usages = $this->fullDataTable($tableConfig);
		foreach ($usages as &$usage) {
			$usage->roles = $this->getResponsibilities($usage->vocabularyId);
			foreach($usage->policies as $policy) {
				// Collibra can insert weird HTML into text attributes; cleaning it up here
				if (preg_match('/<div>/', $policy->policyDescription)) {
					$postData['value'] = preg_replace(['/<div><br\/>/', '/<\/div>/', '/<div>/', '/<\/?span[^>]*>/'], ['<br/>', '', '<br/>', ''], $policy->policyDescription);
					$postData['rid'] = $policy->policyDescriptionId;
					$postString = http_build_query($postData);
					$this->post('attribute/'.$policy->policyDescriptionId, $postString);

					// After updating the value in Collibra, just replace the value for this page load
					$policy->policyDescription = preg_replace(['/<div><br\/>/', '/<\/div>/', '/<div>/', '/<\/?span[^>]*/'], ['<br/>', '', '<br/>', ''], $policy->policyDescription);
				}
			}
		}
		return $usages;
	}

	public function getDataUsageParent($dsaId) {
		$tableConfig = ['TableViewConfig' => [
			'Columns' => [
				['Column' => ['fieldName' => 'id']],
				['Column' => ['fieldName' => 'vocabularyId']],
				['Column' => ['fieldName' => 'signifier']],
				['Column' => ['fieldName' => 'status']]],
			'Resources' => [
				'Term' => [
					'Id' => ['name' => 'id'],
					'Signifier' => ['name' => 'signifier'],
					'Status' => [
						'Signifier' => ['name' => 'status']],
					'Vocabulary' => [
						'Id' => ['name' => 'vocabularyId']],
					'Relation' => [[
						'typeId' => Configure::read('Collibra.relationship.dataUsageToDSA'),
						'type' => 'TARGET',
						'Source' => [
							'Id' => ['name' => 'dsaId']],
						'Filter' => [
							'AND' => [[
								'Field' => [
										'name' => 'dsaId',
										'operator' => 'EQUALS',
										'value' => $dsaId]]]]]],
					'Filter' => [
						'AND' => [[
							'Field' => [
									'name' => 'dsaId',
									'operator' => 'EQUALS',
									'value' => $dsaId]]]],
					'Order' => [
						['Field' => [
							'name' => 'signifier',
							'order' => 'ASC']]]]],
			'displayStart' => 0,
			'displayLength' => -1]];
		$usages = $this->fullDataTable($tableConfig);
		foreach ($usages as &$usage) {
			$usage->roles = $this->getResponsibilities($usage->vocabularyId);
		}
		return $usages;
	}

	public function getAssetPolicies($assetId) {
		$relTypeIds = [
			Configure::read('Collibra.relationship.DSAtoPolicy'),
			Configure::read('Collibra.relationship.DSRtoPolicy')
		];
		$policies = [];

		foreach ($relTypeIds as $typeId) {
			$tableConfig = ['TableViewConfig' => [
				'Columns' => [
					['Group' => [
						'name' => 'arrPolicies',
						'Columns' => [
							['Column' => ['fieldName' => 'policyId']],
							['Column' => ['fieldName' => 'policyName']],
							['Column' => ['fieldName' => 'policyDescription']],
							['Column' => ['fieldName' => 'policyDescriptionId']]]]]],
				'Resources' => [
					'Term' => [
						'Relation' => [[
							'typeId' => $typeId,
							'type' => 'SOURCE',
							'Source' => [
								'Id' => ['name' => 'dsaId']],
							'Target' => [
								'Id' => ['name' => 'policyId'],
								'Signifier' => ['name' => 'policyName'],
								'StringAttribute' => [[
									'Id' => ['name' => 'policyDescriptionId'],
									'Value' => ['name' => 'policyDescription'],
									'labelId' => Configure::read('Collibra.attribute.description')]],
								'Filter' => [
									'AND' => [[
										'Field' => [
												'name' => 'dsaId',
												'operator' => 'NOT_NULL']]]]]]],
						'Filter' => [
							'AND' => [
								['Field' => [
										'name' => 'dsaId',
										'operator' => 'EQUALS',
										'value' => $assetId]]]]]],
				'displayStart' => 0,
				'displayLength' => -1]];

			$results = $this->fullDataTable($tableConfig);
			if (!empty($results)) {
				$policies = array_merge($policies, $results[0]->arrPolicies);
			}
		}

		if (!empty($policies)) {
			foreach($policies as &$policy) {
				// Collibra can insert weird HTML into text attributes; cleaning it up here
				if (preg_match('/<div>/', $policy->policyDescription)) {
					$postData['value'] = preg_replace(['/<div><br\/>/', '/<\/div>/', '/<div>/', '/<\/?span[^>]*>/'], ['<br/>', '', '<br/>', ''], $policy->policyDescription);
					$postData['rid'] = $policy->policyDescriptionId;
					$postString = http_build_query($postData);
					$this->post('attribute/'.$policy->policyDescriptionId, $postString);

					// After updating the value in Collibra, just replace the value for this page load
					$policy->policyDescription = preg_replace(['/<div><br\/>/', '/<\/div>/', '/<div>/', '/<\/?span[^>]*/'], ['<br/>', '', '<br/>', ''], $policy->policyDescription);
				}
			}
			return $policies;
		}
		return $policies;
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

	public function getRequestedTerms($dsrId) {
		$tableConfig = ['TableViewConfig' => [
			'Columns' => [
				['Column' => ['fieldName' => 'termrid']],
				['Column' => ['fieldName' => 'termsignifier']],
				['Column' => ['fieldName' => 'relationrid']],
				['Column' => ['fieldName' => 'startDate']],
				['Column' => ['fieldName' => 'endDate']],
				['Column' => ['fieldName' => 'relstatusrid']],
				['Column' => ['fieldName' => 'relstatusname']],
				['Column' => ['fieldName' => 'communityname']],
				['Column' => ['fieldName' => 'commrid']],
				['Column' => ['fieldName' => 'domainname']],
				['Column' => ['fieldName' => 'domainrid']],
				['Column' => ['fieldName' => 'concepttypename']],
				['Column' => ['fieldName' => 'concepttyperid']]],
			'Resources' => [
				'Term' => [
					'Id' => ['name' => 'termrid'],
					'Signifier' => ['name' => 'termsignifier'],
					'Relation' => [
						'typeId' => Configure::read('Collibra.relationship.isaRequestToTerm'),
						'Id' => ['name' => 'relationrid'],
						'StartingDate' => ['name' => 'startDate'],
						'EndingDate' => ['name' => 'endDate'],
						'Status' => [
							'Id' => ['name' => 'relstatusrid'],
							'Signifier' => ['name' => 'relstatusname']],
						'Filter' => [
							'AND' => [[
								'Field' => [
									'name' => 'reltermrid',
									'operator' => 'EQUALS',
									'value' => $dsrId]]]],
						'type' => 'TARGET',
						'Source' => [
							'Id' => ['name' => 'reltermrid']]],
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
							'AND' => [[
								'Field' => [
									'name' => 'reltermrid',
									'operator' => 'EQUALS',
									'value' => $dsrId]]]]]],
					'Order' => [[
						'Field' => [
							'name' => 'termsignifier',
							'order' => 'ASC']]]]],
			'displayStart' => 0,
			'displayLength' => 100]];

			$requestedTerms = $this->fullDataTable($tableConfig);
			return $requestedTerms;
	}

	public function getAdditionallyIncludedTerms($dsrId) {
		$tableConfig = ['TableViewConfig' => [
			'Columns' => [
				['Column' => ['fieldName' => 'termid']],
				['Column' => ['fieldName' => 'termsignifier']],
				['Column' => ['fieldName' => 'relationid']],
				['Column' => ['fieldName' => 'domainrid']],
				['Column' => ['fieldName' => 'domainname']],
				['Column' => ['fieldName' => 'concept']],
				['Column' => ['fieldName' => 'classification']],
				['Column' => ['fieldName' => 'commrid']],
				['Column' => ['fieldName' => 'communityname']],
				['Column' => ['fieldName' => 'statusname']]],
			'Resources' => [
				'Term' => [
					'Id' => ['name' => 'termid'],
					'Signifier' => ['name' => 'termsignifier'],
					'BooleanAttribute' => [[
						'Value' => ['name' => 'concept'],
						'labelId' => Configure::read('Collibra.attribute.concept')]],
					'SingleValueListAttribute' => [[
						'Value' => ['name' => 'classification'],
						'labelId' => Configure::read('Collibra.attribute.classification')]],
					'Status' => [
						'Signifier' => ['name' => 'statusname']],
					'Vocabulary' => [
						'Community' => [
							'Name' => ['name' => 'communityname'],
							'Id' => ['name' => 'commrid']],
						'Id' => ['name' => 'domainrid'],
						'Name' => ['name' => 'domainname']],
					'Relation' => [[
						'typeId' => Configure::read('Collibra.relationship.DSRtoAdditionallyIncludedAsset'),
						'Id' => ['name' => 'relationid'],
						'type' => 'TARGET',
						'Source' => [
							'Id' => ['name' => 'dsaId']],
						'Filter' => [
							'AND' => [[
								'Field' => [
										'name' => 'dsaId',
										'operator' => 'EQUALS',
										'value' => $dsrId]]]]]],
					'Filter' => [
						'AND' => [[
							'Field' => [
								'name' => 'dsaId',
								'operator' => 'EQUALS',
								'value' => $dsrId]]]]]]]];

		$terms = $this->fullDataTable($tableConfig);
		return $terms;
	}

	public function getNecessaryAPIs($dsrId) {
		$tableConfig = ['TableViewConfig' => [
			'Columns' => [
				['Column' => ['fieldName' => 'apirid']],
				['Column' => ['fieldName' => 'apiname']],
				['Column' => ['fieldName' => 'domainrid']],
				['Column' => ['fieldName' => 'domainname']],
				['Column' => ['fieldName' => 'communityid']],
				['Column' => ['fieldName' => 'communityname']]],
			'Resources' => [
				'Term' => [
					'typeId' => Configure::read('Collibra.type.api'),
					'Id' => ['name' => 'apirid'],
					'Signifier' => ['name' => 'apiname'],
					'Vocabulary' => [
						'Id' => ['name' => 'domainrid'],
						'Name' => ['name' => 'domainname'],
						'Community' => [
							'Id' => ['name' => 'communityid'],
							'Name' => ['name' => 'communityname']]],
					'Relation' => [
						'relationTypeId' => Configure::read('Collibra.relationship.DSRtoNecessaryAPI'),
						'type' => 'TARGET',
						'Source' => [
							'typeId' => Configure::read('Collibra.type.isaRequest'),
							'Id' => ['name' => 'dsrid']]],
					'Filter' => [
						'AND' => [[
							'Field' => [
								'name' => 'dsrid',
								'operator' => 'EQUALS',
								'value' => $dsrId]]]]]]]];

		$apis = $this->fullDataTable($tableConfig);
		return $apis;
	}

	protected function _updateSessionCookies() {
		$config = $this->client()->config;
		if (empty($config['request']['cookies'])) {
			CakeSession::delete('Collibra.cookies');
		} else {
			CakeSession::write('Collibra.cookies', $config['request']['cookies']);
		}
	}

	public function request($options=array()){
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
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($params))
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
				'code: '. $this->code.'<br>'.
				'info: '. print_r($this->info).'<br>'.
				'error: '. implode('<br>', $this->errors) .'<br>';
			//exit;
			echo $url.'<br>';
		}*/
		return $response;
	}
}
