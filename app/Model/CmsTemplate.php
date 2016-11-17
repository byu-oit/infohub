<?php

class CmsTemplate extends AppModel {
    
    public $validate = array(
        'title' => array(
            'rule' => 'notBlank'
        )
    );

}