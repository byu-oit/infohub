<?php

class MyaccountController extends AppController {
    private static function sortUsers($a, $b){
        return strcmp($a->firstName, $b->firstName);
    }
    
    public function index() {
        ///////////////////////////////////////////////////
        // TO DO: make sure user is logged in via BYU API
        ///////////////////////////////////////////////////
        
        // load all submitted requests
        $this->loadModel('ISARequests');
        $isaReq = new ISARequests();
        $req = $isaReq->loadRequestsByUser($_SESSION['netid']);
        
        // load all collibra users for sponsor drop down
        $this->loadModel('CollibraAPI');
        $objCollibra = new CollibraAPI();
        $userResp = $objCollibra->request(array('url'=>'user/all'));
        $userResp = json_decode($userResp);
        //usort($userResp->user, 'self::sortUsers');
        
        $this->set('requests', $req);
        $this->set('username', $_SESSION['username']);
        $this->set('dept', $_SESSION['dept']);
        $this->set('role', $_SESSION['role']);
    }
    public function success() {
        
    }
}