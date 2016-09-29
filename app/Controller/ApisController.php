<?php

class ApisController extends AppController {
	public $uses = ['CollibraAPI'];

	public function index() {
		$hosts = $this->CollibraAPI->getApiHosts();
		if (count($hosts) == 1) {
			return $this->redirect(['action' => 'host', 'api' => $hosts[0]]);
		}
		$this->set('hosts', $this->CollibraAPI->getApiHosts());//'community/' . Configure::read('Collibra.apiCommunity') . '/sub-communities');
	}

	public function host($hostname) {
		$community = $this->CollibraAPI->findTypeByName('community', $hostname, ['full' => true]);
		if (empty($community->resourceId)) {
			$this->redirect(['action' => 'index']);
		}
		$dataAssetDomainTypeId = Configure::read('Collibra.dataAssetDomainTypeId');
		$techAssetDomainTypeId = Configure::read('Collibra.techAssetDomainTypeId');
		$this->set(compact('hostname', 'community', 'dataAssetDomainTypeId', 'techAssetDomainTypeId'));
	}

	public function api() {
		$args = func_get_args();
		$hostname = array_shift($args);
		$basePath = '/' . implode('/', $args);
		$terms = $this->CollibraAPI->getApiTerms($hostname, $basePath);
		if (empty($terms)) {
			return $this->redirect(['action' => 'host', 'api' => $hostname]);
		}

		$this->set(compact('hostname', 'basePath', 'terms'));
	}
}