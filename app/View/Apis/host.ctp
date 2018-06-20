<?php
	$this->Html->css('secondary', null, ['inline' => false]);
	$this->Html->css('search', null, ['inline' => false]);
?>

<script>
$(document).ready(function() {
	$("#apisLink").addClass('active')

	var apis = [<?php foreach ($community->vocabularyReferences->vocabularyReference as $api) { echo '"'.$api->name.'",'; } ?> ""];
	$('#apiFilter').on('input', function() {
		var filterValue = $(this).val().toLowerCase();
		for (var i = 0; i < apis.length; i++) {
			if (!apis[i].toLowerCase().includes(filterValue)) {
				$('#catalogIndex-'+i).css('display', 'none');
			} else {
				$('#catalogIndex-'+i).css('display', 'block');
			}
		}
	});

	$('#apiFilter').keypress(function(event) { return event.keyCode != 13; });
	$('#apiFilter').on({
		keyup: function(e) {
			if (e.which === 13) {
				var filterValue = $(this).val().toLowerCase();
				for (var i = 0; i < apis.length; i++) {
					if (apis[i].toLowerCase().includes(filterValue)) {
						window.location.href = window.location.origin+'/apis/<?=$hostname?>'+$('#catalogIndex-'+i).data('name');
						break;
					}
				}
			}
		}
	});
});
</script>

<style>
.ui-autocomplete {
	display: none !important;
}
.autoCompleteApis {
	width: 490px;
}
</style>

<!-- Background image div -->
<div id="searchBg" class="searchBg">
</div>

<div id="searchBody" class="innerLower apis">

	<?php if (isset($recent)): ?>
		<div id="searchMain" style="padding-top: 35px;">
			<h2 class="headerTab">Recently Viewed APIs</h2>
			<div class="clear"></div>
			<div id="smLower" class="whiteBox">
				<ul class="catalogParent">
					<?php foreach ($recent as $endpoint): ?>
						<li class="catalogItem">
							<?= $this->Html->link($endpoint, array_merge(['action' => 'view', 'hostname' => $hostname], explode('/', $endpoint))) ?>
						</li>
					<?php endforeach ?>
				</ul>
			</div>
		</div>
	<?php endif ?>

	<div id="searchTop">
		<h1 class="headerTab">Filter APIs</h1>
		<div class="clear"></div>
		<div id="stLower" class="whiteBox">
			<form action="#" onsubmit="document.location='/apis/view/<?=$hostname?>/'+this.searchInput.value; return false;" method="post">
				<input id="apiFilter" name="searchInput" type="text" class="inputShade" placeholder="Enter API name" maxlength="50" autocomplete="off" style="width: 490px;" />
			</form>
			<div class="clear"></div>
		</div>
	</div>

	<div id="searchMain">
		<h2 class="headerTab"><?=$community->name?></h2>
		<div class="clear"></div>
		<div id="smLower" class="whiteBox">
			<ul class="catalogParent">
				<?php if (empty($community->vocabularyReferences->vocabularyReference)): ?>
					No endpoints found
				<?php else: ?>
					<?php $i = 0;
					foreach ($community->vocabularyReferences->vocabularyReference as $endpoint): ?>
						<?php
							if ($endpoint->meta == 1) {
								continue;
							}
						?>
						<li id="catalogIndex-<?=$i?>" class="catalogItem" data-name="<?=$endpoint->name?>">
							<?= $this->Html->link($endpoint->name, array_merge(['action' => 'view', 'hostname' => $hostname], explode('/', $endpoint->name))) ?>
						</li>
					<?php $i++;
					endforeach; ?>
				<?php endif ?>
			</ul>
		</div>
	</div>

</div>
