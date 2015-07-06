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
        $this->set('totalPages', 0);
        $this->set('pageNum', 0);
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
        $this->set('totalPages', ceil($terms->iTotalDisplayRecords/25));
        $this->set('pageNum', $page);
        $this->set('terms', $terms);
        $this->set('searchInput', '');
        $this->set('domain', $domainID);
        
        $this->render('results');
    }
    
    public function results() {
        App::uses('Helpers', 'Model');
        $query = $this->request->params['pass'][0];
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
                $sortField = 'termsignifier';
                break;
            case 1:
                $sortField = 'lastModified';
                $sortOrder = 'DESC';
                break;
            case 2:
                $sortField = 'Attre0937764544a4d2198cedc0c1936b465';
                break;
        }
        ///////////////////////////////////////////////////////
        
        $page = 0;
        if(isset($this->request->params['pass'][1])){
            $page = Helpers::getInt($this->request->params['pass'][1]);
        }
        if($page==0) $page=1;
        
        // get all terms matching query
        $terms = $this->searchTerms($query, $page-1, 10, $sortField, $sortOrder, $filter);
        //print_r($terms);exit;
        
        // save search and delete anything over 300 entries
        if(sizeof($terms->aaData)>0){
            $this->loadModel('CollibraAPI');
            $objCollibra = new CollibraAPI();
            // delete last record
            $results = $objCollibra->query("SELECT * FROM common_searches");
            if(sizeof($results)>=300){
                $objCollibra->query("DELETE FROM common_searches WHERE id=".$results[0]['common_searches']['id']);
            }
            // add new record
            $objCollibra->query("INSERT INTO common_searches (query) VALUES('".$query."')");
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
        $this->set('totalPages', ceil($terms->iTotalDisplayRecords/10));
        $this->set('pageNum', $page);
        $this->set('filter', $filter);
        $this->set('sort', $sort);
        $this->set('terms', $terms);
        $this->set('searchInput', $query);
    }
    
    
    // AJAX VIEWS
    /////////////////////////////////////////////////////////
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
        $resp = $objCollibra->request(
            array(
                'url'=>'output/data_table',
                'post'=>true,
                'json'=>true,
                'params'=>'{"TableViewConfig":{"Columns":[{"Community":{"name":"Subcommunities","Columns":[{"Column":{"fieldName":"subcommunityid"}},{"Column":{"fieldName":"subcommunity"}},{"Column":{"fieldName":"hasNonMetaChildren"}},{"Group":{"Columns":[{"Column":{"label":"Admin User ID","fieldId":"00000000-0000-0000-0000-000000005015","fieldName":"userRole00000000000000000000000000005015rid"}},{"Column":{"label":"Admin Gender","fieldName":"userRole00000000000000000000000000005015gender"}},{"Column":{"label":"Admin First Name","fieldName":"userRole00000000000000000000000000005015fn"}},{"Column":{"label":"Admin Last Name","fieldName":"userRole00000000000000000000000000005015ln"}}],"name":"Role00000000000000000000000000005015"}},{"Group":{"Columns":[{"Column":{"label":"Admin Group ID","fieldName":"groupRole00000000000000000000000000005015grid"}},{"Column":{"label":"Admin Group Name","fieldName":"groupRole00000000000000000000000000005015ggn"}}],"name":"Role00000000000000000000000000005015g"}},{"Group":{"Columns":[{"Column":{"label":"Collibra Steward User ID","fieldId":"00000000-0000-0000-0000-000000005016","fieldName":"userRole00000000000000000000000000005016rid"}},{"Column":{"label":"Collibra Steward Gender","fieldName":"userRole00000000000000000000000000005016gender"}},{"Column":{"label":"Collibra Steward First Name","fieldName":"userRole00000000000000000000000000005016fn"}},{"Column":{"label":"Collibra Steward Last Name","fieldName":"userRole00000000000000000000000000005016ln"}}],"name":"Role00000000000000000000000000005016"}},{"Group":{"Columns":[{"Column":{"label":"Collibra Steward Group ID","fieldName":"groupRole00000000000000000000000000005016grid"}},{"Column":{"label":"Collibra Steward Group Name","fieldName":"groupRole00000000000000000000000000005016ggn"}}],"name":"Role00000000000000000000000000005016g"}},{"Group":{"Columns":[{"Column":{"label":"Stakeholder User ID","fieldId":"00000000-0000-0000-0000-000000005018","fieldName":"userRole00000000000000000000000000005018rid"}},{"Column":{"label":"Stakeholder Gender","fieldName":"userRole00000000000000000000000000005018gender"}},{"Column":{"label":"Stakeholder First Name","fieldName":"userRole00000000000000000000000000005018fn"}},{"Column":{"label":"Stakeholder Last Name","fieldName":"userRole00000000000000000000000000005018ln"}}],"name":"Role00000000000000000000000000005018"}},{"Group":{"Columns":[{"Column":{"label":"Stakeholder Group ID","fieldName":"groupRole00000000000000000000000000005018grid"}},{"Column":{"label":"Stakeholder Group Name","fieldName":"groupRole00000000000000000000000000005018ggn"}}],"name":"Role00000000000000000000000000005018g"}}]}},{"Vocabulary":{"name":"Vocabularies","Columns":[{"Column":{"fieldName":"vocabulary"}},{"Column":{"fieldName":"vocabularyid"}},{"Column":{"fieldName":"domainType"}},{"Group":{"Columns":[{"Column":{"label":"Admin User ID","fieldId":"00000000-0000-0000-0000-000000005015","fieldName":"userRole00000000000000000000000000005015VOCSUFFIXrid"}},{"Column":{"label":"Admin Gender","fieldName":"userRole00000000000000000000000000005015VOCSUFFIXgender"}},{"Column":{"label":"Admin First Name","fieldName":"userRole00000000000000000000000000005015VOCSUFFIXfn"}},{"Column":{"label":"Admin Last Name","fieldName":"userRole00000000000000000000000000005015VOCSUFFIXln"}}],"name":"Role00000000000000000000000000005015VOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Admin Group ID","fieldName":"groupRole00000000000000000000000000005015gVOCSUFFIXrid"}},{"Column":{"label":"Admin Group Name","fieldName":"groupRole00000000000000000000000000005015gVOCSUFFIXgn"}}],"name":"Role00000000000000000000000000005015gVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Collibra Steward User ID","fieldId":"00000000-0000-0000-0000-000000005016","fieldName":"userRole00000000000000000000000000005016VOCSUFFIXrid"}},{"Column":{"label":"Collibra Steward Gender","fieldName":"userRole00000000000000000000000000005016VOCSUFFIXgender"}},{"Column":{"label":"Collibra Steward First Name","fieldName":"userRole00000000000000000000000000005016VOCSUFFIXfn"}},{"Column":{"label":"Collibra Steward Last Name","fieldName":"userRole00000000000000000000000000005016VOCSUFFIXln"}}],"name":"Role00000000000000000000000000005016VOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Collibra Steward Group ID","fieldName":"groupRole00000000000000000000000000005016gVOCSUFFIXrid"}},{"Column":{"label":"Collibra Steward Group Name","fieldName":"groupRole00000000000000000000000000005016gVOCSUFFIXgn"}}],"name":"Role00000000000000000000000000005016gVOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Stakeholder User ID","fieldId":"00000000-0000-0000-0000-000000005018","fieldName":"userRole00000000000000000000000000005018VOCSUFFIXrid"}},{"Column":{"label":"Stakeholder Gender","fieldName":"userRole00000000000000000000000000005018VOCSUFFIXgender"}},{"Column":{"label":"Stakeholder First Name","fieldName":"userRole00000000000000000000000000005018VOCSUFFIXfn"}},{"Column":{"label":"Stakeholder Last Name","fieldName":"userRole00000000000000000000000000005018VOCSUFFIXln"}}],"name":"Role00000000000000000000000000005018VOCSUFFIX"}},{"Group":{"Columns":[{"Column":{"label":"Stakeholder Group ID","fieldName":"groupRole00000000000000000000000000005018gVOCSUFFIXrid"}},{"Column":{"label":"Stakeholder Group Name","fieldName":"groupRole00000000000000000000000000005018gVOCSUFFIXgn"}}],"name":"Role00000000000000000000000000005018gVOCSUFFIX"}}]}}],"Resources":{"Community":{"Id":{"name":"subcommunityid"},"Name":{"name":"subcommunity"},"Meta":{"name":"issubmeta"},"hasNonMetaChildren":{"name":"hasNonMetaChildren"},"ParentCommunity":{"Id":{"name":"parentCommunityId"}},"Member":[{"User":{"Gender":{"name":"userRole00000000000000000000000000005015gender"},"FirstName":{"name":"userRole00000000000000000000000000005015fn"},"Id":{"name":"userRole00000000000000000000000000005015rid"},"LastName":{"name":"userRole00000000000000000000000000005015ln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005015sig"},"name":"Role00000000000000000000000000005015","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005015rid"}},"roleId":"00000000-0000-0000-0000-000000005015"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005015g"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005015grid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005015ggn"},"Id":{"name":"groupRole00000000000000000000000000005015grid"}},"roleId":"00000000-0000-0000-0000-000000005015"},{"User":{"Gender":{"name":"userRole00000000000000000000000000005016gender"},"FirstName":{"name":"userRole00000000000000000000000000005016fn"},"Id":{"name":"userRole00000000000000000000000000005016rid"},"LastName":{"name":"userRole00000000000000000000000000005016ln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016sig"},"name":"Role00000000000000000000000000005016","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016rid"}},"roleId":"00000000-0000-0000-0000-000000005016"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016g"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016grid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005016ggn"},"Id":{"name":"groupRole00000000000000000000000000005016grid"}},"roleId":"00000000-0000-0000-0000-000000005016"},{"User":{"Gender":{"name":"userRole00000000000000000000000000005018gender"},"FirstName":{"name":"userRole00000000000000000000000000005018fn"},"Id":{"name":"userRole00000000000000000000000000005018rid"},"LastName":{"name":"userRole00000000000000000000000000005018ln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005018sig"},"name":"Role00000000000000000000000000005018","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005018rid"}},"roleId":"00000000-0000-0000-0000-000000005018"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005018g"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005018grid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005018ggn"},"Id":{"name":"groupRole00000000000000000000000000005018grid"}},"roleId":"00000000-0000-0000-0000-000000005018"}],"Filter":{"AND":[{"AND":[{"Field":{"name":"issubmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"parentCommunityId","operator":"EQUALS","value":"'.$community.'"}}]}]},"Order":[{"Field":{"name":"subcommunity","order":"ASC"}}]},"Vocabulary":{"Name":{"name":"vocabulary"},"Id":{"name":"vocabularyid"},"Meta":{"name":"isvocmeta"},"Community":{"Id":{"name":"vocabularyParentCommunityId"}},"VocabularyType":{"Signifier":{"name":"domainType"}},"Member":[{"User":{"Gender":{"name":"userRole00000000000000000000000000005015VOCSUFFIXgender"},"FirstName":{"name":"userRole00000000000000000000000000005015VOCSUFFIXfn"},"Id":{"name":"userRole00000000000000000000000000005015VOCSUFFIXrid"},"LastName":{"name":"userRole00000000000000000000000000005015VOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005015VOCSUFFIXsig"},"name":"Role00000000000000000000000000005015VOCSUFFIX","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005015VOCSUFFIXrid"}},"roleId":"00000000-0000-0000-0000-000000005015"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005015gVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005015gVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005015gVOCSUFFIXgn"},"Id":{"name":"groupRole00000000000000000000000000005015gVOCSUFFIXrid"}},"roleId":"00000000-0000-0000-0000-000000005015"},{"User":{"Gender":{"name":"userRole00000000000000000000000000005016VOCSUFFIXgender"},"FirstName":{"name":"userRole00000000000000000000000000005016VOCSUFFIXfn"},"Id":{"name":"userRole00000000000000000000000000005016VOCSUFFIXrid"},"LastName":{"name":"userRole00000000000000000000000000005016VOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016VOCSUFFIXsig"},"name":"Role00000000000000000000000000005016VOCSUFFIX","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016VOCSUFFIXrid"}},"roleId":"00000000-0000-0000-0000-000000005016"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016gVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016gVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005016gVOCSUFFIXgn"},"Id":{"name":"groupRole00000000000000000000000000005016gVOCSUFFIXrid"}},"roleId":"00000000-0000-0000-0000-000000005016"},{"User":{"Gender":{"name":"userRole00000000000000000000000000005018VOCSUFFIXgender"},"FirstName":{"name":"userRole00000000000000000000000000005018VOCSUFFIXfn"},"Id":{"name":"userRole00000000000000000000000000005018VOCSUFFIXrid"},"LastName":{"name":"userRole00000000000000000000000000005018VOCSUFFIXln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005018VOCSUFFIXsig"},"name":"Role00000000000000000000000000005018VOCSUFFIX","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005018VOCSUFFIXrid"}},"roleId":"00000000-0000-0000-0000-000000005018"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005018gVOCSUFFIX"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005018gVOCSUFFIXrid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005018gVOCSUFFIXgn"},"Id":{"name":"groupRole00000000000000000000000000005018gVOCSUFFIXrid"}},"roleId":"00000000-0000-0000-0000-000000005018"}],"Filter":{"AND":[{"AND":[{"Field":{"name":"isvocmeta","operator":"EQUALS","value":false}}]},{"AND":[{"Field":{"name":"vocabularyParentCommunityId","operator":"EQUALS","value":"'.$community.'"}}]}]},"Order":[{"Field":{"name":"vocabulary","order":"ASC"}}]}},"displayStart":0,"displayLength":100,"generalConceptId":"4e756e1e-11ee-4d1e-bfaa-fb0ada974fc5"}}'
            )
        );
        return $resp;
    }
    
    public function getFullVocab() {
        $vocabRID= $this->request->query['rid'];
        $this->loadModel('CollibraAPI');
        $objCollibra = new CollibraAPI();
        $resp = $objCollibra->request(
            array('url'=>'vocabulary/'.$vocabRID.'/terms')
        );
        $jsonResp = json_decode($resp);
        
        
        $resp = $objCollibra->request(
            array(
                'url'=>'output/data_table',
                'post'=>true,
                'json'=>true,
                'params'=>'{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"termrid"}},{"Column":{"fieldName":"termsignifier"}},{"Column":{"fieldId":"00000000-0000-0000-0000-000000000202","fieldName":"Attr00000000000000000000000000000202"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202longExpr"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202rid"}},{"Column":{"fieldName":"statusname"}},{"Column":{"fieldName":"statusrid"}},{"Column":{"fieldName":"communityname"}},{"Column":{"fieldName":"commrid"}},{"Column":{"fieldName":"domainname"}},{"Column":{"fieldName":"domainrid"}}],"Resources":{"Term":{"Id":{"name":"termrid"},"Signifier":{"name":"termsignifier"},"StringAttribute":[{"Value":{"name":"Attr00000000000000000000000000000202"},"LongExpression":{"name":"Attr00000000000000000000000000000202longExpr"},"Id":{"name":"Attr00000000000000000000000000000202rid"},"labelId":"00000000-0000-0000-0000-000000000202"}],"Status":{"Signifier":{"name":"statusname"},"Id":{"name":"statusrid"}},"Vocabulary":{"Community":{"Name":{"name":"communityname"},"Id":{"name":"commrid"}},"Name":{"name":"domainname"},"Id":{"name":"domainrid"}},"Filter":{"AND":[{"AND":[{"Field":{"name":"domainrid","operator":"EQUALS","value":"'.$vocabRID.'"}}]}]},"Order":[{"Field":{"name":"termsignifier","order":"ASC"}}]}},"displayStart":0,"displayLength":1000,"relationTypeId":"bc80a28c-57f6-49a7-9d2b-063e91614824","roleDirection":true}}'
            )
        );
        $jsonResp = json_decode($resp);
        
        echo '<div class="checkCol">';
        for($i=0; $i<sizeof($jsonResp->aaData); $i++){
            $term = $jsonResp->aaData[$i];
            $termName = $term->termsignifier;
            $termID = $term->termrid;
            $termDef = addslashes(strip_tags($term->Attr00000000000000000000000000000202));
            $random = uniqid(rand(111111,999999));
            if($i>0 && $i%2==0){
                echo '</div>';echo '</div>';
                echo '<div class="checkCol">';
            }
            echo '    <input type="checkbox" name="terms[]" data-title="'.$termName.'" value="'.$termID.'" id="chk'.$termID.$random.'" class="chk'.$termID.'">'.
                '    <label for="chk'.$termID.$random.'">'.$termName.'</label><div onmouseover="showTermDef(this)" onmouseout="hideTermDef()" data-definition="'.$termDef.'" class="info"><img src="/img/iconInfo.png"></div>';
            if($i%2==0){
                echo '<br/>';
            }
                
        }
        echo '</div>';
        exit;
    }
    
    public function getTermDefinition() {
        $vocabRID= $this->request->query['rid'];
        $this->loadModel('CollibraAPI');
        $objCollibra = new CollibraAPI();
        $resp = $objCollibra->request(
            array('url'=>'term/'.$vocabRID)
        );
        $jsonResp = json_decode($resp);
        $resp = $jsonResp->attributeReferences->attributeReference[0]->value;
        $resp = preg_replace( "/\r|\n/", "", $resp);
        echo strip_tags($resp);
        exit;
    }
    
    public function autoCompleteTerm() {
        $query= $this->request->query['q'];
        if($query!=''){
            $obj = new SearchController();
            $this->loadModel('CollibraAPI');
            $objCollibra = new CollibraAPI();
            $resp = $objCollibra->request(
                array(
                        'url'=>'search',
                        'post'=>true,
                        'json'=>true,
                        'params'=>'{ "query": "'.$query.'*", "filter": { "community": ["'.Configure::read('byuCommunity').'"], "category": ["TE"], "vocabulary": ["fbe8efa7-6273-475b-8770-bf0efac31752"], "type": { "asset":[], "domain":[] }, "status": ["00000000-0000-0000-0000-000000005009"], "includeMeta": true }, "fields": ["name"], "order": { "by": "score", "sort": "desc" }, "limit": 5, "offset": 0, "highlight": false, "relativeUrl": true, "withParents": true }'
                        //'params'=>'{ "query": "'.$query.'*", "filter": { "community": ["'.Configure::read('byuCommunity').'"], "category": ["TE"], "vocabulary": ["fbe8efa7-6273-475b-8770-bf0efac31752"], "type": { "asset":[], "domain":[] }, "includeMeta": true }, "fields": ["name"], "order": { "by": "score", "sort": "desc" }, "limit": 5, "offset": 0, "highlight": false, "relativeUrl": true, "withParents": true }'
                    )
            );
            $jsonResp = json_decode($resp);
            for($i=0; $i<sizeof($jsonResp->results); $i++){
                echo '<li>'.$jsonResp->results[$i]->name->val.'</li>';
            }
        }
        
        exit;
    }
    
    public function getCommonSearches(){
        $this->loadModel('CollibraAPI');
        $objCollibra = new CollibraAPI();
        $commonSearches = array();
        $results = $objCollibra->query("SELECT query, COUNT(*) total FROM common_searches GROUP BY query ORDER BY COUNT(*) DESC LIMIT 0,4");
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
        
        $requestFilter = '{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"createdOn"}},{"Column":{"fieldName":"lastModified"}},{"Column":{"fieldName":"termrid"}},{"Column":{"fieldName":"termsignifier"}},{"Column":{"fieldId":"e0937764-544a-4d21-98ce-dc0c1936b465", "fieldName":"Attre0937764544a4d2198cedc0c1936b465"}},{"Column":{"fieldName":"Attre0937764544a4d2198cedc0c1936b465rid"}},{"Column":{"fieldId":"00000000-0000-0000-0000-000000000202","fieldName":"Attr00000000000000000000000000000202"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202longExpr"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202rid"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldId":"00000000-0000-0000-0000-000000005016","fieldName":"userRole00000000000000000000000000005016rid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole00000000000000000000000000005016gender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole00000000000000000000000000005016fn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole00000000000000000000000000005016ln"}}],"name":"Role00000000000000000000000000005016"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole00000000000000000000000000005016grid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole00000000000000000000000000005016ggn"}}],"name":"Role00000000000000000000000000005016g"}},{"Column":{"fieldName":"statusname"}},{"Column":{"fieldName":"statusrid"}},{"Column":{"fieldName":"communityname"}},{"Column":{"fieldName":"commrid"}},{"Column":{"fieldName":"domainname"}},{"Column":{"fieldName":"domainrid"}},{"Column":{"fieldName":"concepttypename"}},{"Column":{"fieldName":"concepttyperid"}}],'.
            '"Resources":{"Term":{"CreatedOn":{"name":"createdOn"},"LastModified":{"name":"lastModified"},"Id":{"name":"termrid"}, "SingleValueListAttribute":[{"Value":{"name":"Attre0937764544a4d2198cedc0c1936b465"}, "Id":{"name":"Attre0937764544a4d2198cedc0c1936b465rid"}, "labelId":"e0937764-544a-4d21-98ce-dc0c1936b465"}],"Signifier":{"name":"termsignifier"},"StringAttribute":[{"Value":{"name":"Attr00000000000000000000000000000202"},"LongExpression":{"name":"Attr00000000000000000000000000000202longExpr"},"Id":{"name":"Attr00000000000000000000000000000202rid"},"labelId":"00000000-0000-0000-0000-000000000202"}],"Member":[{"User":{"Gender":{"name":"userRole00000000000000000000000000005016gender"},"FirstName":{"name":"userRole00000000000000000000000000005016fn"},"Id":{"name":"userRole00000000000000000000000000005016rid"},"LastName":{"name":"userRole00000000000000000000000000005016ln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016sig"},"name":"Role00000000000000000000000000005016","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016rid"}},"roleId":"00000000-0000-0000-0000-000000005016"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016g"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016grid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005016ggn"},"Id":{"name":"groupRole00000000000000000000000000005016grid"}},"roleId":"00000000-0000-0000-0000-000000005016"}],"Status":{"Signifier":{"name":"statusname"},"Id":{"name":"statusrid"}},"Vocabulary":{"Community":{"Name":{"name":"communityname"},"Id":{"name":"commrid"}},"Name":{"name":"domainname"},"Id":{"name":"domainrid"}},"ConceptType":[{"Signifier":{"name":"concepttypename"},"Id":{"name":"concepttyperid"}}],'.
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
            '        }'.
            '     ]'.
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
            for($i=0; $i<sizeof($resp->aaData); $i++){
                // add parent community names to breadcrumb
                $fullCommunityName = '';
                for($j=0; $j<sizeof($jsonAllCommunities->communityReference); $j++){
                    $parentObj = $jsonAllCommunities->communityReference[$j];
                    if($parentObj->resourceId == $resp->aaData[$i]->commrid){
                        while(isset($parentObj->parentReference)){
                            $parentObj = $parentObj->parentReference;
                            if($parentObj->name != "BYU"){
                                $fullCommunityName = $parentObj->name.' > '.$fullCommunityName;
                            }
                        }
                    }
                }
                $fullCommunityName .= $resp->aaData[$i]->communityname;
                $resp->aaData[$i]->communityname = $fullCommunityName;

                // check to see if this terms is stored in user's quick links cookie
                if(isset($_COOKIE['QL'])){
                    $resp->aaData[$i]->saved = '0';
                    $arrQl = unserialize($_COOKIE['QL']);
                    for($j=0; $j<sizeof($arrQl); $j++){
                        if($arrQl[$j][1] == $resp->aaData[$i]->termrid){
                            $resp->aaData[$i]->saved = '1';
                            break;
                        }
                    }
                }
            }
        }

        return $resp;
    }
    
    private function searchTerms($query, $page=0, $displayLength=20, $sortField='termsignifier', $sortOrder='ASC', $communityFilter='', $termOnly=false){
        $arrResp = '';
        $displayStart = $page*$displayLength;
        
        $obj = new SearchController();
        $obj->loadModel('CollibraAPI');
        $objCollibra = new CollibraAPI();
        
        // get all communities to use for search filtering and bread crumbs in results page
        $resp = $objCollibra->request(
            array('url'=>'community/all')
        );
        $jsonAllCommunities = json_decode($resp);

        //print_r($jsonAllCommunities);exit;
        
        // get all communities to search within if no community filter is applied
        ///////////////////////////////////////////
        $commFilter = '';
        if($communityFilter == ''){ // get all communities if communityFilter is not provided
            for($i=0; $i<sizeof($jsonAllCommunities->communityReference); $i++){
                $commFilterTmp = '';
                $parentObj = $jsonAllCommunities->communityReference[$i];
                // only add reference id if not in current list
                if(strpos($commFilter, $parentObj->resourceId)===false){
                    $commFilterTmp .= '{"Field":{"name":"commrid","operator":"EQUALS","value":"'.$parentObj->resourceId.'"}},';
                }
                while(isset($parentObj->parentReference)){
                    // only add reference id if not in current list
                    if(strpos($commFilter, $parentObj->parentReference->resourceId)==false){
                        $commFilterTmp .= '{"Field":{"name":"commrid","operator":"EQUALS","value":"'.$parentObj->parentReference->resourceId.'"}},';
                    }
                    $parentObj = $parentObj->parentReference;
                }

                if($parentObj->resourceId === Configure::read('byuCommunity')){
                    $commFilter .= $commFilterTmp;
                }
            }
        }else{ // search sub communities if communityFilter is provided
            $resp = $objCollibra->request(
                array('url'=>'community/'.$communityFilter.'/sub-communities')
            );
            $jsonResp = json_decode($resp);
            $commFilter .= '{"Field":{"name":"commrid","operator":"EQUALS","value":"'.$communityFilter.'"}},';
            foreach($jsonResp->communityReference as $comm){
                $commFilter .= '{"Field":{"name":"commrid","operator":"EQUALS","value":"'.$comm->resourceId.'"}},';
            }
        }
        if($commFilter!='') $commFilter = substr($commFilter, 0, strlen($commFilter)-1);
        ///////////////////////////////////////////

        $resp = $objCollibra->request(
            array('url'=>'community/all')
        );

        $requestFilter = '{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"lastModified"}},{"Column":{"fieldName":"createdOn"}},{"Column":{"fieldName":"termrid"}},{"Column":{"fieldName":"termsignifier"}},{"Column":{"fieldId":"00000000-0000-0000-0000-000000000202","fieldName":"Attr00000000000000000000000000000202"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202longExpr"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202rid"}},{"Column":{"fieldId":"e0937764-544a-4d21-98ce-dc0c1936b465","fieldName":"Attre0937764544a4d2198cedc0c1936b465"}},{"Column":{"fieldName":"Attre0937764544a4d2198cedc0c1936b465rid"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldId":"00000000-0000-0000-0000-000000005016","fieldName":"userRole00000000000000000000000000005016rid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole00000000000000000000000000005016gender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole00000000000000000000000000005016fn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole00000000000000000000000000005016ln"}}],"name":"Role00000000000000000000000000005016"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole00000000000000000000000000005016grid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole00000000000000000000000000005016ggn"}}],"name":"Role00000000000000000000000000005016g"}},{"Column":{"fieldName":"statusname"}},{"Column":{"fieldName":"statusrid"}},{"Column":{"fieldName":"communityname"}},{"Column":{"fieldName":"commrid"}},{"Column":{"fieldName":"domainname"}},{"Column":{"fieldName":"domainrid"}},{"Column":{"fieldName":"concepttypename"}},{"Column":{"fieldName":"concepttyperid"}}],'.
            '"Resources":{"Term":{"CreatedOn":{"name":"createdOn"},"LastModified":{"name":"lastModified"},"Id":{"name":"termrid"},"Signifier":{"name":"termsignifier"},"StringAttribute":[{"Value":{"name":"Attr00000000000000000000000000000202"},"LongExpression":{"name":"Attr00000000000000000000000000000202longExpr"},"Id":{"name":"Attr00000000000000000000000000000202rid"},"labelId":"00000000-0000-0000-0000-000000000202"}],"Member":[{"User":{"Gender":{"name":"userRole00000000000000000000000000005016gender"},"FirstName":{"name":"userRole00000000000000000000000000005016fn"},"Id":{"name":"userRole00000000000000000000000000005016rid"},"LastName":{"name":"userRole00000000000000000000000000005016ln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016sig"},"name":"Role00000000000000000000000000005016","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016rid"}},"roleId":"00000000-0000-0000-0000-000000005016"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016g"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016grid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005016ggn"},"Id":{"name":"groupRole00000000000000000000000000005016grid"}},"roleId":"00000000-0000-0000-0000-000000005016"}],"SingleValueListAttribute":[{"Value":{"name":"Attre0937764544a4d2198cedc0c1936b465"},"Id":{"name":"Attre0937764544a4d2198cedc0c1936b465rid"},"labelId":"e0937764-544a-4d21-98ce-dc0c1936b465"}],"Status":{"Signifier":{"name":"statusname"},"Id":{"name":"statusrid"}},"Vocabulary":{"Community":{"Name":{"name":"communityname"},"Id":{"name":"commrid"}},"Name":{"name":"domainname"},"Id":{"name":"domainrid"}},"ConceptType":[{"Signifier":{"name":"concepttypename"},"Id":{"name":"concepttyperid"}}],'.
            '"Filter":{'.
            '   "AND":['.
            '        {'.
            '           "OR":['.
            '              {'.
            '                 "Field":{'.
            '                    "name":"termsignifier",'.
            '                    "operator":"INCLUDES",'.
            '                    "value":"'.$query.'"'.
            '                 }'.
            '              }';

        // search definition as well as term title
        if(!$termOnly){
            $requestFilter .= ',{'.
                '                 "Field":{'.
                '                    "name":"Attr00000000000000000000000000000202longExpr",'.
                '                    "operator":"INCLUDES",'.
                '                    "value":"'.$query.'"'.
                '                 }'.
                '               }';
                /*'               ,{'.
                '                 "Field":{'.
                '                    "name":"Attr00000000000000000000000000000202",'.
                '                    "operator":"INCLUDES",'.
                '                    "value":"'.$query.'"'.
                '                 }'.
                '               }';*/
        }

        $requestFilter .= ']'.
            '        },'.
            '        {'.
            '           "OR":['.$commFilter.']'.
            '        }'.
            //'        },'.
            //'        {'.
            //'           "AND":[{"Field":{"name":"statusname","operator":"EQUALS","value":"Accepted"}}]'.
            //'        }'.
            '     ]'.
            '}'.
            ',"Order":[';

        // set sort
        $requestFilter .= '{"Field":{"name":"'.$sortField.'","order":"'.$sortOrder.'"}}';

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
        //print_r($resp);exit;
        

        // loop through terms to check for quick links and also to build domain breadcrumb
        if(sizeof($resp->aaData)>0){
            for($i=0; $i<sizeof($resp->aaData); $i++){
                // add parent community names to breadcrumb
                $fullCommunityName = '';
                for($j=0; $j<sizeof($jsonAllCommunities->communityReference); $j++){
                    $parentObj = $jsonAllCommunities->communityReference[$j];
                    if($parentObj->resourceId == $resp->aaData[$i]->commrid){
                        while(isset($parentObj->parentReference)){
                            $parentObj = $parentObj->parentReference;
                            if($parentObj->name != "BYU"){
                                $fullCommunityName = $parentObj->name.' > '.$fullCommunityName;
                            }
                        }
                    }
                }
                $fullCommunityName .= $resp->aaData[$i]->communityname;
                $resp->aaData[$i]->communityname = $fullCommunityName;

                // check to see if this terms is stored in user's quick links cookie
                if(isset($_COOKIE['QL'])){
                    $resp->aaData[$i]->saved = '0';
                    $arrQl = unserialize($_COOKIE['QL']);
                    for($j=0; $j<sizeof($arrQl); $j++){
                        if($arrQl[$j][1] == $resp->aaData[$i]->termrid){
                            $resp->aaData[$i]->saved = '1';
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
        
        $requestFilter = '{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"createdOn"}},{"Column":{"fieldName":"lastModified"}},{"Column":{"fieldName":"termrid"}},{"Column":{"fieldName":"termsignifier"}},{"Column":{"fieldId":"e0937764-544a-4d21-98ce-dc0c1936b465", "fieldName":"Attre0937764544a4d2198cedc0c1936b465"}},{"Column":{"fieldName":"Attre0937764544a4d2198cedc0c1936b465rid"}},{"Column":{"fieldId":"00000000-0000-0000-0000-000000000202","fieldName":"Attr00000000000000000000000000000202"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202longExpr"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202rid"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldId":"00000000-0000-0000-0000-000000005016","fieldName":"userRole00000000000000000000000000005016rid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole00000000000000000000000000005016gender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole00000000000000000000000000005016fn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole00000000000000000000000000005016ln"}}],"name":"Role00000000000000000000000000005016"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole00000000000000000000000000005016grid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole00000000000000000000000000005016ggn"}}],"name":"Role00000000000000000000000000005016g"}},{"Column":{"fieldName":"statusname"}},{"Column":{"fieldName":"statusrid"}},{"Column":{"fieldName":"communityname"}},{"Column":{"fieldName":"commrid"}},{"Column":{"fieldName":"domainname"}},{"Column":{"fieldName":"domainrid"}},{"Column":{"fieldName":"concepttypename"}},{"Column":{"fieldName":"concepttyperid"}}],'.
            '"Resources":{"Term":{"CreatedOn":{"name":"createdOn"},"LastModified":{"name":"lastModified"},"Id":{"name":"termrid"},"Signifier":{"name":"termsignifier"},"StringAttribute":[{"Value":{"name":"Attr00000000000000000000000000000202"},"LongExpression":{"name":"Attr00000000000000000000000000000202longExpr"},"Id":{"name":"Attr00000000000000000000000000000202rid"},"labelId":"00000000-0000-0000-0000-000000000202"}],"Member":[{"User":{"Gender":{"name":"userRole00000000000000000000000000005016gender"},"FirstName":{"name":"userRole00000000000000000000000000005016fn"},"Id":{"name":"userRole00000000000000000000000000005016rid"},"LastName":{"name":"userRole00000000000000000000000000005016ln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016sig"},"name":"Role00000000000000000000000000005016","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016rid"}},"roleId":"00000000-0000-0000-0000-000000005016"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016g"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016grid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005016ggn"},"Id":{"name":"groupRole00000000000000000000000000005016grid"}},"roleId":"00000000-0000-0000-0000-000000005016"}], "SingleValueListAttribute":[{"Value":{"name":"Attre0937764544a4d2198cedc0c1936b465"}, "Id":{"name":"Attre0937764544a4d2198cedc0c1936b465rid"}, "labelId":"e0937764-544a-4d21-98ce-dc0c1936b465"}],"Status":{"Signifier":{"name":"statusname"},"Id":{"name":"statusrid"}},"Vocabulary":{"Community":{"Name":{"name":"communityname"},"Id":{"name":"commrid"}},"Name":{"name":"domainname"},"Id":{"name":"domainrid"}},"ConceptType":[{"Signifier":{"name":"concepttypename"},"Id":{"name":"concepttyperid"}}],'.
            '"Filter":{'.
            '   "AND":['.
            '        {'.
            '           "AND":['.
            '               {"Field":{"name":"domainrid","operator":"EQUALS","value":"'.$domainFilter.'"}}'.
            //'               ,{"Field":{"name":"statusname","operator":"EQUALS","value":"Accepted"}}'.
            '           ]'.
            '        },'.
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
            for($i=0; $i<sizeof($resp->aaData); $i++){
                // add parent community names to breadcrumb
                $fullCommunityName = '';
                for($j=0; $j<sizeof($jsonAllCommunities->communityReference); $j++){
                    $parentObj = $jsonAllCommunities->communityReference[$j];
                    if($parentObj->resourceId == $resp->aaData[$i]->commrid){
                        while(isset($parentObj->parentReference)){
                            $parentObj = $parentObj->parentReference;
                            if($parentObj->name != "BYU"){
                                $fullCommunityName = $parentObj->name.' > '.$fullCommunityName;
                            }
                        }
                    }
                }
                $fullCommunityName .= $resp->aaData[$i]->communityname;
                $resp->aaData[$i]->communityname = $fullCommunityName;

                // check to see if this terms is stored in user's quick links cookie
                if(isset($_COOKIE['QL'])){
                    $resp->aaData[$i]->saved = '0';
                    $arrQl = unserialize($_COOKIE['QL']);
                    for($j=0; $j<sizeof($arrQl); $j++){
                        if($arrQl[$j][1] == $resp->aaData[$i]->termrid){
                            $resp->aaData[$i]->saved = '1';
                            break;
                        }
                    }
                }
            }
        }
        
        return $resp;
    }
}