<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('search', null, array('inline' => false));
?>
<script>
	$(document).ready(function() {
		$("#searchLink").addClass('active');
		resultsWidth();

		<?php
			if($submitErr){
				echo "alert('An error occured with your request. Please try again.');";
			}
		?>
	});

	$(window).resize(resultsWidth);

	function resultsWidth() {
		if ($(window).width() > 680) {
			$('.resultContent').css('width', '100%').css('width', '-=200px');
		}
		else {
			$('.resultContent').css('width', '95%').css('width', '-=60px');
		}
	}

	function validate(){
		var isValid = true;
		$('#request input, #request textarea').each(function() {
			if($(this).val()=='' && $(this).prop('name') != 'descriptionOfInformation'){
				isValid = false;
				$(this).focus();
				return false;
			}
		});
		if(!isValid) alert('All fields except Additional Information Requested are required.');
		return isValid;
	}

	function toggleDataNeeded(chk){
		var arrDataNeeded = $('#dataNeeded').val();
		var term = $(chk).val();
		if($(chk).prop('checked')){
			if(arrDataNeeded != '') arrDataNeeded += ', ';
			$('#dataNeeded').val(arrDataNeeded + term);
		}else{
			arrDataNeeded = arrDataNeeded.replace(', ' + term, '').replace(term, '');
			$('#dataNeeded').val(arrDataNeeded);
		}
	}
</script>

<!-- Background image div -->
<div id="searchBg" class="searchBg">
</div>

