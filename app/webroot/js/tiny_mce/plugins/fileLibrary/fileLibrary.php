<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>File Library</title>
	<script type="text/javascript" src="../../tiny_mce_popup.js"></script>
	<script type="text/javascript" src="js/dialog.js"></script>
	<script type="text/javascript" src="../../utils/mctabs.js"></script>
	<script type="text/javascript" src="../../utils/form_utils.js"></script>
	<script src="//code.jquery.com/jquery-2.1.3.min.js"></script>
	<link rel="STYLESHEET" type="text/css" href="css/styles.css">
	<script  type="text/javascript">
    <!--
		function toggleButtons(n){
			if(n == 0){
				$("#btnSubmit").hide();
			}else{
				$("#btnSubmit").show();
			}
		}
		
    	$(document).ready(function(){
			toggleButtons(0);
			<?php //echo $jsCode ?>
		});
		
    //-->
    </script>
</head>

<body>
<form action="fileLibrary.php?pageID=<?php echo $pageID ?>" method="post" enctype="multipart/form-data" >
	<div class="tabs">
		<ul>
			<li id="add_tab" class="current"><span><a href="javascript:mcTabs.displayTab('add_tab','add_panel'); toggleButtons(1);" onmousedown="return false;">Insert Image</a></span></li>                   
		</ul>
	</div>
	<div class="panel_wrapper">
		<br/>
		
		<div id="add_panel" class="panel current">
			<table border="0" cellpadding="0" cellspacing="7">
				<tr>
            		<td>File URL:</td>
					<td>
          	            <table border="0" cellspacing="0" cellpadding="0">
                            <tr>
                                <td><input id="src" name="src" type="text" class="mceFocus" value="" style="width: 200px" onchange="ImageDialog.getImageData();" /></td>
                                <td id="srcbrowsercontainer">&nbsp;</td>
                            </tr>
                        </table>
           	        </td>
            	</tr>
				<tr>
            		<td>Description:</td>
					<td><input id="imgDesc" name="imgDesc" type="text" /></td>
            	</tr>
            	<tr>
            		<td>Dimensions:</td>
					<td>
                        <table border="0" cellspacing="0" cellpadding="0">
                            <tr>
                                <td><input id="width" name="width" type="text" style="width: 30px" /></td>
                                <td>&nbsp;X&nbsp;</td>
                                <td><input id="height" name="height" type="text" style="width: 30px" /></td>
                            </tr>
                        </table>
                    </td>
            	</tr>
            </table>
		</div>
	</div>
	
	<div class="mceActionPanel">
		<div style="float: left">
			<input type="button" id="btnSubmit1" class="button" name="submit" value="submit" onclick="insertImage()" />
		</div>
		<div style="float: right">
			<input type="button" id="cancel" name="cancel" value="Cancel" onclick="tinyMCEPopup.close();" />
		</div>
	</div>
</form>

</body>
</html>
