<?php
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Insert Video</title>
	<script type="text/javascript" src="../../tiny_mce_popup.js"></script>
	<script type="text/javascript" src="js/dialog.js"></script>
	<script type="text/javascript" src="../../utils/mctabs.js"></script>
	<script src="//code.jquery.com/jquery-2.1.3.min.js"></script>
	<link rel="STYLESHEET" type="text/css" href="css/styles.css">
	<script  type="text/javascript">
    <!--
    	function toggleButtons(n){
			if(n == 0){
				$("#btnInsert").hide();
				$("#btnSubmit").hide();
			}else if(n == 1){
				$("#btnInsert").hide();
				$("#btnSubmit").show();
			}else{
				$("#btnInsert").show();
				$("#btnSubmit").hide();
			}
		}
		
		$(document).ready(function(){
			toggleButtons(0);
			<?php echo $jsCode ?>
		});
		
    //-->
    </script>
</head>

<body>
<form action="video.php?pageID=<?php echo $pageID ?>" method="post" enctype="multipart/form-data" >
	<div class="tabs">
		<ul>
			<!--<li id="library_tab" class="current"><span><a href="javascript:mcTabs.displayTab('library_tab','library_panel');  toggleButtons(0);" onmousedown="return false;">Video Library</a></span></li>
			<li id="flv_tab"><span><a href="javascript:mcTabs.displayTab('flv_tab','flv_panel'); toggleButtons(1);" onmousedown="return false;">Add a file</a></span></li>-->
			<li id="youtube_tab" class="current"><span><a href="javascript:mcTabs.displayTab('youtube_tab','youtube_panel'); toggleButtons(2);" onmousedown="return false;">Video Embed</a></span></li>
		</ul>
	</div>
		
	<div class="panel_wrapper">
		
		<div id="youtube_panel" class="panel current">
			<p>Enter the embed code from Youtube.com below.</p>
			<p>embed code:<br/><textarea id="youtube_embed" name="youtube_embed"></textarea></p>
		</div>
	</div>
	
	<div class="mceActionPanel">
		<div style="float: left">
			<input type="button" id="btnInsert1" class="button" name="insert" value="Insert" onclick="insertYoutube();" />
		</div>
		<div style="float: right">
			<input type="button" id="cancel" name="cancel" value="Cancel" onclick="tinyMCEPopup.close();" />
		</div>
	</div>
</form>

</body>
</html>
