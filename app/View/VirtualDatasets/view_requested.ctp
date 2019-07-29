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
		$('table.dataset-columns').find('a.container-collapse').each(function() {
			if ($(this).data('collapsed') != collapsing) {
				$(this).click();
			}
		});
	}
</script>
<style type="text/css">
	table.dataset-columns tr:hover {
		background-color: #eee
	}
	table.dataset-columns tr.header:hover {
		background-color: inherit;
	}
</style>
<div id="apiBody" class="innerDataSet">
	<div id="searchResults">
		<h1 class="headerTab"><?= $dataset->datasetName ?></h1>
		<div class="clear" style="height:20px;"></div>
        <h2 class="headerTab">Requested in <?= $this->Html->link($request->assetName, array_merge(['controller' => 'request', 'action' => 'view', $request->id])) ?></h2>
        <div class="clear"></div>
        <div class="apiHelp">Requested fields are highlighted.</div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem">
				<a class="container-btn grow" onclick="toggleContainerCollapseAll(true)">Collapse All</a><a class="container-btn grow" onclick="toggleContainerCollapseAll(false)">Expand All</a>
				<table class="dataset-columns checkBoxes view">
					<tr class="header">
						<th></th>
						<th class="fieldColumn">Column</th>
						<th class="termColumn">Business Term</th>
						<th class="classificationColumn">Classification</th>
						<th class="glossaryColumn">Glossary</th>
					</tr>
					<?php
					foreach ($dataset->tables as $table) {
						$this->VirtualDataset->printTableViewRequested($table, $requestedAssetIds);
					}
					?>
				</table>
			</div>
		</div>
	</div>
</div>
