<?php

class AdminController extends AppController {
    public $helpers = array('Html', 'Form');
    
    function beforeFilter() {
        parent::beforeFilter();
        $this->layout = 'admin';
        if(!$this->Session->read('userID') || $this->Session->read('userIP')!=$_SERVER["REMOTE_ADDR"]){
            if($this->request->params['action'] != 'login'){
                header('location: /admin/login');
                exit;
            }
        }
    }
    
    public function index(){
        /*$this->layout = 'default';
        $this->render('login');*/
        header('Location: /admin/login');
        exit;
    }
    
    public function login(){
        if ($this->request->is('post')) {
            $username = $this->request->data['login']['Username'];
            $pass = $this->request->data['login']['Password'];
            
            $this->loadModel('CmsUsers');
            $objCmsUser = new CmsUsers();
            $user = $objCmsUser->authUser($username, $pass);
            if(sizeof($user)>0){
                $this->Session->write('userID', $user['CmsUsers']['id']);
                $this->Session->write('userIP', $_SERVER["REMOTE_ADDR"]);
                header('location: /');
                exit;
            }else{
                $this->Session->setFlash('Incorrect Login', 'default', array('class' => 'error'));
                $this->Session->delete('userID');
            }
        }
        $this->layout = 'default';
    }
    
    public function logout(){
        $this->Session->delete('userID');
        $this->Session->delete('userIP');
        header('location: /admin/login');
        exit;
    }
    
    public function managepages(){
        $this->loadModel('CmsPage');
        $objCmsPage = new CmsPage();
        $this->set('pageList', $objCmsPage->listAdminPages());
    }
    
    public function editpage($id=null){
        // update page data
        if ($this->request->data) {
            $this->loadModel('CmsPage');
            $objCmsPage = new CmsPage();
            $page = $objCmsPage->findById($id);
            if (!$page) {
                throw new NotFoundException(__('Invalid page'));
            }

            if ($this->request->is(array('post', 'put'))) {
               $objCmsPage->id = $id;
                if ($objCmsPage->save($this->request->data)) {
                    $this->Session->setFlash(__('The page has been updated.'));
                    //return $this->redirect(array('action' => 'index'));
                }else{
                    $this->Session->setFlash('Unable to update the page', 'default', array('class' => 'error'));
                }
            }
        }
        
        App::uses('Helpers', 'Model');
        $pageID = 0;
        if(sizeof($this->request->params['pass'])>0){
            $pageID = Helpers::getInt($this->request->params['pass'][0]);
        }

        $this->loadModel('CmsPage');
        $objCmsPage = new CmsPage();
        if($pageID!=0){
            $page = $objCmsPage->loadPage($pageID);
            if(!$page){
                header('location: /admin/managepages');
            }else{
                $cmsPage = $page['CmsPage'];
            }
        }else{
            $page = null;
            $cmsPage = null;
        }

        //$this->loadModel('CmsTemplate');
        //$objCmsTemplate = new CmsTemplate();        

        $this->set('pageList', $objCmsPage->listAdminPages());
        $this->set('page', $cmsPage);
        //$this->set('templates', $objCmsTemplate->find('all'));
        $this->request->data = $page;
    }
    
    public function addpage(){
        App::uses('Helpers', 'Model');
        $pageID = 0;
        $this->loadModel('CmsPage');
        $objCmsPage = new CmsPage();
        
        if(sizeof($this->request->params['pass'])>0){
            $pageID = Helpers::getInt($this->request->params['pass'][0]);
            $page = $objCmsPage->loadPage($pageID);
            $cmsPage = $page['CmsPage'];
        }else{
            $this->Session->setFlash('Invalid page', 'default', array('class' => 'error'));
            $page = null;
            $cmsPage = null;
        }
        
        // update page data
        if ($this->request->is('post') && $pageID!=0) {
            $objCmsPage->create();
            if ($objCmsPage->save($this->request->data)) {
                $this->Session->setFlash(__('Your page has been saved.'));
                //return $this->redirect(array('action' => 'index'));
            }else{
                $this->Session->setFlash('Unable to save page', 'default', array('class' => 'error'));
            }
        }
        
        $this->set('pageList', $objCmsPage->listAdminPages());
        $this->set('page', $cmsPage);
        $this->set('parentID', $pageID);
        $this->set('rank', $this->getNextRank($pageID));
    }
    
    public function deletepage($id = null) {
        $this->loadModel('CmsPage');
        $objCmsPage = new CmsPage();
        if ($objCmsPage->delete($id) && $id!=1) {
            $this->Session->setFlash(
                __('The page has been deleted.', h($id))
            );
        } else {
            $this->Session->setFlash(
                __('The page could not be deleted.', h($id))
            );
        }

        return $this->redirect(array('action' => 'managepages'));
    }
    
