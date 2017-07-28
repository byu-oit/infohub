<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('search', null, array('inline' => false));
	$this->Html->css('account', null, array('inline' => false));
?>
<style type="text/css">
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
							<th>Glossary</th>
						</tr>
						<?php foreach ($terms as $index => $term): ?>
							<tr>
								<td><?= $term->name ?></td>
								<td>
									<?php if (empty($term->businessTerm[0])): ?>
										<input type="hidden" name="data[Api][elements][<?=$index?>][id]" value="<?=$term->id?>" id="ApiElements<?=$index?>Id">
										<input type="hidden" name="data[Api][elements][<?=$index?>][name]" class="data-label" data-index="<?=$index?>" value="<?=$term->name?>" id="ApiElements<?=$index?>Name">
										<div class="input select">
											<select name="data[Api][elements][<?=$index?>][business_term]" class="bt-select" data-index="<?=$index?>" id="ApiElements<?=$index?>BusinessTerm"></select>
										</div>
									<?php else: ?>
										<input type="hidden" name="data[Api][elements][<?=$index?>][id]" value="<?=$term->id?>" id="ApiElements<?=$index?>Id">
										<input type="hidden" name="data[Api][elements][<?=$index?>][name]" class="data-label" data-index="<?=$index?>" value="<?=$term->name?>" id="ApiElements<?=$index?>Name" data-pre-linked="true" data-orig-context="<?=$term->businessTerm[0]->termCommunityName?>" data-orig-id="<?=$term->businessTerm[0]->termId?>" data-orig-name="<?=$term->businessTerm[0]->term?>">
										<input type="hidden" name="data[Api][elements][<?=$index?>][previous_business_term]" value="<?=$term->businessTerm[0]->termId?>">
										<input type="hidden" name="data[Api][elements][<?=$index?>][previous_business_term_relation]" value="<?=$term->businessTerm[0]->termRelationId?>">
										<div class="input select">
											<select name="data[Api][elements][<?=$index?>][business_term]" class="bt-select" data-index="<?=$index?>" id="ApiElements<?=$index?>BusinessTerm" data-orig-term="<?=$term->businessTerm[0]->termId?>">
												<option value="<?=$term->businessTerm[0]->termId?>" title="<?=$term->businessTerm[0]->termCommunityName?>"><?=$term->businessTerm[0]->term?></option>
											</select>
									<?php endif ?>
								</td>
								<td class="view-context<?= $index ?>" style="white-space: nowrap">
									<?php if (!empty($term->businessTerm[0])) echo $term->businessTerm[0]->termCommunityName; ?>
								</td>
							</tr>
						<?php endforeach ?>
					</table>
					<a class="lower-btn grow" href="/api_admin/proposeTerms/<?=$hostname.$basePath?>">Propose New Business Terms</a>
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
