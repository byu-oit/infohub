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
			<h2 class="headerTab">Recently Viewed Spaces</h2>
			<div class="clear"></div>
			<div id="smLower" class="whiteBox">
				<ul class="catalogParent">
					<?php foreach ($recent as $space): ?>
						<li class="catalogItem">
							<?= $this->Html->link($space['spaceName'], ['action' => 'view', $space['spaceId']]) ?>
						</li>
					<?php endforeach ?>
				</ul>
			</div>
		</div>
	<?php endif ?>

	<div id="searchMain" style="padding-top: 35px;">
		<h1 class="headerTab">Select Space</h2>
		<div class="clear"></div>
		<div id="smLower" class="whiteBox">
			<ul class="catalogParent">
				<?php if (empty($spaces)): ?>
					No spaces found
				<?php else: ?>
					<?php $i = 0;
					foreach ($spaces as $space): ?>
						<li id="catalogIndex-<?=$i?>" class="catalogItem" data-name="<?=$space->spaceName?>">
							<?php echo '<a href="/virtualDatasets/view/'.$space->spaceId.'">'.$space->spaceName.'</a>'; ?>
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
