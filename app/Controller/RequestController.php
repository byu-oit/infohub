<?php

class RequestController extends AppController {
	public $helpers = array('Html', 'Form');

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
			$newTermsAdded = 0;
			$arrTerms = $this->request->data['t'];
			$arrTermIDs = $this->request->data['id'];
			$arrVocabIDs = $this->request->data['vocab'];
			$clearRelated = $this->request->data['clearRelated']=='true';

			$arrQueue = (array)$this->Cookie->read('queue');
			if(!empty($arrQueue)) {

				// Remove all terms in vocabularies passed and then re-add the ones selected by the user.
				if($clearRelated){
					$arrIdx = array();
					for($i=0; $i<sizeof($arrVocabIDs); $i++){
						foreach($arrQueue as $queueKey => $term) {
							if($term[2] == $arrVocabIDs[$i]){
								unset($arrQueue[$queueKey]);
							}
						}
					}
				}
			}

			for($i=0; $i<sizeof($arrTerms); $i++){
				$term = $arrTerms[$i];
				$termID = $arrTermIDs[$i];
				$vocabID = $arrVocabIDs[$i];

				if($termID != '' && empty($arrQueue[$termID])){
					$requestable = true;
					$this->loadModel('CollibraAPI');
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
						$arrQueue[$termID] = array($term, $termID, $vocabID);
					}
				}
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

	public function getQueueJSArray() {
		$this->autoRender = false;
		$JS = '';

		$arrQueue = $this->Cookie->read('queue');
		if(!empty($arrQueue)) {
			foreach ($arrQueue as $term){
				$JS .= ','.$term[1];
			}
		}
		echo $JS;
	}

	public function listQueue() {
		$this->autoRender = false;
		$listHTML = '';
		$responseHTML = '';

		$arrQueue = $this->Cookie->read('queue');
		if(!empty($arrQueue)) {
			foreach ($arrQueue as $term){
				$listHTML .= '<li id="requestItem'.$term[1].'" data-title="'.$term[0].'" data-rid="'.$term[1].'" data-vocabID="'.$term[2].'">'.$term[0].'<a class="delete" href="javascript:removeFromRequestQueue(\''.$term[1].'\')"><img src="/img/icon-delete.gif" width="11" title="delete" /></a></li>';
			}
		}else{
			$listHTML = 'No request items found.';
		}
		$responseHTML=  '<h3>Requested Items</h3>'.
			'<a class="close" href="javascript: hideRequestQueue()">X</a>'.
			'<div class="arrow"></div>'.
			'<ul>'.
			$listHTML.//'    <li>Information Domain </li>'.//<a class="delete" href=""><img src="/img/icon-delete.gif" width="11" /></a>
			'</ul>';
		if(!empty($arrQueue)){
			$responseHTML .= '<a class="btn-orange" href="/request">Submit Request</a>';
		}
		echo $responseHTML;
	}

	public function success() {
	}

