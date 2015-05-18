<?php

class RequestController extends AppController {
    public $helpers = array('Html', 'Form');
    
    function beforeFilter() {
        $this->initBeforeFilter();
    }
    
    private static function sortUsers($a, $b){
        return strcmp($a->firstName, $b->firstName);
    }
    
    private static function sortTerms($a, $b){
        return strcmp($a->signifier, $b->signifier);
    }
    
    private static function sortTermsByDomain($a, $b){
        return strcmp($a->domainname, $b->domainname);
    }
    
    public function addToQueue() {
        $this->autoRender = false;
        if($this->request->is('post')){
            $newTermsAdded = 0;
            $arrTerms = $this->request->data['t'];
            $arrTermIDs = $this->request->data['id'];
            
            $arrQueue = array();
            if(isset($_COOKIE['queue'])) {
                $arrQueue = unserialize($_COOKIE['queue']);
            }
            
            for($i=0; $i<sizeof($arrTerms); $i++){
                $term = $arrTerms[$i];
                $termID = $arrTermIDs[$i];
                $exists = false;
                
                for($j=0; $j<sizeof($arrQueue); $j++){
                    if($arrQueue[$j][1] == $termID){
                        $exists = true;
                        break;
                    }
                }

                if(!$exists){
                    $newTermsAdded++;
                    array_push($arrQueue, array($term, $termID));
                }
            }
        
            setcookie('queue', serialize($arrQueue), time() + (60*60*24*90), "/"); // 90 days
            echo $newTermsAdded;
        }
    }
    
    /*public function removeFromQueue() {
        $this->autoRender = false;
        if($this->request->is('post')){
            $termID = $this->request->data['id'];
            if(isset($_COOKIE['queue'])) {
                $arrQueue = unserialize($_COOKIE['queue']);
                for($i=0; $i<sizeof($arrQueue); $i++){
                    if($arrQueue[$i][1] == $termID){
                        array_splice($arrQueue, $i, 1);
                        break;
                    }
                }

                setcookie('queue', serialize($arrQueue), time() + (60*60*24*90), "/"); // 90 days
            }
        }
    }*/
    
