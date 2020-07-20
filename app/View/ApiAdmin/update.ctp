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

		var postData = $('form#apiForm').serializeObject();
		var host = postData.data.Api.host;
		var path = postData.data.Api.basePath;
		var numElements = postData.data.Api.elements.length;

		if (numElements < 100) {

			$.post('/api_admin/update/'+host+path, postData)
				.done(function(data) {
					data = JSON.parse(data);
					if (!data.success) {
						window.location.reload(true);
					}
					window.location.href = '/apis/'+host+path;
				});

		} else {
			window.postSuccess = true;
			largeApiUpdate(postData)
				.then(function(data) {
					if (!window.postSuccess) {
						window.location.reload(true);
					} else {
						window.location.href = '/apis/'+host+path;
					}
				});
		}
	}

	function largeApiUpdate(postData, stride = 100) {
		if (postData.data.Api.elements.length > stride) {
			return new Promise(function(resolve) {
				var postDataElements = postData.data.Api.elements;

				postData.data.Api.elements = postDataElements.slice(0, stride);
				var request = $.post('/api_admin/update/'+postData.data.Api.host+postData.data.Api.basePath, postData)
					.then(function(data) {
						data = JSON.parse(data);
						if (!data.success) {
							window.postSuccess = false;
						}
					});

				postData.data.Api.elements = postDataElements.slice(stride);
				var recur = largeApiUpdate(postData);

				Promise.all([request, recur]).then(() => resolve());
			});
		}
		else {
			return new Promise(function(resolve) {
				$.post('/api_admin/update/'+postData.data.Api.host+postData.data.Api.basePath, postData)
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
	table.api-fields td {
		padding-bottom: 0.5em;
	}
	table.api-fields tr:hover {
		background-color: #eee
	}
	table.api-fields tr.header:hover {
		background-color: inherit;
	}
	.resultItem #apiForm .lower-btn {
	  top: 25px !important;
	  text-decoration: none;
	}
</style>
<div id="apiBody" class="innerDataSet">
	<div id="searchResults">
		<h1 class="headerTab"><?= $hostname . '/' . trim($basePath, '/') ?></h1>
		<div class="clear"></div>
		<div class="apiHelp" style="cursor:default;">Can't find a matching business term? Check the "New" box to propose a new one.<br>Highlighted rows are automatic suggestions. Be sure to review these before submitting.</div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem">
				<?= $this->Form->create('Api', ['id' => 'apiForm']) ?>
					<?= $this->Form->input('host', ['type' => 'hidden']) ?>
					<?= $this->Form->input('basePath', ['type' => 'hidden']) ?>
					<table class="api-fields">
						<tr class="header">
							<th>Field</th>
							<th>Business Term</th>
							<th>New</th>
							<th>Glossary</th>
							<th>Definition</th>
						</tr>
						<?php
						$index = 0;
						foreach ($fields as $field) {
							$this->Fieldset->printApiAdminUpdate($field, $index, $glossaries);
						} ?>
					</table>
					<div class="update-submit grow" onclick="chunkPostData()">Save</div>
					<a class="lower-btn grow" href="/apis/<?=$hostname.$basePath?>">Cancel</a>
				<?= $this->Form->end() ?>
			</div>
			<?php if(strpos($_SERVER['HTTP_HOST'],'dev') === false) : ?>
				<a href="https://support.byu.edu/ih?id=view_api&datasetId=<?=$basePath?>">
					<span>
							<input type="button" value="Service Portal IH Beta">
					</span>
				</a> 
			<?php else : ?>
				<a href="https://support-test.byu.edu/ih?id=view_api&datasetId=<?=$basePath?>">
					<span>
							<input type="button" value="Service Portal IH Beta">
					</span>
				</a>
			<?php endif; ?>
		</div>
	</div>
</div>
<?= $this->element('api_match') ?>
