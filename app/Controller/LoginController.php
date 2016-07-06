<?php

class LoginController extends AppController {
	function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->deny('index');
	}

	public function index() {
		$this->redirect('/');
	}

	public function logout() {
		$this->Auth->logout();
		$this->redirect('/');
	}
}