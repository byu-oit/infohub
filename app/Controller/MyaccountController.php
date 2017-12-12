<?php

class MyaccountController extends AppController {
	function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->deny();
		$this->Auth->allow('logout');
	}

	private static function sortUsers($a, $b){
		return strcmp($a->firstName, $b->firstName);
	}

	private static function sortRequests($a, $b){
		return strcmp($a->createdOn, $b->createdOn);
	}

	private static function sortAttributes($a, $b){
		return strcmp($a->labelReference->signifier, $b->labelReference->signifier);
	}

	public function login() {
		$this->redirect($this->request->query['return']);
	}

	public function logout() {
		$this->Auth->logout();
		$this->redirect('/');
	}

	function sortArrayByArray(Array $array, Array $orderArray) {
		$ordered = array();
		foreach($orderArray as $key) {
			if(array_key_exists($key,$array)) {
				$ordered[$key] = $array[$key];
				unset($array[$key]);
			}
		}
		return $ordered + $array;
	}

	public function index() {
		$completedStatuses = ['Completed', 'Obsolete'];
		$expand = '';
		if(isset($this->request->query['expand'])){
			$expand = $this->request->query['expand'];
		}

		$netID = $this->Auth->user('username');
		$this->loadModel('BYUAPI');
		$byuUser = $this->BYUAPI->personalSummary($netID);

		$this->loadModel('CollibraAPI');
		$resp = $this->CollibraAPI->postJSON(
				'search',
				'{"query":"'.$netID.'", "filter": {"category": ["TE"], "type": {"asset": ["' . Configure::read('Collibra.type.isaRequest') . '"] }}, "fields": ["' . Configure::read('Collibra.attribute.isaRequestNetId') . '"] }'
		);
		$isaRequests = json_decode($resp);

		$arrRequests = [];
		foreach($isaRequests->results as $r){
			if ($r->status == 'Deleted') {
				continue;
			}
			// load terms details
			$resp = $this->CollibraAPI->get('term/'.$r->name->id);
			$request = json_decode($resp);
			$request->roles = $this->CollibraAPI->getResponsibilities($request->vocabularyReference->resourceId);
			$request->dataUsages = $this->CollibraAPI->getDataUsages($r->name->id);
			$request->policies = $this->CollibraAPI->getAssetPolicies($r->name->id);
			//$createdDate = $request->createdOn/1000;
			//$createdDate = date('m/d/Y', $request->createdOn);

			$requestedTerms = $this->CollibraAPI->getRequestedTerms($r->name->id);
			$request->termGlossaries = array();
			foreach ($requestedTerms as $term) {
				if (array_key_exists($term->domainname, $request->termGlossaries)) {
					array_push($request->termGlossaries[$term->domainname], $term);
				} else {
					$request->termGlossaries[$term->domainname] = array($term);
				}
			}

			//load additionally included terms
			////////////////////////////////////////////
			$resp = $this->CollibraAPI->getAdditionallyIncludedTerms($r->name->id);
			if (!empty($resp)) {
				$request->additionallyIncluded = new stdClass();
				$request->additionallyIncluded->termGlossaries = [];
				foreach ($resp as $term) {
					if (array_key_exists($term->domainname, $request->additionallyIncluded->termGlossaries)) {
						array_push($request->additionallyIncluded->termGlossaries[$term->domainname], $term);
					} else {
						$request->additionallyIncluded->termGlossaries[$term->domainname] = array($term);
					}
				}
			}
			// add to request data array
			array_push($arrRequests, $request);
		}
		// sort results by date added
		usort($arrRequests, 'self::sortRequests');

		// Temporary fix for a mysterious bug in Collibra that sometimes
		// returns two copies of the most recently created DSR
		$numRequests = count($arrRequests);
		if ($numRequests > 1 && $arrRequests[$numRequests - 1]->resourceId == $arrRequests[$numRequests - 2]->resourceId) {
			array_pop($arrRequests);
		}

		$sortedRequests = [
			'inProgress' => [],
			'completed' => [],
			'canceled' => []
		];

		foreach($arrRequests as $r){
			$arrNewAttr = array();
			$arrCollaborators = array();
			foreach($r->attributeReferences->attributeReference as $attr){
				if ($attr->labelReference->signifier == 'Requester Net Id') {
					if ($attr->value == $netID) {
						$person = $byuUser;
					} else {
						$person = $this->BYUAPI->personalSummary($attr->value);
					}
					unset($person->person_summary_line, $person->personal_information, $person->student_information, $person->relationships);
					array_push($arrCollaborators, $person);
					continue;
				}
				$arrNewAttr[$attr->labelReference->signifier] = $attr;
			}
			$arrNewAttr['Collaborators'] = $arrCollaborators;
			$r->attributeReferences->attributeReference = $arrNewAttr;

			// Making edits in Collibra inserts weird html into the attributes; if an
			// edit was made in Collibra, we replace their html with some more cooperative tags
			foreach($r->attributeReferences->attributeReference as $label => $attr) {
				if ($label == 'Collaborators' || $label == 'Request Date') continue;
				if (preg_match('/<div>/', $attr->value)) {
					$postData['value'] = preg_replace(['/<div><br\/>/', '/<\/div>/', '/<div>/'], ['<br/>', '', '<br/>'], $attr->value);
					$postData['rid'] = $attr->resourceId;
					$postString = http_build_query($postData);
					$this->CollibraAPI->post('attribute/'.$attr->resourceId, $postString);

					// After updating the value in Collibra, just replace the value for this page load
					$attr->value = preg_replace(['/<div><br\/>/', '/<\/div>/', '/<div>/'], ['<br/>', '', '<br/>'], $attr->value);
				}
			}

			for ($i = 0; $i < sizeof($r->dataUsages); $i++) {
				$resp = $this->CollibraAPI->get('term/'.$r->dataUsages[$i]->id);
				$resp = json_decode($resp);
				$r->dataUsages[$i]->attributeReferences = $resp->attributeReferences;
				foreach($r->dataUsages[$i]->attributeReferences->attributeReference as $attr) {
					if (isset($attr->date)) {
						continue;
					}
					if (preg_match('/<div>/', $attr->value)) {
						$postData['value'] = preg_replace(['/<div><br\/>/', '/<\/div>/', '/<div>/'], ['<br/>', '', '<br/>'], $attr->value);
						$postData['rid'] = $attr->resourceId;
						$postString = http_build_query($postData);
						$this->CollibraAPI->post('attribute/'.$attr->resourceId, $postString);

						// After updating the value in Collibra, just replace the value for this page load
						$attr->value = preg_replace(['/<div><br\/>/', '/<\/div>/', '/<div>/'], ['<br/>', '', '<br/>'], $attr->value);
					}
				}
			}

			$pendingStatuses = ['In Progress', 'Request In Progress', 'Agreement Review'];
			if (in_array($r->statusReference->signifier, $pendingStatuses)) {
				array_push($sortedRequests['inProgress'], $r);
			} else if ($r->statusReference->signifier == 'Completed' || $r->statusReference->signifier == 'Obsolete') {
				array_push($sortedRequests['completed'], $r);
			} else if ($r->statusReference->signifier == 'Canceled') {
				array_push($sortedRequests['canceled'], $r);
			}
		}

		$psName = '';
		$psRole = 'N/A';
		$psDepartment = 'N/A';
		$psEmail = '';
		$psNetID = $netID;
		if(isset($byuUser->names->preferred_name)){
			$psName = $byuUser->names->preferred_name;
		}
		if(isset($byuUser->employee_information->job_title)){
			$psRole = $byuUser->employee_information->job_title;
		}
		if(isset($byuUser->employee_information->department)){
			$psDepartment = $byuUser->employee_information->department;
		}
		if(isset($byuUser->contact_information->email_address)){
			$psEmail = $byuUser->contact_information->email_address;
		}
		$this->set(compact('expand', 'psName', 'psRole', 'psDepartment', 'psEmail', 'psNetID'));
		$this->set('requestStatuses', $sortedRequests);
	}
}
