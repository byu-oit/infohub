<?php

class SamlController extends AppController {
	public $uses = ['CollibraAPI'];

	public function index() {
        $responses = $this->CollibraAPI->getSamlResponses();
        $this->set('responses', $responses);
	}

	public function view($responseName) {
		$fields = $this->CollibraAPI->getSamlResponseFields($responseName);
		$this->set(compact('responseName', 'fields'));
	}

	public function viewRequested($requestId, $responseName) {
		$fields = $this->CollibraAPI->getSamlResponseFields($responseName);

		$request = $this->CollibraAPI->getRequestDetails($requestId);
		$requestedAssetIds = [];
		foreach ($request->requestedDataAssets as $asset) {
			array_push($requestedAssetIds, $asset->reqDataId);
		}
		$this->set(compact('responseName', 'fields', 'request', 'requestedAssetIds'));
	}

	public function customView() {

	}
}
