<?php

class VirtualDatasetsController extends AppController {
	public $uses = ['CollibraAPI', 'BYUAPI'];

	function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->deny();
	}

	private function checkAuthorized() {
		if (!(
			$this->Auth->user('activeFulltimeEmployee') == 'true' ||
			$this->Auth->user('activeParttimeEmployee') == 'true' ||
			$this->Auth->user('activeFulltimeInstructor') == 'true' ||
			$this->Auth->user('activeParttimeInstructor') == 'true' ||
			$this->Auth->user('activeFulltimeNonBYUEmployee') == 'true' ||
			$this->Auth->user('activeParttimeNonBYUEmployee') == 'true'
		)) {

			$this->Flash->error('You must be an active BYU employee to browse databases.');
			$this->redirect(['controller' => 'search', 'action' => 'index']);
		}
	}

	public function loadDremioSpace($spaceId = null) {
		$this->autoRender = false;
		if ($spaceId === null) {
			return json_encode($this->CollibraAPI->getDremioSpaces());
		} else {
			return json_encode($this->CollibraAPI->getDremioSpaceDetails($spaceId));
		}
	}

	public function loadFolder($folderId) {
		$this->autoRender = false;
		return json_encode($this->CollibraAPI->getFolder($folderId));
	}

	public function index() {
		$this->checkAuthorized();
		if ($this->Session->check('recentVirtualDatasets')) {
			$this->set('recent', $this->Session->read('recentVirtualDatasets'));
		}

		$matchAuthorized = $this->BYUAPI->isGROGroupMember($this->Auth->user('username'), 'oit04', 'infohub-match');
		$this->set('matchAuthorized', $matchAuthorized);
	}

	public function view($datasetId) {
		$this->checkAuthorized();
		$dataset = $this->CollibraAPI->getVirtualDataset($datasetId);
		$dataset->columns = $this->CollibraAPI->getVirtualDatasetColumns($datasetId);
		$matchAuthorized = $this->BYUAPI->isGROGroupMember($this->Auth->user('username'), 'oit04', 'infohub-match');

		$breadcrumbs = substr($dataset->name, 0, strrpos($dataset->name, '>') - 1);
		$datasetNameOnly = substr($dataset->name, strrpos($dataset->name, '>') + 2);
		$this->set(compact('dataset', 'matchAuthorized', 'breadcrumbs', 'datasetNameOnly'));

		$arrRecent = $this->Session->check('recentVirtualDatasets') ? $this->Session->read('recentVirtualDatasets') : [];
		array_unshift($arrRecent, ['datasetName' => $dataset->name, 'datasetId' => $dataset->id]);
		$arrRecent = array_unique($arrRecent, SORT_REGULAR);
		$this->Session->write('recentVirtualDatasets', array_slice($arrRecent, 0, 5));
	}

	public function viewRequested($requestId, $datasetId) {
		$this->checkAuthorized();
		$dataset = $this->CollibraAPI->getVirtualDataset($datasetId);
		$dataset->columns = $this->CollibraAPI->getVirtualDatasetColumns($datasetId);

		$request = $this->CollibraAPI->getRequestDetails($requestId);
		$requestedAssetIds = [];
		foreach ($request->requestedDataAssets as $asset) {
			array_push($requestedAssetIds, $asset->reqDataId);
		}
		$this->set(compact('dataset', 'request', 'requestedAssetIds'));
	}
}
