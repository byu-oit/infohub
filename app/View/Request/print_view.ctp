<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />	<title>
		BYU InfoHub:
		Print Request	</title>
	<link href="/favicon.ico" type="image/x-icon" rel="icon"/>
    <link href="/favicon.ico" type="image/x-icon" rel="shortcut icon"/>
    <link rel="stylesheet" type="text/css" href="/css/font-awesome.min.css"/>
    <link rel="stylesheet" type="text/css" href="/css/admin-nav.css"/>
	<link rel="stylesheet" type="text/css" href="/css/styles.css"/>
	<link rel="stylesheet" type="text/css" href="/css/print.css"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<link href='//fonts.googleapis.com/css?family=Rokkitt:400,700' rel='stylesheet' type='text/css'>
	<link href='//fonts.googleapis.com/css?family=Open+Sans:400,600,700,300' rel='stylesheet' type='text/css'>
	<link href='//fonts.googleapis.com/css?family=Voltaire' rel='stylesheet' type='text/css'>
	<script src="//code.jquery.com/jquery-2.1.3.min.js"></script>
	<script>
	$(document).ready(function() {
		$('#print').click(function() {
			window.print();
		});
	});
	</script>
</head>
<body>
<div id="container">
	<div id="requestItemWrapper">
		<div id="requestItem">
			<h4 class="riTitle"><?= $request->signifier ?></h4><div id="print">Print</div>
			<p class="riData"><strong>Requested Data:</strong>
<?php
	foreach ($request->termGlossaries as $glossaryName => $terms) {
		echo '<br><em>'.$glossaryName.'&nbsp;-&nbsp;</em>';
		$termCount = 0;
		foreach ($terms as $term) {
			echo $term->termsignifier;
			$termCount++;
			if ($termCount < sizeof($terms)) {
				echo ',&nbsp;&nbsp;';
			}
		}
	}
?>
	        </p>
	        <div class="two-col-row">
	            <p class="riDate data-col"><span>Date Created:&nbsp;</span><?= date('n/j/Y', ($request->createdOn)/1000) ?></p>
	            <p class="status data-col"><span>Approval Status:&nbsp;</span><?= $request->statusReference->signifier ?></p>
	        </div>
			<h4 class="coordinators"><?php echo $parent ? 'Request' : 'Agreement'; ?> Coordinator(s)</h4>
