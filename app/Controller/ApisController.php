<?php

class ApisController extends AppController {
	public $uses = ['CollibraAPI', 'BYUAPI'];

	public function index() {
		$hosts = $this->CollibraAPI->getApiHosts();
		if (count($hosts) == 1) {
			return $this->redirect(['action' => 'host', 'hostname' => $hosts[0]]);
		}
		$this->set('hosts', $hosts);
	}

	public function host($hostname) {
		$community = $this->CollibraAPI->findTypeByName('community', $hostname, ['full' => true]);
		if (empty($community->resourceId)) {
			$this->redirect(['action' => 'index']);
		}
		if (!empty($community->vocabularyReferences->vocabularyReference)) {
			usort(
				$community->vocabularyReferences->vocabularyReference,
				function ($a, $b) {
					return strcmp(strtolower($a->name), strtolower($b->name));
				});
		}
		$dataAssetDomainTypeId = Configure::read('Collibra.dataAssetDomainTypeId');
		$techAssetDomainTypeId = Configure::read('Collibra.techAssetDomainTypeId');
		$this->set(compact('hostname', 'community', 'dataAssetDomainTypeId', 'techAssetDomainTypeId'));
	}

	public function view() {
		$args = func_get_args();
		$hostname = array_shift($args);
		$basePath = '/' . implode('/', $args);
		if (!isset($this->request->query['upper'])) {
			$basePath = strtolower($basePath);
		}
		$terms = $this->CollibraAPI->getApiTerms($hostname, $basePath);
		if (empty($terms) && !isset($this->request->query['upper'])) {
			return $this->redirect($hostname.'/'.implode('/', $args).'?upper=1');
		}
		if (empty($terms)) {
			//Check if non-existent API, or simply empty API
			$community = $this->CollibraAPI->findTypeByName('community', $hostname, ['full' => true]);
			if (empty($community->vocabularyReferences->vocabularyReference)) {
				return $this->redirect(['action' => 'host', 'hostname' => $hostname]);
			}
			$found = false;
			foreach ($community->vocabularyReferences->vocabularyReference as $endpoint) {
				if ($endpoint->name == $basePath) {
					$found = true;
					break;
				}
			}
			if (!$found) {
				return $this->redirect(['action' => 'host', 'hostname' => $hostname]);
			}
		}
		$this->set(compact('hostname', 'basePath', 'terms'));

		if (array_key_exists('checkout', $this->request->query)) {
			return $this->_autoCheckout($hostname, $basePath, $terms);
		}
	}

	protected function _autoCheckout($hostname, $basePath, $terms) {
		$queue = $this->Session->read('queue');
		foreach ($terms as $term) {
			if (empty($term->businessTerm[0])) {
				continue;
			}
			$queue->businessTerms[$term->businessTerm[0]->termId] = [
				'term' => $term->businessTerm[0]->term,
				'communityId' => $term->businessTerm[0]->termCommunityId,
				'apiHost' => $hostname,
				'apiPath' => $basePath];
		}

		$this->Session->write('queue', $queue);
		return $this->redirect(['controller' => 'request', 'action' => 'index']);
	}

	public function deep_links() {
		$args = func_get_args();
		$hostname = array_shift($args);
		$basePath = '/' . implode('/', $args);
		return new CakeResponse(['type' => 'json', 'body' => json_encode($this->BYUAPI->deepLinks($basePath))]);
	}
}
