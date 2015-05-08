<?php

class CmsUsers extends AppModel {
    public $listPageHtml = '';
    
    public $validate = array(
        'username' => array(
            'rule' => array('email'),
            'massage' => 'Please enter a valid email address',
        ),
        'password' => array(
            'rule' => 'notEmpty',
            'massage' => 'Please enter a password',
        )
    );
    
    public function authUser($username, $pass){
        App::uses('Helpers', 'Model');
        App::uses('Bcrypt', 'Model');
        $cmsUser = '';
        $cmsUser = $this->find('first', array(
            'conditions'=>array('username'=>$username, 'active'=>'1')
        ));
        if(sizeof($cmsUser)>0){
            if(!Bcrypt::check($pass, $cmsUser['CmsUsers']['password'])){
                $cmsUser = array();
            }
        }
        return $cmsUser;
    }
    
    public function loadUser($userID){
        App::uses('Helpers', 'Model');
        App::uses('Bcrypt', 'Model');
        $cmsUser = '';
        $cmsUser = $this->find('first', array(
            'conditions'=>array('username'=>$username)
        ));
        if(sizeof($cmsUser)>0){
            if(!Bcrypt::check($pass, $cmsUser['CmsUsers']['password'])){
                $cmsUser = array();
            }
        }
        return $cmsUser;
    }
    
    public function userExists($username){
        $cmsUser = $this->find('first', array(
            'conditions'=>array('username'=>$username)
        ));
        if(sizeof($cmsUser)>0){
            return true;
        }
        return false;
    }
    
    public function listAdminUsers(){
        $cmsUsers = $this->find('all', array(
            'order'=>array('username'=>'ASC')
        ));
        return $cmsUsers;
    }

}