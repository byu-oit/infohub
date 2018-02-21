<?php

class DatabasesController extends AppController {
	public $uses = ['CollibraAPI', 'BYUAPI'];

	public function index() {
		$schemasCommunity = json_decode($this->CollibraAPI->get('community/'.Configure::read('Collibra.community.dwprd')));
		$schemas = [];
		foreach ($schemasCommunity->vocabularyReferences->vocabularyReference as $schema) {
			if ($schema->meta != '1') {
				array_push($schemas, $schema);
			}
		}
		if (count($schemas) == 1) {
			return $this->redirect(['action' => 'schema', $schemas[0]->name]);
		}
		usort($schemas, function ($a, $b) {
			return strcmp($a->name, $b->name);
		});
		$this->set('schemas', $schemas);
	}

	public function schema($schemaName) {
		$schema = $this->CollibraAPI->getSchemaTables($schemaName);
		usort($schema->tables, function($a, $b) {
			return strcmp($a->tableName, $b->tableName);
		});
		$this->set('schema', $schema);
	}

	public function view($schemaName, $tableName) {
		$columns = $this->CollibraAPI->getTableColumns($tableName);

		$isOITEmployee = $this->BYUAPI->isGROGroupMember($this->Auth->user('username'), 'oit04');
		$this->set(compact('schemaName', 'tableName', 'columns', 'isOITEmployee'));

		if (array_key_exists('checkout', $this->request->query)) {
			return $this->_autoCheckout($schemaName, $tableName, $columns);
		}
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
