<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('search', null, array('inline' => false));
?>
<div id="searchBody" class="innerLower">
	<div id="searchResults">
		<h1 class="headerTab"><?= $schema->schemaName ?></h1>
		<div class="clear"></div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem">
				<?php if (empty($schema->tables)): ?>
					No tables found
				<?php else: ?>
					<ul>
						<?php foreach ($schema->tables as $table): ?>
							<li><?= $this->Html->link($table->tableName, ['action' => 'view', $schema->schemaName, $table->tableName]) ?></li>
						<?php endforeach ?>
					</ul>
				<?php endif ?>
			</div>
		</div>
	</div>
</div>
