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

	$(window).unload(function() {
		if (isValid) return;	// If we're leaving the page because we've successfully submitted
								// the request, we don't want to save the form fields.
		var saveNeeded = false;
		var savables = [
			'name',
			'phone',
			'role',
			'email',
			'requestingOrganization',
			'sponsorName',
			'sponsorPhone',
			'sponsorRole',
			'sponsorEmail',
			'applicationName',
			'descriptionOfIntendedUse',
			'accessRights',
			'accessMethod',
			'impactOnSystem'
		];
		var arrSaveData = {
			name: '',
			phone: '',
			role: '',
			email: '',
			requestingOrganization: '',
			sponsorName: '',
			sponsorPhone: '',
			sponsorRole: '',
			sponsorEmail: '',
			applicationName: '',
			descriptionOfIntendedUse: '',
			accessRights: '',
			accessMethod: '',
			impactOnSystem: ''
		};
		$('#srLower').find('input').each(function() {
			if ($.inArray($(this).prop('name'), savables) > -1 && $(this).val() != "") {
				saveNeeded = true;
				arrSaveData[$(this).prop('name')] = $(this).prop('value');
			}
		});
		$('#srLower').find('textarea').each(function() {
			if ($.inArray($(this).prop('name'), savables) > -1 && $(this).val() != "") {
				saveNeeded = true;
				arrSaveData[$(this).prop('name')] = $(this).prop('value');
			}
		})
		if (saveNeeded) {
			$.ajax({
				method: "POST",
				url: "request/saveFormFields",
				data: arrSaveData,
				async: false
			});
		}
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

	var isValid = false;
	function validate(){
		isValid = true;
		$('#request input').each(function() {
			if($(this).val()==''){
				isValid = false;
				$(this).focus();
				return false;
			}
		});
		if(!isValid) alert('Requester and Sponsor Information and Application Name are required.');
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
				<div id="saveNotification">Feel free to leave this page and come back; your changes will be saved.</div>

				<h3 class="headerTab">Information Requested</h3>
				<div class="clear"></div>
				<div class="resultItem">
					<div class="irLower"><ul class="cart">
						<?php
					if(!empty($termDetails->aaData) || !empty($arrQueue->concepts) || !empty($arrQueue->emptyApis) || !empty($arrQueue->apiFields)) {
							foreach ($termDetails->aaData as $term){
								echo '<li id="requestItem'.$term->termrid.'" data-title="'.$term->termsignifier.'" data-rid="'.$term->termrid.'" data-vocabID="'.$term->commrid.'" api-host="'.$term->apihost.'" api-path="'.$term->apipath.'" api="false"><a class="delete" href="javascript:removeFromRequestQueue(\''.$term->termrid.'\')"><img src="/img/icon-delete.gif" width="11" title="delete" /></a>'.$term->termsignifier.'</li>';
							}
							foreach ($arrQueue->concepts as $id => $term) {
								echo '<li id="requestItem'.$id.'" data-title"'.$term['term'].'" data-rid="'.$id.'"data-vocabID="'.$term['communityId'].'" api-host="'.$term['apiHost'].'" api-path="'.$term['apiPath'].'" api="false"><a class="delete" href="javascript:removeFromRequestQueue(\''.$id.'\')"><img src="/img/icon-delete.gif" width="11" title="delete" /></a>'.$term['term'].'</li>';
							}
							foreach ($arrQueue->emptyApis as $path => $api){
								$displayName = strlen($path) > 28 ? substr($path, 0, 28) . "..." : $path;
								$id = preg_replace('/\//', '', $path);
								echo '<li id="requestItem'.$id.'" data-title="'.$path.'" api-host="'.$api['apiHost'].'" api="true"><a class="delete" href="javascript:removeFromRequestQueue(\''.$path.'\')"><img src="/img/icon-delete.gif" width="11" title="delete" /></a>'.$displayName.'</li>';
							}
							foreach ($arrQueue->apiFields as $fieldPath => $field) {
								echo '<li id="requestItem'.$fieldPath.'" data-title="'.$fieldPath.'" api-host="'.$field['apiHost'].'" api-path="'.$field['apiPath'].'" api="false"><a class="delete" href="javascript:removeFromRequestQueue(\''.$fieldPath.'\')"><img src="/img/icon-delete.gif" width="11" title="delete" /></a>'.$field['name'].'</li>';
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
							<label for="name">Requester Name*</label>
							<input type="text" id="name" name="name" class="inputShade noPlaceHolder" value="<?= empty($preFilled['name']) ? h($psName) : h($preFilled['name']) ?>">
						</div>
						<div class="field-container">
							<label for="phone">Requester Phone*</label>
							<input type="text" id="phone" name="phone" class="inputShade noPlaceHolder" value="<?= empty($preFilled['phone']) ? h($psPhone) : h($preFilled['phone']) ?>">
						</div>
					<!-- </div>
					<div class="infoCol"> -->
						<div class="field-container">
							<label for="role">Requester Role*</label>
							<input type="text" id="role" name="role" class="inputShade noPlaceHolder" value="<?= empty($preFilled['role']) ? h($psRole) : h($preFilled['role']) ?>">
						</div>
						<div class="field-container">
							<label for="email">Requester Email*</label>
							<input type="text" id="email" name="email" class="inputShade noPlaceHolder" value="<?= empty($preFilled['email']) ? h($psEmail) : h($preFilled['email']) ?>">
						</div>
						<div class="field-container">
							<label for="requestingOrganization">Requester Organization*</label>
							<input type="text" id="requestingOrganization" name="requestingOrganization" class="inputShade noPlaceHolder" value="<?= empty($preFilled['requestingOrganization']) ? h($psDepartment) : h($preFilled['requestingOrganization']) ?>">
						</div>
					<!-- </div> -->
				</div>

				<h3 class="headerTab">Sponsor Information*</h3>
				<div class="clear"></div>
				<div class="fieldGroup">
					<div class="field-container">
						<label for="sponsorName">Sponsor Name*</label>
						<input type="text" id="sponsorName" name="sponsorName" class="inputShade noPlaceHolder" value="<?= empty($preFilled['sponsorName']) ? ( empty($supervisorInfo->name) ? '' : h($supervisorInfo->name) ) : $preFilled['sponsorName'] ?>">
					</div>
					<div class="field-container">
						<label for="sponsorPhone">Sponsor Phone*</label>
						<input type="text" id="sponsorPhone" name="sponsorPhone" class="inputShade noPlaceHolder" value="<?= empty($preFilled['sponsorPhone']) ? ( empty($supervisorInfo->phone) ? '' : h($supervisorInfo->phone) ) : $preFilled['sponsorPhone'] ?>">
					</div>
					<div class="field-container">
						<label for="sponsorRole">Sponsor Role*</label>
						<input type="text" id="sponsorRole" name="sponsorRole" class="inputShade noPlaceHolder" value="<?= empty($preFilled['sponsorRole']) ? ( empty($supervisorInfo->job_title) ? '' : h($supervisorInfo->job_title) ) : $preFilled['sponsorRole'] ?>">
					</div>
					<div class="field-container">
						<label for="sponsorEmail">Sponsor Email*</label>
						<input type="text" id="sponsorEmail" name="sponsorEmail" class="inputShade noPlaceHolder" value="<?= empty($preFilled['sponsorEmail']) ? ( empty($supervisorInfo->email) ? '' : h($supervisorInfo->email) ) : $preFilled['sponsorEmail'] ?>">
					</div>

				</div>


				<?php
					$arrNonDisplay = array(
						"requesterName",
						"requesterEmail",
						"requesterPhone",
						"requesterRole",
						"requesterPersonId",
						"requesterNetId",
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
						if ($field->name == 'Application Name') echo '*';
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
				<label for="requestSubmit" id="mobileReqd">*Required field</label>
				<div class="clear"></div>
			</div>
		</div>
		<div class="clear"></div>
	</div>
	<div id="formSubmit" class="innerLower">
		<input type="submit" value="Submit Request" id="requestSubmit" name="requestSubmit" class="grow">
		<label for="requestSubmit" class="mobileHide">*Required field</label>
		<div class="clear"></div>
	</div>
	<div class="clear"></div>
</form>

<!-- Quick links -->
<?php echo $this->element('quick_links'); ?>
