<?php

class ISARequests extends AppModel{
    public $useTable = 'isarequests';
    
    public $validate = array(
        'netid' => array(
            'rule' => 'notEmpty'
        ),
        'processid' => array(
            'rule' => 'notEmpty'
        ),
        'request' => array(
            'rule' => 'notEmpty'
        ),
        'collibraUser' => array(
            'rule' => 'notEmpty'
        )
    );
    
    public function loadRequestsByUser($netID){
        App::uses('Helpers', 'Model');
        $netID = Helpers::getInt($netID);
        $requests = '';
        $requests = $this->find('all', array(
            'conditions'=>array('netid'=>$netID),
            'order'=>array('date'=>'DESC')
        ));
        return $requests;
    }
}