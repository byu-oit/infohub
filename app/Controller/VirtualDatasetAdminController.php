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

		$dataset = $this->CollibraAPI->getVirtualDataset($datasetId);
		$dataset->columns = $this->CollibraAPI->getVirtualDatasetColumns($datasetId);
		$glossaries = $this->CollibraAPI->getAllGlossaries();
		$this->set(compact('dataset', 'glossaries'));
		$this->render();
	}

	public function import() {
		$this->autoRender = false;
		if (!$this->request->is('post')) {
			$directorId = $this->CollibraAPI->getResponsibilities(Configure::read('Collibra.community.dataGovernanceCouncil'))['Steward'][0]->resourceId;
			$director = json_decode($this->CollibraAPI->get('user/'.$directorId));
			$this->set(compact('director'));
			$this->render();
		} else {
			$path = str_replace('.', '/', $this->request->data['path']);
			return json_encode($this->_import($path));
		}
	}

	private function _import($path) {
		$asset = $this->DremioAPI->catalog($path);

		if (empty($asset) || isset($asset->errorMessage)) {
			return ['success' => 0, 'message' => 'Error contacting Dremio.'];
		}

		if ($asset->entityType == 'dataset') {

			$pathArray = explode('/', $path);
			$postData = ['folderName' => [], 'datasetName' => '', 'columnNames' => []];
			$postData['folderName'] = $pathArray[0];
			$postData['datasetName'] = implode('.', $pathArray);
			foreach (array_column($asset->fields, 'name') as $columnName) {
				array_push($postData['columnNames'], implode('.', $pathArray).'.'.$columnName);
			}

			$resp = $this->CollibraAPI->post(
				'workflow/'.Configure::read('Collibra.workflow.updateVirtualDatasets').'/start',
				$this->Collibra->preparePostData($postData));
			if ($resp->code != '200') {
				return ['success' => 0, 'message' => 'Error reaching Collibra.'];
			}
			return ['success' => 1, 'message' => 'Dataset imported successfully.', 'redirect' => 1];

		} else {

			$folder = $path;
			$assets = [
				'datasets' => [],
				'arrPostData' => []
			];
			foreach ($asset->children as $ch) {
				if ($ch->type == 'DATASET') {
					array_push($assets['datasets'], $folder.'/'.end($ch->path));
				}
			}

			foreach ($assets['datasets'] as $datasetPath) {
				$dataset = $this->DremioAPI->catalog($datasetPath);

				$pathArray = explode('/', $datasetPath);
				$postData = [
					'folderName' => $folder,
					'datasetName' => implode('.', $pathArray),
					'columnNames' => []
				];
				foreach (array_column($dataset->fields, 'name') as $columnName) {
					array_push($postData['columnNames'], implode('.', $pathArray).'.'.$columnName);
				}

				array_push($assets['arrPostData'], $postData);
			}

			$success = true;
			foreach ($assets['arrPostData'] as $pd) {
				$resp = $this->CollibraAPI->post(
					'workflow/'.Configure::read('Collibra.workflow.updateVirtualDatasets').'/start',
					$this->Collibra->preparePostData($pd));
				if ($resp->code != '200') {
					$success = false;
				}
			}

			if (!$success) {
				return ['success' => 0, 'message' => 'Error reaching Collibra.'];
			}
			return ['success' => 1, 'message' => 'Datasets imported successfully.', 'redirect' => 1];

		}
	}
}
