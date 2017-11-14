<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('search', null, array('inline' => false));
?>
<script>
	$(document).ready(function() {

		$('input.fieldset').change(function() {
			var thisElem = $(this);
			thisElem.closest('tbody').find('input.chk').each(function() {
				if ($(this).attr('fieldset').indexOf(thisElem.attr('data-title')) != -1) {
					$(this).prop('checked', thisElem.prop('checked'));
				}
			});
		});

	});

	function displayPendingApproval(elem) {
		$('#searchResults').append('<div id="pendingApprovalMessage">The classification of this element is pending approval.</div>');
		$('#pendingApprovalMessage').offset({top:$(elem).offset().top - 45, left:$(elem).offset().left - 77});
	}

	function hidePendingAproval() {
		$('#pendingApprovalMessage').remove();
	}
</script>
<style type="text/css">
	table.api-terms tr:hover {
		background-color: #eee
	}
	table.api-terms tr.header:hover {
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
						'Update Unlinked Terms',
						array_merge(['controller' => 'api_admin', 'action' => 'update', $hostname], explode('/', $basePath)),
						['class' => 'inputButton', 'id' => 'admin']) ?>
				</div>
			<?php endif ?>
		</div>
		<div id="api_help_btn" class="apiHelp">Do you need access to call this API?</div>
		<div id="api_help" class="apiHelpSml">There are potentially two steps you'll need to complete.<br><br>1. Subscribe to the API in the API store (WSO2). This gives you the ability to call the API with your own credentials for self-service. You'll need to create an "application" in WSO2 and subscribe to the each API you are interested in. You must complete this step in the API store. An overview of the process is available in the <a href="https://developer.byu.edu/docs/consume-api/5-minute-quickstart">Developer Portal.<br>https://developer.byu.edu/docs/consume-api/5-minute-quickstart</a><br><br>2. Request elevated access to the API. Many APIs can return more data if you have elevated permissions. Select the elements of the API that you need for your application and click the "Add to request" button. Once you've added all the elements you want access to, submit your request from the shopping cart icon at the top of the page. This will initiate the process of securing approvals (if needed) from the appropriate data stewards. More <a href="https://developer.byu.edu/docs/consume-api/get-elevated-access">detailed instructions</a> for this step are available.<br><a href="https://developer.byu.edu/docs/consume-api/get-elevated-access">https://developer.byu.edu/docs/consume-api/get-elevated-access</a></div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem">
				<?php if (empty($terms)): ?>
					<h3>Well, this is embarrassing. We haven't yet specified the output fields for this API, but it is functional, and you can still request access to it.</h3>
				<?php else: ?>
					<input type="button" data-apiHost="<?= h($hostname) ?>" data-apiPath="<?= h(trim($basePath, '/')) ?>" api="<?= empty($terms) ? 'true' : 'false' ?>" onclick="addToQueue(this, false, true)" class="requestAccess grow mainRequestBtn topBtn" value="Add To Request">
					<table class="api-terms checkBoxes">
						<tr class="header">
							<th><input type="checkbox" onclick="toggleAllCheckboxes(this)" checked="checked" name="toggleCheckboxes"/></th>
							<th class="fieldColumn">Field</th>
							<th class="termColumn">Business Term</th>
							<th>Classification</th>
						</tr>
						<?php foreach ($terms as $term): ?>

							<tr>
								<td>
									<?php if (!empty($term->businessTerm[0])): ?>
										<input
										type="checkbox"
										name="terms[]"
										data-title="<?= h($term->businessTerm[0]->term) ?>"
										data-vocabID="<?= h($term->businessTerm[0]->termCommunityId) ?>"
										value="<?= h($term->businessTerm[0]->termId) ?>"
										class="chk"
										id="chk<?= h($term->businessTerm[0]->termId) ?>"
										checked="checked"
										fieldset="<?= $term->name ?>">
									<?php else: ?>
										<input
										type="checkbox"
										name="terms[]"
										data-title="<?= $term->name ?>"
										data-vocabID=""
										value=""
										class="chk<?php if ($term->assetType == 'Fieldset') echo ' fieldset'; ?>"
										checked="checked"
										fieldset="<?= $term->name ?>">
									<?php endif ?>
								</td>
								<td><?php
									$termPath = explode('.', $term->name);
									foreach ($termPath as $pathStep) {
										if ($pathStep != end($termPath)) {
											echo str_repeat('&nbsp;', 12);
										} else {
											echo $pathStep;
										}
									}
								?></td>
								<td>
									<?php if (!empty($term->businessTerm[0])): ?>
										<?php $termDef = nl2br(str_replace("\n\n\n", "\n\n", htmlentities(strip_tags(str_replace(['<div>', '<br>', '<br/>'], "\n", $term->businessTerm[0]->termDescription))))); ?>
										<?= $this->Html->link($term->businessTerm[0]->term, ['controller' => 'search', 'action' => 'term', $term->businessTerm[0]->termId]) ?>
										<div onmouseover="showTermDef(this)" onmouseout="hideTermDef()" data-definition="<?=$termDef?>" class="info"><img src="/img/iconInfo.png"></div>
									<?php endif ?>
								</td>
								<td style="white-space:nowrap;">
									<?php if (!empty($term->businessTerm[0])):
										$classification = $term->businessTerm[0]->termClassification;
										switch($classification){
											case 'Public':
											case '1 - Public':
												$classificationTitle = 'Public';
												$classification = 'Public';
												break;
											case 'Internal':
											case '2 - Internal':
												$classificationTitle = 'Internal';
												$classification = 'Internal';
												break;
											case 'Confidential':
											case '3 - Confidential':
												$classificationTitle = 'Confidential';
												$classification = 'Classified';
												break;
											case 'Highly Confidential':
											case '4 - Highly Confidential':
												$classificationTitle = 'Highly Confidential';
												$classification = 'HighClassified';
												break;
											case 'Not Applicable':
											case '0 - N/A':
												$classificationTitle = 'Not Applicable';
												$classification = 'NotApplicable';
												break;
											default:
												$classificationTitle = 'Unspecified';
												$classification = 'NoClassification2';
												break;
										}
										echo '<img class="classIcon" src="/img/icon'.$classification.'.png">&nbsp;'.$classificationTitle;

										if ($term->businessTerm[0]->approvalStatus != 'Approved') {
											echo '&nbsp;&nbsp;<img class="pendingApprovalIcon" src="/img/alert.png" onmouseover="displayPendingApproval(this)" onmouseout="hidePendingAproval()">';
										}
									endif ?>
								</td>
							</tr>
						<?php endforeach ?>
					</table>
				<?php endif ?>
				<input type="button" data-apiHost="<?= h($hostname) ?>" data-apiPath="<?= h(trim($basePath, '/')) ?>" api="<?= empty($terms) ? 'true' : 'false' ?>" onclick="addToQueue(this, false, true)" class="requestAccess grow mainRequestBtn" value="Add To Request">
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
