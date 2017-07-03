<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('account', null, array('inline' => false));
?>
<script>
	$(document).ready(function() {
		$('#close').click(function() {
			$(this).parent().fadeOut('fast');
		});
		$('.details-btn').click(function() {
			var rid = $(this).attr('data-rid');
			$('#'+rid).slideToggle();
			$(this).toggleClass('active');
		});
		$('.print').click(function() {
			window.open('/request/printView/' + $(this).attr('data-rid'), '_blank').focus();
		});
		$('.edit').click(function() {
			window.location.href = '/request/edit/' + $(this).attr('data-rid');
		});

		$('.approver .user-icon').on('mouseover click', function(){
			$(this).parent().find('.info').css('z-index', 20).toggle();
		});
		$('.approver .user-icon').mouseout(function(){
			$(this).parent().find('.info').hide();
		});
		<?php if (!empty($expand)): ?>
			$('#<?=$expand?>').slideToggle();
			$('#<?=$expand?>').parent().find('.details-btn[data-rid="<?=$expand?>"]').toggleClass('active');
		<?php endif ?>
	});
</script>
<!-- Background image div -->
<div id="accountBody" class="libOnebg">
</div>

<div id="accountMid" class="innerLower">
	<div id="accountTop">
		<div id="atLower" class="whiteBox">
			<div class="accountInfo"><span class="aiLabel">Anyone with whom you share the link to this page can view <strong>and edit</strong> this <?php echo $parent ? 'request.' : 'agreement.'; ?></span></div>
			<div class="clear"></div>
			<div id="close" class="grow">Got it!</div>
		</div>
	</div>
	<div class="clear"></div>
	<div id="accountMain" class="whiteBox">
<?php
		echo '<div class="requestItem">'.
			'    <div class="riLeft">'.
			'        <h4 class="riTitle">'.$request->signifier.'</h4>'.
			'        <p class="riDate"><span>Date Created:&nbsp;</span>'.date('n/j/Y', ($request->createdOn)/1000).'</p>'.
			'        <p class="riDate"><strong>Requested Data:</strong><br>';
		$termCount = 0;
		foreach($request->terms as $term){
			echo $term->termsignifier;
			$termCount++;
			if($termCount < sizeof($request->terms)){
				echo ',&nbsp;&nbsp;';
			}
		}
		echo '</p>';
		echo '<div class="status-details-flex"><div class="status-wrapper">';
		if($request->statusReference->signifier == 'Completed'){
			echo '<div class="status-cell light-green-border left">In Progress</div><div class="status-cell green-border right active">Approved</div>';
		}elseif($request->statusReference->signifier == 'Rejected'){
			echo '<div class="status-cell light-red-border left">In Progress</div><div class="status-cell red-border right active">Rejected</div>';
		}elseif($request->statusReference->signifier == 'In Progress'){
			echo '<div class="status-cell green-border left active">In Progress</div><div class="status-cell light-green-border right">Approved</div>';
		}else{
			echo '<div class="status-cell obsolete">Obsolete</div>';
		}
		echo '</div>';

		echo '	<a class="details-btn grow" data-rid="'.$request->resourceId.'"><span class="detailsLess">Fewer</span><span class="detailsMore">More</span>&nbsp;Details</a>';

		echo '</div></div>';

		// display approvers and their info
		////////////////////////////////////////
		echo '<div class="riRight">'.
			'<h4 class="riTitle">Coordinators for this '; echo $parent ? 'Request' : 'Agreement'; echo '</h4>'.
			'<div class="approverPics">';
		if ($parent) {
			foreach($request->roles['Request Cordinator'] as $rc){			// Yes, 'cordinator' is misspelled here, but that's how the data comes out
				$approverName = $rc->firstName . " " . $rc->lastName;
				if($approverName != ''){
					$approverImage = '../photos/collibraview/'.$rc->resourceId;
					$approverEmail = $rc->emailAddress;
					echo '<div class="approver">'.
						'	<div class="user-icon" style="background-image: url(../'.$approverImage.');"></div>'.
						'	<div class="info">'.
						'		<div class="contactName">'.$approverName.'</div>'.
						'		<div class="contactEmail"><div class="icon"></div><a href="mailto:'.$approverEmail.'">'.$approverEmail.'</a></div>'.
						'	</div>'.
						'</div>';
				}
			}
		} else {
			$oneApprover = (
				$request->roles['Steward'][0]->firstName . " " . $request->roles['Steward'][0]->lastName
				== $request->roles['Custodian'][0]->firstName . " " . $request->roles['Custodian'][0]->lastName
			);
			$approverName = $request->roles['Steward'][0]->firstName . " " . $request->roles['Steward'][0]->lastName;
			if($approverName != ''){
				$approverImage = '../photos/collibraview/'.$request->roles['Steward'][0]->resourceId;
				$approverEmail = $request->roles['Steward'][0]->emailAddress;
				echo '<div class="approver steward">'.
					'	<div class="user-icon" style="background-image: url(../'.$approverImage.');"></div>'.
					'	<div class="info">'.
					'		<div class="contactName">'.$approverName.'</div>';
					if (!$oneApprover) {
						echo '<div class="approverRole"><div class="icon"></div>Steward';
					} else {
						echo '<div class="approverRole"><div class="icon"></div>Custodian and Steward';
					}
					echo '</div>'.
					'		<div class="contactEmail"><div class="icon"></div><a href="mailto:'.$approverEmail.'">'.$approverEmail.'</a></div>'.
					'	</div>'.
					'</div>';
			}
			if(!$oneApprover){
				$approverName = $request->roles['Custodian'][0]->firstName . " " . $request->roles['Custodian'][0]->lastName;
				if($approverName != ''){
					$approverImage = '../photos/collibraview/'.$request->roles['Custodian'][0]->resourceId;
					$approverEmail = $request->roles['Custodian'][0]->emailAddress;
					echo '<div class="approver custodian">'.
						'	<div class="user-icon" style="background-image: url(../'.$approverImage.');"></div>'.
						'	<div class="info">'.
						'		<div class="contactName">'.$approverName.'</div>'.
						'		<div class="approverRole"><div class="icon"></div>Custodian</div>'.
						'		<div class="contactEmail"><div class="icon"></div><a href="mailto:'.$approverEmail.'">'.$approverEmail.'</a></div>'.
						'	</div>'.
						'</div>';
				}
			}
		}
		echo '</div></div>';
		echo '	<div class="detailsBody" id="'.$request->resourceId.'">';
