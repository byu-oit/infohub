<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('search', null, array('inline' => false));
?>
<script>
	$(document).ready(function() {
		$("#searchLink").addClass('active');

		$('li a').click(function (e) {
			$(this).toggleClass('active');
			e.preventDefault();
			var ullist = $(this).parent().children('ul:first');
			ullist.slideToggle();
			listWidth();
		});
	});

	$(document).ready(listWidth);
	$(window).resize(listWidth);

	function listWidth() {
		$('.catalogChild').css('width', '100%').css('width', '-=11px');
		$('.grandChild').css('width', '100%').css('width', '-=11px');
		$('.greatGrandChild').css('width', '100%').css('width', '-=11px');
	}

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
			<form action="submit">
				<input type="text" class="inputShade" placeholder="Search keyword, topic, or phrase">
				<input type="submit" value="Search" class="inputButton">
			</form>
			<div class="clear"></div>
		</div>
	</div>

	<a href="/catalog" id="catalogLink" class="grow"><img src="/img/catalogLink2.png" alt="See full catealog"></a>
	<div class="clear"></div>

	<div id="searchResults">
		<h2 class="headerTab" >Results</h2>
		<div class="clear"></div>
		<div id="srLower" class="whiteBox">
			<div id="searchFilters">
				<label for="filerBy">Filter By:</label>
				<select name="filterBy" id="filerBy" class="inputShade">
					<option value="0" selected>All Results</option>
					<option value="1">This</option>
					<option value="2">That</option>
					<option value="3">The Other</option>
				</select>
				<label for="filerBy">Sort By:</label>
				<select name="filterBy" id="filerBy" class="inputShade">
					<option value="0" selected>Date Added</option>
					<option value="1">This</option>
					<option value="2">That</option>
					<option value="3">The Other</option>
				</select>
			</div>
			<div class="resultItem">
				<h4>Definition Title</h4>
				<h5>/Lorem/Lorem</h5>
				<dl>
					<dt>Data Steward:</dt>
					<dd>Julie Emerson</dd>
					<dt>Date Created:</dt>
					<dd>9/18/14</dd>
					<dt>DataType:</dt>
					<dd>Report</dd>
					<dt>Expiration Date:</dt>
					<dd>1/15/16</dd>
					<dt>Classification: </dt>
					<dd class="redText">Highly Classified</dd>
				</dl>
			</div>
			<div class="resultItem">
				<h4>Definition Title</h4>
				<h5>/Lorem/Lorem</h5>
				<dl>
					<dt>Data Steward:</dt>
					<dd>Julie Emerson</dd>
					<dt>Date Created:</dt>
					<dd>9/18/14</dd>
					<dt>DataType:</dt>
					<dd>Report</dd>
					<dt>Expiration Date:</dt>
					<dd>1/15/16</dd>
					<dt>Classification: </dt>
					<dd class="orangeText">Classified</dd>
				</dl>
			</div>
			<div class="resultItem">
				<h4>Definition Title</h4>
				<h5>/Lorem/Lorem</h5>
				<dl>
					<dt>Data Steward:</dt>
					<dd>Julie Emerson</dd>
					<dt>Date Created:</dt>
					<dd>9/18/14</dd>
					<dt>DataType:</dt>
					<dd>Report</dd>
					<dt>Expiration Date:</dt>
					<dd>1/15/16</dd>
					<dt>Classification: </dt>
					<dd class="greenText">Public</dd>
				</dl>
			</div>
			<div class="resultItem">
				<h4>Definition Title</h4>
				<h5>/Lorem/Lorem</h5>
				<dl>
					<dt>Data Steward:</dt>
					<dd>Julie Emerson</dd>
					<dt>Date Created:</dt>
					<dd>9/18/14</dd>
					<dt>DataType:</dt>
					<dd>Report</dd>
					<dt>Expiration Date:</dt>
					<dd>1/15/16</dd>
					<dt>Classification: </dt>
					<dd class="blueText">Internal</dd>
				</dl>
			</div>
		</div>
	</div>
</div>

<!-- Quick links -->
<?php echo $this->element('quick_links'); ?>
