<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('search', null, array('inline' => false));
?>
<style type="text/css">
	table.api-terms tr:hover {
		background-color: #eee
	}
	table.api-terms tr.header:hover {
		background-color: inherit;
	}
</style>
<div id="searchBody" class="innerLower">
	<div id="searchResults">
		<h1 class="headerTab"><?= $hostname . '/' . trim($basePath, '/') ?></h1>
		<div class="clear"></div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem">
				<?= $this->Form->create('Api', ['id' => 'apiForm']) ?>
					<?= $this->Form->input('host', ['type' => 'hidden']) ?>
					<?= $this->Form->input('basePath', ['type' => 'hidden']) ?>
					<table class="api-terms">
						<tr class="header">
							<th>Field</th>
							<th>Business Term</th>
							<th>Context</th>
						</tr>
						<?php foreach ($terms as $index => $term): ?>
							<tr>
								<td><?= $term->name ?></td>
								<td>
									<?php if (empty($term->businessTerm[0])): ?>
										<?= $this->Form->input("Api.elements.{$index}.id", ['type' => 'hidden']) ?>
										<?= $this->Form->input("Api.elements.{$index}.name", ['type' => 'hidden', 'class' => 'data-label', 'data-index' => $index]) ?>
										<?= $this->Form->input("Api.elements.{$index}.business_term", ['type' => 'hidden', 'id' => "origTerm{$index}"]) ?>
										<?= $this->Form->input("Api.elements.{$index}.business_term", ['label' => false, 'class' => 'bt-select', 'data-index' => $index, 'type' => 'select']) ?>
									<?php else: ?>
										<?= $this->Html->link($term->businessTerm[0]->term, ['controller' => 'search', 'action' => 'term', $term->businessTerm[0]->termId]) ?>
									<?php endif ?>
								</td>
								<td class="view-context<?= $index ?>" style="white-space: nowrap"></td>
							</tr>
						<?php endforeach ?>
					</table>
					<?= $this->Form->submit('Save') ?>
				<?= $this->Form->end() ?>
			</div>
		</div>
	</div>
</div>
<?= $this->element('api_match') ?>
