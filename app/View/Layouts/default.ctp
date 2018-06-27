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
		if (Configure::read('debug') > 0) {
			echo '<meta name="robots" content="noindex" />';
		}
		echo $this->Html->meta('icon');
		// echo $this->Html->css('cake.generic');
		echo $this->Html->css('styles');
		echo $this->Html->css('font-awesome.min');
		echo $this->Html->css('admin-nav');
		echo $this->fetch('meta');
		echo $this->fetch('css');
		echo $this->fetch('script');
	?>
	<meta name="google" content="notranslate">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href='//fonts.googleapis.com/css?family=Rokkitt:400,700' rel='stylesheet' type='text/css'>
	<link href='//fonts.googleapis.com/css?family=Open+Sans:400,600,700,300' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/themes/smoothness/jquery-ui.css" />
	<link href='//fonts.googleapis.com/css?family=Voltaire' rel='stylesheet' type='text/css'>
	<script src="//code.jquery.com/jquery-2.1.3.min.js"></script>
	<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/jquery-ui.min.js"></script>
	<?= $this->Html->script('sitebase') ?>

<script>
	<?php if (Configure::read('debug') == 0): ?>
		window['_fs_debug'] = false;
		window['_fs_host'] = 'fullstory.com';
		window['_fs_org'] = '5S1VK';
		window['_fs_namespace'] = 'FS';
		(function(m,n,e,t,l,o,g,y){
		    if (e in m && m.console && m.console.log) { m.console.log('FullStory namespace conflict. Please set window["_fs_namespace"].'); return;}
		    g=m[e]=function(a,b){g.q?g.q.push([a,b]):g._api(a,b);};g.q=[];
		    o=n.createElement(t);o.async=1;o.src='https://'+_fs_host+'/s/fs.js';
		    y=n.getElementsByTagName(t)[0];y.parentNode.insertBefore(o,y);
		    g.identify=function(i,v){g(l,{uid:i});if(v)g(l,v)};g.setUserVars=function(v){g(l,v)};
		    g.identifyAccount=function(i,v){o='account';v=v||{};v.acctId=i;g(o,v)};
		    g.clearUserCookie=function(c,d,i){if(!c || document.cookie.match('fs_uid=[`;`]*`[`;`]*`[`;`]*`')){
		    d=n.domain;while(1){n.cookie='fs_uid=;domain='+d+
		    ';path=/;expires='+new Date(0).toUTCString();i=d.indexOf('.');if(i<0)break;d=d.slice(i+1)}}};
		})(window,document,window['_fs_namespace'],'script','user');
		<?php if ($casAuthenticated): ?>
			FS.identify('<?=$netID?>', {
				displayName: '<?=$byuUsername?>',
				department: '<?=$byuUserDepartment?>'
			});
		<?php else: ?>
			FS.identify(false);
		<?php endif ?>
	<?php endif ?>
	function login() {
		window.location.href = '/login?return=' + encodeURIComponent(window.location.pathname);
	}


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
								$reqHiddenClass = ' request-hidden';
							}
						?>
						<a href="javascript: toggleRequestQueue()" title="Request Queue">
							<div class="request-num<?php echo $reqHiddenClass ?>"><?php echo $requestedTermCount ?></div>
							<img class="icon" src="/img/icon-cart.png" alt="Request Queue" title="Your Request">
						</a>
						<div id="request-popup"></div>
					</div>
					<span class="userInfo">
						<?php
							if(!$casAuthenticated){
								echo '<a href="javascript:login()" class="login">Login</a>';
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
				<li id="browse-tab">
					Browse Data
					<div id="drop-down-menu">
						<a href="/search" class="drop-down-link">Search Business Terms</a>
						<h6>Technology Types</h6>
						<a href="/apis" class="drop-down-link">APIs</a>
						<a href="/databases" class="drop-down-link">Databases</a>
					</div>
				</li>
				<li><a href="/people" id="findLink">Find People</a></li>
				<li><a href="/resources" id="resourceLink">Procedures</a></li>
				<li>
					<a href="https://developer.byu.edu/" target="_blank">
						<i class="fa fa-external-link" aria-hidden="true"></i>
						Developer Portal
					</a>
				</li>
				<li>
					<a href="https://api.byu.edu/store/" target="_blank">
						<i class="fa fa-external-link" aria-hidden="true"></i>
						API Store
					</a>
				</li>
				<li><a href="/myaccount" id="myRequestsLink">My Requests</a></li>
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
