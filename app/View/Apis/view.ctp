<?php
	$this->Html->css('secondary', null, ['inline' => false]);
	$this->Html->css('search', null, ['inline' => false]);
?>
<script>
	$(document).ready(function() {
		$("#browse-tab").addClass('active');

		$('input.fieldset').change(function() {
			var thisElem = $(this);
			var arrFieldsetPaths = [thisElem.data('name')];
			thisElem.closest('tbody').find('input.chk').each(function() {
				if (arrFieldsetPaths.includes($(this).data('fieldset-path'))) {
					$(this).prop('checked', thisElem.prop('checked'));
					arrFieldsetPaths.push($(this).data('name'));
				}
			});
		});

	});

	function toggleFieldsetCollapse(elem) {
		var $elem = $(elem);
		var collapsing = !$elem.data('collapsed');
		var arrFieldsetPaths = [$elem.closest('tr').data('name')];
		$elem.closest('tbody').find('tr').each(function() {
				var $this = $(this);
				if (arrFieldsetPaths.includes($this.data('fieldset-path'))) {
					if (collapsing) {
						$this.data('num-collapsed', $this.data('num-collapsed') + 1);
					} else {
						$this.data('num-collapsed', $this.data('num-collapsed') - 1);
					}

					if ($this.data('num-collapsed') == 0) {
						$this.css('display', 'table-row');
					} else {
						$this.css('display', 'none');
					}

					arrFieldsetPaths.push($this.data('name'));
				}
		});

		$elem.data('collapsed', collapsing);
		$elem.toggleClass('collapsed');
	}

	function toggleFieldsetCollapseAll(collapsing) {
		$('table.api-fields').find('a.fieldset-collapse').each(function() {
			if ($(this).data('collapsed') != collapsing) {
				$(this).click();
			}
		});
	}

	<?php if (count($fields) > 2): // Some APIs only have one or two root-level fieldsets, in which case we don't want it collapsed by default ?>
		$(function() {
			if ($('tr', '.api-fields').length > 50) {
				toggleFieldsetCollapseAll(true);
			}
		});
	<?php endif ?>

	function displayPendingApproval(elem) {
		$('#searchResults').append('<div id="pendingApprovalMessage">The classification of this element is pending approval.</div>');
		$('#pendingApprovalMessage').offset({top:$(elem).offset().top - 45, left:$(elem).offset().left - 77});
	}

	function hidePendingAproval() {
		$('#pendingApprovalMessage').remove();
	}
</script>
<style type="text/css">
	table.api-fields tr:hover {
		background-color: #eee
	}
	table.api-fields tr.header:hover {
		background-color: inherit;
	}
