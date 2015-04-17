<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('search', null, array('inline' => false));
?>
<script>
	$(document).ready(function() {
		$("#searchLink").addClass('active');
	});
</script>

<!-- Background image div -->
<div id="searchBg" class="deskBg">
</div>

<!-- Request list -->
<div id="searchBody" class="innerLower">
	<div id="searchTop">
		<h1 class="headerTab" >Search Information</h1>
		<div class="clear"></div>
		<div id="stLower" class="whiteBox">
			<form action="#" onsubmit="document.location='/search/results/'+this.searchInput.value; return false;" method="post">
				<input id="searchInput" name="searchInput" type="text" class="inputShade" onkeyup="searchAutoComplete()" placeholder="Search keyword, topic, or phrase" maxlength="50" autocomplete="off"  />
				<?php echo $this->element('auto_complete'); ?>
				<input type="submit" value="Search" class="inputButton" />
			</form>
			<div class="clear"></div>
		</div>
	</div>

	<a href="/search/catalog" id="catalogLink" class="grow"><img src="/img/catalogLink2.png" alt="See full catealog"></a>
	<div class="clear"></div>

</div>

<!-- Quick links -->
<?php echo $this->element('quick_links'); ?>
