<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class DATABASE_CONFIG {

    public $default = array(
        'datasource' => 'Database/Mysql',
        'persistent' => false,
        'host' => 'REQUIRED',
        'login' => 'REQUIRED',
        'password' => 'REQUIRED',
        'database' => 'REQUIRED',
    );

    public $byuApi = array(
        'datasource' => 'DataSource',
        'host' => 'api.byu.edu',
        'api_key' => 'REQUIRED',
        'shared_secret' => 'REQUIRED');

    public $collibra = array(
        'datasource' => 'DataSource',
        'url'       =>  'https://byu-dev.collibra.com/rest/latest/',
        'username'  => 'REQUIRED',
        'password'  => 'REQUIRED'
    );
}