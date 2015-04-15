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

	<div id="searchMain">
		<h2 class="headerTab" >Full Catalog</h2>
		<div class="clear"></div>
		<div id="smLower" class="whiteBox">
			<ul class="catalogParent">
				<li class="catalogItem">
					<a class="hasChildren">Academic</a>
					<ul class="subList catalogChild">
						<li class="catalogItem">
							<a class="hasChildren">Academic Leadership</a>
							<ul class="subList grandChild">
								<li class="catalogItem"><a>Lorem</a></li>
								<li class="catalogItem"><a>Ipsum</a></li>
							</ul>
						</li>
						<li class="catalogItem"><a>CES Admissions</a></li>
						<li class="catalogItem"><a>Class Scheduling</a></li>
						<li class="catalogItem"><a>Course and Instructor Ratings</a></li>
					</ul>
				</li>
				<li class="catalogItem">
					<a class="hasChildren">Advancement</a>
					<ul class="subList catalogChild">
						<li class="catalogItem"><a>Lorem</a></li>
						<li class="catalogItem"><a>Ipsum</a></li>
					</ul>
				</li>
				<li class="catalogItem">
					<a class="hasChildren">Financial</a>
					<ul class="subList catalogChild">
						<li class="catalogItem"><a>Lorem</a></li>
					</ul>
				</li>
			</ul>
		</div>
	</div>
</div>

<!-- Quick links -->
<?php echo $this->element('quick_links'); ?>
