<?php

require 'fpdf' . DIRECTORY_SEPARATOR . 'fpdf.php';

class RequestController extends AppController {
	public $helpers = ['Html', 'Form'];
	public $uses = ['CollibraAPI', 'BYUAPI'];

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
		$dsa = $this->CollibraAPI->getRequestDetails($dsaId, false);
		if (empty($dsa)) {
			return json_encode(['success' => '0', 'message' => 'Invalid DSA ID given.']);
		}
		$attachments = json_decode($this->CollibraAPI->get('term/'.$dsaId.'/attachments'));
		foreach ($attachments->attachment as $file) {
			if ($file->fileName == 'DSA_'.$dsaId.'.pdf') {
				return json_encode(['success' => '0', 'message' => 'A PDF has already been generated for the specified DSA.']);
			}
		}

		$dsa->roles = $this->CollibraAPI->getResponsibilities($dsa->vocabularyId);
		$dsa->reqTermGlossaries = [];
		foreach ($dsa->requestedTerms as $term) {
			if (array_key_exists($term->reqTermVocabName, $dsa->reqTermGlossaries)) {
				array_push($dsa->reqTermGlossaries[$term->reqTermVocabName], $term);
			} else {
				$dsa->reqTermGlossaries[$term->reqTermVocabName] = [$term];
			}
		}

