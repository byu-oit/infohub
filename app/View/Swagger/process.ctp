<style type="text/css">
	table.swagger {
		width: 100%;
	}
	table.swagger td input {
		width: 100%;
	}
	.searchDialog {
		display: none;
	}
	.ui-autocomplete-loading {
		background: white url("<?= $this->Html->url($this->webroot . 'img/ui-anim_basic_16x16.gif') ?>") right center no-repeat;
	}
</style>
<div class="innerLower">
	<?= $this->Form->create('Api', ['id' => 'apiForm']) ?>
		<?= $this->Form->input('host') ?>
		<?= $this->Form->input('basePath') ?>
		<?= $this->Form->input('version') ?>
		<table class="swagger">
			<tr>
				<th>Field</th>
				<th width="1%">Business Term</th>
				<th width="1%">Context</th>
			</tr>
			<?php foreach ($this->request->data['Api']['elements'] as $index => $element): ?>
				<tr>
					<td>
						<?= $this->Form->input("Api.elements.{$index}.name", ['label' => false, 'class' => 'data-label', 'data-index' => $index]) ?>
						<?= $this->Form->input("Api.elements.{$index}.type", ['type' => 'hidden']) ?>
					</td>
					<td>
						<?= $this->Form->input("Api.elements.{$index}.business_term", ['type' => 'hidden', 'id' => "origTerm{$index}"]) ?>
						<?= $this->Form->input("Api.elements.{$index}.business_term", ['label' => false, 'class' => 'bt-select', 'data-index' => $index, 'type' => 'select']) ?>
					</td>
					<td class="view-context<?= $index ?>" style="white-space: nowrap"></td>
					<td class="xview-definition<?= $index ?>"></td>
				</tr>
			<?php endforeach ?>
		</table>
		<?= $this->Form->submit() ?>
	<?= $this->Form->end() ?>
</div>
<?= $this->element('api_match') ?>