</style>
<div id="apiBody" class="innerLower">
	<div id="searchResults">
		<h1 class="headerTab"><?= $hostname . '/' . trim($basePath, '/') ?></h1>
		<div class="clear"></div>
		<div class="btnLinks">
			<a href="https://developer.byu.edu/" id="doc_link" class="inputButton" target="_blank">Read API documentation</a>
			<a href="https://api.byu.edu/store/" id="store_link" class="inputButton" target="_blank">View this API in the store</a>
			<?php if ($isOITEmployee): ?>
				<div style="float: right">
					<?= $this->Html->link(
						'Update Unlinked Fields',
						array_merge(['controller' => 'api_admin', 'action' => 'update', $hostname], explode('/', $basePath)),
						['class' => 'inputButton', 'id' => 'admin']) ?>
				</div>
			<?php endif ?>
		</div>
		<?php if ($apiObject->statusId == Configure::read('Collibra.status.production')): ?>
			<div id="api_help_btn" class="apiHelp">Do you need access to call this API?</div>
			<div id="api_help" class="apiHelpSml">There are potentially two steps you'll need to complete.<br><br>1. Subscribe to the API in the API store (WSO2). This gives you the ability to call the API with your own credentials for self-service. You'll need to create an "application" in WSO2 and subscribe to the each API you are interested in. You must complete this step in the API store. An overview of the process is available in the <a href="https://developer.byu.edu/docs/consume-api/5-minute-quickstart">Developer Portal.<br>https://developer.byu.edu/docs/consume-api/5-minute-quickstart</a><br><br>2. Request elevated access to the API. Many APIs can return more data if you have elevated permissions. Select the elements of the API that you need for your application and click the "Add to request" button. Once you've added all the elements you want access to, submit your request from the shopping cart icon at the top of the page. This will initiate the process of securing approvals (if needed) from the appropriate data stewards. More <a href="https://developer.byu.edu/docs/consume-api/get-elevated-access">detailed instructions</a> for this step are available.<br><a href="https://developer.byu.edu/docs/consume-api/get-elevated-access">https://developer.byu.edu/docs/consume-api/get-elevated-access</a></div>
		<?php elseif ($apiObject->statusId == Configure::read('Collibra.status.testing')): ?>
			<div class="apiHelpStatus">This API is <strong>in testing.</strong></div>
			<div class="apiHelpSmlStatus">It appears in InfoHub only for development purposes and cannot be requested.</div>
		<?php elseif ($apiObject->statusId == Configure::read('Collibra.status.preProduction')): ?>
			<div class="apiHelpStatus">This API is <strong>in pre-production.</strong></div>
			<div class="apiHelpSmlStatus">This API is in InfoHub only for beta testing purposes. If you have not been specifically asked to request this API for testing, it is very unlikely you will be given access.</div>
		<?php elseif ($apiObject->statusId == Configure::read('Collibra.status.deprecated')): ?>
			<div class="apiHelpStatus">This API is <strong>deprecated.</strong></div>
			<div class="apiHelpSmlStatus">Any existing subscriptions to this API will still work, but no new subscriptions can be made, and the API may become inoperational in the future.</div>
		<?php elseif ($apiObject->statusId == Configure::read('Collibra.status.retired')): ?>
			<div class="apiHelpStatus">This API is <strong>retired.</strong></div>
			<div class="apiHelpSmlStatus">This API is no longer operational and exists in InfoHub only for record-keeping purposes.</div>
		<?php endif ?>
		<div id="srLower" class="whiteBox">
			<div class="resultItem">
				<?php if (empty($fields)): ?>
					<h3>Well, this is embarrassing. We haven't yet specified the output fields for this API, but it is functional, and you can still request access to it.</h3>
				<?php else: ?>
					<?php if ($containsFieldset): ?>
						<a class="fieldset-btn grow" onclick="toggleFieldsetCollapseAll(true)">Collapse All</a><a class="fieldset-btn grow" onclick="toggleFieldsetCollapseAll(false)">Expand All</a>
					<?php endif ?>
					<?php if ($apiObject->statusId == Configure::read('Collibra.status.production') || $apiObject->statusId == Configure::read('Collibra.status.preProduction')): ?>
						<input type="button" data-apiHost="<?= h($hostname) ?>" data-apiPath="<?= h(trim($basePath, '/')) ?>" api="<?= empty($fields) ? 'true' : 'false' ?>" onclick="addToQueueAPI(this, true)" class="requestAccess grow mainRequestBtn topBtn" value="Add To Request">
					<?php endif ?>
					<table class="api-fields checkBoxes view">
						<tr class="header">
							<th></th>
							<th><input type="checkbox" onclick="toggleAllCheckboxes(this)" name="toggleCheckboxes"/></th>
							<th class="fieldColumn">Field</th>
							<th class="termColumn">Business Term</th>
							<th>Classification</th>
						</tr>
						<?php foreach ($fields as $field) {
							$this->Fieldset->printApiView($field);
						} ?>
					</table>
				<?php endif ?>
				<?php if ($apiObject->statusId == Configure::read('Collibra.status.production') || $apiObject->statusId == Configure::read('Collibra.status.preProduction')): ?>
					<input type="button" data-apiHost="<?= h($hostname) ?>" data-apiPath="<?= h(trim($basePath, '/')) ?>" api="<?= empty($fields) ? 'true' : 'false' ?>" onclick="addToQueueAPI(this, true)" class="requestAccess grow mainRequestBtn" value="Add To Request">
				<?php endif ?>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	$(document).ready(function () {
		$.get('<?= $this->Html->url(array_merge(['action' => 'deep_links', 'hostname' => $hostname], explode('/', $basePath))) ?>')
			.then(function(response) {
				if (response.link) {
					$('#store_link').attr('href', response.link);
				}
				if (response.name) {
					// Developer Portal URLs have mysteriously stripped underscores from API names.
					// The line below is only a temporary fix until the URLs are corrected.
					var name = response.name.replace(/_/g, '');
					$('#doc_link').attr('href', 'https://developer.byu.edu/api/'+name);
				}
			});
	});
	$('#api_help_btn').click(function() {
		$('#api_help').toggle(500);
	})
</script>
