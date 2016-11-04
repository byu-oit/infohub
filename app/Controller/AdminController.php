<?php

class AdminController extends AppController {
	public $uses = array('CmsPage', 'CmsUsers');
	public $helpers = array('Html', 'Form');

	function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->deny();
		$this->Auth->allow('logout');
		$this->layout = 'admin';
	}

	public function isAuthorized($user) {
		return $this->isAdmin($user);
	}

	public function index(){
		$this->redirect(array('action' => 'manageusers'));
	}

	public function logout(){
		$this->Auth->logout();
		$this->redirect('/');
	}

	public function managepages(){
		$this->set('pageList', $this->CmsPage->listAdminPages());
	}

	public function editpage($id=null){
		// update page data
		if ($this->request->data) {
			$page = $this->CmsPage->findById($id);
			if (!$page) {
				throw new NotFoundException(__('Invalid page'));
			}

			if ($this->request->is(array('post', 'put'))) {
			   $this->CmsPage->id = $id;
				if ($this->CmsPage->save($this->request->data)) {
					$this->Session->setFlash(__('The page has been updated.'));
					//return $this->redirect(array('action' => 'index'));
				}else{
					$this->Session->setFlash('Unable to update the page', 'default', array('class' => 'error'));
				}
			}
		}

		$pageID = 0;
		if(sizeof($this->request->params['pass'])>0){
			$pageID = intval($this->request->params['pass'][0]);
		}

		if($pageID!=0){
			$page = $this->CmsPage->loadPage($pageID);
			if(!$page){
				header('location: /admin/managepages');
			}else{
				$cmsPage = $page['CmsPage'];
			}
		}else{
			$page = null;
			$cmsPage = null;
		}

		$this->set('pageList', $this->CmsPage->listAdminPages());
		$this->set('page', $cmsPage);
		$this->request->data = $page;
	}

	public function addpage(){
		$pageID = 0;

		if(sizeof($this->request->params['pass'])>0){
			$pageID = intval($this->request->params['pass'][0]);
			$page = $this->CmsPage->loadPage($pageID);
			$cmsPage = $page['CmsPage'];
		}else{
			$this->Session->setFlash('Invalid page', 'default', array('class' => 'error'));
			$page = null;
			$cmsPage = null;
		}

		// update page data
		if ($this->request->is('post') && $pageID!=0) {
			$this->CmsPage->create();
			if ($this->CmsPage->save($this->request->data)) {
				$this->Session->setFlash(__('Your page has been saved.'));
				//return $this->redirect(array('action' => 'index'));
			}else{
				$this->Session->setFlash('Unable to save page', 'default', array('class' => 'error'));
			}
		}

		$this->set('pageList', $this->CmsPage->listAdminPages());
		$this->set('page', $cmsPage);
		$this->set('parentID', $pageID);
		$this->set('rank', $this->getNextRank($pageID));
	}

	public function deletepage($id = null) {
		if ($this->CmsPage->delete($id) && $id!=1) {
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
		$this->set('users', $this->CmsUsers->listAdminUsers());
	}

	public function edituser($id=null){
		// update page data
		if ($this->request->data) {
			$user = $this->CmsUsers->findById($id);

			if (!$user) {
				throw new NotFoundException(__('Invalid user'));
			}else{
				if ($this->request->is(array('post', 'put'))) {
					$data = $this->request['data']['CmsUsers'];
					$newActive = $data['active'];

					$this->CmsUsers->id = $id;
					$this->CmsUsers->set('active', $newActive);
					if ($this->Auth->user('infohubUserId') == $id) {
						$this->Session->setFlash(__('Cannot inactivate yourself!'));
					} elseif ($this->CmsUsers->save()) {
						$this->Session->setFlash(__('This user has been updated.'));
						$this->redirect($this->request->here());
					}else{
						$this->Session->setFlash('Unable to update this user.', 'default', array('class' => 'error'));
					}
				}
			}
		}

		$userID = 0;
		if(sizeof($this->request->params['pass'])>0){
			$userID = intval($this->request->params['pass'][0]);
		}

		if($userID!=0){
			$user = $this->CmsUsers->findById($id);
			if(!$user){
				header('location: /admin/manageusers');
			}else{
				$cmsUser = $user['CmsUsers'];
			}
		}else{
			$user = null;
			$cmsUser = null;
		}

		$this->set('users', $this->CmsUsers->listAdminUsers());
		$this->set('user', $cmsUser);
		$this->request->data = $user;
	}

	public function adduser(){
		// update user data
		if ($this->request->is('post')) {
			if($this->CmsUsers->userExists($this->request['data']['CmsUsers']['username'])){
				$this->Session->setFlash('This user already exists.', 'default', array('class' => 'error'));
			}else{
				$data = $this->request['data'];
				if ($this->CmsUsers->save($data)) {
					$this->Session->setFlash(__('The user has been added.'));
				}else{
					$this->Session->setFlash('Unable to save user.', 'default', array('class' => 'error'));
				}
			}
		}

		$this->set('users', $this->CmsUsers->listAdminUsers());
	}

	public function deleteuser($id = null) {
		if ($this->Auth->user('infohubUserId') == $id) {
			$this->Session->setFlash(__('Cannot delete yourself!', h($id)));
		} elseif ($this->CmsUsers->delete($id)) {
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
			$arrPageData = $this->request['data']['pageData'];
			$arrPages = explode("||", $arrPageData);

			$rank = 2;
			$prevParentID = 0;
			foreach($arrPages as $page){
				$arrPageIDs = explode("::", $page);
				$parentID = intval($arrPageIDs[2]);
				$pageID = intval($arrPageIDs[0]);
				$this->CmsPage->save(['id' => $pageID, 'parentID' => $parentID, 'rank' => $rank]);
				$rank++;
			}

			//update home page's rank to 1
			$this->CmsPage->id = 1;
			$this->CmsPage->saveField('rank', 1);
		}
		exit;
	}

	private function getNextRank($parentID){
		$results = $this->CmsPage->find('first', ['fields' => ['MAX(rank) as rank'], 'conditions' => ['parentID' => $parentID]]);
		return empty($results) ? 0 : $results[0]['rank'] + 1;
	}
}