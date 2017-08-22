<?php

class SearchController extends AppController {
	public $uses = array('CollibraAPI', 'CmsPage');

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

	public function noGlossary() {

	}

	public function term($query = null) {
		if (empty($query)) {
			return $this->redirect(['action' => 'index']);
		}
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

		$page = 0;
		if(isset($this->request->params['pass'][1])){
			$page = intval($this->request->params['pass'][1]);
		}
		if($page==0) $page=1;

		$domainID = $this->request->params['pass'][0];
		$terms = $this->getDomainTerms($domainID, $page-1);

		$this->set('commonSearches', $this->getCommonSearches());
		//$this->set('totalPages', ceil($terms->iTotalDisplayRecords/25));
		//$this->set('pageNum', $page);
		$this->set('terms', $terms);
		$this->set('searchInput', '');
		$this->set('domain', $domainID);

		$this->render('results');
	}

	public function results() {
		$query = htmlentities($this->request->params['pass'][0]);
		$defaultCommunity = Configure::read('Collibra.community.byu');

		// set community filter based on querystring value
		///////////////////////////////////////////////////////
		$filter = '';
		if(isset($this->request->query['f']) && $this->request->query['f'] != ''){
			$filter = $this->request->query['f'];
		}
		///////////////////////////////////////////////////////

		// set sort filter based on querystring value
		///////////////////////////////////////////////////////
		$sort = isset($this->request->query['s'])?$this->request->query['s']:0;
		$sortOrder = 'DESC';
		$sortField = null;
		switch($sort){
			case 0:
				$sortField = 'score';
				break;
			case 1:
				$sortField = 'termsignifier';
				$sortOrder = 'ASC';
				break;
			case 2:
				$sortField = 'lastModified';
				break;
			case 3:
				$sortField = 'classification';
				$sortOrder = 'ASC';
				break;
		}
		///////////////////////////////////////////////////////

		$page = 0;
		if(isset($this->request->params['pass'][1])){
			$page = intval($this->request->params['pass'][1]);
		}
		if($page==0) $page=1;
		//$query = str_replace('%2B', 'ss', $this->request->params['pass'][0]);

		// get all terms matching query
		$terms = $this->searchTerms(html_entity_decode($query), $page-1, 10, $sortField, $sortOrder, $filter);
		//print_r($terms);exit;

		if(!empty($terms->aaData)){
			// save search and delete anything over 300 entries
			$this->loadModel('CommonSearch');
			// delete last record
			$results = $this->CommonSearch->find('all', ['order' => 'id']);
			if(count($results)>=300){
				$this->CommonSearch->delete($results[0]['CommonSearch']['id']);
			}
			// add new record
			$this->CommonSearch->save(['query' => $query]);

			//Highlight search terms in description
			$searchInput = trim(html_entity_decode($query), ' \"');
			$arrQuery = explode(" ", $searchInput);
			$wrapBefore = '<span class="highlight">';
			$wrapAfter  = '</span>';
			foreach ($terms->aaData as &$term) {
				if (empty($term->description)) {
					continue;
				}
				$def = strip_tags($term->description, '<p><span><div><ul><li>');
				foreach ($arrQuery as $text){
					$def = preg_replace("/\b({$text})\b/i", "{$wrapBefore}\$1{$wrapAfter}", $def);
				}

				$term->description = $def;
			}
		}
		///////////////////////////////////////////////////////

		// get all sub communities for Data Governance Council
		// to be used in the search filter drop down
		///////////////////////////////////////////////////////
		$resp = $this->CollibraAPI->get('community/'.$defaultCommunity.'/sub-communities');
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
		if(!$community) $community = Configure::read('Collibra.community.byu');
		$this->autoRender = false;

		if ($this->request->is('post')) {
			if(isset($this->request->data['c']) && $this->request->data['c'] != ''){
				$community = $this->request->data['c'];
			}
		}

		if(!Configure::read('allowUnapprovedTerms')){
		}
		$resp = $this->CollibraAPI->postJSON(
				'output/data_table',
				'{"TableViewConfig":{"Columns":[{"Community":{"name":"Subcommunities","Columns":[{"Column":{"fieldName":"subcommunityid"}},{"Column":{"fieldName":"subcommunity"}},{"Column":{"fieldName":"hasNonMetaChildren"}},{"Group":{"Columns":[{"Column":{"label":"Admin User ID","fieldName":"userRole00000000000000000000000000005015rid"}},{"Column":{"label":"Admin Gender","fieldName":"userRole00000000000000000000000000005015gender"}},{"Column":{"label":"Admin First Name","fieldName":"userRole00000000000000000000000000005015fn"}},{"Column":{"label":"Admin Last Name","fieldName":"userRole00000000000000000000000000005015ln"}}],"name":"Role00000000000000000000000000005015"}},{"Group":{"Columns":[{"Column":{"label":"Admin Group ID","fieldName":"groupRole00000000000000000000000000005015grid"}},{"Column":{"label":"Admin Group Name","fieldName":"groupRole00000000000000000000000000005015ggn"}}],"name":"Role00000000000000000000000000005015g"}},{"Group":{"Columns":[{"Column":{"label":"Collibra Steward User ID","fieldName":"userRole00000000000000000000000000005016rid"}},{"Column":{"label":"Collibra Steward Gender","fieldName":"userRole00000000000000000000000000005016gender"}},{"Column":{"label":"Collibra Steward First Name","fieldName":"userRole00000000000000000000000000005016fn"}},{"Column":{"label":"Collibra Steward Last Name","fieldName":"userRole00000000000000000000000000005016ln"}}],"name":"Role00000000000000000000000000005016"}},{"Group":{"Columns":[{"Column":{"label":"Collibra Steward Group ID","fieldName":"groupRole00000000000000000000000000005016grid"}},{"Column":{"label":"Collibra Steward Group Name","fieldName":"groupRole00000000000000000000000000005016ggn"}}],"name":"Role00000000000000000000000000005016g"}},{"Group":{"Columns":[{"Column":{"label":"Stakeholder User ID","fieldName":"userRole00000000000000000000000000005018rid"}},{"Column":{"label":"Stakeholder Gender","fieldName":"userRole00000000000000000000000000005018gender"}},{"Column":{"label":"Stakeholder First Name","fieldName":"userRole00000000000000000000000000005018fn"}},{"Column":{"label":"Stakeholder Last Name","fieldName":"userRole00000000000000000000000000005018ln"}}],"name":"Role00000000000000000000000000005018"}},{"Group":{"Columns":[{"Column":{"label":"Stakeholder Group ID","fieldName":"groupRole00000000000000000000000000005018grid"}},{"Column":{"label":"Stakeholder Group Name","fieldName":"groupRole00000000000000000000000000005018ggn"}}],"name":"Role00000000000000000000000000005018g"}}]}},{"Vocabulary":{"name":"Vocabularies","Columns":[{"Column":{"fieldName":"vocabulary"}},{"Column":{"fieldName":"vocabularyid"}},{"Column":{"fieldName":"domainType"}},{"Group":{"Columns":[{"Column":{"label":"Admin User ID","fieldName":"userRole00000000000000000000000000005015VOCSUFFIXrid"}},{"Column":{"label":"Admin Gender","fieldName":"userRole00000000000000000000000000005015VOCSUFFIXgender"}},{"Column":{"label":"Admin First Name","fieldName":"userRole00000000000000000000000000005015VOCSUFFIXfn"}},{"Column":{"label":"Admin Last Name","fieldName":"userRole00000000000000000000000000005015VOCSUFFIXln"}}],"name":"Role00000000000000000000000000005015VOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Admin Group ID","fieldName":"groupRole00000000000000000000000000005015gVOCSUFFIXrid"}},{"Column":{"label":"Admin Group Name","fieldName":"groupRole00000000000000000000000000005015gVOCSUFFIXgn"}}],"name":"Role00000000000000000000000000005015gVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Collibra Steward User ID","fieldName":"userRole00000000000000000000000000005016VOCSUFFIXrid"}},{"Column":{"label":"Collibra Steward Gender","fieldName":"userRole00000000000000000000000000005016VOCSUFFIXgender"}},{"Column":{"label":"Collibra Steward First Name","fieldName":"userRole00000000000000000000000000005016VOCSUFFIXfn"}},{"Column":{"label":"Collibra Steward Last Name","fieldName":"userRole00000000000000000000000000005016VOCSUFFIXln"}}],"name":"Role00000000000000000000000000005016VOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Collibra Steward Group ID","fieldName":"groupRole00000000000000000000000000005016gVOCSUFFIXrid"}},{"Column":{"label":"Collibra Steward Group Name","fieldName":"groupRole00000000000000000000000000005016gVOCSUFFIXgn"}}],"name":"Role00000000000000000000000000005016gVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Stakeholder User ID","fieldName":"userRole00000000000000000000000000005018VOCSUFFIXrid"}},{"Column":{"label":"Stakeholder Gender","fieldName":"userRole00000000000000000000000000005018VOCSUFFIXgender"}},{"Column":{"label":"Stakeholder First Name","fieldName":"userRole00000000000000000000000000005018VOCSUFFIXfn"}},{"Column":{"label":"Stakeholder Last Name","fieldName":"userRole00000000000000000000000000005018VOCSUFFIXln"}}],"name":"Role00000000000000000000000000005018VOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Stakeholder Group ID","fieldName":"groupRole00000000000000000000000000005018gVOCSUFFIXrid"}},{"Column":{"label":"Stakeholder Group Name","fieldName":"groupRole00000000000000000000000000005018gVOCSUFFIXgn"}}],"name":"Role00000000000000000000000000005018gVOCSUFFIX"}}]}}],"Resources":{"Community":{"Id":{"name":"subcommunityid"},"Name":{"name":"subcommunity"},"Meta":{"name":"issubmeta"},"hasNonMetaChildren":{"name":"hasNonMetaChildren"},"ParentCommunity":{"Id":{"name":"parentCommunityId"}},"Member":[{"User":{"Gender":{"name":"userRole00000000000000000000000000005015gender"},"FirstName":{"name":"userRole00000000000000000000000000005015fn"},"Id":{"name":"userRole00000000000000000000000000005015rid"},"LastName":{"name":"userRole00000000000000000000000000005015ln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005015sig"},"name":"Role00000000000000000000000000005015","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005015rid"}},"roleId":"00000000-0000-0000-0000-000000005015"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005015g"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005015grid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005015ggn"},"Id":{"name":"groupRole00000000000000000000000000005015grid"}},"roleId":"00000000-0000-0000-0000-000000005015"},{"User":{"Gender":{"name":"userRole00000000000000000000000000005016gender"},"FirstName":{"name":"userRole00000000000000000000000000005016fn"},"Id":{"name":"userRole00000000000000000000000000005016rid"},"LastName":{"name":"userRole00000000000000000000000000005016ln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016sig"},"name":"Role00000000000000000000000000005016","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016rid"}},"roleId":"00000000-0000-0000-0000-000000005016"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016g"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016grid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005016ggn"},"Id":{"name":"groupRole00000000000000000000000000005016grid"}},"roleId":"00000000-0000-0000-0000-000000005016"},{"User":{"Gender":{"name":"userRole00000000000000000000000000005018gender"},"FirstName":{"name":"userRole00000000000000000000000000005018fn"},"Id":{"name":"userRole00000000000000000000000000005018rid"},"LastName":{"name":"userRole00000000000000000000000000005018ln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005018sig"},"name":"Role00000000000000000000000000005018","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005018rid"}},"roleId":"00000000-0000-0000-0000-000000005018"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005018g"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005018grid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005018ggn"},"Id":{"name":"groupRole00000000000000000000000000005018grid"}},"roleId":"00000000-0000-0000-0000-000000005018"}],"Filter":{"AND":[{"AND":[{"Field":{"name":"issubmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"parentCommunityId","operator":"EQUALS","value":"'.$community.'"}}]}]},"Order":[{"Field":{"name":"subcommunity","order":"ASC"}}]},"Vocabulary":{"Name":{"name":"vocabulary"},"Id":{"name":"vocabularyid"},"Meta":{"name":"isvocmeta"},"Community":{"Id":{"name":"vocabularyParentCommunityId"}},"VocabularyType":{"Signifier":{"name":"domainType"}},"Member":[{"User":{"Gender":{"name":"userRole00000000000000000000000000005015VOCSUFFIXgender"},"FirstName":{"name":"userRole00000000000000000000000000005015VOCSUFFIXfn"},"Id":{"name":"userRole00000000000000000000000000005015VOCSUFFIXrid"},"LastName":{"name":"userRole00000000000000000000000000005015VOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005015VOCSUFFIXsig"},"name":"Role00000000000000000000000000005015VOCSUFFIX","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005015VOCSUFFIXrid"}},"roleId":"00000000-0000-0000-0000-000000005015"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005015gVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005015gVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005015gVOCSUFFIXgn"},"Id":{"name":"groupRole00000000000000000000000000005015gVOCSUFFIXrid"}},"roleId":"00000000-0000-0000-0000-000000005015"},{"User":{"Gender":{"name":"userRole00000000000000000000000000005016VOCSUFFIXgender"},"FirstName":{"name":"userRole00000000000000000000000000005016VOCSUFFIXfn"},"Id":{"name":"userRole00000000000000000000000000005016VOCSUFFIXrid"},"LastName":{"name":"userRole00000000000000000000000000005016VOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016VOCSUFFIXsig"},"name":"Role00000000000000000000000000005016VOCSUFFIX","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016VOCSUFFIXrid"}},"roleId":"00000000-0000-0000-0000-000000005016"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016gVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016gVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005016gVOCSUFFIXgn"},"Id":{"name":"groupRole00000000000000000000000000005016gVOCSUFFIXrid"}},"roleId":"00000000-0000-0000-0000-000000005016"},{"User":{"Gender":{"name":"userRole00000000000000000000000000005018VOCSUFFIXgender"},"FirstName":{"name":"userRole00000000000000000000000000005018VOCSUFFIXfn"},"Id":{"name":"userRole00000000000000000000000000005018VOCSUFFIXrid"},"LastName":{"name":"userRole00000000000000000000000000005018VOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005018VOCSUFFIXsig"},"name":"Role00000000000000000000000000005018VOCSUFFIX","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005018VOCSUFFIXrid"}},"roleId":"00000000-0000-0000-0000-000000005018"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005018gVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005018gVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005018gVOCSUFFIXgn"},"Id":{"name":"groupRole00000000000000000000000000005018gVOCSUFFIXrid"}},"roleId":"00000000-0000-0000-0000-000000005018"}],"Filter":{"AND":[{"AND":[{"Field":{"name":"isvocmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"vocabularyParentCommunityId","operator":"EQUALS","value":"'.$community.'"}}]},{"AND":[{"Field":{"name":"domainType","operator":"EQUALS","value":"Glossary"}}]}]},"Order":[{"Field":{"name":"vocabulary","order":"ASC"}}]}},"displayStart":0,"displayLength":100,"generalConceptId":"' . Configure::read('Collibra.community.byu') . '"}}'
		);

		return $resp;
	}

