
<table width="50%" class="login-form">
    <tr>
        <td valign="top">
            <?php echo $this->Form->create('login'); ?>
            <?php echo $this->Form->input('Username', array('maxlength'=>'50')); ?>
            <?php echo $this->Form->input('Password', array('type'=>'password','maxlength'=>'50')); ?>
            <?php echo $this->Form->end('Submit'); ?>
        </td>
    </tr>
</table>