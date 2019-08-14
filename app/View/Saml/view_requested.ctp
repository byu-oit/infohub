<?php
	$this->Html->css('secondary', null, ['inline' => false]);
	$this->Html->css('search', null, ['inline' => false]);
?>
<script>
	$(document).ready(function() {
		$("#browse-tab").addClass('active');
	});

	function displayPendingApproval(elem) {
		$('#searchResults').append('<div id="pendingApprovalMessage">The classification of this element is pending approval.</div>');
		$('#pendingApprovalMessage').offset({top:$(elem).offset().top - 45, left:$(elem).offset().left - 77});
	}

	function hidePendingAproval() {
		$('#pendingApprovalMessage').remove();
	}
</script>
<style type="text/css">
	table.saml-fields tr:hover {
		background-color: #eee
	}
	table.saml-fields tr.header:hover {
		background-color: inherit;
	}
</style>
<div id="apiBody" class="innerDataSet">
	<div id="searchResults">
		<h1 class="headerTab"><?= $responseName ?></h1>
		<div class="clear" style="height:20px;"></div>
        <h2 class="headerTab">Requested in <?= $this->Html->link($request->assetName, array_merge(['controller' => 'request', 'action' => 'view', $request->id])) ?></h2>
        <div class="clear"></div>
        <div class="samlHelp" style="cursor:initial;">Requested fields are highlighted.</div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem">
				<table class="saml-fields checkBoxes view">
					<tr class="header">
						<th class="fieldColumn">Field</th>
						<th class="termColumn">Business Term</th>
						<th class="classificationColumn">Classification</th>
						<th class="glossaryColumn">Glossary</th>
					</tr>
					<?php foreach ($fields as $field): ?>
						<tr<?php echo in_array($field->fieldId, $requestedAssetIds) ? ' class="requested"' : ''; ?>>
							<td><?= $field->fieldName ?></td>
							<td>
								<?php if (!empty($field->businessTerm[0])): ?>
									<?php $termDef = nl2br(str_replace("\n\n\n", "\n\n", htmlentities(strip_tags(str_replace(['<div>', '<br>', '<br/>'], "\n", $field->businessTerm[0]->termDescription))))); ?>
									<?= $this->Html->link($field->businessTerm[0]->term, ['controller' => 'search', 'action' => 'term', $field->businessTerm[0]->termId]) ?>
									<div onmouseover="showTermDef(this)" onmouseout="hideTermDef()" data-definition="<?=$termDef?>" class="info"><img src="/img/iconInfo.png"></div>
								<?php endif ?>
							</td>
							<td style="white-space:nowrap;">
								<?php if (!empty($field->businessTerm[0])):
									$classification = $field->businessTerm[0]->termClassification;
									switch($classification){
										case 'Public':
										case '1 - Public':
											$classificationTitle = 'Public';
											$classification = 'Public';
											break;
										case 'Internal':
										case '2 - Internal':
											$classificationTitle = 'Internal';
											$classification = 'Internal';
											break;
										case 'Confidential':
										case '3 - Confidential':
											$classificationTitle = 'Confidential';
											$classification = 'Classified';
											break;
										case 'Highly Confidential':
										case '4 - Highly Confidential':
											$classificationTitle = 'Highly Confidential';
											$classification = 'HighClassified';
											break;
										case 'Not Applicable':
										case '0 - N/A':
											$classificationTitle = 'Not Applicable';
											$classification = 'NotApplicable';
											break;
										default:
											$classificationTitle = 'Unspecified';
											$classification = 'NoClassification2';
											break;
									}
									echo '<img class="classIcon" src="/img/icon'.$classification.'.png">&nbsp;'.$classificationTitle;

									if ($field->businessTerm[0]->approvalStatus != 'Approved') {
										echo '&nbsp;&nbsp;<img class="pendingApprovalIcon" src="/img/alert.png" onmouseover="displayPendingApproval(this)" onmouseout="hidePendingAproval()">';
									}
								endif ?>
							</td>
							<td>
								<?php if (!empty($field->businessTerm[0])) {
									echo '<a href="/search/listTerms/'.$field->businessTerm[0]->termVocabularyId.'">'.$field->businessTerm[0]->termCommunityName.'</a>';
								} ?>
							</td>
						</tr>
					<?php endforeach ?>
				</table>
			</div>
		</div>
	</div>
</div>
