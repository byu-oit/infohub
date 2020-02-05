<?php
	$this->Html->css('search', null, ['inline' => false]);
?>
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
		<?= $this->Form->input('authorizedByFieldset', ['type' => 'hidden']) ?>
		<?= $this->Form->input('version') ?>
		<?= $this->Form->input('destructiveUpdate', ['type' => 'hidden']) ?>
		<table class="swagger">
			<tr>
				<th>Field</th>
				<th width="20%">Business Term</th>
				<th width="1%">Glossary</th>
			</tr>
			<?php foreach ($this->request->data['Api']['elements'] as $index => $element): ?>
				<tr>
					<td>
						<div class="input text">
							<?php if (empty($element['businessTerm'])): ?>
								<input name="data[Api][elements][<?=$index?>][name]" class="data-label" data-index="<?=$index?>" value="<?=$element['name']?>" type="text" id="ApiElements<?=$index?>Name"/>
							<?php else: ?>
								<input name="data[Api][elements][<?=$index?>][name]" class="data-label" data-index="<?=$index?>" value="<?=$element['name']?>" type="text" id="ApiElements<?=$index?>Name" data-pre-linked="true" data-orig-context="<?=$element['businessTerm'][0]->termCommunityName?>" data-orig-id="<?=$element['businessTerm'][0]->termId?>" data-orig-name="<?=$element['businessTerm'][0]->term?>"/>
							<?php endif ?>
						</div>
						<input type="hidden" name="data[Api][elements][<?=$index?>][type]" value="<?=$element['type']?>" id="ApiElements<?=$index?>Type"/>
					</td>
					<td>
						<?php if (empty($element['businessTerm'])): ?>
							<input type="hidden" name="data[Api][elements][<?=$index?>][business_term]" class="bt" data-index="<?=$index?>" id="ApiElements<?=$index?>BusinessTerm">
							<div class="term-wrapper display-loading" id="ApiElements<?=$index?>SearchCell">
								<input type="text" class="bt-search" data-index="<?=$index?>" placeholder="Search for a term"></input>
								<div class="selected-term"><span class="term-name"></span>  <span class="edit-opt" data-index="<?=$index?>" title="Select new term"></span></div>
								<div class="loading">Loading...</div>
							</div>
						<?php else: ?>
							<input type="hidden" name="data[Api][elements][<?=$index?>][business_term]" value="<?=$element['businessTerm'][0]->termId?>" class="bt" data-index="<?=$index?>" id="ApiElements<?=$index?>BusinessTerm" data-orig-term="<?=$element['businessTerm'][0]->termId?>">
							<div class="term-wrapper" id="ApiElements<?=$index?>SearchCell">
								<input type="text" class="bt-search" data-index="<?=$index?>" placeholder="Search for a term"></input>
								<div class="selected-term"><span class="term-name"><?=$element['businessTerm'][0]->term?></span>  <span class="edit-opt" data-index="<?=$index?>" title="Select new term"></span></div>
								<div class="loading">Loading...</div>
							</div>
						<?php endif ?>
					</td>
					<td class="view-context<?= $index ?>" style="white-space: nowrap"></td>
				</tr>
			<?php endforeach ?>
		</table>
		<?= $this->Form->submit() ?>
	<?= $this->Form->end() ?>
</div>
<?= $this->element('api_match') ?>
