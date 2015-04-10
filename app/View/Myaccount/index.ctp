<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('account', null, array('inline' => false));
?>
<script>
	$(document).ready(colSize);
	$(window).resize(colSize);

	function colSize() {
		if($(window).width() > 650) {
			$('.riLeft').css('width', '100%').css('width', '-=270px');
		}
		else {
			$('.riLeft').css('width', '100%');
		}
	}
</script>

<!-- Background image div -->
<div id="accountBody" class="libOnebg">
</div>

<!-- Request list -->
<div id="accountMid" class="innerLower">
	<div id="accountTop">
		<h1 class="headerTab">My Account</h1>
		<div id="atLower" class="whiteBox">
			<h2>Christy Whitehouse</h2>
			<div id="aiDept" class="accountInfo"><span class="aiLabel">Department:&nbsp;</span>Business</div>
			<div id="aiRole" class="accountInfo"><span class="aiLabel">Role:&nbsp;</span>Information Steward</div>
			<div class="clear"></div>
		</div>
	</div>
	<div class="accountTabs">
		<a href="#"><div id="tabLeft" class="atTab active">Current Requests</div></a>
		<a href="#"><div id="tabright" class="atTab">Past Requests</div></a>
	</div>
	<div class="clear"></div>
	<div id="accountMain" class="whiteBox">
		<div class="requestItem">
			<div class="riLeft">
				<h4 class="riTitle">ABC Title of Report</h4>
				<p class="riDate"><span>Date Created:&nbsp;</span>03/08/2015</p>
				<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labare et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
				<img src="/img/iconReview.png" alt="Request in review">
			</div>
			<div class="contactBox">
				<span class="contactName">Brad Gonzales</span>
				<div class="contactNumber"><a href="tel:8015959845">801.595.9845</a></div>
				<div class="contactEmail"><a href="mailto:bgonzales@byu.edu">bgonzales@byu.edu</a></div>
			</div>
		</div>
		<div class="requestItem">
			<div class="riLeft">
				<h4 class="riTitle">ABC Title of Report</h4>
				<p class="riDate"><span>Date Created:&nbsp;</span>03/08/2015</p>
				<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labare et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
				<img src="/img/iconApproved.png" alt="Request in approved">
			</div>
			<div class="contactBox">
				<span class="contactName">Brad Gonzales</span>
				<div class="contactNumber"><a href="tel:8015959845">801.595.9845</a></div>
				<div class="contactEmail"><a href="mailto:bgonzales@byu.edu">bgonzales@byu.edu</a></div>
			</div>
		</div>
		<div class="requestItem">
			<div class="riLeft">
				<h4 class="riTitle">ABC Title of Report</h4>
				<p class="riDate"><span>Date Created:&nbsp;</span>03/08/2015</p>
				<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labare et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
				<img src="/img/iconApproved.png" alt="Request in approved">
			</div>
			<div class="contactBox">
				<span class="contactName">Brad Gonzales</span>
				<div class="contactNumber"><a href="tel:8015959845">801.595.9845</a></div>
				<div class="contactEmail"><a href="mailto:bgonzales@byu.edu">bgonzales@byu.edu</a></div>
			</div>
		</div>
	</div>
</div>

<!-- Quick links -->
<?php echo $this->element('quick_links'); ?>