<?php
	// display approvers and their info
	////////////////////////////////////////
	if ($parent) {
		foreach($request->roles['Request Cordinator'] as $rc){
			$approverName = $rc->firstName.' '.$rc->lastName;
			if($approverName != ''){
				$approverEmail = $rc->emailAddress;
				echo '<div class="approver">'.
						'<div class="contactName">'.$approverName.'</div>'.
						'<div class="contactEmail">'.$approverEmail.'</div>'.
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
			$approverEmail = $request->roles['Steward'][0]->emailAddress;
			echo '<div class="approver steward">'.
				'		<div class="contactName">'.$approverName.'</div>'.
				'		<div class="approverRole">';
				if ($oneApprover) {
					echo 'Custodian and ';
				}
				echo 'Steward</div>'.
				'		<div class="contactEmail">'.$approverEmail.'</div>'.
				'</div>';
		}
		if(!$oneApprover){
			$approverName = $request->roles['Custodian'][0]->firstName . " " . $request->roles['Custodian'][0]->lastName;
			if($approverName != ''){
				$approverEmail = $request->roles['Custodian'][0]->emailAddress;
				echo '<div class="approver custodian">'.
					'	<div class="contactName">'.$approverName.'</div>'.
					'	<div class="approverRole">Custodian</div>'.
					'	<div class="contactEmail">'.$approverEmail.'</div>'.
					'</div>';
			}
		}
	}
?>
	<div class="clear"></div>
	<div class="two-col-row">
		<div class="data-col">
			<h3>Requester</h3>
		</div>
		<div class="data-col">
			<h3>Sponsor</h3>
		</div>
	</div>
	<div class="two-col-row requester-sponsor">
		<?php $attrs = $request->attributeReferences->attributeReference; ?>
		<div class="data-col">
			<p>
				<strong><?= $attrs['Requester Name']->value ?></strong><br>
				<?= $attrs['Requester Role']->value.' | '.$attrs['Requesting Organization']->value ?><br>
				<?= $attrs['Requester Email']->value ?><br>
				<?= $attrs['Requester Phone']->value ?>
			</p>
		</div>
		<div class="data-col">
			<p>
				<strong><?= $attrs['Sponsor Name']->value ?></strong><br>
				<?= $attrs['Sponsor Role']->value ?><br>
				<?= $attrs['Sponsor Email']->value ?><br>
				<?= $attrs['Sponsor Phone']->value ?>
			</p>
		</div>
	</div>
	<div class="clear"></div>

	<h3>Application Name</h3>
	<div class="form-field"><?= $request->attributeReferences->attributeReference['Application Name']->value ?></div>

<?php
	$arrOrderedFormFields = [
		"Description of Intended Use",
		"Access Rights",
		"Access Method",
		"Impact on System",
		"Additional Information Requested"
	];
	$completedStatuses = ['Completed', 'Approved', 'Obsolete'];
	if (empty($request->dataUsages)) {
		foreach ($arrOrderedFormFields as $field) {
			foreach ($request->attributeReferences->attributeReference as $attrRef) {
				if ($attrRef->labelReference->signifier == $field) {
					echo '<h3>'.$attrRef->labelReference->signifier.'</h3>'.
						'<div class="form-field">'.$attrRef->value.'</div>';
					break;
				}
			}
		}
	} else {
		echo '<br><h2>Associated Data Sharing Agreements</h2>';
	}

	foreach($request->dataUsages as $du) {
		echo '<div class="subrequest">';
		$dsaName = $du->signifier;
		echo '<div class="subrequestNameWrapper"><h6 class="subrequestName">'.$dsaName.'</h6></div>';
		$oneApprover = (
			$du->roles['Steward'][0]->firstName . " " . $du->roles['Steward'][0]->lastName
			== $du->roles['Custodian'][0]->firstName . " " . $du->roles['Custodian'][0]->lastName
		);
		$approverName = $du->roles['Steward'][0]->firstName . " " . $du->roles['Steward'][0]->lastName;
		if($approverName != ''){
			$approverEmail = $du->roles['Steward'][0]->emailAddress;
			echo '<div class="approver steward">'.
				'		<div class="contactName">'.$approverName.'</div>'.
				'		<div class="approverRole">';
				if ($oneApprover) {
					echo 'Custodian and ';
				}
				echo 'Steward</div>'.
				'		<div class="contactEmail">'.$approverEmail.'</div>'.
				'</div>';
		}
		if(!$oneApprover){
			$approverName = $du->roles['Custodian'][0]->firstName . " " . $du->roles['Custodian'][0]->lastName;
			if($approverName != ''){
				$approverEmail = $du->roles['Custodian'][0]->emailAddress;
				echo '<div class="approver custodian">'.
					'	<div class="contactName">'.$approverName.'</div>'.
					'	<div class="approverRole">Custodian</div>'.
					'	<div class="contactEmail">'.$approverEmail.'</div>'.
					'</div>';
			}
		}
?>
        <div class="status"><span>Approval Status:&nbsp;</span><?= $du->status ?></div>
		<br>
<?php
		foreach ($arrOrderedFormFields as $field) {
			foreach ($request->attributeReferences->attributeReference as $attrRef) {
				if ($attrRef->labelReference->signifier == $field) {
					echo '<h3>'.$attrRef->labelReference->signifier.'</h3>'.
						'<div class="form-field">'.$attrRef->value.'</div>';
					break;
				}
			}
		}
		echo '</div>';
	}
	echo '</div></div>';
?>
</div>
</body>
