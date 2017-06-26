<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('search', null, array('inline' => false));
?>

<!-- Background image div -->
<div class="searchBg">

	<div id="searchBody" class="innerLower">

		<div id="searchSuccess">
			<h2 class="headerTab" >Thank You</h2>

			<div id="successLower" class="whiteBox">
				<p>Your request has been submitted and you will be notified once it has been reviewed and approved.</p>
				<p>Review your current requests on <?php echo $this->Html->link('My Requests', '/myaccount'); ?>.</p>
			</div>
			<div class="clear"></div>
		</div>

	</div>

</div>
<div class="clear"></div>
