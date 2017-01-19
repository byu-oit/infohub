<?php

class UsersController extends AppController {
	public $uses = ['CollibraAPI', 'Photo'];

	function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->deny();
		$this->layout = 'admin';
	}

	public function isAuthorized($user) {
		return $this->isAdmin($user);
	}

	public function index() {
		$this->redirect('/');
	}

	public function update($netId) {
		$success = $this->CollibraAPI->updateUserFromByu($netId);
		if ($success) {
			$output = ['success' => true];
		} else {
			$output = ['success' => false, 'errors' => $this->CollibraAPI->errors];
		}
		$this->set(compact('output'));
	}
}