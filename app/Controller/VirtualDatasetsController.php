<?php

class VirtualDatasetsController extends AppController {
	public $uses = ['CollibraAPI', 'BYUAPI'];
	public $helpers = ['VirtualDataset'];

	function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->deny();
	}

	private function checkAuthorized() {
		if($this->BYUAPI->isGROGroupMemberAny($this->Auth->user('username'), 'oit04', 'infohub-match')){
			return;
		}
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
		$sortedDataset = $this->dataSet($datasets);
		$this->set(compact('importAuthorized', 'datasets', 'sortedDataset'));
	}

	public function view($datasetId) {
		$this->checkAuthorized();
		$dataset = $this->CollibraAPI->getDremioDatasetDetails($datasetId, true);
		$matchAuthorized = $this->BYUAPI->isGROGroupMemberAny($this->Auth->user('username'), 'oit04', 'infohub-match');
		$this->set(compact('dataset', 'matchAuthorized'));

		$arrRecent = $this->Session->check('recentDatasets') ? $this->Session->read('recentDatasets') : [];
		array_unshift($arrRecent, ['datasetName' => $dataset->datasetName, 'datasetId' => $dataset->datasetId]);
		$arrRecent = array_unique($arrRecent, SORT_REGULAR);
		$sortedDataset = $this->dataSet($datasets);
		$this->Session->write('recentDatasets', array_slice($arrRecent, 0, 5));
	}

	private function dataSet($dataset) {
		$sortedDataset = [];
		$noDataSets = false;
		if(!empty($dataset)) {
			usort($dataset, function ($a, $b) {
				return strcmp(strtolower($a->datasetName), strtolower($b->datasetName));
			});
			foreach ($dataset as $ds) {
				if (
					$ds->statusId == Configure::read('Collibra.status.testing') ||
					$ds->statusId == Configure::read('Collibra.status.retired')
					) continue;
				$sortedDataset[$ds->statusId][] = $ds;
			}
		} else {
			$noDataSets = true;
		}
		return $sortedDataset;
	}

	public function viewRequested($requestId, $datasetId) {
		$this->checkAuthorized();
		$dataset = $this->CollibraAPI->getDremioDatasetDetails($datasetId, true, $nameLookup=isset($this->request->query['n']));

		$request = $this->CollibraAPI->getRequestDetails($requestId);
		$requestedAssetIds = [];
		foreach ($request->requestedDataAssets as $asset) {
			array_push($requestedAssetIds, $asset->reqDataId);
		}
		$this->set(compact('dataset', 'request', 'requestedAssetIds'));
	}
}
