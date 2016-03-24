<?php

class SearchController extends AppController {
	public function beforeFilter() {
		parent::beforeFilter();
	}
	
	private static function  str_getcsv2($arr){
		$a = str_getcsv($arr, ";", '"');
		$epoch = $a[0]/1000;
		$a[0] = date('m/d/Y', $epoch);        
		return $a;
	}
	
	private static function sortUsers($a, $b){
		return strcmp($a->firstName, $b->firstName);
	}
	
	private static function sortTerms($a, $b){
		return strcmp($a->signifier, $b->signifier);
	}
	
	private static function sortCommunities($a, $b){
		if ($a->name < $b->name) {
			return -1;
		} else if ($a->name > $b->name) {
			return 1;
		} else {
			return 0;
		}
	}
	
	private function convert_smart_quotes($string){ 
		$search = array(chr(145), 
			chr(146), 
			chr(147), 
			chr(148), 
			chr(151)); 

		$replace = array("'", 
			"'", 
			'"', 
			'"', 
			'-'); 

		return str_replace($search, $replace, $string); 
	}
	
	// NORMAL VIEWS
	/////////////////////////////////////////////////////////
	public function index() {
		$this->set('commonSearches', $this->getCommonSearches());
	}
	
	public function catalog() {
		$this->set('commonSearches', $this->getCommonSearches());
	}
	
	public function term() {
		if(!isset($this->request->params['pass'][0])){
			header('location: /search');
			exit;
		}
		$query = $this->request->params['pass'][0];
		$terms = $this->getTermDetails($query);
		
		$this->set('commonSearches', $this->getCommonSearches());
		//$this->set('totalPages', 0);
		//$this->set('pageNum', 0);
		$this->set('terms', $terms);
		$this->set('searchInput', '');
		
		$this->render('results');
	}
	
	public function listTerms() {
		if(!isset($this->request->params['pass'][0])){
			header('location: /search');
			exit;
		}
		
		App::uses('Helpers', 'Model');
		$page = 0;
		if(isset($this->request->params['pass'][1])){
			$page = Helpers::getInt($this->request->params['pass'][1]);
		}
		if($page==0) $page=1;
		
		$domainID = $this->request->params['pass'][0];
		$terms = $this->getDomainTerms($domainID, $page-1, 25);
		
		$this->set('commonSearches', $this->getCommonSearches());
		//$this->set('totalPages', ceil($terms->iTotalDisplayRecords/25));
		//$this->set('pageNum', $page);
		$this->set('terms', $terms);
		$this->set('searchInput', '');
		$this->set('domain', $domainID);
		
		$this->render('results');
	}
	
	public function results() {
		App::uses('Helpers', 'Model');
		$query = htmlentities($this->request->params['pass'][0]);
		$defaultCommunity = Configure::read('byuCommunity');
		
		// set community filter based on querystring value
		///////////////////////////////////////////////////////
		$filter = '';
		if(isset($this->request->query['f'])){
			if($this->request->query['f'] != ''){
				$filter = $this->request->query['f'];
			}
		}
		///////////////////////////////////////////////////////
		
		// set sort filter based on querystring value
		///////////////////////////////////////////////////////
		$sort = isset($this->request->query['s'])?$this->request->query['s']:0;
		$sortOrder = null;
		$sortField = null;
		switch($sort){
			case 0:
				$sortField = 'score';
				$sortOrder = 'DESC';
				break;
			case 1:
				$sortField = 'termsignifier';
				break;
			case 2:
				$sortField = 'lastModified';
				$sortOrder = 'DESC';
				break;
			case 3:
				$sortField = 'Attre0937764544a4d2198cedc0c1936b465';
				break;
		}
		///////////////////////////////////////////////////////
		
		$page = 0;
		if(isset($this->request->params['pass'][1])){
			$page = Helpers::getInt($this->request->params['pass'][1]);
		}
		if($page==0) $page=1;
		//$query = str_replace('%2B', 'ss', $this->request->params['pass'][0]);
		
		// get all terms matching query
		$terms = $this->searchTerms(html_entity_decode($query), $page-1, 10, $sortField, $sortOrder, $filter);
		//print_r($terms);exit;
		
		// save search and delete anything over 300 entries
		if(sizeof($terms->aaData)>0){
			$this->loadModel('CmsPage');
			// delete last record
			$results = $this->CmsPage->query("SELECT * FROM common_searches");
			if(sizeof($results)>=300){
				$this->CmsPage->query("DELETE FROM common_searches WHERE id=".$results[0]['common_searches']['id']);
			}
			// add new record
			$this->CmsPage->query("INSERT INTO common_searches (query) VALUES('".$query."')");
		}
		///////////////////////////////////////////////////////
		
		// get all sub communities for Data Governance Council
		// to be used in the search filter drop down
		///////////////////////////////////////////////////////
		$this->loadModel('CollibraAPI');
		$objCollibra = new CollibraAPI();
		$resp = $objCollibra->request(array('url'=>'community/'.$defaultCommunity.'/sub-communities'));
		$communities = json_decode($resp);
		usort($communities->communityReference, 'self::sortCommunities');
		///////////////////////////////////////////////////////
		
		if($filter == $defaultCommunity) $filter = '';
		
		$this->set('commonSearches', $this->getCommonSearches());
		$this->set('communities', $communities);
		//$this->set('totalPages', ceil($terms->iTotalDisplayRecords/10));
		//$this->set('pageNum', $page);
		$this->set('filter', $filter);
		$this->set('sort', $sort);
		$this->set('terms', $terms);
		$this->set('searchInput', str_replace("&amp;", "+", $query));
	}
	
	
	// AJAX VIEWS
	/////////////////////////////////////////////////////////

