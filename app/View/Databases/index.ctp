<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('search', null, array('inline' => false));
?>
<div id="searchBody" class="innerLower">
	<div id="searchResults">
		<h1 class="headerTab">Databases</h1>
		<div class="clear"></div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem"><ul><?php
				foreach ($schemas as $schema) {
					echo '<li><a href="/databases/schema/'.$schema->name.'">'.$schema->name.'</a></li>';
				}
			?></ul></div>
		</div>
	</div>
</div>