    public function manageusers(){
        $this->loadModel('CmsUsers');
        $objCmsUsers = new CmsUsers();
        $this->set('users', $objCmsUsers->listAdminUsers());
    }
    
    public function edituser($id=null){
        $this->loadModel('CmsUsers');
        
        // update page data
        if ($this->request->data) {
            $objCmsUser = new CmsUsers();
            $user = $objCmsUser->findById($id);
            
            if (!$user) {
                throw new NotFoundException(__('Invalid user'));
            }else{
                if ($this->request->is(array('post', 'put'))) {
                    $data = $this->request['data']['CmsUsers'];
                    $newUsername = $data['username'];
                    $newPassword = $data['password'];
                    $newActive = $data['active'];
                        
                    if($newUsername!=$user['CmsUsers']['username'] && $objCmsUser->userExists($this->request['data']['CmsUsers']['username'])){
                        $this->Session->setFlash('This user already exists.', 'default', array('class' => 'error'));
                    }else{
                        App::uses('Bcrypt', 'Model');
                        $objCmsUser->id = $id;
                        $objCmsUser->set('username', $newUsername);
                        $objCmsUser->set('active', $newActive);
                        if($newPassword != 'p#uphukuD-3aP4a&e#hU'){
                            $objCmsUser->set('password', Bcrypt::hash($newPassword));
                        }
                        if ($objCmsUser->save()) {
                            $this->Session->setFlash(__('This user has been updated.'));
                        }else{
                            $this->Session->setFlash('Unable to update this user.', 'default', array('class' => 'error'));
                        }
                    }
                }
            }
        }
        
        App::uses('Helpers', 'Model');
        $userID = 0;
        if(sizeof($this->request->params['pass'])>0){
            $userID = Helpers::getInt($this->request->params['pass'][0]);
        }

        $objCmsUser = new CmsUsers();
        if($userID!=0){
            $user = $objCmsUser->findById($id);
            if(!$user){
                header('location: /admin/manageusers');
            }else{
                $cmsUser = $user['CmsUsers'];
            }
        }else{
            $user = null;
            $cmsUser = null;
        }
    
        $this->set('users', $objCmsUser->listAdminUsers());
        $this->set('user', $cmsUser);
        $this->request->data = $user;
    }
    
    public function adduser(){
        App::uses('Helpers', 'Model');
        App::uses('Bcrypt', 'Model');
        $this->loadModel('CmsUsers');
        $objCmsUser = new CmsUsers();
        
        // update user data
        if ($this->request->is('post')) {
            if($objCmsUser->userExists($this->request['data']['CmsUsers']['username'])){
                $this->Session->setFlash('This user already exists.', 'default', array('class' => 'error'));
            }else{
                $data = $this->request['data'];
                $data['CmsUsers']['password'] = Bcrypt::hash($data['CmsUsers']['password']);
                if ($objCmsUser->save($data)) {
                    $this->Session->setFlash(__('The user has been added.'));
                }else{
                    $this->Session->setFlash('Unable to save user.', 'default', array('class' => 'error'));
                }
            }
        }
        
        $this->set('users', $objCmsUser->listAdminUsers());
    }
    
    public function deleteuser($id = null) {
        $this->loadModel('CmsUsers');
        $objCmsUser = new CmsUsers();
        if ($objCmsUser->delete($id)) {
            $this->Session->setFlash(
                __('The user has been deleted.', h($id))
            );
        } else {
            $this->Session->setFlash(
                __('The user could not be deleted.', h($id))
            );
        }

        return $this->redirect(array('action' => 'manageusers'));
    }
    
    public function changerank(){
        if ($this->request->is('post')) {
            App::uses('Helpers', 'Model');
            $this->loadModel('CmsPage');
            $objCmsPage = new CmsPage();
            
            $arrPageData = $this->request['data']['pageData'];
            $arrPages = explode("||", $arrPageData);

            $rank = 2;
            $prevParentID = 0;
            foreach($arrPages as $page){
                $arrPageIDs = explode("::", $page);
                $parentID = Helpers::getInt($arrPageIDs[2]);
                $pageID = Helpers::getInt($arrPageIDs[0]);
                $objCmsPage->query("UPDATE cms_pages SET parentID=".$parentID.",rank=".$rank." WHERE id=".$pageID);
                $rank++;
            }

            //update home page's rank to 1
            $objCmsPage->query("UPDATE cms_pages SET rank=1 WHERE id=1");
        }
        exit;
    }
    
    private function getNextRank($parentID){
        $this->loadModel('CmsPage');
        $objCmsPage = new CmsPage();
        $results = $objCmsPage->query("SELECT MAX(rank) as rank FROM cms_pages WHERE parentID=".$parentID);
        $nextRank = 0;
        foreach($results as $result){
            $nextRank  = $result[0]['rank']+1;
        }
        return $nextRank;
    }
}