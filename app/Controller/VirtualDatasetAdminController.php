<?php

class VirtualDatasetAdminController extends AppController {
	public $uses = ['CollibraAPI', 'BYUAPI'];
	public $components = ['DataWarehouse'];
	public $helpers = ['VirtualDataset'];

	function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->deny();
	}

	public function update($spaceId) {
		$this->autoRender = false;

		if ($this->request->is('post')) {
			$success = $this->CollibraAPI->updateBusinessTermLinks($this->request->data('Space.elements'));
			if (!empty($success)) {
				$this->Session->setFlash('Space updated successfully');
				return json_encode(['success' => '1']);
			}
			$this->Session->setFlash('Error: ' . implode('<br>', $this->CollibraAPI->errors), 'default', ['class' => 'error']);
			return json_encode(['success' => '0']);
		}

		$space = $this->CollibraAPI->getDremioSpaceDetails($spaceId, true);
		if (empty($space->subfolders) && empty($space->datasets)) {
			return $this->redirect(['controller' => 'virtualDatasets', 'action' => 'index']);
		}
		$glossaries = $this->CollibraAPI->getAllGlossaries();
		$this->set(compact('space', 'glossaries'));
		$this->render();
	}
}
