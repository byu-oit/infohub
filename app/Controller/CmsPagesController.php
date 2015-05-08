<?php

class CmsPagesController extends AppController {
    //public $helpers = array('Html', 'Form');
    
    public function index() {
        if(sizeof($this->request->params['pass'])>0){
            $pageSlug = $this->request->params['pass'][0];
        }else{
            $pageSlug = 'index';
        }
        
        // load page based on slug
        $page = $this->CmsPage->find('all', array(
            'conditions'=>array('slug'=>$pageSlug),
            'limit'=>1
        ));
        
        if(sizeof($page[0])==0){
            // redirect user to home page if page is not found
            header('location: /');
            exit;
        }else{
            // put page into variable to be passed on to view
            $page = $page[0]['CmsPage'];
        }
        
        // load template file
        App::uses('CmsTemplate', 'Model');
        $objTemplates = new CmsTemplate();
        $templateResults = $objTemplates->find('all', array(
            'conditions'=>array('id'=>$page['templateID']),
            'fields'=>'file'
        ));
        $templateFile = str_replace('.ctp', '', $templateResults[0]['CmsTemplate']['file']);
        
        $this->loadModel('CmsPage');
        $objCms = new CmsPage();
        $pageNav = $objCms->listPages(0,1);
        $page['body'] = $objCms->loadCmsBody($page['id'], $page['body'], $this->viewVars['isAdmin']);
        
        $this->set('page', $page);
        $this->set('pageNav', $pageNav);
        $this -> render('/Cmspages/'.$templateFile);
            
        //$this->set('posts', $this->CmsPage->find('all'));
        //print_r();
        //exit;
    }

    public function view($id = null) {
        if (!$id) {
            throw new NotFoundException(__('Invalid post'));
        }

        $post = $this->Post->findById($id);
        if (!$post) {
            throw new NotFoundException(__('Invalid post'));
        }
        $this->set('post', $post);
    }
    
    public function updatePage(){
        if ($this->request->data) {
            App::uses('Helpers', 'Model');
            $pgID = Helpers::getInt($this->request->data['pgID']);
            $pgBody = $this->request->data['pgBody'];
            
            $this->loadModel('CmsPage');
            $objCmsPage = new CmsPage();
            $page = $objCmsPage->findById($pgID);
            //print_r($page['CmsPage']['body']);
            //print_r($page);exit;
            if (!$page) {
                throw new NotFoundException(__('Invalid page'));
            }else{
                $objCmsPage->id = $pgID;
                $objCmsPage->set('body', $pgBody);
                $objCmsPage->save();
            }
        }
        exit;
    }
    
    public function update($id = null) {
        if (!$id) {
            throw new NotFoundException(__('Invalid post'));
        }
        
        $page = $this->CmsPage->findById($id);
        if (!$page) {
            throw new NotFoundException(__('Invalid post'));
        }

        if ($this->request->is(array('post', 'put'))) {
           $this->CmsPage->id = $id;
            if ($this->CmsPage->save($this->request->data)) {
                $this->Session->setFlash(__('Your post has been updated.'));
                return $this->redirect(array('controller'=>'admin', 'action' => 'pagemanager', $id.'#pg'.$id));
            }
            $this->Session->setFlash(__('Unable to update your post.'));
        }

        if (!$this->request->data) {
            $this->request->data = $page;
        }
    }
    
    /*public function edit($id = null) {
        if (!$id) {
            throw new NotFoundException(__('Invalid post'));
        }

        $post = $this->Post->findById($id);
        if (!$post) {
            throw new NotFoundException(__('Invalid post'));
        }

        if ($this->request->is(array('post', 'put'))) {
            $this->Post->id = $id;
            if ($this->Post->save($this->request->data)) {
                $this->Session->setFlash(__('Your post has been updated.'));
                return $this->redirect(array('action' => 'index'));
            }
            $this->Session->setFlash(__('Unable to update your post.'));
        }

        if (!$this->request->data) {
            $this->request->data = $post;
        }
    }
    
    public function delete($id) {
        if ($this->request->is('get')) {
            throw new MethodNotAllowedException();
        }

        if ($this->Post->delete($id)) {
            $this->Session->setFlash(
                __('The post with id: %s has been deleted.', h($id))
            );
        } else {
            $this->Session->setFlash(
                __('The post with id: %s could not be deleted.', h($id))
            );
        }

        return $this->redirect(array('action' => 'index'));
    }*/
}