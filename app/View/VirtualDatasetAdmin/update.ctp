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
		var spaceId = postData.data.Space.spaceId;
		var numElements = postData.data.Space.elements.length;

		if (numElements < 100) {

			$.post('/virtualDatasetAdmin/update/'+spaceId, postData)
				.done(function(data) {
					data = JSON.parse(data);
					if (!data.success) {
						window.location.reload(true);
					}
					window.location.href = '/virtualDatasets/view/'+spaceId;
				});

		} else {
			window.postSuccess = true;
			largeSpaceUpdate(postData)
				.then(function(data) {
					if (!window.postSuccess) {
						window.location.reload(true);
					} else {
						window.location.href = '/virtualDatasets/view/'+spaceId;
					}
				});
		}
	}

	function largeSpaceUpdate(postData, stride = 100) {
		if (postData.data.Space.elements.length > stride) {
			return new Promise(function(resolve) {
				var postDataElements = postData.data.Space.elements;

				postData.data.Space.elements = postDataElements.slice(0, stride);
				var request = $.post('/virtualDatasetAdmin/update/'+postData.data.Space.spaceId, postData)
					.then(function(data) {
						data = JSON.parse(data);
						if (!data.success) {
							window.postSuccess = false;
						}
					});

				postData.data.Space.elements = postDataElements.slice(stride);
				var recur = largeSpaceUpdate(postData);

				Promise.all([request, recur]).then(() => resolve());
			});
		}
		else {
			return new Promise(function(resolve) {
				$.post('/virtualDatasetAdmin/update/'+postData.data.Space.spaceId, postData)
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
	table.space-columns td {
		padding-bottom: 0.5em;
	}
	table.space-columns tr:hover {
		background-color: #eee
	}
	table.space-columns tr.header:hover {
		background-color: inherit;
	}
	.resultItem #datasetForm .lower-btn {
	  top: 25px !important;
	  text-decoration: none;
	}
</style>
<div id="apiBody" class="innerDataSet">
	<div id="searchResults">
		<h1 class="headerTab"><?= $space->spaceName ?></h1>
		<div class="clear"></div>
		<div class="datasetHelp" style="cursor:default;">Can't find a matching business term? Check the "New" box to propose a new one.<br>Highlighted rows are automatic suggestions. Be sure to review these before submitting.</div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem">
				<?= $this->Form->create('Space', ['id' => 'datasetForm']) ?>
					<?= $this->Form->input('spaceId', ['type' => 'hidden', 'value' => $space->spaceId]) ?>
					<table class="space-columns">
						<tr class="header">
							<th>Column</th>
							<th>Business Term</th>
							<th>New</th>
							<th>Glossary</th>
							<th>Definition</th>
						</tr>
						<?php
						$index = 0;
						foreach ($space->subfolders as $folder) {
							$this->VirtualDataset->printFolderUpdate($folder, $index, $glossaries);
						}
						foreach ($space->datasets as $dataset) {
							$this->VirtualDataset->printDatasetUpdate($dataset, $index, $glossaries);
						}
						?>
					</table>
					<div class="update-submit grow" onclick="chunkPostData()">Save</div>
					<a class="lower-btn grow" href="/virtualDatasets/view/<?=$space->spaceId?>">Cancel</a>
				<?= $this->Form->end() ?>
			</div>
		</div>
	</div>
</div>
<?= $this->element('space_match') ?>
