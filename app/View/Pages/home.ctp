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
	<p>test</p>
</div>
<div id="homeQL">
	
</div>
<div id="homeHowTo">
	
</div>