<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('search', null, array('inline' => false));
	$this->Html->css('account', null, array('inline' => false));
?>
<script>

	function proposeRedirect() {
		$('#apiForm').find('input[id=propose]').val('true');
		$('#apiForm').find('input:submit').click();
	}

</script>
<style type="text/css">
	table.api-terms td {
		padding-bottom: 0.5em;
	}
	table.api-terms tr:hover {
		background-color: #eee
	}
	table.api-terms tr.header:hover {
		background-color: inherit;
	}
	.resultItem #apiForm .lower-btn {
	  top: 25px !important;
	  text-decoration: none;
	}
</style>
<div id="apiBody" class="innerLower">
	<div id="searchResults">
		<h1 class="headerTab"><?= $hostname . '/' . trim($basePath, '/') ?></h1>
		<div class="clear"></div>
		<div class="apiHelp" style="cursor:default;">Can't find a matching business term? Hit the button at the bottom to propose a new one.</div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem">
				<?= $this->Form->create('Api', ['id' => 'apiForm']) ?>
					<?= $this->Form->input('host', ['type' => 'hidden']) ?>
					<?= $this->Form->input('basePath', ['type' => 'hidden']) ?>
					<table class="api-terms">
						<tr class="header">
							<th>Field</th>
							<th>Business Term</th>
							<th>Glossary</th>
							<th>Definition</th>
						</tr>
						<?php foreach ($terms as $index => $term): ?>
							<tr>
								<td><?php
									$termPath = explode('.', $term->name);
									foreach ($termPath as $pathStep) {
										if ($pathStep != end($termPath)) {
											echo str_repeat('&nbsp;', 6);
										} else {
											echo $pathStep;
										}
									}
								?></td>
								<td>
									<?php if (empty($term->businessTerm[0])): ?>
										<input type="hidden" name="data[Api][elements][<?=$index?>][id]" value="<?=$term->id?>" id="ApiElements<?=$index?>Id">
										<input type="hidden" name="data[Api][elements][<?=$index?>][name]" class="data-label" data-index="<?=$index?>" value="<?=$term->name?>" id="ApiElements<?=$index?>Name">
										<div class="input select">
											<select name="data[Api][elements][<?=$index?>][business_term]" class="bt-select" data-index="<?=$index?>" id="ApiElements<?=$index?>BusinessTerm"></select>
										</div>
									<?php else: ?>
										<input type="hidden" name="data[Api][elements][<?=$index?>][id]" value="<?=$term->id?>" id="ApiElements<?=$index?>Id">
										<input type="hidden" name="data[Api][elements][<?=$index?>][name]" class="data-label" data-index="<?=$index?>" value="<?=$term->name?>" id="ApiElements<?=$index?>Name"	data-pre-linked="true" data-orig-context="<?=$term->businessTerm[0]->termCommunityName?>" data-orig-id="<?=$term->businessTerm[0]->termId?>" data-orig-name="<?=$term->businessTerm[0]->term?>" data-orig-def="<?=preg_replace('/"/', '&quot;', $term->businessTerm[0]->termDescription)?>">
										<input type="hidden" name="data[Api][elements][<?=$index?>][previous_business_term]" value="<?=$term->businessTerm[0]->termId?>">
										<input type="hidden" name="data[Api][elements][<?=$index?>][previous_business_term_relation]" value="<?=$term->businessTerm[0]->termRelationId?>">
										<div class="input select">
											<select name="data[Api][elements][<?=$index?>][business_term]" class="bt-select" data-index="<?=$index?>" id="ApiElements<?=$index?>BusinessTerm" data-orig-term="<?=$term->businessTerm[0]->termId?>">
												<option value="<?=$term->businessTerm[0]->termId?>" title="<?=$term->businessTerm[0]->termCommunityName?>"><?=$term->businessTerm[0]->term?></option>
											</select>
									<?php endif ?>
								</td>
								<td class="view-context<?= $index ?>" style="white-space: nowrap"></td>
								<td id="view-definition<?= $index ?>" class="view-definition"></td>
							</tr>
						<?php endforeach ?>
					</table>
					<input type="hidden" id="propose" name="propose" value="false">
					<a class="lower-btn grow" href="javascript:proposeRedirect()">Propose New Business Terms</a>
					<a class="lower-btn grow" href="/apis/<?=$hostname.$basePath?>">Cancel</a>
					<div class="submit">
						<input type="submit" class="grow" value="Save">
					</div>
				<?= $this->Form->end() ?>
			</div>
		</div>
	</div>
</div>
<?= $this->element('api_match') ?>
