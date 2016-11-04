<?php

class ISARequests extends AppModel{
    public $useTable = 'isarequests';
    
    public $validate = array(
        'personId' => array(
            'rule' => 'notEmpty'
        ),
        'processId' => array(
            'rule' => 'notEmpty'
        ),
        'collibraUser' => array(
            'rule' => 'notEmpty'
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