<?php
	$this->Html->css('secondary', null, array('inline' => false));
?>

<div class="innerLower">
    <div class="login-form">
        <?php echo $this->Form->create('login'); ?>
        <?php echo $this->Form->input('Username', array('maxlength'=>'50')); ?>
        <?php echo $this->Form->input('Password', array('type'=>'password','maxlength'=>'50')); ?>
        <?php echo $this->Form->end('Submit'); ?>
    </div>
</div>