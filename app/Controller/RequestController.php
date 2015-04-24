<?php

class RequestController extends AppController {
    public $helpers = array('Html', 'Form');
    
    private static function sortUsers($a, $b){
        return strcmp($a->firstName, $b->firstName);
    }
    
    private static function sortTerms($a, $b){
        return strcmp($a->signifier, $b->signifier);
    }
    
    public function success() {
    }
    
    public function submit() {
        $this->autoRender = false;
        
        if(!$this->request->is('post')){
            header('location: /search');
            exit;
        }
        
        ////////////////////////////////////////////////
        // TO DO: validate post data
        ////////////////////////////////////////////////
        
        $this->loadModel('CollibraAPI');
        $objCollibra = new CollibraAPI();
        
        // create guest user to use for submitting request
        $guestUserResp = $objCollibra->request(
            array(
                'url'=>'user/guest',
                'post'=>true,
                'params'=>'firstName=Jon&lastName=Doe&email=anava@summitslc.com'
            )
        );
        $guestUserResp = json_decode($guestUserResp);
        $guestID = $guestUserResp->resourceId;
        //echo ($guestID);
        //exit;
        
        
        $guestID = '';
        //print_r($this->request->data);
        $postData = 'user='.$guestID;
        foreach($this->request->data as $key => $val){
            if($key!='fname' && $key!='lname' && $key!='phone' && $key!='email' && $key!='company' && $key!='terms' && $key!='requestSubmit')
            $postData .= '&'.$key.'='.$val;
        }
        
        $formResp = $objCollibra->request(array(
            'url'=>'workflow/c9b528d6-67a8-458a-902a-d072e09fea19/start',
            'post'=>true,
            'params'=>$postData
        ));
        $formResp = json_decode($formResp);
        $processID = $formResp->startWorkflowResponses[0]->processInstanceId;
        
        // store user's request
        $objCollibra->query("INSERT INTO requests (netid, processid) VALUES('".$_SESSION['netid']."', '".$processID."')");
        
        header('location: /request/success');
        exit;
    }
    
    public function index() {
        ///////////////////////////////////////////////////
        // TO DO: make sure user is logged in via BYU API
        ///////////////////////////////////////////////////
        
        // make sure a term has been passed to this page
        if(!isset($this->request->params['pass'][0])){
            header('location: /search');
            exit;
        }
        
        $termID = $this->request->params['pass'][0];
        $this->loadModel('CollibraAPI');
        $objCollibra = new CollibraAPI();
        
        $requestFilter = '{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"createdOn"}},{"Column":{"fieldName":"termrid"}},{"Column":{"fieldName":"termsignifier"}},{"Column":{"fieldId":"00000000-0000-0000-0000-000000000202","fieldName":"Attr00000000000000000000000000000202"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202longExpr"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202rid"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldId":"00000000-0000-0000-0000-000000005016","fieldName":"userRole00000000000000000000000000005016rid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole00000000000000000000000000005016gender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole00000000000000000000000000005016fn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole00000000000000000000000000005016ln"}}],"name":"Role00000000000000000000000000005016"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole00000000000000000000000000005016grid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole00000000000000000000000000005016ggn"}}],"name":"Role00000000000000000000000000005016g"}},{"Column":{"fieldName":"statusname"}},{"Column":{"fieldName":"statusrid"}},{"Column":{"fieldName":"communityname"}},{"Column":{"fieldName":"commrid"}},{"Column":{"fieldName":"domainname"}},{"Column":{"fieldName":"domainrid"}},{"Column":{"fieldName":"concepttypename"}},{"Column":{"fieldName":"concepttyperid"}}],'.
            '"Resources":{"Term":{"CreatedOn":{"name":"createdOn"},"Id":{"name":"termrid"},"Signifier":{"name":"termsignifier"},"StringAttribute":[{"Value":{"name":"Attr00000000000000000000000000000202"},"LongExpression":{"name":"Attr00000000000000000000000000000202longExpr"},"Id":{"name":"Attr00000000000000000000000000000202rid"},"labelId":"00000000-0000-0000-0000-000000000202"}],"Member":[{"User":{"Gender":{"name":"userRole00000000000000000000000000005016gender"},"FirstName":{"name":"userRole00000000000000000000000000005016fn"},"Id":{"name":"userRole00000000000000000000000000005016rid"},"LastName":{"name":"userRole00000000000000000000000000005016ln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016sig"},"name":"Role00000000000000000000000000005016","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016rid"}},"roleId":"00000000-0000-0000-0000-000000005016"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016g"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016grid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005016ggn"},"Id":{"name":"groupRole00000000000000000000000000005016grid"}},"roleId":"00000000-0000-0000-0000-000000005016"}],"Status":{"Signifier":{"name":"statusname"},"Id":{"name":"statusrid"}},"Vocabulary":{"Community":{"Name":{"name":"communityname"},"Id":{"name":"commrid"}},"Name":{"name":"domainname"},"Id":{"name":"domainrid"}},"ConceptType":[{"Signifier":{"name":"concepttypename"},"Id":{"name":"concepttyperid"}}],'.
            '"Filter":{'.
            '   "AND":['.
            '        {'.
            '           "OR":['.
            '              {'.
            '                 "Field":{'.
            '                    "name":"termrid",'.
            '                    "operator":"EQUALS",'.
            '                    "value":"'.$termID.'"'.
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
        $termResp = $objCollibra->request(
            array(
                'url'=>'output/data_table',
                //'url'=>'output/csv-raw',
                'post'=>true,
                'json'=>true,
                'params'=>$requestFilter
            )
        );
        $termResp = json_decode($termResp);
        
        $formResp = $objCollibra->request(array('url'=>'workflow/c9b528d6-67a8-458a-902a-d072e09fea19/form/start'));
        $formResp = json_decode($formResp);
        
        $userResp = $objCollibra->request(array('url'=>'user/all'));
        $userResp = json_decode($userResp);
        usort($userResp->user, 'self::sortUsers');
        
        $siblingTerms = $objCollibra->request(array('url'=>'vocabulary/'.$termResp->aaData[0]->domainrid.'/terms'));
        $siblingTerms = json_decode($siblingTerms);
        usort($siblingTerms->termReference, 'self::sortTerms');
        
        // start building description of terms needed
        $dataNeeded = $termResp->aaData[0]->communityname.'/'.$termResp->aaData[0]->domainname.': '.$termResp->aaData[0]->termsignifier.', ';
        
        // get all terms selected from search page
        $arrTermsSelected = array();
        if(isset($this->request->data['terms'])){
            $termsSelected = $this->request->data['terms'];
            for($i=0; $i<sizeof($termsSelected); $i++){
                $arrTermsSelected[$termsSelected[$i]] = 1;
                
                // build description of terms needed
                foreach($siblingTerms->termReference as $st){
                    if($st->resourceId == $termsSelected[$i]){
                        $dataNeeded .= $st->signifier.', ';
                    }
                }
            }
        }
        if($dataNeeded != ''){
            $dataNeeded = substr($dataNeeded, 0, strlen($dataNeeded)-2);
        }
        
        $this->set('formFields', $formResp);
        $this->set('sponsors', $userResp);
        $this->set('termDeatils', $termResp);
        $this->set('siblingTerms', $siblingTerms);
        $this->set('termsSelected', $arrTermsSelected);
        $this->set('dataNeeded', $dataNeeded);
    }
}