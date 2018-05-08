<?php

class DatabaseAdminController extends AppController {
	public $uses = ['CollibraAPI', 'BYUAPI'];

	function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->deny();
	}

	public function update($schemaName, $tableName) {
		$this->autoRender = false;

		$this->set(compact('schemaName', 'tableName'));

		if ($this->request->is('post')) {
			$success = $this->CollibraAPI->updateTableBusinessTermLinks($this->request->data('Table.elements'));
			if (!empty($success)) {
				$this->Session->setFlash('Table updated successfully');
				return json_encode(['success' => '1']);
			}
			$this->Session->setFlash('Error: ' . implode('<br>', $this->CollibraAPI->errors), 'default', ['class' => 'error']);
			return json_encode(['success' => '0']);
		}

		$columns = $this->CollibraAPI->getTableColumns($tableName);
		if (empty($columns)) {
			return $this->redirect(['controller' => 'databases', 'action' => 'schema', $schemaName]);
		}
		$glossaries = $this->CollibraAPI->getAllGlossaries();
		$this->set(compact('columns', 'glossaries'));

		$this->request->data = [
			'Table' => [
				'schemaName' => $schemaName,
				'tableName' => $tableName,
				'elements' => []]];
		foreach ($columns as $column) {
			$this->request->data['Table']['elements'][] = [
				'id' => $column->columnId,
				'name' => $column->columnName,
				'business_term' => empty($column->businessTerm[0]) ? null : $column->businessTerm[0]->termId];
		}

		$this->render();
	}

	public function syncDatabase() {
		$this->autoRender = false;
		if (!$this->request->is('post')) {
			$this->render();
		} else {
			$schemaName = $this->request->data['schema'];
			$tableName = $this->request->data['table'];

			$oracleColumns = $this->BYUAPI->oracleColumns($schemaName, $tableName);
			$table = $this->CollibraAPI->getTableObject($schemaName.' > '.$tableName);

			if (empty($oracleColumns) && !empty($table)) {
				// drop the table from Collibra
				$table->columns = $this->CollibraAPI->getTableColumns($schemaName.' > '.$tableName);
				$toDeleteIds = [];
				foreach ($table->columns as $column) {
					array_push($toDeleteIds, $column->columnId);
				}
				array_push($toDeleteIds, $table->id);

				$postString = http_build_query(['resource' => $toDeleteIds]);
				$postString = preg_replace("/%5B[0-9]*%5D/", "", $postString);
				$resp = $this->CollibraAPI->deleteJSON('term/remove/async', $postString);

				if ($resp->code != '200') {
					$resp = json_decode($resp);
					return json_encode(['success' => 0, 'message' => $resp->message, 'redirect' => 0]);
				} else {
					return json_encode(['success' => 1, 'message' => $schemaName.' > '.$tableName.' was removed from Collibra.', 'redirect' => 0]);
				}
			} else if (isset($oracleColumns[$schemaName][$tableName]) && !empty($oracleColumns[$schemaName][$tableName]) && empty($table)) {
				// add the table to Collibra
				$postData = ['schemaName' => $schemaName, 'tableName' => $schemaName.' > '.$tableName, 'columns' => $oracleColumns[$schemaName][$tableName], 'newTable' => 'true'];

				$postString = http_build_query($postData);
				$postString = preg_replace("/%5B[0-9]*%5D/", "", $postString);
				$resp = $this->CollibraAPI->post(
					'workflow/'.Configure::read('Collibra.workflow.updateDataWarehouse').'/start',
					$postString,
					['header' => ['Accept' => 'application/json']]);

				if ($resp->code != '200') {
					$resp = json_decode($resp);
					return json_encode(['success' => 0, 'message' => $resp->message, 'redirect' => 0]);
				} else {
					return json_encode(['success' => 1, 'message' => $schemaName.' > '.$tableName.' was added to Collibra\'s database.', 'redirect' => 1]);
				}
			} else if (isset($oracleColumns[$schemaName][$tableName]) && !empty($oracleColumns[$schemaName][$tableName]) && !empty($table)) {
				// run through the columns and make sure everything matches up
				$table->columns = $this->CollibraAPI->getTableColumns($schemaName.' > '.$tableName);
				$i = 0;
				$j = 0;
				$toDeleteIds = [];
				$toCreateNames = [];
				$success = true;
				$errors = '';
				while ($i < count($table->columns) && $j < count($oracleColumns[$schemaName][$tableName])) {
					if ($table->columns[$i]->columnName === $schemaName.' > '.$tableName.' > '.$oracleColumns[$schemaName][$tableName][$j]) {
						$i++;
						$j++;
					}
					else if (strcmp($table->columns[$i]->columnName, $schemaName.' > '.$tableName.' > '.$oracleColumns[$schemaName][$tableName][$j]) < 0) {
						array_push($toDeleteIds, $table->columns[$i]->columnId);
						$i++;
					}
					else if (strcmp($table->columns[$i]->columnName, $schemaName.' > '.$tableName.' > '.$oracleColumns[$schemaName][$tableName][$j]) > 0) {
						array_push($toCreateNames, $oracleColumns[$schemaName][$tableName][$j]);
						$j++;
					}
				}

				while ($i < count($table->columns)) {
					array_push($toDeleteIds, $table->columns[$i]->columnId);
					$i++;
				}

				while ($j < count($oracleColumns[$schemaName][$tableName])) {
					array_push($toCreateNames, $oracleColumns[$schemaName][$tableName][$j]);
					$j++;
				}

				if (empty($toDeleteIds) && empty($toCreateNames)) {
					return json_encode(['success' => 1, 'message' => 'The table '.$schemaName.' > '.$tableName.' is already up-to-date.', 'redirect' => 1]);
				}

				if (!empty($toDeleteIds)) {
					$postString = http_build_query(['resource' => $toDeleteIds]);
					$postString = preg_replace("/%5B[0-9]*%5D/", "", $postString);
					$resp = $this->CollibraAPI->deleteJSON('term/remove/async', $postString);

					if ($resp->code != '200') {
						$success = false;
						$errors .= 'Failed to remove dropped columns from '.$schemaName.' > '.$tableName.'. ';
					}
				}

				if (!empty($toCreateNames)) {
					$postData = ['schemaName' => $schemaName, 'tableName' => $schemaName.' > '.$tableName, 'columns' => $toCreateNames, 'newTable' => 'false'];
					$postString = http_build_query($postData);
					$postString = preg_replace("/%5B[0-9]*%5D/", "", $postString);

					$resp = $this->CollibraAPI->post(
						'workflow/'.Configure::read('Collibra.workflow.updateDataWarehouse').'/start',
						$postString,
						['header' => ['Accept' => 'application/json']]);

					if ($resp->code != '200') {
						$success = false;
						$errors .= 'Failed to add new columns in '.$schemaName.' > '.$tableName.' to Collibra.';
					}
				}

				if ($success) {
					return json_encode(['success' => 1, 'message' => $schemaName.' > '.$tableName.' has been updated to match the data warehouse.', 'redirect' => 1]);
				} else {
					return json_encode(['success' => 0, 'message' => $errors, 'redirect' => 0]);
				}
			}

			return json_encode(['success' => 1, 'message' => 'The table '.$schemaName.' > '.$tableName.' wasn\'t found in either the data warehouse or in Collibra.', 'redirect' => 0]);
		}
	}
}
