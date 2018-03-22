<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('search', null, array('inline' => false));
?>
<div id="searchBody" class="innerLower">
	<div id="searchResults">
		<h1 class="headerTab">APIs</h1>
		<div class="clear"></div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem"><?php pr($hosts) ?></div>
		</div>
	</div>
</div>
