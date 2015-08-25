<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('account', null, array('inline' => false));
?>
<script>
	$(window).resize(colSize);

	function colSize() {
		if($(window).width() > 650) {
			$('.riLeft').css('width', '100%').css('width', '-=270px');
		}
		else {
			$('.riLeft').css('width', '100%');
		}
	}

	$(document).ready(function() {
		colSize();
		$('.detailsTab').click(function() {
			var rid = $(this).attr('data-rid');
			$('#'+rid).slideToggle();
			$(this).toggleClass('active');
		});
	});
</script>

<!-- Background image div -->
<div id="accountBody" class="libOnebg">
</div>

<!-- Request list -->
<div id="accountMid" class="innerLower">
	<div id="accountTop">
		<h1 class="headerTab">My Account</h1>
		<div id="atLower" class="whiteBox">
			<h2><?php echo $psName ?></h2>
			<div id="aiDept" class="accountInfo"><span class="aiLabel">Department:&nbsp;</span><?php echo $psDepartment ?></div>
			<div id="aiRole" class="accountInfo"><span class="aiLabel">Role:&nbsp;</span><?php echo $psRole ?></div>
			<a class="logout" href="/myaccount/logout">Logout</a>
			<div class="clear"></div>
		</div>
	</div>
	<div class="accountTabs">
		<a href="/myaccount"><div id="tabLeft" class="atTab <?php if($page=='current') echo 'active' ?>">Current Requests</div></a>
		<a href="/myaccount/?s=2"><div id="tabright" class="atTab <?php if($page=='past') echo 'active' ?>">Past Requests</div></a>
	</div>
	<div class="clear"></div>
	<div id="accountMain" class="whiteBox">
<?php
	if(sizeof($requests)==0){
		echo '<div class="requestItem"><div class="riLeft"><h4 class="riTitle">No Requests Found</h4></div></div>';
	}else{
		foreach($requests as $req){
			echo '<div class="requestItem">'.
				'    <div class="riLeft">'.
				'        <h4 class="riTitle">'.$req->signifier.'</h4>'.
				'        <p class="riDate"><span>Date Created:&nbsp;</span>'.date('n/j/Y', ($req->createdOn)/1000).'</p>'.
				'        <p class="riDate"><strong>Requested Data:</strong><br>';
			$termCount = 0;
			foreach($req->terms->aaData as $term){
				echo $term->termsignifier;
				$termCount++;
				if($termCount < sizeof($req->terms->aaData)){
					echo ',&nbsp;&nbsp;';
				}
			}
			echo '</p>';
			if($page=='current'){
				echo '<img src="/img/iconReview.png" alt="Request in review">';
			}else{
				echo '<img src="/img/iconApproved.png" alt="Approved requests">';
			}
			echo '    </div>'.
				'    <!--<div class="contactBox">'.
				'        <span class="contactName">Brad Gonzales</span>'.
				'        <div class="contactNumber"><a href="tel:8015959845">801.595.9845</a></div>'.
				'        <div class="contactEmail"><a href="mailto:bgonzales@byu.edu">bgonzales@byu.edu</a></div>'.
				'    </div>-->'.
				'	<div class="detailsBody" id="'.$req->resourceId.'">'.
				'		<h4>Request Details</h4>';

			foreach($req->attributeReferences->attributeReference as $attrRef){
				if($attrRef->labelReference->signifier != 'Requester Person ID'){
					echo '<div class="data-col"><h5>'.$attrRef->labelReference->signifier.'</h5>'.
						'<p>'.$attrRef->value.'</p></div>';
				}
			}

			echo '	</div>'.
				'	<a class="detailsTab" data-rid="'.$req->resourceId.'"><span class="detailsLess">Fewer</span><span class="detailsMore">More</span>&nbsp;Details</a>'.
				'</div>';

		}
	}
?>
	</div>
</div>

<!-- Quick links -->
<?php echo $this->element('quick_links'); ?>