	// Called from search landing page
	public function loadCommunityData($community = null){
		if(!$community) $community = Configure::read('byuCommunity');
		$this->autoRender = false;
		$resp = '';
		if ($this->request->is('post')) {
			if(isset($this->request->data['c']) && $this->request->data['c'] != ''){
				$community = $this->request->data['c'];
			}
		}
			
		$obj = new SearchController();
		$obj->loadModel('CollibraAPI');
		$objCollibra = new CollibraAPI();
		$request = '{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"termrid"}},{"Column":{"fieldName":"termsignifier"}},{"Column":{"fieldId":"00000000-0000-0000-0000-000000000202","fieldName":"Attr00000000000000000000000000000202"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202longExpr"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202rid"}},{"Column":{"fieldName":"Attr0d798f70b3ca4af2b28354f84c4714aarid"}},{"Column":{"fieldName":"Attr0d798f70b3ca4af2b28354f84c4714aa","fieldId":"0d798f70-b3ca-4af2-b283-54f84c4714aa"}},{"Column":{"fieldName":"statusname"}},{"Column":{"fieldName":"statusrid"}},{"Column":{"fieldName":"communityname"}},{"Column":{"fieldName":"commrid"}},{"Column":{"fieldName":"domainname"}},{"Column":{"fieldName":"domainrid"}},{"Column":{"fieldName":"concepttypename"}},{"Column":{"fieldId":"e0937764-544a-4d21-98ce-dc0c1936b465","fieldName":"Attre0937764544a4d2198cedc0c1936b465"}},{"Column":{"fieldName":"Attre0937764544a4d2198cedc0c1936b465rid"}},{"Group":{"name":"synonym_for","Columns":[{"Column":{"fieldName":"Relc06ed0b7032f4d0fae405824c12f94a6T","fieldId":"c06ed0b7-032f-4d0f-ae40-5824c12f94a6T","label":"Business Term"}},{"Column":{"fieldName":"relRelc06ed0b7032f4d0fae405824c12f94a6Trid","label":"Business Term ID"}},{"Column":{"fieldName":"Relc06ed0b7032f4d0fae405824c12f94a6Trid","label":"Business Term Business Term ID"}}]}}],"Resources":{"Term":{"Id":{"name":"termrid"},"Signifier":{"name":"termsignifier"},"StringAttribute":[{"Value":{"name":"Attr00000000000000000000000000000202"},"LongExpression":{"name":"Attr00000000000000000000000000000202longExpr"},"Id":{"name":"Attr00000000000000000000000000000202rid"},"labelId":"00000000-0000-0000-0000-000000000202"}],"BooleanAttribute":[{"Id":{"name":"Attr0d798f70b3ca4af2b28354f84c4714aarid"},"labelId":"0d798f70-b3ca-4af2-b283-54f84c4714aa","Value":{"name":"Attr0d798f70b3ca4af2b28354f84c4714aa"}}],"Status":{"Signifier":{"name":"statusname"},"Id":{"name":"statusrid"}},"Vocabulary":{"Community":{"Name":{"name":"communityname"},"Id":{"name":"commrid"}},"Name":{"name":"domainname"},"Id":{"name":"domainrid"}},"ConceptType":[{"Signifier":{"name":"concepttypename"},"Id":{"name":"concepttyperid"}}],"SingleValueListAttribute":[{"Value":{"name":"Attre0937764544a4d2198cedc0c1936b465"},"Id":{"name":"Attre0937764544a4d2198cedc0c1936b465rid"},"labelId":"e0937764-544a-4d21-98ce-dc0c1936b465"}],"Relation":[{"typeId":"c06ed0b7-032f-4d0f-ae40-5824c12f94a6","type":"TARGET","Id":{"name":"relRelc06ed0b7032f4d0fae405824c12f94a6Trid"},"Source":{"Id":{"name":"Relc06ed0b7032f4d0fae405824c12f94a6Trid"},"Signifier":{"name":"Relc06ed0b7032f4d0fae405824c12f94a6T"}}}],"Filter":{"AND":[{"OR":[{"Field":{"name":"concepttypename","operator":"INCLUDES","value":"Business Term"}},{"Field":{"name":"concepttypename","operator":"INCLUDES","value":"Synonym"}}]},';
		if(!Configure::read('allowUnapprovedeTerms')){
		}
		$resp = $objCollibra->request(
			array(
				'url'=>'output/data_table',
				'post'=>true,
				'json'=>true,
				'params'=>'{"TableViewConfig":{"Columns":[{"Community":{"name":"Subcommunities","Columns":[{"Column":{"fieldName":"subcommunityid"}},{"Column":{"fieldName":"subcommunity"}},{"Column":{"fieldName":"hasNonMetaChildren"}},{"Group":{"Columns":[{"Column":{"label":"Admin User ID","fieldId":"00000000-0000-0000-0000-000000005015","fieldName":"userRole00000000000000000000000000005015rid"}},{"Column":{"label":"Admin Gender","fieldName":"userRole00000000000000000000000000005015gender"}},{"Column":{"label":"Admin First Name","fieldName":"userRole00000000000000000000000000005015fn"}},{"Column":{"label":"Admin Last Name","fieldName":"userRole00000000000000000000000000005015ln"}}],"name":"Role00000000000000000000000000005015"}},{"Group":{"Columns":[{"Column":{"label":"Admin Group ID","fieldName":"groupRole00000000000000000000000000005015grid"}},{"Column":{"label":"Admin Group Name","fieldName":"groupRole00000000000000000000000000005015ggn"}}],"name":"Role00000000000000000000000000005015g"}},{"Group":{"Columns":[{"Column":{"label":"Collibra Steward User ID","fieldId":"00000000-0000-0000-0000-000000005016","fieldName":"userRole00000000000000000000000000005016rid"}},{"Column":{"label":"Collibra Steward Gender","fieldName":"userRole00000000000000000000000000005016gender"}},{"Column":{"label":"Collibra Steward First Name","fieldName":"userRole00000000000000000000000000005016fn"}},{"Column":{"label":"Collibra Steward Last Name","fieldName":"userRole00000000000000000000000000005016ln"}}],"name":"Role00000000000000000000000000005016"}},{"Group":{"Columns":[{"Column":{"label":"Collibra Steward Group ID","fieldName":"groupRole00000000000000000000000000005016grid"}},{"Column":{"label":"Collibra Steward Group Name","fieldName":"groupRole00000000000000000000000000005016ggn"}}],"name":"Role00000000000000000000000000005016g"}},{"Group":{"Columns":[{"Column":{"label":"Stakeholder User ID","fieldId":"00000000-0000-0000-0000-000000005018","fieldName":"userRole00000000000000000000000000005018rid"}},{"Column":{"label":"Stakeholder Gender","fieldName":"userRole00000000000000000000000000005018gender"}},{"Column":{"label":"Stakeholder First Name","fieldName":"userRole00000000000000000000000000005018fn"}},{"Column":{"label":"Stakeholder Last Name","fieldName":"userRole00000000000000000000000000005018ln"}}],"name":"Role00000000000000000000000000005018"}},{"Group":{"Columns":[{"Column":{"label":"Stakeholder Group ID","fieldName":"groupRole00000000000000000000000000005018grid"}},{"Column":{"label":"Stakeholder Group Name","fieldName":"groupRole00000000000000000000000000005018ggn"}}],"name":"Role00000000000000000000000000005018g"}}]}},{"Vocabulary":{"name":"Vocabularies","Columns":[{"Column":{"fieldName":"vocabulary"}},{"Column":{"fieldName":"vocabularyid"}},{"Column":{"fieldName":"domainType"}},{"Group":{"Columns":[{"Column":{"label":"Admin User ID","fieldId":"00000000-0000-0000-0000-000000005015","fieldName":"userRole00000000000000000000000000005015VOCSUFFIXrid"}},{"Column":{"label":"Admin Gender","fieldName":"userRole00000000000000000000000000005015VOCSUFFIXgender"}},{"Column":{"label":"Admin First Name","fieldName":"userRole00000000000000000000000000005015VOCSUFFIXfn"}},{"Column":{"label":"Admin Last Name","fieldName":"userRole00000000000000000000000000005015VOCSUFFIXln"}}],"name":"Role00000000000000000000000000005015VOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Admin Group ID","fieldName":"groupRole00000000000000000000000000005015gVOCSUFFIXrid"}},{"Column":{"label":"Admin Group Name","fieldName":"groupRole00000000000000000000000000005015gVOCSUFFIXgn"}}],"name":"Role00000000000000000000000000005015gVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Collibra Steward User ID","fieldId":"00000000-0000-0000-0000-000000005016","fieldName":"userRole00000000000000000000000000005016VOCSUFFIXrid"}},{"Column":{"label":"Collibra Steward Gender","fieldName":"userRole00000000000000000000000000005016VOCSUFFIXgender"}},{"Column":{"label":"Collibra Steward First Name","fieldName":"userRole00000000000000000000000000005016VOCSUFFIXfn"}},{"Column":{"label":"Collibra Steward Last Name","fieldName":"userRole00000000000000000000000000005016VOCSUFFIXln"}}],"name":"Role00000000000000000000000000005016VOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Collibra Steward Group ID","fieldName":"groupRole00000000000000000000000000005016gVOCSUFFIXrid"}},{"Column":{"label":"Collibra Steward Group Name","fieldName":"groupRole00000000000000000000000000005016gVOCSUFFIXgn"}}],"name":"Role00000000000000000000000000005016gVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Stakeholder User ID","fieldId":"00000000-0000-0000-0000-000000005018","fieldName":"userRole00000000000000000000000000005018VOCSUFFIXrid"}},{"Column":{"label":"Stakeholder Gender","fieldName":"userRole00000000000000000000000000005018VOCSUFFIXgender"}},{"Column":{"label":"Stakeholder First Name","fieldName":"userRole00000000000000000000000000005018VOCSUFFIXfn"}},{"Column":{"label":"Stakeholder Last Name","fieldName":"userRole00000000000000000000000000005018VOCSUFFIXln"}}],"name":"Role00000000000000000000000000005018VOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Stakeholder Group ID","fieldName":"groupRole00000000000000000000000000005018gVOCSUFFIXrid"}},{"Column":{"label":"Stakeholder Group Name","fieldName":"groupRole00000000000000000000000000005018gVOCSUFFIXgn"}}],"name":"Role00000000000000000000000000005018gVOCSUFFIX"}}]}}],"Resources":{"Community":{"Id":{"name":"subcommunityid"},"Name":{"name":"subcommunity"},"Meta":{"name":"issubmeta"},"hasNonMetaChildren":{"name":"hasNonMetaChildren"},"ParentCommunity":{"Id":{"name":"parentCommunityId"}},"Member":[{"User":{"Gender":{"name":"userRole00000000000000000000000000005015gender"},"FirstName":{"name":"userRole00000000000000000000000000005015fn"},"Id":{"name":"userRole00000000000000000000000000005015rid"},"LastName":{"name":"userRole00000000000000000000000000005015ln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005015sig"},"name":"Role00000000000000000000000000005015","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005015rid"}},"roleId":"00000000-0000-0000-0000-000000005015"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005015g"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005015grid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005015ggn"},"Id":{"name":"groupRole00000000000000000000000000005015grid"}},"roleId":"00000000-0000-0000-0000-000000005015"},{"User":{"Gender":{"name":"userRole00000000000000000000000000005016gender"},"FirstName":{"name":"userRole00000000000000000000000000005016fn"},"Id":{"name":"userRole00000000000000000000000000005016rid"},"LastName":{"name":"userRole00000000000000000000000000005016ln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016sig"},"name":"Role00000000000000000000000000005016","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016rid"}},"roleId":"00000000-0000-0000-0000-000000005016"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016g"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016grid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005016ggn"},"Id":{"name":"groupRole00000000000000000000000000005016grid"}},"roleId":"00000000-0000-0000-0000-000000005016"},{"User":{"Gender":{"name":"userRole00000000000000000000000000005018gender"},"FirstName":{"name":"userRole00000000000000000000000000005018fn"},"Id":{"name":"userRole00000000000000000000000000005018rid"},"LastName":{"name":"userRole00000000000000000000000000005018ln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005018sig"},"name":"Role00000000000000000000000000005018","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005018rid"}},"roleId":"00000000-0000-0000-0000-000000005018"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005018g"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005018grid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005018ggn"},"Id":{"name":"groupRole00000000000000000000000000005018grid"}},"roleId":"00000000-0000-0000-0000-000000005018"}],"Filter":{"AND":[{"AND":[{"Field":{"name":"issubmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"parentCommunityId","operator":"EQUALS","value":"'.$community.'"}}]}]},"Order":[{"Field":{"name":"subcommunity","order":"ASC"}}]},"Vocabulary":{"Name":{"name":"vocabulary"},"Id":{"name":"vocabularyid"},"Meta":{"name":"isvocmeta"},"Community":{"Id":{"name":"vocabularyParentCommunityId"}},"VocabularyType":{"Signifier":{"name":"domainType"}},"Member":[{"User":{"Gender":{"name":"userRole00000000000000000000000000005015VOCSUFFIXgender"},"FirstName":{"name":"userRole00000000000000000000000000005015VOCSUFFIXfn"},"Id":{"name":"userRole00000000000000000000000000005015VOCSUFFIXrid"},"LastName":{"name":"userRole00000000000000000000000000005015VOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005015VOCSUFFIXsig"},"name":"Role00000000000000000000000000005015VOCSUFFIX","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005015VOCSUFFIXrid"}},"roleId":"00000000-0000-0000-0000-000000005015"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005015gVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005015gVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005015gVOCSUFFIXgn"},"Id":{"name":"groupRole00000000000000000000000000005015gVOCSUFFIXrid"}},"roleId":"00000000-0000-0000-0000-000000005015"},{"User":{"Gender":{"name":"userRole00000000000000000000000000005016VOCSUFFIXgender"},"FirstName":{"name":"userRole00000000000000000000000000005016VOCSUFFIXfn"},"Id":{"name":"userRole00000000000000000000000000005016VOCSUFFIXrid"},"LastName":{"name":"userRole00000000000000000000000000005016VOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016VOCSUFFIXsig"},"name":"Role00000000000000000000000000005016VOCSUFFIX","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016VOCSUFFIXrid"}},"roleId":"00000000-0000-0000-0000-000000005016"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016gVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016gVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005016gVOCSUFFIXgn"},"Id":{"name":"groupRole00000000000000000000000000005016gVOCSUFFIXrid"}},"roleId":"00000000-0000-0000-0000-000000005016"},{"User":{"Gender":{"name":"userRole00000000000000000000000000005018VOCSUFFIXgender"},"FirstName":{"name":"userRole00000000000000000000000000005018VOCSUFFIXfn"},"Id":{"name":"userRole00000000000000000000000000005018VOCSUFFIXrid"},"LastName":{"name":"userRole00000000000000000000000000005018VOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005018VOCSUFFIXsig"},"name":"Role00000000000000000000000000005018VOCSUFFIX","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005018VOCSUFFIXrid"}},"roleId":"00000000-0000-0000-0000-000000005018"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005018gVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005018gVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005018gVOCSUFFIXgn"},"Id":{"name":"groupRole00000000000000000000000000005018gVOCSUFFIXrid"}},"roleId":"00000000-0000-0000-0000-000000005018"}],"Filter":{"AND":[{"AND":[{"Field":{"name":"isvocmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"vocabularyParentCommunityId","operator":"EQUALS","value":"'.$community.'"}}]},{"AND":[{"Field":{"name":"domainType","operator":"EQUALS","value":"Glossary"}}]}]},"Order":[{"Field":{"name":"vocabulary","order":"ASC"}}]}},"displayStart":0,"displayLength":100,"generalConceptId":"4e756e1e-11ee-4d1e-bfaa-fb0ada974fc5"}}'
			)
		);

		return $resp;
	}
	
