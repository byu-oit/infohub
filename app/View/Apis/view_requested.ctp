<?php
	$this->Html->css('secondary', null, ['inline' => false]);
	$this->Html->css('search', null, ['inline' => false]);
?>
<script>
	$(document).ready(function() {
		$("#browse-tab").addClass('active');
	});

	function toggleFieldsetCollapse(elem) {
		var $elem = $(elem);
		var collapsing = !$elem.data('collapsed');
		var arrFieldsetPaths = [$elem.closest('tr').data('name')];
		$elem.closest('tbody').find('tr').each(function() {
				var $this = $(this);
				if (arrFieldsetPaths.includes($this.data('fieldset-path'))) {
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

					arrFieldsetPaths.push($this.data('name'));
				}
		});

		$elem.data('collapsed', collapsing);
		$elem.toggleClass('collapsed');
	}

	function toggleFieldsetCollapseAll(collapsing) {
		$('table.api-fields').find('a.fieldset-collapse').each(function() {
			if ($(this).data('collapsed') != collapsing) {
				$(this).click();
			}
		});
	}

	function displayPendingApproval(elem) {
		$('#searchResults').append('<div id="pendingApprovalMessage">The classification of this element is pending approval.</div>');
		$('#pendingApprovalMessage').offset({top:$(elem).offset().top - 45, left:$(elem).offset().left - 77});
	}

	function hidePendingAproval() {
		$('#pendingApprovalMessage').remove();
	}
</script>
<style type="text/css">
	table.api-fields tr:hover {
		background-color: #eee
	}
	table.api-fields tr.header:hover {
		background-color: inherit;
	}
</style>
<div id="apiBody" class="innerLower">
	<div id="searchResults">
		<h1 class="headerTab"><?= $hostname . '/' . trim($basePath, '/') ?></h1>
		<div class="clear" style="height:20px;"></div>
        <h2 class="headerTab">Requested in <?= $request->assetName ?></h2>
        <div class="clear"></div>
		<?php if ($apiObject->statusId == Configure::read('Collibra.status.preProduction')): ?>
			<div class="apiHelpStatus">This API is <strong>in pre-production.</strong></div>
			<div class="apiHelpSmlStatus">This API is in InfoHub only for beta testing purposes. If you have not been specifically asked to request this API for testing, it is very unlikely you will be given access.</div>
		<?php elseif ($apiObject->statusId == Configure::read('Collibra.status.deprecated')): ?>
			<div class="apiHelpStatus">This API is <strong>deprecated.</strong></div>
			<div class="apiHelpSmlStatus">Any existing subscriptions to this API will still work, but no new subscriptions can be made, and the API may become inoperational in the future.</div>
		<?php elseif ($apiObject->statusId == Configure::read('Collibra.status.retired')): ?>
			<div class="apiHelpStatus">This API is <strong>retired.</strong></div>
			<div class="apiHelpSmlStatus">This API is no longer operational and exists in InfoHub only for record-keeping purposes.</div>
		<?php endif ?>
        <div class="apiHelp">Requested fields are highlighted.</div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem">
				<?php if ($containsFieldset): ?>
					<a class="fieldset-btn grow" onclick="toggleFieldsetCollapseAll(true)">Collapse All</a><a class="fieldset-btn grow" onclick="toggleFieldsetCollapseAll(false)">Expand All</a>
				<?php endif ?>
				<table class="api-fields checkBoxes view">
					<tr class="header">
						<th></th>
						<th class="fieldColumn">Field</th>
						<th class="termColumn">Business Term</th>
						<th>Classification</th>
					</tr>
					<?php foreach ($fields as $field) {
						$this->Fieldset->printApiViewRequested($field, $requestedAssetIds);
					} ?>
				</table>
			</div>
		</div>
	</div>
</div>
