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
    $userID = $user['id'];
?>
            <table border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td colspan="2">
                        <h2>Edit <?php echo $user['username'] ?></h2>
                        <a href="/admin/deleteuser/<?php echo $userID ?>" onclick="return confirm('Are you sure you want to delete this user?')"><img src="/img/admin/delete.png" title="Delete Page" border="0" /> Delete User</a>
                        <div class="hr"></div>
                    </td>
                </tr>
                <tr>
                    <td style="padding-bottom: 10px;">
                        <strong>Net ID:</strong> <?= $this->request->data['CmsUsers']['username'] ?>
                    </td>
                </tr>	
                <tr>
                    <td colspan="2" style="padding-bottom: 10px;">
                        <?php echo $this->Form->checkbox('active'); ?>
                        <label class="inline" for="CmsUsersActive">Active User</label>
                    </td>
                </tr>
                <tr>
                    <td style="padding-bottom: 10px;" colspan="2">
				        <input class="btn" type="submit" name="btnSubmit" value="Update" />&nbsp;&nbsp;
                        <input class="btn-cancel" type="button" name="btnCancel" value="Cancel" onclick="parent.tb_remove()" />
                    </td>
                </tr>
            </table>
<?php
    echo $this->Form->input('id', array('type' => 'hidden'));
    echo $this->Form->end();
?>
        </td>
    </tr>
</table>