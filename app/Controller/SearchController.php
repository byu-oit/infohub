<?php

class SearchController extends AppController {
    private static function  str_getcsv2($arr){
        $a = str_getcsv($arr, ";", '"');
        $epoch = $a[0]/1000;
        $a[0] = date('m/d/Y', $epoch);
        //print_r($a);
        //echo "\r\n-----------------\r\n";
        
        // add all terms in vocabulary to array
        /////////////////////////////////////////////////
        /*$obj = new SearchController();
        $obj->loadModel('CollibraAPI');
        $objCollibra = new CollibraAPI();
        if(sizeof($a)>1){
            $resp = $objCollibra->request(
                array('url'=>'vocabulary/'.$a[17].'/terms')
            );
            $jsonResp = json_decode($resp);
            if(sizeof($jsonResp->termReference)>0){
                array_push($a, $jsonResp->termReference);
            }
        }*/
        /////////////////////////////////////////////////
        
        return $a;
    }
    
    public function index() {
        
    }
    
    public function catalog() {
        
    }
    
    public function success() {
        
    }
    
    public function request() {
        
    }
    
    public function results() {
        App::uses('Helpers', 'Model');
        $query = $this->request->params['pass'][0];
        
        // set community filter based on querystring value
        ///////////////////////////////////////////////////////
        $filter = '99582048-38e3-4149-a301-c6d54d8151c8';
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
                $sortOn = 'createdOn';
                break;
        }
        ///////////////////////////////////////////////////////
        
        $page = Helpers::getInt($this->request->params['pass'][1]);
        if($page==0) $page=1;
        
        // get all terms matching query
        ///////////////////////////////////////////////////////
        $terms = $this->searchTerms($query, $page-1, 10, $sortField, $sortOrder, $filter);
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
        // to be used in the search filter
        ///////////////////////////////////////////////////////
        $this->loadModel('CollibraAPI');
        $objCollibra = new CollibraAPI();
        $resp = $objCollibra->request(array('url'=>'community/99582048-38e3-4149-a301-c6d54d8151c8/sub-communities'));
        $communities = json_decode($resp);
        usort($communities->communityReference, 'self::sortCommunities');
        ///////////////////////////////////////////////////////
        
