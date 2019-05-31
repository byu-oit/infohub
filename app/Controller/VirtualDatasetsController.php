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
		if ($this->Session->check('recentSpaces')) {
			$this->set('recent', $this->Session->read('recentSpaces'));
		}

		$importAuthorized = $this->BYUAPI->isGROGroupMember($this->Auth->user('username'), 'oit04');

		$spaces = $this->CollibraAPI->getDremioSpaces();
		$this->set(compact('importAuthorized', 'spaces'));
	}

	public function view($spaceId) {
		$this->checkAuthorized();
		$space = $this->CollibraAPI->getDremioSpaceDetails($spaceId, true);
		$matchAuthorized = $this->BYUAPI->isGROGroupMember($this->Auth->user('username'), 'oit04', 'infohub-match');
		$this->set(compact('space', 'matchAuthorized'));

		$arrRecent = $this->Session->check('recentSpaces') ? $this->Session->read('recentSpaces') : [];
		array_unshift($arrRecent, ['spaceName' => $space->spaceName, 'spaceId' => $space->spaceId]);
		$arrRecent = array_unique($arrRecent, SORT_REGULAR);
		$this->Session->write('recentSpaces', array_slice($arrRecent, 0, 5));
	}

	public function viewRequested($requestId, $spaceId) {
		$this->checkAuthorized();
		$space = $this->CollibraAPI->getDremioSpaceDetails($spaceId, true, $nameLookup=isset($this->request->query['n']));

		$request = $this->CollibraAPI->getRequestDetails($requestId);
		$requestedAssetIds = [];
		foreach ($request->requestedDataAssets as $asset) {
			array_push($requestedAssetIds, $asset->reqDataId);
		}
		$this->set(compact('space', 'request', 'requestedAssetIds'));
	}
}
