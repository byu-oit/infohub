<?php

class ApiAdminController extends AppController {
	public $uses = ['CollibraAPI'];
	public $helpers = ['Fieldset'];

	function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->deny();
	}

	public function update() {
		$this->autoRender = false;

		$args = func_get_args();
		$hostname = array_shift($args);
		$basePath = '/' . implode('/', $args);
		if (empty($hostname)) {
			return $this->redirect(['controller' => 'apis', 'action' => 'index']);
		} elseif (empty($basePath)) {
			return $this->redirect(['controller' => 'apis', 'action' => 'host', 'hostname' => $hostname]);
		}
		$this->set(compact('hostname', 'basePath'));

		if ($this->request->is('post')) {
			$success = $this->CollibraAPI->updateBusinessTermLinks($this->request->data('Api.elements'));
			if (!empty($success)) {
				$this->Session->setFlash('API updated successfully');
				return json_encode(['success' => '1']);
			}
			$this->Session->setFlash('Error: ' . implode('<br>', $this->CollibraAPI->errors), 'default', ['class' => 'error']);
			return json_encode(['success' => '0']);
		}

		$fields = $this->CollibraAPI->getApiFields($hostname, $basePath, true);
		if (empty($fields)) {
			return $this->redirect(['controller' => 'apis', 'action' => 'host', 'hostname' => $hostname]);
		}
		$glossaries = $this->CollibraAPI->getAllGlossaries();
		$this->set(compact('fields', 'glossaries'));

		$this->request->data = [
			'Api' => [
				'host' => $hostname,
				'basePath' => $basePath,
				'elements' => []]];
		foreach ($fields as $field) {
			$this->request->data['Api']['elements'][] = [
				'id' => $field->id,
				'name' => $field->name,
				'business_term' => empty($field->businessTerm[0]) ? null : $field->businessTerm[0]->termId];
		}

		$this->render();
	}
}