	// get list of all other terms within a vocabulary/glossary
	public function getFullVocab() {
		$vocabRID= $this->request->query['rid'];
		$this->loadModel('CollibraAPI');
		$objCollibra = new CollibraAPI();
		$resp = $objCollibra->request(
			array('url'=>'vocabulary/'.$vocabRID.'/terms')
		);
		$jsonResp = json_decode($resp);
		
		// create request JSON string
		$request = '{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"termrid"}},{"Column":{"fieldName":"termsignifier"}},{"Column":{"fieldId":"00000000-0000-0000-0000-000000000202","fieldName":"Attr00000000000000000000000000000202"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202longExpr"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202rid"}},{"Column":{"fieldName":"Attr0d798f70b3ca4af2b28354f84c4714aarid"}},{"Column":{"fieldName":"Attr0d798f70b3ca4af2b28354f84c4714aa","fieldId":"0d798f70-b3ca-4af2-b283-54f84c4714aa"}},{"Column":{"fieldName":"statusname"}},{"Column":{"fieldName":"statusrid"}},{"Column":{"fieldName":"communityname"}},{"Column":{"fieldName":"commrid"}},{"Column":{"fieldName":"domainname"}},{"Column":{"fieldName":"domainrid"}},{"Column":{"fieldName":"concepttypename"}},{"Column":{"fieldId":"e0937764-544a-4d21-98ce-dc0c1936b465","fieldName":"Attre0937764544a4d2198cedc0c1936b465"}},{"Column":{"fieldName":"Attre0937764544a4d2198cedc0c1936b465rid"}},{"Group":{"name":"synonym_for","Columns":[{"Column":{"fieldName":"Relc06ed0b7032f4d0fae405824c12f94a6T","fieldId":"c06ed0b7-032f-4d0f-ae40-5824c12f94a6T","label":"Business Term"}},{"Column":{"fieldName":"relRelc06ed0b7032f4d0fae405824c12f94a6Trid","label":"Business Term ID"}},{"Column":{"fieldName":"Relc06ed0b7032f4d0fae405824c12f94a6Trid","label":"Business Term Business Term ID"}}]}}],"Resources":{"Term":{"Id":{"name":"termrid"},"Signifier":{"name":"termsignifier"},"StringAttribute":[{"Value":{"name":"Attr00000000000000000000000000000202"},"LongExpression":{"name":"Attr00000000000000000000000000000202longExpr"},"Id":{"name":"Attr00000000000000000000000000000202rid"},"labelId":"00000000-0000-0000-0000-000000000202"}],"BooleanAttribute":[{"Id":{"name":"Attr0d798f70b3ca4af2b28354f84c4714aarid"},"labelId":"0d798f70-b3ca-4af2-b283-54f84c4714aa","Value":{"name":"Attr0d798f70b3ca4af2b28354f84c4714aa"}}],"Status":{"Signifier":{"name":"statusname"},"Id":{"name":"statusrid"}},"Vocabulary":{"Community":{"Name":{"name":"communityname"},"Id":{"name":"commrid"}},"Name":{"name":"domainname"},"Id":{"name":"domainrid"}},"ConceptType":[{"Signifier":{"name":"concepttypename"},"Id":{"name":"concepttyperid"}}],"SingleValueListAttribute":[{"Value":{"name":"Attre0937764544a4d2198cedc0c1936b465"},"Id":{"name":"Attre0937764544a4d2198cedc0c1936b465rid"},"labelId":"e0937764-544a-4d21-98ce-dc0c1936b465"}],"Relation":[{"typeId":"c06ed0b7-032f-4d0f-ae40-5824c12f94a6","type":"TARGET","Id":{"name":"relRelc06ed0b7032f4d0fae405824c12f94a6Trid"},"Source":{"Id":{"name":"Relc06ed0b7032f4d0fae405824c12f94a6Trid"},"Signifier":{"name":"Relc06ed0b7032f4d0fae405824c12f94a6T"}}}],"Filter":{"AND":[{"OR":[{"Field":{"name":"concepttypename","operator":"INCLUDES","value":"Business Term"}},{"Field":{"name":"concepttypename","operator":"INCLUDES","value":"Synonym"}}]},';
		if(!Configure::read('allowUnapprovedeTerms')){
			$request .= '	{  '.
				'	 "AND":[  '.
				'		{  '.
				'		   "Field":{  '.
				'			  "name":"statusname",'.
				'			  "operator":"EQUALS",'.
				'			  "value":"Accepted"'.
				'		   }'.
				'		}'.
				'	 ]'.
				'	},';
		}
		$request .= '	{  '.
			'	 "AND":[  '.
			'		{  '.
			'		   "Field":{  '.
			'			  "name":"domainrid",'.
			'			  "operator":"EQUALS",'.
			'			  "value":"'.$vocabRID.'"'.
			'		   }'.
			'		}'.
			'	 ]'.
			'  }'.
			']},"Order":[{"Field":{"name":"termsignifier","order":"ASC"}}]}},"displayStart":0,"displayLength":1000,"relationTypeId":"bc80a28c-57f6-49a7-9d2b-063e91614824","roleDirection":true}}';

		$resp = $objCollibra->request(
			array(
				'url'=>'output/data_table',
				'post'=>true,
				'json'=>true,
				'params'=>$request
			)
		);
		
		$jsonResp = json_decode($resp);
		//print_r($jsonResp);
		//exit;
		
		echo '<div class="checkCol">';
		$itemCount = 0;
		for($i=0; $i<sizeof($jsonResp->aaData); $i++){
			$term = $jsonResp->aaData[$i];
			$termName = $term->termsignifier;
			$termID = $term->termrid;
			$termDef = addslashes(strip_tags($term->Attr00000000000000000000000000000202longExpr));
			if(Configure::read('allowUnrequestableTerms')){
				$disabled = '';
			}else{
				$disabled = $term->Attr0d798f70b3ca4af2b28354f84c4714aa == 'false'?'disabled':'';
			}
			if(!$disabled){
				if(sizeof($term->synonym_for)!=0){
					$termDef = 'Synonym for '.$term->synonym_for[0]->Relc06ed0b7032f4d0fae405824c12f94a6T;
				}

				$random = uniqid(rand(111111,999999));
				$classification = $term->Attre0937764544a4d2198cedc0c1936b465;
				switch($classification){
					case '1 - Public':
						$classificationIcon = '<img src="/img/iconPublic.png" title="Public" alt="Public" width="9" />';
						break;
					case '2 - Internal':
						$classificationIcon = '<img src="/img/iconInternal.png" title="Internal" alt="Internal" width="9" />';
						break;
					case '3 - Confidential':
						$classificationIcon = '<img src="/img/iconClassified.png" title="Confidential" alt="Confidential" width="9" />';
						break;
					case '4 - Highly Confidential':
						$classificationIcon = '<img src="/img/iconHighClassified.png" title="Highly Confidential" alt="Highly Confidential" width="9" />';
						break;
					default:
						$classificationIcon = '';
						break;
				}

				if($itemCount>0 && $itemCount%2==0){
					echo '</div>';echo '</div>';
					echo '<div class="checkCol">';
				}
				if(!$disabled){
					echo '<input type="checkbox" name="terms[]" data-title="'.$termName.'" data-vocabID="'.$term->commrid.'" value="'.$termID.'" id="chk'.$termID.$random.'" class="chk'.$termID.'" '.$disabled.'>';
				}else{
					//echo '<img class="denied" src="/img/denied.png" alt="Not available for request." title="Not available for request.">';
				}

				echo $classificationIcon.
					'    <label for="chk'.$termID.$random.'">'.$termName.'</label><div onmouseover="showTermDef(this)" onmouseout="hideTermDef()" data-definition="'.$termDef.'" class="info"><img src="/img/iconInfo.png"></div>';
				if($itemCount%2==0){
					echo '<br/>';
				}
				$itemCount++;
			}
				
		}
		echo '</div>';
		exit;
	}