    public function listQueue() {
        $this->autoRender = false;
        $listHTML = '';
        $responseHTML = '';
        $emptyQueue = true;
        
        if(isset($_COOKIE['queue'])) {
            $emptyQueue = false;
            $arrQueue = unserialize($_COOKIE['queue']);
            for($j=0; $j<sizeof($arrQueue); $j++){
                $listHTML .= '<li>'.$arrQueue[$j][0].'</li>';
            }
        }else{
            $listHTML = 'Your queue is empty.';
        }
        $responseHTML=  '<h3>Request Queue</h3>'.
            '<a class="close" href="javascript: hideRequestQueue()">X</a>'.
            '<div class="arrow"></div>'.
            '<ul>'.
            $listHTML.//'    <li>Information Domain </li>'.//<a class="delete" href=""><img src="/img/icon-delete.gif" width="11" /></a>
            '</ul>';
        if(!$emptyQueue){
            $responseHTML .= '<a class="btn-orange" href="/request">Submit Request</a>';
        }
        echo $responseHTML;
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
                'params'=>'firstName='.$this->request->data['fname'].'&lastName='.$this->request->data['lname'].'&email='.$this->request->data['email'].''
            )
        );
        $guestUserResp = json_decode($guestUserResp);
        $guestID = $guestUserResp->resourceId;
        
        $guestID = '';
        //print_r($this->request->data);
        $postData = 'user='.$guestID;
        foreach($this->request->data as $key => $val){
            if($key!='fname' && $key!='lname' && $key!='phone' && $key!='email' && $key!='company' && $key!='terms' && $key!='requestSubmit' && $key!='collibraUser')
            $postData .= '&'.$key.'='.$val;
        }
        
        $formResp = $objCollibra->request(array(
            'url'=>'workflow/'.Configure::read('isaWorkflow').'/start',
            'post'=>true,
            'params'=>$postData
        ));
        //$formResp = json_decode($formResp);
        print_r($formResp);
        exit;
        $processID = $formResp->startWorkflowResponses[0]->processInstanceId;
        
        // store user's request
        $this->loadModel('ISARequests');
        $isaReq = new ISARequests();
        $isaReq->create();
        $isaReq->set('netid', $_SESSION['netid']);
        $isaReq->set('processid', $processID);
        $isaReq->set('request', $this->request->data['dataNeeded']);
        $isaReq->set('collibraUser', $this->request->data['collibraUser']);
        $isaReq->save();
        //$objCollibra->query("INSERT INTO isarequests (netid, processid, request) VALUES('".$_SESSION['netid']."', '".$processID."', '".$."')");
        
        header('location: /request/success');
        exit;
    }
    
    public function index() {
        // make sure user is logged in via BYU API
        //require_once $_SERVER['DOCUMENT_ROOT'].'/CAS-1.3.3/config.php';
        //require_once $phpcas_path.'/CAS.php';
        //phpCAS::client(CAS_VERSION_2_0, $cas_host, $cas_port, $cas_context);
        // phpCAS::setCasServerCACert($cas_server_ca_cert_path);
        phpCAS::setNoCasServerValidation();
        if(phpCAS::isAuthenticated()){
            $this->set('casAuthenticated', true);
        }else{
            phpCAS::forceAuthentication();
        }
        
        // make sure terms have been added to the users's queue
        if(!isset($_COOKIE['queue'])) {
            header('location: /search');
            exit;
        }
        
        //$termID = $this->request->params['pass'][0];
        $this->loadModel('CollibraAPI');
        $objCollibra = new CollibraAPI();
        
        $requestFilter = '{"TableViewConfig":{"Columns":[{"Column":{"fieldName":"createdOn"}},{"Column":{"fieldName":"termrid"}},{"Column":{"fieldName":"termsignifier"}},{"Column":{"fieldId":"00000000-0000-0000-0000-000000000202","fieldName":"Attr00000000000000000000000000000202"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202longExpr"}},{"Column":{"fieldName":"Attr00000000000000000000000000000202rid"}},{"Group":{"Columns":[{"Column":{"label":"Steward User ID","fieldId":"00000000-0000-0000-0000-000000005016","fieldName":"userRole00000000000000000000000000005016rid"}},{"Column":{"label":"Steward Gender","fieldName":"userRole00000000000000000000000000005016gender"}},{"Column":{"label":"Steward First Name","fieldName":"userRole00000000000000000000000000005016fn"}},{"Column":{"label":"Steward Last Name","fieldName":"userRole00000000000000000000000000005016ln"}}],"name":"Role00000000000000000000000000005016"}},{"Group":{"Columns":[{"Column":{"label":"Steward Group ID","fieldName":"groupRole00000000000000000000000000005016grid"}},{"Column":{"label":"Steward Group Name","fieldName":"groupRole00000000000000000000000000005016ggn"}}],"name":"Role00000000000000000000000000005016g"}},{"Column":{"fieldName":"statusname"}},{"Column":{"fieldName":"statusrid"}},{"Column":{"fieldName":"communityname"}},{"Column":{"fieldName":"commrid"}},{"Column":{"fieldName":"domainname"}},{"Column":{"fieldName":"domainrid"}},{"Column":{"fieldName":"concepttypename"}},{"Column":{"fieldName":"concepttyperid"}}],'.
            '"Resources":{"Term":{"CreatedOn":{"name":"createdOn"},"Id":{"name":"termrid"},"Signifier":{"name":"termsignifier"},"StringAttribute":[{"Value":{"name":"Attr00000000000000000000000000000202"},"LongExpression":{"name":"Attr00000000000000000000000000000202longExpr"},"Id":{"name":"Attr00000000000000000000000000000202rid"},"labelId":"00000000-0000-0000-0000-000000000202"}],"Member":[{"User":{"Gender":{"name":"userRole00000000000000000000000000005016gender"},"FirstName":{"name":"userRole00000000000000000000000000005016fn"},"Id":{"name":"userRole00000000000000000000000000005016rid"},"LastName":{"name":"userRole00000000000000000000000000005016ln"}},"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016sig"},"name":"Role00000000000000000000000000005016","Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016rid"}},"roleId":"00000000-0000-0000-0000-000000005016"},{"Role":{"Signifier":{"hidden":"true","name":"Role00000000000000000000000000005016g"},"Id":{"hidden":"true","name":"roleRole00000000000000000000000000005016grid"}},"Group":{"GroupName":{"name":"groupRole00000000000000000000000000005016ggn"},"Id":{"name":"groupRole00000000000000000000000000005016grid"}},"roleId":"00000000-0000-0000-0000-000000005016"}],"Status":{"Signifier":{"name":"statusname"},"Id":{"name":"statusrid"}},"Vocabulary":{"Community":{"Name":{"name":"communityname"},"Id":{"name":"commrid"}},"Name":{"name":"domainname"},"Id":{"name":"domainrid"}},"ConceptType":[{"Signifier":{"name":"concepttypename"},"Id":{"name":"concepttyperid"}}],'.
            '"Filter":{'.
            '   "AND":['.
            '        {'.
            '           "OR":[';
        
        $arrQueue = unserialize($_COOKIE['queue']);
        for($i=0; $i<sizeof($arrQueue); $i++){
            $requestFilter .= '{"Field":{'.
                '   "name":"termrid",'.
                '   "operator":"EQUALS",'.
                '   "value":"'.$arrQueue[$i][1].'"'.
                '}},';
        }
        $requestFilter = substr($requestFilter, 0, strlen($requestFilter)-1);
        
        $requestFilter .= ']'.
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
        //usort($termResp->aaData, 'self::sortTermsByDomain');
        foreach ($termResp->aaData as $term) {
            $domains[]  = $term->domainname;
            $termNames[] = $term->termsignifier;
        }
        array_multisort($domains, SORT_ASC, $termNames, SORT_ASC, $termResp->aaData);
        //print_r($termResp);exit;
        
        // load form fields for ISA workflow
        $formResp = $objCollibra->request(array('url'=>'workflow/'.Configure::read('isaWorkflow').'/form/start'));
        $formResp = json_decode($formResp);
        
        // load all collibra users for sponsor drop down
        $userResp = $objCollibra->request(array('url'=>'user/all'));
        $userResp = json_decode($userResp);
        usort($userResp->user, 'self::sortUsers');
        
        // load all sibling terms
        //$siblingTerms = $objCollibra->request(array('url'=>'vocabulary/'.$termResp->aaData[0]->domainrid.'/terms'));
        //$siblingTerms = json_decode($siblingTerms);
        //usort($siblingTerms->termReference, 'self::sortTerms');
        
        // start building description of terms needed
        //$communityPath = $termResp->aaData[0]->communityname.'/'.$termResp->aaData[0]->domainname;
        //$dataNeeded = $termResp->aaData[0]->termsignifier.', ';
        
        // get all terms selected from search page
        /*$arrTermsSelected = array();
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
        }*/
        
        $this->set('formFields', $formResp);
        $this->set('sponsors', $userResp);
        $this->set('termDetails', $termResp);
        //$this->set('siblingTerms', $siblingTerms);
        //$this->set('termsSelected', $arrTermsSelected);
        //$this->set('communityPath', $communityPath);
        //$this->set('dataNeeded', $dataNeeded);
    }
}