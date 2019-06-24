<?php
	$this->Html->css('secondary', null, ['inline' => false]);
	$this->Html->css('search', null, ['inline' => false]);
?>
<script>
	$(document).ready(function() {
		$('#browse-tab').addClass('active');

		$('.update-submit').click(function() {
			var i = 0;
			var loadingTexts = ['Processing   ','Processing.  ','Processing.. ','Processing...'];
			var loadingTextInterval = setInterval(function() {
				$('.update-submit').html(loadingTexts[i]);
				i++;
				if (i == loadingTexts.length) i = 0;
			}, 250);

			var database = '<?= $collibraTable->databaseName ?>';
			var schema = '<?= $collibraTable->schemaName ?>';
			var table = '<?= substr($collibraTable->name, strpos($collibraTable->name, '>') + 2) ?>';
			var thisElem = $(this);

			$.post('/databaseAdmin/syncDatabase', {database:database, schema:schema, table:table, diff:'done'})
                .done(function(data) {
                    clearInterval(loadingTextInterval);
                    thisElem.html('Continue');
                    var data = JSON.parse(data);
                    alert(data.message);

                    if (data.redirect) {
                        window.location.href = '/databases/view/'+database+'/'+schema+'/'+schema+' > '+table;
                    }
                });
		});
	});
</script>
<style type="text/css">
	table.table-columns tr:hover {
		background-color: #eee
	}
	table.table-columns tr.header:hover {
		background-color: inherit;
	}
</style>
<div id="apiBody" class="innerDataSet diff">
	<div id="searchResults">
		<h1 class="headerTab"><?= $collibraTable->databaseName.' > '.$collibraTable->name ?></h1>
		<div class="clear" style="height:20px;"></div>
        <h2 class="headerTab">Diff view</h2>
        <div class="clear"></div>
		<?php if (!$blockedChange): ?>
			<div class="tableHelp">Please review the changes to this table below.</div>
		<?php else: ?>
			<div class="tableHelpStatus">This dataset is part of an active Data Sharing Request.</div>
			<div class="tableHelpSmlStatus">You can't submit changes to this dataset because it has been requested. Our Data Governance Directors have been notified of the attempted change, and they can manually alter the dataset if necessary.<br/>The difference between the existing columns and the columns found in the data warehouse is shown below.</div>
		<?php endif ?>
		<div id="srLower" class="whiteBox">
			<div class="resultItem halfWidth">
				<table class="table-columns view">
					<tr class="header">
						<th>Existing Columns</th>
						<th style="white-space:nowrap;">Business Terms</th>
					</tr>
					<?= $oldRows ?>
				</table>
			</div>
			<div class="resultItem halfWidth">
				<table class="table-columns view">
					<tr class="header">
						<th>New Columns</th>
					</tr>
					<?= $newRows ?>
				</table>
			</div>
			<div class="button-wrapper">
				<a class="lower-btn grow" href="/databases/view/<?= $collibraTable->databaseName."/".$collibraTable->schemaName."/".$collibraTable->name ?>">Cancel</a>
				<?php if (!$blockedChange): ?>
					<div class="update-submit grow">Continue</div>
				<?php endif ?>
			</div>
		</div>
	</div>
</div>
