<?php

require 'fpdf' . DIRECTORY_SEPARATOR . 'fpdf.php';

class RequestController extends AppController {
	public $helpers = array('Html', 'Form');
	public $uses = array('CollibraAPI', 'BYUAPI');

	function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->deny('index', 'submit');
	}

	private static function sortUsers($a, $b){
		return strcmp($a->firstName, $b->firstName);
	}

	private static function sortTerms($a, $b){
		return strcmp($a->signifier, $b->signifier);
	}

	private static function sortTermsByDomain($a, $b){
		return strcmp($a->domainname, $b->domainname);
	}

	// Called by a Collibra workflow upon approval of a DSA
	public function generatePDF() {
		$this->autoRender = false;

		if (!$this->request->is('post')) {
			return;
		}

		$dsaId = $this->request->data['id'];
		$dsa = json_decode($this->CollibraAPI->get('term/'.$dsaId));
		if (empty($dsa)) {
			return json_encode(['success' => '0', 'message' => 'Invalid DSA ID given.']);
		}
		$attachments = json_decode($this->CollibraAPI->get('term/'.$dsaId.'/attachments'));
		foreach ($attachments->attachment as $file) {
			if ($file->fileName == 'DSA_'.$dsaId.'.pdf') {
				return json_encode(['success' => '0', 'message' => 'A PDF has already been generated for the specified DSA.']);
			}
		}

		$dsa->roles = $this->CollibraAPI->getResponsibilities($dsa->vocabularyReference->resourceId);
		$dsa->parent = $this->CollibraAPI->getDataUsageParent($dsaId);
		$dsa->policies = $this->CollibraAPI->getAssetPolicies($dsaId);

		$requestedTerms = $this->CollibraAPI->getRequestedTerms($dsa->parent[0]->id);
		$dsa->termGlossaries = array();
		foreach ($requestedTerms as $term) {
			if (array_key_exists($term->domainname, $dsa->termGlossaries)) {
				array_push($dsa->termGlossaries[$term->domainname], $term);
			} else {
				$dsa->termGlossaries[$term->domainname] = array($term);
			}
		}

		// load additionally included terms
		////////////////////////////////////////////
		$resp = $this->CollibraAPI->getAdditionallyIncludedTerms($dsa->parent[0]->id);
		if (!empty($resp)) {
			$dsa->additionallyIncluded = new stdClass();
			$dsa->additionallyIncluded->termGlossaries = [];
			foreach ($resp as $term) {
				if (array_key_exists($term->domainname, $dsa->additionallyIncluded->termGlossaries)) {
					array_push($dsa->additionallyIncluded->termGlossaries[$term->domainname], $term);
				} else {
					$dsa->additionallyIncluded->termGlossaries[$term->domainname] = array($term);
				}
			}
		}

		// Generate PDF
		////////////////////////////////////////////
		$pdf = new FPDF('P','mm','Letter');
		$pdf->AddPage();
		$pdf->AddFont('Helvetica','','helvetica.php');
		$pdf->SetFont('Helvetica','',16);
		$pdf->SetTextColor(17,68,119);
		$pdf->Cell(0,20,$dsa->signifier,0,1,'C');

		$pdf->SetFont('','B',10);
		$pdf->SetTextColor(0);

		foreach ($dsa->termGlossaries as $glossaryName => $terms) {
			if ($terms[0]->commrid != $dsa->vocabularyReference->communityReference->resourceId) {
				continue;
			}

			$pdf->Write(5,"Requested Data:");
			$pdf->SetFont('','');

			$pdf->SetFont('','I');
			$pdf->Write(5,"\n{$glossaryName} - ");
			$pdf->SetFont('','');
			$termCount = 0;
			foreach ($terms as $term) {
				$pdf->Write(5,$term->termsignifier);
				$termCount++;
				if ($termCount < sizeof($terms)) {
					$pdf->Write(5,',  ');
				}
			}
			$pdf->Ln(10);
		}

		if (!empty($dsa->additionallyIncluded->termGlossaries)) {
			foreach ($dsa->additionallyIncluded->termGlossaries as $glossaryName => $terms) {
				if ($terms[0]->commrid != $dsa->vocabularyReference->communityReference->resourceId) {
					continue;
				}

				$pdf->SetFont('','B');
				$pdf->Write(5,"Additionally Included Data:");
				$pdf->SetFont('','');

				$pdf->SetFont('','I');
				$pdf->Write(5,"\n{$glossaryName} - ");
				$pdf->SetFont('','');
				$termCount = 0;
				foreach ($terms as $term) {
					$pdf->Write(5,$term->termsignifier);
					$termCount++;
					if ($termCount < sizeof($terms)) {
						$pdf->Write(5,',  ');
					}
				}
				$pdf->Ln(10);
			}
		}

		foreach ($dsa->attributeReferences->attributeReference as $attr) {
			if (isset($attr->date)) {
				$effectiveDate = date('n/j/Y', $attr->date/1000);
				break;
			}
		}
		$pdf->Ln(8);
		$pdf->SetFont('','B',12);
		$pdf->Cell(0,4,"Approved ".date('n/j/Y')."  -  Effective Start Date: {$effectiveDate}",0,1);
		if (isset($dsa->roles['Steward'][0])) {
			$pdf->Cell(0,4,"Steward - {$dsa->roles['Steward'][0]->firstName} {$dsa->roles['Steward'][0]->lastName}");
		} else if (isset($dsa->roles['Custodian'][0])) {
			$pdf->Cell(0,4,"Custodian - {$dsa->roles['Custodian'][0]->firstName} {$dsa->roles['Custodian'][0]->lastName}");
		} else if (isset($dsa->roles['Trustee'][0])) {
			$pdf->Cell(0,4,"Trustee - {$dsa->roles['Trustee'][0]->firstName} {$dsa->roles['Trustee'][0]->lastName}");
		}
		$pdf->Ln(8);

		$arrOrderedFormFields = [
			"Application Name",
			"Description of Intended Use",
			"Access Rights",
			"Access Method",
			"Impact on System",
			"Application Identity",
			"Additional Information Requested"
		];
		foreach ($arrOrderedFormFields as $field) {
			foreach ($dsa->attributeReferences->attributeReference as $attr) {
				if ($attr->labelReference->signifier == $field && !empty($attr->value)) {
					$pdf->SetFont('','B',12);
					$pdf->Cell(0,10,$field,'B',1);
					$pdf->Ln(2);
					$pdf->SetFont('','',9);

					$attrText = $attr->value;
					$attrText = preg_replace('/<br[^\/,>]*\/?>/',"\n",$attrText);
					$attrText = preg_replace(['/<li[^>]*>/','/<\/li>/'],[" ".chr(127)." ",""],$attrText);
					$attrText = preg_replace(['/<ul[^>]*>/','/<\/ul>/'],["\n",""],$attrText);
					$attrText = preg_replace('/<[^><]*>/','',$attrText);
					$attrText = str_replace(chr(194),'',$attrText);
					$attrText = preg_replace('/^\s/','',$attrText);

					$pdf->MultiCell(0,4,$attrText,0,'J');
					$pdf->Ln(4);
					break;
				}
			}
		}

		$arrPersonInfo = [
			'Requester Name' => '',
			'Requester Role' => '',
			'Requesting Organization' => '',
			'Requester Email' => '',
			'Requester Phone' => '',
			'Sponsor Name' => '',
			'Sponsor Role' => '',
			'Sponsor Email' => '',
			'Sponsor Phone' => ''
		];
		foreach ($dsa->attributeReferences->attributeReference as $attr) {
			if (array_key_exists($attr->labelReference->signifier, $arrPersonInfo)) {
				$arrPersonInfo[$attr->labelReference->signifier] = html_entity_decode($attr->value);
			}
		}

		$pdf->SetFont('','B',12);
		$w = $pdf->GetPageWidth();
		$pdf->Cell(($w / 4),8,"Requester",'B');
		$pdf->Cell(($w / 4),8);
		$pdf->Cell(($w / 4),8,"Sponsor",'B');
		$pdf->Cell(($w / 4),8,'',0,1);
		$pdf->Ln(2);

		$pdf->SetFont('','',9);
		$pdf->Cell(($w / 2),4,$arrPersonInfo['Requester Name']);
		$pdf->Cell(($w / 2),4,$arrPersonInfo['Sponsor Name'],0,1);
		$pdf->Cell(($w / 2),4,$arrPersonInfo['Requester Role'].' | '.$arrPersonInfo['Requesting Organization']);
		$pdf->Cell(($w / 2),4,$arrPersonInfo['Sponsor Role'],0,1);
		$pdf->Cell(($w / 2),4,$arrPersonInfo['Requester Email']);
		$pdf->Cell(($w / 2),4,$arrPersonInfo['Sponsor Email'],0,1);
		$pdf->Cell(($w / 2),4,$arrPersonInfo['Requester Phone']);
		$pdf->Cell(($w / 2),4,$arrPersonInfo['Sponsor Phone'],0,1);

		if (!empty($dsa->policies)) {
			$pdf->AddPage();
			$pdf->SetFont('','B',12);
			$pdf->Cell(0,10,'Data Usage Policies','B',1);

			foreach($dsa->policies as $policy) {
				$pdf->SetFont('','B',9);
				$pdf->Cell(0,6,$policy->policyName,0,1);
				$pdf->Ln(1);

				$policyText = $policy->policyDescription;
				$policyText = preg_replace('/<br[^\/,>]*\/?>/',"\n",$policyText);
				$policyText = preg_replace(['/<li[^>]*>/','/<\/li>/'],[" ".chr(127)." ",""],$policyText);
				$policyText = preg_replace(['/<ul[^>]*>/','/<\/ul>/'],["\n",""],$policyText);
				$policyText = preg_replace('/<[^><]*>/','',$policyText);
				$policyText = str_replace(chr(194),'',$policyText);
				$policyText = preg_replace('/^\s/','',$policyText);

				$pdf->SetFont('','',9);
				$pdf->MultiCell(0,6,$policyText);
				$pdf->Ln(4);
			}
		}

		$pdfString = $pdf->Output('S');
		$fileId = $this->CollibraAPI->uploadFile($pdfString, "DSA_".$dsaId);

		$postData = [
			'file' => $fileId,
			'fileName' => 'DSA_' . $dsaId . '.pdf'
		];
		$postString = http_build_query($postData);
		$resp = $this->CollibraAPI->post('term/'.$dsaId.'/attachment', $postString);

		if ($resp->code != '201') {
			return json_encode(['success' => '0', 'message' => $resp->body]);
		}
		return json_encode(['success' => '1']);
	}

	public function addToQueue() {
		$this->autoRender = false;
		if($this->request->is('post')){
			if($this->request->data['emptyApi'] == 'true') {
				$newTermsAdded = 1;
				$apiPath = $this->request->data['t'][0];
				$apiHost = $this->request->data['apiHost'];

				$arrQueue = $this->Session->read('queue');
				foreach ($arrQueue['emptyApis'] as $path => $host) {
					if ($path == $apiPath) {
						$newTermsAdded = 0;
					}
				}

				if ($newTermsAdded) {
					$arrQueue['emptyApis'][$apiPath] = ['apiHost' => $apiHost];
				}

				$this->Session->write('queue', $arrQueue);
				echo $newTermsAdded;
			} else {
				$newTermsAdded = 0;
				$arrTerms = empty($this->request->data['t']) ? null : $this->request->data['t'];
				$arrFields = empty($this->request->data['f']) ? null : $this->request->data['f'];
				$arrColumns = empty($this->request->data['c']) ? null : $this->request->data['c'];
				$apiHost = empty($this->request->data['apiHost']) ? null : $this->request->data['apiHost'];
				$apiPath = empty($this->request->data['apiPath']) ? null : $this->request->data['apiPath'];
				$schemaName = empty($this->request->data['schemaName']) ? null : $this->request->data['schemaName'];
				$tableName = empty($this->request->data['tableName']) ? null : $this->request->data['tableName'];

				$arrQueue = $this->Session->read('queue');

				if (isset($arrTerms)) {
					foreach ($arrTerms as $term) {
						if (empty($arrQueue['businessTerms'][$term['id']]) && empty($arrQueue['concepts'][$term['id']])) {
							$requestable = true;
							$concept = false;
							$termResp = $this->CollibraAPI->get('term/'.$term['id']);
							$termResp = json_decode($termResp);

							// verify that the term is requestable
							if(!Configure::read('allowUnrequestableTerms')){
								foreach($termResp->attributeReferences->attributeReference as $attr){
									if($attr->labelReference->resourceId == Configure::read('Collibra.attribute.concept')){
										$concept = $attr->value == 'true';
									}
								}
							}

							// verify that the term is approved
							if(!Configure::read('allowUnapprovedTerms')){
								$requestable = $termResp->statusReference->signifier == 'Accepted';
							}

							if($requestable && !$concept){
								$newTermsAdded++;
								$arrQueue['businessTerms'][$term['id']] = ['term' => $term['title'], 'communityId' => $term['vocabId'], 'apiHost' => $apiHost, 'apiPath' => $apiPath, 'schemaName' => $schemaName, 'tableName' => $tableName];
							} else if ($requestable && $concept) {
								$newTermsAdded++;
								$arrQueue['concepts'][$term['id']] = ['term' => $term['title'], 'communityId' => $term['vocabId'], 'apiHost' => $apiHost, 'apiPath' => $apiPath, 'schemaName' => $schemaName, 'tableName' => $tableName];
							}
						}
					}
				}

				if (isset($arrFields)) {
					foreach ($arrFields as $field) {
						if (empty($arrQueue['apiFields'][$field])) {
							$newTermsAdded++;
							$arrQueue['apiFields'][$field] = ['name' => end((explode(".", $field))), 'apiHost' => $apiHost, 'apiPath' => $apiPath];
						}
					}
				}

				if (isset($arrColumns)) {
					foreach ($arrColumns as $column) {
						if (empty($arrQueue['dbColumns'][$column])) {
							$newTermsAdded++;
							$arrQueue['dbColumns'][$column] = ['name' => end((explode(" > ", $column))), 'schemaName' => $schemaName, 'tableName' => $tableName];
						}
					}
				}

				$this->Session->write('queue', $arrQueue);
				$this->updateDraftCart();
				echo $newTermsAdded;
			}
		}
	}

	public function removeFromQueue() {
		$this->autoRender = false;
		if ($this->request->is('post')) {
			$termID = $this->request->data['id'];
			$arrQueue = $this->Session->read('queue');
			if(array_key_exists($termID, $arrQueue['businessTerms'])) {
				unset($arrQueue['businessTerms'][$termID]);
			} else if (array_key_exists($termID, $arrQueue['concepts'])) {
				unset($arrQueue['concepts'][$termID]);
			} else if (array_key_exists($termID, $arrQueue['apiFields'])) {
				unset($arrQueue['apiFields'][$termID]);
			} else if (array_key_exists($termID, $arrQueue['dbColumns'])) {
				unset($arrQueue['dbColumns'][$termID]);
			} else if (array_key_exists($termID, $arrQueue['emptyApis'])) {
				unset($arrQueue['emptyApis'][$termID]);
			}
			$this->Session->write('queue', $arrQueue);
			$this->updateDraftCart();
		}
	}

	public function clearQueue() {
		$this->autoRender = false;
		$arrQueue = [];
		$arrQueue['businessTerms'] = [];
		$arrQueue['concepts'] = [];
		$arrQueue['apiFields'] = [];
		$arrQueue['dbColumns'] = [];
		$arrQueue['emptyApis'] = [];
		$this->Session->write('queue', $arrQueue);
		$this->updateDraftCart();
	}

	public function getQueueSize() {
		$this->autoRender = false;

		$arrQueue = $this->Session->read('queue');
		echo  count($arrQueue['businessTerms']) +
			  count($arrQueue['concepts']) +
			  count($arrQueue['apiFields']) +
			  count($arrQueue['dbColumns']) +
			  count($arrQueue['emptyApis']);
	}

	public function cartDropdown() {
		$this->autoRender = false;
		$responseHTML = '';
		$this->loadModel('CollibraAPI');

		$arrQueue = $this->Session->read('queue');
		$responseHTML = '<h3>Requested Items: '.(count($arrQueue['businessTerms']) + count($arrQueue['concepts']) + count($arrQueue['apiFields']) + count($arrQueue['dbColumns']) + count($arrQueue['emptyApis'])).'</h3>'.
			'<a class="close" href="javascript: hideRequestQueue()">X</a>'.
			'<div class="arrow"></div>'.
			'<a class="clearQueue" href="javascript: clearRequestQueue()">Empty cart</a>';

		if(
			!empty($arrQueue['businessTerms']) ||
			!empty($arrQueue['concepts']) ||
			!empty($arrQueue['apiFields']) ||
			!empty($arrQueue['dbColumns']) ||
			!empty($arrQueue['emptyApis'])
		){
			$responseHTML .= '<a class="btn-orange" href="/request">View Request</a>';
		}
		echo $responseHTML;
	}

	public function success() {
	}

	public function addCollaborator($dsrId, $personId) {
		$this->autoRender = false;

		if (empty($dsrId) || empty($personId)) {
			return json_encode(['success' => 0, 'message' => 'Bad request']);
		}

		$person = $this->BYUAPI->personalSummary($personId);
		if (!isset($person)) {
			return json_encode(['success' => 0, 'message' => 'Person\'s information could not be loaded']);
		}

		$resp = $this->CollibraAPI->get('term/'.$dsrId);
		$request = json_decode($resp);

		foreach($request->attributeReferences->attributeReference as $attr) {
			if ($attr->labelReference->signifier == 'Requester Net Id' && $attr->value == $person->identifiers->net_id) {
				return json_encode(['success' => 0, 'message' => 'This person is already a collaborator on this request.']);
			}
		}

		if ($request->conceptType->resourceId != Configure::read('Collibra.type.isaRequest')) {
			$parent = $this->CollibraAPI->getDataUsageParent($dsrId);
			return $this->addCollaborator($parent[0]->id, $personId);
		}

		$postData['value'] = $person->identifiers->net_id;
		$postData['representation'] = $dsrId;
		$postData['label'] = Configure::read('Collibra.attribute.isaRequestNetId');
		$postString = http_build_query($postData);
		$formResp = $this->CollibraAPI->post('term/'.$dsrId.'/attributes', $postString);
		$formResp = json_decode($formResp);
		if (!isset($formResp)) {
			return json_encode(['success' => 0, 'message' => 'We had a problem getting to Collibra']);
		}

		// Add to DSAs as well
		$request->dataUsages = $this->CollibraAPI->getDataUsages($dsrId);
		foreach($request->dataUsages as $du) {
			$postData['representation'] = $du->id;
			$postString = http_build_query($postData);
			$formResp = $this->CollibraAPI->post('term/'.$du->id.'/attributes', $postString);
			$formResp = json_decode($formResp);
			if (!isset($formResp)) {
				return json_encode(['success' => 0, 'message' => 'We had a problem getting to Collibra']);
			}
		}

		return json_encode(['success' => 1, 'person' => $person]);
	}

	public function removeCollaborator($dsrId, $netId) {
		$this->autoRender = false;

		if (empty($dsrId) || empty($netId)) {
			return json_encode(['success' => 0, 'message' => 'DSR ID or Net ID missing.']);
		}

		$resp = $this->CollibraAPI->get('term/'.$dsrId);
		$request = json_decode($resp);

		if ($request->conceptType->resourceId != Configure::read('Collibra.type.isaRequest')) {
			$parent = $this->CollibraAPI->getDataUsageParent($dsrId);
			return $this->removeCollaborator($parent[0]->id, $netId);
		}

		$request->dataUsages = $this->CollibraAPI->getDataUsages($dsrId);

		foreach ($request->dataUsages as $du) {
			$resp = $this->CollibraAPI->get('term/'.$du->id);
			$dataUsage = json_decode($resp);
			foreach ($dataUsage->attributeReferences->attributeReference as $attr) {
				if ($attr->labelReference->signifier == 'Requester Net Id' && $attr->value == $netId) {
					$this->CollibraAPI->delete('attribute/'.$attr->resourceId);
					break;
				}
			}
		}

		foreach ($request->attributeReferences->attributeReference as $attr) {
			if ($attr->labelReference->signifier == 'Requester Net Id' && $attr->value == $netId) {
				$this->CollibraAPI->delete('attribute/'.$attr->resourceId);
				break;
			}
		}

		return json_encode(['success' => 1, 'message' => "This person is no longer a collaborator on \"{$request->signifier}\"."]);
	}

	public function saveDraft() {
		$this->autoRender = false;
		$netId = $this->Auth->user('username');

		//First check if there already is a draft for this user; if so, delete it
		$this->loadModel('CollibraAPI');
		$draft = $this->CollibraAPI->checkForDSRDraft($netId);
		if (!empty($draft)) {
			$this->CollibraAPI->delete('term/'.$draft[0]->id);
		}

		$resp = $this->CollibraAPI->post('term', [
			'vocabulary' => Configure::read('Collibra.vocabulary.dataSharingRequests'),
			'signifier' => "DRAFT-{$netId}",
			'conceptType' => Configure::read('Collibra.type.dataSharingRequestDraft')
		]);
		$resp = json_decode($resp);
		$draftId = $resp->resourceId;

		$arrQueue = $this->Session->read('queue');
		$this->request->data['descriptionOfInformation'] = json_encode($arrQueue);

		foreach ($this->request->data as $label => $value) {
			if (empty($value)) continue;
			$postData['label'] = Configure::read("Collibra.formFields.{$label}");
			$postData['value'] = $value;
			$postString = http_build_query($postData);
			$resp = $this->CollibraAPI->post('term/'.$draftId.'/attributes', $postString);
			if ($resp->code != '201') {
				$error = true;
			}
		}

		if (isset($error)) {
			return json_encode(['success' => 0, 'message' => 'Could not save draft']);
		}
		return json_encode(['success' => 1, 'message' => 'Saved successfully']);
	}

	public function updateDraftCart() {
		$this->autoRender = false;
		if (!$netId = $this->Auth->user('username')) {
			return;
		}

		$this->loadModel('CollibraAPI');
		$draftId = $this->CollibraAPI->checkForDSRDraft($netId);
		if (empty($draftId)) {
			return;
		}
		$draft = $this->CollibraAPI->get('term/'.$draftId[0]->id);
		$draft = json_decode($draft);

		foreach ($draft->attributeReferences->attributeReference as $attr) {
			if ($attr->labelReference->signifier == 'Additional Information Requested') {
				$attrId = $attr->resourceId;
			}
		}

		$arrQueue = $this->Session->read('queue');
		$postData['value'] = json_encode($arrQueue);
		$postString = http_build_query($postData);
		$resp = $this->CollibraAPI->post('attribute/'.$attrId, $postString);
	}

	public function deleteDraft() {
		$this->autoRender = false;
		if (!$netId = $this->Auth->user('username')) {
			return json_encode(['success' => '0', 'message' => 'User not logged in']);
		}

		$this->loadModel('CollibraAPI');
		$draftId = $this->CollibraAPI->checkForDSRDraft($netId);
		if (empty($draftId)) {
			return json_encode(['success' => '0', 'message' => 'User doesn\'t have a draft saved']);
		}

		$this->CollibraAPI->delete('term/'.$draftId[0]->id);
		return json_encode(['success' => '1']);
	}

	public function editTermsSubmit() {
		$this->autoRender = false;
		if (!$this->request->is('post')) {
			return;
		}
		if (!isset($this->request->data['dsrId'])) {
			return json_encode(['success' => 0]);
		}

		if ($this->request->data['action'] == 'add') {
			$success = true;
			$arrQueue = $this->Session->read('queue');

			$resp = $this->CollibraAPI->get('term/'.$this->request->data['dsrId']);
			$request = json_decode($resp);
			$request->terms = $this->CollibraAPI->getRequestedTerms($request->resourceId);
			$request->additionallyIncluded = $this->CollibraAPI->getAdditionallyIncludedTerms($request->resourceId);
			$request->isNecessary = $this->CollibraAPI->getNecessaryAPIs($request->resourceId);

			$addedApis = [];
			$addedTables = [];
			$additionString = "<br/><br/>Addition, ".date('Y-m-d').":";

			$postData['source'] = $this->request->data['dsrId'];
			$postData['binaryFactType'] = Configure::read('Collibra.relationship.isaRequestToTerm');
			if (isset($this->request->data['arrBusinessTerms'])) {
				foreach ($this->request->data['arrBusinessTerms'] as $termid) {
					$postData['target'] = $termid;
					$postString = http_build_query($postData);
					$resp = $this->CollibraAPI->post('relation', $postString);

					foreach ($request->additionallyIncluded as $inclTerm) {
						if ($inclTerm->termid == $termid) {
							// remove term from DSR's "Additionally Included" list if it's now requested
							$this->CollibraAPI->delete('relation/'.$inclTerm->relationid);
							break;
						}
					}

					if ($resp->code != '201') {
						$success = false;
						continue;
					}

					foreach ($arrQueue['businessTerms'] as $queueId => $queueTerm) {
						if ($queueId == $termid) {
							if (isset($queueTerm['apiHost']) && isset($queueTerm['apipath'])) {
								$addedApis[$queueTerm['apiHost']][$queueTerm['apiPath']] = [];
								$additionString .= "<br/>{$queueTerm['term']} (Business Term), from API: {$queueTerm['apiPath']}";
							} else if (isset($queueTerm['tableName'])) {
								$addedTables[$queueTerm['tableName']] = [];
								$additionString .= "<br/>{$queueTerm['term']} (Business Term), from table: {$queueTerm['tableName']}";
							}
							unset($arrQueue['businessTerms'][$queueId]);
							break;
						}
					}
				}
			}

			if (isset($this->request->data['arrConcepts']) || isset($this->request->data['arrApiFields']) || isset($this->request->data['arrDbColumns']) || isset($this->request->data['arrApis'])) {
				if (isset($this->request->data['arrConcepts'])) {
					foreach ($this->request->data['arrConcepts'] as $concept) {
						$addedApis[$concept['apiHost']][$concept['apiPath']] = [];
						$additionString .= "<br/>{$concept['term']} (Concept), from API: ".$concept['apiPath'];
						if (array_key_exists($concept['id'], $arrQueue['concepts'])) {
							unset($arrQueue['concepts'][$concept['id']]);
						}
					}
				}
				if (isset($this->request->data['arrApiFields'])) {
					foreach ($this->request->data['arrApiFields'] as $field) {
						$addedApis[$field['apiHost']][$field['apiPath']] = [];
						$additionString .= "<br/>".$field['field'].", from API: ".$field['apiPath'];
						if (array_key_exists($field['field'], $arrQueue['apiFields'])) {
							unset($arrQueue['apiFields'][$field['field']]);
						}
					}
				}
				if (isset($this->request->data['arrDbColumns'])) {
					foreach ($this->request->data['arrDbColumns'] as $column) {
						$addedTables[$column['tableName']] = [];
						$additionString .= "<br/>{$column['name']}";
						if (array_key_exists($column['name'], $arrQueue['dbColumns'])) {
							unset($arrQueue['dbColumns'][$column['name']]);
						}
					}
				}
				if (isset($this->request->data['arrApis'])) {
					foreach ($this->request->data['arrApis'] as $api) {
						$addedApis[$arrQueue['emptyApis'][$api]['apiHost']][$api] = [];
						$additionString .= "<br/>".$api." [No specified output fields]";
						if (array_key_exists($api, $arrQueue['emptyApis'])) {
							unset($arrQueue['emptyApis'][$api]);
						}
					}
				}
			}

			foreach ($request->isNecessary as $alreadyListed) {
				unset($addedApis[$alreadyListed->communityname][substr($alreadyListed->apiname, 1)]);
				if (empty($addedApis[$alreadyListed->communityname])) {
					unset($addedApis[$alreadyListed->communityname]);
				}
			}

			$newBusinessTerms = [];
			$postData = [];
			$postData['source'] = $this->request->data['dsrId'];
			$postData['binaryFactType'] = Configure::read('Collibra.relationship.DSRtoNecessaryAPI');
			foreach ($addedApis as $apiHost => $apiPaths) {
				foreach ($apiPaths as $apiPath => $_) {
					$apiObject = $this->CollibraAPI->getApiObject($apiHost, $apiPath);
					$postData['target'] = $apiObject->id;
					$postString = http_build_query($postData);
					$resp = $this->CollibraAPI->post('relation', $postString);

					$apiTerms = $this->CollibraAPI->getApiTerms($apiHost, $apiPath);
					foreach ($apiTerms as $term) {
						if (!empty($term->assetType) && strtolower($term->assetType) == 'fieldset') {
							continue;
						}
						if (empty($term->businessTerm[0]->termId)) {
							$requestedField = false;
							if (isset($this->request->data['arrApiFields'])) {
								foreach ($this->request->data['arrApiFields'] as $field) {
									if ($term->name == $field['field']) {
										$requestedField = true;
										break;
									}
								}
							}
							if ($requestedField) {
								$addedApis[$apiHost][$apiPath]['unmapped']['requested'][] = $term->name;
							} else {
								$addedApis[$apiHost][$apiPath]['unmapped']['unrequested'][] = $term->name;
							}
						} else {
							array_push($newBusinessTerms, $term->businessTerm[0]->termId);
							$requestedConcept = false;
							if (isset($this->request->data['arrConcepts'])) {
								foreach ($this->request->data['arrConcepts'] as $concept) {
									if ($term->businessTerm[0]->termId == $concept['id']) {
										$requestedConcept = true;
										break;
									}
								}
							}
							if ($requestedConcept) {
								$addedApis[$apiHost][$apiPath]['requestedConcept'][] = '('.$term->businessTerm[0]->termCommunityName.') '.$term->businessTerm[0]->term;
							} else {
								$requestedTerm = false;
								if (isset($this->request->data['arrBusinessTerms'])) {
									foreach ($this->request->data['arrBusinessTerms'] as $termid) {
										if ($term->businessTerm[0]->termId == $termid) {
											$requestedTerm = true;
											break;
										}
									}
								}
								if ($requestedTerm) {
									$addedApis[$apiHost][$apiPath]['requestedBusinessTerm'][] = '('.$term->businessTerm[0]->termCommunityName.') '.$term->businessTerm[0]->term;
								} else {
									$addedApis[$apiHost][$apiPath]['unrequested'][] = '('.$term->businessTerm[0]->termCommunityName.') '.$term->businessTerm[0]->term;
								}
							}
						}
					}
				}
			}
			$postData['binaryFactType'] = Configure::read('Collibra.relationship.DSRtoNecessaryTable');
			foreach ($addedTables as $tableName => $_) {
				$table = $this->CollibraAPI->getTableObject($tableName);
				$postData['target'] = $table->id;
				$postString = http_build_query($postData);
				$resp = $this->CollibraAPI->post('relation', $postString);

				$columns = $this->CollibraAPI->getTableColumns($tableName);
				foreach ($columns as $column) {
					if (empty($column->businessTerm[0]->termId)) {
						$requestedColumn = false;
						if (isset($this->request->data['arrDbColumns'])) {
							foreach ($this->request->data['arrDbColumns'] as $requested) {
								if ($column->columnName == $requested['name']) {
									$requestedColumn = true;
									break;
								}
							}
						}
						if ($requestedColumn) {
							$addedTables[$tableName]['unmapped']['requested'][] = $column->columnName;
						} else {
							$addedTables[$tableName]['unmapped']['unrequested'][] = $column->columnName;
						}
					} else {
						array_push($newBusinessTerms, $column->businessTerm[0]->termId);
						$requestedConcept = false;
						if (isset($this->request->data['arrConcepts'])) {
							foreach ($this->request->data['arrConcepts'] as $concept) {
								if ($column->businessTerm[0]->termId == $concept['id']) {
									$requestedConcept = true;
									break;
								}
							}
						}
						if ($requestedConcept) {
							$addedTables[$tableName]['requestedConcept'][] = '('.$column->businessTerm[0]->termCommunityName.') '.$column->businessTerm[0]->term;
						} else {
							$requestedTerm = false;
							if (isset($this->request->data['arrBusinessTerms'])) {
								foreach ($this->request->data['arrBusinessTerms'] as $termid) {
									if ($column->businessTerm[0]->termId == $termid) {
										$requestedTerm = true;
										break;
									}
								}
							}
							if ($requestedTerm) {
								$addedTables[$tableName]['requestedBusinessTerm'][] = '('.$column->businessTerm[0]->termCommunityName.') '.$column->businessTerm[0]->term;
							} else {
								$addedTables[$tableName]['unrequested'][] = '('.$column->businessTerm[0]->termCommunityName.') '.$column->businessTerm[0]->term;
							}
						}
					}
				}
			}

			if (!empty($addedApis)) {
				$additionString .= "<br/><br/><b>Newly Requested APIs:</b><br/>";
				foreach ($addedApis as $apiHost => $apiPaths) {
					foreach ($apiPaths as $apiPath => $term) {
						$additionString .= ". . <u><b>{$apiHost}/{$apiPath}</u></b><br/>";
						if (!empty($term['requestedBusinessTerm'])) {
							$term['requestedBusinessTerm'] = array_unique($term['requestedBusinessTerm']);
							sort($term['requestedBusinessTerm']);
							$additionString .= "<br/>. . . . <b>Requested business terms:</b><br/>. . . . . . " . implode("<br/>. . . . . . ", $term['requestedBusinessTerm']) . "<br/>";
						}
						if (!empty($term['requestedConcept'])) {
							$term['requestedConcept'] = array_unique($term['requestedConcept']);
							sort($term['requestedConcept']);
							$additionString .= ". . . . Requested conceptual terms:<br/>. . . . . . " . implode("<br/>. . . . . . ", $term['requestedConcept']) . "<br/>";
						}
						if (!empty($term['unrequested'])) {
							$term['unrequested'] = array_unique($term['unrequested']);
							sort($term['unrequested']);
							$additionString .= "<br/>. . . . <b>Unrequested terms:</b><br/>. . . . . . " . implode("<br/>. . . . . . ", $term['unrequested']) . "<br/>";
						}
						if (!empty($term['unmapped'])) {
							$additionString .= "<br/>. . . . <b>*Fields with no Business Terms:</b><br/>";
							if (!empty($term['unmapped']['requested'])) {
								$additionString .= ". . . . . . Requested:<br/>. . . . . . . . " . implode("<br/>. . . . . . . . ", $term['unmapped']['requested']) . "<br/>";
							}
							if (!empty($term['unmapped']['unrequested'])) {
								$additionString .= ". . . . . . Unrequested:<br/>. . . . . . . . " . implode("<br/>. . . . . . . . ", $term['unmapped']['unrequested']) . "<br/>";
							}
						}
						$additionString .= "<br/>";
					}
				}
			}

			if (!empty($addedTables)) {
				$additionString .= "<br/><br/><b>Newly Requested Database Tables:</b><br/>";
				foreach ($addedTables as $tableName => $table) {
					$additionString .= ". . <u><b>{$tableName}</u></b><br/>";
					if (!empty($table['requestedBusinessTerm'])) {
						$table['requestedBusinessTerm'] = array_unique($table['requestedBusinessTerm']);
						sort($table['requestedBusinessTerm']);
						$additionString .= "<br/>. . . . <b>Requested business terms:</b><br/>. . . . . . " . implode("<br/>. . . . . . ", $table['requestedBusinessTerm']) . "<br/>";
					}
					if (!empty($table['requestedConcept'])) {
						$table['requestedConcept'] = array_unique($table['requestedConcept']);
						sort($table['requestedConcept']);
						$additionString .= ". . . . Requested conceptual terms:<br/>. . . . . . " . implode("<br/>. . . . . . ", $table['requestedConcept']) . "<br/>";
					}
					if (!empty($table['unrequested'])) {
						$table['unrequested'] = array_unique($table['unrequested']);
						sort($table['unrequested']);
						$additionString .= "<br/>. . . . <b>Unrequested terms:</b><br/>. . . . . . " . implode("<br/>. . . . . . ", $table['unrequested']) . "<br/>";
					}
					if (!empty($table['unmapped'])) {
						$additionString .= "<br/>. . . . <b>*Columns with no Business Terms:</b><br/>";
						if (!empty($table['unmapped']['requested'])) {
							$additionString .= ". . . . . . Requested:<br/>. . . . . . . . " . implode("<br/>. . . . . . . . ", $table['unmapped']['requested']) . "<br/>";
						}
						if (!empty($table['unmapped']['unrequested'])) {
							$additionString .= ". . . . . . Unrequested:<br/>. . . . . . . . " . implode("<br/>. . . . . . . . ", $table['unmapped']['unrequested']) . "<br/>";
						}
					}
					$additionString .= "<br/>";
				}
			}

			foreach ($request->attributeReferences->attributeReference as $attr) {
				if ($attr->labelReference->signifier == 'Additional Information Requested') {
					$postData = [];
					$postData['value'] = $attr->value . $additionString;
					$postData['rid'] = $attr->resourceId;
					$postString = http_build_query($postData);
					$postString = preg_replace('/%0D%0A/', '<br/>', $postString);
					$formResp = $this->CollibraAPI->post('attribute/'.$attr->resourceId, $postString);
					$formResp = json_decode($formResp);

					if (!isset($formResp)) {
						$success = false;
					}
					break;
				}
			}

			$postData = $this->request->data;
			$newBusinessTerms = array_filter($newBusinessTerms, function($termid) use($request, $postData) {
				foreach ($request->terms as $alreadyRequested) {
					if ($alreadyRequested->termrid == $termid) {
						return false;
					}
				}
				foreach ($request->additionallyIncluded as $alreadyIncluded) {
					if ($alreadyIncluded->termid == $termid) {
						return false;
					}
				}
				foreach ($postData['arrBusinessTerms'] as $newAdditionId) {
					if ($newAdditionId == $termid) {
						return false;
					}
				}
				return true;
			});

			$postData = [];
			$postData['source'] = $this->request->data['dsrId'];
			$postData['binaryFactType'] = Configure::read('Collibra.relationship.DSRtoAdditionallyIncludedAsset');
			foreach ($newBusinessTerms as $termid) {
				$postData['target'] = $termid;
				$postString = http_build_query($postData);
				$resp = $this->CollibraAPI->post('relation', $postString);
			}

			$this->Session->write('queue', $arrQueue);
			return $success ? json_encode(['success' => 1]) : json_encode(['success' => 0]);
		}
		else if ($this->request->data['action'] == 'remove') {
			$success = true;
			foreach ($this->request->data['arrIds'] as $relationrid) {
				$resp = $this->CollibraAPI->delete('relation/'.$relationrid);
				if ($resp->code != '200') {
					$success = false;
				}
			}
			return $success ? json_encode(['success' => 1]) : json_encode(['success' => 0]);
		}
		return json_encode(['success' => 0]);
	}

	public function editTerms($dsrId) {
		if (empty($dsrId)) {
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}

		$resp = $this->CollibraAPI->get('term/'.$dsrId);
		$request = json_decode($resp);

		$netID = $this->Auth->user('username');

		$guest = true;
		foreach($request->attributeReferences->attributeReference as $attr) {
			if ($attr->labelReference->signifier == 'Requester Net Id') {
				if ($attr->value == $netID) {
					$guest = false;
					break;
				}
			}
		}

		$pendingStatuses = ['In Progress', 'Request In Progress', 'Agreement Review'];
		if (!in_array($request->statusReference->signifier, $pendingStatuses)) {
			$this->Flash->error('You cannot edit a Request that isn\'t currently in progress.');
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}

		// Check whether $request is a DSR or DSA
		$isaRequest = $request->conceptType->resourceId == Configure::read('Collibra.type.isaRequest');
		if (!$isaRequest) {
			$this->Flash->error('You cannot edit the terms on an individual DSA.');
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}

		$request->dataUsages = $this->CollibraAPI->getDataUsages($dsrId);
		if (!empty($request->dataUsages)) {
			$this->Flash->error('You cannot edit a DSR with any associated DSAs.');
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}

		$request->terms = $this->CollibraAPI->getRequestedTerms($request->resourceId);

		$arrQueue = $this->Session->read('queue');
		$this->set(compact('request', 'guest', 'arrQueue'));
		$this->set('submitErr', isset($this->request->query['err']));
	}

	public function editSubmit($dsrId) {
		$this->autoRender = false;

		if (!$this->request->is('post')){
			header('location: /search');
			exit;
		}

		$resp = $this->CollibraAPI->get('term/'.$dsrId);
		$dsr = json_decode($resp);

		$err = false;

		foreach ($this->request->data as $id => $val) {
			if ($id == 'requestSubmit') {
				continue;
			}
			$matchFound = false;
			foreach ($dsr->attributeReferences->attributeReference as $original) {
				if ($id == $original->labelReference->resourceId) {
					$matchFound = true;
					if ($val != $original->value) {
						//Update values in Collibra database
						$postData['value'] = $val;
						$postData['rid'] = $original->resourceId;
						$postString = http_build_query($postData);
						$postString = preg_replace('/%0D%0A/', '<br/>', $postString);
						$formResp = $this->CollibraAPI->post('attribute/'.$original->resourceId, $postString);
						$formResp = json_decode($formResp);

						if (!isset($formResp)) {
							$err = true;
						}
					}
					break;
				}
			}
			if (!$matchFound && !empty($val)) {		//i.e., if the value has been left blank/empty until now
				$postData['value'] = $val;
				$postData['representation'] = $dsr->resourceId;
				$postData['label'] = $id;
				$postString = http_build_query($postData);
				$postString = preg_replace('/%0D%0A/', '<br/>', $postString);
				$formResp = $this->CollibraAPI->post('term/'.$dsr->resourceId.'/attributes', $postString);
				$formResp = json_decode($formResp);

				if (!isset($formResp)) {
					$err = true;
				}
			}
		}

		if (!$err) {
			if ($this->request->query['g'] == '0') {
				$this->redirect(['controller' => 'myaccount', 'action' => 'index', '?' => ['expand' => $dsrId]]);
			} else {
				$this->redirect(['controller' => 'request', 'action' => 'view/'.$dsrId, '?' => ['expand' => 'true']]);
			}
		} else {
			$this->redirect(['action' => 'edit/'.$dsrId, '?' => ['err' => 1]]);
		}
	}

	public function edit($dsrId) {
		if (empty($dsrId)) {
			$this->redirect(['action' => 'index']);
		}

		// Load DSR's current state
		$resp = $this->CollibraAPI->get('term/'.$dsrId);
		$request = json_decode($resp);

		$netID = $this->Auth->user('username');

		$guest = true;
		foreach($request->attributeReferences->attributeReference as $attr) {
			if ($attr->labelReference->signifier == 'Requester Net Id') {
				if ($attr->value == $netID) {
					$guest = false;
					break;
				}
			}
		}

		$completedStatuses = ['Completed', 'Approved', 'Obsolete'];
		if (in_array($request->statusReference->signifier, $completedStatuses)) {
			$this->Flash->error('You cannot edit a completed Request.');
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}

		$request->dataUsages = $this->CollibraAPI->getDataUsages($dsrId);
		if (!empty($request->dataUsages)) {
			$this->Flash->error('You cannot edit a DSR with any associated DSAs.');
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}

		// Check whether $request is a DSR or DSA
		$isaRequest = $request->conceptType->resourceId == Configure::read('Collibra.type.isaRequest');

		// load form fields for ISA workflow
		$formResp = $this->CollibraAPI->get('workflow/'.Configure::read('Collibra.isaWorkflow.id').'/form/start');
		$formResp = json_decode($formResp);

		$arrNewAttr = array();
		foreach($formResp->formProperties as $wf){
			foreach($request->attributeReferences->attributeReference as $attr){
				if($attr->labelReference->signifier == $wf->name){
					$arrNewAttr[$attr->labelReference->signifier] = $attr;
					break;
				}
			}
		}
		$request->attributeReferences->attributeReference = $arrNewAttr;

		$this->set('guest', $guest);
		$this->set('formFields', $formResp);
		$this->set('request', $request);
		$this->set('isaRequest', $isaRequest);
		$this->set('submitErr', isset($this->request->query['err']));
	}

	public function delete($dsrId) {
		$this->autoRender = false;

		if (empty($dsrId)) {
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}

		//Load DSR to check that the request isn't completed
		$resp = $this->CollibraAPI->get('term/'.$dsrId);
		$request = json_decode($resp);

		$completedStatuses = ['Completed', 'Approved', 'Obsolete'];
		if (in_array($request->statusReference->signifier, $completedStatuses)) {
			$this->Flash->error('You cannot delete a completed Request.');
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}

		$request->dataUsages = $this->CollibraAPI->getDataUsages($dsrId);
		foreach ($request->dataUsages as $du) {
			if (in_array($du->status, $completedStatuses)) {
				$this->Flash->error('You cannot delete a Request if any associated DSAs are completed.');
				$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
			}
		}

		foreach ($request->dataUsages as $du) {
			$this->delete($du->id);
		}

		$postData['status'] = Configure::read('Collibra.status.deleted');
		$postString = http_build_query($postData);
		$this->CollibraAPI->post("term/{$dsrId}/status", $postString);
		$this->Flash->success('Request deleted.');
		$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
	}

	public function submit() {
		$this->autoRender = false;

		if(!$this->request->is('post')){
			header('location: /search');
			exit;
		}

		$arrQueue = $this->Session->read('queue');
		if (
			empty($arrQueue['businessTerms']) &&
			empty($arrQueue['concepts']) &&
			empty($arrQueue['apiFields']) &&
			empty($arrQueue['dbColumns']) &&
			empty($arrQueue['emptyApis'])
		) {
			$this->redirect(['action' => 'index', '?' => ['err' => 1]]);
			exit;
		}

		$businessTermIds = [];
		$apis = [];
		$tables = [];
		foreach ($arrQueue['businessTerms'] as $id => $term) {
			array_push($businessTermIds, $id);
			if (!empty($term['apiPath']) && !empty($term['apiHost'])) {
				$apis[$term['apiHost']][$term['apiPath']] = [];
			}
			if (!empty($term['tableName'])) {
				$tables[$term['tableName']] = [];
			}
		}
		foreach ($arrQueue['concepts'] as $id => $term) {
			if (!empty($term['apiPath']) && !empty($term['apiHost'])) {
				$apis[$term['apiHost']][$term['apiPath']] = [];
			}
		}
		foreach ($arrQueue['apiFields'] as $fieldName => $field) {
			if (!empty($field['apiPath']) && !empty($field['apiHost'])) {
				$apis[$field['apiHost']][$field['apiPath']] = [];
			}
		}
		foreach ($arrQueue['dbColumns'] as $columnName => $column) {
			if (!empty($column['tableName'])) {
				$tables[$column['tableName']] = [];
			}
		}

		$additionalInformationAPIs = "";
		foreach ($arrQueue['emptyApis'] as $path => $api) {
			$additionalInformationAPIs .= "\n. . {$api['apiHost']}/{$path}\n. . . . [No specified output fields]";
			$apis[$api['apiHost']][$path] = [];
		}
		$this->request->data['descriptionOfInformation'] .= $additionalInformationAPIs;

		$name = explode(' ',$this->request->data['name']);
		$firstName = $name[0];
		$lastName = '';
		if(sizeof($name)>1) $lastName = $name[1];
		$email = $this->request->data['email'];
		$phone = $this->request->data['phone'];
		$role = $this->request->data['role'];

		// create guest user to use for submitting request
		/*
		$guestUserResp = $this->CollibraAPI->post(
				'user/guest',
				['firstName' => $firstName, 'lastName' => $lastName, 'email' => $this->request->data['email']]
		);
		$guestUserResp = json_decode($guestUserResp);
		$guestID = $guestUserResp->resourceId;
		*/

		$netID = $this->Auth->user('username');
		$byuUser = $this->BYUAPI->personalSummary($netID);
		$supervisorInfo = $this->BYUAPI->supervisorLookup($netID);

		$postData['requesterNetId'] = [$byuUser->identifiers->net_id, $supervisorInfo->net_id];
		foreach($this->request->data as $key => $val){
			if (!in_array($key, ['name', 'phone', 'email', 'role', 'terms', 'apiTerms', 'requestSubmit', 'collibraUser'])) {
				$postData[$key] = $val;
			}
		}
		// add user's contact info to post
		$postData['requesterName'] = $firstName.' '.$lastName;
		$postData['requesterEmail'] = $email;
		$postData['requesterPhone'] = $phone;
		$postData['requesterRole'] = $role;

		$requiredElementsString = Configure::read('Collibra.isaWorkflow.requiredElementsString');
		$additionalElementsString = Configure::read('Collibra.isaWorkflow.additionalElementsString');
		$postData[$requiredElementsString] = !empty($businessTermIds) ? $businessTermIds : '';
		$postData['api'] = [];
		$postData['table'] = [];
		if (!empty($additionalElementsString)) {
			$postData[$additionalElementsString] = array();
			foreach ($apis as $apiHost => $apiPaths) {
				foreach ($apiPaths as $apiPath => $ignore) {
					$apiObject = $this->CollibraAPI->getApiObject($apiHost, $apiPath);
					array_push($postData['api'], $apiObject->id);
					$apiTerms = $this->CollibraAPI->getApiTerms($apiHost, $apiPath);
					foreach ($apiTerms as $term) {
						if (!empty($term->assetType) && strtolower($term->assetType) == 'fieldset') {
							continue;
						}
						if (empty($term->businessTerm[0]->termId)) {
							continue;
						}
						if (!array_key_exists($term->businessTerm[0]->termId, $arrQueue['businessTerms'])) {
							array_push($postData[$additionalElementsString], $term->businessTerm[0]->termId);
						}
					}
				}
			}
			foreach ($tables as $tableName => $_) {
				$tableObject = $this->CollibraAPI->getTableObject($tableName);
				array_push($postData['table'], $tableObject->id);
				$tableColumns = $this->CollibraAPI->getTableColumns($tableName);
				foreach ($tableColumns as $column) {
					if (empty($column->businessTerm[0]->termId)) {
						continue;
					}
					if (!array_key_exists($column->businessTerm[0]->termId, $arrQueue['businessTerms'])) {
						array_push($postData[$additionalElementsString], $column->businessTerm[0]->termId);
					}
				}
			}
			$postData[$additionalElementsString] = array_unique($postData[$additionalElementsString]);
			if (empty($postData[$additionalElementsString])) {
				//Collibra requires "additionalElements" field to exist, even if empty,
				//but http_build_query throws out fields if null or empty array.
				//So we'll put a blank space in, which http_build_query
				//will not throw away
				$postData[$additionalElementsString] = '';
			}
		}

		//See above comment regarding "additionalElements"
		if (empty($postData['api'])) {
			$postData['api'] = '';
		}
		if (empty($postData['table'])) {
			$postData['table'] = '';
		}

		//For array data, PHP's http_build_query creates query/POST string in a format Collibra doesn't like,
		//so we have to tweak the output a bit
		$postString = http_build_query($postData);
		$postString = preg_replace("/requesterNetId%5B[0-9]*%5D/", "requesterNetId", $postString);
		$postString = preg_replace("/api%5B[0-9]*%5D/", "api", $postString);
		$postString = preg_replace("/table%5B[0-9]*%5D/", "table", $postString);
		$postString = preg_replace("/{$requiredElementsString}%5B[0-9]*%5D/", $requiredElementsString, $postString);
		if (!empty($additionalElementsString)) {
			$postString = preg_replace("/{$additionalElementsString}%5B[0-9]*%5D/", $additionalElementsString, $postString);
		}
		$postString = preg_replace('/%0D%0A/','<br/>',$postString);

		$formResp = $this->CollibraAPI->post(
			'workflow/'.Configure::read('Collibra.isaWorkflow.id').'/start',
			$postString
		);
		$formResp = json_decode($formResp);

		if(isset($formResp->startWorkflowResponses[0]->successmessage)){
			$processID = $formResp->startWorkflowResponses[0]->processInstanceId;

			// attempt to reindex source to make sure latest requests are displayed
			$resp = $this->CollibraAPI->post('search/re-index');

			$this->deleteDraft();

			// clear items in queue
			$this->Session->delete('queue');

			$this->redirect(['action' => 'success']);
		}else{
			$this->redirect(['action' => 'index', '?' => ['err' => 1]]);
		}
	}

	public function view($dsrId) {
		if (empty($dsrId)) {
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}
		$expand = '';
		if (isset($this->request->query['expand'])) {
			$expand = $dsrId;
		}

		$netID = $this->Auth->user('username');

		$this->loadModel('CollibraAPI');
		$resp = $this->CollibraAPI->get('term/'.$dsrId);
		$dsr = json_decode($resp);

		$parent = $dsr->conceptType->resourceId == Configure::read('Collibra.type.isaRequest');

		$dsr->roles = $this->CollibraAPI->getResponsibilities($dsr->vocabularyReference->resourceId);
		$dsr->policies = $this->CollibraAPI->getAssetPolicies($dsrId);
		$resp = $this->CollibraAPI->get('term/'.$dsrId.'/attachments');
		$resp = json_decode($resp);
		$dsr->attachments = $resp->attachment;
		if ($parent) {
			$dsr->dataUsages = $this->CollibraAPI->getDataUsages($dsr->resourceId);
		} else {
			$dsr->parent = $this->CollibraAPI->getDataUsageParent($dsr->resourceId);
		}

		$termRequestId = $parent ? $dsr->resourceId : $dsr->parent[0]->id;
		$requestedTerms = $this->CollibraAPI->getRequestedTerms($termRequestId);
		$dsr->termGlossaries = array();
		foreach ($requestedTerms as $term) {
			if (array_key_exists($term->domainname, $dsr->termGlossaries)) {
				array_push($dsr->termGlossaries[$term->domainname], $term);
			} else {
				$dsr->termGlossaries[$term->domainname] = array($term);
			}
		}

		// load additionally included terms
		////////////////////////////////////////////
		$resp = $this->CollibraAPI->getAdditionallyIncludedTerms($termRequestId);
		if (!empty($resp)) {
			$dsr->additionallyIncluded = new stdClass();
			$dsr->additionallyIncluded->termGlossaries = [];
			foreach ($resp as $term) {
				if (array_key_exists($term->domainname, $dsr->additionallyIncluded->termGlossaries)) {
					array_push($dsr->additionallyIncluded->termGlossaries[$term->domainname], $term);
				} else {
					$dsr->additionallyIncluded->termGlossaries[$term->domainname] = array($term);
				}
			}
		}

		$arrNewAttr = [];
		$arrCollaborators = array();
			foreach($dsr->attributeReferences->attributeReference as $attr){
				if ($attr->labelReference->signifier == 'Requester Net Id') {
					$person = $this->BYUAPI->personalSummary($attr->value);
					unset($person->person_summary_line, $person->personal_information, $person->student_information, $person->relationships);
					array_push($arrCollaborators, $person);
					continue;
				}
				$arrNewAttr[$attr->labelReference->signifier] = $attr;
			}
			$arrNewAttr['Collaborators'] = $arrCollaborators;
		$dsr->attributeReferences->attributeReference = $arrNewAttr;

		if ($parent) {
			for ($i = 0; $i < sizeof($dsr->dataUsages); $i++) {
				$resp = $this->CollibraAPI->get('term/'.$dsr->dataUsages[$i]->id);
				$resp = json_decode($resp);
				$dsr->dataUsages[$i]->attributeReferences = $resp->attributeReferences;

				$resp = $this->CollibraAPI->get('term/'.$dsr->dataUsages[$i]->id.'/attachments');
				$resp = json_decode($resp);
				$dsr->dataUsages[$i]->attachments = $resp->attachment;
			}
		}

		$this->set('netID', $netID);
		$this->set('request', $dsr);
		$this->set('parent', $parent);
		$this->set('expand', $expand);
	}

	public function printView($dsrId) {
		if (empty($dsrId)) {
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}
		$this->autoLayout = false;

		$this->loadModel('CollibraAPI');
		$resp = $this->CollibraAPI->get('term/'.$dsrId);
		$dsr = json_decode($resp);

		$parent = $dsr->conceptType->resourceId == Configure::read('Collibra.type.isaRequest');

		$dsr->roles = $this->CollibraAPI->getResponsibilities($dsr->vocabularyReference->resourceId);
		$dsr->policies = $this->CollibraAPI->getAssetPolicies($dsrId);
		if($parent) {
			$dsr->dataUsages = $this->CollibraAPI->getDataUsages($dsr->resourceId);
		} else {
			$dsr->parent = $this->CollibraAPI->getDataUsageParent($dsr->resourceId);
		}

		$termRequestId = $parent ? $dsr->resourceId : $dsr->parent[0]->id;
		$requestedTerms = $this->CollibraAPI->getRequestedTerms($termRequestId);
		$dsr->termGlossaries = array();
		foreach ($requestedTerms as $term) {
			if (array_key_exists($term->domainname, $dsr->termGlossaries)) {
				array_push($dsr->termGlossaries[$term->domainname], $term);
			} else {
				$dsr->termGlossaries[$term->domainname] = array($term);
			}
		}

		// load additionally included terms
		////////////////////////////////////////////
		$resp = $this->CollibraAPI->getAdditionallyIncludedTerms($termRequestId);
		if (!empty($resp)) {
			$dsr->additionallyIncluded = new stdClass();
			$dsr->additionallyIncluded->termGlossaries = [];
			foreach ($resp as $term) {
				if (array_key_exists($term->domainname, $dsr->additionallyIncluded->termGlossaries)) {
					array_push($dsr->additionallyIncluded->termGlossaries[$term->domainname], $term);
				} else {
					$dsr->additionallyIncluded->termGlossaries[$term->domainname] = array($term);
				}
			}
		}

		// sort request attribute data based on workflow form field order
		$workflowResp = $this->CollibraAPI->get('workflow/'.Configure::read('Collibra.isaWorkflow.id').'/form/start');
		$workflowResp = json_decode($workflowResp);

		$arrNewAttr = [];
		foreach($workflowResp->formProperties as $wf){
			foreach($dsr->attributeReferences->attributeReference as $attr){
				if($attr->labelReference->signifier == $wf->name){
					$arrNewAttr[$attr->labelReference->signifier] = $attr;
					break;
				}
			}
		}
		$dsr->attributeReferences->attributeReference = $arrNewAttr;

		if ($parent) {
			for ($i = 0; $i < sizeof($dsr->dataUsages); $i++) {
				$resp = $this->CollibraAPI->get('term/'.$dsr->dataUsages[$i]->id);
				$resp = json_decode($resp);
				$dsr->dataUsages[$i]->attributeReferences = $resp->attributeReferences;
			}
		}

		$this->set('request', $dsr);
		$this->set('parent', $parent);
	}

	public function downloadFile($fileId, $fileName) {
		$this->loadModel('CollibraAPI');

		if (!file_exists(TMP.'/attachments/'.$fileName)) {
			$file = $this->CollibraAPI->get('attachment/download/'.$fileId);
			file_put_contents(TMP.'/attachments/'.$fileName, $file);
		}

		$this->response->file('tmp/attachments/'.$fileName);
		return $this->response;
	}

	public function index() {
		$netID = $this->Auth->user('username');
		$byuUser = $this->BYUAPI->personalSummary($netID);
		$supervisorInfo = $this->BYUAPI->supervisorLookup($netID);

		// make sure terms have been added to the users's queue
		$arrQueue = $this->Session->read('queue');
		if(
			empty($arrQueue['businessTerms']) &&
			empty($arrQueue['concepts']) &&
			empty($arrQueue['apiFields']) &&
			empty($arrQueue['dbColumns']) &&
			empty($arrQueue['emptyApis'])
		) {
			header('location: /search');
			exit;
		}

		$apis = [];
		$tables = [];
		foreach ($arrQueue['businessTerms'] as $term) {
			if (!empty($term['apiPath']) && !empty($term['apiHost'])) {
				$apis[$term['apiHost']][$term['apiPath']] = [];
			}
			if (!empty($term['tableName'])) {
				$tables[$term['tableName']] = [];
			}
		}
		foreach ($arrQueue['apiFields'] as $fieldName => $field) {
			if (!empty($field['apiPath']) && !empty($field['apiHost'])) {
				$apis[$field['apiHost']][$field['apiPath']] = [];
			}
		}
		foreach ($arrQueue['dbColumns'] as $column) {
			if (!empty($column['tableName'])) {
				$tables[$column['tableName']] = [];
			}
		}

		$preFilled = [];
		foreach ($apis as $apiHost => $apiPaths) {
			foreach ($apiPaths as $apiPath => $ignore) {
				$apiTerms = $this->CollibraAPI->getApiTerms($apiHost, $apiPath);
				foreach ($apiTerms as $term) {
					if (!empty($term->assetType) && strtolower($term->assetType) == 'fieldset') {
						continue;
					}
					if (empty($term->businessTerm[0]->termId)) {
						if (array_key_exists($term->name, $arrQueue['apiFields'])) {
							$apis[$apiHost][$apiPath]['unmapped']['requested'][] = $term->name;
						} else {
							$apis[$apiHost][$apiPath]['unmapped']['unrequested'][] = $term->name;
						}
					} else {
						if (array_key_exists($term->businessTerm[0]->termId, $arrQueue['concepts'])) {
							$apis[$apiHost][$apiPath]['requestedConcept'][] = '('.$term->businessTerm[0]->termCommunityName.') '.$term->businessTerm[0]->term;
						} else if (array_key_exists($term->businessTerm[0]->termId, $arrQueue['businessTerms'])) {
							$apis[$apiHost][$apiPath]['requestedBusinessTerm'][] = '('.$term->businessTerm[0]->termCommunityName.') '.$term->businessTerm[0]->term;
						} else {
							$apis[$apiHost][$apiPath]['unrequested'][] = '('.$term->businessTerm[0]->termCommunityName.') '.$term->businessTerm[0]->term;
						}
					}
				}
			}
		}
		foreach ($tables as $tableName => $_) {
			$tableColumns = $this->CollibraAPI->getTableColumns($tableName);
			foreach ($tableColumns as $column) {
				if (empty($column->businessTerm[0]->termId)) {
					if (array_key_exists($column->columnName, $arrQueue['dbColumns'])) {
						$tables[$tableName]['unmapped']['requested'][] = $column->columnName;
					} else {
						$tables[$tableName]['unmapped']['unrequested'][] = $column->columnName;
					}
				} else {
					if (array_key_exists($column->businessTerm[0]->termId, $arrQueue['concepts'])) {
						$tables[$tableName]['requestedConcept'][] = '('.$column->businessTerm[0]->termCommunityName.') '.$column->businessTerm[0]->term;
					} else if (array_key_exists($column->businessTerm[0]->termId, $arrQueue['businessTerms'])) {
						$tables[$tableName]['requestedBusinessTerm'][] = '('.$column->businessTerm[0]->termCommunityName.') '.$column->businessTerm[0]->term;
					} else {
						$tables[$tableName]['unrequested'][] = '('.$column->businessTerm[0]->termCommunityName.') '.$column->businessTerm[0]->term;
					}
				}
			}
		}
		$preFilled['descriptionOfInformation'] = '';
		if (!empty($apis)) {
			$apiList = "<b>Requested APIs:</b>\n";
			foreach ($apis as $apiHost => $apiPaths) {
				foreach ($apiPaths as $apiPath => $term) {
					$apiList .= ". . <u><b>{$apiHost}/{$apiPath}</u></b>\n";
					if (!empty($term['requestedBusinessTerm'])) {
						$term['requestedBusinessTerm'] = array_unique($term['requestedBusinessTerm']);
						sort($term['requestedBusinessTerm']);
						$apiList .= "\n. . . . <b>Requested business terms:</b>\n. . . . . . " . implode("\n. . . . . . ", $term['requestedBusinessTerm']) . "\n";
					}
					if (!empty($term['requestedConcept'])) {
						$term['requestedConcept'] = array_unique($term['requestedConcept']);
						sort($term['requestedConcept']);
						$apiList .= ". . . . Requested conceptual terms:\n. . . . . . " . implode("\n. . . . . . ", $term['requestedConcept']) . "\n";
					}
					if (!empty($term['unrequested'])) {
						$term['unrequested'] = array_unique($term['unrequested']);
						sort($term['unrequested']);
						$apiList .= "\n. . . . <b>Unrequested terms:</b>\n. . . . . . " . implode("\n. . . . . . ", $term['unrequested']) . "\n";
					}
					if (!empty($term['unmapped'])) {
						$apiList .= "\n. . . . <b>*Fields with no Business Terms:</b>\n";
						if (!empty($term['unmapped']['requested'])) {
							$apiList .= ". . . . . . Requested:\n. . . . . . . . " . implode("\n. . . . . . . . ", $term['unmapped']['requested']) . "\n";
						}
						if (!empty($term['unmapped']['unrequested'])) {
							$apiList .= ". . . . . . Unrequested:\n. . . . . . . . " . implode("\n. . . . . . . . ", $term['unmapped']['unrequested']) . "\n";
						}
					}
					$apiList .= "\n\n";
				}
			}
			$preFilled['descriptionOfInformation'] .= $apiList;
		}
		if (!empty($tables)) {
			$tableList = "<b>Requested Tables:</b>\n";
			foreach ($tables as $tableName => $term) {
				$tableList .= ". . <u><b>{$tableName}</u></b>\n";
				if (!empty($term['requestedBusinessTerm'])) {
					$term['requestedBusinessTerm'] = array_unique($term['requestedBusinessTerm']);
					sort($term['requestedBusinessTerm']);
					$tableList .= "\n. . . . <b>Requested business terms:</b>\n. . . . . . " . implode("\n. . . . . . ", $term['requestedBusinessTerm']) . "\n";
				}
				if (!empty($term['requestedConcept'])) {
					$term['requestedConcept'] = array_unique($term['requestedConcept']);
					sort($term['requestedConcept']);
					$tableList .= ". . . . Requested conceptual terms:\n. . . . . . " . implode("\n. . . . . . ", $term['requestedConcept']) . "\n";
				}
				if (!empty($term['unrequested'])) {
					$term['unrequested'] = array_unique($term['unrequested']);
					sort($term['unrequested']);
					$tableList .= "\n. . . . <b>Unrequested terms:</b>\n. . . . . . " . implode("\n. . . . . . ", $term['unrequested']) . "\n";
				}
				if (!empty($term['unmapped'])) {
					$tableList .= "\n. . . . <b>*Columns with no Business Terms:</b>\n";
					if (!empty($term['unmapped']['requested'])) {
						$tableList .= ". . . . . . Requested:\n. . . . . . . . " . implode("\n. . . . . . . . ", $term['unmapped']['requested']) . "\n";
					}
					if (!empty($term['unmapped']['unrequested'])) {
						$tableList .= ". . . . . . Unrequested:\n. . . . . . . . " . implode("\n. . . . . . . . ", $term['unmapped']['unrequested']) . "\n";
					}
				}
				$tableList .= "\n";
			}
			$preFilled['descriptionOfInformation'] .= $tableList;
		}

		// Retrieve the customer's work if they have a draft
		$draftId = $this->CollibraAPI->checkForDSRDraft($this->Auth->user('username'));
		if (!empty($draftId)) {

			$draft = $this->CollibraAPI->get('term/'.$draftId[0]->id);
			$draft = json_decode($draft);

			$arrLabelMatch = [
				'Requester Name' => 'name',
				'Requester Phone' => 'phone',
				'Requester Role' => 'role',
				'Requester Email' => 'email',
				'Requesting Organization' => 'requestingOrganization',
				'Sponsor Name' => 'sponsorName',
				'Sponsor Phone' => 'sponsorPhone',
				'Sponsor Role' => 'sponsorRole',
				'Sponsor Email' => 'sponsorEmail',
				'Application Name' => 'applicationName',
				//'Additional Information Requested' => 'descriptionOfInformation',
				'Description of Intended Use' => 'descriptionOfIntendedUse',
				'Access Rights' => 'accessRights',
				'Access Method' => 'accessMethod',
				'Impact on System' => 'impactOnSystem'
			];
			$arrFormFields = [];
			foreach ($draft->attributeReferences->attributeReference as $attr) {
				if ($attr->labelReference->signifier == 'Additional Information Requested') {
					continue;
				}
				$label = $arrLabelMatch[$attr->labelReference->signifier];
				$preFilled[$label] = $attr->value;
			}
		}

		$termResp = $this->CollibraAPI->getBusinessTermDetails($arrQueue['businessTerms']);
		if (!empty($termResp)) {
			foreach ($termResp as $term) {
				$termNames[] = $term->termsignifier;
				$term->apihost = $arrQueue['businessTerms'][$term->termrid]['apiHost'];
				$term->apipath = $arrQueue['businessTerms'][$term->termrid]['apiPath'];
				$term->schemaname = $arrQueue['businessTerms'][$term->termrid]['schemaName'];
				$term->tablename = $arrQueue['businessTerms'][$term->termrid]['tableName'];
			}
			array_multisort($termNames, SORT_ASC, $termResp);
		}
		// If a business term in the cart has been deleted in Collibra, remove from cart
		foreach ($arrQueue['businessTerms'] as $termID => $term) {
			if (!in_array($term['term'], $termNames)) {
				unset($arrQueue['businessTerms'][$termID]);
				$this->Session->write('queue', $arrQueue);
			}
		}

		$policies = [];
		$allPolicies = $this->CollibraAPI->getPolicies();
		foreach ($allPolicies as $policy) {
			switch($policy->policyName) {
				case 'Standard Data Usage Policies':
					array_push($policies, $policy);
					break;
				case 'Trusted Partner Security Standards':
					foreach ($arrQueue['businessTerms'] as $term) {
						if ($term['communityId'] == Configure::read('Collibra.community.academicRecords')) {
							array_push($policies, $policy);
							break;
						}
					}
					break;
			}
		}

		// load form fields for ISA workflow
		$formResp = $this->CollibraAPI->get('workflow/'.Configure::read('Collibra.isaWorkflow.id').'/form/start');
		$formResp = json_decode($formResp);

		$this->set('formFields', $formResp);
		$this->set('termDetails', $termResp);

		$psName = '';
		$psPhone = '';
		$psEmail = '';
		$psRole = '';
		$psDepartment = '';
		$psReportsToName = '';
		if(isset($byuUser->names->preferred_name)){
			$psName = $byuUser->names->preferred_name;
		}
		if(isset($byuUser->contact_information->work_phone)){
			$psPhone = $byuUser->contact_information->work_phone;
		}
		if(isset($byuUser->contact_information->email)){
			$psEmail = $byuUser->contact_information->email;
		}
		if(isset($byuUser->employee_information->job_title)){
			$psRole = $byuUser->employee_information->job_title;
		}
		if(isset($byuUser->employee_information->reportsToName)){
			$psReportsToName = $byuUser->employee_information->reportsToName;
		}
		if (!empty($byuUser->employee_information->department)) {
			$psDepartment = $byuUser->employee_information->department;
		}

		$this->set(compact('preFilled', 'arrQueue', 'psName', 'psPhone', 'psEmail', 'psRole', 'psDepartment', 'psReportsToName', 'supervisorInfo', 'policies'));
		$this->set('submitErr', isset($this->request->query['err']));
	}
}
