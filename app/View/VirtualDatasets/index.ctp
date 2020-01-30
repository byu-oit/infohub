<?php
	$this->Html->css('secondary', null, ['inline' => false]);
	$this->Html->css('search', null, ['inline' => false]);
?>

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
					<?php foreach ($recent as $ds): ?>
						<li class="catalogItem">
							<?= $this->Html->link($ds['datasetName'], ['action' => 'view', $ds['datasetId']]) ?>
						</li>
					<?php endforeach ?>
				</ul>
			</div>
		</div>
	<?php endif ?>

	<div id="searchMain" style="padding-top: 35px;">
		<h1 class="headerTab">Select Dataset</h2>
		<div class="clear"></div>
		<div id="smLower" class="whiteBox">
			<?php if ($noEndpoints): ?>
				<ul class="catalogParent">
					No endpoints found
				</ul>
			<?php else: ?>
				<h3 class="headerTab">Production</h3>
				<ul class="catalogParent">
					<?php $i = 0;
					foreach ($sortedDataset[Configure::read('Collibra.status.production')] as $ds): ?>
						<li id="catalogIndex-<?=$i?>" class="catalogItem" data-name="<?=$ds->datasetName?>">
							<?php echo '<a href="/virtualDatasets/view/'.$ds->datasetId.'">'.$ds->datasetName.'</a>'; ?>
						</li>
					<?php $i++;
					endforeach; ?>
				</ul>
				<h3 class="headerTab">Pre-Production</h3>
				<ul class="catalogParent">
					<?php foreach ($sortedDataset[Configure::read('Collibra.status.preProduction')] as $ds): ?>
						<li id="catalogIndex-<?=$i?>" class="catalogItem" data-name="<?=$ds->datasetName?>">
							<?php echo '<a href="/virtualDatasets/view/'.$ds->datasetId.'">'.$ds->datasetName.'</a>'; ?>
						</li>
					<?php $i++;
					endforeach; ?>
				</ul>
				<h3 class="headerTab">Deprecated</h3>
				<ul class="catalogParent">
					<?php foreach ($sortedDataset[Configure::read('Collibra.status.deprecated')] as $ds): ?>
						<li id="catalogIndex-<?=$i?>" class="catalogItem" data-name="<?=$ds->datasetName?>">
							<?php echo '<a href="/virtualDatasets/view/'.$ds->datasetId.'">'.$ds->datasetName.'</a>'; ?>
						</li>
					<?php $i++;
					endforeach; ?>
				</ul>
				<?php endif ?>
		</div>
	</div>
	<?php if ($importAuthorized): ?>
		<div style="padding-top: 35px;">
			<div style="float: right">
				<?= $this->Html->link(
					'Import Virtual Tables',
					array_merge(['controller' => 'virtualDatasetAdmin', 'action' => 'import']),
					['class' => 'btn-db-sync grow', 'id' => 'admin']) ?>
			</div>
		</div>
	<?php endif ?>
</div>
