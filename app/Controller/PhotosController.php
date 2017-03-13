<?php

class PhotosController extends AppController {
	public $uses = ['Photo', 'CollibraAPI'];

	function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->deny();
		$this->layout = 'admin';
	}

	public function isAuthorized($user) {
		//All authenticated users allowed to see collibraview
		return ($this->request->action == 'collibraview') ? true : $this->isAdmin($user);
	}

	public function index() {
		$this->loadModel('CollibraAPI');
		$limit = $this->request->query('limit') ? $this->request->query('limit') : 200;
		$offset = $this->request->query('offset') ? $this->request->query('offset') : 0;
		$userData = $this->CollibraAPI->userList($limit, $offset);
		$users = (empty($userData->aaData)) ? [] : $userData->aaData;
		$total = (empty($userData->iTotalDisplayRecords)) ? 0 : $userData->iTotalDisplayRecords;
		$this->set(compact('users', 'limit', 'offset', 'total'));
	}

	public function update() {
		$this->response->type('json');
		if (!$this->request->is('post')) {
			return new CakeResponse(['type' => 'json', 'body' => json_encode(['success' => 'false', 'message' => 'POST only'])]);
		}
		if (!$netId = $this->request->data('netId')) {
			return new CakeResponse(['type' => 'json', 'body' => json_encode(['success' => 'false', 'message' => 'netId required'])]);
		}

		if (!$byuPhoto = $this->Photo->get($netId)) {
			return new CakeResponse(['type' => 'json', 'body' => json_encode(['success' => 'false', 'message' => 'BYU Photo not found'])]);
		}

		if (!$userResourceId = $this->CollibraAPI->userResourceFromUsername($netId)) {
			return new CakeResponse(['type' => 'json', 'body' => json_encode(['success' => 'false', 'message' => 'Collibra user not found'])]);
		}
		if ($this->CollibraAPI->photo($userResourceId, $byuPhoto)) {
			$this->response->body(json_encode(['success' => 'true', 'message' => 'Collibra photo updated']));
		} else {
			$this->response->body(json_encode(['success' => 'false', 'message' => 'Error updating Collibra photo']));
		}
		return $this->response;
	}

	public function view($netId = null) {
		session_write_close();
		$photo = $this->Photo->get($netId);
		if (empty($photo)) {
			exit("No photo found");
		}
		header("Content-Type: {$photo['type']}");
		echo $photo['body'];
		exit();
	}

	public function collibraview($userResourceId = null, $size = null) {
		session_write_close();
		if (empty($userResourceId)) {
			exit("No photo found");
		}

		$photo = $this->CollibraAPI->photo($userResourceId, null, $size);
		if (empty($photo)) {
			$photo = [
				'body' => file_get_contents(IMAGES . 'icon-question.png'),
				'type' => 'image/png'];
		}
		header('Content-Type: ' . $photo['type']);
		echo $photo['body'];
		exit();
	}
}