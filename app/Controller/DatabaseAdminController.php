<?php

class DatabaseAdminController extends AppController {
	public $uses = ['CollibraAPI', 'BYUAPI'];
	public $components = ['DataWarehouse'];

	function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->deny();
	}

	public function update($databaseName, $schemaName, $tableName) {
		$this->autoRender = false;
		$this->set(compact('databaseName', 'schemaName', 'tableName'));

		if ($this->request->is('post')) {
			$success = $this->CollibraAPI->updateBusinessTermLinks($this->request->data('Table.elements'));
			if (!empty($success)) {
				$this->Session->setFlash('Table updated successfully');
				return json_encode(['success' => '1']);
			}
			$this->Session->setFlash('Error: ' . implode('<br>', $this->CollibraAPI->errors), 'default', ['class' => 'error']);
			return json_encode(['success' => '0']);
		}

		$columns = $this->CollibraAPI->getTableColumns($databaseName, $tableName);
		if (empty($columns)) {
			return $this->redirect(['controller' => 'databases', 'action' => 'schema', $schemaName]);
		}
		$glossaries = $this->CollibraAPI->getAllGlossaries();
		$this->set(compact('columns', 'glossaries'));

		$this->request->data = [
			'Table' => [
				'databaseName' => $databaseName,
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
			$resp = json_decode($this->CollibraAPI->get('community/'.Configure::read('Collibra.community.dataWarehouse').'/sub-communities'));
			$databases = array_column($resp->communityReference, 'name');
			$this->set(compact('databases'));
			$this->render();
		} else {
			$databaseName = $this->request->data['database'];
			$schemaName = $this->request->data['schema'];
			$tableName = $this->request->data['table'];
			if (!empty($tableName)) {
				$oracleColumns = $this->BYUAPI->oracleColumns($databaseName, $schemaName, $tableName);
				if (!isset($this->request->data['diff'])) {
					$collibraTable = $this->CollibraAPI->getTableObject($databaseName, $schemaName.' > '.$tableName);
					if (!empty($collibraTable)) {
						return json_encode(['diff' => 1]);
					}
				}
				return json_encode($this->DataWarehouse->syncDataWarehouse($databaseName, $schemaName, $tableName, $oracleColumns));
			} else {
				$success = true;
				$errors = [];
				$imported = [];
				$conflicts = [];
				$oracleSchema = $this->BYUAPI->oracleColumns($databaseName, $schemaName);
				foreach ($oracleSchema as $tableName => $oracleColumns) {
					$collibraTable = $this->CollibraAPI->getTableObject($databaseName, $schemaName.' > '.$tableName);
					if (!empty($collibraTable)) {
						array_push($conflicts, $tableName);
						continue;
					}

					$resp = $this->DataWarehouse->syncDataWarehouse($databaseName, $schemaName, $tableName, $oracleColumns);
					if (!$resp['success']) {
						$success = false;
						array_push($errors, $resp['message']);
						continue;
					}
					array_push($imported, $tableName);
				}
				if ($success) {
					$message = 'The following tables from the schema '.$databaseName.' > '.$schemaName.' have been imported successfully: '.implode(', ', $imported).'.';
					if (!empty($conflicts)) $message .= "\n\n".'The following tables were not imported because they are already listed in InfoHub: '.implode(', ', $conflicts).'. '.
														'You can attempt to move forward with updating them by inputting each table individually.';
					return json_encode(['success' => 1, 'message' => $message, 'redirect' => 1]);
				} else {
					return json_encode(['success' => 0, 'message' => $errors, 'redirect' => 0]);
				}
			}
		}
	}

	public function diff($databaseName, $schemaName, $tableName) {
		$oracleColumns = $this->BYUAPI->oracleColumns($databaseName, $schemaName, $tableName);
		$collibraTable = $this->CollibraAPI->getTableObject($databaseName, $schemaName.' > '.$tableName);
		$collibraTable->columns = $this->CollibraAPI->getTableColumns($databaseName, $schemaName.' > '.$tableName);
		list($oldRows, $newRows, $removedElems, $addedElems) = $this->_calculateDiff($collibraTable, $oracleColumns);

		$requested = false;
		foreach ($collibraTable->dataSharingRequests as $dsr) {
			if (!in_array($dsr->dsrStatus, ['Canceled', 'Deleted', 'Obsolete'])) {
				$requested = true;
				break;
			}
		}
		$blockedChange = $requested && (!empty($removedElems) || !empty($addedElems));
		if ($blockedChange) {
			$emailBody = "An InfoHub user ({$_SESSION["byuUsername"]}) attempted to re-import the Data Warehouse table {$collibraTable->name}. The change wasn't completed because the table is requested in the following DSRs:<br/>";
			foreach ($collibraTable->dataSharingRequests as $dsr) {
				$emailBody .= $dsr->dsrName."<br/>";
			}
			$emailBody .= "<br/>Removed elements:<br/>";
			foreach ($removedElems as $elem) {
				$emailBody .= $elem."<br/>";
			}
			$emailBody .= "<br/>Added elements:<br/>";
			foreach ($addedElems as $elem) {
				$emailBody .= $elem."<br/>";
			}

			$postData = [
				'subjectLine' => 'Requested dataset update',
				'emailBody' => $emailBody
			];
			$postString = http_build_query($postData);
			$resp = $this->CollibraAPI->post('workflow/'.Configure::read('Collibra.workflow.emailGovernanceDirectors').'/start', $postString);
		}

		$this->set(compact('collibraTable', 'oracleColumns', 'oldRows', 'newRows', 'blockedChange'));
	}

	protected function _calculateDiff($collibraTable, $oracleColumns) {
		$i = $j = 0;
		$oldRows = $newRows = '';
		$removedElems = $addedElems = [];
		while ($i < count($collibraTable->columns) && $j < count($oracleColumns)) {
			$cn = end((explode(' > ', $collibraTable->columns[$i]->columnName)));

			if ($cn == $oracleColumns[$j]) {
				$bt = !empty($collibraTable->columns[$i]->businessTerm) ? $collibraTable->columns[$i]->businessTerm[0]->term : '';
				$oldRows .= '<tr><td>'.$cn.'</td><td>'.$bt.'</td></tr>';
				$newRows .= '<tr><td>'.$oracleColumns[$j].'</td></tr>';
				$i++;
				$j++;
			} else if ($cn < $oracleColumns[$j]) {
				$bt = !empty($collibraTable->columns[$i]->businessTerm) ? $collibraTable->columns[$i]->businessTerm[0]->term : '';
				$oldRows .= '<tr class="removed"><td>'.$cn.'</td><td>'.$bt.'</td></tr>';
				array_push($removedElems, $cn);
				$newRows .= '<tr><td>&nbsp;</td></tr>';
				$i++;
			} else if ($cn > $oracleColumns[$j]) {
				$oldRows .= '<tr><td>&nbsp;</td><td></td></tr>';
				$newRows .= '<tr class="added"><td>'.$oracleColumns[$j].'</td></tr>';
				array_push($addedElems, $oracleColumns[$j]);
				$j++;
			}
		}
		while ($i < count($collibraTable->columns)) {
			$bt = !empty($collibraTable->columns[$i]->businessTerm) ? $collibraTable->columns[$i]->businessTerm[0]->term : '';
			$oldRows .= '<tr class="removed"><td>'.$cn.'</td><td>'.$bt.'</td></tr>';
			array_push($removedElems, $cn);
			$newRows .= '<tr><td>&nbsp;</td></tr>';
			$i++;
		}
		while ($j < count($oracleColumns)) {
			$oldRows .= '<tr><td>&nbsp;</td><td></td></tr>';
			$newRows .= '<tr class="added"><td>'.$oracleColumns[$j].'</td></tr>';
			array_push($addedElems, $oracleColumns[$j]);
			$j++;
		}

		return [$oldRows, $newRows, $removedElems, $addedElems];
	}
}
