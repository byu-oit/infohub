<?php

class VirtualTablesController extends AppController {
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
			return json_encode($this->CollibraAPI->getRootDremioSpaces());
		} else {
			return json_encode($this->CollibraAPI->getDremioSpaceDetails($spaceId));
		}
	}

	public function index() {
		$this->checkAuthorized();
		if ($this->Session->check('recentVirtualTables')) {
			$this->set('recent', $this->Session->read('recentVirtualTables'));
		}

		$matchAuthorized = $this->BYUAPI->isGROGroupMember($this->Auth->user('username'), 'oit04', 'infohub-match');
		$this->set('matchAuthorized', $matchAuthorized);
	}

	public function view($tableId) {
		$this->checkAuthorized();
		$table = $this->CollibraAPI->getVirtualTable($tableId);
		$table->columns = $this->CollibraAPI->getVirtualTableColumns($tableId);
		$matchAuthorized = $this->BYUAPI->isGROGroupMember($this->Auth->user('username'), 'oit04', 'infohub-match');
		$this->set(compact('table', 'matchAuthorized'));

		$arrRecent = $this->Session->check('recentVirtualTables') ? $this->Session->read('recentVirtualTables') : [];
		array_unshift($arrRecent, ['tableName' => $table->name, 'tableId' => $table->id]);
		$arrRecent = array_unique($arrRecent, SORT_REGULAR);
		$this->Session->write('recentVirtualTables', array_slice($arrRecent, 0, 5));
	}

	public function viewRequested($requestId, $tableId) {
		$this->checkAuthorized();
		$table = $this->CollibraAPI->getVirtualTable($tableId);
		$table->columns = $this->CollibraAPI->getVirtualTableColumns($tableId);

		$request = $this->CollibraAPI->getRequestDetails($requestId);
		$requestedAssetIds = [];
		foreach ($request->requestedDataAssets as $asset) {
			array_push($requestedAssetIds, $asset->reqDataId);
		}
		$this->set(compact('table', 'request', 'requestedAssetIds'));
	}

	protected function _autoCheckout($schemaName, $tableName, $columns) {
		$queue = $this->Session->read('queue');
		foreach ($columns as $column) {
			if (empty($column->businessTerm[0])) {
				continue;
			}
			$queue->businessTerms[$column->businessTerm[0]->termId] = [
				'term' => $column->businessTerm[0]->term,
				'communityId' => $column->businessTerm[0]->termCommunityId,
				'databaseSchema' => $schemaName,
				'databaseTable' => $tableName];
		}

		$this->Session->write('queue', $queue);
		return $this->redirect(['controller' => 'request', 'action' => 'index']);
	}
}