?>

		<h3 class="headerTab">Requester</h3>
		<div class="clear"></div>
		<div class="data-col">
			<h5>Name:</h5>
			<div class="attrValue"><?php echo $request->attributeReferences->attributeReference['Requester Name']->value ?></div>
		</div>
		<div class="data-col">
			<h5>Phone Number:</h5>
			<div class="attrValue"><?php echo $request->attributeReferences->attributeReference['Requester Phone']->value ?></div>
		</div>
		<div class="data-col">
			<h5>Email:</h5>
			<div class="attrValue"><?php echo $request->attributeReferences->attributeReference['Requester Email']->value ?></div>
		</div>
		<div class="data-col">
			<h5>Role:</h5>
			<div class="attrValue"><?php echo $request->attributeReferences->attributeReference['Requester Role']->value ?></div>
		</div>
		<div class="data-col">
			<h5>Requesting Organization:</h5>
			<div class="attrValue"><?php echo $request->attributeReferences->attributeReference['Requesting Organization']->value ?></div>
		</div>
		<div class="clear"></div>

		<h3 class="headerTab">Sponsor</h3>
		<div class="clear"></div>
		<div class="data-col">
			<h5>Sponsor Name:</h5>
			<div class="attrValue"><?php echo $request->attributeReferences->attributeReference['Sponsor Name']->value ?></div>
		</div>
		<div class="data-col">
			<h5>Sponsor Role:</h5>
			<div class="attrValue"><?php echo $request->attributeReferences->attributeReference['Sponsor Role']->value ?></div>
		</div>
		<div class="data-col">
			<h5>Sponsor Email:</h5>
			<div class="attrValue"><?php echo $request->attributeReferences->attributeReference['Sponsor Email']->value ?></div>
		</div>
		<div class="data-col">
			<h5>Sponsor Phone:</h5>
			<div class="attrValue"><?php echo $request->attributeReferences->attributeReference['Sponsor Phone']->value ?></div>
		</div>
		<div class="clear"></div>

		<h3 class="headerTab">Application Name</h3>
		<div class="clear"></div>
		<div class="attrValue"><?= $request->attributeReferences->attributeReference['Application Name']->value ?></div>