<!-- Request Form -->
<form action="/request/submit" method="post" id="request" onsubmit="return validate();">
	<div id="searchBody" class="innerLower">

		<div id="requestForm">
			<h2 class="headerTab">Request Form</h2>

			<div id="srLower" class="whiteBox">
				<h3 class="headerTab">Information Requested</h3>
				<div class="clear"></div>
				<div class="resultItem">
					<div class="irLower"><ul>
						<?php
						if(!empty($termDetails) || !empty($emptyApis)) {
							// pr($emptyApis);exit();
							foreach ($termDetails->aaData as $term){
								echo '<li id="requestItem'.$term->termrid.'" data-title="'.$term->termsignifier.'" data-rid="'.$term->termrid.'" data-vocabID="'.$term->commrid.'" api-host="'.$term->apihost.'" api-path="'.$term->apipath.'" api="false">'.$term->termsignifier.'<a class="delete" href="javascript:removeFromRequestQueue(\''.$term->termrid.'\')"><img src="/img/icon-delete.gif" width="11" title="delete" /></a></li>';
							}
							foreach ($emptyApis as $index => $api){
								if (strlen($api['apiPath']) > 28) {
									$displayName = substr($api['apiPath'], 0, 28) . "...";
								} else {
									$displayName = $api['apiPath'];
								}
								echo '<li id="requestItem'.$index.'" data-title="'.$api['apiPath'].'" api-host="'.$api['apiHost'].'" api="true">'.$displayName.'<a class="delete" href="javascript:removeFromRequestQueue(\''.$index.'\')"><img src="/img/icon-delete.gif" width="11" title="delete" /></a></li>';
							}
							foreach ($unspecifiedTerms as $termName => $term) {
								echo '<li id="requestItem'.$termName.'" data-title="'.$termName.'" api-host="'.$term['apiHost'].'" api-path="'.$term['apiPath'].'" api="false">'.$termName.'<a class="delete" href="javascript:removeFromRequestQueue(\''.$termName.'\')"><img src="/img/icon-delete.gif" width="11" title="delete" /></a></li>';
							}
							echo '</ul><a class="clearQueue" href="javascript: clearRequestQueue()">Clear All Items</a>';
						}else{
							echo 'No request items found.</ul>';
						} ?>
					</div>
				</div>

				<h3 class="headerTab">Requester Information</h3>
				<div class="clear"></div>
				<div class="fieldGroup">
					<!-- <div class="infoCol"> -->
						<div class="field-container">
							<label for="name">Requester Name</label>
							<input type="text" id="name" name="name" class="inputShade noPlaceHolder" value="<?= h($psName) ?>">
						</div>
						<div class="field-container">
							<label for="phone">Requester Phone</label>
							<input type="text" id="phone" name="phone" class="inputShade noPlaceHolder" value="<?= h($psPhone) ?>">
						</div>
					<!-- </div>
					<div class="infoCol"> -->
						<div class="field-container">
							<label for="role">Requester Role</label>
							<input type="text" id="role" name="role" class="inputShade noPlaceHolder" value="<?= h($psRole) ?>">
						</div>
						<div class="field-container">
							<label for="email">Requester Email</label>
							<input type="text" id="email" name="email" class="inputShade noPlaceHolder" value="<?= h($psEmail) ?>">
						</div>
						<div class="field-container">
							<label for="requestingOrganization">Requester Organization</label>
							<input type="text" id="requestingOrganization" name="requestingOrganization" class="inputShade noPlaceHolder" value="<?= h($psDepartment) ?>">
						</div>
					<!-- </div> -->
				</div>

				<h3 class="headerTab">Sponsor Information</h3>
				<div class="clear"></div>
				<div class="fieldGroup">
					<div class="field-container">
						<label for="sponsorName">Sponsor Name</label>
						<input type="text" id="sponsorName" name="sponsorName" class="inputShade noPlaceHolder" value="<?= empty($supervisorInfo->name) ? '' : h($supervisorInfo->name) ?>">
					</div>
					<div class="field-container">
						<label for="sponsorPhone">Sponsor Phone</label>
						<input type="text" id="sponsorPhone" name="sponsorPhone" class="inputShade noPlaceHolder" value="<?= empty($supervisorInfo->phone) ? '' : h($supervisorInfo->phone) ?>">
					</div>
					<div class="field-container">
						<label for="sponsorRole">Sponsor Role</label>
						<input type="text" id="sponsorRole" name="sponsorRole" class="inputShade noPlaceHolder" value="<?= empty($supervisorInfo->job_title) ? '' : h($supervisorInfo->job_title) ?>">
					</div>
					<div class="field-container">
						<label for="sponsorEmail">Sponsor Email</label>
						<input type="text" id="sponsorEmail" name="sponsorEmail" class="inputShade noPlaceHolder" value="<?= empty($supervisorInfo->email) ? '' : h($supervisorInfo->email) ?>">
					</div>

				</div>


				<?php
					$arrNonDisplay = array(
						"requesterName",
						"requesterEmail",
						"requesterPhone",
						"requesterRole",
						"requesterPersonId",
						"requestingOrganization",
						"sponsorName",
						"sponsorRole",
						"sponsorEmail",
						"sponsorPhone",
						Configure::read('Collibra.isaWorkflow.requiredElementsString'),
						Configure::read('Collibra.isaWorkflow.additionalElementsString')
					);
					foreach($formFields->formProperties as $field){
						if(in_array($field->id, $arrNonDisplay)){
							continue;
						}
						echo '<label class="headerTab" for="'.$field->id.'">'.$field->name;
						if ($field->id == 'descriptionOfInformation') echo ' (Optional)*';
						echo '</label>'.
							'<div class="clear"></div>'.
							'<div class="taBox">';
						$placeholderText = $field->value;

						$val = empty($preFilled[$field->id]) ? '' : h($preFilled[$field->id]);
						if($field->type == 'textarea'){
							echo '<textarea name="'.$field->id.'" id="'.$field->id.'" class="inputShade noPlaceHolder" placeholder="'.$placeholderText.'">'.$val.'</textarea>';
						}elseif($field->type == 'user'){
							echo '<select name="'.$field->id.'" id="'.$field->id.'">';
							foreach($sponsors->user as $sponsor){
								if($sponsor->enabled==1){
									echo '<option value="'.$sponsor->resourceId.'">'.$sponsor->firstName.' '.$sponsor->lastName.'</option>';
								}
							}
							echo '</select>';
						}else{
							echo '<input type="text" name="'.$field->id.'" id="'.$field->id.'" value="'.$val.'" class="inputShade full noPlaceHolder" placeholder="'.$placeholderText.'" />';
						}

						echo '</div>';
					}
				?>
				<label for="requestSubmit" id="mobileReqd">*All Other Fields Required</label>
				<div class="clear"></div>
			</div>
		</div>
		<div class="clear"></div>
	</div>
	<div id="formSubmit" class="innerLower">
		<input type="submit" value="Submit Request" id="requestSubmit" name="requestSubmit" class="grow">
		<label for="requestSubmit" class="mobileHide">*All Other Fields Required</label>
		<div class="clear"></div>
	</div>
	<div class="clear"></div>
</form>

<!-- Quick links -->
<?php echo $this->element('quick_links'); ?>
