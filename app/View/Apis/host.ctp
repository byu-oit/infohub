<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('search', null, array('inline' => false));
?>
<div id="searchBody" class="innerLower">
	<div id="searchResults">
		<h1 class="headerTab"><?= $community->name ?></h1>
		<div class="clear"></div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem">
				<?php if (empty($community->vocabularyReferences->vocabularyReference)): ?>
					No endpoints found
				<?php else: ?>
					<ul>
						<?php foreach ($community->vocabularyReferences->vocabularyReference as $endpoint): ?>
							<?php
								if (empty($endpoint->typeReference->resourceId) || !in_array($endpoint->typeReference->resourceId, [$dataAssetDomainTypeId, $techAssetDomainTypeId])) {
									continue;
								}
							?>
							<li><?= $this->Html->link($endpoint->name, array_merge(['action' => 'view', 'hostname' => $hostname], explode('/', $endpoint->name))) ?></li>
						<?php endforeach ?>
					</ul>
				<?php endif ?>
			</div>
		</div>
	</div>
</div>