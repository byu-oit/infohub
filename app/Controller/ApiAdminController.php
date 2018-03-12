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
			$success = $this->CollibraAPI->updateApiBusinessTermLinks($this->request->data('Api.elements'));
			if (!empty($success)) {
				$this->Session->setFlash('API updated successfully');
				return json_encode(['success' => '1']);
			}
			$this->Session->setFlash('Error: ' . implode('<br>', $this->CollibraAPI->errors), 'default', ['class' => 'error']);
			return json_encode(['success' => '0']);
		}

		$terms = $this->CollibraAPI->getApiTerms($hostname, $basePath, true);
		if (empty($terms)) {
			return $this->redirect(['controller' => 'apis', 'action' => 'host', 'hostname' => $hostname]);
		}
		$glossaries = $this->CollibraAPI->getAllGlossaries();
		$this->set(compact('terms', 'glossaries'));

		$this->request->data = [
			'Api' => [
				'host' => $hostname,
				'basePath' => $basePath,
				'elements' => []]];
		foreach ($terms as $term) {
			$this->request->data['Api']['elements'][] = [
				'id' => $term->id,
				'name' => $term->name,
				'business_term' => empty($term->businessTerm[0]) ? null : $term->businessTerm[0]->termId];
		}

		$this->render();
	}
}
