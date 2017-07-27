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
		$this->redirect('/');
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

	public function getCoordinatorPhoneNumber($userid) {
		$this->autoRender = false;

		if (empty($userid)) {
			return;
		}

		$this->loadModel('CollibraAPI');
		$profile = $this->CollibraAPI->get("user/{$userid}", ['json' => true]);

		if (!isset($profile)) {
			return;
		}

		if (!empty($profile->phoneNumbers) && count(get_object_vars($profile->phoneNumbers)) > 0) {
			foreach ($profile->phoneNumbers->phone as $ph) {
				if ($ph->phoneType == 'WORK') {
					return $ph->number;
				}
			}
		}
	}

	public function index() {
		$completedStatuses = ['Completed', 'Obsolete'];
		$page = 'current';
		if(isset($this->request->query['s'])){
			$page = 'past';
		}
		$expand = '';
		if(isset($this->request->query['expand'])){
			$expand = $this->request->query['expand'];
		}

		$netID = $this->Auth->user('username');
		$this->loadModel('BYUAPI');
		$byuUser = $this->BYUAPI->personalSummary($netID);
		$personID = $byuUser->identifiers->person_id;

		$this->loadModel('CollibraAPI');
		// attempt to reindex source to make sure latest requests are displayed
		/*$resp = $this->CollibraAPI->post('search/re-index');*/

		// get all request for this user
		$resp = $this->CollibraAPI->postJSON(
				'search',
				'{"query":"'.$personID.'", "filter": {"category": ["TE"], "type": {"asset": ["' . Configure::read('Collibra.vocabulary.isaRequest') . '"] }}, "fields": ["' . Configure::read('Collibra.attribute.isaRequestNetId') . '"] }'
		);
		$isaRequests = json_decode($resp);
		// In the past, DSRs were tied to users via the user's person ID, but we want to move
		// to netIDs, so first we find all DSRs with the user's person ID and then change each
		// to a netID. Then we query with the netID.
		foreach ($isaRequests->results as $req) {
			foreach($req->attributes as $attr) {
				if ($attr->type == 'Requester Net Id') {
					$person = $this->BYUAPI->personalSummary($attr->val);
					$postData['value'] = $person->identifiers->net_id;
					$postData['rid'] = $attr->id;
					$postString = http_build_query($postData);
					$formResp = $this->CollibraAPI->post('attribute/'.$attr->id, $postString);
				}
			}
		}
		$resp = $this->CollibraAPI->postJSON(
				'search',
				'{"query":"'.$netID.'", "filter": {"category": ["TE"], "type": {"asset": ["' . Configure::read('Collibra.vocabulary.isaRequest') . '"] }}, "fields": ["' . Configure::read('Collibra.attribute.isaRequestNetId') . '"] }'
		);
		$isaRequests = json_decode($resp);

		$arrRequests = array();
		foreach($isaRequests->results as $r){
			if ($page == 'past' && !in_array($r->status, $completedStatuses)) {
				continue;
			} elseif ($page == 'current' && in_array($r->status, $completedStatuses)) {
				continue;
			}
			if ($r->status == 'Archived') {
				continue;
			}
			// load terms details
			$resp = $this->CollibraAPI->get('term/'.$r->name->id);
			$request = json_decode($resp);
			$request->roles = $this->CollibraAPI->getResponsibilities($request->vocabularyReference->resourceId);
			$request->dataUsages = $this->CollibraAPI->getDataUsages($r->name->id);
			//$createdDate = $request->createdOn/1000;
			//$createdDate = date('m/d/Y', $request->createdOn);

			// load terms submitted in request
			////////////////////////////////////////////
			$resp = $this->CollibraAPI->postJSON(
					'output/data_table',
					'{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"termrid"}},{"Column":{"fieldName":"termsignifier"}},{"Column":{"fieldName":"relationrid"}},{"Column":{"fieldName":"startDate"}},{"Column":{"fieldName":"endDate"}},{"Column":{"fieldName":"relstatusrid"}},{"Column":{"fieldName":"relstatusname"}},{"Column":{"fieldName":"communityname"}},{"Column":{"fieldName":"commrid"}},{"Column":{"fieldName":"domainname"}},{"Column":{"fieldName":"domainrid"}},{"Column":{"fieldName":"concepttypename"}},{"Column":{"fieldName":"concepttyperid"}}],"Resources":{"Term":{"Id":{"name":"termrid"},"Signifier":{"name":"termsignifier"},"Relation":{"typeId":"' . Configure::read('Collibra.relationship.isaRequestToTerm') . '","Id":{"name":"relationrid"},"StartingDate":{"name":"startDate"},"EndingDate":{"name":"endDate"},"Status":{"Id":{"name":"relstatusrid"},"Signifier":{"name":"relstatusname"}},"Filter":{"AND":[{"Field":{"name":"reltermrid", "operator":"EQUALS", "value":"'.$r->name->id.'"}}]},"type":"TARGET","Source":{"Id":{"name":"reltermrid"}}},"Vocabulary":{"Community":{"Name":{"name":"communityname"},"Id":{"name":"commrid"}},"Name":{"name":"domainname"},"Id":{"name":"domainrid"}},"ConceptType":[{"Signifier":{"name":"concepttypename"}, "Id":{"name":"concepttyperid"}}],"Filter":{"AND":[{"AND":[{"Field":{"name":"reltermrid", "operator":"EQUALS", "value":"'.$r->name->id.'"}}]}]},"Order":[{"Field":{"name":"termsignifier", "order":"ASC"}}]}},"displayStart":0,"displayLength":100}}'
			);
			$requestedTerms = json_decode($resp);
			// add property to request object to hold terms
			if(isset($requestedTerms->aaData)){
				$request->termGlossaries = array();
				foreach ($requestedTerms->aaData as $term) {
					if (array_key_exists($term->domainname, $request->termGlossaries)) {
						array_push($request->termGlossaries[$term->domainname], $term);
					} else {
						$request->termGlossaries[$term->domainname] = array($term);
					}
				}
			}
			////////////////////////////////////////////

			// load approval objects for request
			////////////////////////////////////////////
			$termRid = $r->name->id;
			$resp = $this->CollibraAPI->postJSON(
					'output/data_table',
					'{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"termrid"}},{"Column":{"fieldName":"termsignifier"}},{"Column":{"fieldName":"relationrid"}},{"Column":{"fieldName":"startDate"}},{"Column":{"fieldName":"endDate"}},{"Column":{"fieldName":"relstatusrid"}},{"Column":{"fieldName":"relstatusname"}},{"Column":{"fieldName":"Attr4331ec0988a248e6b0969ece6648aff3rid"}},{"Column":{"fieldName":"Attr4331ec0988a248e6b0969ece6648aff3longExpr"}},{"Column":{"fieldName":"Attr4331ec0988a248e6b0969ece6648aff3"}},{"Column":{"fieldName":"Attr0cbbfd32fc9747cebef1a89ae4e77ee8rid"}},{"Column":{"fieldName":"Attr0cbbfd32fc9747cebef1a89ae4e77ee8longExpr"}},{"Column":{"fieldName":"Attr0cbbfd32fc9747cebef1a89ae4e77ee8"}},{"Column":{"fieldName":"Attr9a18e247c09040c3896eab97335ae759rid"}},{"Column":{"fieldName":"Attr9a18e247c09040c3896eab97335ae759longExpr"}},{"Column":{"fieldName":"Attr9a18e247c09040c3896eab97335ae759"}},{"Column":{"fieldName":"statusrid"}},{"Column":{"fieldName":"statusname"}}],"Resources":{"Term":{"Id":{"name":"termrid"},"Signifier":{"name":"termsignifier"},"Relation":{"typeId":"' . Configure::read('Collibra.relationship.isaRequestToApproval') . '","Id":{"name":"relationrid"},"StartingDate":{"name":"startDate"},"EndingDate":{"name":"endDate"},"Status":{"Id":{"name":"relstatusrid"},"Signifier":{"name":"relstatusname"}},"Filter":{"AND":[{"Field":{"name":"reltermrid","operator":"EQUALS","value":"'.$termRid.'"}}]},"type":"TARGET","Source":{"Id":{"name":"reltermrid"}}},"StringAttribute":[{"Id":{"name":"Attr4331ec0988a248e6b0969ece6648aff3rid"},"labelId":"' . Configure::read('Collibra.attribute.stewardName') . '","LongExpression":{"name":"Attr4331ec0988a248e6b0969ece6648aff3longExpr"},"Value":{"name":"Attr4331ec0988a248e6b0969ece6648aff3"}},{"Id":{"name":"Attr0cbbfd32fc9747cebef1a89ae4e77ee8rid"},"labelId":"' . Configure::read('Collibra.attribute.stewardEmail') . '","LongExpression":{"name":"Attr0cbbfd32fc9747cebef1a89ae4e77ee8longExpr"},"Value":{"name":"Attr0cbbfd32fc9747cebef1a89ae4e77ee8"}},{"Id":{"name":"Attr9a18e247c09040c3896eab97335ae759rid"},"labelId":"' . Configure::read('Collibra.attribute.stewardPhone') . '","LongExpression":{"name":"Attr9a18e247c09040c3896eab97335ae759longExpr"},"Value":{"name":"Attr9a18e247c09040c3896eab97335ae759"}}],"Status":{"Id":{"name":"statusrid"},"Signifier":{"name":"statusname"}},"Filter":{"AND":[{"AND":[{"Field":{"name":"reltermrid","operator":"EQUALS","value":"'.$termRid.'"}}]}]},"Order":[{"Field":{"name":"termsignifier","order":"ASC"}}]}},"displayStart":0,"displayLength":100}}'
			);
			$approvalObjects = json_decode($resp);// add property to request object to hold approvals
			if(isset($approvalObjects->aaData)){
				$request->approvals = $approvalObjects;
			}
			////////////////////////////////////////////

			// add to request data array
			array_push($arrRequests, $request);
		}
		// sort results by date added
		usort($arrRequests, 'self::sortRequests');

		// sort request attribute data based on workflow form field order
		$workflowResp = $this->CollibraAPI->get('workflow/'.Configure::read('Collibra.isaWorkflow.id').'/form/start');
		$workflowResp = json_decode($workflowResp);
		foreach($arrRequests as $r){
			$arrNewAttr = array();
			$arrCollaborators = array();
			foreach($r->attributeReferences->attributeReference as $attr){
				if ($attr->labelReference->signifier == 'Requester Net Id') {
					$person = $this->BYUAPI->personalSummary($attr->value);
					unset($person->person_summary_line, $person->identifiers, $person->personal_information, $person->student_information, $person->relationships);
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
		}

		$psName = '';
		$psRole = 'N/A';
		$psDepartment = 'N/A';
		$psEmail = '';
		$psNetID = $byuUser->identifiers->net_id;
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
		$this->set(compact('expand', 'psName', 'psRole', 'psDepartment', 'psEmail', 'psNetID', 'page'));
		$this->set('requests', $arrRequests);
	}
}
