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
		</div>
	</div>	
</div>
<div id="homeQL">
	
</div>
<div id="homeHowTo">
	
</div>