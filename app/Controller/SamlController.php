<?php

class SamlController extends AppController {
	public $uses = ['CollibraAPI', 'BYUAPI'];

	public function index() {
        $responses = $this->CollibraAPI->getSamlResponses();
        $this->set('responses', $responses);
	}

	public function view($responseName) {
		$fields = $this->CollibraAPI->getSamlResponseFields($responseName);
		$isOITEmployee = $this->BYUAPI->isGROGroupMember($this->Auth->user('username'), 'oit04');
		$this->set(compact('responseName', 'fields', 'isOITEmployee'));
	}
}
