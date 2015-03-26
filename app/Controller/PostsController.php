<?php

class PostsController extends AppController {
    public $helpers = array('Html', 'Form');

    public function index() {
        /*
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://byu.collibra.com/rest/1.0/workflow/workflows");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERPWD, "***REMOVED***:***REMOVED***");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "resource=workflowDefinition&enabled=false");
        //$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $response = curl_exec($ch);
        //$response = json_decode($response);
        //$info = curl_getinfo($ch);
        curl_close($ch);

        print_r($response);
        //print_r($response->communityReference[0]->name);
        exit;
        */
        $this->set('posts', $this->Post->find('all'));
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
    
    public function edit($id = null) {
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
    }
}