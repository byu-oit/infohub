<?php

class RequestController extends AppController {
	public $helpers = array('Html', 'Form');
	public $uses = array('CollibraAPI', 'BYUAPI');

	function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->deny('index', 'submit');
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
				foreach ($arrQueue->emptyApis as $path => $host) {
					if ($path == $apiPath) {
						$newTermsAdded = 0;
					}
				}

				if ($newTermsAdded) {
					$arrQueue->emptyApis[$apiPath] = ['apiHost' => $apiHost];
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
				if(!empty($arrQueue->businessTerms) && $clearRelated) {
					// Remove all terms in vocabularies passed and then re-add the ones selected by the user.
					foreach ($arrVocabIDs as $communityId){
						foreach ($arrQueue->businessTerms as $termId => $term) {
							if ($term['communityId'] == $communityId) {
								unset($arrQueue->businessTerms[$termId]);
							}
						}
					}
				}

				for($i=0; $i<sizeof($arrTerms); $i++){
					$term = $arrTerms[$i];
					$termID = $arrTermIDs[$i];
					$vocabID = $arrVocabIDs[$i];

					if(!empty($termID) && empty($arrQueue->businessTerms[$termID])){ // Specified business term
						$requestable = true;
						$termResp = $this->CollibraAPI->get('term/'.$termID);
						$termResp = json_decode($termResp);

						// verify that the term is requestable
						if(!Configure::read('allowUnrequestableTerms')){
							foreach($termResp->attributeReferences->attributeReference as $attr){
								if($attr->labelReference->resourceId == Configure::read('Collibra.attribute.concept')){
									$requestable = $attr->value != 'true';
								}
							}
						}

						// verify that the term is approved
						if(!Configure::read('allowUnapprovedTerms')){
							$requestable = $termResp->statusReference->signifier == 'Accepted';
						}

						if($requestable){
							$newTermsAdded++;
							$arrQueue->businessTerms[$termID] = ['term' => $term, 'communityId' => $vocabID, 'apiHost' => $apiHost, 'apiPath' => $apiPath];
						}
					} else if (empty($termID) && !empty($term) && empty($arrQueue->apiFields[$term])) { // Unspecified API field
						$newTermsAdded++;
						$arrQueue->apiFields[$term] = ['apiHost' => $apiHost, 'apiPath' => $apiPath];
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
			if(array_key_exists($termID, $arrQueue->businessTerms)) {
				unset($arrQueue->businessTerms[$termID]);
			} else if (array_key_exists($termID, $arrQueue->apiFields)) {
				unset($arrQueue->apiFields[$termID]);
			} else if (array_key_exists($termID, $arrQueue->emptyApis)) {
				unset($arrQueue->emptyApis[$termID]);
			}
			$this->Session->write('queue', $arrQueue);
		}
	}

	public function clearQueue() {
		$this->autoRender = false;
		$this->Session->delete('queue');
	}

	public function getQueueJSArray() {
		$this->autoRender = false;
		$JS = '';

		$arrQueue = $this->Session->read('queue');
		$JS .= implode(',', array_keys($arrQueue->businessTerms));
		$JS .= implode(',', array_keys($arrQueue->apiFields));
		$JS .= implode(',', array_keys($arrQueue->emptyApis));
		echo $JS;
	}

	public function saveFormFields() {
		$this->autoRender = false;
		$this->Session->write('inProgressFormFields', $this->request->data);
	}

	public function cartDropdown() {
		$this->autoRender = false;
		$responseHTML = '';

		$arrQueue = $this->Session->read('queue');
		$responseHTML=  '<h3>Requested Items: '.(count($arrQueue->businessTerms) + count($arrQueue->apiFields) + count($arrQueue->emptyApis)).'</h3>'.
			'<a class="close" href="javascript: hideRequestQueue()">X</a>'.
			'<div class="arrow"></div>'.
			'<a class="clearQueue" href="javascript: clearRequestQueue()">Empty cart</a>';
		if(!empty($arrQueue->businessTerms) || !empty($arrQueue->apiFields) || !empty($arrQueue->emptyApis)){
			$responseHTML .= '<a class="btn-orange" href="/request">View Request</a>';
		}
		echo $responseHTML;
	}

	public function success() {
	}

	public function addCollaborator($dsrId, $personId) {
		$this->autoRender = false;

		if (empty($dsrId) || empty($personId)) {
			echo '{"success":0,"message":"Bad request"}';
			return;
		}

		$person = $this->BYUAPI->personalSummary($personId);
		if (!isset($person)) {
			echo '{"success":0,"message":"Person\'s information could not be loaded"}';
			return;
		}

		$resp = $this->CollibraAPI->get('term/'.$dsrId);
		$request = json_decode($resp);
		foreach($request->attributeReferences->attributeReference as $attr) {
			if (($attr->labelReference->signifier == 'Requester Person Id' || $attr->labelReference->signifier == 'Requester Net Id') && $attr->value == $person->identifiers->net_id) {
				echo '{"success":0,"message":"This person is already a collaborator on this request."}';
				return;
			}
		}

		$postData['value'] = $person->identifiers->net_id;
		$postData['representation'] = $dsrId;
		$postData['label'] = Configure::read('Collibra.attribute.isaRequestNetId');
		$postString = http_build_query($postData);
		$postString = preg_replace('/%0D%0A/', '<br/>', $postString);
		$formResp = $this->CollibraAPI->post('term/'.$dsrId.'/attributes', $postString);
		$formResp = json_decode($formResp);
		if (!isset($formResp)) {
			echo '{"success":0,"message":"We had a problem getting to Collibra"}';
			return;
		}

		echo '{"success":1,"person":'.json_encode($person).'}';
	}

	public function removeCollaborator($dsrId, $netId) {
		$this->autoRender = false;

		if (empty($dsrId) || empty($netId)) {
			$this->Flash->error('We ran into an error performing that action.');
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}

		$resp = $this->CollibraAPI->get('term/'.$dsrId);
		$request = json_decode($resp);

		foreach ($request->attributeReferences->attributeReference as $attr) {
			if (($attr->labelReference->signifier == 'Requester Person Id' || $attr->labelReference->signifier == 'Requester Net Id') && $attr->value == $netId) {
				$this->CollibraAPI->delete('attribute/'.$attr->resourceId);
				$this->Flash->success("You are no longer a collaborator on \"{$request->signifier}\"");
				break;
			}
		}

		$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
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
			if ($attr->labelReference->signifier == 'Requester Person Id' || $attr->labelReference->signifier == 'Requester Net Id') {
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
			$this->Flash->error('You cannot edit a DSR with any child DSAs.');
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}

		// Check whether $request is a DSR or DSA
		$isaRequest = $request->conceptType->resourceId == Configure::read('Collibra.vocabulary.isaRequest');

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
			$this->CollibraAPI->delete('term/'.$du->id);
		}

		$this->CollibraAPI->delete('term/'.$dsrId);
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
		if (empty($arrQueue->businessTerms) && empty($arrQueue->apiFields) && empty($arrQueue->emptyApis)) {
			$this->redirect(['action' => 'index', '?' => ['err' => 1]]);
			exit;
		}

		$businessTermIds = [];
		foreach ($arrQueue->businessTerms as $id => $term) {
			array_push($businessTermIds, $id);
		}

		$additionalInformationAPIs = "";
		foreach ($arrQueue->emptyApis as $path => $api) {
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

		$postData = ['requesterPersonId' => $byuUser->identifiers->net_id];
		$postData['requesterNetId'] = $byuUser->identifiers->net_id;
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
		if (!empty($additionalElementsString)) {
			$postData[$additionalElementsString] = $this->request->data('apiTerms');
			if (!empty($postData[$additionalElementsString])) {
				$postData[$additionalElementsString] = array_diff($postData[$additionalElementsString], $postData[$requiredElementsString]);
			}
			if (empty($postData[$additionalElementsString])) {
				//Collibra requires "additionalElements" field to exist, even if empty,
				//but http_build_query throws out fields if null or empty array.
				//So we'll put a blank space in, which http_build_query
				//will not throw away
				$postData[$additionalElementsString] = '';
			}
		}

		//For array data, PHP's http_build_query creates query/POST string in a format Collibra doesn't like,
		//so we have to tweak the output a bit
		$postString = http_build_query($postData);
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

		$this->loadModel('CollibraAPI');

		$resp = $this->CollibraAPI->get('term/'.$dsrId);
		$dsr = json_decode($resp);

		$parent = $dsr->conceptType->resourceId == Configure::read('Collibra.vocabulary.isaRequest');

		$dsr->roles = $this->CollibraAPI->getResponsibilities($dsr->vocabularyReference->resourceId);
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
			$dsr->terms = $requestedTerms->aaData;
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

		$parent = $dsr->conceptType->resourceId == Configure::read('Collibra.vocabulary.isaRequest');

		$dsr->roles = $this->CollibraAPI->getResponsibilities($dsr->vocabularyReference->resourceId);
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
		if(empty($arrQueue->businessTerms) && empty($arrQueue->apiFields) && empty($arrQueue->emptyApis)) {
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
		foreach ($arrQueue->businessTerms as $termId => $term){
			$requestFilter .= '{"Field":{'.
				'   "name":"termrid",'.
				'   "operator":"EQUALS",'.
				'   "value":"'.$termId.'"'.
				'}},';
			if (!empty($term['apiPath']) && !empty($term['apiHost'])) {
				$apis[$term['apiHost']][$term['apiPath']] = [];
			}
		}
		foreach ($arrQueue->apiFields as $fieldName => $field) {
			if (!empty($field['apiPath']) && !empty($field['apiHost'])) {
				$apis[$field['apiHost']][$field['apiPath']] = [];
			}
		}

		$preFilled = [];
		$apiAllTerms = [];
		$additionalElementsString = Configure::read('Collibra.isaWorkflow.additionalElementsString');
		foreach ($apis as $apiHost => $apiPaths) {
			foreach ($apiPaths as $apiPath => $ignore) {
				$apiTerms = $this->CollibraAPI->getApiTerms($apiHost, $apiPath);
				foreach ($apiTerms as $term) {
					if (!empty($term->assetType) && strtolower($term->assetType) == 'fieldset') {
						continue;
					}
					if (empty($term->businessTerm[0]->termId)) {
						$apis[$apiHost][$apiPath]['unmapped'][] = $term->name;
					} else {
						$apiAllTerms[$term->businessTerm[0]->termId] = $term->businessTerm[0]->termId;
						if (!array_key_exists($term->businessTerm[0]->termId, $arrQueue->businessTerms)) {
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
					$term['unrequested'] = array_unique($term['unrequested']);
					$apiList .= "    {$apiHost}/{$apiPath}\n";
					if (!empty($term['unrequested'])) {
						$apiList .= "        Unrequested fields:\n            " . implode("\n            ", $term['unrequested']) . "\n";
					}
					if (!empty($term['unmapped'])) {
						$apiList .= "        Fields with no Business Terms:\n            " . implode("\n            ", $term['unmapped']) . "\n";
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
		foreach ($termResp->aaData as $term) {
			$domains[]  = $term->domainname;
			$termNames[] = $term->termsignifier;
			$term->apihost = $arrQueue->businessTerms[$term->termrid]['apiHost'];
			$term->apipath = $arrQueue->businessTerms[$term->termrid]['apiPath'];
		}
		array_multisort($domains, SORT_ASC, $termNames, SORT_ASC, $termResp->aaData);

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

		$this->set(compact('apiAllTerms', 'preFilled', 'arrQueue', 'psName', 'psPhone', 'psEmail', 'psRole', 'psDepartment', 'psReportsToName', 'supervisorInfo'));
		$this->set('submitErr', isset($this->request->query['err']));
	}
}
