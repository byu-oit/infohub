<?php

require 'fpdf' . DIRECTORY_SEPARATOR . 'fpdf.php';

class RequestController extends AppController {
	public $helpers = ['Html', 'Form'];
	public $uses = ['CollibraAPI', 'BYUAPI'];
	public $components = ['Collibra'];

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

	private function isLegacy($asset, $parent = true) {
		// The DSR form fields were changed on 2018/06/22; this asset's creation
		// date relative to that date indicates which fields we have to work with
		if ($parent) {
			return intval($asset->attributes['Request Date']->attrValue) < 1529625600000;
		} else {
			$parentDSR = $this->CollibraAPI->getRequestDetails($asset->parentId);
			return intval($parentDSR->attributes['Request Date']->attrValue) < 1529625600000;
		}
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

		$effectiveDate = date('n/j/Y', $dsa->attributes['Effective Start Date']->attrValue/1000);
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
			"Application or Project Name",
			"Description of Intended Use",
			"Access Rights",
			"Access Method",
			"Impact on System",
			"Application Identity",
			"Additional Information Requested",
			"Description of Application or Project",
			"Necessity of Data",
			"Scope and Control",
			"Requested Information Map",
			"Data Steward Response"
		];
		foreach ($arrOrderedFormFields as $field) {
			if (!empty($dsa->attributes[$field])) {
				$pdf->SetFont('','B',12);
				$pdf->Cell(0,10,$field,'B',1);
				$pdf->Ln(2);
				$pdf->SetFont('','',9);

				$attrText = $dsa->attributes[$field]->attrValue;
				$attrText = preg_replace('/<br[^\/,>]*\/?>/',"\n",$attrText);
				$attrText = preg_replace(['/<li[^>]*>/','/<\/li>/'],[" ".chr(127)." ",""],$attrText);
				$attrText = preg_replace(['/<ul[^>]*>/','/<\/ul>/'],["\n",""],$attrText);
				$attrText = preg_replace('/<[^><]*>/','',$attrText);
				$attrText = str_replace(chr(194),'',$attrText);
				$attrText = preg_replace('/^\s/','',$attrText);

				$pdf->MultiCell(0,4,$attrText,0,'J');
				$pdf->Ln(4);
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
		$pdf->Cell(($w / 2),4,$dsa->attributes['Requester Name']->attrValue);
		$pdf->Cell(($w / 2),4,$dsa->attributes['Sponsor Name']->attrValue,0,1);
		$pdf->Cell(($w / 2),4,$dsa->attributes['Requester Role']->attrValue.' | '.$dsa->attributes['Requesting Organization']->attrValue);
		$pdf->Cell(($w / 2),4,$dsa->attributes['Sponsor Role']->attrValue,0,1);
		$pdf->Cell(($w / 2),4,$dsa->attributes['Requester Email']->attrValue);
		$pdf->Cell(($w / 2),4,$dsa->attributes['Sponsor Email']->attrValue,0,1);
		$pdf->Cell(($w / 2),4,$dsa->attributes['Requester Phone']->attrValue);
		$pdf->Cell(($w / 2),4,$dsa->attributes['Sponsor Phone']->attrValue,0,1);

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
				$this->updateDraftCart();
				echo $newTermsAdded;
			} else {
				$newTermsAdded = 0;
				$arrTerms = empty($this->request->data['t']) ? null : $this->request->data['t'];
				$arrFields = empty($this->request->data['f']) ? null : $this->request->data['f'];
				$arrFieldIds = empty($this->request->data['fi']) ? null : $this->request->data['fi'];
				$arrColumns = empty($this->request->data['c']) ? null : $this->request->data['c'];
				$arrColumnIds = empty($this->request->data['ci']) ? null : $this->request->data['ci'];
				$arrSamlFields = empty($this->request->data['s']) ? null : $this->request->data['s'];
				$arrSamlFieldIds = empty($this->request->data['si']) ? null : $this->request->data['si'];
				$apiHost = empty($this->request->data['apiHost']) ? null : $this->request->data['apiHost'];
				$apiPath = empty($this->request->data['apiPath']) ? null : $this->request->data['apiPath'];
				$authorizedByFieldset = empty($this->request->data['afs']) ? null : $this->request->data['afs'];
				$databaseName = empty($this->request->data['databaseName']) ? null : $this->request->data['databaseName'];
				$schemaName = empty($this->request->data['schemaName']) ? null : $this->request->data['schemaName'];
				$tableName = empty($this->request->data['tableName']) ? null : $this->request->data['tableName'];
				$responseName = empty($this->request->data['responseName']) ? null : $this->request->data['responseName'];

				$arrQueue = $this->Session->read('queue');

				if (isset($arrFieldIds)) {
					foreach ($arrFieldIds as $index => $fieldId) {
						if (empty($arrQueue['apiFields'][$fieldId])) {
							$newTermsAdded++;
							$arrQueue['apiFields'][$fieldId] = ['name' => end((explode(".", $arrFields[$index]))), 'fullName' => $arrFields[$index], 'apiHost' => $apiHost, 'apiPath' => $apiPath, 'authorizedByFieldset' => $authorizedByFieldset];
						}
					}
				}
				if (isset($arrColumnIds)) {
					foreach ($arrColumnIds as $index => $columnId) {
						if (empty($arrQueue['dbColumns'][$columnId])) {
							$newTermsAdded++;
							$arrQueue['dbColumns'][$columnId] = ['name' => end((explode(" > ", $arrColumns[$index]))), 'fullName' => $arrColumns[$index], 'databaseName' => $databaseName, 'schemaName' => $schemaName, 'tableName' => $tableName];
						}
					}
				}
				if (isset($arrSamlFieldIds)) {
					foreach ($arrSamlFieldIds as $index => $fieldId) {
						if (empty($arrQueue['samlFields'][$fieldId])) {
							$newTermsAdded++;
							$arrQueue['samlFields'][$fieldId] = ['name' => $arrSamlFields[$index], 'responseName' => $responseName];
						}
					}
				}
				if (isset($arrTerms)) {
					$arrTermIds = [];
					foreach ($arrTerms as $term) {
						$arrTermIds[$term['id']] = 'ignore';
					}
					$arrTermDetails = $this->CollibraAPI->getBusinessTermDetails($arrTermIds);

					foreach ($arrTermDetails as $term) {
						if (empty($arrQueue['businessTerms'][$term->termrid])) {
							$requestable = true;
							if (!Configure::read('allowUnapprovedTerms')) {
								$requestable = $term->statusname == 'Accepted';
							}

							if ($requestable) {
								$newTermsAdded++;
								$arrQueue['businessTerms'][$term->termrid] = ['term' => $term->termsignifier, 'communityId' => $term->commrid];
							}
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
			if (array_key_exists($termID, $arrQueue['apiFields'])) {
				unset($arrQueue['apiFields'][$termID]);
			} else if (array_key_exists($termID, $arrQueue['dbColumns'])) {
				unset($arrQueue['dbColumns'][$termID]);
			} else if (array_key_exists($termID, $arrQueue['samlFields'])) {
				unset($arrQueue['samlFields'][$termID]);
			} else if (array_key_exists($termID, $arrQueue['businessTerms'])) {
				unset($arrQueue['businessTerms'][$termID]);
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
		$arrQueue['apiFields'] = [];
		$arrQueue['dbColumns'] = [];
		$arrQueue['samlFields'] = [];
		$arrQueue['businessTerms'] = [];
		$arrQueue['emptyApis'] = [];
		$this->Session->write('queue', $arrQueue);
		$this->updateDraftCart();
	}

	public function getQueueSize() {
		$this->autoRender = false;

		$arrQueue = $this->Session->read('queue');
		echo  count($arrQueue['apiFields']) +
			  count($arrQueue['dbColumns']) +
			  count($arrQueue['samlFields']) +
			  count($arrQueue['businessTerms']) +
			  count($arrQueue['emptyApis']);
	}

	public function cartDropdown() {
		$this->autoRender = false;
		$responseHTML = '';

		$arrQueue = $this->Session->read('queue');
		$responseHTML = '<h3>Requested Items: '.(count($arrQueue['apiFields']) + count($arrQueue['dbColumns']) + count($arrQueue['samlFields']) + count($arrQueue['businessTerms']) + count($arrQueue['emptyApis'])).'</h3>'.
			'<a class="close" href="javascript: hideRequestQueue()">X</a>'.
			'<div class="arrow"></div>'.
			'<a class="clearQueue" href="javascript: clearRequestQueue()">Empty cart</a>';

		if(
			!empty($arrQueue['apiFields']) ||
			!empty($arrQueue['dbColumns']) ||
			!empty($arrQueue['samlFields']) ||
			!empty($arrQueue['businessTerms']) ||
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

		foreach($request->collaborators as $collaborator) {
			if ($collaborator->identifiers->net_id == $person->identifiers->net_id) {
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
			list($dsa->attributes, $dsa->collaborators) = $this->CollibraAPI->getAttributes($dsa->dsaId);
			foreach ($dsa->collaborators as $collaborator) {
				if ($collaborator->identifiers->net_id == $netId) {
					array_push($toDeleteIds, $collaborator->attrInfo->attrResourceId);
					break;
				}
			}
		}

		foreach ($request->collaborators as $collaborator) {
			if ($collaborator->identifiers->net_id == $netId) {
				array_push($toDeleteIds, $collaborator->attrInfo->attrResourceId);
				break;
			}
		}

		$resp = $this->CollibraAPI->deleteJSON('attribute', $this->Collibra->preparePostData(['resource' => $toDeleteIds]));
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
		$oldDraft = $this->CollibraAPI->checkForDSRDraft($netId);
		if (!empty($oldDraft)) {
			$this->CollibraAPI->post('term/'.$oldDraft[0]->id.'/signifier', 'signifier=_DRAFT-'.$netId);
		}

		$postData['attributeIds'] = [];
		$postData['values'] = [];
		$arrQueue = $this->Session->read('queue');
		array_push($postData['attributeIds'], Configure::read('Collibra.formFields.draftUserCart'));
		array_push($postData['values'], json_encode($arrQueue).'  ');

		foreach ($this->request->data as $label => $value) {
			if (empty($value)) continue;
			array_push($postData['attributeIds'], Configure::read("Collibra.formFields.{$label}"));
			array_push($postData['values'], $value.'  ');
		}

		$success = true;
		$resp = $this->CollibraAPI->post(
			'workflow/'.Configure::read('Collibra.workflow.createDSRDraft').'/start',
			rtrim($this->Collibra->preparePostData($postData), '+'));
		if ($resp->code != '200') $success = false;

		if (!$success) {
			if (!empty($oldDraft)) {
				$this->CollibraAPI->post('term/'.$oldDraft[0]->id.'/signifier', 'signifier=DRAFT-'.$netId);
			}

			return json_encode(['success' => 0, 'message' => 'Error saving draft']);
		}

		if (!empty($oldDraft)) {
			$this->CollibraAPI->delete('term/'.$oldDraft[0]->id);
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
		$attrId = $draft->attributes['Draft User Cart']->attrResourceId;

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

		$success = true;
		if ($this->request->data['action'] == 'add') {
			$arrQueue = $this->Session->read('queue');

			$request = $this->CollibraAPI->getRequestDetails($this->request->data['dsrId']);

			$addedApis = [];
			$addedFieldsetApis = [];
			$addedTables = [];
			$addedSamlResponses = [];
			$additionString = "<br/><br/>Addition, ".date('Y-m-d').":";

			$toDeleteIds = [];
			$relationsPostData['dsrId'] = $this->request->data['dsrId'];
			$relationsPostData['requestedTerms'] = [];
			$relationsPostData['additionalTerms'] = [];
			$relationsPostData['requestedDataAssets'] = [];
			$relationsPostData['additionalDataAssets'] = [];
			$relationsPostData['apis'] = [];
			$relationsPostData['tables'] = [];
			$relationsPostData['saml'] = [];
			$relationsPostData['policies'] = [];

			if (isset($this->request->data['arrApiFields'])) {
				foreach ($this->request->data['arrApiFields'] as $field) {
					$additionString .= "<br/>".$arrQueue['apiFields'][$field['id']]['fullName'].", from API: ".$field['apiPath'];
					if ($arrQueue['apiFields'][$field['id']]['authorizedByFieldset'] == 'true') {
						if (strpos($field['fullName'], '.') === false) {		// is fieldset
		   					 $addedFieldsetApis[$field['apiHost']][$field['apiPath']][$field['fullName']] = [];
		   				 } else {												// is field
		   					 $addedFieldsetApis[$field['apiHost']][$field['apiPath']][substr($field['fullName'],0,strpos($field['fullName'],'.'))] = [];
		   				 }
					} else {
						$addedApis[$field['apiHost']][$field['apiPath']] = [];
					}
					unset($arrQueue['apiFields'][$field['id']]);
				}
			}
			if (isset($this->request->data['arrDbColumns'])) {
				foreach ($this->request->data['arrDbColumns'] as $column) {
					$addedTables[$column['databaseName'].' > '.$column['tableName']] = [];
					$additionString .= "<br/>{$arrQueue['dbColumns'][$column['id']]['fullName']}";
					unset($arrQueue['dbColumns'][$column['id']]);
				}
			}
			if (isset($this->request->data['arrSamlFields'])) {
				foreach ($this->request->data['arrSamlFields'] as $field) {
					$addedSamlResponses[$field['responseName']] = [];
					$additionString .= "<br/>{$arrQueue['samlFields'][$field['id']]['name']}";
					unset($arrQueue['samlFields'][$field['id']]);
				}
			}
			if (isset($this->request->data['arrApis'])) {
				foreach ($this->request->data['arrApis'] as $api) {
					$addedApis[$arrQueue['emptyApis'][$api]['apiHost']][$api] = [];
					$additionString .= "<br/>".$api." [No specified output fields]";
					unset($arrQueue['emptyApis'][$api]);
				}
			}
			if (isset($this->request->data['arrBusinessTerms'])) {
				foreach ($this->request->data['arrBusinessTerms'] as $termid) {
					array_push($relationsPostData['requestedTerms'], $termid);
					$additionString .= "<br/>{$arrQueue['businessTerms'][$termid]['term']} (Business Term)";
					unset($arrQueue['businessTerms'][$termid]);

					foreach ($request->additionallyIncludedTerms as $term) {
						if ($term->addTermId == $termid) {
							array_push($toDeleteIds, $term->addTermRelationId);
						}
					}
				}
			}

			foreach ($request->additionallyIncludedDataAssets as $dataAsset) {
				if ((isset($this->request->data['arrApiFields']) && array_search($dataAsset->addDataId, array_column($this->request->data['arrApiFields'], 'id')) !== false) ||
					(isset($this->request->data['arrDbColumns']) && array_search($dataAsset->addDataId, array_column($this->request->data['arrDbColumns'], 'id')) !== false) ||
					(isset($this->request->data['arrSamlFields']) && array_search($dataAsset->addDataId, array_column($this->request->data['arrSamlFields'], 'id')) !== false)) {

					array_push($toDeleteIds, $dataAsset->addDataRelationId);
					array_push($relationsPostData['requestedDataAssets'], $dataAsset->addDataId);

					if (!empty($dataAsset->addDataBusinessTermId)) {
						foreach ($request->additionallyIncludedTerms as $term) {
							if ($term->addTermId == $dataAsset->addDataBusinessTermId) {
								array_push($toDeleteIds, $term->addTermRelationId);
							}
						}
						array_push($relationsPostData['requestedTerms'], $dataAsset->addDataBusinessTermId);
					}
		 		}
		 	}

			foreach ($request->requestedDataAssets as $alreadyListed) {
				if (array_column($request->necessaryApis, 'apiAuthorizedByFieldset', 'apiName')[$alreadyListed->reqDataVocabName] == 'true') {
					$fieldset = substr($alreadyListed->reqDataSignifier,0,strpos($alreadyListed->reqDataSignifier,'.'));
					if (array_key_exists($fieldset, $addedFieldsetApis[$alreadyListed->reqDataCommName][substr($alreadyListed->reqDataVocabName, 1)])) {
						unset($addedFieldsetApis[$alreadyListed->reqDataCommName][substr($alreadyListed->reqDataVocabName, 1)][$fieldset]);
					}
					if (empty($addedFieldsetApis[$alreadyListed->reqDataCommName][substr($alreadyListed->reqDataVocabName, 1)])) {
						unset($addedFieldsetApis[$alreadyListed->reqDataCommName][substr($alreadyListed->reqDataVocabName, 1)]);
					}
					if (empty($addedFieldsetApis[$alreadyListed->reqDataCommName])) {
						unset($addedFieldsetApis[$alreadyListed->reqDataCommName]);
					}
				}
				if (empty($addedApis[$alreadyListed->reqDataCommName][substr($alreadyListed->reqDataVocabName, 1)])) {
					unset($addedApis[$alreadyListed->reqDataCommName][substr($alreadyListed->reqDataVocabName, 1)]);
				}
				if (empty($addedApis[$alreadyListed->reqDataCommName])) {
					unset($addedApis[$alreadyListed->reqDataCommName]);
				}
			}
			foreach ($request->necessaryTables as $alreadyListed) {
				unset($addedTables[$alreadyListed->tableCommName.' > '.$alreadyListed->tableName]);
			}
			foreach ($request->necessarySamlResponses as $alreadyListed) {
				unset($addedSamlResponses[$alreadyListed->responseName]);
			}

			$requestedTerms = [];
			$additionalTerms = [];
			foreach ($addedApis as $apiHost => $apiPaths) {
				foreach ($apiPaths as $apiPath => $_) {
					$apiObject = $this->CollibraAPI->getApiObject($apiHost, $apiPath);
					array_push($relationsPostData['apis'], $apiObject->id);

					$apiFields = $this->CollibraAPI->getApiFields($apiHost, $apiPath);
					foreach ($apiFields as $apiField) {
						if (!empty($apiField->assetType) && strtolower($apiField->assetType) == 'fieldset') {
							continue;
						}
						$requestedField = false;
						if (isset($this->request->data['arrApiFields'])) {
							foreach ($this->request->data['arrApiFields'] as $field) {
								if ($apiField->id == $field['id']) {
									$requestedField = true;
									break;
								}
							}
						}
						if (empty($apiField->businessTerm[0]->termId)) {
							if ($requestedField) {
								$addedApis[$apiHost][$apiPath]['unmapped']['requested'][] = $apiField->name;
								array_push($relationsPostData['requestedDataAssets'], $apiField->id);
							} else {
								$addedApis[$apiHost][$apiPath]['unmapped']['unrequested'][] = $apiField->name;
								array_push($relationsPostData['additionalDataAssets'], $apiField->id);
							}
						} else {
							if ($requestedField) {
								$addedApis[$apiHost][$apiPath]['requestedBusinessTerm'][] = '('.$apiField->businessTerm[0]->termCommunityName.') '.$apiField->businessTerm[0]->term;
								array_push($relationsPostData['requestedDataAssets'], $apiField->id);
								array_push($requestedTerms, $apiField->businessTerm[0]);
							} else {
								$addedApis[$apiHost][$apiPath]['unrequested'][] = '('.$apiField->businessTerm[0]->termCommunityName.') '.$apiField->businessTerm[0]->term;
								array_push($relationsPostData['additionalDataAssets'], $apiField->id);
								array_push($additionalTerms, $apiField->businessTerm[0]);
							}
						}
					}
				}
			}
			foreach ($addedFieldsetApis as $apiHost => $apiPaths) {
				foreach ($apiPaths as $apiPath => $fieldsets) {
					$apiObject = $this->CollibraAPI->getApiObject($apiHost, $apiPath);
					$related = false;
					foreach ($request->necessaryApis as $alreadyRequested) {
						if ($apiObject->id == $alreadyRequested->apiId) {
							$related = true;
							break;
						}
					}
					if (!$related) array_push($relationsPostData['apis'], $apiObject->id);

					$apiFields = $this->CollibraAPI->getApiFields($apiHost, $apiPath, true);
					foreach ($fieldsets as $fieldset => $_) {
						foreach ($apiFields[$fieldset]->descendantFields as $apiField) {
							$requestedField = false;
							if (isset($this->request->data['arrApiFields'])) {
								foreach ($this->request->data['arrApiFields'] as $field) {
									if ($apiField->id == $field['id']) {
										$requestedField = true;
										break;
									}
								}
							}
							if (empty($apiField->businessTerm[0]->termId)) {
								if ($requestedField) {
									$addedFieldsetApis[$apiHost][$apiPath][$fieldset]['unmapped']['requested'][] = $apiField->name;
									array_push($relationsPostData['requestedDataAssets'], $apiField->id);
								} else {
									$addedFieldsetApis[$apiHost][$apiPath][$fieldset]['unmapped']['unrequested'][] = $apiField->name;
									array_push($relationsPostData['additionalDataAssets'], $apiField->id);
								}
							} else {
								if ($requestedField) {
									$addedFieldsetApis[$apiHost][$apiPath][$fieldset]['requestedBusinessTerm'][] = '('.$apiField->businessTerm[0]->termCommunityName.') '.$apiField->businessTerm[0]->term;
									array_push($relationsPostData['requestedDataAssets'], $apiField->id);
									array_push($requestedTerms, $apiField->businessTerm[0]);
								} else {
									$addedFieldsetApis[$apiHost][$apiPath][$fieldset]['unrequested'][] = '('.$apiField->businessTerm[0]->termCommunityName.') '.$apiField->businessTerm[0]->term;
									array_push($relationsPostData['additionalDataAssets'], $apiField->id);
									array_push($additionalTerms, $apiField->businessTerm[0]);
								}
							}
						}
					}
				}
			}
			foreach ($addedTables as $tableName => $_) {
				$databaseName = substr($tableName, 0, strpos($tableName, '>') - 1);
				$schemaAndTableNameOnly = substr($tableName, strpos($tableName, '>') + 2);
				$table = $this->CollibraAPI->getTableObject($databaseName, $schemaAndTableNameOnly);
				array_push($relationsPostData['tables'], $table->id);

				$columns = $this->CollibraAPI->getTableColumns($databaseName, $schemaAndTableNameOnly);
				foreach ($columns as $column) {
					$requestedColumn = false;
					if (isset($this->request->data['arrDbColumns'])) {
						foreach ($this->request->data['arrDbColumns'] as $requested) {
							if ($column->columnId == $requested['id']) {
								$requestedColumn = true;
								break;
							}
						}
					}
					if (empty($column->businessTerm[0]->termId)) {
						if ($requestedColumn) {
							$addedTables[$tableName]['unmapped']['requested'][] = $column->columnName;
							array_push($relationsPostData['requestedDataAssets'], $column->columnId);
						} else {
							$addedTables[$tableName]['unmapped']['unrequested'][] = $column->columnName;
							array_push($relationsPostData['additionalDataAssets'], $column->columnId);
						}
					} else {
						if ($requestedColumn) {
							$addedTables[$tableName]['requestedBusinessTerm'][] = '('.$column->businessTerm[0]->termCommunityName.') '.$column->businessTerm[0]->term;
							array_push($relationsPostData['requestedDataAssets'], $column->columnId);
							array_push($requestedTerms, $column->businessTerm[0]);
						} else {
							$addedTables[$tableName]['unrequested'][] = '('.$column->businessTerm[0]->termCommunityName.') '.$column->businessTerm[0]->term;
							array_push($relationsPostData['additionalDataAssets'], $column->columnId);
							array_push($additionalTerms, $column->businessTerm[0]);
						}
					}
				}
			}
			foreach ($addedSamlResponses as $responseName => $_) {
				$response = $this->CollibraAPI->getSamlResponseObject($responseName);
				array_push($relationsPostData['saml'], $response->id);

				$fields = $this->CollibraAPI->getSamlResponseFields($responseName);
				foreach ($fields as $field) {
					$requestedField = false;
					if (isset($this->request->data['arrSamlFields'])) {
						foreach ($this->request->data['arrSamlFields'] as $requested) {
							if ($field->fieldId == $requested['id']) {
								$requestedField = true;
								break;
							}
						}
					}
					if (empty($field->businessTerm[0]->termId)) {
						if ($requestedField) {
							$addedSamlResponses[$responseName]['unmapped']['requested'][] = $field->fieldName;
							array_push($relationsPostData['requestedDataAssets'], $field->fieldId);
						} else {
							$addedSamlResponses[$responseName]['unmapped']['unrequested'][] = $field->fieldName;
						}
					} else {
						if ($requestedField) {
							$addedSamlResponses[$responseName]['requestedBusinessTerm'][] = '('.$field->businessTerm[0]->termCommunityName.') '.$field->businessTerm[0]->term;
							array_push($relationsPostData['requestedDataAssets'], $field->fieldId);
							array_push($requestedTerms, $field->businessTerm[0]);
						} else {
							$addedSamlResponses[$responseName]['unrequested'][] = '('.$field->businessTerm[0]->termCommunityName.') '.$field->businessTerm[0]->term;
						}
					}
				}
			}

			$legacy = $this->isLegacy($request);
			if (!empty($addedApis)) {
				if (!$legacy && strpos($request->attributes['Technology Type']->attrValue, 'API') === false) {
					$attr = $request->attributes['Technology Type'];
					$newValues = array_merge(explode(';', $attr->attrValue), ['API']);
					$resp = $this->CollibraAPI->post('attribute/'.$attr->attrResourceId, $this->Collibra->preparePostData(['value' => $newValues]));
					if ($resp->code != '200') $success = false;
				}

				$additionString .= "<br/><br/><b>Newly Requested APIs:</b><br/>";
				foreach ($addedApis as $apiHost => $apiPaths) {
					foreach ($apiPaths as $apiPath => $term) {
						$additionString .= ". . <u><b>{$apiHost}/{$apiPath}</u></b><br/>";
						if (!empty($term['requestedBusinessTerm'])) {
							$term['requestedBusinessTerm'] = array_unique($term['requestedBusinessTerm']);
							sort($term['requestedBusinessTerm']);
							$additionString .= "<br/>. . . . <b>Requested business terms:</b><br/>. . . . . . " . implode("<br/>. . . . . . ", $term['requestedBusinessTerm']) . "<br/>";
						}
						if (!empty($term['unrequested'])) {
							$term['unrequested'] = array_unique(array_diff($term['unrequested'], $term['requestedBusinessTerm']));
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

			if (!empty($addedFieldsetApis)) {
				if (!$legacy && strpos($request->attributes['Technology Type']->attrValue, 'API') === false) {
					$attr = $request->attributes['Technology Type'];
					$newValues = array_merge(explode(';', $attr->attrValue), ['API']);
					$resp = $this->CollibraAPI->post('attribute/'.$attr->attrResourceId, $this->Collibra->preparePostData(['value' => $newValues]));
					if ($resp->code != '200') $success = false;
				}

				$additionString .= "<br/><br/><b>Newly Requested University API Fieldsets:</b><br/>";
				foreach ($addedFieldsetApis as $apiHost => $apiPaths) {
					foreach ($apiPaths as $apiPath => $fieldsets) {
						foreach ($fieldsets as $fieldset => $term) {
							$additionString .= ". . <u><b>{$apiHost}/{$apiPath}, {$fieldset}</u></b><br/>";
							if (!empty($term['requestedBusinessTerm'])) {
								$term['requestedBusinessTerm'] = array_unique($term['requestedBusinessTerm']);
								sort($term['requestedBusinessTerm']);
								$additionString .= "<br/>. . . . <b>Requested business terms:</b><br/>. . . . . . " . implode("<br/>. . . . . . ", $term['requestedBusinessTerm']) . "<br/>";
							}
							if (!empty($term['unrequested'])) {
								$term['unrequested'] = array_unique(array_diff($term['unrequested'], $term['requestedBusinessTerm']));
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
			}

			if (!empty($addedTables)) {
				if (!$legacy && strpos($request->attributes['Technology Type']->attrValue, 'Data Warehouse') === false) {
					$attr = $request->attributes['Technology Type'];
					$newValues = array_merge(explode(';', $attr->attrValue), ['Data Warehouse']);
					$resp = $this->CollibraAPI->post('attribute/'.$attr->attrResourceId, $this->Collibra->preparePostData(['value' => $newValues]));
					if ($resp->code != '200') $success = false;
				}

				$additionString .= "<br/><br/><b>Newly Requested Database Tables:</b><br/>";
				foreach ($addedTables as $tableName => $table) {
					$additionString .= ". . <u><b>{$tableName}</u></b><br/>";
					if (!empty($table['requestedBusinessTerm'])) {
						$table['requestedBusinessTerm'] = array_unique($table['requestedBusinessTerm']);
						sort($table['requestedBusinessTerm']);
						$additionString .= "<br/>. . . . <b>Requested business terms:</b><br/>. . . . . . " . implode("<br/>. . . . . . ", $table['requestedBusinessTerm']) . "<br/>";
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

			if (!empty($addedSamlResponses)) {
				if (!$legacy && strpos($request->attributes['Technology Type']->attrValue, 'SAML') === false) {
					$attr = $request->attributes['Technology Type'];
					$newValues = array_merge(explode(';', $attr->attrValue), ['SAML']);
					$resp = $this->CollibraAPI->post('attribute/'.$attr->attrResourceId, $this->Collibra->preparePostData(['value' => $newValues]));
					if ($resp->code != '200') $success = false;
				}

				$additionString .= "<br/><br/><b>Newly Requested SAML Responses:</b><br/>";
				foreach ($addedSamlResponses as $responseName => $response) {
					$additionString .= ". . <u><b>{$responseName}</u></b><br/>";
					if (!empty($response['requestedBusinessTerm'])) {
						$response['requestedBusinessTerm'] = array_unique($response['requestedBusinessTerm']);
						sort($response['requestedBusinessTerm']);
						$additionString .= "<br/>. . . . <b>Requested business terms:</b><br/>. . . . . . " . implode("<br/>. . . . . . ", $response['requestedBusinessTerm']) . "<br/>";
					}
					if (!empty($response['unrequested'])) {
						$response['unrequested'] = array_unique($response['unrequested']);
						sort($response['unrequested']);
						$additionString .= "<br/>. . . . <b>Unrequested terms:</b><br/>. . . . . . " . implode("<br/>. . . . . . ", $response['unrequested']) . "<br/>";
					}
					if (!empty($response['unmapped'])) {
						$additionString .= "<br/>. . . . <b>*Fields with no Business Terms:</b><br/>";
						if (!empty($response['unmapped']['requested'])) {
							$additionString .= ". . . . . . Requested:<br/>. . . . . . . . " . implode("<br/>. . . . . . . . ", $response['unmapped']['requested']) . "<br/>";
						}
						if (!empty($response['unmapped']['unrequested'])) {
							$additionString .= ". . . . . . Unrequested:<br/>. . . . . . . . " . implode("<br/>. . . . . . . . ", $response['unmapped']['unrequested']) . "<br/>";
						}
					}
					$additionString .= "<br/>";
				}
			}

			$attrKey = $legacy ? 'Additional Information Requested' : 'Requested Information Map';
			$attrName = $legacy ? 'descriptionOfInformation' : 'requestedInformationMap';
			if (!empty($request->attributes[$attrKey])) {
				$formResp = $this->CollibraAPI->post(
					'attribute/'.$request->attributes[$attrKey]->attrResourceId,
					$this->Collibra->preparePostData(['value' => $request->attributes[$attrKey]->attrValue . $additionString], '/%0D%0A/', '<br/>'));
				$formResp = json_decode($formResp);
				if (!isset($formResp)) $success = false;
			} else {
				$resp = $this->CollibraAPI->post(
					'term/'.$this->request->data['dsrId'].'/attributes',
					$this->Collibra->preparePostData(['label' => Configure::read('Collibra.formFields.'.$attrName), 'value' => $additionString], '/%0D%0A/', '<br/>'));
				if ($resp->code != '201') $success = false;
			}

			$requestedTerms = array_filter($requestedTerms, function($term) use($request) {
				foreach ($request->requestedTerms as $alreadyRequested) {
					if ($alreadyRequested->reqTermId == $term->termId) {
						return false;
					}
				}
				return true;
			});
			$_tmp = [];
			foreach ($requestedTerms as $term) {
			    if (!array_key_exists($term->termId, $_tmp)) {
			        $_tmp[$term->termId] = $term;
			    }
			}
			$requestedTerms = array_values($_tmp);

			$requestData = $this->request->data;
			$additionalTerms = array_filter($additionalTerms, function($term) use($request, $requestData) {
				foreach ($request->requestedTerms as $alreadyRequested) {
					if ($alreadyRequested->reqTermId == $term->termId) {
						return false;
					}
				}
				foreach ($request->additionallyIncludedTerms as $alreadyIncluded) {
					if ($alreadyIncluded->addTermId == $term->termId) {
						return false;
					}
				}
				if (isset($requestData['arrBusinessTerms'])) {
					foreach ($requestData['arrBusinessTerms'] as $newAdditionId) {
						if ($newAdditionId == $term->termId) {
							return false;
						}
					}
				}
				return true;
			});
			$_tmp = [];
			foreach ($additionalTerms as $term) {
			    if (!array_key_exists($term->termId, $_tmp)) {
			        $_tmp[$term->termId] = $term;
			    }
			}
			$additionalTerms = array_values($_tmp);

			$addPolicy = false;
			foreach ($requestedTerms as $term) {
				if ($term->termCommunityId == Configure::read('Collibra.community.academicRecords')) {
					$addPolicy = true;
					break;
				}
			}
			if (!$addPolicy) {
				foreach ($additionalTerms as $term) {
					if ($term->termCommunityId == Configure::read('Collibra.community.academicRecords')) {
						$addPolicy = true;
						break;
					}
				}
			}
			if ($addPolicy) {
				foreach ($request->policies as $policy) {
					if ($policy->policyId == Configure::read('Collibra.policy.trustedPartnerSecurityStandards')) {
						$addPolicy = false;
						break;
					}
				}

				if ($addPolicy) {
					array_push($relationsPostData['policies'], Configure::read('Collibra.policy.trustedPartnerSecurityStandards'));
				}
			}

			foreach ($requestedTerms as $term) {
				array_push($relationsPostData['requestedTerms'], $term->termId);
			}
			foreach ($additionalTerms as $term) {
				array_push($relationsPostData['additionalTerms'], $term->termId);
			}

			if (!empty($toDeleteIds)) {
				$resp = $this->CollibraAPI->deleteJSON('relation', $this->Collibra->preparePostData(['resource' => $toDeleteIds]));
				if ($resp->code != '200') $success = false;
			}

			$resp = $this->CollibraAPI->post('workflow/'.Configure::read('Collibra.workflow.changeDSRRelations').'/start', $this->Collibra->preparePostData($relationsPostData));
			if ($resp->code != '200') $success = false;

			$this->Session->write('queue', $arrQueue);
			return $success ? json_encode(['success' => 1]) : json_encode(['success' => 0]);
		} else if ($this->request->data['action'] == 'remove') {
			$resp = $this->CollibraAPI->deleteJSON('relation', $this->Collibra->preparePostData(['resource' => $this->request->data['arrRelIds']]));
			if ($resp->code != '200') $success = false;

			$deletionString = "<br/><br/>Removal, ".date('Y-m-d').":";
			foreach ($this->request->data['arrNames'] as $termName) {
				$deletionString .= "<br/>{$termName}";
			}

			$request = $this->CollibraAPI->getRequestDetails($this->request->data['dsrId']);
			$legacy = $this->isLegacy($request);
			$attrKey = $legacy ? 'Additional Information Requested' : 'Requested Information Map';
			$attrName = $legacy ? 'descriptionOfInformation' : 'requestedInformationMap';

			if (!empty($request->attributes[$attrKey])) {
				$resp = $this->CollibraAPI->post(
					'attribute/'.$request->attributes[$attrKey]->attrResourceId,
					$this->Collibra->preparePostData(['value' => $request->attributes[$attrKey]->attrValue.$deletionString], '/%0D%0A/', '<br/>'));
				$resp = json_decode($resp);
				if (!isset($resp)) $success = false;
			} else {
				$resp = $this->CollibraAPI->post(
					'term/'.$this->request->data['dsrId'].'/attributes',
					$this->Collibra->preparePostData(['label' => Configure::read('Collibra.formFields.'.$attrName),'value' => $deletionString], '/%0D%0A/', '<br/>'));
				if ($resp->code != '201') $success = false;
			}

			$toDeleteIds = [];
			$nowAdditionallyIncluded = [];
			foreach ($request->necessaryApis as $api) {
				if ($api->apiAuthorizedByFieldset != 'true') {
					$thisApiDeletedFieldIds = [];
					$thisApiAllFieldIds = [];
					$apiStillRequested = false;
					$api->fields = $this->CollibraAPI->getApiFields($api->apiCommName, $api->apiName);

					foreach ($api->fields as $field) {
						array_push($thisApiAllFieldIds, $field->id);
						if (in_array($field->id, $this->request->data['arrIds'])) {
							array_push($thisApiDeletedFieldIds, $field->id);
						} else {
							foreach ($request->requestedDataAssets as $dataAsset) {
								if ($field->id == $dataAsset->reqDataId) {
									$apiStillRequested = true;
								}
							}
						}
					}

					if ($apiStillRequested) {
						$nowAdditionallyIncluded = array_merge($nowAdditionallyIncluded, $thisApiDeletedFieldIds);
					} else {
						foreach ($request->additionallyIncludedDataAssets as $dataAsset) {
							if (in_array($dataAsset->addDataId, $thisApiAllFieldIds)) {
								array_push($toDeleteIds, $dataAsset->addDataRelationId);
							}
						}
						array_push($toDeleteIds, $api->apiRelationId);
					}
				} else {
					$api->fields = $this->CollibraAPI->getApiFields($api->apiCommName, $api->apiName, true);
					$apiStillRequested = false;

					foreach ($api->fields as $fieldset) {
						$fieldsetDeletedFieldIds = [];
						$fieldsetAllFieldIds = [];
						$fieldsetStillRequested = false;

						foreach ($fieldset->descendantFields as $field) {
							array_push($fieldsetAllFieldIds, $field->id);
							if (in_array($field->id, $this->request->data['arrIds'])) {
								array_push($fieldsetDeletedFieldIds, $field->id);
							} else {
								foreach ($request->requestedDataAssets as $dataAsset) {
									if ($field->id == $dataAsset->reqDataId) {
										$fieldsetStillRequested = true;
										$apiStillRequested = true;
									}
								}
							}
						}

						if ($fieldsetStillRequested) {
							$nowAdditionallyIncluded = array_merge($nowAdditionallyIncluded, $fieldsetDeletedFieldIds);
						} else {
							foreach ($request->additionallyIncludedDataAssets as $dataAsset) {
								if (in_array($dataAsset->addDataId, $fieldsetAllFieldIds)) {
									array_push($toDeleteIds, $dataAsset->addDataRelationId);
								}
							}
						}
					}

					if (!$apiStillRequested) {
						array_push($toDeleteIds, $api->apiRelationId);
					}
				}
			}
			foreach ($request->necessaryTables as $table) {
				$thisTableDeletedColumnIds = [];
				$thisTableAllColumnIds = [];
				$tableStillRequested = false;
				$table->columns = $this->CollibraAPI->getTableColumns($table->tableCommName, $table->tableName);

				foreach ($table->columns as $column) {
					array_push($thisTableAllColumnIds, $column->columnId);
					if (in_array($column->columnId, $this->request->data['arrIds'])) {
						array_push($thisTableDeletedColumnIds, $column->columnId);
					} else {
						foreach ($request->requestedDataAssets as $dataAsset) {
							if ($column->columnId == $dataAsset->reqDataId) {
								$tableStillRequested = true;
							}
						}
					}
				}

				if ($tableStillRequested) {
					$nowAdditionallyIncluded = array_merge($nowAdditionallyIncluded, $thisTableDeletedColumnIds);
				} else {
					foreach ($request->additionallyIncludedDataAssets as $dataAsset) {
						if (in_array($dataAsset->addDataId, $thisTableAllColumnIds)) {
							array_push($toDeleteIds, $dataAsset->addDataRelationId);
						}
					}
					array_push($toDeleteIds, $table->tableRelationId);
				}
			}
			foreach ($request->necessarySamlResponses as $response) {
				$thisResponseAllFieldIds = [];
				$responseStillRequested = false;
				$response->fields = $this->CollibraAPI->getSamlResponseFields($response->responseName);

				foreach ($response->fields as $field) {
					array_push($thisResponseAllFieldIds, $field->fieldId);
					if (!in_array($field->fieldId, $this->request->data['arrIds'])) {
						foreach ($request->requestedDataAssets as $dataAsset) {
							if ($field->fieldId == $dataAsset->reqDataId) {
								$responseStillRequested = true;
							}
						}
					}
				}

				if (!$responseStillRequested) {
					foreach ($request->additionallyIncludedDataAssets as $dataAsset) {
						if (in_array($dataAsset->addDataId, $thisResponseAllFieldIds)) {
							array_push($toDeleteIds, $dataAsset->addDataRelationId);
						}
					}
					array_push($toDeleteIds, $response->responseRelationId);
				}
			}

			$resp = $this->CollibraAPI->post(
				'workflow/'.Configure::read('Collibra.workflow.changeDSRRelations').'/start',
				$this->Collibra->preparePostData(['dsrId' => $this->request->data['dsrId'], 'additionalDataAssets' => $nowAdditionallyIncluded]));
			if ($resp->code != '200') $success = false;

			if (!empty($toDeleteIds)) {
				$resp = $this->CollibraAPI->deleteJSON('relation', $this->Collibra->preparePostData(['resource' => $toDeleteIds]));
				if ($resp->code != '200') $success = false;
			}

			// Re-loading the DSR's state after adjusting the data asset relations
			$request = $this->CollibraAPI->getRequestDetails($this->request->data['dsrId']);

			$newRequestedBusinessTerms = [];
			$newAdditionalBusinessTerms = [];
			foreach ($request->requestedDataAssets as $dataAsset) {
				if (!empty($dataAsset->reqDataBusinessTermId)) {
					array_push($newRequestedBusinessTerms, $dataAsset->reqDataBusinessTermId);
				}
			}
			$newRequestedBusinessTerms = array_unique($newRequestedBusinessTerms);
			foreach ($request->additionallyIncludedDataAssets as $dataAsset) {
				if (!empty($dataAsset->addDataBusinessTermId) && !in_array($dataAsset->addDataBusinessTermId, $newRequestedBusinessTerms)) {
					array_push($newAdditionalBusinessTerms, $dataAsset->addDataBusinessTermId);
				}
			}
			$newAdditionalBusinessTerms = array_unique($newAdditionalBusinessTerms);

			$toDeleteIds = [];
			$nowAdditionallyIncluded = [];
			foreach ($request->requestedTerms as $term) {
				if (!in_array($term->reqTermId, $newRequestedBusinessTerms)) {
					array_push($toDeleteIds, $term->reqTermRelationId);
					if (in_array($term->reqTermId, $newAdditionalBusinessTerms)) {
						array_push($nowAdditionallyIncluded, $term->reqTermId);
					}
				}
			}
			foreach ($request->additionallyIncludedTerms as $term) {
				if (!in_array($term->addTermId, $newAdditionalBusinessTerms)) {
					array_push($toDeleteIds, $term->addTermRelationId);
				}
			}

			$resp = $this->CollibraAPI->post(
				'workflow/'.Configure::read('Collibra.workflow.changeDSRRelations').'/start',
				$this->Collibra->preparePostData(['dsrId' => $this->request->data['dsrId'], 'additionalTerms' => $nowAdditionallyIncluded]));
			if ($resp->code != '200') $success = false;

			if (!empty($toDeleteIds)) {
				$resp = $this->CollibraAPI->deleteJSON('relation', $this->Collibra->preparePostData(['resource' => $toDeleteIds]));
				if ($resp->code != '200') $success = false;
			}

			// If all of a request's APIs, tables, or SAML responses have been removed, edit the technologyType attribute
			if (empty($request->necessaryApis) || empty($request->necessaryTables) || empty($request->necessarySamlResponses)) {
				foreach ($request->attributes as $attr) {
					if ($attr->attrTypeId == Configure::read('Collibra.formFields.technologyType')) {
						$arrOldValue = explode(';', $attr->attrValue);
						sort($arrOldValue);
						$oldValue = implode(';', $arrOldValue);
						$attrId = $attr->attrResourceId;
						break;
					}
				}

				$arrNewValue = [];
				if (!empty($request->necessaryApis)) {
					array_push($arrNewValue, 'API');
				}
				if (!empty($request->necessaryTables)) {
					array_push($arrNewValue, 'Data Warehouse');
				}
				if (!empty($request->necessarySamlResponses)) {
					array_push($arrNewValue, 'SAML');
				}
				$newValue = implode(';', $arrNewValue);

				if ($newValue !== $oldValue) {
					$resp = $this->CollibraAPI->post('attribute/'.$attrId, $this->Collibra->preparePostData(['value' => $newValue]));
					if ($resp->code != '200') $success = false;
				}
			}

			// If the request no longer contains terms from the Academic Records glossary, remove the corresponding policy
			$trustedPartnerPolicyRelation = '';
			foreach ($request->policies as $policy) {
				if ($policy->policyId == Configure::read('Collibra.policy.trustedPartnerSecurityStandards')) {
					$trustedPartnerPolicyRelation = $policy->policyRelationId;
					break;
				}
			}
			if (!empty($trustedPartnerPolicyRelation)) {
				$keepPolicy = false;
				foreach ($request->requestedTerms as $reqTerm) {
					if ($reqTerm->reqTermCommId == Configure::read('Collibra.community.academicRecords')) {
						$keepPolicy = true;
						break;
					}
				}
				if (!$keepPolicy) {
					foreach ($request->additionallyIncludedTerms as $addTerm) {
						if ($addTerm->addTermCommId == Configure::read('Collibra.community.academicRecords')) {
							$keepPolicy = true;
							break;
						}
					}
				}
				if (!$keepPolicy) {
					$resp = $this->CollibraAPI->delete('relation/'.$trustedPartnerPolicyRelation);
					if ($resp->code != '200') $success = false;
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

		$organizedApiFields = [];
		foreach ($arrQueue['apiFields'] as $id => $field) {
			$alreadyRequested = false;
			foreach ($request->requestedDataAssets as $requestedAsset) {
				if ($requestedAsset->reqDataId == $id) {
					$alreadyRequested = true;
					break;
				}
			}
			if ($alreadyRequested) continue;

			$organizedApiFields[$field['apiPath']][$id] = $field;
		}
		foreach ($organizedApiFields as $apiPath => $fields) {
			uasort($organizedApiFields[$apiPath], function($a, $b) {
				return strcmp($a['fullName'], $b['fullName']);
			});
		}

		$organizedDbColumns = [];
		foreach ($arrQueue['dbColumns'] as $id => $column) {
			$alreadyRequested = false;
			foreach ($request->requestedDataAssets as $requestedAsset) {
				if ($requestedAsset->reqDataId == $id) {
					$alreadyRequested = true;
					break;
				}
			}
			if ($alreadyRequested) continue;

			$organizedDbColumns[$column['tableName']][$id] = $column;
		}
		foreach ($organizedDbColumns as $table => $columns) {
			uasort($organizedDbColumns[$table], function($a, $b) {
				return strcmp($a['fullName'], $b['fullName']);
			});
		}

		$organizedSamlFields = [];
		foreach ($arrQueue['samlFields'] as $id => $field) {
			$alreadyRequested = false;
			foreach ($request->requestedDataAssets as $requestedAsset) {
				if ($requestedAsset->reqDataId == $id) {
					$alreadyRequested = true;
					break;
				}
			}
			if ($alreadyRequested) continue;

			$organizedSamlFields[$field['responseName']][$id] = $field;
		}
		foreach ($organizedSamlFields as $response => $fields) {
			uasort($organizedSamlFields[$response], function($a, $b) {
				return strcmp($a['name'], $b['name']);
			});
		}

		$filteredApis = [];
		foreach ($arrQueue['emptyApis'] as $path => $api) {
			$alreadyRequested = false;
			foreach ($request->necessaryApis as $requestedApi) {
				if (substr($requestedApi->apiName, 1) == $path) {
					$alreadyRequested = true;
					break;
				}
			}
			if ($alreadyRequested) continue;

			$filteredApis[$path] = $api;
		}

		$filteredCartTerms = [];
		foreach ($arrQueue['businessTerms'] as $id => $term) {
			$alreadyRequested = false;
			foreach ($request->requestedTerms as $requestedTerm) {
				if ($requestedTerm->reqTermId == $id) {
					$alreadyRequested = true;
					break;
				}
			}
			if ($alreadyRequested) continue;

			$filteredCartTerms[$id] = $term;
		}

		$requestedData = [];
		foreach ($request->requestedDataAssets as $data) {
			$requestedData[$data->reqDataVocabName][] = $data;
		}
		foreach ($requestedData as $vocab => $dataAssets) {
			uasort($requestedData[$vocab], function($a, $b) {
				return strcmp($a->reqDataSignifier, $b->reqDataSignifier);
			});
		}

		$this->set(compact('request', 'arrQueue', 'organizedApiFields', 'organizedDbColumns', 'organizedSamlFields', 'filteredApis', 'filteredCartTerms', 'requestedData'));
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
				$formResp = $this->CollibraAPI->post('term/'.$asset->id.'/attributes', $this->Collibra->preparePostData($postData, '/%0D%0A/', '<br/>'));
				$formResp = json_decode($formResp);

				if (!isset($formResp)) {
					$err = true;
				}
			}
		}

		if (!empty($wfPostData['attributes'])) {
			$resp = $this->CollibraAPI->post(
				'workflow/'.Configure::read('Collibra.workflow.changeAttributes').'/start',
				$this->Collibra->preparePostData($wfPostData, ['/%0D%0A/', '/%5B[0-9]*%5D/'], ['<br/>', '']));
			if ($resp->code != '200') $err = true;
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
		foreach ($asset->collaborators as $collaborator) {
			if ($collaborator->identifiers->net_id == $netID) {
				$guest = false;
				break;
			}
		}
		foreach($asset->attributes as $attr) {
			if (preg_match('/<head\/?>/', $attr->attrValue)) {
				$attr->attrValue = preg_replace('/<head\/?>/', '', $attr->attrValue);
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

		if ($this->isLegacy($asset, $parent)) {
			$formFields = [
				(object) [
					'id' => 'applicationOrProjectName',
					'name' => 'Application or Project Name',
					'type' => 'string',
					'value' => 'This name will be included in the title of this request to help you easily find it in the future.',
					'writable' => 1,
					'required' => null,
					'multiValue' => null
				],
				(object) [
					'id' => 'descriptionOfInformation',
					'name' => 'Additional Information Requested',
					'type' => 'textarea',
					'value' => 'Was there other information you need to access but were unable to find on this site?',
					'writable' => 1,
					'required' => null,
					'multiValue' => null
				],
				(object) [
					'id' => 'descriptionOfIntendedUse',
					'name' => 'Description of Intended Use',
					'type' => 'textarea',
					'value' => 'Enter a paragraph or two describing the application and how access to the requested information will help your users to be more successful (e.g. quicker, more accurate, safer, less work).',
					'writable' => 1,
					'required' => null,
					'multiValue' => null
				],
				(object) [
					'id' => 'accessRights',
					'name' => 'Access Rights',
					'type' => 'textarea',
					'value' => 'Who will be allowed to access the requested information?',
					'writable' => 1,
					'required' => null,
					'multiValue' => null
				],
				(object) [
					'id' => 'accessMethod',
					'name' => 'Access Method',
					'type' => 'textarea',
					'value' => 'How is access expected to be granted and managed to ensure compliance?',
					'writable' => 1,
					'required' => null,
					'multiValue' => null
				],
				(object) [
					'id' => 'impactOnSystem',
					'name' => 'Impact on System',
					'type' => 'textarea',
					'value' => 'How often will the information need to be updated (e.g. These five data elements need to be refreshed on demand - anticipated no more than 15 times per hours)?',
					'writable' => 1,
					'required' => null,
					'multiValue' => null
				]
			];
		} else {
			$formResp = $this->CollibraAPI->get('workflow/'.Configure::read('Collibra.workflow.intakeDSR').'/form/start');
			$formResp = json_decode($formResp);
			$formFields = array_filter($formResp->formProperties, function($property) {
				return !in_array($property->id, [
					'requesterName',
					'requesterEmail',
					'requesterPhone',
					'requesterRole',
					'requesterPersonId',
					'requesterNetId',
					'requestingOrganization',
					'sponsorName',
					'sponsorRole',
					'sponsorEmail',
					'sponsorPhone',
					'developmentShopId',
					'developmentShop',
					'applicationOrProjectId',
					'applicationOrProjectName',
					'api',
					'tables',
					'saml',
					'readWriteAccess',
					'requestedInformationMap',
					'technologyType',
					Configure::read('Collibra.requiredTermsString'),
					Configure::read('Collibra.additionalTermsString'),
					Configure::read('Collibra.requiredElementsString'),
					Configure::read('Collibra.additionalElementsString')
				]);
			});
		}

		$this->set(compact('guest', 'formFields', 'asset', 'parent', 'legacy'));
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
			empty($arrQueue['apiFields']) &&
			empty($arrQueue['dbColumns']) &&
			empty($arrQueue['samlFields']) &&
			empty($arrQueue['businessTerms']) &&
			empty($arrQueue['emptyApis'])
		) {
			$this->redirect(['action' => 'index', '?' => ['err' => 1]]);
			exit;
		}

		$apis = [];
		$fieldsetApis = [];
		$tables = [];
		$samlResponses = [];
		$individualTerms = [];
		$dataAssetIds = [];
		$addDataAssetIds = [];
		$businessTermIds = [];
		$addBusinessTermIds = [];
		foreach ($arrQueue['apiFields'] as $id => $field) {
			if (!empty($field['apiPath']) && !empty($field['apiHost'])) {
				if ($field['authorizedByFieldset'] == 'true') {
					if (strpos($field['fullName'], '.') === false) {		// is fieldset
						$fieldsetApis[$field['apiHost']][$field['apiPath']][$field['name']] = [];
					} else {												// is field
						$fieldsetApis[$field['apiHost']][$field['apiPath']][substr($field['fullName'],0,strpos($field['fullName'],'.'))] = [];
					}
				} else {
					$apis[$field['apiHost']][$field['apiPath']] = [];
				}
			}
		}
		foreach ($arrQueue['dbColumns'] as $id => $column) {
			if (!empty($column['tableName'])) {
				$tables[$column['databaseName'].' > '.$column['tableName']] = [];
			}
		}
		foreach ($arrQueue['samlFields'] as $id => $field) {
			if (!empty($field['responseName'])) {
				$samlResponses[$field['responseName']] = [];
			}
		}
		foreach ($arrQueue['businessTerms'] as $id => $term) {
			array_push($individualTerms, $term['term']);
			array_push($businessTermIds, $id);
		}

		foreach ($apis as $apiHost => $apiPaths) {
			foreach ($apiPaths as $apiPath => $ignore) {
				$apiFields = $this->CollibraAPI->getApiFields($apiHost, $apiPath);
				foreach ($apiFields as $field) {
					if (!empty($field->assetType) && strtolower($field->assetType) == 'fieldset') {
						continue;
					}
					if (empty($field->businessTerm[0]->termId)) {
						if (array_key_exists($field->id, $arrQueue['apiFields'])) {
							array_push($dataAssetIds, $field->id);
							$apis[$apiHost][$apiPath]['unmapped']['requested'][] = $field->name;
						} else {
							array_push($addDataAssetIds, $field->id);
							$apis[$apiHost][$apiPath]['unmapped']['unrequested'][] = $field->name;
						}
					} else {
						if (array_key_exists($field->id, $arrQueue['apiFields'])) {
							array_push($dataAssetIds, $field->id);
							array_push($businessTermIds, $field->businessTerm[0]->termId);
							$apis[$apiHost][$apiPath]['requestedBusinessTerm'][] = '('.$field->businessTerm[0]->termCommunityName.') '.$field->businessTerm[0]->term;
						} else {
							array_push($addDataAssetIds, $field->id);
							array_push($addBusinessTermIds, $field->businessTerm[0]->termId);
							$apis[$apiHost][$apiPath]['unrequested'][] = '('.$field->businessTerm[0]->termCommunityName.') '.$field->businessTerm[0]->term;
						}
					}
				}
			}
		}
		foreach ($fieldsetApis as $apiHost => $apiPaths) {
			foreach ($apiPaths as $apiPath => $fieldsets) {
				$apiFields = $this->CollibraAPI->getApiFields($apiHost, $apiPath, true);
				foreach ($fieldsets as $fieldset => $_) {
					foreach ($apiFields[$fieldset]->descendantFields as $field) {
						if (!empty($field->assetType) && strtolower($field->assetType) == 'fieldset') {
							continue;
						}
						if (empty($field->businessTerm[0]->termId)) {
							if (array_key_exists($field->id, $arrQueue['apiFields'])) {
								array_push($dataAssetIds, $field->id);
								$fieldsetApis[$apiHost][$apiPath][$fieldset]['unmapped']['requested'][] = $field->name;
							} else {
								array_push($addDataAssetIds, $field->id);
								$fieldsetApis[$apiHost][$apiPath][$fieldset]['unmapped']['unrequested'][] = $field->name;
							}
						} else {
							if (array_key_exists($field->id, $arrQueue['apiFields'])) {
								array_push($dataAssetIds, $field->id);
								array_push($businessTermIds, $field->businessTerm[0]->termId);
								$fieldsetApis[$apiHost][$apiPath][$fieldset]['requestedBusinessTerm'][] = '('.$field->businessTerm[0]->termCommunityName.') '.$field->businessTerm[0]->term;
							} else {
								array_push($addDataAssetIds, $field->id);
								array_push($addBusinessTermIds, $field->businessTerm[0]->termId);
								$fieldsetApis[$apiHost][$apiPath][$fieldset]['unrequested'][] = '('.$field->businessTerm[0]->termCommunityName.') '.$field->businessTerm[0]->term;
							}
						}
					}
				}
			}
		}
		foreach ($tables as $tableName => $_) {
			$databaseName = substr($tableName, 0, strpos($tableName, '>') - 1);
			$schemaAndTableNameOnly = substr($tableName, strpos($tableName, '>') + 2);
			$tableColumns = $this->CollibraAPI->getTableColumns($databaseName, $schemaAndTableNameOnly);
			foreach ($tableColumns as $column) {
				if (empty($column->businessTerm[0]->termId)) {
					if (array_key_exists($column->columnId, $arrQueue['dbColumns'])) {
						array_push($dataAssetIds, $column->columnId);
						$tables[$tableName]['unmapped']['requested'][] = $column->columnName;
					} else {
						array_push($addDataAssetIds, $column->columnId);
						$tables[$tableName]['unmapped']['unrequested'][] = $column->columnName;
					}
				} else {
					if (array_key_exists($column->columnId, $arrQueue['dbColumns'])) {
						array_push($dataAssetIds, $column->columnId);
						array_push($businessTermIds, $column->businessTerm[0]->termId);
						$tables[$tableName]['requestedBusinessTerm'][] = '('.$column->businessTerm[0]->termCommunityName.') '.$column->businessTerm[0]->term;
					} else {
						array_push($addDataAssetIds, $column->columnId);
						array_push($addBusinessTermIds, $column->businessTerm[0]->termId);
						$tables[$tableName]['unrequested'][] = '('.$column->businessTerm[0]->termCommunityName.') '.$column->businessTerm[0]->term;
					}
				}
			}
		}
		foreach ($samlResponses as $responseName => $_) {
			$responseFields = $this->CollibraAPI->getSamlResponseFields($responseName);
			foreach ($responseFields as $field) {
				if (empty($field->businessTerm[0]->termId)) {
					if (array_key_exists($field->fieldId, $arrQueue['samlFields'])) {
						array_push($dataAssetIds, $field->fieldId);
						$samlResponses[$responseName]['unmapped']['requested'][] = $field->fieldName;
					} else {
						$samlResponses[$responseName]['unmapped']['unrequested'][] = $field->fieldName;
					}
				} else {
					if (array_key_exists($field->fieldId, $arrQueue['samlFields'])) {
						array_push($dataAssetIds, $field->fieldId);
						array_push($businessTermIds, $field->businessTerm[0]->termId);
						$samlResponses[$responseName]['requestedBusinessTerm'][] = '('.$field->businessTerm[0]->termCommunityName.') '.$field->businessTerm[0]->term;
					} else {
						$samlResponses[$responseName]['unrequested'][] = '('.$field->businessTerm[0]->termCommunityName.') '.$field->businessTerm[0]->term;
					}
				}
			}
		}
		$postData['requestedInformationMap'] = '';
		if (!empty($individualTerms)) {
			$postData['requestedInformationMap'] .= "<b>Individual Business Terms:</b><br/>. . " . implode("<br/>. . ", $individualTerms) . "<br/><br/>";
		}
		if (!empty($apis) || !empty($fieldsetApis) || !empty($arrQueue['emptyApis'])) {
			$postData['requestedInformationMap'] .= "<b>Requested APIs:</b><br/>";
		}
		foreach ($arrQueue['emptyApis'] as $path => $api) {
			$postData['requestedInformationMap'] .= "<br/>. . {$api['apiHost']}/{$path}<br/>. . . . [No specified output fields]";
			$apis[$api['apiHost']][$path] = [];
		}
		if (!empty($apis)) {
			$apiList = "";
			foreach ($apis as $apiHost => $apiPaths) {
				foreach ($apiPaths as $apiPath => $term) {
					$apiList .= ". . <u><b>{$apiHost}/{$apiPath}</u></b><br/>";
					if (!empty($term['requestedBusinessTerm'])) {
						$term['requestedBusinessTerm'] = array_unique($term['requestedBusinessTerm']);
						sort($term['requestedBusinessTerm']);
						$apiList .= "<br/>. . . . <b>Requested business terms:</b><br/>. . . . . . " . implode("<br/>. . . . . . ", $term['requestedBusinessTerm']) . "<br/>";
					}
					if (!empty($term['unrequested'])) {
						$term['unrequested'] = array_unique(array_diff($term['unrequested'], $term['requestedBusinessTerm']));
						sort($term['unrequested']);
						$apiList .= "<br/>. . . . <b>Unrequested terms:</b><br/>. . . . . . " . implode("<br/>. . . . . . ", $term['unrequested']) . "<br/>";
					}
					if (!empty($term['unmapped'])) {
						$apiList .= "<br/>. . . . <b>*Fields with no Business Terms:</b><br/>";
						if (!empty($term['unmapped']['requested'])) {
							$apiList .= ". . . . . . Requested:<br/>. . . . . . . . " . implode("<br/>. . . . . . . . ", $term['unmapped']['requested']) . "<br/>";
						}
						if (!empty($term['unmapped']['unrequested'])) {
							$apiList .= ". . . . . . Unrequested:<br/>. . . . . . . . " . implode("<br/>. . . . . . . . ", $term['unmapped']['unrequested']) . "<br/>";
						}
					}
					$apiList .= "<br/><br/>";
				}
			}
			$postData['requestedInformationMap'] .= $apiList;
		}
		if (!empty($fieldsetApis)) {
			$fieldsetApiList = "";
			foreach ($fieldsetApis as $apiHost => $apiPaths) {
				foreach ($apiPaths as $apiPath => $fieldsets) {
					foreach ($fieldsets as $fieldset => $term) {
						$fieldsetApiList .= ". . <u><b>{$apiHost}/{$apiPath}, {$fieldset}</u></b><br/>";
						if (!empty($term['requestedBusinessTerm'])) {
							$term['requestedBusinessTerm'] = array_unique($term['requestedBusinessTerm']);
							sort($term['requestedBusinessTerm']);
							$fieldsetApiList .= "<br/>. . . . <b>Requested business terms:</b><br/>. . . . . . " . implode("<br/>. . . . . . ", $term['requestedBusinessTerm']) . "<br/>";
						}
						if (!empty($term['unrequested'])) {
							$term['unrequested'] = array_unique(array_diff($term['unrequested'], $term['requestedBusinessTerm']));
							sort($term['unrequested']);
							$fieldsetApiList .= "<br/>. . . . <b>Unrequested terms:</b><br/>. . . . . . " . implode("<br/>. . . . . . ", $term['unrequested']) . "<br/>";
						}
						if (!empty($term['unmapped'])) {
							$fieldsetApiList .= "<br/>. . . . <b>*Fields with no Business Terms:</b><br/>";
							if (!empty($term['unmapped']['requested'])) {
								$fieldsetApiList .= ". . . . . . Requested:<br/>. . . . . . . . " . implode("<br/>. . . . . . . . ", $term['unmapped']['requested']) . "<br/>";
							}
							if (!empty($term['unmapped']['unrequested'])) {
								$fieldsetApiList .= ". . . . . . Unrequested:<br/>. . . . . . . . " . implode("<br/>. . . . . . . . ", $term['unmapped']['unrequested']) . "<br/>";
							}
						}
						$fieldsetApiList .= "<br/><br/>";
					}
				}
			}
			$postData['requestedInformationMap'] .= $fieldsetApiList;
		}
		if (!empty($tables)) {
			$tableList = "<b>Requested Tables:</b><br/>";
			foreach ($tables as $tableName => $term) {
				$tableList .= ". . <u><b>{$tableName}</u></b><br/>";
				if (!empty($term['requestedBusinessTerm'])) {
					$term['requestedBusinessTerm'] = array_unique($term['requestedBusinessTerm']);
					sort($term['requestedBusinessTerm']);
					$tableList .= "<br/>. . . . <b>Requested business terms:</b><br/>. . . . . . " . implode("<br/>. . . . . . ", $term['requestedBusinessTerm']) . "<br/>";
				}
				if (!empty($term['unrequested'])) {
					$term['unrequested'] = array_unique($term['unrequested']);
					sort($term['unrequested']);
					$tableList .= "<br/>. . . . <b>Unrequested terms:</b><br/>. . . . . . " . implode("<br/>. . . . . . ", $term['unrequested']) . "<br/>";
				}
				if (!empty($term['unmapped'])) {
					$tableList .= "<br/>. . . . <b>*Columns with no Business Terms:</b><br/>";
					if (!empty($term['unmapped']['requested'])) {
						$tableList .= ". . . . . . Requested:<br/>. . . . . . . . " . implode("<br/>. . . . . . . . ", $term['unmapped']['requested']) . "<br/>";
					}
					if (!empty($term['unmapped']['unrequested'])) {
						$tableList .= ". . . . . . Unrequested:<br/>. . . . . . . . " . implode("<br/>. . . . . . . . ", $term['unmapped']['unrequested']) . "<br/>";
					}
				}
				$tableList .= "<br/>";
			}
			$postData['requestedInformationMap'] .= $tableList;
		}
		if (!empty($samlResponses)) {
			$responseList = "<b>Requested SAML Responses:</b><br/>";
			foreach ($samlResponses as $responseName => $term) {
				$responseList .= ". . <u><b>{$responseName}</u></b><br/>";
				if (!empty($term['requestedBusinessTerm'])) {
					$term['requestedBusinessTerm'] = array_unique($term['requestedBusinessTerm']);
					sort($term['requestedBusinessTerm']);
					$responseList .= "<br/>. . . . <b>Requested business terms:</b><br/>. . . . . . " . implode("<br/>. . . . . . ", $term['requestedBusinessTerm']) . "<br/>";
				}
				if (!empty($term['unrequested'])) {
					$term['unrequested'] = array_unique($term['unrequested']);
					sort($term['unrequested']);
					$responseList .= "<br/>. . . . <b>Unrequested terms:</b><br/>. . . . . . " . implode("<br/>. . . . . . ", $term['unrequested']) . "<br/>";
				}
				if (!empty($term['unmapped'])) {
					$responseList .= "<br/>. . . . <b>*Fields with no Business Terms:</b><br/>";
					if (!empty($term['unmapped']['requested'])) {
						$responseList .= ". . . . . . Requested:<br/>. . . . . . . . " . implode("<br/>. . . . . . . . ", $term['unmapped']['requested']) . "<br/>";
					}
					if (!empty($term['unmapped']['unrequested'])) {
						$responseList .= ". . . . . . Unrequested:<br/>. . . . . . . . " . implode("<br/>. . . . . . . . ", $term['unmapped']['unrequested']) . "<br/>";
					}
				}
				$responseList .= "<br/>";
			}
			$postData['requestedInformationMap'] .= $responseList;
		}


		$name = explode(' ',$this->request->data['name']);
		$firstName = $name[0];
		$lastName = sizeof($name) > 1 ? $name[1] : '';
		$email = $this->request->data['email'];
		$phone = $this->request->data['phone'];
		$role = $this->request->data['role'];

		$netID = $this->request->data['requesterNetId'];
		$byuUser = $this->BYUAPI->personalSummary($netID);
		$supervisorInfo = $this->BYUAPI->supervisorLookup($netID);

		$postData['requesterNetId'] = array_unique([$byuUser->identifiers->net_id, $supervisorInfo->net_id, $this->Auth->user('username')]);
		foreach($this->request->data as $key => $val){
			if (!in_array($key, ['name', 'phone', 'email', 'role', 'terms', 'apiFields', 'requestSubmit', 'collibraUser', 'requesterNetId'])) {
				$postData[$key] = $val;
			}
		}
		// add user's contact info to post
		$postData['requesterName'] = $firstName.' '.$lastName;
		$postData['requesterEmail'] = $email;
		$postData['requesterPhone'] = $phone;
		$postData['requesterRole'] = $role;

		if (!empty($apis) || !empty($fieldsetApis)) {
			$postData['technologyType'][] = 'API';
		}
		if (!empty($tables)) {
			$postData['technologyType'][] = 'Data Warehouse';
		}
		if (!empty($samlResponses)) {
			$postData['technologyType'][] = 'SAML';
		}
		if (!isset($postData['technologyType']) || !in_array('SAML', $postData['technologyType'])) {
			if (strpos($this->request->data['descriptionOfInformation'], 'SAML') !== false) {
				$postData['technologyType'][] = 'SAML';
			}
		}

		$requiredTermsString = Configure::read('Collibra.requiredTermsString');
		$requiredElementsString = Configure::read('Collibra.requiredElementsString');
		$additionalTermsString = Configure::read('Collibra.additionalTermsString');
		$additionalElementsString = Configure::read('Collibra.additionalElementsString');
		$postData[$requiredTermsString] = !empty($businessTermIds) ? array_unique($businessTermIds) : '';
		$postData[$requiredElementsString] = !empty($dataAssetIds) ? array_unique($dataAssetIds) : '';
		if (!empty($additionalTermsString)) {
			$postData[$additionalTermsString] = array_unique(array_diff($addBusinessTermIds, $businessTermIds));
		}
		if (!empty($additionalElementsString)) {
			$postData[$additionalElementsString] = array_unique($addDataAssetIds);
		}
		// Collibra requires "additionalTerms" and "additionalElements" fields
		// to exist, even if empty, but http_build_query throws out fields if
		// null or empty array. So we'll put a blank space in, which
		// http_build_query will not throw away
		if (empty($postData[$additionalTermsString])) {
			$postData[$additionalTermsString] = '';
		}
		if (empty($postData[$additionalElementsString])) {
			$postData[$additionalElementsString] = '';
		}

		$postData['api'] = [];
		$postData['tables'] = [];
		$postData['saml'] = [];
		foreach ($apis as $apiHost => $apiPaths) {
			foreach ($apiPaths as $apiPath => $ignore) {
				$apiObject = $this->CollibraAPI->getApiObject($apiHost, $apiPath);
				array_push($postData['api'], $apiObject->id);
			}
		}
		foreach ($fieldsetApis as $apiHost => $apiPaths) {
			foreach ($apiPaths as $apiPath => $ignore) {
				$apiObject = $this->CollibraAPI->getApiObject($apiHost, $apiPath);
				array_push($postData['api'], $apiObject->id);
			}
		}
		foreach ($tables as $tableName => $_) {
			$databaseName = substr($tableName, 0, strpos($tableName, '>') - 1);
			$schemaAndTableNameOnly = substr($tableName, strpos($tableName, '>') + 2);
			$tableObject = $this->CollibraAPI->getTableObject($databaseName, $schemaAndTableNameOnly);
			array_push($postData['tables'], $tableObject->id);
		}
		foreach ($samlResponses as $responseName => $_) {
			$responseObject = $this->CollibraAPI->getSamlResponseObject($responseName);
			array_push($postData['saml'], $responseObject->id);
		}

		//See above comment regarding "additionalTerms"
		if (empty($postData['api'])) {
			$postData['api'] = '';
		}
		if (empty($postData['tables'])) {
			$postData['tables'] = '';
		}
		if (empty($postData['saml'])) {
			$postData['saml'] = '';
		}

		$formResp = $this->CollibraAPI->post(
			'workflow/'.Configure::read('Collibra.workflow.intakeDSR').'/start',
			$this->Collibra->preparePostData($postData)
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

		if ($parent) {
			for ($i = 0; $i < sizeof($asset->dsas); $i++) {
				list($asset->dsas[$i]->attributes, $asset->dsas[$i]->collaborators) = $this->CollibraAPI->getAttributes($asset->dsas[$i]->dsaId);
				$asset->dsas[$i]->roles = $this->CollibraAPI->getResponsibilities($asset->dsas[$i]->dsaVocabularyId);
				$resp = $this->CollibraAPI->get('term/'.$asset->dsas[$i]->dsaId.'/attachments');
				$resp = json_decode($resp);
				$asset->dsas[$i]->attachments = $resp->attachment;
			}
		}

		$this->Collibra->cleanEdits($asset, $parent);

		if (!empty($arrChangedAttrIds)) {
			// Here update all the attributes Collibra inserted HTML tags into
			$resp = $this->CollibraAPI->post(
				'workflow/'.Configure::read('Collibra.workflow.changeAttributes').'/start',
				$this->Collibra->preparePostData(['attributes' => $arrChangedAttrIds, 'values' => $arrChangedAttrValues]));
		}

		$this->set(compact('netID', 'asset', 'parent', 'expand'));
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

		if ($parent) {
			for ($i = 0; $i < sizeof($asset->dsas); $i++) {
				list($asset->dsas[$i]->attributes, $asset->dsas[$i]->collaborators) = $this->CollibraAPI->getAttributes($asset->dsas[$i]->dsaId);
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

		// make sure terms have been added to the user's queue
		$arrQueue = $this->Session->read('queue');
		if(
			empty($arrQueue['apiFields']) &&
			empty($arrQueue['dbColumns']) &&
			empty($arrQueue['samlFields']) &&
			empty($arrQueue['businessTerms']) &&
			empty($arrQueue['emptyApis'])
		) {
			header('location: /search');
			exit;
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
				'Sponsor Name' => 'sponsorName',
				'Sponsor Phone' => 'sponsorPhone',
				'Sponsor Role' => 'sponsorRole',
				'Sponsor Email' => 'sponsorEmail',
				'Requesting Organization' => 'requestingOrganization',
				'Development Shop Id' => 'developmentShopId',
				'Development Shop Name' => 'developmentShop',
				'Application or Project Id' => 'applicationOrProjectId',
				'Application or Project Name' => 'applicationOrProjectName',
				'Additional Information Requested' => 'descriptionOfInformation',
				'Scope and Control' => 'scopeAndControl',
				'Necessity of Data' => 'necessityOfData',
				'Description of Application or Project' => 'descriptionOfApplicationOrProject',
				'Read-write Access' => 'readWriteAccess'
			];
			foreach ($arrLabelMatch as $signifier => $label) {
				if (!empty($draft->attributes[$signifier])) {
					$preFilled[$label] = html_entity_decode($draft->attributes[$signifier]->attrValue);
				}
			}
			if (!empty($draft->collaborators)) {
				$preFilled['requesterNetId'] = $draft->collaborators[0]->attrInfo->attrValue;
			}
		}

		$termResp = $this->CollibraAPI->getBusinessTermDetails($arrQueue['businessTerms']);
		$this->set('termDetails', $termResp);
		// If a business term in the cart has been deleted in Collibra, remove from cart
		if (!empty($termResp)) {
			foreach ($termResp as $term) {
				$termNames[] = $term->termsignifier;
			}
		}
		foreach ($arrQueue['businessTerms'] as $termID => $term) {
			if (!in_array($term['term'], $termNames)) {
				unset($arrQueue['businessTerms'][$termID]);
				$this->Session->write('queue', $arrQueue);
			}
		}

		$policies = [];
		$allPolicies = $this->CollibraAPI->getPolicies();
		foreach ($allPolicies as $policy) {
			switch($policy->id) {
				case Configure::read('Collibra.policy.standardDataUsagePolicies'):
					array_push($policies, $policy);
					break;
				case Configure::read('Collibra.policy.trustedPartnerSecurityStandards'):
					foreach ($arrQueue['businessTerms'] as $term) {
						if ($term['communityId'] == Configure::read('Collibra.community.academicRecords')) {
							array_push($policies, $policy);
							break;
						}
					}
					break;
				default:
					break;
			}
		}

		// load form fields for ISA workflow
		$formResp = $this->CollibraAPI->get('workflow/'.Configure::read('Collibra.workflow.intakeDSR').'/form/start');
		$formResp = json_decode($formResp);
		$this->set('formFields', $formResp);

		$netId = $this->Auth->user('username');
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
		if(isset($byuUser->contact_information->work_email_address)) {
			$psEmail = $byuUser->contact_information->work_email_address;
		} else if(isset($byuUser->contact_information->email)){
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

		$developmentShops = $this->CollibraAPI->getDevelopmentShopDetails('');
		usort($developmentShops, function($a, $b) {
			return strcmp(strtolower($a->name), strtolower($b->name));
		});

		$this->set(compact('preFilled', 'arrQueue', 'netId', 'psName', 'psPhone', 'psEmail', 'psRole', 'psDepartment', 'psReportsToName', 'supervisorInfo', 'policies', 'developmentShops'));
		$this->set('submitErr', isset($this->request->query['err']));
	}
}
