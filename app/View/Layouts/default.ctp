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
		echo $this->Html->css('font-awesome.min');
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
	<?= $this->Html->script('sitebase') ?>

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
				<li><a href="/apis" id="apisLink">APIs</a></li>
				<li>
					<a href="https://developer.byu.edu/api/api-list" target="_blank">
						<i class="fa fa-external-link" aria-hidden="true"></i>
						API Documentation
					</a>
				</li>
				<li>
					<a href="https://api.byu.edu/store/" target="_blank">
						<i class="fa fa-external-link" aria-hidden="true"></i>
						API Store
					</a>
				</li>
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
