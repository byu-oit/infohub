<?php

class VirtualDatasetAdminController extends AppController {
	public $uses = ['CollibraAPI', 'BYUAPI'];
	public $components = ['DataWarehouse'];

	function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->deny();
	}

	public function update($datasetId) {
		$this->autoRender = false;

		if ($this->request->is('post')) {
			$success = $this->CollibraAPI->updateBusinessTermLinks($this->request->data('Dataset.elements'));
			if (!empty($success)) {
				$this->Session->setFlash('Dataset updated successfully');
				return json_encode(['success' => '1']);
			}
			$this->Session->setFlash('Error: ' . implode('<br>', $this->CollibraAPI->errors), 'default', ['class' => 'error']);
			return json_encode(['success' => '0']);
		}

		$dataset = $this->CollibraAPI->getVirtualDataset($datasetId);
		$dataset->columns = $this->CollibraAPI->getVirtualDatasetColumns($datasetId);
		if (empty($dataset)) {
			return $this->redirect(['controller' => 'virtualDatasets', 'action' => 'index']);
		}
		$glossaries = $this->CollibraAPI->getAllGlossaries();
		$this->set(compact('dataset', 'glossaries'));

		$this->request->data = [
			'Dataset' => [
				'datasetId' => $datasetId,
				'elements' => []]];
		foreach ($dataset->columns as $column) {
			$this->request->data['Dataset']['elements'][] = [
				'id' => $column->columnId,
				'name' => $column->columnName,
				'business_term' => empty($column->businessTerm[0]) ? null : $column->businessTerm[0]->termId];
		}

		$this->render();
	}
}