	public function getTermDefinition() {
		$vocabRID= $this->request->query['vocabRid'];
		$searchInput= html_entity_decode($this->request->query['searchInput']);
		$searchInput = addslashes($searchInput);

		$this->loadModel('CollibraAPI');
		$objCollibra = new CollibraAPI();
		$resp = $objCollibra->request(
			array('url'=>'term/'.$vocabRID)
		);
		$jsonResp = json_decode($resp);
		$def = '';
		foreach($jsonResp->attributeReferences->attributeReference as $attr){
			if($attr->labelReference->signifier == 'Definition'){
				$def = $attr->value;
				break;
			}
		}

		// highlight each search term
		$def = strip_tags($def, '<p><span><div><ul><li>');
		$wrapBefore = '<span class="highlight">';
		$wrapAfter  = '</span>';
		
		$searchInput = trim($searchInput, '\"');
		$arrQuery = explode(" ", $searchInput);
		for($i=0; $i<sizeof($arrQuery); $i++){
			$def = preg_replace("/\b(".$arrQuery[$i].")\b/i", "$wrapBefore$1$wrapAfter", $def);
		}

		echo $def;
		exit;
	}
	
	public function autoCompleteTerm() {
		$query= $this->request->query['q'];
		if($query!=''){
			$obj = new SearchController();
			$this->loadModel('CollibraAPI');
			$objCollibra = new CollibraAPI();

			// create JSON request string
			$request = '{"query": "'.$query.'*", "filter": { "community": ["'.Configure::read('byuCommunity').'"], "category": ["TE"], "vocabulary": [], "type": { "asset":["00000000-0000-0000-0000-000000011001","ed82f17f-c1e7-4d6d-83cc-50f6b529c296"], "domain":[] },';
			if(!Configure::read('allowUnapprovedeTerms')){
				$request .= '"status": ["00000000-0000-0000-0000-000000005009"], ';
			}
			$request .= '"includeMeta": true }, "fields": ["name"], "order": { "by": "score", "sort": "desc" }, "limit": 5, "offset": 0, "highlight": false, "relativeUrl": true, "withParents": true }';
			
			$resp = $objCollibra->request(
				array(
						'url'=>'search',
						'post'=>true,
						'json'=>true,
						'params'=>$request
					)
			);
			$jsonResp = json_decode($resp);
			for($i=0; $i<sizeof($jsonResp->results); $i++){
				$requestable = true;
				// don't show non-requestable items
				foreach($jsonResp->results[$i]->attributes as $attr){
					if($attr->type == 'Requestable' && $attr->val == 'false'){
						$requestable = false;
						break;
					}
				}
				if($requestable){
					echo '<li>'.$jsonResp->results[$i]->name->val.'</li>';
				}
			}
		}		
		exit;
	}
	
