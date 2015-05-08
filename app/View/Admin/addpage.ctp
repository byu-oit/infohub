<script  type="text/javascript">
    function checkPageURL(fld){
        //val = val.replace(/\W/g, '');
        var val = $(fld).val().replace(/[\s]+/g,'-');
        val = val.replace(/[^a-zA-Z0-9\-]+/g,'');
        $(fld).val(val);
    }
    
    function setPageURL(fld){
        var val = $(fld).val().replace(/[^a-zA-Z0-9\s]+/g,'');
        $(fld).val(val);
        
        val = $(fld).val().toLowerCase();
        $('#CmsPageSlug').val(val);
        checkPageURL($('#CmsPageSlug'));
    }
</script>
<table width="100%" class="form">
    <tr>
        <td valign="top">
            <div id="pageList-container" style="width: 220px; height: 500px; overflow: auto;">
                <?php echo $this->element('admin/pagenav'); ?>
            </div>
        </td>
        <td valign="top" width="100%" style="padding: 10px 20px 20px 20px; border-left: solid 1px #ccc;">
<?php
    echo $this->Form->create('CmsPage');
    $pgID = $page['id'];
?>
            <table border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td colspan="2">
                        <h2>Add Page Under <?php echo $page['title'] ?></h2>
                    </td>
                </tr>
                <tr>
                    <td style="padding-bottom: 10px;">
                        <?php echo $this->Form->input('title', array('label'=>'Page Title', 'maxlength'=>'50', 'class'=>'fld', 'onblur'=>'setPageURL(this)')); ?>
                    </td>
                    <td style="padding-bottom: 10px;">
                        <?php echo $this->Form->input('slug', array('label'=>'Page URL', 'maxlength'=>'50', 'class'=>'fld', 'onblur'=>'checkPageURL(this)')); ?>
                    </td>
                </tr>	
                <tr>
                    <td style="padding-bottom: 10px;" valign="top">
                        <?php echo $this->Form->input('redirectURL', array('label'=>'Redirect URL', 'class'=>'fld')); ?>
                    </td>
                    <td style="padding-bottom: 10px;">
                        <?php echo $this->Form->checkbox('active'); ?>
                        <label class="inline" for="CmsPageActive">Public Page</label>
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
    echo $this->Form->input('parentID', array('type' => 'hidden', 'value'=>$parentID));
    echo $this->Form->input('rank', array('type' => 'hidden', 'value'=>$rank));
    echo $this->Form->end();
?>
        </td>
    </tr>
</table>