        $this->set('commonSearches', $this->getCommonSearches());
        $this->set('communities', $communities);
        $this->set('totalPages', ceil($terms->iTotalDisplayRecords/10));
        $this->set('pageNum', $page);
        $this->set('filter', $filter);
        $this->set('sort', $sort);
        $this->set('terms', $terms);
        $this->set('searchInput', $query);
    }
    
    public function getFullVocab() {
        $vacabRID= $this->request->query['rid'];
        $this->loadModel('CollibraAPI');
        $objCollibra = new CollibraAPI();
        $resp = $objCollibra->request(
            array('url'=>'vocabulary/'.$vacabRID.'/terms')
        );
        $jsonResp = json_decode($resp);
        
        echo '<div class="checkCol">';
        for($i=0; $i<sizeof($jsonResp->termReference)-1; $i++){
            $term = $jsonResp->termReference[$i]->signifier;
            if($i>0 && $i%2==0){
                echo '</div>';echo '</div>';
                echo '<div class="checkCol">';
            }
            echo '    <input type="checkbox" name="'.$term.'" id="'.$term.'">'.
                '    <label for="'.$term.'">'.$term.'</label>';
            if($i%2==0){
                echo '<br/>';
            }
                
        }
        echo '</div>';
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
                        'params'=>'{ "query": "'.$query.'*", "filter": { "community": ["99582048-38e3-4149-a301-c6d54d8151c8"], "category": ["TE"], "vocabulary": ["fbe8efa7-6273-475b-8770-bf0efac31752"], "type": { "asset":[], "domain":[] }, "status": [], "includeMeta": true }, "fields": ["name"], "order": { "by": "score", "sort": "desc" }, "limit": 5, "offset": 0, "highlight": false, "relativeUrl": true, "withParents": true }'
                    )
            );
            $jsonResp = json_decode($resp);
            for($i=0; $i<sizeof($jsonResp->results); $i++){
                echo '<li>'.$jsonResp->results[$i]->name->val.'</li>';
            }
        }
        
        exit;
    }
    
    private function getCommonSearches(){
        $this->loadModel('CollibraAPI');
        $objCollibra = new CollibraAPI();
        $commonSearches = array();
        $results = $objCollibra->query("SELECT query, COUNT(*) total FROM common_searches GROUP BY query ORDER BY COUNT(*) DESC LIMIT 0,4");
        foreach($results as $result){
            array_push($commonSearches, ucfirst($result['common_searches']['query']));
        }
        return $commonSearches;
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
    
    private function searchTerms($query, $page=0, $displayLength=20, $sortField='termsignifier', $sortOrder='ASC', $communityFilter='99582048-38e3-4149-a301-c6d54d8151c8', $termOnly=false){
        $arrResp = '';
        $displayStart = $page*$displayLength;
        
        $obj = new SearchController();
        $obj->loadModel('CollibraAPI');
        $objCollibra = new CollibraAPI();
        
        // get all communities to search within if no community filter is applied
        ///////////////////////////////////////////
        $resp = $objCollibra->request(
            array('url'=>'community/'.$communityFilter.'/sub-communities')
        );
        $jsonResp = json_decode($resp);
        ///////////////////////////////////////////
        
        $commFilter = '';
        $commFilter .= '{"Field":{"name":"commrid","operator":"EQUALS","value":"'.$communityFilter.'"}},';
        foreach($jsonResp->communityReference as $comm){
            $commFilter .= '{"Field":{"name":"commrid","operator":"EQUALS","value":"'.$comm->resourceId.'"}},';
        }
        if($commFilter!='') $commFilter = substr($commFilter, 0, strlen($commFilter)-1);

        $requestFilter = '{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"createdOn"}},{"Column":{"fieldName":"termrid"}},{"Column":{"fieldName":"termsignifier"}},{"Column":{"fieldId":"00000000-0000-0000-0000-000000000202","fieldName":"Attr00000000000000000000000000000202"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202longExpr"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202rid"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldId":"00000000-0000-0000-0000-000000005016","fieldName":"userRole00000000000000000000000000005016rid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole00000000000000000000000000005016gender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole00000000000000000000000000005016fn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole00000000000000000000000000005016ln"}}],"name":"Role00000000000000000000000000005016"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole00000000000000000000000000005016grid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole00000000000000000000000000005016ggn"}}],"name":"Role00000000000000000000000000005016g"}},{"Column":{"fieldName":"statusname"}},{"Column":{"fieldName":"statusrid"}},{"Column":{"fieldName":"communityname"}},{"Column":{"fieldName":"commrid"}},{"Column":{"fieldName":"domainname"}},{"Column":{"fieldName":"domainrid"}},{"Column":{"fieldName":"concepttypename"}},{"Column":{"fieldName":"concepttyperid"}}],'.
            '"Resources":{"Term":{"CreatedOn":{"name":"createdOn"},"Id":{"name":"termrid"},"Signifier":{"name":"termsignifier"},"StringAttribute":[{"Value":{"name":"Attr00000000000000000000000000000202"},"LongExpression":{"name":"Attr00000000000000000000000000000202longExpr"},"Id":{"name":"Attr00000000000000000000000000000202rid"},"labelId":"00000000-0000-0000-0000-000000000202"}],"Member":[{"User":{"Gender":{"name":"userRole00000000000000000000000000005016gender"},"FirstName":{"name":"userRole00000000000000000000000000005016fn"},"Id":{"name":"userRole00000000000000000000000000005016rid"},"LastName":{"name":"userRole00000000000000000000000000005016ln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016sig"},"name":"Role00000000000000000000000000005016","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016rid"}},"roleId":"00000000-0000-0000-0000-000000005016"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016g"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016grid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005016ggn"},"Id":{"name":"groupRole00000000000000000000000000005016grid"}},"roleId":"00000000-0000-0000-0000-000000005016"}],"Status":{"Signifier":{"name":"statusname"},"Id":{"name":"statusrid"}},"Vocabulary":{"Community":{"Name":{"name":"communityname"},"Id":{"name":"commrid"}},"Name":{"name":"domainname"},"Id":{"name":"domainrid"}},"ConceptType":[{"Signifier":{"name":"concepttypename"},"Id":{"name":"concepttyperid"}}],'.
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
                '                    "name":"Attr00000000000000000000000000000202",'.
                '                    "operator":"INCLUDES",'.
                '                    "value":"'.$query.'"'.
                '                 }'.
                '               }';
        }

        $requestFilter .= ']'.
            '        },'.
            '        {'.
            '           "OR":['.$commFilter.']'.
            '        }'.
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
        //$arrResp = array_map('self::str_getcsv2', explode("\n", $resp));
        
        //print_r($resp);
        //exit;
        return $resp;
    }
}