	public function getCommonSearches(){
		$this->loadModel('CmsPage');
		$commonSearches = array();
		$results = $this->CmsPage->query("SELECT query, COUNT(*) total FROM common_searches GROUP BY query ORDER BY COUNT(*) DESC LIMIT 0,4");
		foreach($results as $result){
			array_push($commonSearches, ucfirst($result['common_searches']['query']));
		}
		return $commonSearches;
	}
	/////////////////////////////////////////////////////////
	
	private function getTermDetails($query){
		$arrResp = '';
		$obj = new SearchController();
		$obj->loadModel('CollibraAPI');
		$objCollibra = new CollibraAPI();

		// get all communities to use for bread crumbs in results page
		$resp = $objCollibra->request(
			array('url'=>'community/all')
		);
		$jsonAllCommunities = json_decode($resp);
		
		$requestFilter = '{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"createdOn"}},{"Column":{"fieldName":"Attr0d798f70b3ca4af2b28354f84c4714aarid"}},{"Column":{"fieldName":"Attr0d798f70b3ca4af2b28354f84c4714aa","fieldId":"0d798f70-b3ca-4af2-b283-54f84c4714aa"}},{"Column":{"fieldName":"lastModified"}},{"Column":{"fieldName":"termrid"}},{"Column":{"fieldName":"termsignifier"}},{"Column":{"fieldId":"e0937764-544a-4d21-98ce-dc0c1936b465", "fieldName":"Attre0937764544a4d2198cedc0c1936b465"}},{"Column":{"fieldName":"Attre0937764544a4d2198cedc0c1936b465rid"}},{"Column":{"fieldId":"00000000-0000-0000-0000-000000000202","fieldName":"Attr00000000000000000000000000000202"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202longExpr"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202rid"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldId":"00000000-0000-0000-0000-000000005016","fieldName":"userRole00000000000000000000000000005016rid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole00000000000000000000000000005016gender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole00000000000000000000000000005016fn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole00000000000000000000000000005016ln"}}],"name":"Role00000000000000000000000000005016"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole00000000000000000000000000005016grid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole00000000000000000000000000005016ggn"}}],"name":"Role00000000000000000000000000005016g"}},{"Column":{"fieldName":"statusname"}},{"Column":{"fieldName":"statusrid"}},{"Column":{"fieldName":"communityname"}},{"Column":{"fieldName":"commrid"}},{"Column":{"fieldName":"domainname"}},{"Column":{"fieldName":"domainrid"}},{"Column":{"fieldName":"concepttypename"}},{"Column":{"fieldName":"concepttyperid"}}, {"Group":{"name":"synonym_for","Columns":[{"Column":{"fieldName":"Relc06ed0b7032f4d0fae405824c12f94a6T","fieldId":"c06ed0b7-032f-4d0f-ae40-5824c12f94a6T","label":"Business Term"}},{"Column":{"fieldName":"relRelc06ed0b7032f4d0fae405824c12f94a6Trid","label":"Business Term ID"}},{"Column":{"fieldName":"Relc06ed0b7032f4d0fae405824c12f94a6Trid","label":"Business Term Business Term ID"}}]}}],'.
			'"Resources":{"Term":{"CreatedOn":{"name":"createdOn"},"LastModified":{"name":"lastModified"},"Id":{"name":"termrid"},"BooleanAttribute":[{"Id":{"name":"Attr0d798f70b3ca4af2b28354f84c4714aarid"},"labelId":"0d798f70-b3ca-4af2-b283-54f84c4714aa","Value":{"name":"Attr0d798f70b3ca4af2b28354f84c4714aa"}}], "SingleValueListAttribute":[{"Value":{"name":"Attre0937764544a4d2198cedc0c1936b465"}, "Id":{"name":"Attre0937764544a4d2198cedc0c1936b465rid"}, "labelId":"e0937764-544a-4d21-98ce-dc0c1936b465"}],"Signifier":{"name":"termsignifier"},"StringAttribute":[{"Value":{"name":"Attr00000000000000000000000000000202"},"LongExpression":{"name":"Attr00000000000000000000000000000202longExpr"},"Id":{"name":"Attr00000000000000000000000000000202rid"},"labelId":"00000000-0000-0000-0000-000000000202"}],"Member":[{"User":{"Gender":{"name":"userRole00000000000000000000000000005016gender"},"FirstName":{"name":"userRole00000000000000000000000000005016fn"},"Id":{"name":"userRole00000000000000000000000000005016rid"},"LastName":{"name":"userRole00000000000000000000000000005016ln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016sig"},"name":"Role00000000000000000000000000005016","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016rid"}},"roleId":"00000000-0000-0000-0000-000000005016"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016g"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016grid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005016ggn"},"Id":{"name":"groupRole00000000000000000000000000005016grid"}},"roleId":"00000000-0000-0000-0000-000000005016"}],"Status":{"Signifier":{"name":"statusname"},"Id":{"name":"statusrid"}},"Vocabulary":{"Community":{"Name":{"name":"communityname"},"Id":{"name":"commrid"}},"Name":{"name":"domainname"},"Id":{"name":"domainrid"}},"ConceptType":[{"Signifier":{"name":"concepttypename"},"Id":{"name":"concepttyperid"}}],"Relation":[{"typeId":"c06ed0b7-032f-4d0f-ae40-5824c12f94a6","type":"TARGET","Id":{"name":"relRelc06ed0b7032f4d0fae405824c12f94a6Trid"},"Source":{"Id":{"name":"Relc06ed0b7032f4d0fae405824c12f94a6Trid"},"Signifier":{"name":"Relc06ed0b7032f4d0fae405824c12f94a6T"}}}],'.
			'"Filter":{'.
			'   "AND":['.
			'        {'.
			'           "OR":['.
			'              {'.
			'                 "Field":{'.
			'                    "name":"termrid",'.
			'                    "operator":"EQUALS",'.
			'                    "value":"'.$query.'"'.
			'                 }'.
			'              }'.
			'           ]'.
			'        }';
		if(!Configure::read('allowUnapprovedeTerms')){
			$requestFilter .= '        ,'.
				'        {'.
				'           "AND":[{"Field":{"name":"statusname","operator":"EQUALS","value":"Accepted"}}]'.
				'        }';
		}
		$requestFilter .= '     ]'.
			'}'.
			',"Order":['.
			'   {"Field":{"name":"termsignifier","order":"ASC"}}'.
			']'.
			'}'.
			'},"displayStart":0,"displayLength":10}}';
		
		$resp = $objCollibra->request(
			array(
				'url'=>'output/data_table',
				//'url'=>'output/csv-raw',
				'post'=>true,
				'json'=>true,
				'params'=>$requestFilter
			)
		);
		$resp = json_decode($resp);

		// loop through terms to check for quick links and also to build domain breadcrumb
		if(sizeof($resp->aaData)>0){
			$term = $resp->aaData;
			for($i=0; $i<sizeof($term); $i++){
				// add parent community names to breadcrumb
				$fullCommunityName = '';
				for($j=0; $j<sizeof($jsonAllCommunities->communityReference); $j++){
					$parentObj = $jsonAllCommunities->communityReference[$j];
					if($parentObj->resourceId == $term[$i]->commrid){
						while(isset($parentObj->parentReference)){
							$parentObj = $parentObj->parentReference;
							if($parentObj->name != "BYU"){
								$fullCommunityName = $parentObj->name.' <span class="arrow-separator">&gt;</span> '.$fullCommunityName;
							}
						}
					}
				}
				$fullCommunityName .= $term[$i]->communityname;
				$term[$i]->communityname = $fullCommunityName;

				// check to see if this terms is stored in user's quick links cookie
				if(isset($_COOKIE['QL'])){
					$term[$i]->saved = '0';
					$arrQl = unserialize($_COOKIE['QL']);
					for($j=0; $j<sizeof($arrQl); $j++){
						if($arrQl[$j][1] == $term[$i]->termrid){
							$term[$i]->saved = '1';
							break;
						}
					}
				}
			}
		}

		return $resp;
	}
	
