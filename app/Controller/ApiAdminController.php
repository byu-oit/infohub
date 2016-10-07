<?php

class ApiAdminController extends AppController {
	public $uses = ['CollibraAPI'];

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
}