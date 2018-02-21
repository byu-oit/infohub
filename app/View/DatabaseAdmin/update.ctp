<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('search', null, array('inline' => false));
	$this->Html->css('account', null, array('inline' => false));
?>
<script type="text/javascript" src="/js/jquery.serialize-object.min.js"></script>
<script>

	function chunkPostData() {
		var postData = $('form#tableForm').serializeObject();
		var schema = postData.data.Table.schemaName;
		var tableName = postData.data.Table.tableName;
		var numElements = postData.data.Table.elements.length;

		if (numElements < 150) {

			$.post('/database_admin/update/'+schema+'/'+tableName, postData)
				.done(function(data) {
					data = JSON.parse(data);
					if (!data.success) {
						window.location.reload(true);
					}

					if (postData.propose == "true") {
						window.location.href = '/database_admin/proposeTerms/'+schema+'/'+tableName;
					} else {
						window.location.href = '/databases/view/'+schema+'/'+tableName;
					}
				});

		} else {

			var chunkedElements = [];
			var i;
			for (i = 0; i < numElements - 149; i += 150) {
				chunkedElements.push(postData.data.Table.elements.slice(i, i+150))
			}
			if (i < numElements) {
				chunkedElements.push(postData.data.Table.elements.slice(i, numElements));
			}

			var success = true;
			for (i = 0; i < chunkedElements.length; i++) {
				postData.data.Table.elements = chunkedElements[i];
				$.post('/database_admin/update/'+schema+'/'+tableName, postData)
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
				window.location.href = '/database_admin/proposeTerms/'+schema+'/'+tableName;
			} else {
				window.location.href = '/databases/view/'+schema+'/'+tableName;
			}
		}
	}

	function proposeRedirect() {
		$('#tableForm').find('input[id=propose]').val('true');
		$('#tableForm').find('.update-submit').click();
	}

</script>
<style type="text/css">
	table.table-columns td {
		padding-bottom: 0.5em;
	}
	table.table-columns tr:hover {
		background-color: #eee
	}
	table.table-columns tr.header:hover {
		background-color: inherit;
	}
	.resultItem #tableForm .lower-btn {
	  top: 25px !important;
	  text-decoration: none;
	}
</style>
<div id="apiBody" class="innerLower">
	<div id="searchResults">
		<h1 class="headerTab"><?= $tableName ?></h1>
		<div class="clear"></div>
		<div class="tableHelp" style="cursor:default;">Can't find a matching business term? Hit the button at the bottom to propose a new one.</div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem">
				<?= $this->Form->create('Table', ['id' => 'tableForm']) ?>
					<?= $this->Form->input('schemaName', ['type' => 'hidden']) ?>
					<?= $this->Form->input('tableName', ['type' => 'hidden']) ?>
					<table class="table-columns">
						<tr class="header">
							<th>Column</th>
							<th>Business Term</th>
							<th>Glossary</th>
							<th>Definition</th>
						</tr>
						<?php foreach ($columns as $index => $column): ?>
							<tr>
								<td><?php
									$columnPath = explode(' > ', $column->columnName);
									echo end($columnPath);
								?></td>
								<?php if (empty($column->businessTerm[0])): ?>
									<td>
										<input type="hidden" name="data[Table][elements][<?=$index?>][id]" value="<?=$column->columnId?>" id="TableElements<?=$index?>Id">
										<input type="hidden" name="data[Table][elements][<?=$index?>][name]" class="data-label" data-index="<?=$index?>" value="<?=$column->columnName?>" id="TableElements<?=$index?>Name">
										<input type="hidden" name="data[Table][elements][<?=$index?>][business_term]" class="bt" data-index="<?=$index?>" id="TableElements<?=$index?>BusinessTerm">
										<div class="term-wrapper display-loading" id="TableElements<?=$index?>SearchCell">
											<input type="text" class="bt-search" data-index="<?=$index?>" placeholder="Search for a term"></input>
											<div class="selected-term"><span class="term-name"></span>  <span class="edit-opt" data-index="<?=$index?>" title="Select new term"></span></div>
											<div class="loading">Loading...</div>
										</div>
									</td>
									<td class="view-context<?= $index ?>" style="white-space: nowrap"></td>
									<td id="view-definition<?= $index ?>" class="view-definition"></td>
								<?php else: ?>
									<td>
										<input type="hidden" name="data[Table][elements][<?=$index?>][id]" value="<?=$column->columnId?>" id="TableElements<?=$index?>Id">
										<input type="hidden" name="data[Table][elements][<?=$index?>][name]" class="data-label" data-index="<?=$index?>" value="<?=$column->columnName?>" id="TableElements<?=$index?>Name"	data-pre-linked="true" data-orig-context="<?=$column->businessTerm[0]->termCommunityName?>" data-orig-id="<?=$column->businessTerm[0]->termId?>" data-orig-name="<?=$column->businessTerm[0]->term?>" data-orig-def="<?=preg_replace('/"/', '&quot;', $column->businessTerm[0]->termDescription)?>">
										<input type="hidden" name="data[Table][elements][<?=$index?>][previous_business_term]" value="<?=$column->businessTerm[0]->termId?>">
										<input type="hidden" name="data[Table][elements][<?=$index?>][previous_business_term_relation]" value="<?=$column->businessTerm[0]->termRelationId?>">
										<input type="hidden" name="data[Table][elements][<?=$index?>][business_term]" value="<?=$column->businessTerm[0]->termId?>" class="bt" data-index="<?=$index?>" id="TableElements<?=$index?>BusinessTerm" data-orig-term="<?=$column->businessTerm[0]->termId?>">
										<div class="term-wrapper" id="TableElements<?=$index?>SearchCell">
											<input type="text" class="bt-search" data-index="<?=$index?>" placeholder="Search for a term"></input>
											<div class="selected-term"><span class="term-name"><?=$column->businessTerm[0]->term?></span>  <span class="edit-opt" data-index="<?=$index?>" title="Select new term"></span></div>
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
					<a class="lower-btn grow" href="/databases/view/<?=$schemaName.'/'.$tableName?>">Cancel</a>
					<div class="update-submit grow" onclick="chunkPostData()">Save</div>
				<?= $this->Form->end() ?>
			</div>
		</div>
	</div>
</div>
<?= $this->element('table_match') ?>