	private function searchTerms($query, $page=0, $displayLength=20, $sortField='termsignifier', $sortOrder='ASC', $communityFilter='', $termOnly=false){
		$query = addslashes($query);
		if(substr($query, 0, 2)!='\"' && substr($query, strlen($query)-2, 2)!='\"' && substr($query, strlen($query)-1, 1)!='*'){
			$query .= '*';	
		}

		$displayStart = $page*$displayLength;
		
		$obj = new SearchController();
		$obj->loadModel('CollibraAPI');
		$objCollibra = new CollibraAPI();

		// use API search call to query based on user input
		///////////////////////////////////
		// set filter to community selected or the "BYU" community by default
		if($communityFilter == ''){
			$communityFilter = Configure::read('byuCommunity');
		}
		// call search API limiting results to business term and synonym types
		$request = '{"query":"'.$query.'", "filter": { "community": ["'.$communityFilter.'"], "category":["TE"], "vocabulary":[], "type":{"asset":["00000000-0000-0000-0000-000000011001","ed82f17f-c1e7-4d6d-83cc-50f6b529c296"]},';
		if(!Configure::read('allowUnapprovedeTerms')){
			$request .= '"status": ["00000000-0000-0000-0000-000000005009"], ';
		}
		$request .= '"includeMeta":false}, "fields":["name","attributes"], "order":{"by":"score","sort": "desc"}, "limit":200, "offset":0, "highlight":false}';
		//die($request);
		$searchResp = $objCollibra->request(
			array('url'=>'search', 'post'=>true, 'json'=>true, 'params'=>$request)
		);
		$searchResp = json_decode($searchResp);

		// build filter string of term ID used to full detail search below
		$ridFilter = '';
		foreach($searchResp->results as $result){
			$ridFilter .= '{"Field":{"name":"termrid","operator":"EQUALS","value":"'.$result->name->id.'"}},';
		}
		if($ridFilter!='') $ridFilter = substr($ridFilter, 0, strlen($ridFilter)-1);
		//die($ridFilter);
 		///////////////////////////////////

		$requestFilter = '{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"lastModified"}},{"Column":{"fieldName":"createdOn"}},{"Column":{"fieldName":"Attr0d798f70b3ca4af2b28354f84c4714aarid"}},{"Column":{"fieldName":"Attr0d798f70b3ca4af2b28354f84c4714aa","fieldId":"0d798f70-b3ca-4af2-b283-54f84c4714aa"}},{"Column":{"fieldName":"termrid"}},{"Column":{"fieldName":"termsignifier"}},{"Column":{"fieldId":"00000000-0000-0000-0000-000000000202","fieldName":"Attr00000000000000000000000000000202"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202longExpr"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202rid"}},{"Column":{"fieldId":"e0937764-544a-4d21-98ce-dc0c1936b465","fieldName":"Attre0937764544a4d2198cedc0c1936b465"}},{"Column":{"fieldName":"Attre0937764544a4d2198cedc0c1936b465rid"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldId":"00000000-0000-0000-0000-000000005016","fieldName":"userRole00000000000000000000000000005016rid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole00000000000000000000000000005016gender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole00000000000000000000000000005016fn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole00000000000000000000000000005016ln"}}],"name":"Role00000000000000000000000000005016"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole00000000000000000000000000005016grid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole00000000000000000000000000005016ggn"}}],"name":"Role00000000000000000000000000005016g"}},{"Column":{"fieldName":"statusname"}},{"Column":{"fieldName":"statusrid"}},{"Column":{"fieldName":"communityname"}},{"Column":{"fieldName":"commrid"}},{"Column":{"fieldName":"domainname"}},{"Column":{"fieldName":"domainrid"}},{"Column":{"fieldName":"concepttypename"}},{"Column":{"fieldName":"concepttyperid"}}, {"Group":{"name":"synonym_for","Columns":[{"Column":{"fieldName":"Relc06ed0b7032f4d0fae405824c12f94a6T","fieldId":"c06ed0b7-032f-4d0f-ae40-5824c12f94a6T","label":"Business Term"}},{"Column":{"fieldName":"relRelc06ed0b7032f4d0fae405824c12f94a6Trid","label":"Business Term ID"}},{"Column":{"fieldName":"Relc06ed0b7032f4d0fae405824c12f94a6Trid","label":"Business Term Business Term ID"}}]}}],'.
			'"Resources":{"Term":{"CreatedOn":{"name":"createdOn"},"LastModified":{"name":"lastModified"},"Id":{"name":"termrid"},"BooleanAttribute":[{"Id":{"name":"Attr0d798f70b3ca4af2b28354f84c4714aarid"},"labelId":"0d798f70-b3ca-4af2-b283-54f84c4714aa","Value":{"name":"Attr0d798f70b3ca4af2b28354f84c4714aa"}}],"Signifier":{"name":"termsignifier"},"StringAttribute":[{"Value":{"name":"Attr00000000000000000000000000000202"},"LongExpression":{"name":"Attr00000000000000000000000000000202longExpr"},"Id":{"name":"Attr00000000000000000000000000000202rid"},"labelId":"00000000-0000-0000-0000-000000000202"}],"Member":[{"User":{"Gender":{"name":"userRole00000000000000000000000000005016gender"},"FirstName":{"name":"userRole00000000000000000000000000005016fn"},"Id":{"name":"userRole00000000000000000000000000005016rid"},"LastName":{"name":"userRole00000000000000000000000000005016ln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016sig"},"name":"Role00000000000000000000000000005016","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016rid"}},"roleId":"00000000-0000-0000-0000-000000005016"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016g"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016grid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005016ggn"},"Id":{"name":"groupRole00000000000000000000000000005016grid"}},"roleId":"00000000-0000-0000-0000-000000005016"}],"SingleValueListAttribute":[{"Value":{"name":"Attre0937764544a4d2198cedc0c1936b465"},"Id":{"name":"Attre0937764544a4d2198cedc0c1936b465rid"},"labelId":"e0937764-544a-4d21-98ce-dc0c1936b465"}],"Status":{"Signifier":{"name":"statusname"},"Id":{"name":"statusrid"}},"Vocabulary":{"Community":{"Name":{"name":"communityname"},"Id":{"name":"commrid"}},"Name":{"name":"domainname"},"Id":{"name":"domainrid"}},"ConceptType":[{"Signifier":{"name":"concepttypename"},"Id":{"name":"concepttyperid"}}],"Relation":[{"typeId":"c06ed0b7-032f-4d0f-ae40-5824c12f94a6","type":"TARGET","Id":{"name":"relRelc06ed0b7032f4d0fae405824c12f94a6Trid"},"Source":{"Id":{"name":"Relc06ed0b7032f4d0fae405824c12f94a6Trid"},"Signifier":{"name":"Relc06ed0b7032f4d0fae405824c12f94a6T"}}}],'.
			'"Filter":{'.
			'   "AND":['.
			'		  {'.
			'			 "OR":['.
			'				{  '.
			'				   "Field":{  '.
			'					  "name":"concepttypename",'.
			'					  "operator":"INCLUDES",'.
			'					  "value":"Business Term"'.
			'				   }'.
			'				},'.
			'				{  '.
			'				   "Field":{  '.
			'					  "name":"concepttypename",'.
			'					  "operator":"INCLUDES",'.
			'					  "value":"Synonym"'.
			'				   }'.
			'				}'.
			'			 ]'.
			'       },'.
			'		{'.
			'			"OR":['.$ridFilter.']'.
			'		}';

		if(!Configure::read('allowUnrequestableTerms')){
			$requestFilter .= ',{  '.
			'	   "OR":[  '.
			'		  {  '.
			'			 "Field":{  '.
			'				"name":"Attr0d798f70b3ca4af2b28354f84c4714aa",'.
			'				"operator":"NULL"'.
			'			 }'.
			'		  },'.
			'		  {  '.
			'			 "Field":{  '.
			'				"name":"Attr0d798f70b3ca4af2b28354f84c4714aa",'.
			'				"operator":"EQUALS",'.
			'				"value":true'.
			'			 }'.
			'		  }'.
			'	   ]'.
			'	}';
		}

		if(!Configure::read('allowUnapprovedeTerms')){
			$requestFilter .= ',{"AND":[{"Field":{"name":"statusname","operator":"EQUALS","value":"Accepted"}}]}';
		}

		$requestFilter .= ']'.
			'}'.
			',"Order":[';

		// set sort if not sorting by score
		if($sortField != 'score'){
			$requestFilter .= '{"Field":{"name":"'.$sortField.'","order":"'.$sortOrder.'"}}';
		}

		// set paging
		$displayStart = 0;
		$displayLength = 100;
		$requestFilter .= ']}},"displayStart":'.$displayStart.',"displayLength":'.$displayLength.'}}';

		$resp = $objCollibra->request(
			array(
				'url'=>'output/data_table',
				//'url'=>'output/csv-raw',
				'post'=>true,
				'json'=>true,
				'params'=>$requestFilter
			)
		);
		$resp = json_decode($resp);

		//order results based on first search
		if($sortField == 'score' && sizeof($resp->aaData)>0){
			$arrTmpTerms = array();
			foreach($searchResp->results as $result){
				foreach($resp->aaData as $term){
					if($term->termrid == $result->name->id){
						array_push($arrTmpTerms, $term);
						break;
					}
				}
			}
			$resp->aaData = $arrTmpTerms;
		}

		// get all communities to use for search filtering and bread crumbs in results page
		$communityResp = $objCollibra->request(
			array('url'=>'community/all')
		);
		$jsonAllCommunities = json_decode($communityResp);
		
		// loop through terms to check for quick links and also to build domain breadcrumb
		if(is_array($resp->aaData)){
			$term = $resp->aaData;
			for($i=0; $i<sizeof($term); $i++){
				// add parent community names to breadcrumb
				$fullCommunityName = '';
				for($j=0; $j<sizeof($jsonAllCommunities->communityReference); $j++){
					$parentObj = $jsonAllCommunities->communityReference[$j];
					if($parentObj->resourceId == $term[$i]->commrid){
						while(isset($parentObj->parentReference)){
							$parentObj = $parentObj->parentReference;
							if($parentObj->name != "BYU"){
								$fullCommunityName = $parentObj->name.' <span class="arrow-separator">&gt;</span> '.$fullCommunityName;
							}
						}
					}
				}
				$fullCommunityName .= $term[$i]->communityname;
				$term[$i]->communityname = $fullCommunityName;

				// check to see if this terms is stored in user's quick links cookie
				if(isset($_COOKIE['QL'])){
					$term[$i]->saved = '0';
					$arrQl = unserialize($_COOKIE['QL']);
					for($j=0; $j<sizeof($arrQl); $j++){
						if($arrQl[$j][1] == $term[$i]->termrid){
							$term[$i]->saved = '1';
							break;
						}
					}
				}
			}
		}

		//print_r($resp);exit;
		return $resp;
	}
	
