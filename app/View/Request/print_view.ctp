<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />	<title>
		BYU InfoHub:
		Print Request	</title>
	<link href="/favicon.ico" type="image/x-icon" rel="icon"/>
    <link href="/favicon.ico" type="image/x-icon" rel="shortcut icon"/>
    <?php echo $this->Html->css('font-awesome');
		  echo $this->Html->css('admin-nav');
		  echo $this->Html->css('styles');
		  echo $this->Html->css('print'); ?>
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
			<p class="riData"><strong>Additionally Included Data:</strong>
<?php
	foreach ($request->additionallyIncluded->termGlossaries as $glossaryName => $terms) {
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
	}
?>

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

<?php
	if (empty($request->dataUsages)) {
		if (!empty($request->policies)) {
			echo '<h3 style="page-break-before:always;">Data Usage Policies</h3>';
			foreach ($request->policies as $policy) {
				echo '<h5>'.$policy->policyName.'</h5>';
				echo '<div class="form-field">'.$policy->policyDescription.'</div>';
			}
		}
	} else {
		echo '<br><h2>Associated Data Sharing Agreements</h2>';
	}

	foreach($request->dataUsages as $du) {
		echo '<div class="subrequest">';
		$dsaName = $du->signifier;
		echo '<div class="subrequestNameWrapper"><h6 class="subrequestName">'.$dsaName.'</h6></div>';
		echo '<p class="riData"><strong>Requested Data:</strong>';
		foreach ($request->termGlossaries as $glossaryName => $terms) {
			if ($terms[0]->commrid != $du->communityId) {
				continue;
			}
			echo '<br><em>'.$glossaryName.'&nbsp;-&nbsp;</em>';
			$termCount = 0;
			foreach ($terms as $term) {
				echo $term->termsignifier;
				$termCount++;
				if ($termCount < sizeof($terms)) {
					echo ',&nbsp;&nbsp;';
				}
			}
			break;
		}
		echo '</p>';
?>
        <div class="status"><span>Approval Status:&nbsp;</span><?= $du->status ?></div>
		<br>
<?php
		foreach ($arrOrderedFormFields as $field) {
			foreach ($du->attributeReferences->attributeReference as $attrRef) {
				if ($attrRef->labelReference->signifier == $field) {
					echo '<h3>'.$attrRef->labelReference->signifier.'</h3>'.
						'<div class="form-field">'.$attrRef->value.'</div>';
					break;
				}
			}
		}
		if (!empty($du->policies)) {
			echo '<h3>Data Usage Policies</h3>';
			foreach ($du->policies as $policy) {
				echo '<h5>'.$policy->policyName.'</h5>';
				echo '<div class="form-field">'.$policy->policyDescription.'</div>';
			}
		}
		echo '</div>';
	}
	echo '</div></div>';
?>
</div>
</body>
