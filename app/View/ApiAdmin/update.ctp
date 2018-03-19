<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('search', null, array('inline' => false));
	$this->Html->css('account', null, array('inline' => false));
?>
<script type="text/javascript" src="/js/jquery.serialize-object.min.js"></script>
<script>

	function chunkPostData() {
		var i = 0;
		var loadingTexts = ['Working on it   ','Working on it.  ','Working on it.. ','Working on it...'];
		var loadingTextInterval = setInterval(function() {
			$('.update-submit').html(loadingTexts[i]);
			i++;
			if (i == loadingTexts.length) i = 0;
		}, 250);

		if (!validateForm()) {
			alert('You must propose a name and definition for each business term selected.');
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

			var chunkedElements = [];
			var i;
			for (i = 0; i < numElements - 99; i += 100) {
				chunkedElements.push(postData.data.Api.elements.slice(i, i+100))
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

			window.location.href = '/apis/'+host+path;
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

			if (!thisElem.find('.bt-new-name').val() || !thisElem.find('.bt-new-definition').val()) {
				valid = false;
				return false;
			}
		});
		return valid;
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
		<div class="apiHelp" style="cursor:default;">Can't find a matching business term? Check the "New" box to propose a new one.<br>Highlighted rows are automatic suggestions. Be sure to review these before submitting.</div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem">
				<?= $this->Form->create('Api', ['id' => 'apiForm']) ?>
					<?= $this->Form->input('host', ['type' => 'hidden']) ?>
					<?= $this->Form->input('basePath', ['type' => 'hidden']) ?>
					<table class="api-terms">
						<tr class="header">
							<th>Field</th>
							<th>Business Term</th>
							<th>New</th>
							<th>Glossary</th>
							<th>Definition</th>
						</tr>
						<?php
						$index = 0;
						foreach ($terms as $term) {
							$this->Fieldset->printApiAdminUpdate($term, $index, $glossaries);
						} ?>
					</table>
					<a class="lower-btn grow" href="/apis/<?=$hostname.$basePath?>">Cancel</a>
					<div class="update-submit grow" onclick="chunkPostData()">Save</div>
				<?= $this->Form->end() ?>
			</div>
		</div>
	</div>
</div>
<?= $this->element('api_match') ?>
