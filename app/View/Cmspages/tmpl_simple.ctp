<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('resource', null, array('inline' => false));
?>
<script>
	$(document).ready(function() {
		$("#resourceLink").addClass('active');

		$('.catalogParent li a').click(function (e) {
			$(this).toggleClass('active');
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

		var heighest = Math.max(asideHeight,contentHeight)+20;

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
                   <?php echo $pageNav ?>
                </ul>
			</aside>
			<div class="contentBody">
				<h2><?php echo $page['title'] ?></h2>
				<?php echo $page['body'] ?>
			</div>
			<div class="clear"></div>
		</div>
	</div>
</div>

<!-- Quick links -->
<?php echo $this->element('quick_links'); ?>