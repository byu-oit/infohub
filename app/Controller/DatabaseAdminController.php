<?php

class DatabaseAdminController extends AppController {
	public $uses = ['CollibraAPI', 'BYUAPI'];
	public $components = ['DataWarehouse'];

	function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->deny();
	}

	public function update($databaseName, $schemaName, $tableName) {
		$this->autoRender = false;
		$this->set(compact('databaseName', 'schemaName', 'tableName'));

		if ($this->request->is('post')) {
			$success = $this->CollibraAPI->updateTableBusinessTermLinks($this->request->data('Table.elements'));
			if (!empty($success)) {
				$this->Session->setFlash('Table updated successfully');
				return json_encode(['success' => '1']);
			}
			$this->Session->setFlash('Error: ' . implode('<br>', $this->CollibraAPI->errors), 'default', ['class' => 'error']);
			return json_encode(['success' => '0']);
		}

		$columns = $this->CollibraAPI->getTableColumns($databaseName, $tableName);
		if (empty($columns)) {
			return $this->redirect(['controller' => 'databases', 'action' => 'schema', $schemaName]);
		}
		$glossaries = $this->CollibraAPI->getAllGlossaries();
		$this->set(compact('columns', 'glossaries'));

		$this->request->data = [
			'Table' => [
				'databaseName' => $databaseName,
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

	public function syncDatabase() {
		$this->autoRender = false;
		if (!$this->request->is('post')) {
			$this->render();
		} else {
			$databaseName = $this->request->data['database'];
			$schemaName = $this->request->data['schema'];
			$tableName = $this->request->data['table'];
			$oracleColumns = $this->BYUAPI->oracleColumns($databaseName, $schemaName, $tableName);
			return json_encode($this->DataWarehouse->syncDataWarehouse($databaseName, $schemaName, $tableName, $oracleColumns));
		}
	}
}
