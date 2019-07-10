<?php
	$this->Html->css('secondary', null, ['inline' => false]);
	$this->Html->css('search', null, ['inline' => false]);
?>

<script>
$(document).ready(function() {
	$('#datasetFilter').focus();
	$('#browse-tab').addClass('active');

	var datasets = [ <?php
		foreach ($datasets as $ds) {
			echo '"'.$ds->datasetName.'",';
		}
	?> ""];
	$('#datasetFilter').on('input', function() {
		var filterValue = $(this).val().toLowerCase();
		for (var i = 0; i < datasets.length; i++) {
			if (!datasets[i].toLowerCase().includes(filterValue)) {
				$('#catalogIndex-'+i).css('display', 'none');
			} else {
				$('#catalogIndex-'+i).css('display', 'block');
			}
		}
	});

	$('#datasetFilter').keypress(function(event) { return event.keyCode != 13; });
	$('#datasetFilter').on({
		keyup: function(e) {
			if (e.which === 13) {
				var filterValue = $(this).val().toLowerCase();
				for (var i = 0; i < datasets.length; i++) {
					if (datasets[i].toLowerCase().includes(filterValue)) {
						window.location.href = window.location.origin+'/virtualDatasets/view/'+$('#catalogIndex-'+i).data('id');
						break;
					}
				}
			}
		}
	});
});
</script>

<!-- Background image div -->
<div id="searchBg" class="searchBg">
</div>

<div id="searchBody" class="innerLower">

	<?php if (isset($recent)): ?>
		<div id="searchMain" style="padding-top: 35px;">
			<h2 class="headerTab">Recently Viewed Datasets</h2>
			<div class="clear"></div>
			<div id="smLower" class="whiteBox">
				<ul class="catalogParent">
					<?php foreach ($recent as $dataset): ?>
						<li class="catalogItem">
							<?= $this->Html->link($dataset['datasetName'], ['action' => 'view', $dataset['datasetId']]) ?>
						</li>
					<?php endforeach ?>
				</ul>
			</div>
		</div>
	<?php endif ?>

	<div id="searchTop">
		<h1 class="headerTab">Filter Datasets</h1>
		<div class="clear"></div>
		<div id="stLower" class="whiteBox">
			<input id="datasetFilter" name="searchInput" type="text" class="inputShade" placeholder="Enter dataset name" maxlength="50" autocomplete="off" style="width: 490px;" />
			<div class="clear"></div>
		</div>
	</div>

	<div id="searchMain" style="padding-top: 35px;">
		<h1 class="headerTab">Select Dataset</h2>
		<div class="clear"></div>
		<div id="smLower" class="whiteBox">
			<ul class="catalogParent">
				<?php if (empty($datasets)): ?>
					No datasets found
				<?php else: ?>
					<?php $i = 0;
					foreach ($datasets as $dataset): ?>
						<li id="catalogIndex-<?=$i?>" class="catalogItem" data-name="<?=$dataset->datasetName?>" data-id="<?=$dataset->datasetId?>">
							<?php echo '<a href="/virtualDatasets/view/'.$dataset->datasetId.'">'.$dataset->datasetName.'</a>'; ?>
						</li>
					<?php $i++;
					endforeach; ?>
				<?php endif ?>
			</ul>
		</div>
	</div>
	<?php if ($importAuthorized): ?>
		<div style="padding-top: 35px;">
			<div style="float: right">
				<?= $this->Html->link(
					'Import Datasets',
					array_merge(['controller' => 'virtualDatasetAdmin', 'action' => 'import']),
					['class' => 'btn-db-sync grow', 'id' => 'admin']) ?>
			</div>
		</div>
	<?php endif ?>
</div>
