<script  type="text/javascript">
    
</script>
<table width="100%" class="form">
    <tr>
        <td valign="top">
            <div id="pageList-container" style="width: 220px; height: 500px; overflow: auto;">
                <?php echo $this->element('admin/usernav'); ?>
            </div>
        </td>
        <td valign="top" width="100%" style="padding: 10px 20px 20px 20px; border-left: solid 1px #ccc;">
<?php
    echo $this->Form->create('CmsUsers');
?>
            <table border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="padding-bottom: 10px;">
                        <?php echo $this->Form->input('username', array('label'=>'Email', 'maxlength'=>'50', 'class'=>'fld', 'type'=>'email')); ?>
                    </td>
                    <td style="padding-bottom: 10px;">
                        <?php echo $this->Form->input('password', array('label'=>'Password', 'maxlength'=>'50', 'class'=>'fld')); ?>
                    </td>
                </tr>	
                <tr>
                    <td colspan="2" style="padding-bottom: 10px;">
                        <?php echo $this->Form->checkbox('active', array('checked'=>true)); ?>
                        <label class="inline" for="CmsUsersActive">Active User</label>
                    </td>
                </tr>
                <tr>
                    <td style="padding-bottom: 10px;" colspan="2">
				        <input class="btn" type="submit" name="btnSubmit" value="Add New" />&nbsp;&nbsp;
                        <input class="btn-cancel" type="button" name="btnCancel" value="Cancel" onclick="parent.tb_remove()" />
                    </td>
                </tr>
            </table>
<?php
    echo $this->Form->end();
?>
        </td>
    </tr>
</table>