<?php
	$this->Html->css('secondary', null, ['inline' => false]);
	$this->Html->css('search', null, ['inline' => false]);
?>

<script>
$(document).ready(function() {
	$('#tableFilter').focus();
	$('#browse-tab').addClass('active');

	var tables = [<?php foreach ($schema->tables as $table) { echo '"'.$table->tableName.'",'; } ?> ""];
	$('#tableFilter').on('input', function() {
		var filterValue = $(this).val().toLowerCase();
		for (var i = 0; i < tables.length; i++) {
			if (!tables[i].toLowerCase().includes(filterValue)) {
				$('#catalogIndex-'+i).css('display', 'none');
			} else {
				$('#catalogIndex-'+i).css('display', 'block');
			}
		}
	});

	$('#tableFilter').keypress(function(event) { return event.keyCode != 13; });
	$('#tableFilter').on({
		keyup: function(e) {
			if (e.which === 13) {
				var filterValue = $(this).val().toLowerCase();
				for (var i = 0; i < tables.length; i++) {
					if (tables[i].toLowerCase().includes(filterValue)) {
						window.location.href = window.location.origin+'/databases/view/<?=$schema->databaseName.'/'.$schema->schemaName?>/'+$('#catalogIndex-'+i).data('name');
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

<div id="searchBody" class="innerLower tables">

	<?php if (isset($recent)): ?>
		<div id="searchMain" style="padding-top: 35px;">
			<h2 class="headerTab">Recently Viewed Tables</h2>
			<div class="clear"></div>
			<div id="smLower" class="whiteBox">
				<ul class="catalogParent">
					<?php foreach ($recent as $table): ?>
						<li class="catalogItem">
							<?= $this->Html->link($table['databaseName'].' > '.$table['tableName'], ['action' => 'view', $table['databaseName'], $table['schemaName'], $table['tableName']]) ?>
						</li>
					<?php endforeach ?>
				</ul>
			</div>
		</div>
	<?php endif ?>

	<div id="searchTop">
		<h1 class="headerTab">Filter Tables</h1>
		<div class="clear"></div>
		<div id="stLower" class="whiteBox">
			<form action="#" onsubmit="document.location='/databases/view/<?=$schema->databaseName.'/'.$schema->schemaName?>/'+this.searchInput.value; return false;" method="post">
				<input id="tableFilter" name="searchInput" type="text" class="inputShade" placeholder="Enter table name" maxlength="50" autocomplete="off" style="width: 490px;" />
			</form>
			<div class="clear"></div>
		</div>
	</div>

	<div id="searchMain">
		<h2 class="headerTab"><a href="/databases/database/<?=$schema->databaseName?>?noredirect=1"><?=$schema->databaseName?></a> > <?=$schema->schemaName?></h2>
		<div class="clear"></div>
		<div id="smLower" class="whiteBox">
			<ul class="catalogParent">
				<?php if (empty($schema->tables)): ?>
					No tables found
				<?php else: ?>
					<ul>
						<?php $i = 0;
						foreach ($schema->tables as $table): ?>
							<li id="catalogIndex-<?=$i?>" class="catalogItem" data-name="<?=$table->tableName?>">
								<?= $this->Html->link($schema->databaseName.' > '.$table->tableName, ['action' => 'view', $schema->databaseName, $schema->schemaName, $table->tableName]) ?>
							</li>
						<?php $i++;
						endforeach; ?>
					</ul>
				<?php endif ?>
			</ul>
		</div>
	</div>

</div>
