<?php

class DatabasesController extends AppController {
	public $uses = ['CollibraAPI', 'BYUAPI'];

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
		if ($this->Session->check('recentTables')) {
			$this->set('recent', $this->Session->read('recentTables'));
		}

		$databases = json_decode($this->CollibraAPI->get('community/'.Configure::read('Collibra.community.dataWarehouse')));
		if (count($databases->subCommunityReferences->communityReference) == 1) {
			return $this->redirect(['action' => 'database', 'dbId' => $databases->subCommunityReferences->communityReference[0]->resourceId]);
		}

		$matchAuthorized = $this->BYUAPI->isGROGroupMemberAny($this->Auth->user('username'), 'oit04', 'infohub-match');
		$this->set('matchAuthorized', $matchAuthorized);
		$this->set('databases', $databases);
	}

	public function database($databaseName) {
		$this->checkAuthorized();
		if ($this->Session->check('recentTables')) {
			$this->set('recent', $this->Session->read('recentTables'));
		}

		$schemas = $this->CollibraAPI->getDatabaseSchemas($databaseName);
		if (count($schemas) == 1 && empty($this->request->query['noredirect'])) {
			return $this->redirect(['action' => 'schema', $databaseName, $schemas[0]->name]);
		}
		usort($schemas, function ($a, $b) {
			return strcmp($a->name, $b->name);
		});
		$matchAuthorized = $this->BYUAPI->isGROGroupMemberAny($this->Auth->user('username'), 'oit04', 'infohub-match');
		$this->set(compact('matchAuthorized', 'databaseName', 'schemas'));
	}

	public function schema($databaseName, $schemaName) {
		$this->checkAuthorized();
		if ($this->Session->check('recentTables')) {
			$this->set('recent', $this->Session->read('recentTables'));
		}

		$schema = $this->CollibraAPI->getSchemaTables($databaseName, $schemaName);
		usort($schema->tables, function($a, $b) {
			return strcmp($a->tableName, $b->tableName);
		});
		$this->set('schema', $schema);
	}

	public function view($databaseName, $schemaName, $tableName) {
		$this->checkAuthorized();
		$columns = $this->CollibraAPI->getTableColumns($databaseName, $tableName);
		$schema = $this->CollibraAPI->getSchemaTables($databaseName, $schemaName);
		foreach ($schema->tables as $tn) {
			if($tn->tableName == $tableName) {
				$tableInfo = $this->CollibraAPI->getAttributes($tn->tableId);
				foreach($tableInfo as $attr) {
					//Usage Notes
					if($attr['Usage Notes']->attrTypeId == "3dda4b76-2b31-4dbd-a058-729dac94f230") {
						$usageNotes = $attr['Usage Notes']->attrValue;
					}
				}
			}
		}

		$tableNameOnly = substr($tableName, strpos($tableName, '>') + 2);
		$matchAuthorized = $this->BYUAPI->isGROGroupMemberAny($this->Auth->user('username'), 'oit04', 'infohub-match');
		$this->set(compact('databaseName', 'schemaName', 'tableName', 'columns', 'tableNameOnly', 'matchAuthorized', 'usageNotes'));

		$arrRecent = $this->Session->check('recentTables') ? $this->Session->read('recentTables') : [];
		array_unshift($arrRecent, ['databaseName' => $databaseName, 'schemaName' => $schemaName, 'tableName' => $tableName]);
		$arrRecent = array_unique($arrRecent, SORT_REGULAR);
		$this->Session->write('recentTables', array_slice($arrRecent, 0, 5));

		if (array_key_exists('checkout', $this->request->query)) {
			return $this->_autoCheckout($schemaName, $tableName, $columns);
		}
	}

	public function viewRequested($requestId, $databaseName, $schemaName, $tableName) {
		$this->checkAuthorized();
		$columns = $this->CollibraAPI->getTableColumns($databaseName, $schemaName.' > '.$tableName);

		$request = $this->CollibraAPI->getRequestDetails($requestId);
		$requestedAssetIds = [];
		foreach ($request->requestedDataAssets as $asset) {
			array_push($requestedAssetIds, $asset->reqDataId);
		}
		$this->set(compact('databaseName', 'schemaName', 'tableName', 'columns', 'request', 'requestedAssetIds'));
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
