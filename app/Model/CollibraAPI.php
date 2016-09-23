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

	public function dataTable($config) {
		$response = $this->postJSON("output/data_table", json_encode($config));
		if (!($response && $response->isOk())) {
			return null;
		}
		return @json_decode($response->body());
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
		$byuInfo = ClassRegistry::init('BYUWS')->personalSummary($netId);
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
	public function photo($userResourceId, $update = null) {
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
		$photo = $this->get("user/{$userResourceId}/avatar", ['raw' => true]);
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

	public function importSwagger($swagger) {
		$hostCommunityId = $this->findCommunityByName($swagger['host'], Configure::read('Collibra.apiCommunity'));
		if (empty($hostCommunityId)) {
			$this->errors[] = "Host \"{$swagger['host']}\" does not exist in Collibra";
			return false;
		}

		$vocabularyId = $this->createVocabulary($swagger['basePath'], $hostCommunityId);
		if (empty($vocabularyId)) {
			$this->errors[] = "Unable to create vocabulary \"{$swagger['basePath']}\" in community \"{$swagger['host']}\"";
			return false;
		}

		//Add terms
		$query = http_build_query(['vocabulary' => $vocabularyId, 'conceptType' => Configure::read('Collibra.fieldTypeId')]);
		//For array data, PHP's http_build_query creates query/POST string in a format Collibra doesn't like,
		//so we're manually building the remaining POST string
		$signifiers = [];
		foreach ($swagger['elements'] as $field) {
			if (!empty($signifiers[$field['name']])) {
				continue;
			}
			$signifiers[$field['name']] = $field;
			$query .= '&signifier=' . urlencode($field['name']);
		}
		$termsResult = $this->post('term/multiple', $query);
		if (empty($termsResult) || !$termsResult->isOk()) {
			$this->deleteVocabulary($vocabularyId);
			$this->errors[] = "Error adding fields to \"{$swagger['basePath']}\"";
			return false;
		}

		//Link created terms to selected Business Terms
		$terms = json_decode($termsResult->body());
		if (empty($terms->termReference)) {
			return true;
		}
		$relationshipTypeId = Configure::read('Collibra.businessTermToFieldRelationId');
		foreach ($terms->termReference as $term) {
			if (empty($term->signifier) || empty($term->resourceId) || empty($signifiers[$term->signifier]['business_term'])) {
				continue;
			}
			$success = $this->post("term/{$term->resourceId}/relations", [
				'type' => $relationshipTypeId,
				'target' => $signifiers[$term->signifier]['business_term'],
				'inverse' => 'true'
			]);
			if (empty($success) || !$success->isOk()) {
				$foo = $success->body();
			}
		}
		return true;
	}

	public function findCommunityByName($name, $parentCommunity = null) {
		$query = ['searchName' => $name];
		if (!empty($parentCommunity)) {
			$query['community'] = $parentCommunity;
		}

		$search = $this->get('community/find?' . http_build_query($query), ['json' => true]);
		if (empty($search) || empty($search->communityReference)) {
			return null;
		}

		$match = null;
		foreach ($search->communityReference as $community) {
			if (!empty($community->name) && $community->name == $name) {
				$match = $community->resourceId;
				break;
			}
		}
		return $match;
	}

	public function createVocabulary($name, $communityId) {
		$success = $this->post('vocabulary', [
			'name' => $name,
			'community' => $communityId,
			'type' => Configure::read('Collibra.vocabularyTypeId')
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

	public function searchStandardLabel($term) {
		$query = [
			'query' => $term,
			'filter' => [
				'category' => ['TE'],
				'type' => [
					'asset' => [Configure::read('Collibra.businessTermTypeId')]],
				'community' => [Configure::read('Collibra.byuCommunity')]],
			'fields' => [Configure::read('Collibra.standardDataElementLabelTypeId')],
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