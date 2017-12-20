<?php

class ApiAdminController extends AppController {
	public $uses = ['CollibraAPI'];

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
			if (!empty($success) && $this->request->data['propose'] == 'false') {
				$this->Session->setFlash('API updated successfully');
				return json_encode(['success' => '1']);
			} else if (!empty($success) && $this->request->data['propose'] == 'true') {
				return json_encode(['success' => '1']);
			}
			$this->Session->setFlash('Error: ' . implode('<br>', $this->CollibraAPI->errors), 'default', ['class' => 'error']);
			return json_encode(['success' => '0']);
		}

		$terms = $this->CollibraAPI->getApiTerms($hostname, $basePath);
		if (empty($terms)) {
			return $this->redirect(['controller' => 'apis', 'action' => 'host', 'hostname' => $hostname]);
		}
		$this->set(compact('terms'));

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

	public function proposeTerms() {
		if ($this->request->is('post')) {
			$this->autoRender = false;

			foreach ($this->request->data['fields'] as &$field) {
				$resp = $this->CollibraAPI->post('term', [
					'vocabulary' => Configure::read('Collibra.vocabulary.newBusinessTerms'),
					'signifier' => $field['propName'],
					'conceptType' => Configure::read('Collibra.type.term')
				]);
				$resp = json_decode($resp);
				$termId = $resp->resourceId;

				$field['business_term'] = $termId;

				$postString = http_build_query([
					'label' => Configure::read('Collibra.attribute.definition'),
					'value' => $field['desc']."\n\n(Field appears in ".$this->request->data['api'].": ".$field['fieldName'].")"
				]);
				$resp = $this->CollibraAPI->post('term/'.$termId.'/attributes', $postString);
				if ($resp->code != '201') {
					$error = true;
				}
			}

			$this->CollibraAPI->updateApiBusinessTermLinks($this->request->data['fields']);

			if (isset($error)) {
				return json_encode(['success' => 0]);
			}
			return json_encode(['success' => 1]);
		}

		$args = func_get_args();
		$hostname = array_shift($args);
		$basePath = '/' . implode('/', $args);
		if (empty($hostname)) {
			return $this->redirect(['controller' => 'apis', 'action' => 'index']);
		} elseif (empty($basePath)) {
			return $this->redirect(['controller' => 'apis', 'action' => 'host', 'hostname' => $hostname]);
		}

		$terms = $this->CollibraAPI->getApiTerms($hostname, $basePath);
		if (empty($terms)) {
			return $this->redirect(['controller' => 'apis', 'action' => 'host', 'hostname' => $hostname]);
		}
		$this->set(compact('hostname', 'basePath', 'terms'));
	}
}
