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
		if($this->request->is('post') && $this->request->data['vocab'][0] != 'emptyApi'){
			$newTermsAdded = 0;
			$arrTerms = $this->request->data['t'];
			$arrTermIDs = $this->request->data['id'];
			$arrVocabIDs = $this->request->data['vocab'];
			$clearRelated = $this->request->data['clearRelated']=='true';
			$apiHost = empty($this->request->data['apiHost']) ? null : $this->request->data['apiHost'];
			$apiPath = empty($this->request->data['apiPath']) ? null : $this->request->data['apiPath'];

			$arrQueue = (array)$this->Cookie->read('queue');
			if(!empty($arrQueue)) {

				// Remove all terms in vocabularies passed and then re-add the ones selected by the user.
				if($clearRelated){
					$arrIdx = array();
					foreach ($arrVocabIDs as $communityId){
						foreach ($arrQueue as $termId => $term) {
							if ($term['communityId'] == $communityId) {
								unset($arrQueue[$termId]);
							}
						}
					}
				}
			}

			for($i=0; $i<sizeof($arrTerms); $i++){
				$term = $arrTerms[$i];
				$termID = $arrTermIDs[$i];
				$vocabID = $arrVocabIDs[$i];

				if(!empty($termID) && empty($arrQueue[$termID])){
					$requestable = true;
					$termResp = $this->CollibraAPI->get('term/'.$termID);
					$termResp = json_decode($termResp);

					// verify that the term is requestable
					if(!Configure::read('allowUnrequestableTerms')){
						foreach($termResp->attributeReferences->attributeReference as $attr){
							if($attr->labelReference->resourceId == Configure::read('Collibra.attribute.requestable')){
								$requestable = $attr->value == 'true';
							}
						}
					}

					// verify that the term is approved
					if(!Configure::read('allowUnapprovedTerms')){
						$requestable = $termResp->statusReference->signifier == 'Accepted';
					}

					if($requestable){
						$newTermsAdded++;
						$arrQueue[$termID] = array('term' => $term, 'communityId' => $vocabID, 'apiHost' => $apiHost, 'apiPath' => $apiPath);
					}
				}
			}

			$this->Cookie->write('queue', $arrQueue, true, '90 days');
			echo $newTermsAdded;
		} else {
			//Add an API with unspecified fields to cart.
			$newTermsAdded = 1;
			$apiPath = $this->request->data['t'][0];
			$apiHost = $this->request->data['apiHost'];
			$newApiId = 0;

			$arrQueue = (array)$this->Cookie->read('queue');

			foreach ($arrQueue as $id => $term) {
				if ($term['communityId'] == 'emptyApi') {
					if ($newApiId <= $id) {
						//Ensure the new api's id is one greater than the current max to prevent duplicates
						$newApiId = $id + 1;
					}
				 	if ($term['term'] == $apiPath) {
						$newTermsAdded = 0;
					}
				}
			}

			if ($newTermsAdded) {
				$arrQueue[$newApiId] = ['term' => $apiPath, 'communityId' => 'emptyApi', 'apiHost' => $apiHost];
			}

			$this->Cookie->write('queue', $arrQueue, true, '90 days');
			echo $newTermsAdded;
		}
	}

	public function removeFromQueue() {
		$this->autoRender = false;
		if($this->request->is('post')){
			$termID = $this->request->data['id'];
			$arrQueue = $this->Cookie->read('queue');
			if(array_key_exists($termID, $arrQueue)) {
				unset($arrQueue[$termID]);
				$this->Cookie->write('queue', $arrQueue, true, '90 days');
			}
		}
	}

	public function clearQueue() {
		$this->autoRender = false;
		$this->Cookie->delete('queue');
	}

	public function getQueueJSArray() {
		$this->autoRender = false;
		$JS = '';

		$arrQueue = $this->Cookie->read('queue');
		if(!empty($arrQueue)) {
			$JS = implode(',', array_keys($arrQueue));
		}
		echo $JS;
	}

	public function listQueue() {
		$this->autoRender = false;
		$listHTML = '';
		$responseHTML = '';

		$arrQueue = $this->Cookie->read('queue');
		if(!empty($arrQueue)) {
			foreach ($arrQueue as $termId => $term){
				if (strlen($term['term']) > 28) {
					$displayName = substr($term['term'], 0, 28) . "...";
				} else {
					$displayName = $term['term'];
				}
				if ($term['communityId'] != 'emptyApi') {
					$listHTML .= '<li id="requestItem'.$termId.'" data-title="'.$term['term'].'" data-rid="'.$termId.'" data-vocabID="'.$term['communityId'].'" api-host="'.$term['apiHost'].'" api-path="'.$term['apiPath'].'" api="false">'.$displayName.'<a class="delete" href="javascript:removeFromRequestQueue(\''.$termId.'\')"><img src="/img/icon-delete.gif" width="11" title="delete" /></a></li>';
				} else {
					$listHTML .= '<li id="requestItem'.$termId.'" data-title="'.$term['term'].'" api-host="'.$term['apiHost'].'" api="true">'.$displayName.'<a class="delete" href="javascript:removeFromRequestQueue(\''.$termId.'\')"><img src="/img/icon-delete.gif" width="11" title="delete" /></a></li>';
				}
			}
			$listHTML .= '</ul><a class="clearQueue" href="javascript: clearRequestQueue()">Empty cart</a>';
		}else{
			$listHTML = 'No request items found.</ul>';
		}
		$responseHTML=  '<h3>Requested Items</h3>'.
			'<a class="close" href="javascript: hideRequestQueue()">X</a>'.
			'<div class="arrow"></div>'.
			'<ul>'.
			$listHTML;
		if(!empty($arrQueue)){
			$responseHTML .= '<a class="btn-orange" href="/request">Submit Request</a>';
		}
		echo $responseHTML;
	}

	public function success() {
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
		//It is possible to go through this process w/o actually changing any info on the DSR.
		//$changed ensures that we only comment on child DSAs if changes did take place.
		$changed = false;
		$changeComment = 'The following changes were made to this Agreement on '.date("Y-m-d H:i:s T").':';

		foreach ($this->request->data as $key => $val) {
			$key = preg_replace('/_/', ' ', $key);
			foreach ($dsr->attributeReferences->attributeReference as $original) {
				if ($key == $original->labelReference->signifier && $val != $original->value) {
					$changed = true;

					//Update values in Collibra database
					$postData['value'] = $val;
					$postData['rid'] = $original->resourceId;
					$postString = http_build_query($postData);
					$postString = preg_replace('/%0D50A/', '<br />', $postString);
					$formResp = $this->CollibraAPI->post('attribute/'.$original->resourceId, $postString);
					$formResp = json_decode($formResp);

					if (!isset($formResp)) {
						$err = true;
						break;
					}

					//Build string for comments on child DSAs
					$changeComment .= '<br />'.$key.': '.$val;
					break;
				}
			}
		}

		//Update child DSAs
		if ($changed) {
			$dsr->dataUsages = $this->CollibraAPI->getDataUsages($dsrId);
			for ($i = 0; $i < sizeof($dsr->dataUsages); $i++) {
				$resp = $this->CollibraAPI->get('term/'.$dsr->dataUsages[$i]->id);
				$dsr->dataUsages[$i] = json_decode($resp);
			}
			foreach ($dsr->dataUsages as $du) {
				if (!in_array($du->statusReference->signifier, ['Approved', 'Obsolete'])) {
					foreach ($this->request->data as $key => $val) {
						$key = preg_replace('/_/', ' ', $key);
						foreach ($du->attributeReferences->attributeReference as $original) {
							if ($key == $original->labelReference->signifier && $val != $original->value) {
								//Update values in Collibra database
								$postData['value'] = $val;
								$postData['rid'] = $original->resourceId;
								$postString = http_build_query($postData);
								$postString = preg_replace('/%0D50A/', '<br />', $postString);
								$formResp = $this->CollibraAPI->post('attribute/'.$original->resourceId, $postString);
								$formResp = json_decode($formResp);

								if (!isset($formResp)) {
									$err = true;
									break;
								}
							}
						}
					}
					$formResp = $this->CollibraAPI->post('term/'.$du->resourceId.'/comment', 'content='.$changeComment);
				}
			}
		}



		if (!$err) {
			// attempt to reindex source to make sure latest requests are displayed
			$resp = $this->CollibraAPI->post('search/re-index');

			$this->redirect(['action' => 'success']);
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

		$completedStatuses = ['Completed', 'Obsolete'];
		if (in_array($request->statusReference->signifier, $completedStatuses)) {
			$this->Flash->error('You cannot edit a completed Request.');
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}

		// load form fields for ISA workflow
		$formResp = $this->CollibraAPI->get('workflow/'.Configure::read('Collibra.isaWorkflow.id').'/form/start');
		$formResp = json_decode($formResp);

		$this->set('formFields', $formResp);
		$this->set('request', $request);
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

		$postData = ['requesterPersonId' => $byuUser->identifiers->person_id];
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
		$postData[$requiredElementsString] = $this->request->data('terms');
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
		$postString = preg_replace('/%0D%0A/','<br />',$postString);

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
			$this->Cookie->delete('queue');

			$this->redirect(['action' => 'success']);
		}else{
			$this->redirect(['action' => 'index', '?' => ['err' => 1]]);
		}
	}

	public function index() {
		$netID = $this->Auth->user('username');
		$byuUser = $this->BYUAPI->personalSummary($netID);
		$supervisorInfo = $this->BYUAPI->supervisorLookup($netID);

		// make sure terms have been added to the users's queue
		$arrQueue = $this->Cookie->read('queue');
		if(empty($arrQueue)) {
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
		$emptyApis = [];
		foreach ($arrQueue as $termId => $term){
			if ($term['communityId'] == 'emptyApi') {
				array_push($emptyApis, ['apiHost' => $term['apiHost'], 'apiPath' => $term['term']]);
				continue;
			}
			$requestFilter .= '{"Field":{'.
				'   "name":"termrid",'.
				'   "operator":"EQUALS",'.
				'   "value":"'.$termId.'"'.
				'}},';
			if (!empty($term['apiPath']) && !empty($term['apiHost'])) {
				$apis[$term['apiHost']][$term['apiPath']] = [];
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
						if (!array_key_exists($term->businessTerm[0]->termId, $arrQueue)) {
							$apis[$apiHost][$apiPath]['unrequested'][] = $term->businessTerm[0]->term;
						}
					}
				}
			}
		}
		if (!empty($apis) || !empty($emptyApis)) {
			$apiList = "Requested APIs:\n";
			foreach ($apis as $apiHost => $apiPaths) {
				foreach ($apiPaths as $apiPath => $term) {
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
			foreach ($emptyApis as $api) {
				$apiList .= "    {$api['apiHost']}/{$api['apiPath']}\n        [No specified output fields]\n\n";
			}
			$preFilled['descriptionOfInformation'] = $apiList;
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

		$this->set(compact('apiAllTerms', 'preFilled', 'psName', 'psPhone', 'psEmail', 'psRole', 'psDepartment', 'psReportsToName', 'supervisorInfo'));
		$this->set('submitErr', isset($this->request->query['err']));
	}
}
