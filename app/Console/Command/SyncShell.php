<?php

session_save_path(TMP.'/sessions');

App::uses('DataWarehouseComponent', 'Controller/Component');
App::uses('ComponentCollection', 'Controller');

class SyncShell extends AppShell {
    public $uses = ['BYUAPI', 'CollibraAPI'];

    public function getOptionParser() {
        $parser = parent::getOptionParser();
        $parser->addArgument('tables-file', [
            'help' => 'Path to a JSON file containing the list of tables to be synced',
            'required' => true
        ]);
        return $parser;
    }

    // Override inherited function to not print welcome message to logs
    public function _welcome() {
    }

    public function main() {
        $this->out(date('Y-m-d H:i:s'));

        $tablesJSON = file_get_contents($this->args[0]);
        if ($tablesJSON === false) {
            $this->out('Error reading the list of tables.', 2);
            return;
        }
        $tables = json_decode($tablesJSON, true);

        $arrAllColumns = [];
        $columnsCount = 0;
        foreach ($tables as $table) {
            $cols = $this->BYUAPI->oracleColumns($table['database'], $table['schema'], $table['table']);
            $arrAllColumns[$table['database']][$table['schema']][$table['table']] = $cols;
            $columnsCount += count($cols);
        }

        if ($columnsCount == 0) {
            $this->out(date('H:i:s').' - Failed to read any Oracle database columns.', 2);
            return;
        }

        $hash = hash('sha256', json_encode($arrAllColumns));
        if (file_exists(TMP.'/cache/databaseColumnsHash')) {
            $previousHash = file_get_contents(TMP.'/cache/databaseColumnsHash');
            if ($hash === $previousHash) {
                $this->out(date('H:i:s').' - No change detected in the database.', 2);
                return;
            }

            $this->out(date('H:i:s').' - New hash didn\'t match the saved one. Double-checking the result.');
            $arrDoubleCheckColumns = [];
            foreach ($tables as $table) {
                $arrDoubleCheckColumns[$table['database']][$table['schema']][$table['table']] =
                    $this->BYUAPI->oracleColumns($table['database'], $table['schema'], $table['table']);
            }
            $doubleCheckHash = hash('sha256', json_encode($arrDoubleCheckColumns));
            if ($doubleCheckHash !== $hash) {
                $this->out(date('H:i:s').' - Inconsistent results. Aborting.', 2);
                return;
            }

            $this->out(date('H:i:s').' - Change in database asserted.', 2);
        } else {
            $this->out(date('H:i:s').' - No saved hash found.', 2);
        }
        file_put_contents(TMP.'/cache/databaseColumnsHash', $hash);

        $collection = new ComponentCollection();
        $this->DataWarehouse = $collection->load('DataWarehouse');
        foreach ($arrAllColumns as $databaseName => $databaseSchemas) {
            foreach ($databaseSchemas as $schemaName => $schemaTables) {
                foreach ($schemaTables as $tableName => $columns) {

                    $collibraTable = $this->CollibraAPI->getTableObject($databaseName, $schemaName.' > '.$tableName);
                    if (isset($collibraTable->tableAltered) && $collibraTable->tableAltered == 'true') {
                        continue;
                    }

                    $requested = false;
                    foreach ($collibraTable->dataSharingRequests as $dsr) {
                        if (!in_array($dsr->dsrStatus, ['Canceled', 'Deleted', 'Obsolete'])) {
                            $requested = true;
                            break;
                        }
                    }
                    if ($requested) {
                        $collibraTable->columns = $this->CollibraAPI->getTableColumns($databaseName, $schemaName.' > '.$tableName);
                        $i = 0;
                        $j = 0;
                        $changed = false;
                        while ($i < count($collibraTable->columns) && $j < count($columns)) {
                            if ($collibraTable->columns[$i]->columnName === $schemaName.' > '.$tableName.' > '.$columns[$j]) {
                                $i++;
                                $j++;
                            }
                            else {
                                $changed = true;
                                break;
                            }
                        }

                        if (empty($columns) || $changed) {
                            if (!isset($collibraTable->tableAltered) || $collibraTable->tableAltered != 'true') {
                                $postString = http_build_query([
                                    'label' => Configure::read('Collibra.attribute.tableAltered'),
                                    'value' => 'true'
                                ]);
                                $resp = $this->CollibraAPI->post('term/'.$collibraTable->id.'/attributes', $postString);

                                $emailBody = "The automated data warehouse sync job detected a change to {$databaseName} > {$schemaName} > {$tableName}. The change hasn't been reflected in Collibra because the table is part of an active Data Sharing Request.<br/><br/>"
                                             ."Please review the changes to the table via the InfoHub table import UI. If you change the data in Collibra to match the data warehouse, remember to set the table's attribute Table Altered to false.";
                                $postString = http_build_query([
                                    'subjectLine' => 'InfoHub: Change in Data Warehouse',
                                    'emailBody' => $emailBody
                                ]);
                                $resp = $this->CollibraAPI->post('workflow/'.Configure::read('Collibra.workflow.emailGovernanceDirectors').'/start', $postString);
                            }
                            continue;
                        }
                    }

                    $resp = $this->DataWarehouse->syncDataWarehouse($databaseName, $schemaName, $tableName, $columns);
                    $level = isset($resp['noChange']) ? Shell::VERBOSE : Shell::NORMAL;
                    $this->out(date('H:i:s').' - '.$resp['message'], 1, $level);
                }
            }
        }
        $this->out(' ');
    }
}
