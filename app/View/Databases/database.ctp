<?php
	$this->Html->css('secondary', null, ['inline' => false]);
	$this->Html->css('search', null, ['inline' => false]);
?>
<script>
	$(document).ready(function() {
		$("#browse-tab").addClass('active');
	});
</script>

<!-- Background image div -->
<div id="searchBg" class="searchBg">
</div>

<div id="searchBody" class="innerLower tables">

	<?php if (isset($recent)): ?>
		<div id="searchMain" style="padding-top: 35px;">
			<h2 class="headerTab">Recently Viewed Tables</h2>
			<div class="clear"></div>
			<div id="smLower" class="whiteBox">
				<ul class="catalogParent" id="catalogList0" data-level="0">
					<?php foreach ($recent as $tableName):
						$schemaName = rtrim(substr($tableName, 0, strpos($tableName, '>'))); ?>
						<li class="catalogItem">
							<?= $this->Html->link($tableName, ['action' => 'view', $schemaName, $tableName]) ?>
						</li>
					<?php endforeach ?>
				</ul>
			</div>
		</div>
	<?php endif ?>

	<div id="searchMain" style="padding-top: 35px;">
		<h1 class="headerTab"><?=$dbName?></h1>
		<div class="clear"></div>
		<div id="smLower" class="whiteBox">
			<ul class="catalogParent"><?php
				foreach ($schemas as $schema) {
					echo '<li class="catalogItem"><a href="/databases/schema/'.$schema->name.'">'.$schema->name.'</a></li>';
				}
			?></ul>
		</div>
	</div>

	<?php if ($isOITEmployee): ?>
		<div style="padding-top: 35px;">
			<div style="float: right">
				<?= $this->Html->link(
					'Update a Table',
					array_merge(['controller' => 'databaseAdmin', 'action' => 'syncDatabase']),
					['class' => 'btn-db-sync grow', 'id' => 'admin']) ?>
			</div>
		</div>
	<?php endif ?>

</div>
