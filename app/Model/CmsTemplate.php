<?php

class CmsTemplate extends AppModel {

    public $validate = [
        'title' => [
            'rule' => 'notBlank'
        ]
    ];

}
