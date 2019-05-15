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

		var postData = $('form#datasetForm').serializeObject();
		var datasetId = postData.data.Dataset.datasetId;
		var numElements = postData.data.Dataset.elements.length;

		if (numElements < 100) {

			$.post('/virtualDatasetAdmin/update/'+datasetId, postData)
				.done(function(data) {
					data = JSON.parse(data);
					if (!data.success) {
						window.location.reload(true);
					}
					window.location.href = '/virtualDatasets/view/'+datasetId;
				});

		} else {
			window.postSuccess = true;
			largeDatasetUpdate(postData)
				.then(function(data) {
					if (!window.postSuccess) {
						window.location.reload(true);
					} else {
						window.location.href = '/virtualDatasets/view/'+datasetId;
					}
				});
		}
	}

	function largeDatasetUpdate(postData, stride = 100) {
		if (postData.data.Dataset.elements.length > stride) {
			return new Promise(function(resolve) {
				var postDataElements = postData.data.Dataset.elements;

				postData.data.Dataset.elements = postDataElements.slice(0, stride);
				var request = $.post('/virtualDatasetAdmin/update/'+postData.data.Dataset.datasetId, postData)
					.then(function(data) {
						data = JSON.parse(data);
						if (!data.success) {
							window.postSuccess = false;
						}
					});

				postData.data.Dataset.elements = postDataElements.slice(stride);
				var recur = largeDatasetUpdate(postData);

				Promise.all([request, recur]).then(() => resolve());
			});
		}
		else {
			return new Promise(function(resolve) {
				$.post('/virtualDatasetAdmin/update/'+postData.data.Dataset.datasetId, postData)
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
	table.dataset-columns td {
		padding-bottom: 0.5em;
	}
	table.dataset-columns tr:hover {
		background-color: #eee
	}
	table.dataset-columns tr.header:hover {
		background-color: inherit;
	}
	.resultItem #datasetForm .lower-btn {
	  top: 25px !important;
	  text-decoration: none;
	}
</style>
<div id="apiBody" class="innerDataSet">
	<div id="searchResults">
		<h1 class="headerTab"><?= $dataset->name ?></h1>
		<div class="clear"></div>
		<div class="datasetHelp" style="cursor:default;">Can't find a matching business term? Check the "New" box to propose a new one.<br>Highlighted rows are automatic suggestions. Be sure to review these before submitting.</div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem">
				<?= $this->Form->create('Dataset', ['id' => 'datasetForm']) ?>
					<?= $this->Form->input('datasetId', ['type' => 'hidden']) ?>
					<table class="dataset-columns">
						<tr class="header">
							<th>Column</th>
							<th>Business Term</th>
							<th>New</th>
							<th>Glossary</th>
							<th>Definition</th>
						</tr>
						<?php foreach ($dataset->columns as $index => $column): ?>
							<tr id="tr<?=$index?>">
								<td><?php
									$columnPath = explode(' > ', $column->columnName);
									echo end($columnPath);
								?></td>
								<?php if (empty($column->businessTerm[0])): ?>
									<td>
										<input type="hidden" name="data[Dataset][elements][<?=$index?>][id]" value="<?=$column->columnId?>" id="DatasetElements<?=$index?>Id">
										<input type="hidden" name="data[Dataset][elements][<?=$index?>][name]" class="data-label" data-index="<?=$index?>" value="<?=$column->columnName?>" id="DatasetElements<?=$index?>Name">
										<input type="hidden" name="data[Dataset][elements][<?=$index?>][business_term]" class="bt" data-index="<?=$index?>" id="DatasetElements<?=$index?>BusinessTerm">
										<div class="term-wrapper display-loading" id="DatasetElements<?=$index?>SearchCell">
											<input type="text" class="bt-search" data-index="<?=$index?>" placeholder="Search for a term"></input>
											<div class="selected-term"><span class="term-name"></span>  <span class="edit-opt" data-index="<?=$index?>" title="Select new term"></span></div>
											<div class="loading">Loading...</div>
										</div>
								<?php else: ?>
									<td>
										<input type="hidden" name="data[Dataset][elements][<?=$index?>][id]" value="<?=$column->columnId?>" id="DatasetElements<?=$index?>Id">
										<input type="hidden" name="data[Dataset][elements][<?=$index?>][name]" class="data-label" data-index="<?=$index?>" value="<?=$column->columnName?>" id="DatasetElements<?=$index?>Name"	data-pre-linked="true" data-orig-context="<?=$column->businessTerm[0]->termCommunityName?>" data-orig-id="<?=$column->businessTerm[0]->termId?>" data-orig-name="<?=$column->businessTerm[0]->term?>" data-orig-def="<?=preg_replace('/"/', '&quot;', $column->businessTerm[0]->termDescription)?>">
										<input type="hidden" name="data[Dataset][elements][<?=$index?>][previous_business_term]" value="<?=$column->businessTerm[0]->termId?>">
										<input type="hidden" name="data[Dataset][elements][<?=$index?>][previous_business_term_relation]" value="<?=$column->businessTerm[0]->termRelationId?>">
										<input type="hidden" name="data[Dataset][elements][<?=$index?>][business_term]" value="<?=$column->businessTerm[0]->termId?>" class="bt" data-index="<?=$index?>" id="DatasetElements<?=$index?>BusinessTerm" data-orig-term="<?=$column->businessTerm[0]->termId?>">
										<div class="term-wrapper" id="DatasetElements<?=$index?>SearchCell">
											<input type="text" class="bt-search" data-index="<?=$index?>" placeholder="Search for a term"></input>
											<div class="selected-term"><span class="term-name"><?=$column->businessTerm[0]->term?></span>  <span class="edit-opt" data-index="<?=$index?>" title="Select new term"></span></div>
											<div class="loading">Loading...</div>
										</div>
								<?php endif ?>
									<input type="text" name="data[Dataset][elements][<?=$index?>][propName]" class="bt-new-name" id="DatasetElements<?=$index?>PropName" data-index="<?=$index?>" placeholder="Proposed name for the term"></input>
								</td>
								<td>
									<input type="checkbox" name="data[Dataset][elements][<?=$index?>][new]" id="DatasetElements<?=$index?>New" class="new-check" data-index="<?=$index?>">
								</td>
								<td class="glossary-cell">
									<div class="view-context<?=$index?>" style="white-space: nowrap"></div>
									<select name="data[Dataset][elements][<?=$index?>][propGlossary]" class="bt-new-glossary" id="DatasetElements<?=$index?>PropGlossary">
										<option value="">Select a glossary</option>
										<option value="">I don't know</option>
											<?php foreach ($glossaries as $glossary) {
												echo '<option value="'.$glossary->glossaryId.'">'.$glossary->glossaryName.'</option>';
											} ?>
									</select>
								</td>
								<td>
									<div id="view-definition<?=$index?>" class="view-definition"></div>
									<textarea name="data[Dataset][elements][<?=$index?>][propDefinition]" class="bt-new-definition" id="DatasetElements<?=$index?>PropDefinition" placeholder="Propose a definition for the term" rows="1" style="width:100%;"></textarea>
								</td>
							</tr>
						<?php endforeach ?>
					</table>
					<a class="lower-btn grow" href="/virtualDatasets/view/<?=$dataset->id?>">Cancel</a>
					<div class="update-submit grow" onclick="chunkPostData()">Save</div>
				<?= $this->Form->end() ?>
			</div>
		</div>
	</div>
</div>
<?= $this->element('dataset_match') ?>
