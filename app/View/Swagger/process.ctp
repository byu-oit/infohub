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
						<input type="hidden" name="data[Api][elements][<?=$index?>][type]" value="fieldset" id="ApiElements<?=$index?>Type"/>
					</td>
					<td>
						<input type="hidden" name="data[Api][elements][<?=$index?>][business_term]" id="origTerm<?=$index?>"/>
						<div class="input select">
							<?php if (empty($element['businessTerm'])): ?>
								<select name="data[Api][elements][<?=$index?>][business_term]" class="bt-select" data-index="<?=$index?>" id="ApiElements<?=$index?>BusinessTerm">
								</select>
							<?php else: ?>
								<select name="data[Api][elements][<?=$index?>][business_term]" class="bt-select" data-index="<?=$index?>" id="ApiElements<?=$index?>BusinessTerm" data-orig-term="<?=$element['businessTerm'][0]->termId?>">
									<option value="<?=$element['businessTerm'][0]->termId?>" title="<?=$element['businessTerm'][0]->termCommunityName?>"><?=$element['businessTerm'][0]->term?></option>
								</select>
							<?php endif ?>
						</div>
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
