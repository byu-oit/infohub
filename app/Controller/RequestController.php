<?php

class RequestController extends AppController {
	public $helpers = array('Html', 'Form');
	public $uses = array('CollibraAPI', 'BYUAPI');

	function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->deny('index', 'submit', 'loadDraft');
	}

	private static function sortUsers($a, $b){
		return strcmp($a->firstName, $b->firstName);
	}

	private static function sortTerms($a, $b){
		return strcmp($a->signifier, $b->signifier);
	}

	private static function sortTermsByDomain($a, $b){
		return strcmp($a->domainname, $b->domainname);
	}

	public function addToQueue() {
		$this->autoRender = false;
		if($this->request->is('post')){
			if($this->request->data['emptyApi'] == 'true') {
				$newTermsAdded = 1;
				$apiPath = $this->request->data['t'][0];
				$apiHost = $this->request->data['apiHost'];

				$arrQueue = $this->Session->read('queue');
				foreach ($arrQueue['emptyApis'] as $path => $host) {
					if ($path == $apiPath) {
						$newTermsAdded = 0;
					}
				}

				if ($newTermsAdded) {
					$arrQueue['emptyApis'][$apiPath] = ['apiHost' => $apiHost];
				}

				$this->Session->write('queue', $arrQueue);
				echo $newTermsAdded;
			} else {
				$newTermsAdded = 0;
				$arrTerms = $this->request->data['t'];
				$arrTermIDs = $this->request->data['id'];
				$arrVocabIDs = $this->request->data['vocab'];
				$clearRelated = $this->request->data['clearRelated']=='true';
				$apiHost = empty($this->request->data['apiHost']) ? null : $this->request->data['apiHost'];
				$apiPath = empty($this->request->data['apiPath']) ? null : $this->request->data['apiPath'];

				$arrQueue = $this->Session->read('queue');
				if($clearRelated) {
					// Remove all terms in vocabularies passed and then re-add the ones selected by the user.
					foreach ($arrVocabIDs as $communityId){
						foreach ($arrQueue['businessTerms'] as $termId => $term) {
							if ($term['communityId'] == $communityId) {
								unset($arrQueue['businessTerms'][$termId]);
								break;
							}
						}
						foreach ($arrQueue['concepts'] as $termId => $term) {
							if ($term['communityId'] == $communityId) {
								unset($arrQueue['concepts'][$termId]);
								break;
							}
						}
					}
				}

				for($i=0; $i<sizeof($arrTerms); $i++){
					$term = $arrTerms[$i];
					$termID = $arrTermIDs[$i];
					$vocabID = $arrVocabIDs[$i];

					if(!empty($termID) && empty($arrQueue['businessTerms'][$termID]) && empty($arrQueue['concepts'][$termID])){ // Specified business term
						$requestable = true;
						$concept = false;
						$termResp = $this->CollibraAPI->get('term/'.$termID);
						$termResp = json_decode($termResp);

						// verify that the term is requestable
						if(!Configure::read('allowUnrequestableTerms')){
							foreach($termResp->attributeReferences->attributeReference as $attr){
								if($attr->labelReference->resourceId == Configure::read('Collibra.attribute.concept')){
									$concept = $attr->value == 'true';
								}
							}
						}

						// verify that the term is approved
						if(!Configure::read('allowUnapprovedTerms')){
							$requestable = $termResp->statusReference->signifier == 'Accepted';
						}

						if($requestable && !$concept){
							$newTermsAdded++;
							$arrQueue['businessTerms'][$termID] = ['term' => $term, 'communityId' => $vocabID, 'apiHost' => $apiHost, 'apiPath' => $apiPath];
						} else if ($requestable && $concept) {
							$newTermsAdded++;
							$arrQueue['concepts'][$termID] = ['term' => $term, 'communityId' => $vocabID, 'apiHost' => $apiHost, 'apiPath' => $apiPath];
						}
					} else if (empty($termID) && !empty($term) && empty($arrQueue['apiFields'][$term])) { // Unspecified API field
						$newTermsAdded++;
						$arrQueue['apiFields'][$term] = ['name' => end((explode(".", $term))), 'apiHost' => $apiHost, 'apiPath' => $apiPath];
					}
				}

				$this->Session->write('queue', $arrQueue);
				echo $newTermsAdded;
			}
		}
	}

	public function removeFromQueue() {
		$this->autoRender = false;
		if($this->request->is('post')){
			$termID = $this->request->data['id'];
			$arrQueue = $this->Session->read('queue');
			if(array_key_exists($termID, $arrQueue['businessTerms'])) {
				unset($arrQueue['businessTerms'][$termID]);
			} else if (array_key_exists($termID, $arrQueue['concepts'])) {
				unset($arrQueue['concepts'][$termID]);
			} else if (array_key_exists($termID, $arrQueue['apiFields'])) {
				unset($arrQueue['apiFields'][$termID]);
			} else if (array_key_exists($termID, $arrQueue['emptyApis'])) {
				unset($arrQueue['emptyApis'][$termID]);
			}
			$this->Session->write('queue', $arrQueue);
		}
	}

	public function clearQueue() {
		$this->autoRender = false;
		$this->Session->delete('queue');
	}

	public function getQueueSize() {
		$this->autoRender = false;

		$arrQueue = $this->Session->read('queue');
		echo  sizeof($arrQueue['businessTerms']) +
			  sizeof($arrQueue['concepts']) +
			  sizeof($arrQueue['apiFields']) +
			  sizeof($arrQueue['emptyApis']);
	}

	public function saveFormFields() {
		$this->autoRender = false;
		$this->Session->write('inProgressFormFields', $this->request->data);
	}

	public function cartDropdown() {
		$this->autoRender = false;
		$responseHTML = '';
		$this->loadModel('CollibraAPI');
		$draftId = $this->CollibraAPI->checkForDSRDraft($this->Auth->user('username'));
		$hasUnloadedDraft =	!empty($draftId) && !$this->Session->check('draftLoaded');

		$arrQueue = $this->Session->read('queue');
		$responseHTML=  '<h3>Requested Items: '.(count($arrQueue['businessTerms']) + count($arrQueue['concepts']) + count($arrQueue['apiFields']) + count($arrQueue['emptyApis'])).'</h3>'.
			'<a class="close" href="javascript: hideRequestQueue()">X</a>'.
			'<div class="arrow"></div>'.
			'<a class="clearQueue" href="javascript: clearRequestQueue()">Empty cart</a>';
		if ($hasUnloadedDraft) {
			$responseHTML .= '<a class="loadDraft" href="/request/loadDraft">Load Draft</a>'.
				'<a class="deleteDraft" href="javascript: deleteDraft()">Delete Draft</a>';
		}
		if(
			!empty($arrQueue['businessTerms']) ||
			!empty($arrQueue['concepts']) ||
			!empty($arrQueue['apiFields']) ||
			!empty($arrQueue['emptyApis'])
		){
			$responseHTML .= '<a class="btn-orange" href="/request">View Request</a>';
		}
		echo $responseHTML;
	}

	public function success() {
	}

	public function addCollaborator($dsrId, $personId) {
		$this->autoRender = false;

		if (empty($dsrId) || empty($personId)) {
			return '{"success":0,"message":"Bad request"}';
		}

		$person = $this->BYUAPI->personalSummary($personId);
		if (!isset($person)) {
			return '{"success":0,"message":"Person\'s information could not be loaded"}';
		}

		$resp = $this->CollibraAPI->get('term/'.$dsrId);
		$request = json_decode($resp);

		foreach($request->attributeReferences->attributeReference as $attr) {
			if ($attr->labelReference->signifier == 'Requester Net Id' && $attr->value == $person->identifiers->net_id) {
				return '{"success":0,"message":"This person is already a collaborator on this request."}';
			}
		}

		if ($request->conceptType->resourceId != Configure::read('Collibra.type.isaRequest')) {
			$parent = $this->CollibraAPI->getDataUsageParent($dsrId);
			return $this->addCollaborator($parent[0]->id, $personId);
		}

		$postData['value'] = $person->identifiers->net_id;
		$postData['representation'] = $dsrId;
		$postData['label'] = Configure::read('Collibra.attribute.isaRequestNetId');
		$postString = http_build_query($postData);
		$formResp = $this->CollibraAPI->post('term/'.$dsrId.'/attributes', $postString);
		$formResp = json_decode($formResp);
		if (!isset($formResp)) {
			return '{"success":0,"message":"We had a problem getting to Collibra"}';
		}

		// Add to DSAs as well
		$request->dataUsages = $this->CollibraAPI->getDataUsages($dsrId);
		foreach($request->dataUsages as $du) {
			$postData['representation'] = $du->id;
			$postString = http_build_query($postData);
			$formResp = $this->CollibraAPI->post('term/'.$du->id.'/attributes', $postString);
			$formResp = json_decode($formResp);
			if (!isset($formResp)) {
				return '{"success":0,"message":"We had a problem getting to Collibra"}';
			}
		}

		return '{"success":1,"person":'.json_encode($person).'}';
	}

	public function removeCollaborator($dsrId, $netId) {
		$this->autoRender = false;

		if (empty($dsrId) || empty($netId)) {
			return json_encode(['success' => 0, 'message' => 'DSR ID or Net ID missing.']);
		}

		$resp = $this->CollibraAPI->get('term/'.$dsrId);
		$request = json_decode($resp);

		if ($request->conceptType->resourceId != Configure::read('Collibra.type.isaRequest')) {
			$parent = $this->CollibraAPI->getDataUsageParent($dsrId);
			return $this->removeCollaborator($parent[0]->id, $netId);
		}

		$request->dataUsages = $this->CollibraAPI->getDataUsages($dsrId);

		foreach ($request->dataUsages as $du) {
			$resp = $this->CollibraAPI->get('term/'.$du->id);
			$dataUsage = json_decode($resp);
			foreach ($dataUsage->attributeReferences->attributeReference as $attr) {
				if ($attr->labelReference->signifier == 'Requester Net Id' && $attr->value == $netId) {
					$this->CollibraAPI->delete('attribute/'.$attr->resourceId);
					break;
				}
			}
		}

		foreach ($request->attributeReferences->attributeReference as $attr) {
			if ($attr->labelReference->signifier == 'Requester Net Id' && $attr->value == $netId) {
				$this->CollibraAPI->delete('attribute/'.$attr->resourceId);
				break;
			}
		}

		return json_encode(['success' => 1, 'message' => "This person is no longer a collaborator on \"{$request->signifier}\"."]);
	}

	public function saveDraft() {
		$this->autoRender = false;
		$netId = $this->Auth->user('username');

		//First check if there already is a draft for this user; if so, delete it
		$this->loadModel('CollibraAPI');
		$draft = $this->CollibraAPI->checkForDSRDraft($netId);
		if (!empty($draft)) {
			$this->CollibraAPI->delete('term/'.$draft[0]->id);
		}

		$resp = $this->CollibraAPI->post('term', [
			'vocabulary' => Configure::read('Collibra.vocabulary.dataSharingRequests'),
			'signifier' => "DRAFT-{$netId}",
			'conceptType' => Configure::read('Collibra.type.dataSharingRequestDraft')
		]);
		$resp = json_decode($resp);
		$draftId = $resp->resourceId;

		$arrQueue = $this->Session->read('queue');
		$this->request->data['descriptionOfInformation'] = json_encode($arrQueue);

		foreach ($this->request->data as $label => $value) {
			if (empty($value)) continue;
			$postData['label'] = Configure::read("Collibra.formFields.{$label}");
			$postData['value'] = $value;
			$postString = http_build_query($postData);
			$resp = $this->CollibraAPI->post('term/'.$draftId.'/attributes', $postString);
			if ($resp->code != '201') {
				$error = true;
			}
		}

		if (isset($error)) {
			return json_encode(['success' => 0]);
		}
		$this->Session->write('draftLoaded', true);
		return json_encode(['success' => 1]);
	}

	public function loadDraft() {
		$netId = $this->Auth->user('username');
		$this->loadModel('CollibraAPI');
		$draftId = $this->CollibraAPI->checkForDSRDraft($netId);

		if (empty($draftId)) {
			$this->redirect(['controller' => 'search', 'action' => 'index']);
		}

		$draft = $this->CollibraAPI->get('term/'.$draftId[0]->id);
		$draft = json_decode($draft);

		$arrLabelMatch = [
			'Requester Name' => 'name',
			'Requester Phone' => 'phone',
			'Requester Role' => 'role',
			'Requester Email' => 'email',
			'Requesting Organization' => 'requestingOrganization',
			'Sponsor Name' => 'sponsorName',
			'Sponsor Phone' => 'sponsorPhone',
			'Sponsor Role' => 'sponsorRole',
			'Sponsor Email' => 'sponsorEmail',
			'Application Name' => 'applicationName',
			'Additional Information Requested' => 'descriptionOfInformation',
			'Description of Intended Use' => 'descriptionOfIntendedUse',
			'Access Rights' => 'accessRights',
			'Access Method' => 'accessMethod',
			'Impact on System' => 'impactOnSystem'
		];
		$arrFormFields = [];
		foreach ($draft->attributeReferences->attributeReference as $attr) {
			if ($attr->labelReference->signifier == 'Additional Information Requested') {
				$arrQueue = json_decode($attr->value, true);
				$this->Session->write('queue', $arrQueue);
				continue;
			}
			$label = $arrLabelMatch[$attr->labelReference->signifier];
			$arrFormFields[$label] = $attr->value;
		}

		$this->Session->write('inProgressFormFields', $arrFormFields);
		$this->Session->write('draftLoaded', true);

		$this->redirect(['action' => 'index']);
	}

	public function deleteDraft() {
		$this->autoRender = false;
		if (!$netId = $this->Auth->user('username')) {
			return json_encode(['success' => '0', 'message' => 'User not logged in']);
		}

		$this->loadModel('CollibraAPI');
		$draftId = $this->CollibraAPI->checkForDSRDraft($netId);
		if (empty($draftId)) {
			return json_encode(['success' => '0', 'message' => 'User doesn\'t have a draft saved']);
		}

		$this->CollibraAPI->delete('term/'.$draftId[0]->id);
		return json_encode(['success' => '1']);
	}

	public function editTermsSubmit() {
		$this->autoRender = false;
		if (!$this->request->is('post')) {
			return;
		}
		if (!isset($this->request->data['dsrId'])) {
			return '{"success":0}';
		}

		if ($this->request->data['action'] == 'add') {
			$success = true;
			$arrQueue = $this->Session->read('queue');

			$postData['source'] = $this->request->data['dsrId'];
			$postData['binaryFactType'] = Configure::read('Collibra.relationship.isaRequestToTerm');
			if (isset($this->request->data['arrBusinessTerms'])) {
				foreach ($this->request->data['arrBusinessTerms'] as $termid) {
					$postData['target'] = $termid;
					$postString = http_build_query($postData);
					$resp = $this->CollibraAPI->post('relation', $postString);

					if ($resp->code != '201') {
						$success = false;
						continue;
					}

					foreach ($arrQueue['businessTerms'] as $queueId => $queueTerm) {
						if ($queueId == $termid) {
							unset($arrQueue['businessTerms'][$queueId]);
							break;
						}
					}
				}
			}

			if (isset($this->request->data['arrConcepts']) || isset($this->request->data['arrApiFields']) || isset($this->request->data['arrApis'])) {
				$additionString = "<br/><br/>Addition, ".date('Y-m-d').":";
				if (isset($this->request->data['arrConcepts'])) {
					foreach ($this->request->data['arrConcepts'] as $concept) {
						$additionString .= "<br/>".$concept['term']." (Concept), from API: ".$concept['apiPath'];
						foreach ($arrQueue['concepts'] as $queueId => $_) {
							if ($queueId == $concept['id']) {
								unset($arrQueue['concepts'][$queueId]);
								break;
							}
						}
					}
				}
				if (isset($this->request->data['arrApiFields'])) {
					foreach ($this->request->data['arrApiFields'] as $field) {
						$additionString .= "<br/>".$field['field'].", from API: ".$field['apiPath'];
						foreach ($arrQueue['apiFields'] as $path => $_) {
							if ($path == $field['field']) {
								unset($arrQueue['apiFields'][$path]);
								break;
							}
						}
					}
				}
				if (isset($this->request->data['arrApis'])) {
					foreach ($this->request->data['arrApis'] as $api) {
						$additionString .= "<br/>".$api." [No specified output fields]";
						foreach ($arrQueue['emptyApis'] as $path => $_) {
							if ($path == $api) {
								unset($arrQueue['emptyApis'][$path]);
								break;
							}
						}
					}
				}

				$resp = $this->CollibraAPI->get('term/'.$this->request->data['dsrId']);
				$request = json_decode($resp);

				foreach ($request->attributeReferences->attributeReference as $attr) {
					if ($attr->labelReference->signifier == 'Additional Information Requested') {
						$postData['value'] = $attr->value . $additionString;
						$postData['rid'] = $attr->resourceId;
						$postString = http_build_query($postData);
						$postString = preg_replace('/%0D%0A/', '<br/>', $postString);
						$formResp = $this->CollibraAPI->post('attribute/'.$attr->resourceId, $postString);
						$formResp = json_decode($formResp);

						if (!isset($formResp)) {
							$success = false;
						}
						break;
					}
				}
			}

			$this->Session->write('queue', $arrQueue);
			return $success ? '{"success":1}' : '{"success":0}';
		}
		else if ($this->request->data['action'] == 'remove') {
			$success = true;
			foreach ($this->request->data['arrIds'] as $relationrid) {
				$resp = $this->CollibraAPI->delete('relation/'.$relationrid);
				if ($resp->code != '200') {
					$success = false;
				}
			}
			return $success ? '{"success":1}' : '{"success":0}';
		}
		return '{"success":0}';
	}

	public function editTerms($dsrId) {
		if (empty($dsrId)) {
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}

		$resp = $this->CollibraAPI->get('term/'.$dsrId);
		$request = json_decode($resp);

		$netID = $this->Auth->user('username');

		$guest = true;
		foreach($request->attributeReferences->attributeReference as $attr) {
			if ($attr->labelReference->signifier == 'Requester Net Id') {
				if ($attr->value == $netID) {
					$guest = false;
					break;
				}
			}
		}

		$completedStatuses = ['Completed', 'Approved', 'Obsolete'];
		if (in_array($request->statusReference->signifier, $completedStatuses)) {
			$this->Flash->error('You cannot edit a completed Request.');
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}

		// Check whether $request is a DSR or DSA
		$isaRequest = $request->conceptType->resourceId == Configure::read('Collibra.type.isaRequest');
		if (!$isaRequest) {
			$this->Flash->error('You cannot edit the terms on a DSA.');
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}

		$request->dataUsages = $this->CollibraAPI->getDataUsages($dsrId);
		if (!empty($request->dataUsages)) {
			$this->Flash->error('You cannot edit a DSR with any associated DSAs.');
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}

		$requestId = $request->resourceId;
		$resp = $this->CollibraAPI->postJSON(
				'output/data_table',
				'{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"termrid"}},{"Column":{"fieldName":"termsignifier"}},{"Column":{"fieldName":"relationrid"}},{"Column":{"fieldName":"startDate"}},{"Column":{"fieldName":"endDate"}},{"Column":{"fieldName":"relstatusrid"}},{"Column":{"fieldName":"relstatusname"}},{"Column":{"fieldName":"communityname"}},{"Column":{"fieldName":"commrid"}},{"Column":{"fieldName":"domainname"}},{"Column":{"fieldName":"domainrid"}},{"Column":{"fieldName":"concepttypename"}},{"Column":{"fieldName":"concepttyperid"}}],"Resources":{"Term":{"Id":{"name":"termrid"},"Signifier":{"name":"termsignifier"},"Relation":{"typeId":"' . Configure::read('Collibra.relationship.isaRequestToTerm') . '","Id":{"name":"relationrid"},"StartingDate":{"name":"startDate"},"EndingDate":{"name":"endDate"},"Status":{"Id":{"name":"relstatusrid"},"Signifier":{"name":"relstatusname"}},"Filter":{"AND":[{"Field":{"name":"reltermrid", "operator":"EQUALS", "value":"'.$requestId.'"}}]},"type":"TARGET","Source":{"Id":{"name":"reltermrid"}}},"Vocabulary":{"Community":{"Name":{"name":"communityname"},"Id":{"name":"commrid"}},"Name":{"name":"domainname"},"Id":{"name":"domainrid"}},"ConceptType":[{"Signifier":{"name":"concepttypename"}, "Id":{"name":"concepttyperid"}}],"Filter":{"AND":[{"AND":[{"Field":{"name":"reltermrid", "operator":"EQUALS", "value":"'.$requestId.'"}}]}]},"Order":[{"Field":{"name":"termsignifier", "order":"ASC"}}]}},"displayStart":0,"displayLength":100}}'
		);
		$requestedTerms = json_decode($resp);

		if(isset($requestedTerms->aaData)){
			$request->terms = $requestedTerms->aaData;
		}

		$arrQueue = $this->Session->read('queue');
		$this->set(compact('request', 'guest', 'arrQueue'));
		$this->set('submitErr', isset($this->request->query['err']));
	}

	public function editSubmit($dsrId) {
		$this->autoRender = false;

		if (!$this->request->is('post')){
			header('location: /search');
			exit;
		}

		$resp = $this->CollibraAPI->get('term/'.$dsrId);
		$dsr = json_decode($resp);

		$err = false;

		foreach ($this->request->data as $id => $val) {
			if ($id == 'requestSubmit') {
				continue;
			}
			$matchFound = false;
			foreach ($dsr->attributeReferences->attributeReference as $original) {
				if ($id == $original->labelReference->resourceId) {
					$matchFound = true;
					if ($val != $original->value) {
						//Update values in Collibra database
						$postData['value'] = $val;
						$postData['rid'] = $original->resourceId;
						$postString = http_build_query($postData);
						$postString = preg_replace('/%0D%0A/', '<br/>', $postString);
						$formResp = $this->CollibraAPI->post('attribute/'.$original->resourceId, $postString);
						$formResp = json_decode($formResp);

						if (!isset($formResp)) {
							$err = true;
						}
					}
					break;
				}
			}
			if (!$matchFound && !empty($val)) {		//i.e., if the value has been left blank/empty until now
				$postData['value'] = $val;
				$postData['representation'] = $dsr->resourceId;
				$postData['label'] = $id;
				$postString = http_build_query($postData);
				$postString = preg_replace('/%0D%0A/', '<br/>', $postString);
				$formResp = $this->CollibraAPI->post('term/'.$dsr->resourceId.'/attributes', $postString);
				$formResp = json_decode($formResp);

				if (!isset($formResp)) {
					$err = true;
				}
			}
		}

		if (!$err) {
			if ($this->request->query['g'] == '0') {
				$this->redirect(['controller' => 'myaccount', 'action' => 'index', '?' => ['expand' => $dsrId]]);
			} else {
				$this->redirect(['controller' => 'request', 'action' => 'view/'.$dsrId, '?' => ['expand' => 'true']]);
			}
		} else {
			$this->redirect(['action' => 'edit/'.$dsrId, '?' => ['err' => 1]]);
		}
	}

	public function edit($dsrId) {
		if (empty($dsrId)) {
			$this->redirect(['action' => 'index']);
		}

		// Load DSR's current state
		$resp = $this->CollibraAPI->get('term/'.$dsrId);
		$request = json_decode($resp);

		$netID = $this->Auth->user('username');

		$guest = true;
		foreach($request->attributeReferences->attributeReference as $attr) {
			if ($attr->labelReference->signifier == 'Requester Net Id') {
				if ($attr->value == $netID) {
					$guest = false;
					break;
				}
			}
		}

		$completedStatuses = ['Completed', 'Approved', 'Obsolete'];
		if (in_array($request->statusReference->signifier, $completedStatuses)) {
			$this->Flash->error('You cannot edit a completed Request.');
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}

		$request->dataUsages = $this->CollibraAPI->getDataUsages($dsrId);
		if (!empty($request->dataUsages)) {
			$this->Flash->error('You cannot edit a DSR with any associated DSAs.');
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}

		// Check whether $request is a DSR or DSA
		$isaRequest = $request->conceptType->resourceId == Configure::read('Collibra.type.isaRequest');

		// load form fields for ISA workflow
		$formResp = $this->CollibraAPI->get('workflow/'.Configure::read('Collibra.isaWorkflow.id').'/form/start');
		$formResp = json_decode($formResp);

		$arrNewAttr = array();
		foreach($formResp->formProperties as $wf){
			foreach($request->attributeReferences->attributeReference as $attr){
				if($attr->labelReference->signifier == $wf->name){
					$arrNewAttr[$attr->labelReference->signifier] = $attr;
					break;
				}
			}
		}
		$request->attributeReferences->attributeReference = $arrNewAttr;

		$this->set('guest', $guest);
		$this->set('formFields', $formResp);
		$this->set('request', $request);
		$this->set('isaRequest', $isaRequest);
		$this->set('submitErr', isset($this->request->query['err']));
	}

	public function delete($dsrId) {
		$this->autoRender = false;

		if (empty($dsrId)) {
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}

		//Load DSR to check that the request isn't completed
		$resp = $this->CollibraAPI->get('term/'.$dsrId);
		$request = json_decode($resp);

		$completedStatuses = ['Completed', 'Approved', 'Obsolete'];
		if (in_array($request->statusReference->signifier, $completedStatuses)) {
			$this->Flash->error('You cannot delete a completed Request.');
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}

		$request->dataUsages = $this->CollibraAPI->getDataUsages($dsrId);
		foreach ($request->dataUsages as $du) {
			if (in_array($du->status, $completedStatuses)) {
				$this->Flash->error('You cannot delete a Request if any associated DSAs are completed.');
				$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
			}
		}

		foreach ($request->dataUsages as $du) {
			$this->delete($du->id);
		}

		$postData['status'] = Configure::read('Collibra.status.archived');
		$postString = http_build_query($postData);
		$this->CollibraAPI->post("term/{$dsrId}/status", $postString);
		$this->Flash->success('Request deleted.');
		$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
	}

	public function submit() {
		$this->autoRender = false;

		if(!$this->request->is('post')){
			header('location: /search');
			exit;
		}

		$arrQueue = $this->Session->read('queue');
		if (
			empty($arrQueue['businessTerms']) &&
			empty($arrQueue['concepts']) &&
			empty($arrQueue['apiFields']) &&
			empty($arrQueue['emptyApis'])
		) {
			$this->redirect(['action' => 'index', '?' => ['err' => 1]]);
			exit;
		}

		$businessTermIds = [];
		$apis = [];
		foreach ($arrQueue['businessTerms'] as $id => $term) {
			array_push($businessTermIds, $id);
			if (!empty($term['apiPath']) && !empty($term['apiHost'])) {
				$apis[$term['apiHost']][$term['apiPath']] = [];
			}
		}
		foreach ($arrQueue['concepts'] as $id => $term) {
			if (!empty($term['apiPath']) && !empty($term['apiHost'])) {
				$apis[$term['apiHost']][$term['apiPath']] = [];
			}
		}
		foreach ($arrQueue['apiFields'] as $fieldName => $field) {
			if (!empty($field['apiPath']) && !empty($field['apiHost'])) {
				$apis[$field['apiHost']][$field['apiPath']] = [];
			}
		}

		$additionalInformationAPIs = "";
		foreach ($arrQueue['emptyApis'] as $path => $api) {
			$additionalInformationAPIs .= "\n    {$api['apiHost']}/{$path}\n        [No specified output fields]";
		}
		$this->request->data['descriptionOfInformation'] .= $additionalInformationAPIs;

		$name = explode(' ',$this->request->data['name']);
		$firstName = $name[0];
		$lastName = '';
		if(sizeof($name)>1) $lastName = $name[1];
		$email = $this->request->data['email'];
		$phone = $this->request->data['phone'];
		$role = $this->request->data['role'];

		// create guest user to use for submitting request
		/*
		$guestUserResp = $this->CollibraAPI->post(
				'user/guest',
				['firstName' => $firstName, 'lastName' => $lastName, 'email' => $this->request->data['email']]
		);
		$guestUserResp = json_decode($guestUserResp);
		$guestID = $guestUserResp->resourceId;
		*/

		$netID = $this->Auth->user('username');
		$byuUser = $this->BYUAPI->personalSummary($netID);
		$supervisorInfo = $this->BYUAPI->supervisorLookup($netID);

		$postData['requesterNetId'] = [$byuUser->identifiers->net_id, $supervisorInfo->net_id];
		foreach($this->request->data as $key => $val){
			if (!in_array($key, ['name', 'phone', 'email', 'role', 'terms', 'apiTerms', 'requestSubmit', 'collibraUser'])) {
				$postData[$key] = $val;
			}
		}
		// add user's contact info to post
		$postData['requesterName'] = $firstName.' '.$lastName;
		$postData['requesterEmail'] = $email;
		$postData['requesterPhone'] = $phone;
		$postData['requesterRole'] = $role;

		$requiredElementsString = Configure::read('Collibra.isaWorkflow.requiredElementsString');
		$additionalElementsString = Configure::read('Collibra.isaWorkflow.additionalElementsString');
		$postData[$requiredElementsString] = !empty($businessTermIds) ? $businessTermIds : '';
		$postData['api'] = [];
		if (!empty($additionalElementsString)) {
			$postData[$additionalElementsString] = array();
			foreach ($apis as $apiHost => $apiPaths) {
				foreach ($apiPaths as $apiPath => $ignore) {
					$apiObject = $this->CollibraAPI->getApiObject($apiHost, $apiPath);
					array_push($postData['api'], $apiObject->id);
					$apiTerms = $this->CollibraAPI->getApiTerms($apiHost, $apiPath);
					foreach ($apiTerms as $term) {
						if (!empty($term->assetType) && strtolower($term->assetType) == 'fieldset') {
							continue;
						}
						if (empty($term->businessTerm[0]->termId)) {
							continue;
						}
						if (!array_key_exists($term->businessTerm[0]->termId, $arrQueue['businessTerms'])) {
							array_push($postData[$additionalElementsString], $term->businessTerm[0]->termId);
						}
					}
				}
			}
			$postData[$additionalElementsString] = array_unique($postData[$additionalElementsString]);
			if (empty($postData[$additionalElementsString])) {
				//Collibra requires "additionalElements" field to exist, even if empty,
				//but http_build_query throws out fields if null or empty array.
				//So we'll put a blank space in, which http_build_query
				//will not throw away
				$postData[$additionalElementsString] = '';
			}
		}

		foreach ($arrQueue['emptyApis'] as $path => $api) {
			$apiObject = $this->CollibraAPI->getApiObject($api['apiHost'],$path);
			array_push($postData['api'], $apiObject->id);
		}
		if (empty($postData['api'])) {
			//See above comment regarding "additionalElements"
			$postData['api'] = '';
		}

		//For array data, PHP's http_build_query creates query/POST string in a format Collibra doesn't like,
		//so we have to tweak the output a bit
		$postString = http_build_query($postData);
		$postString = preg_replace("/requesterNetId%5B[0-9]*%5D/", "requesterNetId", $postString);
		$postString = preg_replace("/api%5B[0-9]*%5D/", "api", $postString);
		$postString = preg_replace("/{$requiredElementsString}%5B[0-9]*%5D/", $requiredElementsString, $postString);
		if (!empty($additionalElementsString)) {
			$postString = preg_replace("/{$additionalElementsString}%5B[0-9]*%5D/", $additionalElementsString, $postString);
		}
		$postString = preg_replace('/%0D%0A/','<br/>',$postString);

		$formResp = $this->CollibraAPI->post(
			'workflow/'.Configure::read('Collibra.isaWorkflow.id').'/start',
			$postString
		);
		$formResp = json_decode($formResp);

		if(isset($formResp->startWorkflowResponses[0]->successmessage)){
			$processID = $formResp->startWorkflowResponses[0]->processInstanceId;

			// attempt to reindex source to make sure latest requests are displayed
			$resp = $this->CollibraAPI->post('search/re-index');

			$this->deleteDraft();

			// clear items in queue
			$this->Session->delete('queue');
			$this->Session->delete('inProgressFormFields');

			$this->redirect(['action' => 'success']);
		}else{
			$this->redirect(['action' => 'index', '?' => ['err' => 1]]);
		}
	}

	public function view($dsrId) {
		if (empty($dsrId)) {
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}
		$expand = '';
		if (isset($this->request->query['expand'])) {
			$expand = $dsrId;
		}

		$netID = $this->Auth->user('username');

		$this->loadModel('CollibraAPI');
		$resp = $this->CollibraAPI->get('term/'.$dsrId);
		$dsr = json_decode($resp);

		$parent = $dsr->conceptType->resourceId == Configure::read('Collibra.type.isaRequest');

		$dsr->roles = $this->CollibraAPI->getResponsibilities($dsr->vocabularyReference->resourceId);
		$dsr->policies = $this->CollibraAPI->getAssetPolicies($dsrId);
		if($parent) {
			$dsr->dataUsages = $this->CollibraAPI->getDataUsages($dsr->resourceId);
		} else {
			$dsr->parent = $this->CollibraAPI->getDataUsageParent($dsr->resourceId);
		}

		// load terms submitted in request
		////////////////////////////////////////////
		$termRequestId = $parent ? $dsr->resourceId : $dsr->parent[0]->id;
		$resp = $this->CollibraAPI->postJSON(
				'output/data_table',
				'{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"termrid"}},{"Column":{"fieldName":"termsignifier"}},{"Column":{"fieldName":"relationrid"}},{"Column":{"fieldName":"startDate"}},{"Column":{"fieldName":"endDate"}},{"Column":{"fieldName":"relstatusrid"}},{"Column":{"fieldName":"relstatusname"}},{"Column":{"fieldName":"communityname"}},{"Column":{"fieldName":"commrid"}},{"Column":{"fieldName":"domainname"}},{"Column":{"fieldName":"domainrid"}},{"Column":{"fieldName":"concepttypename"}},{"Column":{"fieldName":"concepttyperid"}}],"Resources":{"Term":{"Id":{"name":"termrid"},"Signifier":{"name":"termsignifier"},"Relation":{"typeId":"' . Configure::read('Collibra.relationship.isaRequestToTerm') . '","Id":{"name":"relationrid"},"StartingDate":{"name":"startDate"},"EndingDate":{"name":"endDate"},"Status":{"Id":{"name":"relstatusrid"},"Signifier":{"name":"relstatusname"}},"Filter":{"AND":[{"Field":{"name":"reltermrid", "operator":"EQUALS", "value":"'.$termRequestId.'"}}]},"type":"TARGET","Source":{"Id":{"name":"reltermrid"}}},"Vocabulary":{"Community":{"Name":{"name":"communityname"},"Id":{"name":"commrid"}},"Name":{"name":"domainname"},"Id":{"name":"domainrid"}},"ConceptType":[{"Signifier":{"name":"concepttypename"}, "Id":{"name":"concepttyperid"}}],"Filter":{"AND":[{"AND":[{"Field":{"name":"reltermrid", "operator":"EQUALS", "value":"'.$termRequestId.'"}}]}]},"Order":[{"Field":{"name":"termsignifier", "order":"ASC"}}]}},"displayStart":0,"displayLength":100}}'
		);
		$requestedTerms = json_decode($resp);
		// add property to request object to hold terms
		if(isset($requestedTerms->aaData)){
			$dsr->termGlossaries = array();
			foreach ($requestedTerms->aaData as $term) {
				if (array_key_exists($term->domainname, $dsr->termGlossaries)) {
					array_push($dsr->termGlossaries[$term->domainname], $term);
				} else {
					$dsr->termGlossaries[$term->domainname] = array($term);
				}
			}
		}

		// load additionally included terms
		////////////////////////////////////////////
		$resp = $this->CollibraAPI->getAdditionallyIncludedTerms($termRequestId);
		if (!empty($resp)) {
			$dsr->additionallyIncluded = new stdClass();
			$dsr->additionallyIncluded->termGlossaries = [];
			foreach ($resp as $term) {
				if (array_key_exists($term->domainname, $dsr->additionallyIncluded->termGlossaries)) {
					array_push($dsr->additionallyIncluded->termGlossaries[$term->domainname], $term);
				} else {
					$dsr->additionallyIncluded->termGlossaries[$term->domainname] = array($term);
				}
			}
		}

		$arrNewAttr = [];
		$arrCollaborators = array();
			foreach($dsr->attributeReferences->attributeReference as $attr){
				if ($attr->labelReference->signifier == 'Requester Net Id') {
					$person = $this->BYUAPI->personalSummary($attr->value);
					unset($person->person_summary_line, $person->personal_information, $person->student_information, $person->relationships);
					array_push($arrCollaborators, $person);
					continue;
				}
				$arrNewAttr[$attr->labelReference->signifier] = $attr;
			}
			$arrNewAttr['Collaborators'] = $arrCollaborators;
		$dsr->attributeReferences->attributeReference = $arrNewAttr;

		if ($parent) {
			for ($i = 0; $i < sizeof($dsr->dataUsages); $i++) {
				$resp = $this->CollibraAPI->get('term/'.$dsr->dataUsages[$i]->id);
				$resp = json_decode($resp);
				$dsr->dataUsages[$i]->attributeReferences = $resp->attributeReferences;
			}
		}

		$this->set('netID', $netID);
		$this->set('request', $dsr);
		$this->set('parent', $parent);
		$this->set('expand', $expand);
	}

	public function printView($dsrId) {
		if (empty($dsrId)) {
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}
		$this->autoLayout = false;

		$this->loadModel('CollibraAPI');
		$resp = $this->CollibraAPI->get('term/'.$dsrId);
		$dsr = json_decode($resp);

		$parent = $dsr->conceptType->resourceId == Configure::read('Collibra.type.isaRequest');

		$dsr->roles = $this->CollibraAPI->getResponsibilities($dsr->vocabularyReference->resourceId);
		$dsr->policies = $this->CollibraAPI->getAssetPolicies($dsrId);
		if($parent) {
			$dsr->dataUsages = $this->CollibraAPI->getDataUsages($dsr->resourceId);
		} else {
			$dsr->parent = $this->CollibraAPI->getDataUsageParent($dsr->resourceId);
		}

		// load terms submitted in request
		////////////////////////////////////////////
		$termRequestId = $parent ? $dsr->resourceId : $dsr->parent[0]->id;
		$resp = $this->CollibraAPI->postJSON(
				'output/data_table',
				'{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"termrid"}},{"Column":{"fieldName":"termsignifier"}},{"Column":{"fieldName":"relationrid"}},{"Column":{"fieldName":"startDate"}},{"Column":{"fieldName":"endDate"}},{"Column":{"fieldName":"relstatusrid"}},{"Column":{"fieldName":"relstatusname"}},{"Column":{"fieldName":"communityname"}},{"Column":{"fieldName":"commrid"}},{"Column":{"fieldName":"domainname"}},{"Column":{"fieldName":"domainrid"}},{"Column":{"fieldName":"concepttypename"}},{"Column":{"fieldName":"concepttyperid"}}],"Resources":{"Term":{"Id":{"name":"termrid"},"Signifier":{"name":"termsignifier"},"Relation":{"typeId":"' . Configure::read('Collibra.relationship.isaRequestToTerm') . '","Id":{"name":"relationrid"},"StartingDate":{"name":"startDate"},"EndingDate":{"name":"endDate"},"Status":{"Id":{"name":"relstatusrid"},"Signifier":{"name":"relstatusname"}},"Filter":{"AND":[{"Field":{"name":"reltermrid", "operator":"EQUALS", "value":"'.$termRequestId.'"}}]},"type":"TARGET","Source":{"Id":{"name":"reltermrid"}}},"Vocabulary":{"Community":{"Name":{"name":"communityname"},"Id":{"name":"commrid"}},"Name":{"name":"domainname"},"Id":{"name":"domainrid"}},"ConceptType":[{"Signifier":{"name":"concepttypename"}, "Id":{"name":"concepttyperid"}}],"Filter":{"AND":[{"AND":[{"Field":{"name":"reltermrid", "operator":"EQUALS", "value":"'.$termRequestId.'"}}]}]},"Order":[{"Field":{"name":"termsignifier", "order":"ASC"}}]}},"displayStart":0,"displayLength":100}}'
		);
		$requestedTerms = json_decode($resp);
		// add property to request object to hold terms
		if(isset($requestedTerms->aaData)){
			$dsr->termGlossaries = array();
			foreach ($requestedTerms->aaData as $term) {
				if (array_key_exists($term->domainname, $dsr->termGlossaries)) {
					array_push($dsr->termGlossaries[$term->domainname], $term);
				} else {
					$dsr->termGlossaries[$term->domainname] = array($term);
				}
			}
		}

		// load additionally included terms
		////////////////////////////////////////////
		$resp = $this->CollibraAPI->getAdditionallyIncludedTerms($termRequestId);
		if (!empty($resp)) {
			$dsr->additionallyIncluded = new stdClass();
			$dsr->additionallyIncluded->termGlossaries = [];
			foreach ($resp as $term) {
				if (array_key_exists($term->domainname, $dsr->additionallyIncluded->termGlossaries)) {
					array_push($dsr->additionallyIncluded->termGlossaries[$term->domainname], $term);
				} else {
					$dsr->additionallyIncluded->termGlossaries[$term->domainname] = array($term);
				}
			}
		}

		// sort request attribute data based on workflow form field order
		$workflowResp = $this->CollibraAPI->get('workflow/'.Configure::read('Collibra.isaWorkflow.id').'/form/start');
		$workflowResp = json_decode($workflowResp);

		$arrNewAttr = [];
		foreach($workflowResp->formProperties as $wf){
			foreach($dsr->attributeReferences->attributeReference as $attr){
				if($attr->labelReference->signifier == $wf->name){
					$arrNewAttr[$attr->labelReference->signifier] = $attr;
					break;
				}
			}
		}
		$dsr->attributeReferences->attributeReference = $arrNewAttr;

		if ($parent) {
			for ($i = 0; $i < sizeof($dsr->dataUsages); $i++) {
				$resp = $this->CollibraAPI->get('term/'.$dsr->dataUsages[$i]->id);
				$resp = json_decode($resp);
				$dsr->dataUsages[$i]->attributeReferences = $resp->attributeReferences;
			}
		}

		$this->set('request', $dsr);
		$this->set('parent', $parent);
	}

	public function index() {
		$netID = $this->Auth->user('username');
		$byuUser = $this->BYUAPI->personalSummary($netID);
		$supervisorInfo = $this->BYUAPI->supervisorLookup($netID);

		// make sure terms have been added to the users's queue
		$arrQueue = $this->Session->read('queue');
		if(
			empty($arrQueue['businessTerms']) &&
			empty($arrQueue['concepts']) &&
			empty($arrQueue['apiFields']) &&
			empty($arrQueue['emptyApis'])
		) {
			header('location: /search');
			exit;
		}

		//$termID = $this->request->params['pass'][0];
		$requestFilter = '{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"createdOn"}},{"Column":{"fieldName":"termrid"}},{"Column":{"fieldName":"termsignifier"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202longExpr"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202rid"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldName":"userRole00000000000000000000000000005016rid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole00000000000000000000000000005016gender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole00000000000000000000000000005016fn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole00000000000000000000000000005016ln"}}],"name":"Role00000000000000000000000000005016"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole00000000000000000000000000005016grid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole00000000000000000000000000005016ggn"}}],"name":"Role00000000000000000000000000005016g"}},{"Column":{"fieldName":"statusname"}},{"Column":{"fieldName":"statusrid"}},{"Column":{"fieldName":"communityname"}},{"Column":{"fieldName":"commrid"}},{"Column":{"fieldName":"domainname"}},{"Column":{"fieldName":"domainrid"}},{"Column":{"fieldName":"concepttypename"}},{"Column":{"fieldName":"concepttyperid"}}],'.
			'"Resources":{"Term":{"CreatedOn":{"name":"createdOn"},"Id":{"name":"termrid"},"Signifier":{"name":"termsignifier"},"StringAttribute":[{"Value":{"name":"Attr00000000000000000000000000000202"},"LongExpression":{"name":"Attr00000000000000000000000000000202longExpr"},"Id":{"name":"Attr00000000000000000000000000000202rid"},"labelId":"' . Configure::read('Collibra.attribute.definition') . '"}],"Member":[{"User":{"Gender":{"name":"userRole00000000000000000000000000005016gender"},"FirstName":{"name":"userRole00000000000000000000000000005016fn"},"Id":{"name":"userRole00000000000000000000000000005016rid"},"LastName":{"name":"userRole00000000000000000000000000005016ln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016sig"},"name":"Role00000000000000000000000000005016","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016rid"}},"roleId":"00000000-0000-0000-0000-000000005016"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016g"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016grid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005016ggn"},"Id":{"name":"groupRole00000000000000000000000000005016grid"}},"roleId":"00000000-0000-0000-0000-000000005016"}],"Status":{"Signifier":{"name":"statusname"},"Id":{"name":"statusrid"}},"Vocabulary":{"Community":{"Name":{"name":"communityname"},"Id":{"name":"commrid"}},"Name":{"name":"domainname"},"Id":{"name":"domainrid"}},"ConceptType":[{"Signifier":{"name":"concepttypename"},"Id":{"name":"concepttyperid"}}],'.
			'"Filter":{'.
			'   "AND":['.
			'        {'.
			'           "OR":[';

		$apis = [];
		foreach ($arrQueue['businessTerms'] as $termId => $term){
			$requestFilter .= '{"Field":{'.
				'   "name":"termrid",'.
				'   "operator":"EQUALS",'.
				'   "value":"'.$termId.'"'.
				'}},';
			if (!empty($term['apiPath']) && !empty($term['apiHost'])) {
				$apis[$term['apiHost']][$term['apiPath']] = [];
			}
		}
		foreach ($arrQueue['apiFields'] as $fieldName => $field) {
			if (!empty($field['apiPath']) && !empty($field['apiHost'])) {
				$apis[$field['apiHost']][$field['apiPath']] = [];
			}
		}

		$preFilled = [];
		foreach ($apis as $apiHost => $apiPaths) {
			foreach ($apiPaths as $apiPath => $ignore) {
				$apiTerms = $this->CollibraAPI->getApiTerms($apiHost, $apiPath);
				foreach ($apiTerms as $term) {
					if (!empty($term->assetType) && strtolower($term->assetType) == 'fieldset') {
						continue;
					}
					if (empty($term->businessTerm[0]->termId)) {
						if (array_key_exists($term->name, $arrQueue['apiFields'])) {
							$apis[$apiHost][$apiPath]['unmapped']['requested'][] = $term->name;
						} else {
							$apis[$apiHost][$apiPath]['unmapped']['unrequested'][] = $term->name;
						}
					} else {
						if (array_key_exists($term->businessTerm[0]->termId, $arrQueue['concepts'])) {
							$apis[$apiHost][$apiPath]['requestedConcept'][] = $term->businessTerm[0]->term;
						} else if (array_key_exists($term->businessTerm[0]->termId, $arrQueue['businessTerms'])) {
							$apis[$apiHost][$apiPath]['requestedBusinessTerm'][] = $term->businessTerm[0]->term;
						} else {
							$apis[$apiHost][$apiPath]['unrequested'][] = $term->businessTerm[0]->term;
						}
					}
				}
			}
		}
		if (!empty($apis)) {
			$apiList = "Requested APIs:\n";
			foreach ($apis as $apiHost => $apiPaths) {
				foreach ($apiPaths as $apiPath => $term) {
					$apiList .= "    {$apiHost}/{$apiPath}\n";
					if (!empty($term['requestedBusinessTerm'])) {
						$term['requestedBusinessTerm'] = array_unique($term['requestedBusinessTerm']);
						$apiList .= "        Requested business terms:\n            " . implode("\n            ", $term['requestedBusinessTerm']) . "\n";
					}
					if (!empty($term['requestedConcept'])) {
						$term['requestedConcept'] = array_unique($term['requestedConcept']);
						$apiList .= "        Requested conceptual terms:\n            " . implode("\n            ", $term['requestedConcept']) . "\n";
					}
					if (!empty($term['unrequested'])) {
						$term['unrequested'] = array_unique($term['unrequested']);
						$apiList .= "        Unrequested terms:\n            " . implode("\n            ", $term['unrequested']) . "\n";
					}
					if (!empty($term['unmapped'])) {
						$apiList .= "        Fields with no Business Terms:\n";
						if (!empty($term['unmapped']['requested'])) {
							$apiList .= "            Requested:\n                " . implode("\n                ", $term['unmapped']['requested']) . "\n";
						}
						if (!empty($term['unmapped']['unrequested'])) {
							$apiList .= "            Unrequested:\n                " . implode("\n                ", $term['unmapped']['unrequested']) . "\n";
						}
					}
					$apiList .= "\n";
				}
			}
			$preFilled['descriptionOfInformation'] = $apiList;
		}

		// If the customer's already filled in some of the request form, retrieve their work
		if ($this->Session->check('inProgressFormFields')) {
			$savedFields = $this->Session->read('inProgressFormFields');
			foreach ($savedFields as $id => $val) {
				if (!empty($val)) {
					$preFilled[$id] = $val;
				}
			}
		}

		$requestFilter = substr($requestFilter, 0, strlen($requestFilter)-1);

		$requestFilter .= ']'.
			'        }'.
			'     ]'.
			'}'.
			',"Order":['.
			'   {"Field":{"name":"termsignifier","order":"ASC"}}'.
			']'.
			'}'.
			'},"displayStart":0,"displayLength":100}}';

		$termResp = $this->CollibraAPI->postJSON('output/data_table', $requestFilter);
		$termResp = json_decode($termResp);
		//usort($termResp->aaData, 'self::sortTermsByDomain');
		if (isset($termResp->aaData)) {
			foreach ($termResp->aaData as $term) {
				$domains[]  = $term->domainname;
				$termNames[] = $term->termsignifier;
				$term->apihost = $arrQueue['businessTerms'][$term->termrid]['apiHost'];
				$term->apipath = $arrQueue['businessTerms'][$term->termrid]['apiPath'];
			}
			array_multisort($domains, SORT_ASC, $termNames, SORT_ASC, $termResp->aaData);
		}

		// load form fields for ISA workflow
		$formResp = $this->CollibraAPI->get('workflow/'.Configure::read('Collibra.isaWorkflow.id').'/form/start');
		$formResp = json_decode($formResp);

		$this->set('formFields', $formResp);
		$this->set('termDetails', $termResp);

		$psName = '';
		$psPhone = '';
		$psEmail = '';
		$psRole = '';
		$psDepartment = '';
		$psReportsToName = '';
		if(isset($byuUser->names->preferred_name)){
			$psName = $byuUser->names->preferred_name;
		}
		if(isset($byuUser->contact_information->work_phone)){
			$psPhone = $byuUser->contact_information->work_phone;
		}
		if(isset($byuUser->contact_information->email)){
			$psEmail = $byuUser->contact_information->email;
		}
		if(isset($byuUser->employee_information->job_title)){
			$psRole = $byuUser->employee_information->job_title;
		}
		if(isset($byuUser->employee_information->reportsToName)){
			$psReportsToName = $byuUser->employee_information->reportsToName;
		}
		if (!empty($byuUser->employee_information->department)) {
			$psDepartment = $byuUser->employee_information->department;
		}

		$this->set(compact('preFilled', 'arrQueue', 'psName', 'psPhone', 'psEmail', 'psRole', 'psDepartment', 'psReportsToName', 'supervisorInfo'));
		$this->set('submitErr', isset($this->request->query['err']));
	}
}
