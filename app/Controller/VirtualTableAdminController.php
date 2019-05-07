<?php

class VirtualTableAdminController extends AppController {
	public $uses = ['CollibraAPI', 'BYUAPI'];
	public $components = ['DataWarehouse'];

	function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->deny();
	}

	public function update($tableId) {
		$this->autoRender = false;

		if ($this->request->is('post')) {
			$success = $this->CollibraAPI->updateBusinessTermLinks($this->request->data('Table.elements'));
			if (!empty($success)) {
				$this->Session->setFlash('Table updated successfully');
				return json_encode(['success' => '1']);
			}
			$this->Session->setFlash('Error: ' . implode('<br>', $this->CollibraAPI->errors), 'default', ['class' => 'error']);
			return json_encode(['success' => '0']);
		}

		$table = $this->CollibraAPI->getVirtualTable($tableId);
		$table->columns = $this->CollibraAPI->getVirtualTableColumns($tableId);
		if (empty($table)) {
			return $this->redirect(['controller' => 'virtualTables', 'action' => 'index']);
		}
		$glossaries = $this->CollibraAPI->getAllGlossaries();
		$this->set(compact('table', 'glossaries'));

		$this->request->data = [
			'Table' => [
				'tableId' => $tableId,
				'elements' => []]];
		foreach ($table->columns as $column) {
			$this->request->data['Table']['elements'][] = [
				'id' => $column->columnId,
				'name' => $column->columnName,
				'business_term' => empty($column->businessTerm[0]) ? null : $column->businessTerm[0]->termId];
		}

		$this->render();
	}
}
