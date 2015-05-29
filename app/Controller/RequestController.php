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
    
    public function removeFromQueue() {
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
    }
    
    public function getQueueJSArray() {
        $this->autoRender = false;
        $JS = '';
        
        if(isset($_COOKIE['queue'])) {
            $arrQueue = unserialize($_COOKIE['queue']);
            for($j=0; $j<sizeof($arrQueue); $j++){
                $JS .= ','.$arrQueue[$j][1];
            }
        }
        echo $JS;
    }
    
    public function listQueue() {
        $this->autoRender = false;
        $listHTML = '';
        $responseHTML = '';
        $emptyQueue = true;
        
        if(isset($_COOKIE['queue'])) {
            $emptyQueue = false;
            $arrQueue = unserialize($_COOKIE['queue']);
            if(sizeof($arrQueue)>=1){
                for($j=0; $j<sizeof($arrQueue); $j++){
                    $listHTML .= '<li id="requestItem'.$arrQueue[$j][1].'">'.$arrQueue[$j][0].'<a class="delete" onclick="return confirm(\'Are you sure you want to delete this item?\')" href="javascript:removeFromRequestQueue(\''.$arrQueue[$j][1].'\')"><img src="/img/icon-delete.gif" width="11" title="delete" /></a></li>';
                }
            }else{
                $emptyQueue = true;
                $listHTML = 'No request items found.';   
            }
        }else{
            $listHTML = 'No request items found.';
        }
        $responseHTML=  '<h3>Requested Items</h3>'.
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
    
    public function success() {
    }
    
    public function submit() {
        $this->autoRender = false;
        
        if(!$this->request->is('post')){
            header('location: /search');
            exit;
        }
        
        /*// make sure user is logged in via BYU API
        require_once $_SERVER['DOCUMENT_ROOT'].'/CAS-1.3.3/config.php';
        require_once $phpcas_path.'/CAS.php';
        phpCAS::client(CAS_VERSION_2_0, $cas_host, $cas_port, $cas_context);
        // phpCAS::setCasServerCACert($cas_server_ca_cert_path);
        phpCAS::setNoCasServerValidation();
        if(phpCAS::isAuthenticated()){
            $this->set('casAuthenticated', true);
            $netID = phpCAS::getUser();
        }else{
            phpCAS::forceAuthentication();
        }*/
        if(!phpCAS::isAuthenticated()){
            phpCAS::forceAuthentication();
        }
        
        ////////////////////////////////////////////////
        // TO DO: validate post data
        ////////////////////////////////////////////////
        
        $this->loadModel('CollibraAPI');
        $objCollibra = new CollibraAPI();
        
        $name = explode(' ',$this->request->data['name']);
        $firstName = $name[0];
        $lastName = '';
        if(sizeof($name)>1) $lastName = $name[1];
        $email = $this->request->data['email'];
        $phone = $this->request->data['phone'];
        $role = $this->request->data['role'];
        $dataRequested = '';
        
        // create guest user to use for submitting request
        /*$guestUserResp = $objCollibra->request(
            array(
                'url'=>'user/guest',
                'post'=>true,
                'params'=>'firstName='.$firstName.'&lastName='.$lastName.'&email='.$this->request->data['email'].''
            )
        );
        $guestUserResp = json_decode($guestUserResp);
        $guestID = $guestUserResp->resourceId;
        */
        
        $postData = '';//'user='.$guestID;
        foreach($this->request->data as $key => $val){
            if($key!='name' && $key!='phone' && $key!='email' && $key!='role' && $key!='terms' && $key!='requestSubmit' && $key!='collibraUser'){
                $postData .= '&'.$key.'='.$val;
            }
        }
        // add user's contact info to post
        $postData .= '&requesterName='.$firstName.' '.$lastName.
            '&requesterEmail='.$email.
            '&requesterPhone='.$phone.
            '&requesterRole='.$role;
        // add requested terms to post
        foreach($this->request->data['terms'] as $term){
            $postData .= '&informationElements='.$term;
            $dataRequested .= $term.',';
        }
        
        $formResp = $objCollibra->request(array(
            'url'=>'workflow/'.Configure::read('isaWorkflow').'/start',
            'post'=>true,
            'params'=>$postData
        ));
        
        $formResp = json_decode($formResp);
        //print_r($formResp);
        //exit;
        
        if(isset($formResp->startWorkflowResponses[0]->successmessage)){
            $processID = $formResp->startWorkflowResponses[0]->processInstanceId;
            
            // attempt to reindex source to make sure latest requests are displayed
            $objCollibra = new CollibraAPI();
            $resp = $objCollibra->request(
                array(
                    'url'=>'search/re-index',
                    'post'=>true
                )
            );
            
            // store user's request
            $this->loadModel('ISARequests');
            $isaReq = new ISARequests();
            $isaReq->create();
            $isaReq->set('processId', $processID);
            $isaReq->set('request', $dataRequested);
            $isaReq->set('personId', $this->request->data['requesterPersonId']);
            $isaReq->save();
            
            // clear items in queue
            setcookie('queue', '', time()-3600, "/");

            header('location: /request/success');
        }else{
            header('location: /request/?err=1');
        }
        exit;
    }
    
    public function index() {
        /*// make sure user is logged in via BYU API
        require_once $_SERVER['DOCUMENT_ROOT'].'/CAS-1.3.3/config.php';
        require_once $phpcas_path.'/CAS.php';
        phpCAS::client(CAS_VERSION_2_0, $cas_host, $cas_port, $cas_context);
        // phpCAS::setCasServerCACert($cas_server_ca_cert_path);
        phpCAS::setNoCasServerValidation();
        if(phpCAS::isAuthenticated()){
            $this->set('casAuthenticated', true);
            $netID = phpCAS::getUser();
        }else{
            phpCAS::forceAuthentication();
        }*/
        
        if(!phpCAS::isAuthenticated()){
            phpCAS::forceAuthentication();
        }else{
            $netID = phpCAS::getUser();
            $this->loadModel('BYUWS');
            $objBYUWS = new BYUWS();
            $byuUser = $objBYUWS->personalSummary($netID);
        }
        
        // make sure terms have been added to the users's queue
        if(!isset($_COOKIE['queue'])) {
            header('location: /search');
            exit;
        }
        
        // redirect if cookie is set but not empty
        $arrQueue = unserialize($_COOKIE['queue']);
        if(sizeof($arrQueue)<=0){
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
        /*$userResp = $objCollibra->request(array('url'=>'user/all'));
        $userResp = json_decode($userResp);
        usort($userResp->user, 'self::sortUsers');*/
        
        $this->set('formFields', $formResp);
        //$this->set('sponsors', $userResp);
        $this->set('termDetails', $termResp);
        //$this->set('byuUser', $byuUser);
        
        $psName = '';
        $psPhone = '';
        $psEmail = '';
        $psRole = '';
        $psDepartment = '';
        $psPersonID = $byuUser->identifiers->person_id;
        if(isset($byuUser->names->preferred_name)){
            $psName = $byuUser->names->preferred_name;
        }
        if(isset($byuUser->contact_information->work_phone)){
            $psPhone = $byuUser->contact_information->work_phone;
        }
        if(isset($byuUser->contact_information->email)){
            $psEmail = $byuUser->contact_information->email;
        }
        if(isset($byuUser->employee_information->employee_role)){
            $psRole = $byuUser->employee_information->employee_role;
        }
        if(isset($byuUser->employee_information->department)){
            $psDepartment = $byuUser->employee_information->department;
        }
        $this->set('psName', $psName);
        $this->set('psPhone', $psPhone);
        $this->set('psEmail', $psEmail);
        $this->set('psRole', $psRole);
        $this->set('psDepartment', $psDepartment);
        $this->set('psPersonID', $psPersonID);
        $this->set('submitErr', isset($this->request->query['err']));
    }
}