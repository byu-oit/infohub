<?php

class SearchController extends AppController {
    private static function  str_getcsv2($arr){
        $a = str_getcsv($arr, ";", '"');
        $epoch = $a[0]/1000;
        $a[0] = date('m d Y', $epoch);
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
        $query = $this->request->params['pass'][0];
        $arrResp = $this->searchTerms($query);
        
        $this->set('terms', $arrResp);
        $this->set('searchInput', $query);
    }
    
    public function getFullVocab() {
        $vacabRID= $this->request->query['rid'];
        $obj = new SearchController();
        $obj->loadModel('CollibraAPI');
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
            echo '    <input type="checkbox" name="'.$term.'">'.
                '    <label for="american-leadership">'.$term.'</label>';
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
            $obj->loadModel('CollibraAPI');
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
    
    private function searchTerms($query, $termOnly=false, $limit=20){
        $arrResp = '';
        
        $obj = new SearchController();
        $obj->loadModel('CollibraAPI');
        $objCollibra = new CollibraAPI();
        
        // get all communities to search within
        ///////////////////////////////////////////
        $resp = $objCollibra->request(
            array('url'=>'community/99582048-38e3-4149-a301-c6d54d8151c8/sub-communities')
        );
        $jsonResp = json_decode($resp);
        ///////////////////////////////////////////
        
        $commFilter = '';
        if(sizeof($jsonResp->communityReference)>0){
            foreach($jsonResp->communityReference as $comm){
                $commFilter .= '{"Field":{"name":"commrid","operator":"EQUALS","value":"'.$comm->resourceId.'"}},';
            }
            if($commFilter!='') $commFilter = substr($commFilter, 0, strlen($commFilter)-1);
            
            $requestFilter = 'tableViewConfig={"TableViewConfig":{"Columns":[{"Column":{"fieldName":"createdOn"}},{"Column":{"fieldName":"termrid"}},{"Column":{"fieldName":"termsignifier"}},{"Column":{"fieldId":"00000000-0000-0000-0000-000000000202","fieldName":"Attr00000000000000000000000000000202"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202longExpr"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202rid"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldId":"00000000-0000-0000-0000-000000005016","fieldName":"userRole00000000000000000000000000005016rid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole00000000000000000000000000005016gender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole00000000000000000000000000005016fn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole00000000000000000000000000005016ln"}}],"name":"Role00000000000000000000000000005016"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole00000000000000000000000000005016grid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole00000000000000000000000000005016ggn"}}],"name":"Role00000000000000000000000000005016g"}},{"Column":{"fieldName":"statusname"}},{"Column":{"fieldName":"statusrid"}},{"Column":{"fieldName":"communityname"}},{"Column":{"fieldName":"commrid"}},{"Column":{"fieldName":"domainname"}},{"Column":{"fieldName":"domainrid"}},{"Column":{"fieldName":"concepttypename"}},{"Column":{"fieldName":"concepttyperid"}}],'.
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
                ',"Order":[{"Field":{"name":"termsignifier","order":"ASC"}}]}},"displayStart":0,"displayLength":'.$limit.'}}';

            $resp = $objCollibra->request(
                array(
                    'url'=>'output/csv-raw',
                    'post'=>true,
                    //'params'=>'tableViewConfig={"TableViewConfig":{"Columns":[{"Column":{"fieldName":"termrid"}},{"Column":{"fieldName":"termsignifier"}},{"Column":{"fieldId":"00000000-0000-0000-0000-000000000202","fieldName":"Attr00000000000000000000000000000202"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202longExpr"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202rid"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldId":"00000000-0000-0000-0000-000000005016","fieldName":"userRole00000000000000000000000000005016rid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole00000000000000000000000000005016gender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole00000000000000000000000000005016fn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole00000000000000000000000000005016ln"}}],"name":"Role00000000000000000000000000005016"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole00000000000000000000000000005016grid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole00000000000000000000000000005016ggn"}}],"name":"Role00000000000000000000000000005016g"}},{"Column":{"fieldName":"statusname"}},{"Column":{"fieldName":"statusrid"}},{"Column":{"fieldName":"communityname"}},{"Column":{"fieldName":"commrid"}},{"Column":{"fieldName":"domainname"}},{"Column":{"fieldName":"domainrid"}},{"Column":{"fieldName":"concepttypename"}},{"Column":{"fieldName":"concepttyperid"}}],"Resources":{"Term":{"Id":{"name":"termrid"},"Signifier":{"name":"termsignifier"},"StringAttribute":[{"Value":{"name":"Attr00000000000000000000000000000202"},"LongExpression":{"name":"Attr00000000000000000000000000000202longExpr"},"Id":{"name":"Attr00000000000000000000000000000202rid"},"labelId":"00000000-0000-0000-0000-000000000202"}],"Member":[{"User":{"Gender":{"name":"userRole00000000000000000000000000005016gender"},"FirstName":{"name":"userRole00000000000000000000000000005016fn"},"Id":{"name":"userRole00000000000000000000000000005016rid"},"LastName":{"name":"userRole00000000000000000000000000005016ln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016sig"},"name":"Role00000000000000000000000000005016","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016rid"}},"roleId":"00000000-0000-0000-0000-000000005016"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016g"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016grid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005016ggn"},"Id":{"name":"groupRole00000000000000000000000000005016grid"}},"roleId":"00000000-0000-0000-0000-000000005016"}],"Status":{"Signifier":{"name":"statusname"},"Id":{"name":"statusrid"}},"Vocabulary":{"Community":{"Name":{"name":"communityname"},"Id":{"name":"commrid"}},"Name":{"name":"domainname"},"Id":{"name":"domainrid"}},"ConceptType":[{"Signifier":{"name":"concepttypename"},"Id":{"name":"concepttyperid"}}],"Filter":{"AND":[{"AND":[{"Field":{"name":"commrid","operator":"EQUALS","value":"5ae5c05f-b85b-4695-9627-3c08c3fd818e"}}]},{"OR":[{"Field":{"name":"termsignifier","operator":"INCLUDES","value":"'.$query.'"}},{"Field":{"name":"Attr00000000000000000000000000000202","operator":"INCLUDES","value":"'.$query.'"}}]}]},"Order":[{"Field":{"name":"termsignifier","order":"ASC"}}]}},"displayStart":0,"displayLength":25}}'
                    'params'=>$requestFilter
                )
            );

            $arrResp = array_map('self::str_getcsv2', explode("\n", $resp));
        }
        
        //print_r($arrResp);
        //exit;
        return $arrResp;
    }
}