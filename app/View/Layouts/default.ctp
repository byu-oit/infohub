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
	<link href='http://fonts.googleapis.com/css?family=Rokkitt:400,700' rel='stylesheet' type='text/css'>
	<link href='http://fonts.googleapis.com/css?family=Open+Sans:400,600,700,300' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/themes/smoothness/jquery-ui.css" />
	<script src="//code.jquery.com/jquery-2.1.3.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/jquery-ui.min.js"></script>
	
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
                    }else if  ( e == true ) {
                        $('.autoComplete').hide();
                    }
                    else if  ( e.which == 27 ) {
                        $('.autoComplete').hide();
                        index = -1;
                    }
                    else if(e.which == 13) {
                        $('#searchInput').val($('.autoComplete li.active').text());
                        $('#searchInput').parent().submit();
                        $('.autoComplete').hide();
                    }else if(e.which == 38){
                        e.preventDefault();
                        if(index == -1){
                            index = $('.autoComplete li').length - 1;
                        }else {
                            index--;
                        }
                        
                        if(index > $('.autoComplete li').length ){
                            index = $('.autoComplete li').length + 1;
                        }
                        m = true;
                    }else if(e.which === 40){
                        e.preventDefault();
                        if(index >= $('.autoComplete li').length -1){
                            index = 0;
                        }else{
                            index++;
                        }
                        m = true;
                    }else{
                        var val = $('#searchInput').val();
                        $.get( "/search/autoCompleteTerm", { q: val } )
                            .done(function( data ) {
                                $('.autoComplete .results').html(data);
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
            $('#info-win .info-win-content').text(data);
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
                    $('#requestItem'+id).remove();
                    getCurrentRequestTerms();
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
					<span class="userInfo">
						<?php if(!$casAuthenticated) echo $this->Html->link('Login', '/login'); ?>
					</span>
					<div id="request-queue">
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
                    <?php if($casAuthenticated){ ?>
                    <a id="settingsWheel" href="/myaccount"><img src="/img/icon-settings.png" alt="My Account" title="My Account" /></a>
                    <?php } ?>

					<!-- Below is fixed pos. on destop -->
					<div id="needHelp">
						<a id="mobileHelp"><img src="/img/icon-question.png" alt="Need Help?"></a>
						<a id="deskTopHelp" class="grow">Need <br>Help? <br><span>&nbsp;</span></a>
						<div id="nhContent">
							<a class="close">Close <br>X</a>
							<div id="nhLeft">
								<h3>Have questions? We’re here to help.</h3>
								<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod veniam, quis nostrud exercitation ullamco laboris nisiut aliquip utexa commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur datat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. </p>
								<a href="">Contact Us</a>
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
			</ul>
		</nav>
		<div id="content">

			<?php echo $this->Session->flash(); ?>

			<?php echo $this->fetch('content'); ?>
		</div>
		<footer>
			<div id="footerTop">
				<div class="inner">
					<h4>Univerisity Contact&nbsp;&nbsp;&nbsp;</h4>
					<div class="footerBox">
						<p>Mailing address: <br>Brigham Young University <br> Provo, UT 84602</p>
					</div>
					<div class="footerBox">
						<p>Telephone: <br>801-422-4636 or <br>801-422-1211</p>
					</div>
					<div class="footerBox">
						<p>Web: <br><a href="/contact">Contact Us</a></p>
					</div>
					<div class="footerBox">
						<p>Directions: <br><a href="">Google Maps</a></p>
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
