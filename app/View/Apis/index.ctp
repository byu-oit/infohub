<?php
	$this->Html->css('secondary', null, ['inline' => false]);
	$this->Html->css('search', null, ['inline' => false]);
?>
<script>
	$(document).ready(function() {
		$("#browse-tab").addClass('active');
	});
</script>

<div id="searchBody" class="innerLower">

	<?php if (isset($recent)): ?>
		<div id="searchMain" style="padding-top: 35px;">
			<h2 class="headerTab">Recently Viewed APIs</h2>
			<div class="clear"></div>
			<div id="smLower" class="whiteBox">
				<ul class="catalogParent">
					<?php foreach ($recent as $endpoint): ?>
						<li class="catalogItem">
							<?= $this->Html->link($endpoint['basePath'], array_merge(['action' => 'view', 'hostname' => $endpoint['host']], explode('/', $endpoint['basePath']))) ?>
						</li>
					<?php endforeach ?>
				</ul>
			</div>
		</div>
	<?php endif ?>

	<div id="searchMain" style="padding-top: 35px;">
		<h1 class="headerTab">Select API Host</h1>
		<div class="clear"></div>
		<div id="smLower" class="whiteBox">
			<ul class="catalogParent"><?php
				foreach ($hosts as $host) {
					echo '<li class="catalogItem"><a href="/apis/'.$host.'">'.$host.'</a></li>';
				}
			?></ul>
		</div>
	</div>

</div>
