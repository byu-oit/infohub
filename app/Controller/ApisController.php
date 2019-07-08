<?php

class ApisController extends AppController {
	public $uses = ['CollibraAPI', 'BYUAPI'];
	public $helpers = ['Fieldset'];

	public function index() {
		$hosts = $this->CollibraAPI->getApiHosts();
		if (count($hosts) == 1) {
			return $this->redirect(['action' => 'host', 'hostname' => $hosts[0]]);
		}
		$this->set('hosts', $hosts);
	}

	public function host($hostname) {
		if ($this->Session->check('recentAPIs')) {
			$this->set('recent', $this->Session->read('recentAPIs'));
		}

		$community = $this->CollibraAPI->findTypeByName('community', $hostname);
		if (empty($community->resourceId)) {
			$this->redirect(['action' => 'index']);
		}
		$apis = $this->CollibraAPI->getHostApis($community->resourceId);

		$sortedApis = [];
		$noEndpoints = false;
		if (!empty($apis)) {
			usort($apis, function ($a, $b) {
				return strcmp(strtolower($a->name), strtolower($b->name));
			});
			foreach ($apis as $api) {
				if (
					$api->statusId == Configure::read('Collibra.status.testing') ||
					$api->statusId == Configure::read('Collibra.status.retired')
					) continue;
				$sortedApis[$api->statusId][] = $api;
			}
		} else {
			$noEndpoints = true;
		}


		$this->set(compact('hostname', 'community', 'noEndpoints', 'sortedApis'));
	}

	public function view() {
		$args = func_get_args();
		$hostname = array_shift($args);
		$basePath = '/' . implode('/', $args);
		if (!isset($this->request->query['upper'])) {
			$basePath = strtolower($basePath);
		}
		$fields = $this->CollibraAPI->getApiFields($hostname, $basePath, true);
		if (empty($fields) && !isset($this->request->query['upper'])) {
			return $this->redirect($hostname.'/'.implode('/', $args).'?upper=1');
		}
		if (empty($fields)) {
			//Check if non-existent API, or simply empty API
			$community = $this->CollibraAPI->findTypeByName('community', $hostname, ['full' => true]);
			if (empty($community->vocabularyReferences->vocabularyReference)) {
				return $this->redirect(['action' => 'host', 'hostname' => $hostname]);
			}
			$found = in_array($basePath, array_column($community->vocabularyReferences->vocabularyReference, 'name'));
			if (!$found) {
				$this->Flash->error('We couldn\'t find that API in our database.');
				return $this->redirect(['action' => 'host', 'hostname' => $hostname]);
			}
		}
		$containsFieldset = count(array_filter(array_column($fields, 'descendantFields')));
		$apiObject = $this->CollibraAPI->getApiObject($hostname, $basePath);
		$matchAuthorized = $this->BYUAPI->isGROGroupMemberAny($this->Auth->user('username'), 'oit04', 'infohub-match');

		$coordinatorId = $this->CollibraAPI->getResponsibilities(Configure::read('Collibra.community.dataGovernanceCouncil'))['Steward'][0]->resourceId;
		$coordinator = json_decode($this->CollibraAPI->get('user/'.$coordinatorId));
		$this->set(compact('hostname', 'basePath', 'fields', 'apiObject', 'matchAuthorized', 'containsFieldset', 'coordinator'));

		$arrRecent = $this->Session->check('recentAPIs') ? $this->Session->read('recentAPIs') : [];
		array_unshift($arrRecent, ['host' => $hostname, 'basePath' => $basePath]);
		$arrRecent = array_unique($arrRecent, SORT_REGULAR);
		$this->Session->write('recentAPIs', array_slice($arrRecent, 0, 5));

		if (array_key_exists('checkout', $this->request->query)) {
			return $this->_autoCheckout($hostname, $basePath, $fields);
		}
	}

	public function viewRequested() {
		$args = func_get_args();
		$requestId = array_shift($args);
		$hostname = array_shift($args);
		$basePath = '/' . implode('/', $args);
		if (!isset($this->request->query['upper'])) {
			$basePath = strtolower($basePath);
		}
		$fields = $this->CollibraAPI->getApiFields($hostname, $basePath, true);
		if (empty($fields) && !isset($this->request->query['upper'])) {
			return $this->redirect($requestId.'/'.$hostname.'/'.implode('/', $args).'?upper=1');
		}
		if (empty($fields)) {
			//Check if non-existent API, or simply empty API
			$community = $this->CollibraAPI->findTypeByName('community', $hostname, ['full' => true]);
			if (empty($community->vocabularyReferences->vocabularyReference)) {
				return $this->redirect(['action' => 'host', 'hostname' => $hostname]);
			}
			$found = in_array($basePath, array_column($community->vocabularyReferences->vocabularyReference, 'name'));
			if (!$found) {
				$this->Flash->error('We couldn\'t find that API in our database.');
				return $this->redirect(['action' => 'host', 'hostname' => $hostname]);
			}
		}
		$containsFieldset = count(array_filter(array_column($fields, 'descendantFields')));
		$apiObject = $this->CollibraAPI->getApiObject($hostname, $basePath);
		$request = $this->CollibraAPI->getRequestDetails($requestId);
		$requestedAssetIds = [];
		foreach ($request->requestedDataAssets as $asset) {
			array_push($requestedAssetIds, $asset->reqDataId);
		}
		$this->set(compact('hostname', 'basePath', 'fields', 'apiObject', 'request', 'requestedAssetIds', 'containsFieldset'));
	}

	protected function _autoCheckout($hostname, $basePath, $fields) {
		$queue = $this->Session->read('queue');
		foreach ($fields as $field) {
			if (empty($field->businessTerm[0])) {
				continue;
			}
			$queue['businessTerms'][$field->businessTerm[0]->termId] = [
				'term' => $field->businessTerm[0]->term,
				'communityId' => $field->businessTerm[0]->termCommunityId,
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
