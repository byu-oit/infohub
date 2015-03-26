<?php

class CmsPagesController extends AppController {
    //public $helpers = array('Html', 'Form');
    
    public function index() {
        
        App::uses('Cms', 'Model');
        $objCms = new Cms();
        $objCms->listPages();
        
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
        
        //print_r($templateFile);
        //echo '<hr>';
        //echo $page[0]['CmsPage']['title'];
        //exit;
        
        
        
        $this->set('page', $page);
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
    
    public function add() {
        if ($this->request->is('post')) {
            $this->Post->create();
            if ($this->Post->save($this->request->data)) {
                $this->Session->setFlash(__('Your post has been saved.'));
                return $this->redirect(array('action' => 'index'));
            }
            $this->Session->setFlash(__('Unable to add your post.'));
        }
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