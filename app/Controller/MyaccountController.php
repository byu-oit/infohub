<?php

class MyaccountController extends AppController {
	private static function sortUsers($a, $b){
		return strcmp($a->firstName, $b->firstName);
	}
	
	private static function sortRequests($a, $b){
		return strcmp($a->createdOn, $b->createdOn);
	}

	private static function sortAttributes($a, $b){
		return strcmp($a->labelReference->signifier, $b->labelReference->signifier);
	}
	
	public function logout() {
		phpCAS::logout(array('url'=>'http://'.$_SERVER['SERVER_NAME']));
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
		$requestStatus = 'Submitted';
		$page = 'current';
		if(isset($this->request->query['s'])){
			$requestStatus = 'Complete';
			$page = 'past';
		}
		
		if(!phpCAS::isAuthenticated()){
			phpCAS::forceAuthentication();
		}else{
			$netID = phpCAS::getUser();
			$this->loadModel('BYUWS');
			$objBYUWS = new BYUWS();
			$byuUser = $objBYUWS->personalSummary($netID);
			$personID = $byuUser->identifiers->person_id;
		}
		
		// attempt to reindex source to make sure latest requests are displayed
		$this->loadModel('CollibraAPI');
		$objCollibra = new CollibraAPI();
		/*$resp = $objCollibra->request(
			array(
				'url'=>'search/re-index',
				'post'=>true
			)
		);*/
		
		// get all request for this user
		$resp = $objCollibra->request(
			array(
				'url'=>'search',
				'post'=>true,
				'json'=>true,
				'params'=>'{"query":"'.$personID.'", "filter": {"category": ["TE"], "vocabulary": ["b1ae53a6-46a1-4fdc-a4fb-5dcfcd9c61a1"] }, "fields": ["f1d5726a-32a2-47c4-b48c-d3be43880462"] }'
			)
		);
		$isaRequests = json_decode($resp);
		
		$arrRequests = array();
		foreach($isaRequests->results as $r){
			if($r->status == $requestStatus){
				// load terms details
				$resp = $objCollibra->request(
					array(
						'url'=>'term/'.$r->name->id
					)
				);
				$request = json_decode($resp);
				//$createdDate = $request->createdOn/1000;
				//$createdDate = date('m/d/Y', $request->createdOn);
				
				// load terms submitted in request
				$resp = $objCollibra->request(
					array(
						'url'=>'output/data_table',
						'post'=>true,
						'json'=>true,
						'params'=>'{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"termrid"}},{"Column":{"fieldName":"termsignifier"}},{"Column":{"fieldName":"relationrid"}},{"Column":{"fieldName":"startDate"}},{"Column":{"fieldName":"endDate"}},{"Column":{"fieldName":"relstatusrid"}},{"Column":{"fieldName":"relstatusname"}},{"Column":{"fieldName":"communityname"}},{"Column":{"fieldName":"commrid"}},{"Column":{"fieldName":"domainname"}},{"Column":{"fieldName":"domainrid"}},{"Column":{"fieldName":"concepttypename"}},{"Column":{"fieldName":"concepttyperid"}}], "Resources":{"Term":{"Id":{"name":"termrid"}, "Signifier":{"name":"termsignifier"}, "Relation":{"typeId":"f4a6a8fe-8509-458a-9b60-417141a9abd4", "Id":{"name":"relationrid"}, "StartingDate":{"name":"startDate"}, "EndingDate":{"name":"endDate"}, "Status":{"Id":{"name":"relstatusrid"}, "Signifier":{"name":"relstatusname"}}, "Filter":{"AND":[{"Field":{"name":"reltermrid", "operator":"EQUALS", "value":"'.$r->name->id.'"}}]}, "type":"TARGET", "Source":{"Id":{"name":"reltermrid"}}}, "Vocabulary":{"Community":{"Name":{"name":"communityname"}, "Id":{"name":"commrid"}}, "Name":{"name":"domainname"}, "Id":{"name":"domainrid"}}, "ConceptType":[{"Signifier":{"name":"concepttypename"}, "Id":{"name":"concepttyperid"}}], "Filter":{"AND":[{"AND":[{"Field":{"name":"reltermrid", "operator":"EQUALS", "value":"'.$r->name->id.'"}}]}]}, "Order":[{"Field":{"name":"termsignifier", "order":"ASC"}}]}}, "displayStart":0, "displayLength":10}}'
					)
				);
				$requestedTerms = json_decode($resp);
				
				// add property to request object to hold terms
				if(isset($requestedTerms->aaData)){
					$request->terms = $requestedTerms;
				}
				
				// add to request data array
				array_push($arrRequests, $request);
			}
		}
		// sort results by date added
		usort($arrRequests, 'self::sortRequests');
		
		// sort request attribute data based on workflow form field order
		$workflowResp = $objCollibra->request(array('url'=>'workflow/'.Configure::read('isaWorkflow').'/form/start'));
		$workflowResp = json_decode($workflowResp);
		foreach($arrRequests as $r){
			$arrNewAttr = array();
			foreach($workflowResp->formProperties as $wf){
				foreach($r->attributeReferences->attributeReference as $attr){
					if($attr->labelReference->signifier == $wf->name){
						array_push($arrNewAttr, $attr);
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