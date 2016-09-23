<?php

class SwaggerController extends AppController {

	public $uses = ['Swagger', 'CollibraAPI'];

	function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->deny();
	}

	public function index() {
		if ($this->request->is('post')) {
			$swag = $this->request->data('Swagger.swag');
			$swagUrl = $this->request->data('Swagger.url');
			$filename = $this->_swaggerFilename();
			if (!empty($swagUrl)) {
				$swag = file_get_contents($swagUrl);
				if (empty($swag)) {
					$this->Session->setFlash('Error downloading from URL', 'default', ['class' => 'error']);
					return;
				}
				if (!file_put_contents($filename, file_get_contents($swagUrl))) {
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
			$import = $this->CollibraAPI->importSwagger($this->request->data('Swagger'));
			if (!empty($import)) {
				unlink($this->_swaggerFilename());
				$this->Session->setFlash('Swagger data imported successfully');
				$this->redirect(['action' => 'index']);
			}
			$this->Session->setFlash('Error: ' . implode('<br>', $this->CollibraAPI->errors), 'default', ['class' => 'error']);
		} else {
			$this->request->data['Swagger'] = $this->_getUploadedSwagger();
			if (empty($this->request->data['Swagger'])) {
				$this->Session->setFlash('Error: ' . implode('<br>', $this->Swagger->parseErrors), 'default', ['class' => 'error']);
				return $this->redirect(['action' => 'index']);
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