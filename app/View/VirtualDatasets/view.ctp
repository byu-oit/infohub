<?php
	$this->Html->css('secondary', null, ['inline' => false]);
	$this->Html->css('search', null, ['inline' => false]);
?>
<script>
	$(document).ready(function() {
		$("#browse-tab").addClass('active');

		$('input.container').change(function() {
			var thisElem = $(this);
			var checkedTable = thisElem.data('name');
			thisElem.closest('tbody').find('input.chk').each(function() {
				if (checkedTable == $(this).data('tableName')) {
					$(this).prop('checked', thisElem.prop('checked'));
				}
			});
		});
	});

	function displayPendingApproval(elem) {
		$('#searchResults').append('<div id="pendingApprovalMessage">The classification of this element is pending approval.</div>');
		$('#pendingApprovalMessage').offset({top:$(elem).offset().top - 45, left:$(elem).offset().left - 77});
	}

	function hidePendingAproval() {
		$('#pendingApprovalMessage').remove();
	}

	function toggleContainerCollapse(elem) {
		var $elem = $(elem);
		var collapsing = !$elem.data('collapsed');
		var collapsingTable = $elem.closest('tr').data('name');
		$elem.closest('tbody').find('tr').each(function() {
				var $this = $(this);
				if (collapsingTable == $this.data('tableName')) {
					if (collapsing) {
						$this.css('display', 'none');
					} else {
						$this.css('display', 'table-row');
					}
				}
		});

		$elem.data('collapsed', collapsing);
		$elem.toggleClass('collapsed');
	}

	function toggleContainerCollapseAll(collapsing) {
		$('table.dataset-columns').find('a.container-collapse').each(function() {
			if ($(this).data('collapsed') != collapsing) {
				$(this).click();
			}
		});
	}
</script>
<style type="text/css">
	table.dataset-columns tr:hover {
		background-color: #eee;
	}
	table.dataset-columns tr.header:hover {
		background-color: inherit;
	}
</style>
<div id="apiBody" class="innerDataSet">
	<div id="searchResults">
		<h1 class="headerTab"><?= $dataset->datasetName ?></h1>
		<div class="clear"></div>		
		<div class="btnLinks">
			<?php if ($matchAuthorized): ?>
				<div style="float: right">
				<div style="position: absolute">
								<?php if(strpos($_SERVER['HTTP_HOST'],'dev') === false) : ?>
									<a href="https://support.byu.edu/ih?id=update_virtual_dataset&datasetId=<?=$dataset->datasetId?>" target="_blank">
										<span>
												<input type="button" value="Service Portal IH Beta">
										</span>
									</a> 
								<?php else : ?>
									<a href="https://support-test.byu.edu/ih?id=update_virtual_dataset&datasetId=<?=$dataset->datasetId?>" target="_blank">
										<span>
												<input type="button" value="Service Portal IH Beta">
										</span>
									</a>
								<?php endif; ?>
								</div>
					<?= $this->Html->link(
						'Update Unlinked Columns',
						array_merge(['controller' => 'virtual_dataset_admin', 'action' => 'update', $dataset->datasetId]),
						['class' => 'inputButton dataset', 'id' => 'admin']) ?>
				</div>
			<?php endif ?>
		</div>
		<div class="usageNotes">
			<?= $dataset->usageNotes ?>
		</div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem">
				<a class="container-btn grow" onclick="toggleContainerCollapseAll(true)">Collapse All</a><a class="container-btn grow" onclick="toggleContainerCollapseAll(false)">Expand All</a>
				<input type="button" data-datasetName="<?= h($dataset->datasetName) ?>" data-datasetId="<?= h($dataset->datasetId) ?>" api="false" onclick="addToQueueVirtualDataset(this, true)" class="requestAccess grow mainRequestBtn topBtn" value="Add To Request">
				<table class="dataset-columns checkBoxes view">
					<tr class="header">
						<th></th>
						<th><input type="checkbox" onclick="toggleAllCheckboxes(this)" name="toggleCheckboxes"/></th>
						<th class="fieldColumn">Column</th>
						<th class="termColumn">Business Term</th>
						<th class="classificationColumn">Classification</th>
						<th class="glossaryColumn">Glossary</th>
					</tr>
					<?php
					foreach ($dataset->tables as $table) {
						$this->VirtualDataset->printTableView($table);
					}
					?>
				</table>
				<input type="button" data-datasetName="<?= h($dataset->datasetName) ?>" data-datasetId="<?= h($dataset->datasetId) ?>" api="false" onclick="addToQueueVirtualDataset(this, true)" class="requestAccess grow mainRequestBtn topBtn" value="Add To Request">
			</div>
		</div>
	</div>
</div>
