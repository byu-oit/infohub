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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
	<title>Admin</title>
	<?php
		echo $this->Html->css('popup-styles');
        echo $this->Html->css('admin-nav');
	?>
	<script src="//code.jquery.com/jquery-2.1.3.min.js"></script>
	<script  type="text/javascript">
		function pgTitleBlured(){
			var pgTitleFld = document.forms[0].pgTitle;
			var displayFld = document.forms[0].pgDisplayTitle;
			
			if(displayFld.value == ""){
				displayFld.value = pgTitleFld.value;
			}
			
			pgTitleFld.value = pgTitleFld.value.replace(/[^a-zA-Z 0-9]+/g,'');
		}
        
        $(document).ready(function(){
            $('#pageList li .expand, #pageList li .close').click(function(){
                $(this).parent().parent().find('.left-subnav').toggle();
                $(this).toggleClass('expand').toggleClass('close');
                return false;
            });
        });
    </script>
</head>

<body class="cmsWindow">
    <div id="admin-top">
        <ul id="admin-top-nav">
            <li><a href="/">Return to InfoHub</a></li>
            <li><a href="/admin/managepages">Manage Pages</a></li>
            <li><a href="/admin/manageusers">Manage Users</a></li>
        </ul>
        <ul id="admin-top-nav" class="right">
            <li><a href="/admin/logout">Logout</a></li>
        </ul>
    </div>
    <div id="header">

    </div>
    <div id="content">
        <?php echo $this->Session->flash(); ?>

        <?php echo $this->fetch('content'); ?>
    </div>
</body>
</html>
