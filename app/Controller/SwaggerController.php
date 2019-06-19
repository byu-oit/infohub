<?php

class SwaggerController extends AppController {

	public $uses = ['Swagger', 'CollibraAPI'];

	function beforeFilter() {
		parent::beforeFilter();
		if ($this->request->param('action') == 'import') {
			$this->Auth->authenticate = ['QuickDirty'];
		}
		$this->Auth->allow('import');
	}

	public function index() {
		if ($this->request->is('post')) {
			$swag = $this->request->data('Swagger.swag');
			$swagUrl = $this->request->data('Swagger.url');
			$filename = $this->_swaggerFilename();
			if (!empty($swagUrl)) {
				$swag = $this->Swagger->downloadFile($swagUrl);
				if (empty($swag)) {
					$this->Session->setFlash('Error downloading from URL', 'default', ['class' => 'error']);
					return;
				}
				if (!file_put_contents($filename, $swag)) {
					$this->Session->setFlash('Error saving swagger file locally', 'default', ['class' => 'error']);
					return;
				}
				$this->redirect(['action' => 'process']);
			}
			if (empty($swag) || !is_array($swag) || !array_key_exists('error', $swag) || !array_key_exists('tmp_name', $swag) || $swag['error'] != UPLOAD_ERR_OK) {
				$this->Session->setFlash('Error uploading file', 'default', ['class' => 'error']);
				return;
			}
			$moved = move_uploaded_file($swag['tmp_name'], $filename);
			if (!$moved) {
				$this->Session->setFlash('Error moving uploaded file', 'default', ['class' => 'error']);
				return;
			}
			$this->redirect(['action' => 'process']);
		}
	}

	public function process() {
		if ($this->request->is('post')) {
			$import = $this->CollibraAPI->importSwagger($this->request->data('Api'));
			if (!empty($import)) {
				unlink($this->_swaggerFilename());
				$this->Session->setFlash('Swagger data imported successfully');
				$this->redirect(['action' => 'index']);
			}
			$this->Session->setFlash('Error: ' . implode('<br>', $this->CollibraAPI->errors), 'default', ['class' => 'error']);
		} else {
			$this->request->data['Api'] = $this->_getUploadedSwagger();
			if (empty($this->request->data['Api'])) {
				$this->Session->setFlash('Error: ' . implode('<br>', $this->Swagger->parseErrors), 'default', ['class' => 'error']);
				return $this->redirect(['action' => 'index']);
			}
			$fields = $this->CollibraAPI->getApiFields($this->request->data['Api']['host'], $this->request->data['Api']['basePath'].'/'.$this->request->data['Api']['version']);
			if (!empty($fields)) {
				if (!isset($this->request->query['diff'])) {
					$this->redirect(['action' => 'diff']);
				}

				$this->request->data['Api']['destructiveUpdate'] = true;
				for ($i = 0; $i < count($this->request->data['Api']['elements']); $i++) {
					foreach ($fields as $existingField) {
						if ($this->request->data['Api']['elements'][$i]['name'] == $existingField->name) {
							$this->request->data['Api']['elements'][$i]['businessTerm'] = $existingField->businessTerm;
							break;
						}
					}
				}
			}
		}
	}

