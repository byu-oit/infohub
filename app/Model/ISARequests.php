<?php

class ISARequests extends AppModel{
    public $useTable = 'isarequests';
    
    public $validate = array(
        'personId' => array(
            'rule' => 'notBlank'
        ),
        'processId' => array(
            'rule' => 'notBlank'
        ),
        'collibraUser' => array(
            'rule' => 'notBlank'
        )
    );
    
    public function loadRequestsByUser($personID){
        $personID = intval($personID);
        $requests = $this->find('all', array(
            'conditions'=>array('personId'=>$personID),
            'order'=>array('date'=>'DESC')
        ));
        return $requests;
    }
}