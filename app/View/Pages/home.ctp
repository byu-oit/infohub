<?php
    $this->Html->css('home', null, array('inline' => false));
?>
<script>
	$(window).resize(boxWidth);	
 	$(window).load(boxWidth);
 	$(window).load(showHelpFirst);
 	$(window).scroll(showHelpFirst);

 	var setCheck = false;

 	function boxWidth() {
 		var boxTarget = $('#welcomeBox').width();
 		if ($(window).width() > 550) {
 			$('#searchBox').width(boxTarget);
 		}
 	}

 	function isScrolledIntoView(elem) {
	    var $elem = $(elem);
	    var $window = $(window);

	    var docViewTop = $window.scrollTop();
	    var docViewBottom = docViewTop + $window.height();

	    var elemTop = $elem.offset().top;
	    var elemBottom = elemTop + $elem.height();

	    return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
	}

	function showHelpFirst() {
		if (isScrolledIntoView("#homeHowTo") && setCheck == false) {
			$('#htFind .htPop').fadeIn();
			$('#htFind h2').addClass('active');
			setCheck = true;
		}
	}
 	$(document).ready(function() {

 		$('.htBox h2').on('mouseover', function () {
 			$('.htBox h2').removeClass('active');
 			$('.htPop').fadeOut("fast");
 			$(this).siblings('.htPop').fadeIn("fast");
 			$(this).addClass('active');
 		});

 		//Trigger Icon scaling when hovering over text link
 		$('.qlLink').hover(function() {
 			$('.qLicon').removeClass('active');
 			$(this).siblings('.qLicon').addClass('active');
 		}, function(){
 			$(this).siblings('.qLicon').removeClass('active');
 		});
 	});
</script>
<div id="homeTop">
	<div id="htBack">
		<div class="inner" id="hbWrap">
			<div id="welcomeBox">
				<h1>Welcome to BYU InfoHub</h1>
				<h5>Information we can use, trust, and share safely</h5>
			</div>
			<div id="mobSearchBox">
				<h3>Search Information</h3>
			</div>
			<div id="searchBox">
				<form action="#" onsubmit="document.location='/search/results/'+this.searchInput.value; return false;" method="post">
                    <input id="searchInput" name="searchInput" type="text" class="inputShade" onkeyup="searchAutoComplete()" placeholder="Search keyword, topic, or phrase" maxlength="50" autocomplete="off"  />
                    <?php echo $this->element('auto_complete'); ?>
                    <input type="submit" value="Search" class="inputButton" />
                </form>
			</div>
			<a href="/search" id="catalogLink" class="grow"><img src="/img/catalogLink2.png" alt="See full catealog"></a>
		</div>
	</div>	
</div>
<div id="homeQL" class="inner">
	<h3><span>Quick Links</span></h3>
	<div class="qlBox">
		<a href="/people" class="qLicon grow"><img src="/img/ql-book.png" alt="Locate People"></a>
		<p>Locate peolpe who <br>can help you find <br>your information.</p>
		<a href="#" class="qlLink">Find People</a>
	</div>
	<div class="qlBox">
		<a href="#" class="qLicon grow"><img src="/img/ql-list.png" alt="See policies"></a>
		<p>Understand policies and <br>procedures for proper <br>information usage</p>
		<a href="" class="qlLink">See Policies and Procedures.</a>
	</div>
	<div class="qlBox">
		<a href="/myaccount" class="qLicon grow"><img src="/img/ql-cogs.png" alt="Login"></a>
		<p>Track your requests, <br>add favorites and <br>customize your alerts.</p>
		<a href=""  class="qlLink">Log into or Create an Account</a>
	</div>
</div>
<div id="homeHowTo" class="inner">
	<h3>How to Use InfoHub</h3>
	<div id="htFind" class="htBox">
		<div class="htPop">
			<p>Search by keyword, topic or phrase to find the information you need, or browse the full catalog. </p>
		</div>
		<h2>1</h2>
		<p>Find Information</p>
	</div>
	<div class="arrow">
		<img src="/img/dtRightArrow.gif" alt="-->" class="dt">
		<img src="/img/mobDownArrow.png" alt="V" class="mob">
	</div>
	<div id="htRequest" class="htBox">
		<div class="htPop">
			<p>Once you’ve found your information, request access from the steward in charge of it. (You’ll need a NetID to request access.)</p>
		</div>
		<h2>2</h2>
		<p>Request Access</p>
	</div>
	<div class="arrow">
		<img src="/img/dtRightArrow.gif" alt="-->" class="dt">
		<img src="/img/mobDownArrow.png" alt="V" class="mob">
	</div>
	<div id="htTrack" class="htBox">
		<div class="htPop">
			<p>Go to your account to track the progress of your request. Once your request is approved, the link to your information will be available there. </p>
		</div>
		<h2>3</h2>
		<p>Track Progress</p>
	</div>
	<div class="arrow">
		<img src="/img/dtRightArrow.gif" alt="-->" class="dt">
		<img src="/img/mobDownArrow.png" alt="V" class="mob">
	</div>
	<div id="htUse" class="htBox">
		<div class="htPop">
			<p>All information requested through InfoHub is safe to use, with sharing permissions clearly noted. You can also be confident it’s the most accurate and up-to-date information available. </p>
		</div>
		<h2>4</h2>
		<p>Use Confidently</p>
	</div>
</div>