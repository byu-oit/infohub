<?php

class CmsUsers extends AppModel {
    public $listPageHtml = '';

    public $validate = [
        'username' => [
            'rule' => 'notBlank',
            'massage' => 'Please enter a username',
        ],
        'password' => [
            'rule' => 'notBlank',
            'massage' => 'Please enter a password',
        ]
    ];

    public function authUser($username, $pass){
        App::uses('Bcrypt', 'Model');
        $cmsUser = '';
        $cmsUser = $this->find('first', [
            'conditions'=>['username'=>$username, 'active'=>'1']
        ]);
        if(sizeof($cmsUser)>0){
            if(!Bcrypt::check($pass, $cmsUser['CmsUsers']['password'])){
                $cmsUser = [];
            }
        }
        return $cmsUser;
    }

    public function loadUser($userID){
        App::uses('Bcrypt', 'Model');
        $cmsUser = '';
        $cmsUser = $this->find('first', [
            'conditions'=>['username'=>$username]
        ]);
        if(sizeof($cmsUser)>0){
            if(!Bcrypt::check($pass, $cmsUser['CmsUsers']['password'])){
                $cmsUser = [];
            }
        }
        return $cmsUser;
    }

    public function userExists($username){
        $cmsUser = $this->find('first', [
            'conditions'=>['username'=>$username]
        ]);
        if(sizeof($cmsUser)>0){
            return true;
        }
        return false;
    }

    public function listAdminUsers(){
        $cmsUsers = $this->find('all', [
            'order'=>['username'=>'ASC']
        ]);
        return $cmsUsers;
    }

}