	public function diff() {
		$newApi = $this->_getUploadedSwagger();
		$oldApi = $this->CollibraAPI->getApiObject($newApi['host'], $newApi['basePath'].'/'.$newApi['version']);
		$oldApi->fields = $this->CollibraAPI->getApiFields($newApi['host'], $newApi['basePath'].'/'.$newApi['version']);
		usort($newApi['elements'], function($a, $b) {
			return strcmp($a['name'], $b['name']);
		});

		list($oldRows, $newRows, $removedElems, $addedElems) = $this->_calculateDiff($oldApi, $newApi);

		$requested = false;
		foreach ($oldApi->dataSharingRequests as $dsr) {
			if (!in_array($dsr->dsrStatus, ['Canceled', 'Deleted', 'Obsolete'])) {
				$requested = true;
				break;
			}
		}
		if ($requested && (!empty($removedElems) || !empty($addedElems))) {
			$emailBody = "An InfoHub user ({$_SESSION["byuUsername"]}) attempted to re-import the API {$oldApi->name}. The change wasn't completed because the API is requested in the following DSRs:<br/>";
			foreach ($oldApi->dataSharingRequests as $dsr) {
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

		$this->set(compact('oldApi', 'newApi', 'oldRows', 'newRows', 'requested'));
	}

	protected function _calculateDiff($oldApi, $newApi) {
		$i = $j = 0;
		$oldRows = $newRows = '';
		$removedElems = $addedElems = [];
		while ($i < count($oldApi->fields) && $j < count($newApi['elements'])) {
			if ($oldApi->fields[$i]->name == $newApi['elements'][$j]['name']) {
				$bt = !empty($oldApi->fields[$i]->businessTerm) ? $oldApi->fields[$i]->businessTerm[0]->term : '';
				$oldRows .= '<tr><td>'.$oldApi->fields[$i]->name.'</td><td>'.$bt.'</td></tr>';
				$newRows .= '<tr><td>'.$newApi['elements'][$j]['name'].'</td></tr>';
				$i++;
				$j++;
			} else if ($oldApi->fields[$i]->name < $newApi['elements'][$j]['name']) {
				$bt = !empty($oldApi->fields[$i]->businessTerm) ? $oldApi->fields[$i]->businessTerm[0]->term : '';
				$oldRows .= '<tr class="removed"><td>'.$oldApi->fields[$i]->name.'</td><td>'.$bt.'</td></tr>';
				array_push($removedElems, $oldApi->fields[$i]->name);
				$newRows .= '<tr><td>&nbsp;</td></tr>';
				$i++;
			} else if ($oldApi->fields[$i]->name > $newApi['elements'][$j]['name']) {
				$oldRows .= '<tr><td>&nbsp;</td><td></td></tr>';
				$newRows .= '<tr class="added"><td>'.$newApi['elements'][$j]['name'].'</td></tr>';
				array_push($addedElems, $newApi['elements'][$j]['name']);
				$j++;
			}
		}
		while ($i < count($oldApi->fields)) {
			$bt = !empty($oldApi->fields[$i]->businessTerm) ? $oldApi->fields[$i]->businessTerm[0]->term : '';
			$oldRows .= '<tr class="removed"><td>'.$oldApi->fields[$i]->name.'</td><td>'.$bt.'</td></tr>';
			array_push($removedElems, $oldApi->fields[$i]->name);
			$newRows .= '<tr><td>&nbsp;</td></tr>';
			$i++;
		}
		while ($j < count($newApi['elements'])) {
			$oldRows .= '<tr><td>&nbsp;</td><td></td></tr>';
			$newRows .= '<tr class="added"><td>'.$newApi['elements'][$j]['name'].'</td></tr>';
			array_push($addedElems, $newApi['elements'][$j]['name']);
			$j++;
		}

		return [$oldRows, $newRows, $removedElems, $addedElems];
	}

	public function find_business_term($label = null) {
		session_write_close(); //Not writing out data past this point, so do not lock session
		if (empty($label)) {
			$label = $this->request->query('label');
		}
		if (empty($label)) {
			$label = $this->request->data('label');
		}
		if (empty($label)) {
			return new CakeResponse(['type' => 'json', 'body' => '[]']);
		}
		$response = $this->CollibraAPI->searchStandardLabel($label);
		return new CakeResponse(['type' => 'json', 'body' => json_encode($response)]);
	}

	public function import() {
		return new CakeResponse(['type' => 'json', 'body' => json_encode($this->_import())]);
	}

	protected function _import() {
		if (!$this->request->is('post')) {
			return ['error' => ['messages' => ['POST only']]];
		}

		if (isset($this->request->data['url'])) {
			$json = $this->Swagger->downloadFile($this->request->data['url']);
			if (empty($json)) {
				return ['error' => ['messages' => ["Unable to download swagger from {$this->request->data['url']}"]]];
			}
		} else {
			$json = $this->request->input();
			if (empty($json)) {
				return ['error' => ['messages' => ["No swagger document specified"]]];
			}
		}

		$swagger = $this->Swagger->parse($json);
		if (empty($swagger)) {
			return ['error' => ['messages' => $this->Swagger->parseErrors]];
		}

		$termToFieldRelationshipId = Configure::read('Collibra.relationship.termToDataAsset');
		foreach ($swagger['elements'] as &$elem) {
			$businessTerm = $this->CollibraAPI->searchStandardLabel($elem['name']);
			if (!empty($businessTerm)) {
				$elem['business_term'] = $businessTerm[0]->name->id;
			}
		}

		$import = $this->CollibraAPI->importSwagger($swagger);
		if (empty($import)) {
			return ['error' => ['messages' => $this->CollibraAPI->errors]];
		}

		return ['status' => 'success', 'link' => "{$this->request->host()}/apis/{$swagger['host']}{$swagger['basePath']}/{$swagger['version']}"];
	}

	protected function _getUploadedSwagger() {
		$filename = $this->_swaggerFilename();
		if (!file_exists($filename)) {
			$this->Swagger->parseErrors[] = "Unable to read Swagger doc";
			return false;
		}
		$json = file_get_contents($filename);

		return $this->Swagger->parse($json);
	}

	protected function _swaggerFilename() {
		return TMP . DS . 'swagger' . DS . $this->Session->id() . '.json';
	}
}
