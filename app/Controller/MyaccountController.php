<?php

class MyaccountController extends AppController {
	public $components = ['Collibra'];

	function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->deny();
		$this->Auth->allow('logout');
	}

	private static function sortUsers($a, $b){
		return strcmp($a->firstName, $b->firstName);
	}

	private static function sortRequests($a, $b){
		return strcmp($a->createdOn, $b->createdOn);
	}

	private static function sortAttributes($a, $b){
		return strcmp($a->labelReference->signifier, $b->labelReference->signifier);
	}

	public function login() {
		$this->redirect($this->request->query['return']);
	}

	public function logout() {
		$this->Auth->logout();
		$this->redirect('/');
	}

	function sortArrayByArray(Array $array, Array $orderArray) {
		$ordered = [];
		foreach($orderArray as $key) {
			if(array_key_exists($key,$array)) {
				$ordered[$key] = $array[$key];
				unset($array[$key]);
			}
		}
		return $ordered + $array;
	}

	public function index() {
		$completedStatuses = ['Completed', 'Obsolete'];
		$expand = '';
		if(isset($this->request->query['expand'])){
			$expand = $this->request->query['expand'];
		}

		$netID = $this->Auth->user('username');
		$this->loadModel('BYUAPI');
		$byuUser = $this->BYUAPI->personalSummary($netID);

		$this->loadModel('CollibraAPI');
		$resp = $this->CollibraAPI->postJSON(
				'search',
				'{"query":"'.$netID.'", "filter": {"category": ["TE"], "type": {"asset": ["' . Configure::read('Collibra.type.dataSharingRequest') . '"] }}, "fields": ["' . Configure::read('Collibra.attribute.requesterNetId') . '"] }'
		);
		$requests = json_decode($resp);

		$arrRequests = [];
		foreach($requests->results as $r){
			if ($r->status == 'Deleted') {
				continue;
			}

			$request = $this->CollibraAPI->getRequestDetails($r->name->id);
			$request->roles = $this->CollibraAPI->getResponsibilities($request->vocabularyId);
			for ($i = 0; $i < sizeof($request->dsas); $i++) {
				$request->dsas[$i]->roles = $this->CollibraAPI->getResponsibilities($request->dsas[$i]->dsaId);
			}
			$resp = $this->CollibraAPI->get('term/'.$r->name->id.'/attachments');
			$resp = json_decode($resp);
			$request->attachments = $resp->attachment;

			$request->reqTermGlossaries = [];
			foreach ($request->requestedTerms as $term) {
				if (array_key_exists($term->reqTermVocabName, $request->reqTermGlossaries)) {
					array_push($request->reqTermGlossaries[$term->reqTermVocabName], $term);
				} else {
					$request->reqTermGlossaries[$term->reqTermVocabName] = [$term];
				}
			}

			if (!empty($request->additionallyIncludedTerms)) {
				$request->addTermGlossaries = [];
				foreach ($request->additionallyIncludedTerms as $term) {
					if (array_key_exists($term->addTermVocabName, $request->addTermGlossaries)) {
						array_push($request->addTermGlossaries[$term->addTermVocabName], $term);
					} else {
						$request->addTermGlossaries[$term->addTermVocabName] = [$term];
					}
				}
			}
			// add to request data array
			array_push($arrRequests, $request);
		}
		// sort results by date added
		usort($arrRequests, 'self::sortRequests');

		// Temporary fix for a mysterious bug in Collibra that sometimes
		// returns two copies of the most recently created DSR
		$numRequests = count($arrRequests);
		if ($numRequests > 1 && $arrRequests[$numRequests - 1]->id == $arrRequests[$numRequests - 2]->id) {
			array_pop($arrRequests);
		}
		$arrRequests = array_reverse($arrRequests);

		$sortedRequests = [
			'inProgress' => [],
			'completed' => [],
			'canceled' => []
		];

		$arrChangedAttrIds = [];
		$arrChangedAttrValues = [];
		foreach($arrRequests as $r){
			for ($i = 0; $i < sizeof($r->dsas); $i++) {
				list($r->dsas[$i]->attributes, $r->dsas[$i]->collaborators) = $this->CollibraAPI->getAttributes($r->dsas[$i]->dsaId);
				$resp = $this->CollibraAPI->get('term/'.$r->dsas[$i]->dsaId.'/attachments');
				$resp = json_decode($resp);
				$r->dsas[$i]->attachments = $resp->attachment;
			}

			$this->Collibra->cleanEdits($r, true);

			$pendingStatuses = ['In Progress', 'Request In Progress', 'Agreement Review', 'In Provisioning'];
			if (in_array($r->statusName, $pendingStatuses)) {
				array_push($sortedRequests['inProgress'], $r);
			} else if ($r->statusName == 'Completed' || $r->statusName == 'Obsolete') {
				array_push($sortedRequests['completed'], $r);
			} else if ($r->statusName == 'Canceled') {
				array_push($sortedRequests['canceled'], $r);
			}
		}

		$psName = '';
		$psRole = 'N/A';
		$psDepartment = 'N/A';
		$psEmail = '';
		$psNetID = $netID;
		if(isset($byuUser->names->preferred_name)){
			$psName = $byuUser->names->preferred_name;
		}
		if(isset($byuUser->employee_information->job_title)){
			$psRole = $byuUser->employee_information->job_title;
		}
		if(isset($byuUser->employee_information->department)){
			$psDepartment = $byuUser->employee_information->department;
		}
		if(isset($byuUser->contact_information->work_email_address)) {
			$psEmail = $byuUser->contact_information->work_email_address;
		} else if(isset($byuUser->contact_information->email_address)){
			$psEmail = $byuUser->contact_information->email_address;
		}
		$this->set(compact('expand', 'psName', 'psRole', 'psDepartment', 'psEmail', 'psNetID'));
		$this->set('requestStatuses', $sortedRequests);
	}
}
