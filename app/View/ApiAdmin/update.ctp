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
						<?php
						$index = 0;
						foreach ($terms as $term) {
							$this->Fieldset->printApiAdminUpdate($term, $index);
						} ?>
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
