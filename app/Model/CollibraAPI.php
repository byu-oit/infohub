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
		$photo = $this->get("user/{$userResourceId}/avatar?width=300&height=300", ['raw' => true]);
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
							['Column' => ['fieldName' => 'termId']],
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
							'Source' => [
								'Id' => ['name' => 'termId'],
								'Vocabulary' => [
									'Community' => [
										'Id' => ['name' => 'termCommunityId']]],
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

	public function searchTerms($query, $limit = 5, $community = null) {
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
			'length' => 25];
		$options = array_merge($defaults, $passedOptions);

		$tableConfig = ["TableViewConfig" => [
			"Columns" => [
				["Column" => ["fieldName" => "termrid"]],
				["Column" => ["fieldName" => "termsignifier"]],
				["Column" => ["fieldName" => "description"]],
				["Column" => ["fieldName" => "lastModified"]],
				["Column" => ["fieldName" => "domainrid"]],
				["Column" => ["fieldName" => "domainname"]],
				["Column" => ["fieldName" => "requestable"]],
				["Column" => ["fieldName" => "classification"]],
				["Column" => ["fieldName" => "commrid"]],
				["Column" => ["fieldName" => "communityname"]],
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
						["Column" => ["fieldName" => "synonymid"]]]]]],
			"Resources" => [
				"Term" => [
					"Id" => ["name" => "termrid"],
					"Signifier" => ["name" => "termsignifier"],
					"LastModified" => ["name" => "lastModified"],
					"BooleanAttribute" => [[
						"Value" => ["name" => "requestable"],
						"labelId" => Configure::read('Collibra.attribute.requestable')]],
					"StringAttribute" => [[
						"LongExpression" => ["name" => "description"],
						"labelId" => Configure::read('Collibra.attribute.definition')]],
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
						"Name" => ["name" => "domainname"]
					],
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
							"Signifier" => ["name" => "synonymname"]]]],
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
		$vocabularyId = $this->createVocabulary("{$swagger['basePath']}/{$swagger['version']}", $hostCommunity->resourceId);
		if (empty($vocabularyId)) {
			$this->errors[] = "Unable to create vocabulary \"{$swagger['basePath']}/{$swagger['version']}\" in community \"{$swagger['host']}\"";
			return false;
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
			if (!empty($element['type']) && $element['type'] == 'fieldset') {
				$fieldSets[$name] = $name;
			} else {
				$fields[$name] = $name;
			}
			$elements[$name] = $element;
		}
		//Add fields
		$fieldsResult = $this->addTermsToVocabulary($vocabularyId, Configure::read('Collibra.type.field'), $fields);
		if (empty($fieldsResult) || !$fieldsResult->isOk()) {
			$this->errors[] = "Error adding fields to \"{$swagger['basePath']}/{$swagger['version']}\"";
			$this->deleteVocabulary($vocabularyId);
			return false;
		}
		$fieldSetsResult = $this->addTermsToVocabulary($vocabularyId, Configure::read('Collibra.type.fieldSet'), $fieldSets);
		if (empty($fieldSetsResult) || !$fieldSetsResult->isOk()) {
			$this->errors[] = "Error adding fieldSets to \"{$swagger['basePath']}/{$swagger['version']}\"";
			$this->deleteVocabulary($vocabularyId);
			return false;
		}

		//Link created terms to selected Business Terms
		foreach (['fieldsResult', 'fieldSetsResult'] as $result) {
			$terms = json_decode(${$result}->body());
			if (empty($terms->termReference)) {
				continue;
			}
			$relationshipTypeId = Configure::read('Collibra.relationship.termToField');
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
			if (empty($term['id']) || empty($term['business_term'])) {
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

	public function searchStandardLabel($term) {
		$query = [
			'query' => $term,
			'filter' => [
				'category' => ['TE'],
				'type' => [
					'asset' => [Configure::read('Collibra.businessTermTypeId')]],
				'community' => [Configure::read('Collibra.community.byu')]],
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

	public function getResponsibilities($resourceId) {
		$members = $this->get("member/find/all?resource={$resourceId}", ['json' => true]);
		if (empty($members->memberReference)) {
			return [];
		}
		$output = [];
		foreach ($members->memberReference as $member) {
			$output[$member->role->signifier][] = $member->ownerUser;
		}
		return $output;
	}

	public function getDataUsages($dsaId) {
		$tableConfig = ['TableViewConfig' => [
			'Columns' => [
				['Column' => ['fieldName' => 'id']],
				['Column' => ['fieldName' => 'signifier']],
				['Column' => ['fieldName' => 'status']]],
			'Resources' => [
				'Term' => [
					'Id' => ['name' => 'id'],
					'Signifier' => ['name' => 'signifier'],
					'Status' => [
						'Signifier' => ['name' => 'status']],
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
			$usage->roles = $this->getResponsibilities($usage->id);
		}
		return $usages;
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
