<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('resource', null, array('inline' => false));
?>
<script>
	$(document).ready(function() {
		$("#resourceLink").addClass('active');

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

	$(document).ready(colHeight);
	$(window).resize(colHeight);

	function colHeight() {
		$('.contentBody').css('height', 'initial');

		var asideHeight = $('aside').height();
		var contentHeight = $('.contentBody').height();

		var heighest = Math.max(asideHeight,contentHeight);

		$('aside').height(heighest);
		$('.contentBody').height(heighest);


	}
</script>

<!-- Background image div -->
<div id="resourceBg" class="deskBg">
</div>

<!-- Request list -->
<div id="resourceBody" class="innerLower">
	<div id="resourceTop">
		<h1 class="headerTab" >Resources</h1>
		<div class="clear"></div>
		<div id="reLower" class="whiteBox">
			<aside>
				<ul class="catalogParent">
				<li class="catalogItem">
					<a class="hasChildren">A</a>
					<ul class="subList catalogChild">
						<li class="catalogItem">
							<a class="hasChildren">1</a>
							<ul class="subList grandChild">
								<li class="catalogItem"><a>a</a></li>
								<li class="catalogItem"><a>b</a></li>
							</ul>
						</li>
						<li class="catalogItem"><a>2</a></li>
						<li class="catalogItem"><a>3</a></li>
						<li class="catalogItem"><a>4</a></li>
					</ul>
				</li>
				<li class="catalogItem">
					<a class="hasChildren">B</a>
					<ul class="subList catalogChild">
						<li class="catalogItem"><a>1</a></li>
						<li class="catalogItem"><a>2</a></li>
					</ul>
				</li>
				<li class="catalogItem">
					<a class="hasChildren">C</a>
					<ul class="subList catalogChild">
						<li class="catalogItem"><a>1</a></li>
					</ul>
				</li>
			</ul>
			</aside>
			<div class="contentBody">
				<h2>2. Title</h2>
				<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labare et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Lorem ipsum dolor sit amet, re magna aliqua.Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.Duis aute irure dolor perspiciatis. Sed do eiusmod tempor incididunt ut labare et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco</p>
				<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor iut labare et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Lorem ipsum dolor sit amet, magna aliqua.Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.Duis aute irure dolor perspiciatis. Sed do eiusmod tempor incididunt ut labare et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco</p>
				<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labare et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
			</div>
			<div class="clear"></div>
		</div>
	</div>
</div>

<!-- Quick links -->
<?php echo $this->element('quick_links'); ?>