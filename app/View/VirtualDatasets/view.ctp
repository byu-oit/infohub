<?php
	$this->Html->css('secondary', null, ['inline' => false]);
	$this->Html->css('search', null, ['inline' => false]);
?>
<script>
	$(document).ready(function() {
		$("#browse-tab").addClass('active');

		$('input.container').change(function() {
			var thisElem = $(this);
			var arrContainerPaths = [thisElem.data('name')];
			thisElem.closest('tbody').find('input.chk').each(function() {
				if (arrContainerPaths.includes($(this).data('container-path'))) {
					$(this).prop('checked', thisElem.prop('checked'));
					arrContainerPaths.push($(this).data('name'));
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
		var arrContainerPaths = [$elem.closest('tr').data('name')];
		$elem.closest('tbody').find('tr').each(function() {
				var $this = $(this);
				if (arrContainerPaths.includes($this.data('container-path'))) {
					if (collapsing) {
						$this.data('num-collapsed', $this.data('num-collapsed') + 1);
					} else {
						$this.data('num-collapsed', $this.data('num-collapsed') - 1);
					}

					if ($this.data('num-collapsed') == 0) {
						$this.css('display', 'table-row');
					} else {
						$this.css('display', 'none');
					}

					arrContainerPaths.push($this.data('name'));
				}
		});

		$elem.data('collapsed', collapsing);
		$elem.toggleClass('collapsed');
	}

	function toggleContainerCollapseAll(collapsing) {
		$('table.space-columns').find('a.container-collapse').each(function() {
			if ($(this).data('collapsed') != collapsing) {
				$(this).click();
			}
		});
	}
</script>
<style type="text/css">
	table.space-columns tr:hover {
		background-color: #eee
	}
	table.space-columns tr.header:hover {
		background-color: inherit;
	}
</style>
<div id="apiBody" class="innerDataSet">
	<div id="searchResults">
		<h1 class="headerTab"><?= $space->spaceName ?></h1>
		<div class="clear"></div>
		<div class="btnLinks">
			<?php if ($matchAuthorized): ?>
				<div style="float: right">
					<?= $this->Html->link(
						'Update Unlinked Columns',
						array_merge(['controller' => 'virtual_dataset_admin', 'action' => 'update', $space->spaceId]),
						['class' => 'inputButton dataset', 'id' => 'admin']) ?>
				</div>
			<?php endif ?>
		</div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem">
				<a class="container-btn grow" onclick="toggleContainerCollapseAll(true)">Collapse All</a><a class="container-btn grow" onclick="toggleContainerCollapseAll(false)">Expand All</a>
				<input type="button" data-spaceName="<?= h($space->spaceName) ?>" data-spaceId="<?= h($space->spaceId) ?>" api="false" onclick="addToQueueVirtualDataset(this, true)" class="requestAccess grow mainRequestBtn topBtn" value="Add To Request">
				<table class="space-columns checkBoxes view">
					<tr class="header">
						<th></th>
						<th><input type="checkbox" onclick="toggleAllCheckboxes(this)" name="toggleCheckboxes"/></th>
						<th class="fieldColumn">Column</th>
						<th class="termColumn">Business Term</th>
						<th class="classificationColumn">Classification</th>
						<th class="glossaryColumn">Glossary</th>
					</tr>
					<?php
					foreach ($space->subfolders as $folder) {
						$this->VirtualDataset->printFolderView($folder);
					}
					foreach ($space->datasets as $dataset) {
						$this->VirtualDataset->printDatasetView($dataset);
					}
					?>
				</table>
				<input type="button" data-spaceName="<?= h($space->spaceName) ?>" data-spaceId="<?= h($space->spaceId) ?>" api="false" onclick="addToQueueVirtualDataset(this, true)" class="requestAccess grow mainRequestBtn topBtn" value="Add To Request">
			</div>
		</div>
	</div>
</div>