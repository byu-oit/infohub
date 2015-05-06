<?php

class AdminController extends AppController {
    public $helpers = array('Html', 'Form');
    
    function beforeFilter() {
        parent::beforeFilter();
        $this->layout = 'admin';
        if(!$this->Session->read('netid') && $this->request->params['action'] != 'login'){
            header('location: /admin/login');
            exit;
        }
    }
    
    public function index(){
        $this->layout = 'default';
    }
    
    public function login(){
        if ($this->request->is('post')) {
            $username = $this->request->data['login']['Username'];
            $pass = $this->request->data['login']['Password'];
            // BYU API
            $this->Session->delete('netid');
            $this->Session->write('netid', '1111');
            header('location: /');
            exit;
        }
        $this->layout = 'default';
    }
    
    public function managepages(){
        $this->loadModel('CmsPage');
        $objCmsPage = new CmsPage();
        $this->set('pageList', $objCmsPage->listPages());
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

        $this->set('pageList', $objCmsPage->listPages());
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
        
        $this->set('pageList', $objCmsPage->listPages());
        $this->set('page', $cmsPage);
        $this->set('parentID', $pageID);
        $this->set('rank', $this->getNextRank($pageID));
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
                $objCmsPage->query("UPDATE CMS_Pages SET ParentID=".$parentID.",Rank=".$rank." WHERE ID=".$pageID);
                $rank++;
            }

            //update home page's rank to 1
            $objCmsPage->query("UPDATE CMS_Pages SET Rank=1 WHERE ID=1");
        }
        exit;
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