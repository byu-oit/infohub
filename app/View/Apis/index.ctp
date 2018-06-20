<?php
	$this->Html->css('secondary', null, ['inline' => false]);
	$this->Html->css('search', null, ['inline' => false]);
?>
<script>
	$(document).ready(function() {
		$("#apisLink").addClass('active');
	});
</script>

<div id="searchBody" class="innerLower">
	<div id="searchResults">
		<h1 class="headerTab">APIs</h1>
		<div class="clear"></div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem"><?php pr($hosts) ?></div>
		</div>
	</div>
</div>
