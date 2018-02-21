<?php

class DatabaseAdminController extends AppController {
	public $uses = ['CollibraAPI'];

	function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->deny();
	}

	public function update($schemaName, $tableName) {
		$this->autoRender = false;

		$this->set(compact('schemaName', 'tableName'));

		if ($this->request->is('post')) {
			$success = $this->CollibraAPI->updateTableBusinessTermLinks($this->request->data('Table.elements'));
			if (!empty($success) && $this->request->data['propose'] == 'false') {
				$this->Session->setFlash('Table updated successfully');
				return json_encode(['success' => '1']);
			} else if (!empty($success) && $this->request->data['propose'] == 'true') {
				return json_encode(['success' => '1']);
			}
			$this->Session->setFlash('Error: ' . implode('<br>', $this->CollibraAPI->errors), 'default', ['class' => 'error']);
			return json_encode(['success' => '0']);
		}

		$columns = $this->CollibraAPI->getTableColumns($tableName);
		if (empty($columns)) {
			return $this->redirect(['controller' => 'databases', 'action' => 'schema', $schemaName]);
		}
		$this->set(compact('columns'));

		$this->request->data = [
			'Table' => [
				'schemaName' => $schemaName,
				'tableName' => $tableName,
				'elements' => []]];
		foreach ($columns as $column) {
			$this->request->data['Table']['elements'][] = [
				'id' => $column->columnId,
				'name' => $column->columnName,
				'business_term' => empty($column->businessTerm[0]) ? null : $column->businessTerm[0]->termId];
		}

		$this->render();
	}

	public function proposeTerms($schemaName, $tableName) {
		if ($this->request->is('post')) {
			$this->autoRender = false;

			foreach ($this->request->data['columns'] as &$column) {
				$resp = $this->CollibraAPI->post('term', [
					'vocabulary' => Configure::read('Collibra.vocabulary.newBusinessTerms'),
					'signifier' => $column['propName'],
					'conceptType' => Configure::read('Collibra.type.term')
				]);
				$resp = json_decode($resp);
				$termId = $resp->resourceId;

				$column['business_term'] = $termId;

				$postString = http_build_query([
					'label' => Configure::read('Collibra.attribute.definition'),
					'value' => "{$column['desc']}\n\n(Database column: {$schemaName} > {$column['columnName']})"
				]);
				$resp = $this->CollibraAPI->post('term/'.$termId.'/attributes', $postString);
				if ($resp->code != '201') {
					$error = true;
				}
			}

			$this->CollibraAPI->updateTableBusinessTermLinks($this->request->data['columns']);

			if (isset($error)) {
				return json_encode(['success' => 0]);
			}
			return json_encode(['success' => 1]);
		}

		if (empty($schemaName)) {
			return $this->redirect(['controller' => 'databases', 'action' => 'index']);
		} elseif (empty($tableName)) {
			return $this->redirect(['controller' => 'databases', 'action' => 'schema', $schemaName]);
		}

		$columns = $this->CollibraAPI->getTableColumns($tableName);
		if (empty($columns)) {
			return $this->redirect(['controller' => 'databases', 'action' => 'schema', $schemaName]);
		}
		$this->set(compact('schemaName', 'tableName', 'columns'));
	}
}
