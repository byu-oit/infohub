<?php

class CommonSearch extends AppModel {
    public $validate = array(
        'query' => array(
            'rule' => 'notEmpty'
        )
    );
}