	// called on results page's more details tab
	public function getTermRoles() {
		session_write_close(); //No local database writes, so allow this ajax request to run in parallel with others
		$this->autoRender = false;
		$termrid = $this->request->query['resource'];

		$resp = json_decode($this->CollibraAPI->get('/member/find/all?resource='.$termrid));
		$steward = null;
		$custodian = null;
		$members = $resp->memberReference;
		$i = 0;
		while (!($steward && $custodian) && ($i < 20)) {
			if ($members[$i]->role->signifier == 'Steward') {
				$steward = $members[$i]->ownerUser->firstName . " " . $members[$i]->ownerUser->lastName;
			}
			if ($members[$i]->role->signifier == 'Custodian') {
				$custodian = $members[$i]->ownerUser->firstName . " " . $members[$i]->ownerUser->lastName;
			}
			$i++;
		}
		$result = [
			'steward' => $steward,
			'custodian' => $custodian
		];
		return json_encode($result);
	}

	// get list of all other terms within a vocabulary/glossary
	public function getFullVocab() {
		session_write_close(); //No local database writes, so allow this ajax request to run in parallel with others
		$vocabRID= $this->request->query['rid'];
		$originTermID = isset($this->request->query['termid']) ? $this->request->query['termid'] : null;

		$jsonResp = $this->CollibraAPI->getTerms($vocabRID);


		echo '<div class="checkCol">';
		$itemCount = 0;
		for($i=0; $i<sizeof($jsonResp->aaData); $i++){
			$term = $jsonResp->aaData[$i];
			$termName = $term->termsignifier;
			$termID = $term->termrid;
			$termDef = nl2br(str_replace("\n\n\n", "\n\n", htmlentities(strip_tags(str_replace(['<div>', '<br>', '<br/>'], "\n", $term->description)))));
			if(Configure::read('allowUnrequestableTerms')){
				$disabled = '';
			}else{
				$disabled = $term->concept == 'true'?'disabled':'';
			}
			if(!$disabled){
				if(sizeof($term->synonym_for)!=0){
					$termDef = 'Synonym for '.$term->synonym_for[0]->synonymname;
				}

				$random = uniqid(rand(111111,999999));
				$classification = $term->classification;
				switch($classification){
					case 'Public':
					case '1 - Public':
						$classificationIcon = '<img src="/img/iconPublic.png" title="Public" alt="Public" width="9" />';
						break;
					case 'Internal':
					case '2 - Internal':
						$classificationIcon = '<img src="/img/iconInternal.png" title="Internal" alt="Internal" width="9" />';
						break;
					case 'Confidential':
					case '3 - Confidential':
						$classificationIcon = '<img src="/img/iconClassified.png" title="Confidential" alt="Confidential" width="9" />';
						break;
					case 'Highly Confidential':
					case '4 - Highly Confidential':
						$classificationIcon = '<img src="/img/iconHighClassified.png" title="Highly Confidential" alt="Highly Confidential" width="9" />';
						break;
					case 'Not Applicable':
					case '0 - N/A':
						$classificationIcon = '<img src="/img/iconNotApplicable.png" title="Not Applicable" alt="Not Applicable" width="9" />';
						break;
					default:
						$classificationIcon = '<img src="/img/iconNoClassification2.png" title="Unspecified" alt="Unspecified" width="9" />';
						break;
				}

				if($itemCount>0 && $itemCount%2==0){
					echo '</div>';echo '</div>';
					echo '<div class="checkCol">';
				}
				if(!$disabled){
					echo '<input type="checkbox" name="terms[]" data-title="'.$termName.'" data-vocabID="'.$term->commrid.'" value="'.$termID.'" id="chk'.$termID.$random.'" class="chk'.$termID.'" '.$disabled.' '.($originTermID == $termID ? 'checked' : '').'>';
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

	public function getTermApis($term) {
		$arrApis = [];
		foreach ($term->representing_apifields as $field) {
			$resp = json_decode($this->CollibraAPI->get("term/{$field->fieldrid}"));
			$api = $resp->vocabularyReference->name;
			if (!in_array($api, $arrApis)) {
				array_push($arrApis, $api);
			}
		}

		return $arrApis;
	}

	public function autoCompleteTerm() {
		session_write_close(); //No local database writes, so allow this ajax request to run in parallel with others
		$query= $this->request->query('q');
		if(empty($query)){
			return new CakeResponse(['type' => 'application/javascript', 'body' => []]);
		}

		$definitionAttributeTypeId = Configure::read('Collibra.attribute.definition');

		$results = [];
		// create JSON request string
		$jsonResp = $this->CollibraAPI->searchTerms($query);
		for($i=0; $i<sizeof($jsonResp->results); $i++){
			$requestable = true;
			// don't show non-requestable items
			foreach($jsonResp->results[$i]->attributes as $attr){
				if($attr->type == 'Concept' && $attr->val == 'true'){
					$requestable = false;
					break;
				}
			}
			if($requestable){
				$result = $jsonResp->results[$i];
				if (empty($result->definition) && !empty($result->attributes)) {
					foreach ($result->attributes as $attribute) {
						if ($attribute->typeId == $definitionAttributeTypeId) {
							$result->definition = $attribute;
						}
					}
				}
				$results[] = $result;
			}
		}
		return new CakeResponse(['type' => 'application/javascript', 'body' => json_encode($results)]);
	}

	public function getCommonSearches(){
		$commonSearches = array();
		$this->loadModel('CommonSearch');
		$results = $this->CommonSearch->find('all', [
			'fields' => 'query',
			'group' => 'query',
			'order' => ['COUNT(*)' => 'DESC'],
			'limit' => 4]);
		foreach($results as $result){
			array_push($commonSearches, ucfirst($result['CommonSearch']['query']));
		}
		return $commonSearches;
	}
	/////////////////////////////////////////////////////////

	private function getTermDetails($query){
		$arrResp = '';

		// get all communities to use for bread crumbs in results page
		$resp = $this->CollibraAPI->get('community/all');
		$jsonAllCommunities = json_decode($resp);

		$resp = $this->CollibraAPI->getTerms(null, ['additionalFilters' => [[
			'OR' => [['Field' => [
				'name' => 'termrid',
				'operator' => 'EQUALS',
				'value' => $query
			]]]]]]);

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
			}
		}

		return $resp;
	}

	private function searchTerms($query, $page=0, $displayLength=1000, $sortField='termsignifier', $sortOrder='ASC', $communityFilter='', $termOnly=false){
		$displayStart = $page*$displayLength;

		// use API search call to query based on user input
		$searchResp = $this->CollibraAPI->searchTerms($query, 200, $communityFilter);

		// build filter string of term ID used to full detail search below
		$ridFilter = [];
		foreach($searchResp->results as $result){
			$ridFilter[] = ['Field' => [
				'name' => 'termrid',
				'operator' => 'EQUALS',
				'value' => $result->name->id]];
		}
		$filters = [['OR' => $ridFilter]];
		if(!Configure::read('allowUnrequestableTerms')){
			$filters[] = [
				'OR' => [
					['Field' => [
						'name' => 'concept',
						'operator' => 'NULL']],
					['Field' => [
						'name' => 'concept',
						'operator' => 'EQUALS',
						'value' => false]]]];
		}
		$options = ['additionalFilters' => $filters];
		// set sort if not sorting by score
		if($sortField != 'score'){
			$options['sortField'] = $sortField;
			$options['sortOrder'] = $sortOrder;
		}
		$resp = $this->CollibraAPI->getTerms(null, $options);
		if (empty($resp)) {
			return false;
		}

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
		$communityResp = $this->CollibraAPI->get('community/all');
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
			}
		}

		return $resp;
	}

	private function getDomainTerms($domainFilter='', $page=0, $length=1000, $sortField='termsignifier', $sortOrder='ASC'){
		$start = $page*$length;
		$options = compact('sortField', 'sortOrder', 'start', 'length');

		// get all communities to use for bread crumbs in results page
		$communities = $this->CollibraAPI->get('community/all', ['json' => true]);

		$resp = $this->CollibraAPI->getTerms($domainFilter, $options);

		// loop through terms to check for quick links and also to build domain breadcrumb
		if(sizeof($resp->aaData)>0){
			$term = $resp->aaData;
			for($i=0; $i<sizeof($term); $i++){
				// add parent community names to breadcrumb
				$fullCommunityName = '';
				for($j=0; $j<sizeof($communities->communityReference); $j++){
					$parentObj = $communities->communityReference[$j];
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
			}
		}
		return $resp;
	}
}
