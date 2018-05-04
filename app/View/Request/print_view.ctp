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
			<h4 class="riTitle"><?= $asset->assetName ?></h4><div id="print">Print</div>
			<p class="riData"><strong>Requested Data:</strong>
<?php
	if (empty($asset->termGlossaries)) echo 'No requested business terms';

	foreach ($asset->termGlossaries as $glossaryName => $terms) {
		echo '<br><em>'.$glossaryName.'&nbsp;-&nbsp;</em>';
		$termCount = 0;
		foreach ($terms as $term) {
			echo $term->reqTermSignifier;
			$termCount++;
			if ($termCount < sizeof($terms)) {
				echo ',&nbsp;&nbsp;';
			}
		}
	}
?>
	        </p>
			<?php if (!empty($asset->addTermGlossaries)): ?>
				<p class="riData"><strong>Additionally Included Data:</strong>
			<?php endif ?>
<?php
	foreach ($asset->addTermGlossaries as $glossaryName => $terms) {
		echo '<br><em>'.$glossaryName.'&nbsp;-&nbsp;</em>';
		$termCount = 0;
		foreach ($terms as $term) {
			echo $term->addTermSignifier;
			$termCount++;
			if ($termCount < sizeof($terms)) {
				echo ',&nbsp;&nbsp;';
			}
		}
	}
?>
	        </p>
	        <div class="two-col-row">
				<p class="data-col"><span class="label">Approval Status:&nbsp;</span><?= $asset->statusName ?></p>
				<p class="data-col"><span class="label">Date Created:&nbsp;</span><?= date('n/j/Y', ($asset->createdOn)/1000) ?></p>
	        </div>
			<?php if (!$parent): ?>
				<div class="two-col-row">
					<p class="data-col"><span class="label">Custodian:&nbsp;</span><?= $asset->roles['Custodian'][0]->firstName.' '.$asset->roles['Custodian'][0]->lastName ?></p>
					<p class="data-col"><span class="label">Steward:&nbsp;</span><?= $asset->roles['Steward'][0]->firstName.' '.$asset->roles['Steward'][0]->lastName ?></p>
				</div>
			<?php endif ?>
	<div class="clear"></div>

	<h3>Application Name</h3>
	<div class="form-field"><?= $asset->attributes['Application Name']->attrValue ?></div>

<?php
	$arrOrderedFormFields = [
		"Description of Intended Use",
		"Access Rights",
		"Access Method",
		"Impact on System",
		"Additional Information Requested"
	];
	if (empty($asset->dsas)) {
		foreach ($arrOrderedFormFields as $field) {
			foreach ($asset->attributes as $attr) {
				if ($attr->attrSignifier == $field) {
					echo '<h3>'.$attr->attrSignifier.'</h3>'.
						'<div class="form-field">'.$attr->attrValue.'</div>';
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
		<?php $attrs = $asset->attributes; ?>
		<p class="data-col">
			<strong><?= $attrs['Requester Name']->attrValue ?></strong><br>
			<?= $attrs['Requester Role']->attrValue.' | '.$attrs['Requesting Organization']->attrValue ?><br>
			<?= $attrs['Requester Email']->attrValue ?><br>
			<?= $attrs['Requester Phone']->attrValue ?>
		</p>
		<p class="data-col">
			<strong><?= $attrs['Sponsor Name']->attrValue ?></strong><br>
			<?= $attrs['Sponsor Role']->attrValue ?><br>
			<?= $attrs['Sponsor Email']->attrValue ?><br>
			<?= $attrs['Sponsor Phone']->attrValue ?>
		</p>
	</div>
	<div class="clear"></div>

<?php
	if (empty($asset->dsas)) {
		if (!empty($asset->policies)) {
			echo '<h3 style="page-break-before:always;">Data Usage Policies</h3>';
			foreach ($asset->policies as $policy) {
				echo '<h5>'.$policy->policyName.'</h5>';
				echo '<div class="form-field">'.$policy->policyDescription.'</div>';
			}
		}
	} else {
		echo '<br><h2>Associated Data Sharing Agreements</h2>';
	}

	foreach($asset->dsas as $dsa) {
		echo '<div class="subrequest">';
		$dsaName = $dsa->dsaSignifier;
		echo '<div class="subrequestNameWrapper"><h6 class="subrequestName">'.$dsaName.'</h6></div>';
		echo '<p class="riData"><strong>Requested Data:</strong>';
		foreach ($asset->termGlossaries as $glossaryName => $terms) {
			if ($terms[0]->reqTermCommId != $dsa->dsaCommunityId) {
				continue;
			}
			echo '<br><em>'.$glossaryName.'&nbsp;-&nbsp;</em>';
			$termCount = 0;
			foreach ($terms as $term) {
				echo $term->reqTermSignifier;
				$termCount++;
				if ($termCount < sizeof($terms)) {
					echo ',&nbsp;&nbsp;';
				}
			}
			break;
		}
		echo '</p>';
?>
        <div class="two-col-row">
			<p class="data-col"><span class="label">Approval Status:&nbsp;</span><?= $dsa->dsaStatus ?></p>
		</div>
		<div class="two-col-row">
			<p class="data-col"><span class="label">Custodian:&nbsp;</span><?= $dsa->roles['Custodian'][0]->firstName.' '.$dsa->roles['Custodian'][0]->lastName ?></p>
			<p class="data-col"><span class="label">Steward:&nbsp;</span><?= $dsa->roles['Steward'][0]->firstName.' '.$dsa->roles['Steward'][0]->lastName ?></p>
		</div>
<?php
		foreach ($arrOrderedFormFields as $field) {
			foreach ($dsa->attributes as $attr) {
				if ($attr->attrSignifier == $field) {
					echo '<h3>'.$attr->attrSignifier.'</h3>'.
						'<div class="form-field">'.$attr->attrValue.'</div>';
					break;
				}
			}
		}
		if (!empty($dsa->policies)) {
			echo '<h3>Data Usage Policies</h3>';
			foreach ($dsa->policies as $policy) {
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
