<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.View.Layouts
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

$cakeDescription = __d('cake_dev', 'BYU InfoHub');
$cakeVersion = __d('cake_dev', 'CakePHP %s', Configure::version())
?>
<!DOCTYPE html>
<html>
<head>
	<?php echo $this->Html->charset(); ?>
	<title>
		<?php echo $cakeDescription ?>:
		<?php echo $this->fetch('title'); ?>
	</title>
	<?php
		echo $this->Html->meta('icon');
		// echo $this->Html->css('cake.generic');
		echo $this->Html->css('styles');
		echo $this->Html->css('admin-nav');
		echo $this->fetch('meta');
		echo $this->fetch('css');
		echo $this->fetch('script');
	?>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href='//fonts.googleapis.com/css?family=Rokkitt:400,700' rel='stylesheet' type='text/css'>
	<link href='//fonts.googleapis.com/css?family=Open+Sans:400,600,700,300' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/themes/smoothness/jquery-ui.css" />
	<link href='//fonts.googleapis.com/css?family=Voltaire' rel='stylesheet' type='text/css'>
	<script src="//code.jquery.com/jquery-2.1.3.min.js"></script>
	<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/jquery-ui.min.js"></script>
	
	<script>
		var headIn;
		var winOu;
		var headFor;
		var headFinal;

		$(document).ready(function(){
			// Shows mobile menu
			$('#mob-nav').click(function() {
				$('#mainNav').toggle("slide", { direction: "left" }, 300);
			});

			// Help Pop-out functionality
			$("#deskTopHelp").click(function() {
				$(this).hide();
				widenBorder();
				$("#nhContent").show("slide", { direction: "right" }, 500);
			});
			$(".close").click(function() {
				if ($(window).width() > 750) {
					$("#nhContent").hide("slide", { direction: "right" }, 500, function() {
						$("#deskTopHelp").fadeIn("fast");
					});
				}
				else {
					$("#nhContent").slideUp();	
				}
			});

			$("#mobileHelp").click(function() {
				$("#nhContent").slideToggle();
			});

			$('.editQL').click(function() {
				$('.ql-edit').addClass('active');
				$('.ql-list').addClass('ql-active');
				$('.quickLink').addClass('active-link')
			});
			$('.saveEdit').click(function() {
				$('.ql-edit').removeClass('active');
				$('.ql-list').removeClass('ql-active');
				$('.quickLink').removeClass('active-link')
			});
		});

		$(document).ready(resizeFonts);	
		$(document).ready(iniVars);	
		$(window).resize(resizeFonts);	
		$(window).resize(iniVars);	
			$(window).load(resizeFonts);
			$(window).load(iniVars);
			// $(window).resize(function(){location.reload();});

			//Get values 
			function iniVars() {
				headIn = $("#headerInner").width();
			winOut = $(window).width();
			headFor = winOut - headIn;
			headFinal = headFor / 2;
			}

			//Sets right border for "Need Help" fly-out on larger screens
			function widenBorder() {
			//    iniVars();
			// 	if (winOut > 1090) {
			// 	$("#nhContent").css("border-right-width", headFinal);
			// }
			// else {
			// 	$("#nhContent").css("border-right-width", 0);
			// }
			$("#nhContent").css("border-right-width", 0);
			}

		function resizeFonts(){
			var defSize = 10;
			var mobileWidth = 550;

			// reset font size when in mobile view
			if($(window).width()<=mobileWidth){
				$('body').css('fontSize', defSize);
				return false;
			}
			
			var size = $(document).width() / 1230;
			var maxSize =  10;
			var minSize = 6.5;
			size = defSize * size;
			if(size>maxSize) size=maxSize;
			if(size<minSize) size=minSize;
			$('body').css('fontSize', size);
		}
		
		$(document).ready(function(){
			var index = -1;
			$('#searchInput').keypress(function(event) { return event.keyCode != 13; });
			$('#searchInput').on({
				keyup: function(e){
					var m;

					if ($.trim($('#searchInput').val()) == ''){
						$('.autoComplete').hide();
					}
					else if  ( e == true ) {
						$('.autoComplete').hide();
					}
					else if  ( e.which == 27 ) {
						$('.autoComplete').hide();
						index = -1;
					}
					else if(e.which == 13) {
						if($('.autoComplete li').hasClass('active')){
							$('#searchInput').val($('.autoComplete li.active').text());
							$('#searchInput').parent().submit();
						}
						else {
							$('#searchInput').parent().submit();
						}
						$('.autoComplete').hide();
					}
					else if(e.which == 38){
						e.preventDefault();
						if(index == -1){
							index = $('.autoComplete li').length - 1;
						}
						else {
							index--;
						}
			
						if(index > $('.autoComplete li').length ){
							index = $('.autoComplete li').length + 1;
						}
						m = true;
					}
					else if(e.which === 40){
						e.preventDefault();
						if(index >= $('.autoComplete li').length -1){
							index = 0;
						}
						else{
							index++;
						}
						m = true;
					}
					else{
						var val = $('#searchInput').val();
						$.getJSON( "/search/autoCompleteTerm", { q: val } )
						.done(function( data ) {
								$('.autoComplete .results').html('');
								for (var i in data) {
									$('.autoComplete .results').append($('<li>', {text: data[i].name.val}));
								}
								$('.autoComplete li').click(function(){
									$('#searchInput').val($(this).text());
									$('#searchInput').parent().submit();
									$('.autoComplete').hide();
								});
						});

						$('.autoComplete').show();
					}

					if(m){
						$('.autoComplete li.active').removeClass('active');
						$('.autoComplete li').eq(index).addClass('active');
				   }
				}
			});
		});
		
		function showTermDef(elem){
			var pos = $(elem).offset();
			var data = $(elem).attr('data-definition');
			$('#info-win .info-win-content').html(data);
			$('#info-win').show();
			var winLeft = pos.left - $('#info-win').outerWidth()/2 + 5;
			var winTop = pos.top - $('#info-win').outerHeight() - 5;
			$('#info-win').css('top',winTop).css('left',winLeft);
		}
			function hideTermDef(){
					$('#info-win').hide();
			}
		
		// Request functions
		///////////////////////////////
		function showRequestQueue(){
			$.get("/request/listQueue")
				.done(function(data){
					if($(window).scrollTop()>50){
						var requestIconPos = $('#request-queue .request-num').offset();
						var left = requestIconPos.left - $('#request-popup').width() - 16;
						$('#request-popup').addClass('fixed').css('left', left);
					}else{
						$('#request-popup').removeClass('fixed').css('left', 'auto');
					}
					$('#request-popup').html(data).slideDown('fast');
				});
		}
		function hideRequestQueue(){
			$('#request-popup').hide();
		}
		function removeFromRequestQueue(id){
			$.post("/request/removeFromQueue", {id:id})
				.done(function(data){
					var title = $('#requestItem'+id).attr('data-title');
					var rID = $('#requestItem'+id).attr('data-rid');
					var vocabID = $('#requestItem'+id).attr('data-vocabID');
					
					$('#request-undo').remove();
					$('#requestItem'+id).fadeOut('fast',function(){
						var html = '<div id="request-undo" data-title="' + title + '" data-rid="' + rID + '" data-vocabID="' + vocabID + '">Item removed. Click to undo.</div>';
						$(html).insertBefore('#request-popup ul');
						$('#request-undo').click(function(){
							addToQueue(this, false);
							$(this).remove();
						})
						getCurrentRequestTerms();
					});
			});
		}
		function getCurrentRequestTerms(){
			$.get("/request/getQueueJSArray")
				.done(function(data){
					data = data.split(',');
					$('input[type=checkbox]').prop('checked', false);

					for(i=0; i<data.length; i++){
						$('.chk'+data[i]).prop('checked', true);
					}
					$('#request-queue .request-num').text(data.length-1);
					if(data.length-1 <= 0){
						$('#request-queue .request-num').addClass('request-hidden');
						hideRequestQueue();
					}
			});
		}
		function addToQueue(elem, clearRelated){
			var arrTitles = new Array($(elem).attr('data-title'));
			var arrIDs = new Array($(elem).attr('data-rid'));
			var arrVocabIDs = new Array($(elem).attr('data-vocabID'));

			$(elem).parent().find('.checkBoxes').find('input').each(function(){
				if($(this).prop("checked")){
					arrTitles.push($(this).attr('data-title'));
					arrIDs.push($(this).val());
					arrVocabIDs.push($(this).attr('data-vocabID'));
				}
			});
			$.post("/request/addToQueue", {t:arrTitles, id:arrIDs, vocab:arrVocabIDs, clearRelated:clearRelated})
				.done(function(data){
					$(elem).attr('value', 'Added to Request').removeClass('grow').addClass('inactive');
					var oldCount = parseInt($('#request-queue .request-num').text());
					data = parseInt(data);
					if(oldCount+data>0){
						$('#request-queue .request-num').text(oldCount+data).removeClass('request-hidden');
						showRequestQueue();
						getCurrentRequestTerms();
					}
			});
		}
		/////////////////////////////

		$(document).on( 'click', function ( e ) {
			if ( $( e.target ).closest('.autoComplete').length === 0 ) {
				$('.autoComplete').hide();
			}
		});

		$(window).scroll(function(){
			if($(this).scrollTop()>50){
				var requestIconPos = $('#request-queue .request-num').offset();
				var left = requestIconPos.left - $('#request-popup').width() - 16;
				$('#request-popup').addClass('fixed').css('left', left);
			}else{
				$('#request-popup').removeClass('fixed').css('left', 'auto');
			}
		});

		$(function() {
			if(!$.support.placeholder) {
				var active = document.activeElement;
				$('textarea').each(function(index, element) {
					if($(this).val().length == 0 && !$(this).hasClass('noPlaceHolder')) {
						$(this).html($(this).attr('id')).addClass('hasPlaceholder');
					}
				});
				$('input, textarea').focus(function () {
					if ($(this).attr('placeholder') != '' && $(this).val() == $(this).attr('placeholder') && !$(this).hasClass('noPlaceHolder')) {
						$(this).val('').removeClass('hasPlaceholder');
					}
				}).blur(function () {
					if (($(this).attr('placeholder') != '' && ($(this).val() == '' || $(this).val() == $(this).attr('placeholder')) && !$(this).hasClass('noPlaceHolder'))) {
						$(this).val($(this).attr('placeholder')).addClass('hasPlaceholder');
						//$(this).css('background', 'red');
					}
				});
				$(':text').blur();
				$(active).focus();
				$('form').submit(function () {
					$(this).find('.hasPlaceholder').each(function() { $(this).val(''); });
				});
			}
		}); 
	</script>