<?php
		$arrNonDisplay = [
			"Requester Name",
			"Requester Email",
			"Requester Phone",
			"Information Elements",
			"Requester Role",
			"Requesting Organization",
			"Sponsor Name",
			"Sponsor Role",
			"Sponsor Email",
			"Sponsor Phone",
			"Requester Person Id",
			"Requester Net Id",
			"Request Date",
			"Application Name"
		];
		$completedStatuses = ['Completed', 'Approved', 'Obsolete'];
		if (empty($request->dataUsages)) {
			foreach($request->attributeReferences->attributeReference as $attrRef){
				if(!in_array($attrRef->labelReference->signifier, $arrNonDisplay) && !empty($attrRef->value)){
					echo '<h3 class="headerTab">'.$attrRef->labelReference->signifier.'</h3><div class="clear"></div>'.
						'<div class="attrValue">'.$attrRef->value.'</div>';
				}
			}
		}
		if (empty($request->dataUsages)) {
			echo '<div class="lower-btn edit grow" data-rid="'.$request->resourceId.'">Edit</div>';
		}
		echo '<div class="lower-btn print grow" data-rid="'.$request->resourceId.'">Print</div>';
		echo '</div>';

		foreach($request->dataUsages as $du) {
			echo '<div class="riBelow">';
			$dsaName = $du->signifier;
			$dsaStatus = strtolower($du->status);
			echo '<div class="subrequestNameWrapper"><h6 class="riTitle subrequestName">'.$dsaName.'</h6></div>';
			echo '<div class="approverPics">';
			$oneApprover = (
				$du->roles['Steward'][0]->firstName . " " . $du->roles['Steward'][0]->lastName
				== $du->roles['Custodian'][0]->firstName . " " . $du->roles['Custodian'][0]->lastName
			);
			$approverName = $du->roles['Steward'][0]->firstName . " " . $du->roles['Steward'][0]->lastName;
			if($approverName != ''){
				$approverImage = '../photos/collibraview/'.$du->roles['Steward'][0]->resourceId;
				$approverEmail = $du->roles['Steward'][0]->emailAddress;
				echo '<div class="approver steward">'.
					'	<div class="user-icon" style="background-image: url(../'.$approverImage.');"></div>'.
					'	<div class="info">'.
					'		<div class="contactName">'.$approverName.'</div>';
					if (!$oneApprover) {
						echo '<div class="approverRole"><div class="icon"></div>Steward';
					} else {
						echo '<div class="approverRole"><div class="icon"></div>Custodian and Steward';
					}
					echo '</div>'.
					'		<div class="contactEmail"><div class="icon"></div><a href="mailto:'.$approverEmail.'">'.$approverEmail.'</a></div>'.
					'	</div>'.
					'</div>';
			}
			if(!$oneApprover){
				$approverName = $du->roles['Custodian'][0]->firstName . " " . $du->roles['Custodian'][0]->lastName;
				if($approverName != ''){
					$approverImage = '../photos/collibraview/'.$du->roles['Custodian'][0]->resourceId;
					$approverEmail = $du->roles['Custodian'][0]->emailAddress;
					echo '<div class="approver custodian">'.
						'	<div class="user-icon" style="background-image: url(../'.$approverImage.');"></div>'.
						'	<div class="info">'.
						'		<div class="contactName">'.$approverName.'</div>'.
						'		<div class="approverRole"><div class="icon"></div>Custodian</div>'.
						'		<div class="contactEmail"><div class="icon"></div><a href="mailto:'.$approverEmail.'">'.$approverEmail.'</a></div>'.
						'	</div>'.
						'</div>';
				}
			}
			echo '</div>';
			echo '<br />';
			echo '<div class="status-details-flex"><div class="status-wrapper">';
			if($dsaStatus == 'candidate' || $dsaStatus == 'in progress'){
				echo '<div class="status-cell green-border left active">In Progress</div><div class="status-cell light-green-border right">Approved</div>';
			}elseif($dsaStatus == 'approved'){
				echo '<div class="status-cell light-green-border left">In Progress</div><div class="status-cell green-border right active">Approved</div>';
			}elseif($dsaStatus == 'rejected'){
				echo '<div class="status-cell light-red-border left">In Progress</div><div class="status-cell red-border right active">Rejected</div>';
			}else{
				echo '<div class="status-cell obsolete">Obsolete</div>';
			}
			echo '</div>';
			echo '	<a class="details-btn grow" data-rid="'.$du->id.'"><span class="detailsLess">Fewer</span><span class="detailsMore">More</span>&nbsp;Details</a></div></div>';

			echo '<div class="detailsBody" id="'.$du->id.'">';
			echo '<p class="riDate"><strong>Requested Data:</strong><br>';
			$termCount = 0;
			foreach($request->terms as $term){
				echo $term->termsignifier;
				$termCount++;
				if($termCount < sizeof($request->terms)){
					echo ',&nbsp;&nbsp;';
				}
			}
			echo '</p>';
			foreach($du->attributeReferences->attributeReference as $attr){
				if(!in_array($attr->labelReference->signifier, $arrNonDisplay) && !empty($attr->value)){
					echo '<h3 class="headerTab">'.$attr->labelReference->signifier.'</h3><div class="clear"></div>'.
						'<div class="attrValue">'.$attr->value.'</div>';
				}
			}

			if (!in_array($du->status, $completedStatuses)) {
				echo '<div class="lower-btn edit grow" data-rid="'.$du->id.'">Edit</div>';
			}
			echo '<div class="lower-btn print grow" data-rid="'.$du->id.'">Print</div>';
			echo '</div>';
		}
	echo '</div>';
?>
	</div>
</div>

<!-- Quick links -->
<?php echo $this->element('quick_links'); ?>
