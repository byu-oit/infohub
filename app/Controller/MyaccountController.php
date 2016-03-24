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
		phpCAS::logout();
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
				////////////////////////////////////////////
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
				////////////////////////////////////////////

				// load approval objects for request
				////////////////////////////////////////////
				$termRid = $r->name->id;
				//$termRid = 'd3b7c819-6f5f-43da-88ef-c99c97963842';
				$resp = $objCollibra->request(
					array(
						'url'=>'output/data_table',
						'post'=>true,
						'json'=>true,
						'params'=>'{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"termrid"}},{"Column":{"fieldName":"termsignifier"}},{"Column":{"fieldName":"relationrid"}},{"Column":{"fieldName":"startDate"}},{"Column":{"fieldName":"endDate"}},{"Column":{"fieldName":"relstatusrid"}},{"Column":{"fieldName":"relstatusname"}},{"Column":{"fieldName":"Attr9debcb7584544f44b6ad7d85d8b67cb7rid"}},{"Column":{"fieldName":"Attr9debcb7584544f44b6ad7d85d8b67cb7longExpr"}},{"Column":{"fieldName":"Attr9debcb7584544f44b6ad7d85d8b67cb7","fieldId":"9debcb75-8454-4f44-b6ad-7d85d8b67cb7"}},{"Column":{"fieldName":"Attr4331ec0988a248e6b0969ece6648aff3rid"}},{"Column":{"fieldName":"Attr4331ec0988a248e6b0969ece6648aff3longExpr"}},{"Column":{"fieldName":"Attr4331ec0988a248e6b0969ece6648aff3","fieldId":"4331ec09-88a2-48e6-b096-9ece6648aff3"}},{"Column":{"fieldName":"Attr0cbbfd32fc9747cebef1a89ae4e77ee8rid"}},{"Column":{"fieldName":"Attr0cbbfd32fc9747cebef1a89ae4e77ee8longExpr"}},{"Column":{"fieldName":"Attr0cbbfd32fc9747cebef1a89ae4e77ee8","fieldId":"0cbbfd32-fc97-47ce-bef1-a89ae4e77ee8"}},{"Column":{"fieldName":"Attr9a18e247c09040c3896eab97335ae759rid"}},{"Column":{"fieldName":"Attr9a18e247c09040c3896eab97335ae759longExpr"}},{"Column":{"fieldName":"Attr9a18e247c09040c3896eab97335ae759","fieldId":"9a18e247-c090-40c3-896e-ab97335ae759"}},{"Column":{"fieldName":"statusrid"}},{"Column":{"fieldName":"statusname"}}],"Resources":{"Term":{"Id":{"name":"termrid"},"Signifier":{"name":"termsignifier"},"Relation":{"typeId":"8750ed82-b3be-4a61-92ac-6df2e41b408c","Id":{"name":"relationrid"},"StartingDate":{"name":"startDate"},"EndingDate":{"name":"endDate"},"Status":{"Id":{"name":"relstatusrid"},"Signifier":{"name":"relstatusname"}},"Filter":{"AND":[{"Field":{"name":"reltermrid","operator":"EQUALS","value":"'.$termRid.'"}}]},"type":"TARGET","Source":{"Id":{"name":"reltermrid"}}},"StringAttribute":[{"Id":{"name":"Attr9debcb7584544f44b6ad7d85d8b67cb7rid"},"labelId":"9debcb75-8454-4f44-b6ad-7d85d8b67cb7","LongExpression":{"name":"Attr9debcb7584544f44b6ad7d85d8b67cb7longExpr"},"Value":{"name":"Attr9debcb7584544f44b6ad7d85d8b67cb7"}},{"Id":{"name":"Attr4331ec0988a248e6b0969ece6648aff3rid"},"labelId":"4331ec09-88a2-48e6-b096-9ece6648aff3","LongExpression":{"name":"Attr4331ec0988a248e6b0969ece6648aff3longExpr"},"Value":{"name":"Attr4331ec0988a248e6b0969ece6648aff3"}},{"Id":{"name":"Attr0cbbfd32fc9747cebef1a89ae4e77ee8rid"},"labelId":"0cbbfd32-fc97-47ce-bef1-a89ae4e77ee8","LongExpression":{"name":"Attr0cbbfd32fc9747cebef1a89ae4e77ee8longExpr"},"Value":{"name":"Attr0cbbfd32fc9747cebef1a89ae4e77ee8"}},{"Id":{"name":"Attr9a18e247c09040c3896eab97335ae759rid"},"labelId":"9a18e247-c090-40c3-896e-ab97335ae759","LongExpression":{"name":"Attr9a18e247c09040c3896eab97335ae759longExpr"},"Value":{"name":"Attr9a18e247c09040c3896eab97335ae759"}}],"Status":{"Id":{"name":"statusrid"},"Signifier":{"name":"statusname"}},"Filter":{"AND":[{"AND":[{"Field":{"name":"reltermrid","operator":"EQUALS","value":"'.$termRid.'"}}]}]},"Order":[{"Field":{"name":"termsignifier","order":"ASC"}}]}},"displayStart":0,"displayLength":20}}'
					)
				);
				$approvalObjects = json_decode($resp);// add property to request object to hold approvals
				if(isset($approvalObjects->aaData)){
					$request->approvals = $approvalObjects;
				}
				////////////////////////////////////////////
				
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