		if (!empty($dsa->additionallyIncludedTerms)) {
			$dsa->addTermGlossaries = [];
			foreach ($dsa->additionallyIncludedTerms as $term) {
				if (array_key_exists($term->addTermVocabName, $dsa->addTermGlossaries)) {
					array_push($dsa->addTermGlossaries[$term->addTermVocabName], $term);
				} else {
					$dsa->addTermGlossaries[$term->addTermVocabName] = [$term];
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
		$pdf->Cell(0,20,$dsa->assetName,0,1,'C');

		$pdf->SetFont('','B',10);
		$pdf->SetTextColor(0);

		foreach ($dsa->reqTermGlossaries as $glossaryName => $terms) {
			if ($terms[0]->reqTermCommId != $dsa->communityId) {
				continue;
			}

			$pdf->Write(5,"Requested Data:");
			$pdf->SetFont('','');

			$pdf->SetFont('','I');
			$pdf->Write(5,"\n{$glossaryName} - ");
			$pdf->SetFont('','');
			$termCount = 0;
			foreach ($terms as $term) {
				$pdf->Write(5,$term->reqTermSignifier);
				$termCount++;
				if ($termCount < sizeof($terms)) {
					$pdf->Write(5,',  ');
				}
			}
			$pdf->Ln(10);
		}

		if (!empty($dsa->addTermGlossaries)) {
			foreach ($dsa->addTermGlossaries as $glossaryName => $terms) {
				if ($terms[0]->addTermCommId != $dsa->communityId) {
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
					$pdf->Write(5,$term->addTermSignifier);
					$termCount++;
					if ($termCount < sizeof($terms)) {
						$pdf->Write(5,',  ');
					}
				}
				$pdf->Ln(10);
			}
		}

		foreach ($dsa->attributes as $attr) {
			if ($attr->attrSignifier == 'Effective Start Date') {
				$effectiveDate = date('n/j/Y', $attr->attrValue/1000);
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
			foreach ($dsa->attributes as $attr) {
				if ($attr->attrSignifier == $field && !empty($attr->attrValue)) {
					$pdf->SetFont('','B',12);
					$pdf->Cell(0,10,$field,'B',1);
					$pdf->Ln(2);
					$pdf->SetFont('','',9);

					$attrText = $attr->attrValue;
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
		foreach ($dsa->attributes as $attr) {
			if (array_key_exists($attr->attrSignifier, $arrPersonInfo)) {
				$arrPersonInfo[$attr->attrSignifier] = html_entity_decode($attr->attrValue);
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
					$arrTermIds = [];
					foreach ($arrTerms as $term) {
						$arrTermIds[$term['id']] = 'ignore';
					}
					$arrTermDetails = $this->CollibraAPI->getBusinessTermDetails($arrTermIds);

					foreach ($arrTermDetails as $term) {
						if (empty($arrQueue['businessTerms'][$term->termrid]) && empty($arrQueue['concepts'][$term->termrid])) {
							// verify that the term is requestable
							$concept = false;
							if (!Configure::read('allowUnrequestableTerms')) {
								$concept = $term->concept == 'true';
							}

							$requestable = true;
							if (!Configure::read('allowUnapprovedTerms')) {
								$requestable = $term->statusname == 'Accepted';
							}

							if ($requestable && !$concept) {
								$newTermsAdded++;
								$arrQueue['businessTerms'][$term->termrid] = ['term' => $term->termsignifier, 'communityId' => $term->commrid, 'apiHost' => $apiHost, 'apiPath' => $apiPath, 'schemaName' => $schemaName, 'tableName' => $tableName];
							} else if ($requestable && $concept) {
								$newTermsAdded++;
								$arrQueue['concepts'][$term->termrid] = ['term' => $term->termsignifier, 'communityId' => $term->commrid, 'apiHost' => $apiHost, 'apiPath' => $apiPath, 'schemaName' => $schemaName, 'tableName' => $tableName];
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

		if (isset($this->request->data['dsa'])) {
			unset($this->request->data['dsa']);
			$dsa = $this->CollibraAPI->getRequestDetails($dsrId, false);
			return $this->addCollaborator($dsa->parentId, $personId);
		}

		if (empty($dsrId) || empty($personId)) {
			return json_encode(['success' => 0, 'message' => 'Bad request']);
		}

		$person = $this->BYUAPI->personalSummary($personId);
		if (!isset($person)) {
			return json_encode(['success' => 0, 'message' => 'Person\'s information could not be loaded']);
		}

		$request = $this->CollibraAPI->getRequestDetails($dsrId);

		foreach($request->attributes as $attr) {
			if ($attr->attrSignifier == 'Requester Net Id' && $attr->attrValue == $person->identifiers->net_id) {
				return json_encode(['success' => 0, 'message' => 'This person is already a collaborator on this request.']);
			}
		}

		$postData['value'] = $person->identifiers->net_id;
		$postData['representation'] = $dsrId;
		$postData['label'] = Configure::read('Collibra.attribute.requesterNetId');
		$postString = http_build_query($postData);
		$formResp = $this->CollibraAPI->post('term/'.$dsrId.'/attributes', $postString);
		$formResp = json_decode($formResp);
		if (!isset($formResp)) {
			return json_encode(['success' => 0, 'message' => 'We had a problem getting to Collibra']);
		}

		// Add to DSAs as well
		foreach($request->dsas as $dsa) {
			$postData['representation'] = $dsa->dsaId;
			$postString = http_build_query($postData);
			$formResp = $this->CollibraAPI->post('term/'.$dsa->dsaId.'/attributes', $postString);
			$formResp = json_decode($formResp);
			if (!isset($formResp)) {
				return json_encode(['success' => 0, 'message' => 'We had a problem getting to Collibra']);
			}
		}

		return json_encode(['success' => 1, 'person' => $person]);
	}

	public function removeCollaborator($dsrId, $netId) {
		$this->autoRender = false;

		if (isset($this->request->data['dsa'])) {
			unset($this->request->data['dsa']);
			$dsa = $this->CollibraAPI->getRequestDetails($dsrId, false);
			return $this->removeCollaborator($dsa->parentId, $netId);
		}

		if (empty($dsrId) || empty($netId)) {
			return json_encode(['success' => 0, 'message' => 'DSR ID or Net ID missing.']);
		}

		$request = $this->CollibraAPI->getRequestDetails($dsrId);

		$toDeleteIds = [];
		foreach ($request->dsas as $dsa) {
			$dsa->attributes = $this->CollibraAPI->getAttributes($dsa->dsaId);
			foreach ($dsa->attributes as $attr) {
				if ($attr->attrSignifier == 'Requester Net Id' && $attr->attrValue == $netId) {
					array_push($toDeleteIds, $attr->attrResourceId);
					break;
				}
			}
		}

		foreach ($request->attributes as $attr) {
			if ($attr->attrSignifier == 'Requester Net Id' && $attr->attrValue == $netId) {
				array_push($toDeleteIds, $attr->attrResourceId);
				break;
			}
		}

		$postString = http_build_query(['resource' => $toDeleteIds]);
		$postString = preg_replace("/%5B[0-9]*%5D/", "", $postString);
		$resp = $this->CollibraAPI->deleteJSON('attribute', $postString);

		if ($resp->code != '200') {
			$resp = json_decode($resp);
			return json_encode(['success' => 0, 'message' => $resp->message]);
		} else {
			return json_encode(['success' => 1, 'message' => "This person is no longer a collaborator on \"{$request->assetName}\"."]);
		}
	}

	public function saveDraft() {
		$this->autoRender = false;
		$netId = $this->Auth->user('username');

		$postData = [];
		$postData['netId'] = $netId;
		$draft = $this->CollibraAPI->checkForDSRDraft($netId);
		if (!empty($draft)) {
			$this->CollibraAPI->delete('term/'.$draft[0]->id);
		}

		$postData['attributeIds'] = [];
		$postData['values'] = [];
		$arrQueue = $this->Session->read('queue');
		array_push($postData['attributeIds'], Configure::read('Collibra.formFields.descriptionOfInformation'));
		array_push($postData['values'], json_encode($arrQueue).'  ');

		foreach ($this->request->data as $label => $value) {
			if (empty($value) || $label == 'descriptionOfInformation') continue;
			array_push($postData['attributeIds'], Configure::read("Collibra.formFields.{$label}"));
			array_push($postData['values'], $value.'  ');
		}

		$postString = http_build_query($postData);
		$postString = preg_replace("/%5B[0-9]*%5D/", "", $postString);
		$resp = $this->CollibraAPI->post('workflow/'.Configure::read('Collibra.workflow.createDSRDraft').'/start', $postString);

		if ($resp->code != '200') {
			return json_encode(['success' => 0, 'message' => $resp->message]);
		}
		return json_encode(['success' => 1, 'message' => 'Saved successfully']);
	}

	public function updateDraftCart() {
		$this->autoRender = false;
		if (!$netId = $this->Auth->user('username')) {
			return;
		}

		$draftId = $this->CollibraAPI->checkForDSRDraft($netId);
		if (empty($draftId)) {
			return;
		}
		$draft = $this->CollibraAPI->getRequestDetails($draftId[0]->id);
		foreach ($draft->attributes as $attr) {
			if ($attr->attrSignifier == 'Additional Information Requested') {
				$attrId = $attr->attrResourceId;
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

			$request = $this->CollibraAPI->getRequestDetails($this->request->data['dsrId']);

			$addedApis = [];
			$addedTables = [];
			$additionString = "<br/><br/>Addition, ".date('Y-m-d').":";

			$relationsPostData['dsrId'] = $this->request->data['dsrId'];
			$relationsPostData['requestedTerms'] = [];
			$relationsPostData['additionalTerms'] = [];
			$relationsPostData['apis'] = [];
			$relationsPostData['tables'] = [];
			if (isset($this->request->data['arrBusinessTerms'])) {
				$toDeleteIds = [];
				foreach ($this->request->data['arrBusinessTerms'] as $termid) {
					array_push($relationsPostData['requestedTerms'], $termid);

					foreach ($request->additionallyIncludedTerms as $inclTerm) {
						if ($inclTerm->addTermId == $termid) {
							// remove term from DSR's "Additionally Included" list if it's now requested
							array_push($toDeleteIds, $inclTerm->addTermRelationId);
							break;
						}
					}

					foreach ($arrQueue['businessTerms'] as $queueId => $queueTerm) {
						if ($queueId == $termid) {
							if (isset($queueTerm['apiHost']) && isset($queueTerm['apiPath'])) {
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
				$postString = http_build_query(['resource' => $toDeleteIds]);
				$postString = preg_replace("/%5B[0-9]*%5D/", "", $postString);
				$resp = $this->CollibraAPI->deleteJSON('relation', $postString);
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

			foreach ($request->necessaryApis as $alreadyListed) {
				unset($addedApis[$alreadyListed->apiCommName][substr($alreadyListed->apiName, 1)]);
				if (empty($addedApis[$alreadyListed->apiCommName])) {
					unset($addedApis[$alreadyListed->apiCommName]);
				}
			}

			$newBusinessTerms = [];
			foreach ($addedApis as $apiHost => $apiPaths) {
				foreach ($apiPaths as $apiPath => $_) {
					$apiObject = $this->CollibraAPI->getApiObject($apiHost, $apiPath);
					array_push($relationsPostData['apis'], $apiObject->id);

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
			foreach ($addedTables as $tableName => $_) {
				$table = $this->CollibraAPI->getTableObject($tableName);
				array_push($relationsPostData['tables'], $table->id);

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

			foreach ($request->attributes as $attr) {
				if ($attr->attrSignifier == 'Additional Information Requested') {
					$postData = [];
					$postData['value'] = $attr->attrValue . $additionString;
					$postData['rid'] = $attr->attrResourceId;
					$postString = http_build_query($postData);
					$postString = preg_replace('/%0D%0A/', '<br/>', $postString);
					$formResp = $this->CollibraAPI->post('attribute/'.$attr->attrResourceId, $postString);
					$formResp = json_decode($formResp);

					if (!isset($formResp)) {
						$success = false;
					}
					break;
				}
			}

			$postData = $this->request->data;
			$newBusinessTerms = array_filter($newBusinessTerms, function($termid) use($request, $postData) {
				foreach ($request->requestedTerms as $alreadyRequested) {
					if ($alreadyRequested->reqTermId == $termid) {
						return false;
					}
				}
				foreach ($request->additionallyIncludedTerms as $alreadyIncluded) {
					if ($alreadyIncluded->addTermId == $termid) {
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
			$newBusinessTerms = array_unique($newBusinessTerms);

			foreach ($newBusinessTerms as $termid) {
				array_push($relationsPostData['additionalTerms'], $termid);
			}

			while (count($relationsPostData['requestedTerms']) + count($relationsPostData['additionalTerms']) > 100) {
				if (count($relationsPostData['requestedTerms']) > count($relationsPostData['additionalTerms'])) {
					$longerArrayKey = 'requestedTerms';
				} else {
					$longerArrayKey = 'additionalTerms';
				}
				$postString = http_build_query(['dsrId' => $relationsPostData['dsrId'], $longerArrayKey => array_slice($relationsPostData[$longerArrayKey], 0, 100)]);
				$postString = preg_replace("/%5B[0-9]*%5D/", "", $postString);
				$resp = $this->CollibraAPI->post('workflow/'.Configure::read('Collibra.workflow.changeDSRRelations').'/start', $postString);

				$relationsPostData[$longerArrayKey] = array_slice($relationsPostData[$longerArrayKey], 100);
			}

			$postString = http_build_query($relationsPostData);
			$postString = preg_replace("/%5B[0-9]*%5D/", "", $postString);
			$resp = $this->CollibraAPI->post('workflow/'.Configure::read('Collibra.workflow.changeDSRRelations').'/start', $postString);

			$this->Session->write('queue', $arrQueue);
			return $success ? json_encode(['success' => 1]) : json_encode(['success' => 0]);
		}
		else if ($this->request->data['action'] == 'remove') {
			$toDeleteIds = [];
			foreach ($this->request->data['arrIds'] as $relationrid) {
				array_push($toDeleteIds, $relationrid);
			}
			$postString = http_build_query(['resource' => $toDeleteIds]);
			$postString = preg_replace("/%5B[0-9]*%5D/", "", $postString);
			$resp = $this->CollibraAPI->deleteJSON('relation', $postString);

			if ($resp->code != '200') {
				$resp = json_decode($resp);
				return json_encode(['success' => 0, 'message' => $resp->message]);
			} else {
				return json_encode(['success' => 1]);
			}
		}
		return json_encode(['success' => 0]);
	}

	public function editTerms($dsrId) {
		if (empty($dsrId)) {
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}

		$request = $this->CollibraAPI->getRequestDetails($dsrId);
		$netID = $this->Auth->user('username');

		$pendingStatuses = ['In Progress', 'Request In Progress', 'Agreement Review'];
		if (!in_array($request->statusName, $pendingStatuses)) {
			$this->Flash->error('You cannot edit a Request that isn\'t currently in progress.');
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}

		// Check whether $request is a DSR or DSA
		$parent = $request->conceptTypeId == Configure::read('Collibra.type.dataSharingRequest');
		if (!$parent) {
			$this->Flash->error('You cannot edit the terms on an individual DSA.');
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}

		if (!empty($request->dsas)) {
			$this->Flash->error('You cannot edit a DSR with any associated DSAs.');
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}

		$arrQueue = $this->Session->read('queue');
		$this->set(compact('request', 'arrQueue'));
		$this->set('submitErr', isset($this->request->query['err']));
	}

	public function editSubmit($assetId, $dsr = 'true') {
		$this->autoRender = false;
		if (!$this->request->is('post')) {
			header('location: /search');
			exit;
		}
		$parent = $dsr == 'true';

		$asset = $this->CollibraAPI->getRequestDetails($assetId, $parent);
		$err = false;

		$wfPostData = ['attributes' => [], 'values' => []];
		foreach ($this->request->data as $id => $val) {
			if ($id == 'requestSubmit') {
				continue;
			}
			$matchFound = false;
			foreach ($asset->attributes as $original) {
				if ($id == $original->attrTypeId) {
					$matchFound = true;
					if (preg_replace('/\R/', '<br/>', $val) != preg_replace('/&gt;/', '>', $original->attrValue)) {
						//Update values in Collibra database
						array_push($wfPostData['attributes'], $original->attrResourceId);
						array_push($wfPostData['values'], $val.'  ');
					}
					break;
				}
			}
			if (!$matchFound && !empty($val)) {		// i.e., if the value has been left blank/empty until now
				$postData['value'] = $val;
				$postData['representation'] = $asset->id;
				$postData['label'] = $id;
				$postString = http_build_query($postData);
				$postString = preg_replace('/%0D%0A/', '<br/>', $postString);
				$formResp = $this->CollibraAPI->post('term/'.$asset->id.'/attributes', $postString);
				$formResp = json_decode($formResp);

				if (!isset($formResp)) {
					$err = true;
				}
			}
		}

		if (!empty($wfPostData['attributes'])) {
			$postString = http_build_query($wfPostData);
			$postString = preg_replace('/%0D%0A/', '<br/>', $postString);
			$postString = preg_replace("/%5B[0-9]*%5D/", "", $postString);
			$resp = $this->CollibraAPI->post('workflow/'.Configure::read('Collibra.workflow.changeAttributes').'/start', $postString);
			if ($resp->code != '200') {
				$err = true;
			}
		}

		if (!$err) {
			if ($this->request->query['g'] == '0') {
				$this->redirect(['controller' => 'myaccount', 'action' => 'index', '?' => ['expand' => $assetId]]);
			} else if (!$parent) {
				$this->redirect(['controller' => 'request', 'action' => 'view/'.$assetId.'/false', '?' => ['expand' => 'true']]);
			} else {
				$this->redirect(['controller' => 'request', 'action' => 'view/'.$assetId, '?' => ['expand' => 'true']]);
			}
		} else if (!$parent) {
			$this->redirect(['action' => 'edit/'.$assetId.'/false', '?' => ['err' => 1]]);
		} else {
			$this->redirect(['action' => 'edit/'.$assetId, '?' => ['err' => 1]]);
		}
	}

	public function edit($assetId, $dsr = 'true') {
		if (empty($assetId)) {
			$this->redirect(['action' => 'index']);
		}
		$parent = $dsr == 'true';

		$asset = $this->CollibraAPI->getRequestDetails($assetId, $parent);
		$netID = $this->Auth->user('username');

		$guest = true;
		foreach($asset->attributes as $attr) {
			if ($attr->attrSignifier == 'Requester Net Id') {
				if ($attr->attrValue == $netID) {
					$guest = false;
					break;
				}
			}
		}

		$completedStatuses = ['Completed', 'Approved', 'Obsolete'];
		if (in_array($asset->statusName, $completedStatuses)) {
			$this->Flash->error('You cannot edit a completed Request.');
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}

		if ($parent && !empty($asset->dsas)) {
			$this->Flash->error('You cannot edit a DSR with any associated DSAs.');
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}

		// load form fields for ISA workflow
		$formResp = $this->CollibraAPI->get('workflow/'.Configure::read('Collibra.workflow.intakeDSR').'/form/start');
		$formResp = json_decode($formResp);

		$arrNewAttr = [];
		foreach($formResp->formProperties as $wf){
			foreach($asset->attributes as $attr){
				if($attr->attrSignifier == $wf->name){
					$arrNewAttr[$attr->attrSignifier] = $attr;
					break;
				}
			}
		}
		$asset->attributes = $arrNewAttr;

		$this->set('guest', $guest);
		$this->set('formFields', $formResp);
		$this->set('asset', $asset);
		$this->set('parent', $parent);
		$this->set('submitErr', isset($this->request->query['err']));
	}

	public function delete($dsrId) {
		$this->autoRender = false;

		if (empty($dsrId)) {
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}

		//Load DSR to check that the request isn't completed
		$request = $this->CollibraAPI->getRequestDetails($dsrId);

		$completedStatuses = ['Completed', 'Approved', 'Obsolete'];
		if (in_array($request->statusName, $completedStatuses)) {
			$this->Flash->error('You cannot delete a completed Request.');
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}

		foreach ($request->dsas as $dsa) {
			if (in_array($dsa->dsaStatus, $completedStatuses)) {
				$this->Flash->error('You cannot delete a Request if any associated DSAs are completed.');
				$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
			}
		}

		$postData['status'] = Configure::read('Collibra.status.deleted');
		$postString = http_build_query($postData);
		foreach ($request->dsas as $dsa) {
			$this->CollibraAPI->post("term/{$dsa->dsaId}/status", $postString);
		}
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

		$requiredElementsString = Configure::read('Collibra.requiredElementsString');
		$additionalElementsString = Configure::read('Collibra.additionalElementsString');
		$postData[$requiredElementsString] = !empty($businessTermIds) ? $businessTermIds : '';
		$postData['api'] = [];
		$postData['tables'] = [];
		if (!empty($additionalElementsString)) {
			$postData[$additionalElementsString] = [];
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
				array_push($postData['tables'], $tableObject->id);
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
		if (empty($postData['tables'])) {
			$postData['tables'] = '';
		}

		//For array data, PHP's http_build_query creates query/POST string in a format Collibra doesn't like,
		//so we have to tweak the output a bit
		$postString = http_build_query($postData);
		$postString = preg_replace("/%5B[0-9]*%5D/", "", $postString);
		$postString = preg_replace('/%0D%0A/','<br/>',$postString);

		$formResp = $this->CollibraAPI->post(
			'workflow/'.Configure::read('Collibra.workflow.intakeDSR').'/start',
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

	public function view($assetId, $dsr = 'true') {
		if (empty($assetId)) {
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}
		$parent = $dsr == 'true';
		$expand = '';
		if (isset($this->request->query['expand'])) {
			$expand = $assetId;
		}

		$netID = $this->Auth->user('username');
		$asset = $this->CollibraAPI->getRequestDetails($assetId, $parent);
		$asset->roles = $this->CollibraAPI->getResponsibilities($asset->vocabularyId);
		$resp = $this->CollibraAPI->get('term/'.$assetId.'/attachments');
		$resp = json_decode($resp);
		$asset->attachments = $resp->attachment;

		$asset->termGlossaries = [];
		foreach ($asset->requestedTerms as $term) {
			if (array_key_exists($term->reqTermVocabName, $asset->termGlossaries)) {
				array_push($asset->termGlossaries[$term->reqTermVocabName], $term);
			} else {
				$asset->termGlossaries[$term->reqTermVocabName] = [$term];
			}
		}

		if (!empty($asset->additionallyIncludedTerms)) {
			$asset->addTermGlossaries = [];
			foreach ($asset->additionallyIncludedTerms as $term) {
				if (array_key_exists($term->addTermVocabName, $asset->addTermGlossaries)) {
					array_push($asset->addTermGlossaries[$term->addTermVocabName], $term);
				} else {
					$asset->addTermGlossaries[$term->addTermVocabName] = [$term];
				}
			}
		}

		$arrNewAttr = [];
		$arrCollaborators = [];
		foreach($asset->attributes as $attr){
			if ($attr->attrSignifier == 'Requester Net Id') {
				$person = $this->BYUAPI->personalSummary($attr->attrValue);
				unset($person->person_summary_line, $person->personal_information, $person->student_information, $person->relationships);
				array_push($arrCollaborators, $person);
				continue;
			}
			$arrNewAttr[$attr->attrSignifier] = $attr;
		}
		$arrNewAttr['Collaborators'] = $arrCollaborators;
		$asset->attributes = $arrNewAttr;

		// Making edits in Collibra inserts weird html into the attributes; if an
		// edit was made in Collibra, we replace their html with some more cooperative tags
		$arrChangedAttrIds = [];
		$arrChangedAttrValues = [];
		foreach($asset->attributes as $label => $attr) {
			if ($label == 'Collaborators' || $label == 'Request Date') continue;
			if (preg_match('/<div>/', $attr->attrValue)) {
				array_push($arrChangedAttrIds, $attr->attrResourceId);
				$newValue = preg_replace(['/<div><br\/>/', '/<\/div>/', '/<div>/'], ['<br/>', '', '<br/>'], $attr->attrValue);
				array_push($arrChangedAttrValues, $newValue.'  ');

				// After updating the value in Collibra, just replace the value for this page load
				$attr->attrValue = $newValue;
			}
		}

		if ($parent) {
			for ($i = 0; $i < sizeof($asset->dsas); $i++) {
				$asset->dsas[$i]->attributes = $this->CollibraAPI->getAttributes($asset->dsas[$i]->dsaId);

				foreach($asset->dsas[$i]->attributes as $attr) {
					if (preg_match('/<div>/', $attr->attrValue)) {
						array_push($arrChangedAttrIds, $attr->attrResourceId);
						$newValue = preg_replace(['/<div><br\/>/', '/<\/div>/', '/<div>/'], ['<br/>', '', '<br/>'], $attr->attrValue);
						array_push($arrChangedAttrValues, $newValue.'  ');

						// After updating the value in Collibra, just replace the value for this page load
						$attr->attrValue = $newValue;
					}
				}

				$asset->dsas[$i]->roles = $this->CollibraAPI->getResponsibilities($asset->dsas[$i]->dsaVocabularyId);
				$resp = $this->CollibraAPI->get('term/'.$asset->dsas[$i]->dsaId.'/attachments');
				$resp = json_decode($resp);
				$asset->dsas[$i]->attachments = $resp->attachment;
			}
		}

		if (!empty($arrChangedAttrIds)) {
			// Here update all the attributes Collibra inserted HTML tags into
			$postData['attributes'] = $arrChangedAttrIds;
			$postData['values'] = $arrChangedAttrValues;
			$postString = http_build_query($postData);
			$postString = preg_replace("/%5B[0-9]*%5D/", "", $postString);
			$resp = $this->CollibraAPI->post('workflow/'.Configure::read('Collibra.workflow.changeAttributes').'/start', $postString);
		}

		$this->set('netID', $netID);
		$this->set('asset', $asset);
		$this->set('parent', $parent);
		$this->set('expand', $expand);
	}

	public function printView($assetId, $dsr = 'true') {
		if (empty($assetId)) {
			$this->redirect(['controller' => 'myaccount', 'action' => 'index']);
		}
		$this->autoLayout = false;
		$parent = $dsr == 'true';

		$asset = $this->CollibraAPI->getRequestDetails($assetId, $parent);
		$asset->roles = $this->CollibraAPI->getResponsibilities($asset->vocabularyId);

		$asset->termGlossaries = [];
		foreach ($asset->requestedTerms as $term) {
			if (array_key_exists($term->reqTermVocabName, $asset->termGlossaries)) {
				array_push($asset->termGlossaries[$term->reqTermVocabName], $term);
			} else {
				$asset->termGlossaries[$term->reqTermVocabName] = [$term];
			}
		}

		if (!empty($asset->additionallyIncludedTerms)) {
			$asset->addTermGlossaries = [];
			foreach ($asset->additionallyIncludedTerms as $term) {
				if (array_key_exists($term->addTermVocabName, $asset->addTermGlossaries)) {
					array_push($asset->addTermGlossaries[$term->addTermVocabName], $term);
				} else {
					$asset->addTermGlossaries[$term->addTermVocabName] = [$term];
				}
			}
		}

		// sort request attribute data based on workflow form field order
		$workflowResp = $this->CollibraAPI->get('workflow/'.Configure::read('Collibra.workflow.intakeDSR').'/form/start');
		$workflowResp = json_decode($workflowResp);

		$arrNewAttr = [];
		foreach ($workflowResp->formProperties as $wf) {
			foreach ($asset->attributes as $attr) {
				if ($attr->attrSignifier == $wf->name) {
					$arrNewAttr[$attr->attrSignifier] = $attr;
					break;
				}
			}
		}
		$asset->attributes = $arrNewAttr;

		if ($parent) {
			for ($i = 0; $i < sizeof($asset->dsas); $i++) {
				$asset->dsas[$i]->attributes = $this->CollibraAPI->getAttributes($asset->dsas[$i]->dsaId);
				$asset->dsas[$i]->roles = $this->CollibraAPI->getResponsibilities($asset->dsas[$i]->dsaVocabularyId);
			}
		}

		$this->set('asset', $asset);
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

			$draft = $this->CollibraAPI->getRequestDetails($draftId[0]->id);

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
			foreach ($draft->attributes as $attr) {
				if ($attr->attrSignifier == 'Additional Information Requested') {
					continue;
				}
				$label = $arrLabelMatch[$attr->attrSignifier];
				$preFilled[$label] = $attr->attrValue;
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
		$formResp = $this->CollibraAPI->get('workflow/'.Configure::read('Collibra.workflow.intakeDSR').'/form/start');
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
