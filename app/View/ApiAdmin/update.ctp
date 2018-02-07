<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('search', null, array('inline' => false));
	$this->Html->css('account', null, array('inline' => false));
?>
<script type="text/javascript" src="/js/jquery.serialize-object.min.js"></script>
<script>

	function chunkPostData() {
		var postData = $('form#apiForm').serializeObject();
		var host = postData.data.Api.host;
		var path = postData.data.Api.basePath;
		var numElements = postData.data.Api.elements.length;

		if (numElements < 150) {

			$.post('/api_admin/update/'+host+path, postData)
				.done(function(data) {
					data = JSON.parse(data);
					if (!data.success) {
						window.location.reload(true);
					}

					if (postData.propose == "true") {
						window.location.href = '/api_admin/proposeTerms/'+host+path;
					} else {
						window.location.href = '/apis/'+host+path;
					}
				});

		} else {

			var chunkedElements = [];
			var i;
			for (i = 0; i < numElements - 149; i += 150) {
				chunkedElements.push(postData.data.Api.elements.slice(i, i+150))
			}
			if (i < numElements) {
				chunkedElements.push(postData.data.Api.elements.slice(i, numElements));
			}

			var success = true;
			for (i = 0; i < chunkedElements.length; i++) {
				postData.data.Api.elements = chunkedElements[i];
				$.post('/api_admin/update/'+host+path, postData)
					.done(function(data) {
	            		data = JSON.parse(data);
						if (!data.success) {
							success = false;
						}
					});
			}
			if (!success) {
				window.location.reload(true);
			}

			if (postData.propose == "true") {
				window.location.href = '/api_admin/proposeTerms/'+host+path;
			} else {
				window.location.href = '/apis/'+host+path;
			}
		}
	}

	function proposeRedirect() {
		$('#apiForm').find('input[id=propose]').val('true');
		$('#apiForm').find('.update-submit').click();
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
									for ($i = 0; $i < count($termPath) - 1; $i++) {
										echo str_repeat('&nbsp;', 12);
									}
									echo end($termPath);
								?></td>
								<?php if (empty($term->businessTerm[0])): ?>
									<td>
										<input type="hidden" name="data[Api][elements][<?=$index?>][id]" value="<?=$term->id?>" id="ApiElements<?=$index?>Id">
										<input type="hidden" name="data[Api][elements][<?=$index?>][name]" class="data-label" data-index="<?=$index?>" value="<?=$term->name?>" id="ApiElements<?=$index?>Name">
										<input type="hidden" name="data[Api][elements][<?=$index?>][business_term]" class="bt" data-index="<?=$index?>" id="ApiElements<?=$index?>BusinessTerm">
										<div class="term-wrapper display-loading" id="ApiElements<?=$index?>SearchCell">
											<input type="text" class="bt-search" data-index="<?=$index?>" placeholder="Search for a term"></input>
											<div class="selected-term"><span class="term-name"></span>  <span class="edit-opt" data-index="<?=$index?>" title="Select new term"></span></div>
											<div class="loading">Loading...</div>
										</div>
									</td>
									<td class="view-context<?= $index ?>" style="white-space: nowrap"></td>
									<td id="view-definition<?= $index ?>" class="view-definition"></td>
								<?php else: ?>
									<td>
										<input type="hidden" name="data[Api][elements][<?=$index?>][id]" value="<?=$term->id?>" id="ApiElements<?=$index?>Id">
										<input type="hidden" name="data[Api][elements][<?=$index?>][name]" class="data-label" data-index="<?=$index?>" value="<?=$term->name?>" id="ApiElements<?=$index?>Name"	data-pre-linked="true" data-orig-context="<?=$term->businessTerm[0]->termCommunityName?>" data-orig-id="<?=$term->businessTerm[0]->termId?>" data-orig-name="<?=$term->businessTerm[0]->term?>" data-orig-def="<?=preg_replace('/"/', '&quot;', $term->businessTerm[0]->termDescription)?>">
										<input type="hidden" name="data[Api][elements][<?=$index?>][previous_business_term]" value="<?=$term->businessTerm[0]->termId?>">
										<input type="hidden" name="data[Api][elements][<?=$index?>][previous_business_term_relation]" value="<?=$term->businessTerm[0]->termRelationId?>">
										<input type="hidden" name="data[Api][elements][<?=$index?>][business_term]" value="<?=$term->businessTerm[0]->termId?>" class="bt" data-index="<?=$index?>" id="ApiElements<?=$index?>BusinessTerm" data-orig-term="<?=$term->businessTerm[0]->termId?>">
										<div class="term-wrapper" id="ApiElements<?=$index?>SearchCell">
											<input type="text" class="bt-search" data-index="<?=$index?>" placeholder="Search for a term"></input>
											<div class="selected-term"><span class="term-name"><?=$term->businessTerm[0]->term?></span>  <span class="edit-opt" data-index="<?=$index?>" title="Select new term"></span></div>
											<div class="loading">Loading...</div>
										</div>
									</td>
									<td class="view-context<?= $index ?>" style="white-space: nowrap"></td>
									<td id="view-definition<?= $index ?>" class="view-definition"></td>
								<?php endif ?>
							</tr>
						<?php endforeach ?>
					</table>
					<input type="hidden" id="propose" name="propose" value="false">
					<a class="lower-btn grow" href="javascript:proposeRedirect()">Propose New Business Terms</a>
					<a class="lower-btn grow" href="/apis/<?=$hostname.$basePath?>">Cancel</a>
					<div class="update-submit grow" onclick="chunkPostData()">Save</div>
				<?= $this->Form->end() ?>
			</div>
		</div>
	</div>
</div>
<?= $this->element('api_match') ?>