	private function getDomainTerms($domainFilter='', $page=0, $displayLength=20, $sortField='termsignifier', $sortOrder='ASC'){
		$arrResp = '';
		$displayStart = $page*$displayLength;
		
		$obj = new SearchController();
		$obj->loadModel('CollibraAPI');
		$objCollibra = new CollibraAPI();

		// get all communities to use for bread crumbs in results page
		$resp = $objCollibra->request(
			array('url'=>'community/all')
		);
		$jsonAllCommunities = json_decode($resp);
		
		
		$requestFilter = '{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"createdOn"}},{"Column":{"fieldName":"lastModified"}},{"Column":{"fieldName":"Attr0d798f70b3ca4af2b28354f84c4714aarid"}},{"Column":{"fieldName":"Attr0d798f70b3ca4af2b28354f84c4714aa","fieldId":"0d798f70-b3ca-4af2-b283-54f84c4714aa"}},{"Column":{"fieldName":"termrid"}},{"Column":{"fieldName":"termsignifier"}},{"Column":{"fieldId":"e0937764-544a-4d21-98ce-dc0c1936b465", "fieldName":"Attre0937764544a4d2198cedc0c1936b465"}},{"Column":{"fieldName":"Attre0937764544a4d2198cedc0c1936b465rid"}},{"Column":{"fieldId":"00000000-0000-0000-0000-000000000202","fieldName":"Attr00000000000000000000000000000202"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202longExpr"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202rid"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldId":"00000000-0000-0000-0000-000000005016","fieldName":"userRole00000000000000000000000000005016rid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole00000000000000000000000000005016gender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole00000000000000000000000000005016fn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole00000000000000000000000000005016ln"}}],"name":"Role00000000000000000000000000005016"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole00000000000000000000000000005016grid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole00000000000000000000000000005016ggn"}}],"name":"Role00000000000000000000000000005016g"}},{"Column":{"fieldName":"statusname"}},{"Column":{"fieldName":"statusrid"}},{"Column":{"fieldName":"communityname"}},{"Column":{"fieldName":"commrid"}},{"Column":{"fieldName":"domainname"}},{"Column":{"fieldName":"domainrid"}},{"Column":{"fieldName":"concepttypename"}},{"Column":{"fieldName":"concepttyperid"}},{"Group":{"name":"synonym_for","Columns":[{"Column":{"fieldName":"Relc06ed0b7032f4d0fae405824c12f94a6T","fieldId":"c06ed0b7-032f-4d0f-ae40-5824c12f94a6T","label":"Business Term"}},{"Column":{"fieldName":"relRelc06ed0b7032f4d0fae405824c12f94a6Trid","label":"Business Term ID"}},{"Column":{"fieldName":"Relc06ed0b7032f4d0fae405824c12f94a6Trid","label":"Business Term Business Term ID"}}]}}],'.
			'"Resources":{"Term":{"CreatedOn":{"name":"createdOn"},"LastModified":{"name":"lastModified"},"Id":{"name":"termrid"},"BooleanAttribute":[{"Id":{"name":"Attr0d798f70b3ca4af2b28354f84c4714aarid"},"labelId":"0d798f70-b3ca-4af2-b283-54f84c4714aa","Value":{"name":"Attr0d798f70b3ca4af2b28354f84c4714aa"}}],"Signifier":{"name":"termsignifier"},"StringAttribute":[{"Value":{"name":"Attr00000000000000000000000000000202"},"LongExpression":{"name":"Attr00000000000000000000000000000202longExpr"},"Id":{"name":"Attr00000000000000000000000000000202rid"},"labelId":"00000000-0000-0000-0000-000000000202"}],"Member":[{"User":{"Gender":{"name":"userRole00000000000000000000000000005016gender"},"FirstName":{"name":"userRole00000000000000000000000000005016fn"},"Id":{"name":"userRole00000000000000000000000000005016rid"},"LastName":{"name":"userRole00000000000000000000000000005016ln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016sig"},"name":"Role00000000000000000000000000005016","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016rid"}},"roleId":"00000000-0000-0000-0000-000000005016"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016g"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016grid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005016ggn"},"Id":{"name":"groupRole00000000000000000000000000005016grid"}},"roleId":"00000000-0000-0000-0000-000000005016"}], "SingleValueListAttribute":[{"Value":{"name":"Attre0937764544a4d2198cedc0c1936b465"}, "Id":{"name":"Attre0937764544a4d2198cedc0c1936b465rid"}, "labelId":"e0937764-544a-4d21-98ce-dc0c1936b465"}],"Status":{"Signifier":{"name":"statusname"},"Id":{"name":"statusrid"}},"Vocabulary":{"Community":{"Name":{"name":"communityname"},"Id":{"name":"commrid"}},"Name":{"name":"domainname"},"Id":{"name":"domainrid"}},"ConceptType":[{"Signifier":{"name":"concepttypename"},"Id":{"name":"concepttyperid"}}],"Relation":[{"typeId":"c06ed0b7-032f-4d0f-ae40-5824c12f94a6","type":"TARGET","Id":{"name":"relRelc06ed0b7032f4d0fae405824c12f94a6Trid"},"Source":{"Id":{"name":"Relc06ed0b7032f4d0fae405824c12f94a6Trid"},"Signifier":{"name":"Relc06ed0b7032f4d0fae405824c12f94a6T"}}}],'.
		
		/*$requestFilter = '{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"createdOn"}},{"Column":{"fieldName":"lastModified"}},{"Column":{"fieldName":"termrid"}},{"Column":{"fieldName":"termsignifier"}},{"Column":{"fieldId":"e0937764-544a-4d21-98ce-dc0c1936b465", "fieldName":"Attre0937764544a4d2198cedc0c1936b465"}},{"Column":{"fieldName":"Attre0937764544a4d2198cedc0c1936b465rid"}},{"Column":{"fieldId":"00000000-0000-0000-0000-000000000202","fieldName":"Attr00000000000000000000000000000202"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202longExpr"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202rid"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldId":"00000000-0000-0000-0000-000000005016","fieldName":"userRole00000000000000000000000000005016rid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole00000000000000000000000000005016gender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole00000000000000000000000000005016fn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole00000000000000000000000000005016ln"}}],"name":"Role00000000000000000000000000005016"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole00000000000000000000000000005016grid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole00000000000000000000000000005016ggn"}}],"name":"Role00000000000000000000000000005016g"}},{"Column":{"fieldName":"statusname"}},{"Column":{"fieldName":"statusrid"}},{"Column":{"fieldName":"communityname"}},{"Column":{"fieldName":"commrid"}},{"Column":{"fieldName":"domainname"}},{"Column":{"fieldName":"domainrid"}},{"Column":{"fieldName":"concepttypename"}},{"Column":{"fieldName":"concepttyperid"}}],'.
			'"Resources":{"Term":{"CreatedOn":{"name":"createdOn"},"LastModified":{"name":"lastModified"},"Id":{"name":"termrid"},"Signifier":{"name":"termsignifier"},"StringAttribute":[{"Value":{"name":"Attr00000000000000000000000000000202"},"LongExpression":{"name":"Attr00000000000000000000000000000202longExpr"},"Id":{"name":"Attr00000000000000000000000000000202rid"},"labelId":"00000000-0000-0000-0000-000000000202"}],"Member":[{"User":{"Gender":{"name":"userRole00000000000000000000000000005016gender"},"FirstName":{"name":"userRole00000000000000000000000000005016fn"},"Id":{"name":"userRole00000000000000000000000000005016rid"},"LastName":{"name":"userRole00000000000000000000000000005016ln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016sig"},"name":"Role00000000000000000000000000005016","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016rid"}},"roleId":"00000000-0000-0000-0000-000000005016"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016g"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016grid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005016ggn"},"Id":{"name":"groupRole00000000000000000000000000005016grid"}},"roleId":"00000000-0000-0000-0000-000000005016"}], "SingleValueListAttribute":[{"Value":{"name":"Attre0937764544a4d2198cedc0c1936b465"}, "Id":{"name":"Attre0937764544a4d2198cedc0c1936b465rid"}, "labelId":"e0937764-544a-4d21-98ce-dc0c1936b465"}],"Status":{"Signifier":{"name":"statusname"},"Id":{"name":"statusrid"}},"Vocabulary":{"Community":{"Name":{"name":"communityname"},"Id":{"name":"commrid"}},"Name":{"name":"domainname"},"Id":{"name":"domainrid"}},"ConceptType":[{"Signifier":{"name":"concepttypename"},"Id":{"name":"concepttyperid"}}],'.
		*/	'"Filter":{'.
			'   "AND":['.
			'		  {'.
			'			 "OR":['.
			'				{  '.
			'				   "Field":{  '.
			'					  "name":"concepttypename",'.
			'					  "operator":"INCLUDES",'.
			'					  "value":"Business Term"'.
			'				   }'.
			'				},'.
			'				{  '.
			'				   "Field":{  '.
			'					  "name":"concepttypename",'.
			'					  "operator":"INCLUDES",'.
			'					  "value":"Synonym"'.
			'				   }'.
			'				}'.
			'			 ]'.
			'       },'.
			'        {'.
			'           "AND":['.
			'               {"Field":{"name":"domainrid","operator":"EQUALS","value":"'.$domainFilter.'"}}'.
			'           ]'.
			'        }';
		if(!Configure::read('allowUnapprovedeTerms')){
			$requestFilter .= '        ,'.
				'        {'.
				'           "AND":[{"Field":{"name":"statusname","operator":"EQUALS","value":"Accepted"}}]'.
				'        }';
		}
		$requestFilter .=''.
			'     ]'.
			'}'.
			',"Order":[';

		// set sort options
		$requestFilter .= '{"Field":{"name":"'.$sortField.'","order":"'.$sortOrder.'"}}';
		// set paging options
		$requestFilter .= ']}},"displayStart":'.$displayStart.',"displayLength":'.$displayLength.'}}';

		$resp = $objCollibra->request(
			array(
				'url'=>'output/data_table',
				//'url'=>'output/csv-raw',
				'post'=>true,
				'json'=>true,
				'params'=>$requestFilter
			)
		);
		$resp = json_decode($resp);
		
		// loop through terms to check for quick links and also to build domain breadcrumb
		if(sizeof($resp->aaData)>0){
			$term = $resp->aaData;
			for($i=0; $i<sizeof($term); $i++){
				// add terms reference for synonym terms
				/*if(sizeof($term[$i]->synonym_for)!=0){
					$synonymRid = $term[$i]->synonym_for[0]->Relc06ed0b7032f4d0fae405824c12f94a6Trid;
					$term[$i]->synonym_for[0]->definition = $synonymRid.'--'.$this->getTermDefinition($synonymRid);
				}*/

				// add parent community names to breadcrumb
				$fullCommunityName = '';
				for($j=0; $j<sizeof($jsonAllCommunities->communityReference); $j++){
					$parentObj = $jsonAllCommunities->communityReference[$j];
					if($parentObj->resourceId == $term[$i]->commrid){
						while(isset($parentObj->parentReference)){
							$parentObj = $parentObj->parentReference;
							if($parentObj->name != "BYU"){
								$fullCommunityName = $parentObj->name.' <span class="arrow-separator">&gt;</span> '.$fullCommunityName;
							}
						}
					}
				}
				$fullCommunityName .= $term[$i]->communityname;
				$term[$i]->communityname = $fullCommunityName;

				// check to see if this terms is stored in user's quick links cookie
				if(isset($_COOKIE['QL'])){
					$term[$i]->saved = '0';
					$arrQl = unserialize($_COOKIE['QL']);
					for($j=0; $j<sizeof($arrQl); $j++){
						if($arrQl[$j][1] == $term[$i]->termrid){
							$term[$i]->saved = '1';
							break;
						}
					}
				}
			}
		}
		return $resp;
	}
}