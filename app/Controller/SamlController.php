<?php

class SamlController extends AppController {
	public $uses = ['CollibraAPI'];

	public function index() {
		$responses = [];
		$rawResponses = $this->CollibraAPI->getSamlResponses();
		foreach ($rawResponses as $resp) {
			if($resp->responseName != "SAML Custom Attributes" && $resp->responseName != "SAML Optional Attributes") {
				$responses[] = $resp;
			}
		}
		$this->set('responses', $responses);
	}

	public function view($responseName) {
		$fields = $this->CollibraAPI->getSamlResponseFields($responseName);
		$rawResponses = $this->CollibraAPI->getSamlResponses();
		foreach($rawResponses as $resp) {
			if($resp->responseName == $responseName) {
				$tableInfo = $this->CollibraAPI->getAttributes($resp->responseId);
				foreach($tableInfo as $attr) {
					//Usage Notes
					if($attr['Usage Notes']->attrTypeId == "3dda4b76-2b31-4dbd-a058-729dac94f230") {
						$usageNotes = $attr['Usage Notes']->attrValue;
					}
				}
			}
		}
		$this->set(compact('responseName', 'fields', 'usageNotes'));
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
