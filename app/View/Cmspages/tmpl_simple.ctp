<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('resource', null, array('inline' => false));
?>
<script>
	$(document).ready(function() {
		$("#resourceLink").addClass('active');
		$('#asideMobile').click(function() {
			$('aside ul').slideToggle('fast');
		})
	});


	$(document).ready(listWidth);
	$(window).resize(listWidth);

	function listWidth() {
		$('.catalogChild').css('width', '100%').css('width', '-=11px');
		$('.grandChild').css('width', '100%').css('width', '-=11px');
		$('.greatGrandChild').css('width', '100%').css('width', '-=11px');
	}

	$(document).ready(function() {
		if($(window).width() > 750) {
			colHeight();
		}
		else {
			$('.contentBody, .aside').css('height', 'initial');
			$('aside').css('height', 'initial');
		}
		
	});
		
	$(window).resize(function() {
		if($(window).width() > 750) {
			$('.contentBody, .aside').css('height', 'initial');
			$('aside').css('height', 'initial');
			setTimeout(function() {colHeight();}, 100);
		}
		else {
			$('.contentBody, .aside').css('height', 'initial');
			$('aside').css('height', 'initial');
		}
		
	});

	function colHeight() {
        /*$('aside, .contentBody').css('height','auto');
		var asideHeight = $('aside').outerHeight();
		var contentHeight = $('.contentBody').outerHeight();
		var heighest = Math.max(asideHeight,contentHeight) -30;
		$('aside').height(heighest);
		$('.contentBody').height(heighest);*/
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
				<div id="asideMobile">
					<a id="resourceNav">&nbsp;</a>
				</div>
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