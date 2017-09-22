<?php

class ApiAdminController extends AppController {
	public $uses = ['CollibraAPI'];

	function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->deny();
	}

	public function update() {
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

		if ($this->request->is('post')) {
			$success = $this->CollibraAPI->updateApiBusinessTermLinks($this->request->data('Api.elements'));
			if (!empty($success)) {
				$this->Session->setFlash('API updated successfully');
				$this->redirect(array_merge(['controller' => 'apis', 'action' => 'view', 'hostname' => $hostname], $args));
			}
			$this->Session->setFlash('Error: ' . implode('<br>', $this->CollibraAPI->errors), 'default', ['class' => 'error']);
		} else {
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
		}
		$this->set(compact('hostname', 'basePath', 'terms'));
	}

	public function proposeTerms() {
		if ($this->request->is('post')) {
			$this->autoRender = false;
			$error = false;
			$originatingApi = "\n\n(Field appears in ".$this->request->data['api'].")";

			$postData['intakeVocabulary'] = Configure::read('Collibra.vocabulary.newBusinessTerms');
			$postData['conceptType'] = Configure::read('Collibra.type.term');
			foreach ($this->request->data['fields'] as $field) {
				$postData['signifier'] = substr($field['name'], strpos($field['name'], '.')+1);
				$postData['definition'] = $field['desc'].$originatingApi;
				$postString = http_build_query($postData);
				$postString = preg_replace('/%0D%0A/', '<br/>', $postString);
				$resp = $this->CollibraAPI->post('workflow/'.Configure::read('Collibra.newBusinessTermWorkflow.id').'/start', $postString);
				$resp = json_decode($resp);

				if (!isset($resp->startWorkflowResponses[0]->successmessage)) {
					$error = true;
				}
			}

			if (!$error) {
				return '{"success":1}';
			}
			return '{"success":0}';
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
