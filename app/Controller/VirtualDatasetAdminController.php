<?php

class VirtualDatasetAdminController extends AppController {
	public $uses = ['CollibraAPI', 'BYUAPI', 'DremioAPI'];
	public $components = ['DataWarehouse', 'Collibra'];
	public $helpers = ['VirtualDataset'];

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

		$dataset = $this->CollibraAPI->getDremioDatasetDetails($datasetId, true);
		if (empty($dataset->tables)) {
			return $this->redirect(['controller' => 'virtualTables', 'action' => 'index']);
		}
		$glossaries = $this->CollibraAPI->getAllGlossaries();
		$this->set(compact('dataset', 'glossaries'));
		$this->render();
	}

	public function import() {
		$this->autoRender = false;
		if (!$this->request->is('post')) {
			$directorId = $this->CollibraAPI->getResponsibilities(Configure::read('Collibra.community.dataGovernanceCouncil'))['Steward'][0]->resourceId;
			$director = json_decode($this->CollibraAPI->get('user/'.$directorId));
			$datasets = $this->CollibraAPI->getDremioDatasets();
			$this->set(compact('director', 'datasets'));
			$this->render();
		} else {
			$path = str_replace('.', '/', $this->request->data['path']);
			return json_encode($this->_import($path, $this->request->data['dataset']));
		}
	}

	private function _import($path, $dataset) {
		$asset = $this->DremioAPI->catalog($path);

		return ($asset);
		if (empty($asset) || isset($asset->errorMessage)) {
			return ['success' => 0, 'message' => 'Error contacting Dremio.'];
		}

		if ($asset->entityType == 'dataset') {

			$pathArray = explode('/', $path);
			$postData = ['spaceName' => '', 'folderNames' => [], 'tableName' => '', 'columnNames' => [], 'datasetName' => $dataset];
			$postData['spaceName'] = $pathArray[0];
			$i = 2;
			while (count($postData['folderNames']) < count($pathArray) - 2) {
				array_push($postData['folderNames'], implode('.', array_slice($pathArray, 0, $i)));
				$i++;
			}
			$postData['tableName'] = implode('.', $pathArray);
			foreach (array_column($asset->fields, 'name') as $columnName) {
				array_push($postData['columnNames'], implode('.', $pathArray).'.'.$columnName);
			}

			$resp = $this->CollibraAPI->post(
				'workflow/'.Configure::read('Collibra.workflow.updateVirtualTables').'/start',
				$this->Collibra->preparePostData($postData));
			if ($resp->code != '200') {
				return ['success' => 0, 'message' => 'Error reaching Collibra.'];
			} else {
				return ['success' => 0, 'message' => 'Virtual table imported successfully.', 'redirect' => 1];
			}

		} else {

			$assets = [
				'folders' => [],
				'tables' => [],
				'arrPostData' => []
			];
			foreach ($asset->children as $ch) {
				if ($ch->type == 'CONTAINER' && $ch->containerType == 'FOLDER') {
					array_push($assets['folders'], implode('/', $ch->path));
				}
				else if ($ch->type == 'DATASET') {
					array_push($assets['tables'], implode('/', $ch->path));
				}
			}

			while (!empty($assets['folders'])) {
				$folderPath = array_shift($assets['folders']);
				$folder = $this->DremioAPI->catalog($folderPath);
				foreach ($folder->children as $ch) {
					if ($ch->type == 'CONTAINER' && $ch->containerType == 'FOLDER') {
						array_push($assets['folders'], implode('/', $ch->path));
					}
					else if ($ch->type == 'DATASET') {
						array_push($assets['tables'], implode('/', $ch->path));
					}
				}
			}

			foreach ($assets['tables'] as $tablePath) {
				$table = $this->DremioAPI->catalog($tablePath);

				$pathArray = explode('/', $tablePath);
				$postData = ['spaceName' => '', 'folderNames' => [], 'tableName' => '', 'columnNames' => [], 'datasetName' => $dataset];
				$postData['spaceName'] = $pathArray[0];
				$i = 2;
				while (count($postData['folderNames']) < count($pathArray) - 2) {
					array_push($postData['folderNames'], implode('.', array_slice($pathArray, 0, $i)));
					$i++;
				}
				$postData['tableName'] = implode('.', $pathArray);
				foreach (array_column($table->fields, 'name') as $columnName) {
					array_push($postData['columnNames'], implode('.', $pathArray).'.'.$columnName);
				}

				array_push($assets['arrPostData'], $postData);
			}

			$success = true;
			foreach ($assets['arrPostData'] as $pd) {
				$resp = $this->CollibraAPI->post(
					'workflow/'.Configure::read('Collibra.workflow.updateVirtualTables').'/start',
					$this->Collibra->preparePostData($pd));
				if ($resp->code != '200') {
					$success = false;
				}
			}

			if ($success) {
				return ['success' => 1, 'message' => 'Virtual tables imported successfully.', 'redirect' => 1];
			} else {
				return ['success' => 0, 'message' => 'Error reaching Collibra.'];
			}

		}
	}
}
