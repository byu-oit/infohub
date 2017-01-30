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
			<a href="https://developer-dev.byu.edu/api/api-list" id="doc_link" class="inputButton" target="_blank">Read API documentation</a>
			<a href="https://api.byu.edu/store/" id="store_link" class="inputButton" target="_blank">View this API in the store</a>
		</div>
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
					<h3>No available output fields</h3>
				<?php else: ?>
					<table class="api-terms checkBoxes">
						<tr class="header">
							<th>Field</th>
							<th>Business Term</th>
						</tr>
						<?php foreach ($terms as $term): ?>
							<tr>
								<td><?= $term->name ?></td>
								<td>
									<?php if (!empty($term->businessTerm[0])): ?>
										<?php $hasSelectable = true; ?>
										<?= $this->Html->link($term->businessTerm[0]->term, ['controller' => 'search', 'action' => 'term', $term->businessTerm[0]->termId]) ?>
									<?php endif ?>
								</td>
								<td>
									<?php if (!empty($term->businessTerm[0])): ?>
										<input
											type="checkbox"
											name="terms[]"
											data-title="<?= h($term->businessTerm[0]->term) ?>"
											data-vocabID="<?= h($term->businessTerm[0]->termCommunityId) ?>"
											value="<?= h($term->businessTerm[0]->termId) ?>"
											checked="checked">
									<?php endif ?>
								</td>
							</tr>
						<?php endforeach ?>
					</table>
				<?php endif ?>
				<?php if ($hasSelectable): ?>
					<input type="button" onclick="addToQueue(this, false)" class="requestAccess grow mainRequestBtn" value="Add To Request">
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
					var href = $('#doc_link').attr('href');
					$('#doc_link').attr('href', href.replace('api-list', response.name.replace('.', '')));
				}
			});
	});
</script>