	public function submit() {
		$this->autoRender = false;

		if(!$this->request->is('post')){
			header('location: /search');
			exit;
		}

		$this->loadModel('CollibraAPI');

		$name = explode(' ',$this->request->data['name']);
		$firstName = $name[0];
		$lastName = '';
		if(sizeof($name)>1) $lastName = $name[1];
		$email = $this->request->data['email'];
		$phone = $this->request->data['phone'];
		$role = $this->request->data['role'];
		$dataRequested = '';

		// create guest user to use for submitting request
		/*
		$guestUserResp = $this->CollibraAPI->post(
				'user/guest',
				['firstName' => $firstName, 'lastName' => $lastName, 'email' => $this->request->data['email']]
		);
		$guestUserResp = json_decode($guestUserResp);
		$guestID = $guestUserResp->resourceId;
		*/

		$postData = [];//'user' => $guestID;
		foreach($this->request->data as $key => $val){
			if (!in_array($key, ['name', 'phone', 'email', 'role', 'terms', 'requestSubmit', 'collibraUser'])) {
				$postData[$key] = $val;
			}
		}
		// add user's contact info to post
		$postData['requesterName'] = $firstName.' '.$lastName;
		$postData['requesterEmail'] = $email;
		$postData['requesterPhone'] = $phone;
		$postData['requesterRole'] = $role;

		// add requested terms to post
		//For array data, PHP's http_build_query creates query/POST string in a format Collibra doesn't like,
		//so we're manually building the remaining POST string
		$postString = http_build_query($postData);
		foreach($this->request->data['terms'] as $term){
			$postString .= '&informationElements=' . urlencode($term);
			$dataRequested .= $term.',';
		}

		$formResp = $this->CollibraAPI->post(
			'workflow/'.Configure::read('Collibra.isaWorkflow').'/start',
			$postString
		);
		$formResp = json_decode($formResp);
		//print_r($postData);
		//exit;

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
		$this->loadModel('BYUAPI');
		$byuUser = $this->BYUAPI->personalSummary($netID);
		$supervisorInfo = $this->BYUAPI->supervisorLookup($netID);

		// make sure terms have been added to the users's queue
		$arrQueue = $this->Cookie->read('queue');
		if(empty($arrQueue)) {
			header('location: /search');
			exit;
		}

		//$termID = $this->request->params['pass'][0];
		$this->loadModel('CollibraAPI');

		$requestFilter = '{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"createdOn"}},{"Column":{"fieldName":"termrid"}},{"Column":{"fieldName":"termsignifier"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202longExpr"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202rid"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldName":"userRole00000000000000000000000000005016rid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole00000000000000000000000000005016gender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole00000000000000000000000000005016fn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole00000000000000000000000000005016ln"}}],"name":"Role00000000000000000000000000005016"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole00000000000000000000000000005016grid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole00000000000000000000000000005016ggn"}}],"name":"Role00000000000000000000000000005016g"}},{"Column":{"fieldName":"statusname"}},{"Column":{"fieldName":"statusrid"}},{"Column":{"fieldName":"communityname"}},{"Column":{"fieldName":"commrid"}},{"Column":{"fieldName":"domainname"}},{"Column":{"fieldName":"domainrid"}},{"Column":{"fieldName":"concepttypename"}},{"Column":{"fieldName":"concepttyperid"}}],'.
			'"Resources":{"Term":{"CreatedOn":{"name":"createdOn"},"Id":{"name":"termrid"},"Signifier":{"name":"termsignifier"},"StringAttribute":[{"Value":{"name":"Attr00000000000000000000000000000202"},"LongExpression":{"name":"Attr00000000000000000000000000000202longExpr"},"Id":{"name":"Attr00000000000000000000000000000202rid"},"labelId":"' . Configure::read('Collibra.attribute.definition') . '"}],"Member":[{"User":{"Gender":{"name":"userRole00000000000000000000000000005016gender"},"FirstName":{"name":"userRole00000000000000000000000000005016fn"},"Id":{"name":"userRole00000000000000000000000000005016rid"},"LastName":{"name":"userRole00000000000000000000000000005016ln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016sig"},"name":"Role00000000000000000000000000005016","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016rid"}},"roleId":"00000000-0000-0000-0000-000000005016"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016g"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016grid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005016ggn"},"Id":{"name":"groupRole00000000000000000000000000005016grid"}},"roleId":"00000000-0000-0000-0000-000000005016"}],"Status":{"Signifier":{"name":"statusname"},"Id":{"name":"statusrid"}},"Vocabulary":{"Community":{"Name":{"name":"communityname"},"Id":{"name":"commrid"}},"Name":{"name":"domainname"},"Id":{"name":"domainrid"}},"ConceptType":[{"Signifier":{"name":"concepttypename"},"Id":{"name":"concepttyperid"}}],'.
			'"Filter":{'.
			'   "AND":['.
			'        {'.
			'           "OR":[';

		foreach ($arrQueue as $term){
			$requestFilter .= '{"Field":{'.
				'   "name":"termrid",'.
				'   "operator":"EQUALS",'.
				'   "value":"'.$term[1].'"'.
				'}},';
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
			'},"displayStart":0,"displayLength":10}}';

		$termResp = $this->CollibraAPI->postJSON('output/data_table', $requestFilter);
		$termResp = json_decode($termResp);
		//usort($termResp->aaData, 'self::sortTermsByDomain');
		foreach ($termResp->aaData as $term) {
			$domains[]  = $term->domainname;
			$termNames[] = $term->termsignifier;
		}
		array_multisort($domains, SORT_ASC, $termNames, SORT_ASC, $termResp->aaData);

		// load form fields for ISA workflow
		$formResp = $this->CollibraAPI->get('workflow/'.Configure::read('Collibra.isaWorkflow').'/form/start');
		$formResp = json_decode($formResp);

		$this->set('formFields', $formResp);
		$this->set('termDetails', $termResp);

		$psName = '';
		$psPhone = '';
		$psEmail = '';
		$psRole = '';
		$psDepartment = '';
		$psReportsToName = '';
		$psPersonID = $byuUser->identifiers->person_id;
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

		$this->set('psName', $psName);
		$this->set('psPhone', $psPhone);
		$this->set('psEmail', $psEmail);
		$this->set('psRole', $psRole);
		$this->set('psDepartment', $psDepartment);
		$this->set('psPersonID', $psPersonID);
		$this->set('psReportsToName', $psReportsToName);
		$this->set('supervisorInfo', $supervisorInfo);
		$this->set('submitErr', isset($this->request->query['err']));
	}
}