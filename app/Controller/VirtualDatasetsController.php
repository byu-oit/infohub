<?php

class VirtualDatasetsController extends AppController {
	public $uses = ['CollibraAPI', 'BYUAPI'];
	public $helpers = ['VirtualDataset'];

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

	public function index() {
		$this->checkAuthorized();
		if ($this->Session->check('recentDatasets')) {
			$this->set('recent', $this->Session->read('recentDatasets'));
		}

		$importAuthorized = $this->BYUAPI->isGROGroupMemberAny($this->Auth->user('username'), 'oit04');

		$datasets = $this->CollibraAPI->getDremioDatasets();
		$this->set(compact('importAuthorized', 'datasets'));
	}

	public function view($datasetId) {
		$this->checkAuthorized();
		$dataset = $this->CollibraAPI->getVirtualDataset($datasetId);
		$dataset->columns = $this->CollibraAPI->getVirtualDatasetColumns($datasetId);
		$matchAuthorized = $this->BYUAPI->isGROGroupMemberAny($this->Auth->user('username'), 'oit04', 'infohub-match');
		$this->set(compact('dataset', 'matchAuthorized'));

		$arrRecent = $this->Session->check('recentDatasets') ? $this->Session->read('recentDatasets') : [];
		array_unshift($arrRecent, ['datasetName' => $dataset->name, 'datasetId' => $dataset->id]);
		$arrRecent = array_unique($arrRecent, SORT_REGULAR);
		$this->Session->write('recentDatasets', array_slice($arrRecent, 0, 5));
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