</head>
<body>
<?php
	if($isAdmin){
?>
   <div id="admin-top">
		<ul id="admin-top-nav">
			<li><a href="/admin/managepages">Manage Pages</a></li>
			<li><a href="/admin/manageusers">Manage Users</a></li>
			<?php if($controllerName=='cmsPages'){ ?>
			<li><a href="javascript:togglePreview()">Preview Page</a></li>
			<?php } ?>
		</ul>
		<ul id="admin-top-nav" class="right">
			<li><a href="/admin/logout">Logout</a></li>
		</ul>
	</div>
<?php } ?>    
	<div id="info-win"><div class="info-win-content"></div><div class="info-win-arrow"></div></div>
	<div id="container">
		<header>
			<div id="headerInner" class="inner">
				<h1><a href="/" id="logo">BYU InfoHub</a></h1>
				<h2><a href="/" id="site-title">InfoHub</a></h2>
				<div id="headerRight">
					<div id="request-queue" <?php if(!empty($casAuthenticated))  echo "class=loggedIn"; ?> >
						<?php
							$reqHiddenClass = '';
							if($requestedTermCount==0){
								$reqHiddenClass = 'request-hidden';
							}
						?>
						<a href="javascript: showRequestQueue()" title="Request Queue">
							<div class="request-num <?php echo $reqHiddenClass ?>"><?php echo $requestedTermCount ?></div>
							<img class="icon" src="/img/icon-cart.png" alt="Request Queue" title="Your Request">
						</a>
						<div id="request-popup"></div>
					</div>
					<span class="userInfo">
						<?php
							if(!$casAuthenticated){
								echo '<a href="/login" class="login">Login</a>';
							}else{
								echo '<a href="/myaccount">'.$byuUsername.'</a>';
								echo '<br><a href="/myaccount/logout" class="logout">Logout</a>';
							}
						?>
					</span>
					<!-- Below is fixed pos. on destop -->
					<div id="needHelp">
						<a id="mobileHelp"><img src="/img/icon-question.png" alt="Need Help?"></a>
						<a id="deskTopHelp" class="grow">Need <br>Help? <br><span>&nbsp;</span></a>
						<div id="nhContent">
							<a class="close">Close <br>X</a>
							<div id="nhLeft">
								<h3>Have questions? We're here to help.</h3>
								<p>If you need any help with your search, please <a href="mailto:infogov@byu.edu">Contact Us</a> and an information steward will get back to you within 24 hours.</p>
								
							</div>
							<img src="/img/questionQuote.gif" alt="Have Questions?">
						</div>
					</div>

				</div>
			</div>
		</header>
		<nav>
			<a id="mob-nav" class="box-shadow-menu inner">&nbsp;</a>
			<ul id="mainNav" class="inner">
				<li><a href="/search" id="searchLink">Search</a></li>
				<li><a href="/people" id="findLink">Find People</a></li>
				<li><a href="/resources" id="resourceLink">Resources</a></li>
				<?php
					if($casAuthenticated){
						echo '<li><a href="/myaccount" id="resourceLink">My Requests</a></li>';
					}
				?>
			</ul>
		</nav>
		<div id="content">

			<?php echo $this->Session->flash(); ?>

			<?php echo $this->fetch('content'); ?>
		</div>
		<footer>
			<div id="footerTop">
				<div class="inner">
					<h4>University Contact&nbsp;&nbsp;&nbsp;</h4>
					<div class="footerBox">
						<p>Mailing address: <br>Brigham Young University <br> Provo, UT 84602</p>
					</div>
					<div class="footerBox">
						<p>Telephone: <br><a href="tel:801-422-4636">801-422-4636</a> or <br><a href="tel:801-422-1211">801-422-1211</a></p>
					</div>
					<div class="footerBox">
						<p>Web: <br><a href="mailto:infogov@byu.edu">Contact Us</a></p>
					</div>
					<div class="footerBox">
						<p>Directions: <br><a href="https://www.google.com/maps/place/Brigham+Young+University/@40.251844,-111.649316,17z/data=!3m1!4b1!4m2!3m1!1s0x874d90bc4aa0b68d:0xbf3eb3a3f30fdc4c" target="_blank">Google Maps</a></p>
					</div>
				</div>
			</div>
			<div id="footerBottom">
				<div class="inner">
					<p>
						<a href="http://www.byui.edu/">BYU–Idaho</a> 
						<a href="http://www.byuh.edu/">BYU–Hawaii</a> 
						<a href="http://www.ldsbc.edu/">LDS Business College</a> 
						<a href="http://ce.byu.edu/sl/">Salt Lake Center</a> 
						<a href="http://ce.byu.edu/jc/">Jerusalem Center</a> 
						<a href="http://www.mtc.byu.edu/">Missionary Training Center</a>
					</p>
					<p>
						<a href="http://www.lds.org" style="margin:0 auto">The Church of Jesus Christ of Latter-day Saints</a><br>
						<a href="http://home.byu.edu/home/copyright">Copyright ©2015, All Rights Reserved</a>
					</p>
				</div>
			</div>
		</footer>
	</div>
	<?php echo $this->element('sql_dump'); ?>
</body>
</html>
