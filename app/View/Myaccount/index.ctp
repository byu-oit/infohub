<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('account', null, array('inline' => false));
?>
<script>
	$(window).resize(colSize);

	function colSize() {
		if($(window).width() > 650) {
			//$('.riLeft').css('width', '100%').css('width', '-=270px');
		}
		else {
			//$('.riLeft').css('width', '100%');
		}
	}

	$(document).ready(function() {
		colSize();
		$('.details-btn').click(function() {
			var rid = $(this).attr('data-rid');
			$('#'+rid).slideToggle();
			$(this).toggleClass('active');
		});
		$('.share').click(function() {
			window.location.href = '/request/view/' + $(this).attr('data-rid');
		});
		$('.print').click(function() {
			window.location.href = '/request/print/' + $(this).attr('data-rid');
		});
		$('.edit').click(function() {
			window.location.href = '/request/edit/' + $(this).attr('data-rid');
		});
		$('.delete').click(function() {
			if (confirm("Are you sure you would like to delete this request?")) {
				window.location.href = '/request/delete/' + $(this).attr('data-rid');
			}
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

<!-- Request list -->
<div id="accountMid" class="innerLower">
	<div id="accountTop">
		<h1 class="headerTab">My Requests</h1>
		<div id="atLower" class="whiteBox">
			<h2><?php echo $psName ?></h2>
			<div id="aiDept" class="accountInfo"><span class="aiLabel">Department:&nbsp;</span><?php echo $psDepartment ?></div>
			<div id="aiRole" class="accountInfo"><span class="aiLabel">Role:&nbsp;</span><?php echo $psRole ?></div>
			<a class="logout" href="/myaccount/logout">Logout</a>
			<div class="clear"></div>
		</div>
	</div>
	<div class="accountTabs">
		<a href="/myaccount"><div id="tabLeft" class="atTab <?php if($page=='current') echo 'active' ?>">Pending Requests</div></a>
		<a href="/myaccount/?s=2"><div id="tabright" class="atTab <?php if($page=='past') echo 'active' ?>">Completed Requests</div></a>
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
			echo '<div class="status-details-flex"><div class="status-wrapper">';
			if($req->statusReference->signifier == 'Completed'){
				echo '<div class="status-cell light-green-border left">In Progress</div><div class="status-cell green-border right active">Approved</div>';
			}elseif($req->statusReference->signifier == 'Rejected'){
				echo '<div class="status-cell light-red-border left">In Progress</div><div class="status-cell red-border right active">Rejected</div>';
			}elseif($req->statusReference->signifier == 'In Progress'){
				echo '<div class="status-cell green-border left active">In Progress</div><div class="status-cell light-green-border right">Approved</div>';
			}else{
				echo '<div class="status-cell obsolete">Obsolete</div>';
			}
			echo '</div>';

			echo '	<a class="details-btn grow" data-rid="'.$req->resourceId.'"><span class="detailsLess">Fewer</span><span class="detailsMore">More</span>&nbsp;Details</a>';

			echo '</div></div>';

			// display approvers and their info
			////////////////////////////////////////
			echo '<div class="riRight">'.
				'<h4 class="riTitle">Coordinators for this Request</h4>'.
				'<div class="approverPics">';
			foreach($req->roles['Request Cordinator'] as $rc){			//Yes, 'cordinator' is misspelled here, but that's how the data comes out
				$approverName = $rc->firstName . " " . $rc->lastName;
				if($approverName != ''){
					$approverImage = '../photos/collibraview/'.$rc->resourceId;
					$approverEmail = $rc->emailAddress;
					echo '<div class="approver">'.
						'	<div class="user-icon" style="background-image: url('.$approverImage.');"></div>'.
						'	<div class="info">'.
						'		<div class="contactName">'.$approverName.'</div>'.
						'		<div class="contactEmail"><div class="icon"></div><a href="mailto:'.$approverEmail.'">'.$approverEmail.'</a></div>'.
						'	</div>'.
						'</div>';
				}
			}
			echo '</div></div>';
			echo '	<div class="detailsBody" id="'.$req->resourceId.'">';
?>

			<h3 class="headerTab">Requester</h3>
			<div class="clear"></div>
			<div class="data-col">
				<h5>Name:</h5>
				<p><?php echo $req->attributeReferences->attributeReference['Requester Name']->value ?></p>
			</div>
			<div class="data-col">
				<h5>Phone Number:</h5>
				<p><?php echo $req->attributeReferences->attributeReference['Requester Phone']->value ?></p>
			</div>
			<div class="data-col">
				<h5>Email:</h5>
				<p><?php echo $req->attributeReferences->attributeReference['Requester Email']->value ?></p>
			</div>
			<div class="data-col">
				<h5>Role:</h5>
				<p><?php echo $req->attributeReferences->attributeReference['Requester Role']->value ?></p>
			</div>
			<div class="data-col">
				<h5>Requesting Organization:</h5>
				<p><?php echo $req->attributeReferences->attributeReference['Requesting Organization']->value ?></p>
			</div>
			<div class="clear"></div>

			<h3 class="headerTab">Sponsor</h3>
			<div class="clear"></div>
			<div class="data-col">
				<h5>Sponsor Name:</h5>
				<p><?php echo $req->attributeReferences->attributeReference['Sponsor Name']->value ?></p>
			</div>
			<div class="data-col">
				<h5>Sponsor Role:</h5>
				<p><?php echo $req->attributeReferences->attributeReference['Sponsor Role']->value ?></p>
			</div>
			<div class="data-col">
				<h5>Sponsor Email:</h5>
				<p><?php echo $req->attributeReferences->attributeReference['Sponsor Email']->value ?></p>
			</div>
			<div class="data-col">
				<h5>Sponsor Phone:</h5>
				<p><?php echo $req->attributeReferences->attributeReference['Sponsor Phone']->value ?></p>
			</div>
			<div class="clear"></div>

			<h3 class="headerTab">Application Name</h3>
			<div class="clear"></div>
			<p><?= $req->attributeReferences->attributeReference['Application Name']->value ?></p>

<?php
			$arrNonDisplay = [
				"Requester Name",
				"Requester Email",
				"Requester Phone",
				"Information Elements",
				"Requester Role",
				"Requester PersonId",
				"Requesting Organization",
				"Sponsor Name",
				"Sponsor Role",
				"Sponsor Email",
				"Sponsor Phone",
				"Requester Person Id",
				"Request Date",
				"Application Name"
			];
			$completedStatuses = ['Completed', 'Approved', 'Obsolete'];
			if (empty($req->dataUsages)) {
				foreach($req->attributeReferences->attributeReference as $attrRef){
					if(!in_array($attrRef->labelReference->signifier, $arrNonDisplay)){
						echo '<h3 class="headerTab">'.$attrRef->labelReference->signifier.'</h3><div class="clear"></div>'.
							'<p>'.$attrRef->value.'</p>';
					}
				}
			}
			if (empty($req->dataUsages)) {
				echo '<div class="delete grow" data-rid="'.$req->resourceId.'">Delete</div>';
				echo '<div class="edit grow" data-rid="'.$req->resourceId.'">Edit</div>';
			}
			echo '<div class="print grow" data-rid="'.$req->resourceId.'">Print</div>';
			echo '<div class="share grow" data-rid="'.$req->resourceId.'">Share</div>';
			echo '</div>';

			foreach($req->dataUsages as $du) {
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
						'	<div class="user-icon" style="background-image: url('.$approverImage.');"></div>'.
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
							'	<div class="user-icon" style="background-image: url('.$approverImage.');"></div>'.
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
				foreach($du->attributeReferences->attributeReference as $attr){
					if(!in_array($attr->labelReference->signifier, $arrNonDisplay)){
						echo '<h3 class="headerTab">'.$attr->labelReference->signifier.'</h3><div class="clear"></div>'.
							'<p>'.$attr->value.'</p>';
					}
				}

				if (!in_array($du->status, $completedStatuses)) {
					echo '<div class="delete grow" data-rid="'.$du->id.'">Delete</div>';
					echo '<div class="edit grow" data-rid="'.$du->id.'">Edit</div>';
				}
				echo '<div class="print grow" data-rid="'.$du->id.'">Print</div>';
				echo '<div class="share grow" data-rid="'.$du->id.'">Share</div>';
				echo '</div>';
			}

			echo '</div>';
		}
	}
?>
	</div>
</div>

<!-- Quick links -->
<?php echo $this->element('quick_links'); ?>
