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

	public function customView() {
		
	}
}
