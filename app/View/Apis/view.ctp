<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('search', null, array('inline' => false));
	$hasSelectable = false;
?>
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
		</div>
		<div id="api_help_btn" class="apiHelpButton">Do you need access to call this API?</div>
		<div id="api_help" class="apiHelp">There are potentially two steps you'll need to complete.<br><br>1. Subscribe to the API in the API store (WSO2). This gives you the ability to call the API with your own credentials for self-service. You'll need to create an "application" in WSO2 and subscribe to the each API you are interested in. You must complete this step in the API store. An overview of the process is available in the <a href="https://developer.byu.edu/docs/consume-api/5-minute-quickstart">Developer Portal.<br>https://developer.byu.edu/docs/consume-api/5-minute-quickstart</a><br><br>2. Request elevated access to the API. Many APIs can return more data if you have elevated permissions. Select the elements of the API that you need for your application and click the "Add to request" button. Once you've added all the elements you want access to, submit your request from the shopping cart icon at the top of the page. This will initiate the process of securing approvals (if needed) from the appropriate data stewards. More <a href="https://developer.byu.edu/docs/consume-api/get-elevated-access">detailed instructions</a> for this step are available.<br><a href="https://developer.byu.edu/docs/consume-api/get-elevated-access">https://developer.byu.edu/docs/consume-api/get-elevated-access</a></div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem">
				<?php //TODO: make this a deep link directly to this particular API ?>
				<?php if ($isAdmin): ?>
					<div style="float: right">
						<?= $this->Html->link(
							'Update Unlinked Terms',
							array_merge(['controller' => 'api_admin', 'action' => 'update', $hostname], explode('/', $basePath)),
							['class' => 'inputButton']) ?>
					</div>
				<?php endif ?>
				<?php if (empty($terms)): ?>
					<h3>Well, this is embarrassing. We haven't yet specified the output fields for this API, but it is functional, and you can still request access to it.</h3>
				<?php else: ?>
					<table class="api-terms checkBoxes">
						<tr class="header">
							<th class="fieldColumn">Field</th>
							<th class="termColumn">Business Term</th>
							<th>Classification</th>
						</tr>
						<?php foreach ($terms as $term): ?>

							<tr>
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
										<?php $hasSelectable = true; ?>
										<?= $this->Html->link($term->businessTerm[0]->term, ['controller' => 'search', 'action' => 'term', $term->businessTerm[0]->termId]) ?>
									<?php endif ?>
								</td>
								<td>
									<?php if (!empty($term->businessTerm[0])):
										$classification = $term->businessTerm[0]->termClassification;
										switch($classification){
											case 'Public':
												$classificationTitle = 'Public';
												$classification = 'Public';
												break;
											case 'Internal':
												$classificationTitle = 'Internal';
												$classification = 'Internal';
												break;
											case 'Confidential':
												$classificationTitle = 'Confidential';
												$classification = 'Classified';
												break;
											case 'Highly Confidential':
												$classificationTitle = 'Highly Confidential';
												$classification = 'HighClassified';
												break;
											case 'Not Applicable':
												$classificationTitle = 'Not Applicable';
												$classification = 'NotApplicable';
												break;
											default:
												$classificationTitle = 'Unspecified';
												$classification = 'NoClassification2';
												break;
										}
										echo '<img class="classIcon" src="/img/icon'.$classification.'.png">&nbsp;'.$classificationTitle;
									endif ?>
								</td>
								<td>
									<?php if (!empty($term->businessTerm[0])): ?>
										<input
											type="checkbox"
											name="terms[]"
											data-title="<?= h($term->businessTerm[0]->term) ?>"
											data-vocabID="<?= h($term->businessTerm[0]->termCommunityId) ?>"
											value="<?= h($term->businessTerm[0]->termId) ?>"
											class="chk<?= h($term->businessTerm[0]->termId) ?>"
											checked="checked">
									<?php endif ?>
								</td>
							</tr>
						<?php endforeach ?>
					</table>
				<?php endif ?>
				<input type="button" data-apiHost="<?= h($hostname) ?>" data-apiPath="<?= h(trim($basePath, '/')) ?>" api="<?= $hasSelectable ? 'false' : 'true' ?>" onclick="addToQueue(this, false)" class="requestAccess grow mainRequestBtn" value="Add To Request">
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
					var href = $('#doc_link').attr('href');
					$('#doc_link').attr('href', href.replace('api-list', response.name.replace('.', '')));
				}
			});
	});
	$('#api_help_btn').click(function() {
		$('#api_help').toggle(500);
	})
</script>
