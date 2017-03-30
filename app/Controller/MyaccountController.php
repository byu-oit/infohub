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

	public function index() {
		$completedStatuses = ['Completed', 'Obsolete'];
		$page = 'current';
		if(isset($this->request->query['s'])){
			$page = 'past';
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
				'{"query":"'.$personID.'", "filter": {"category": ["TE"], "type": {"asset": ["' . Configure::read('Collibra.vocabulary.isaRequest') . '"] }}, "fields": ["' . Configure::read('Collibra.attribute.isaRequestPersonId') . '"] }'
		);
		$isaRequests = json_decode($resp);

		$arrRequests = array();
		foreach($isaRequests->results as $r){
			if ($page == 'past' && !in_array($r->status, $completedStatuses)) {
				continue;
			} elseif ($page == 'current' && in_array($r->status, $completedStatuses)) {
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
				$request->terms = $requestedTerms;
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
			foreach($workflowResp->formProperties as $wf){
				foreach($r->attributeReferences->attributeReference as $attr){
					if($attr->labelReference->signifier == $wf->name){
						$arrNewAttr[$attr->labelReference->signifier] = $attr;
						break;
					}
				}
			}
			$r->attributeReferences->attributeReference = $arrNewAttr;
		}

		$psName = '';
		$psRole = 'N/A';
		$psDepartment = 'N/A';
		$psPersonID = $byuUser->identifiers->person_id;
		if(isset($byuUser->names->preferred_name)){
			$psName = $byuUser->names->preferred_name;
		}
		if(isset($byuUser->employee_information->job_title)){
			$psRole = $byuUser->employee_information->job_title;
		}
		if(isset($byuUser->employee_information->department)){
			$psDepartment = $byuUser->employee_information->department;
		}
		$this->set('psName', $psName);
		$this->set('psRole', $psRole);
		$this->set('psDepartment', $psDepartment);
		$this->set('requests', $arrRequests);
		$this->set('page', $page);
	}
}
