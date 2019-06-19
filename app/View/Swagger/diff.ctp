<?php
	$this->Html->css('secondary', null, ['inline' => false]);
	$this->Html->css('search', null, ['inline' => false]);
?>
<script>
	$(document).ready(function() {
		$("#browse-tab").addClass('active');
	});
</script>
<style type="text/css">
	table.api-fields tr:hover {
		background-color: #eee
	}
	table.api-fields tr.header:hover {
		background-color: inherit;
	}
</style>
<div id="apiBody" class="innerDataSet diff">
	<div id="searchResults">
		<h1 class="headerTab"><?=$newApi['host'].'/'.trim($newApi['basePath'], '/').'/'.$newApi['version']?></h1>
		<div class="clear" style="height:20px;"></div>
        <h2 class="headerTab">Diff view</h2>
        <div class="clear"></div>
		<?php if (!$blockedChange): ?>
			<div class="apiHelp">Please review the changes to this API below.</div>
		<?php else: ?>
			<div class="apiHelpStatus">This dataset is part of an active Data Sharing Request.</div>
			<div class="apiHelpSmlStatus">You can't submit changes to this dataset because it has been requested. Our Data Governance Directors have been notified of the attempted change, and they can manually alter the dataset if necessary.<br/>The difference between the existing API and the provided Swagger is shown below.</div>
		<?php endif ?>
		<div id="srLower" class="whiteBox">
			<div class="resultItem halfWidth">
				<table class="api-fields view">
					<tr class="header">
						<th>Existing Fields</th>
						<th style="white-space:nowrap;">Business Terms</th>
					</tr>
					<?= $oldRows ?>
				</table>
			</div>
			<div class="resultItem halfWidth">
				<table class="api-fields view">
					<tr class="header">
						<th>New Fields</th>
					</tr>
					<?= $newRows ?>
				</table>
			</div>
			<div class="button-wrapper">
				<a class="lower-btn grow" href="/apis/<?=$newApi['host'].'/'.trim($newApi['basePath'], '/').'/'.$newApi['version']?>">Cancel</a>
				<?php if (!$blockedChange): ?>
					<div class="update-submit grow" onclick="window.location.href='/swagger/process?diff=done'">Continue</div>
				<?php endif ?>
			</div>
		</div>
	</div>
</div>
