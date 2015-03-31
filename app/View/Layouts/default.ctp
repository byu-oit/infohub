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

$cakeDescription = __d('cake_dev', 'CakePHP: the rapid development php framework');
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
		echo $this->fetch('meta');
		echo $this->fetch('css');
		echo $this->fetch('script');
	?>
	<link href='http://fonts.googleapis.com/css?family=Rokkitt:400,700' rel='stylesheet' type='text/css'>
	<link href='http://fonts.googleapis.com/css?family=Open+Sans:400,600,700,300' rel='stylesheet' type='text/css'>
	<script src="//code.jquery.com/jquery-2.1.3.min.js"></script>
	<script>
		$("#needHelp").click(function() {

		});
	</script>
</head>
<body>
	<div id="container">
		<header>
			<div id="headerInner" class="inner">
				<h1><a href="/" id="logo">BYU InfoHub</a></h1>
				<h2><a href="/" id="site-title">InfoHub</a></h2>
				<div id="headerLeft">
					<span class="userInfo">Welcome, Christy</span>
					<a id="settingsWheel"><img src="/img/icon-settings.png" alt="Settings"></a>
					<!-- Below is fixed pos. on destop -->
					<div id="needHelp">
						<a href="" id="mobileHelp"><img src="/img/icon-question.png" alt="Need Help?"></a>
						<a href="" id="deskTopHelp">Need <br>Help? <br><span>&nbsp;</span></a>
						<div id="nhContent">
							<a class="close">Close X</a>
							<h3>Have questions? We’re here to help.</h3>
							<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod veniam, quis nostrud exercitation ullamco laboris nisiut aliquip utexa commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur datat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. <br>
								<a href="">Contact Us</a></p>
						</div>
					</div>
				</div>
			</div>
		</header>
		<nav>
			<ul class="inner">
				<li><a href="#">Search</a></li>
				<li><a href="#">Find People</a></li>
				<li><a href="#">Resources</a></li>
			</ul>
		</nav>
		<div id="content">

			<?php echo $this->Session->flash(); ?>

			<?php echo $this->fetch('content'); ?>
		</div>
		<div id="footer">
			<?php echo $this->Html->link(
					$this->Html->image('cake.power.gif', array('alt' => $cakeDescription, 'border' => '0')),
					'http://www.cakephp.org/',
					array('target' => '_blank', 'escape' => false, 'id' => 'cake-powered')
				);
			?>
			<p>
				<?php echo $cakeVersion; ?>
			</p>
		</div>
	</div>
	<?php echo $this->element('sql_dump'); ?>
</body>
</html>
