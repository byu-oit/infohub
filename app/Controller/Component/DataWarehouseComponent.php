<?php

App::uses('Component', 'Controller');

class DataWarehouseComponent extends Component {
    public $components = ['Post'];

    public function syncDataWarehouse($databaseName, $schemaName, $tableName, $oracleColumns) {
        $this->CollibraAPI = ClassRegistry::init('CollibraAPI');
        $table = $this->CollibraAPI->getTableObject($databaseName, $schemaName.' > '.$tableName);

        if (empty($oracleColumns) && !empty($table)) {
            // drop the table from Collibra
            $table->columns = $this->CollibraAPI->getTableColumns($databaseName, $schemaName.' > '.$tableName);
            $toDeleteIds = [];
            foreach ($table->columns as $column) {
                array_push($toDeleteIds, $column->columnId);
            }
            array_push($toDeleteIds, $table->id);

            $resp = $this->CollibraAPI->deleteJSON('term/remove/async', $this->Post->preparePostData(['resource' => $toDeleteIds]));

            if ($resp->code != '200') {
                $resp = json_decode($resp);
                return ['success' => 0, 'message' => $resp->message, 'redirect' => 0];
            } else {
                return ['success' => 1, 'message' => $schemaName.' > '.$tableName.' was removed from Collibra.', 'redirect' => 0];
            }
        } else if (!empty($oracleColumns) && empty($table)) {
            // add the table to Collibra
            $postData = ['database' => $databaseName, 'schemaName' => $schemaName, 'tableName' => $schemaName.' > '.$tableName, 'columns' => $oracleColumns, 'newTable' => 'true'];

            $resp = $this->CollibraAPI->post(
                'workflow/'.Configure::read('Collibra.workflow.updateDataWarehouse').'/start',
                $this->Post->preparePostData($postData),
                ['header' => ['Accept' => 'application/json']]);

            if ($resp->code != '200') {
                $resp = json_decode($resp);
                return ['success' => 0, 'message' => $resp->message, 'redirect' => 0];
            } else {
                return ['success' => 1, 'message' => $schemaName.' > '.$tableName.' was added to Collibra\'s database.', 'redirect' => 1];
            }
        } else if (!empty($oracleColumns) && !empty($table)) {
            // run through the columns and make sure everything matches up
            $table->columns = $this->CollibraAPI->getTableColumns($databaseName, $schemaName.' > '.$tableName);
            $i = 0;
            $j = 0;
            $toDeleteIds = [];
            $toCreateNames = [];
            $success = true;
            $errors = '';
            while ($i < count($table->columns) && $j < count($oracleColumns)) {
                if ($table->columns[$i]->columnName === $schemaName.' > '.$tableName.' > '.$oracleColumns[$j]) {
                    $i++;
                    $j++;
                }
                else if (strcmp($table->columns[$i]->columnName, $schemaName.' > '.$tableName.' > '.$oracleColumns[$j]) < 0) {
                    array_push($toDeleteIds, $table->columns[$i]->columnId);
                    $i++;
                }
                else if (strcmp($table->columns[$i]->columnName, $schemaName.' > '.$tableName.' > '.$oracleColumns[$j]) > 0) {
                    array_push($toCreateNames, $oracleColumns[$j]);
                    $j++;
                }
            }

            while ($i < count($table->columns)) {
                array_push($toDeleteIds, $table->columns[$i]->columnId);
                $i++;
            }

            while ($j < count($oracleColumns)) {
                array_push($toCreateNames, $oracleColumns[$j]);
                $j++;
            }

            if (empty($toDeleteIds) && empty($toCreateNames)) {
                return ['success' => 1, 'message' => 'The table '.$schemaName.' > '.$tableName.' is already up-to-date.', 'redirect' => 1, 'noChange' => 1];
            }

            if (!empty($toDeleteIds)) {
                $resp = $this->CollibraAPI->deleteJSON('term/remove/async', $this->Post->preparePostData(['resource' => $toDeleteIds]));

                if ($resp->code != '200') {
                    $success = false;
                    $errors .= 'Failed to remove dropped columns from '.$schemaName.' > '.$tableName.'. ';
                }
            }

            if (!empty($toCreateNames)) {
                $postData = ['schemaName' => $schemaName, 'tableName' => $schemaName.' > '.$tableName, 'columns' => $toCreateNames, 'newTable' => 'false'];
                $resp = $this->CollibraAPI->post(
                    'workflow/'.Configure::read('Collibra.workflow.updateDataWarehouse').'/start',
                    $this->Post->preparePostData($postData),
                    ['header' => ['Accept' => 'application/json']]);

                if ($resp->code != '200') {
                    $success = false;
                    $errors .= 'Failed to add new columns in '.$schemaName.' > '.$tableName.' to Collibra.';
                }
            }

            if ($success) {
                return ['success' => 1, 'message' => $schemaName.' > '.$tableName.' has been updated to match the data warehouse.', 'redirect' => 1];
            } else {
                return ['success' => 0, 'message' => $errors, 'redirect' => 0];
            }
        }

        return ['success' => 1, 'message' => $schemaName.' > '.$tableName.' wasn\'t found in either the data warehouse or in Collibra.', 'redirect' => 0];
    }
}
