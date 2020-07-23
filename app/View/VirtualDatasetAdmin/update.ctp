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
	.resultItem #tableForm .lower-btn {
	  top: 25px !important;
	  text-decoration: none;
	}
</style>
<div id="apiBody" class="innerDataSet">
	<div id="searchResults">
		<h1 class="headerTab"><?= $dataset->datasetName ?></h1>
		<div class="clear"></div>
		<div class="datasetHelp" style="cursor:default;">Can't find a matching business term? Check the "New" box to propose a new one.<br>Highlighted rows are automatic suggestions. Be sure to review these before submitting.</div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem">
				<?= $this->Form->create('Dataset', ['id' => 'tableForm']) ?>
					<?= $this->Form->input('datasetId', ['type' => 'hidden', 'value' => $dataset->datasetId]) ?>
					<table class="dataset-columns">
						<tr class="header">
							<th>Column</th>
							<th>Business Term</th>
							<th>New</th>
							<th>Glossary</th>
							<th>Definition</th>
						</tr>
						<?php
						$index = 0;
						foreach ($dataset->tables as $table) {
							$this->VirtualDataset->printTableUpdate($table, $index, $glossaries);
						}
						?>
					</table>
					<div class="update-submit grow" onclick="chunkPostData()">Save</div>
					<a class="lower-btn grow" href="/virtualDatasets/view/<?=$dataset->datasetId?>">Cancel</a>
				<?= $this->Form->end() ?>
			</div>
			<?php if(strpos($_SERVER['HTTP_HOST'],'dev') === false) : ?>
				<a href="https://support.byu.edu/ih?id=update_virtual_dataset&datasetId=<?=$dataset->datasetId?>" target="_blank">
					<span>
							<input type="button" value="Service Portal IH Beta">
					</span>
				</a> 
			<?php else : ?>
				<a href="https://support-test.byu.edu/ih?id=update_virtual_dataset&datasetId=<?=$dataset->datasetId?>" target="_blank">
					<span>
							<input type="button" value="Service Portal IH Beta">
					</span>
				</a>
			<?php endif; ?>
		</div>
	</div>
</div>
<?= $this->element('dataset_match') ?>

