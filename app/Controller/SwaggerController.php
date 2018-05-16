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
