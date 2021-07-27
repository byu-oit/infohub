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
	table.table-columns tr:hover {
		background-color: #eee
	}
	table.table-columns tr.header:hover {
		background-color: inherit;
	}
</style>
<div id="apiBody" class="innerDataSet">
	<div id="searchResults">
		<h1 class="headerTab"><a href="/databases/database/<?= $databaseName ?>"><?= $databaseName ?></a> > <a href="/databases/schema/<?= $databaseName.'/'.$schemaName ?>"><?= $schemaName ?></a> > <?= $tableNameOnly ?></h1>
		<div class="clear"></div>		
		<div class="btnLinks">
			<?php if ($matchAuthorized): ?>
				<div style="float: right">
				<div style="position: absolute">
				<?php if(strpos($_SERVER['HTTP_HOST'],'dev') === false) : ?>
				<a href="https://support.byu.edu/ih?id=update_business_terms&database=<?=$databaseName.'&schema='.$schemaName.'&table='.substr($tableName, strrpos($tableName, " ") + 1)?>" target="_blank">
					<span>
							<input type="button" value="Service Portal IH Beta">
					</span>
				</a> 
			<?php else : ?>
				<a href="https://support-test.byu.edu/ih?id=update_business_terms&database=<?=$databaseName.'&schema='.$schemaName.'&table='.substr($tableName, strrpos($tableName, " ") + 1)?>" target="_blank">
					<span>
							<input type="button" value="Service Portal IH Beta">
					</span>
				</a>
			<?php endif; ?>
			</div>
					<?= $this->Html->link(
						'Update Unlinked Columns',
						array_merge(['controller' => 'database_admin', 'action' => 'update', $databaseName, $schemaName, $tableName]),
						['class' => 'inputButton dbTable', 'id' => 'admin']) ?>
				</div>
			<?php endif ?>
		</div>
		<div class="usageNotes">
			<?= $usageNotes ?>
		</div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem">
				<input type="button" data-databaseName="<?= h($databaseName) ?>" data-schemaName="<?= h($schemaName) ?>" data-tableName="<?= h($tableName) ?>" api="false" onclick="addToQueueDBTable(this, true)" class="requestAccess grow mainRequestBtn topBtn" value="Add To Request">
				<table class="table-columns checkBoxes view">
					<tr class="header">
						<th><input type="checkbox" onclick="toggleAllCheckboxes(this)" name="toggleCheckboxes"/></th>
						<th class="fieldColumn">Column</th>
						<th class="termColumn">Business Term</th>
						<th class="classificationColumn">Classification</th>
						<th class="glossaryColumn">Glossary</th>
					</tr>
					<?php foreach ($columns as $column): ?>
						<tr>
							<td>
								<?php if (!empty($column->businessTerm[0])): ?>
									<input
									type="checkbox"
									data-title="<?= h($column->businessTerm[0]->term) ?>"
									data-vocabID="<?= h($column->businessTerm[0]->termCommunityId) ?>"
									value="<?= h($column->businessTerm[0]->termId) ?>"
									class="chk"
									id="chk<?= h($column->businessTerm[0]->termId) ?>"
									data-name="<?= $column->columnName ?>"
									data-column-id="<?= $column->columnId ?>">
								<?php else: ?>
									<input
									type="checkbox"
									data-title="<?= $column->columnName ?>"
									data-vocabID=""
									value=""
									class="chk"
									data-name="<?= $column->columnName ?>"
									data-column-id="<?= $column->columnId ?>">
								<?php endif ?>
							</td>
							<td><?php
								$columnPath = explode(' > ', $column->columnName);
								echo end($columnPath);
							?></td>
							<td>
								<?php if (!empty($column->businessTerm[0])): ?>
									<?php $termDef = nl2br(str_replace("\n\n\n", "\n\n", htmlentities(strip_tags(str_replace(['<div>', '<br>', '<br/>'], "\n", $column->businessTerm[0]->termDescription))))); ?>
									<?= $this->Html->link($column->businessTerm[0]->term, ['controller' => 'search', 'action' => 'term', $column->businessTerm[0]->termId]) ?>
									<div onmouseover="showTermDef(this)" onmouseout="hideTermDef()" data-definition="<?=$termDef?>" class="info"><img src="/img/iconInfo.png"></div>
								<?php endif ?>
							</td>
							<td style="white-space:nowrap;">
								<?php if (!empty($column->businessTerm[0])):
									$classification = $column->businessTerm[0]->termClassification;
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

									if ($column->businessTerm[0]->approvalStatus != 'Approved') {
										echo '&nbsp;&nbsp;<img class="pendingApprovalIcon" src="/img/alert.png" onmouseover="displayPendingApproval(this)" onmouseout="hidePendingAproval()">';
									}
								endif ?>
							</td>
							<td>
								<?php if (!empty($column->businessTerm[0])) {
									echo '<a href="/search/listTerms/'.$column->businessTerm[0]->termVocabularyId.'">'.$column->businessTerm[0]->termCommunityName.'</a>';
								} ?>
							</td>
						</tr>
					<?php endforeach ?>
				</table>
				<input type="button" data-databaseName="<?= h($databaseName) ?>" data-schemaName="<?= h($schemaName) ?>" data-tableName="<?= h($tableName) ?>" api="false" onclick="addToQueueDBTable(this, true)" class="requestAccess grow mainRequestBtn" value="Add To Request">
			</div>
		</div>
	</div>
</div>
