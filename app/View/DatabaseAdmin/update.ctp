<?php
	$this->Html->css('secondary', null, ['inline' => false]);
	$this->Html->css('search', null, ['inline' => false]);
	$this->Html->css('account', null, ['inline' => false]);
?>
<script type="text/javascript" src="/js/jquery.serialize-object.min.js"></script>
<script>
	$(document).ready(function() {
		$("#browse-tab").addClass('active');

		$('.bt-new-name').focusout(function() {
			checkDuplicate('#'+$(this).closest('tr').attr('id'));
		});

		$('.bt-new-glossary').change(function() {
			checkDuplicate('#'+$(this).closest('tr').attr('id'));
		});

		function checkDuplicate(rowId) {
			var name = $(rowId).find('.bt-new-name').val();
			var glossary = $(rowId).find('.bt-new-glossary').val();
			if (!glossary) {
				return;
			}

			$.getJSON('/search/autoCompleteTerm/1', {q:name}, function(data) {
				for (let i = 0; i < data.length; i++) {
					if (data[i].signifier == name && data[i].context.id == glossary) {
						alert('A business term with this name already exists in this glossary and cannot be duplicated. Check whether the existing term is appropriate for this field. If not, choose a new name for this term.');
						$(rowId).addClass('duplicate');
						$(rowId).data('duplicate', true);
						return;
					}
				}
				$(rowId).removeClass('duplicate');
				$(rowId).data('duplicate', false);
			});
		}
	});

	function chunkPostData() {
		var i = 0;
		var loadingTexts = ['Working on it   ','Working on it.  ','Working on it.. ','Working on it...'];
		var loadingTextInterval = setInterval(function() {
			$('.update-submit').html(loadingTexts[i]);
			i++;
			if (i == loadingTexts.length) i = 0;
		}, 250);

		if (!validateForm()) {
			alert('You must provide a valid name for each new business term proposed.');
			clearInterval(loadingTextInterval);
			return;
		}

		var postData = $('form#tableForm').serializeObject();
		var databaseName = postData.data.Table.databaseName;
		var schemaName = postData.data.Table.schemaName;
		var tableName = postData.data.Table.tableName;
		var numElements = postData.data.Table.elements.length;

		if (numElements < 100) {

			$.post('/database_admin/update/'+databaseName+'/'+schemaName+'/'+tableName, postData)
				.done(function(data) {
					data = JSON.parse(data);
					if (!data.success) {
						window.location.reload(true);
					}
					window.location.href = '/databases/view/'+databaseName+'/'+schemaName+'/'+tableName;
				});

		} else {
			window.postSuccess = true;
			largeTableUpdate(postData)
				.then(function(data) {
					if (!window.postSuccess) {
						window.location.reload(true);
					} else {
						window.location.href = '/databases/view/'+databaseName+'/'+schemaName+'/'+tableName;
					}
				});
		}
	}

	function largeTableUpdate(postData, stride = 100) {
		if (postData.data.Table.elements.length > stride) {
			return new Promise(function(resolve) {
				var postDataElements = postData.data.Table.elements;

				postData.data.Table.elements = postDataElements.slice(0, stride);
				var request = $.post('/database_admin/update/'+postData.data.Table.databaseName+'/'+postData.data.Table.schemaName+'/'+postData.data.Table.tableName, postData)
					.then(function(data) {
						data = JSON.parse(data);
						if (!data.success) {
							window.postSuccess = false;
						}
					});

				postData.data.Table.elements = postDataElements.slice(stride);
				var recur = largeTableUpdate(postData);

				Promise.all([request, recur]).then(() => resolve());
			});
		}
		else {
			return new Promise(function(resolve) {
				$.post('/database_admin/update/'+postData.data.Table.databaseName+'/'+postData.data.Table.schemaName+'/'+postData.data.Table.tableName, postData)
					.then(function(data) {
						data = JSON.parse(data);
						if (!data.success) {
							window.postSuccess = false;
						}
						resolve();
					});
			});
		}
	}

	function validateForm() {
		var valid = true;
		$('tbody tr').each(function() {
			var thisElem = $(this);
			if (!thisElem.find('input:checkbox')) {
				return;
			}
			if (!thisElem.find('input:checkbox').prop('checked')) {
				return;
			}

			if (!thisElem.find('.bt-new-name').val()) {
				valid = false;
				return false;
			}
			if (thisElem.data('duplicate')) {
				valid = false;
				return false;
			}
		});
		return valid;
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
<div id="apiBody" class="innerDataSet">
	<div id="searchResults">
		<h1 class="headerTab"><?= $databaseName.' > '.$tableName ?></h1>
		<div class="clear"></div>
		<div class="tableHelp" style="cursor:default;">Can't find a matching business term? Check the "New" box to propose a new one.<br>Highlighted rows are automatic suggestions. Be sure to review these before submitting.</div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem">
				<?= $this->Form->create('Table', ['id' => 'tableForm']) ?>
					<?= $this->Form->input('databaseName', ['type' => 'hidden']) ?>
					<?= $this->Form->input('schemaName', ['type' => 'hidden']) ?>
					<?= $this->Form->input('tableName', ['type' => 'hidden']) ?>
					<table class="table-columns">
						<tr class="header">
							<th>Column</th>
							<th>Business Term</th>
							<th>New</th>
							<th>Glossary</th>
							<th>Definition</th>
						</tr>
						<?php foreach ($columns as $index => $column): ?>
							<tr id="tr<?=$index?>">
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
											<input type="text" class="bt-search" data-index="<?=$index?>" data-default-search="<?=str_replace('_', ' ', end($columnPath))?>" placeholder="Search for a term"></input>
											<div class="selected-term"><span class="term-name"></span>  <span class="edit-opt" data-index="<?=$index?>" title="Select new term"></span></div>
											<div class="loading">Loading...</div>
										</div>
								<?php else: ?>
									<td>
										<input type="hidden" name="data[Table][elements][<?=$index?>][id]" value="<?=$column->columnId?>" id="TableElements<?=$index?>Id">
										<input type="hidden" name="data[Table][elements][<?=$index?>][name]" class="data-label" data-index="<?=$index?>" value="<?=$column->columnName?>" id="TableElements<?=$index?>Name"	data-pre-linked="true" data-orig-context="<?=$column->businessTerm[0]->termCommunityName?>" data-orig-id="<?=$column->businessTerm[0]->termId?>" data-orig-name="<?=$column->businessTerm[0]->term?>" data-orig-def="<?=preg_replace('/"/', '&quot;', $column->businessTerm[0]->termDescription)?>">
										<input type="hidden" name="data[Table][elements][<?=$index?>][previous_business_term]" value="<?=$column->businessTerm[0]->termId?>">
										<input type="hidden" name="data[Table][elements][<?=$index?>][previous_business_term_relation]" value="<?=$column->businessTerm[0]->termRelationId?>">
										<input type="hidden" name="data[Table][elements][<?=$index?>][business_term]" value="<?=$column->businessTerm[0]->termId?>" class="bt" data-index="<?=$index?>" id="TableElements<?=$index?>BusinessTerm" data-orig-term="<?=$column->businessTerm[0]->termId?>">
										<div class="term-wrapper" id="TableElements<?=$index?>SearchCell">
											<input type="text" class="bt-search" data-index="<?=$index?>" data-default-search="<?=str_replace('_', ' ', end($columnPath))?>" placeholder="Search for a term"></input>
											<div class="selected-term"><span class="term-name"><?=$column->businessTerm[0]->term?></span>  <span class="edit-opt" data-index="<?=$index?>" title="Select new term"></span></div>
											<div class="loading">Loading...</div>
										</div>
								<?php endif ?>
									<input type="text" name="data[Table][elements][<?=$index?>][propName]" class="bt-new-name" id="TableElements<?=$index?>PropName" data-index="<?=$index?>" placeholder="Proposed name for the term"></input>
								</td>
								<td>
									<input type="checkbox" name="data[Table][elements][<?=$index?>][new]" id="TableElements<?=$index?>New" class="new-check" data-index="<?=$index?>">
								</td>
								<td class="glossary-cell">
									<div class="view-context<?=$index?>" style="white-space: nowrap"></div>
									<select name="data[Table][elements][<?=$index?>][propGlossary]" class="bt-new-glossary" id="TableElements<?=$index?>PropGlossary">
										<option value="">Select a glossary</option>
										<option value="">I don't know</option>
											<?php foreach ($glossaries as $glossary) {
												echo '<option value="'.$glossary->glossaryId.'">'.$glossary->glossaryName.'</option>';
											} ?>
									</select>
								</td>
								<td>
									<div id="view-definition<?=$index?>" class="view-definition"></div>
									<textarea name="data[Table][elements][<?=$index?>][propDefinition]" class="bt-new-definition" id="TableElements<?=$index?>PropDefinition" placeholder="Propose a definition for the term" rows="1" style="width:100%;"></textarea>
								</td>
							</tr>
						<?php endforeach ?>
					</table>
					<div class="update-submit grow" onclick="chunkPostData()">Save</div>
					<a class="lower-btn grow" href="/databases/view/<?=$databaseName.'/'.$schemaName.'/'.$tableName?>">Cancel</a>
				<?= $this->Form->end() ?>
			</div>
			<?php if(strpos($_SERVER['HTTP_HOST'],'dev') === false) : ?>
				<a href="https://support.byu.edu/ih?id=update_business_terms&database=<?=$databaseName.'&schema='.$schemaName.'&table='.substr($tableName, strrpos($tableName, " ") + 1)?>">
					<span>
							<input type="button" value="Service Portal IH Beta">
					</span>
				</a> 
			<?php else : ?>
				<a href="https://supoport-test.byu.edu/ih?id=update_business_terms&database=<?=$databaseName.'&schema='.$schemaName.'&table='.substr($tableName, strrpos($tableName, " ") + 1)?>">
					<span>
							<input type="button" value="Service Portal IH Beta">
					</span>
				</a>
			<?php endif; ?>
		</div>
	</div>
</div>
<?= $this->element('table_match') ?>
