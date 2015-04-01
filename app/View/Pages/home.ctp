<?php
/**
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.View.Pages
 * @since         CakePHP(tm) v 0.10.0.1076
 */

if (!Configure::read('debug')):
	throw new NotFoundException();
endif;

App::uses('Debugger', 'Utility');

$this->Html->css('home', null, array('inline' => false));
?>
<script>
	$(window).resize(boxWidth);	
 	$(window).load(boxWidth);

 	function boxWidth() {
 		var boxTarget = $('#welcomeBox').width();

 		$('#searchBox').width(boxTarget);
 	}
</script>
<div id="homeTop">
	<div id="htBack">
		<div class="inner">
			<div id="welcomeBox">
				<h1>Welcome to BYU InfoHub</h1>
				<h5>Information we can use, trust, and share safely</h5>
			</div>
			<div id="searchBox">
				<input type="text" placeholder="Search keyword, topic, or phrase">
				<input type="submit" value="Search" >
			</div>
			<a href="/catalog" id="catalogLink" class="grow"><img src="/img/catalogLink.png" alt="See full catealog"></a>
		</div>
	</div>	
</div>
<div id="homeQL" class="inner">
	<h3><span>Quick Links</span></h3>
	<div class="qlBox">
		<a href="3" class="qLicon grow"><img src="/img/ql-book.gif" alt="Locate People"></a>
		<p>Locate peolpe who <br>can help you find <br>your information.</p>
		<a href="#" class="qlLink">Find People</a>
	</div>
	<div class="qlBox">
		<a href="3" class="qLicon grow"><img src="/img/ql-list.gif" alt="See policies"></a>
		<p>Understand policies and <br>procedures for proper <br>information usage</p>
		<a href="" class="qlLink">See Policies and Procedures.</a>
	</div>
	<div class="qlBox">
		<a href="3" class="qLicon grow"><img src="/img/ql-cogs.gif" alt="Login"></a>
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
		<img src="/img/mobDownArrow" alt="V" class="mob">
	</div>
	<div id="htRequest" class="htBox">
		<div class="htPop">
			<p>Lorem Ipsum bla bla bla Lorem Ipsum bla bla bla Lorem Ipsum bla bla bla</p>
		</div>
		<h2>2</h2>
		<p>Request Access</p>
	</div>
	<div class="arrow">
		<img src="/img/dtRightArrow.gif" alt="-->" class="dt">
		<img src="/img/mobDownArrow" alt="V" class="mob">
	</div>
	<div id="htTrack" class="htBox">
		<div class="htPop">
			<p>Lorem Ipsum bla bla bla Lorem Ipsum bla bla bla Lorem Ipsum bla bla bla</p>
		</div>
		<h2>3</h2>
		<p>Track Progress</p>
	</div>
	<div class="arrow">
		<img src="/img/dtRightArrow.gif" alt="-->" class="dt">
		<img src="/img/mobDownArrow" alt="V" class="mob">
	</div>
	<div id="htUse" class="htBox">
		<div class="htPop">
			<p>Lorem Ipsum bla bla bla Lorem Ipsum bla bla bla Lorem Ipsum bla bla bla</p>
		</div>
		<h2>4</h2>
		<p>Use Confidently</p>
	</div>
</div>