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

		$('.policies-btn').click(function() {
			$(this).toggleClass('collapsed');
			$('#policies').slideToggle(600);
		});

		$('#srLower').find('input, textarea').on('input', function() {
			autoSave();
		});
		$('.irLower').find('a').on('click', function() {
			autoSave();
		});
		$('.irLower').on('click', '#request-undo', function() {
			autoSave();
		});
	});

	$(window).resize(resultsWidth);

	function autoSave() {

		window.clearTimeout($('#srLower').data('timeout'));
		$('#srLower').data('timeout', setTimeout(function() {

			var i = 0;
			var savingTexts = ['Saving&nbsp;&nbsp;&nbsp;','Saving.&nbsp;&nbsp;','Saving..&nbsp;','Saving...'];
			var savingTextInterval = setInterval(function() {
				$('#saving').html(savingTexts[i]);
				i++;
				if (i == savingTexts.length) i = 0;
			}, 250);

			$('#saving').slideDown();

			var postData = {};
			$('#srLower').find('input, textarea').each(function() {
				postData[$(this).prop('name')] = $(this).prop('value');
			});

			$.post('request/saveDraft', postData)
				.done(function(data) {
					data = JSON.parse(data);
					clearInterval(savingTextInterval);

					$('#saving').html(data.message);
					data.success ? $('#saving').addClass('success') : $('#saving').addClass('error');
					setTimeout(function() {
						$('#saving').slideUp().promise().done(function() {
							$('#saving').html(savingTexts[0]).removeClass('success error');
						});
					}, 1000);

				});
		}, 4000));
	}

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
				<div class="saveNotification">Feel free to leave this page and finish later; your changes will be saved automatically.</div>

				<h3 class="headerTab">Information Requested</h3>
				<div class="clear"></div>
				<div class="resultItem">
					<div class="irLower"><ul class="cart">
						<?php
					if(!empty($termDetails) || !empty($arrQueue['concepts']) || !empty($arrQueue['emptyApis']) || !empty($arrQueue['apiFields']) || !empty($arrQueue['dbColumns'])) {
							foreach ($termDetails as $term){
								echo '<li id="requestItem'.$term->termrid.'" data-title="'.$term->termsignifier.'" data-id="'.$term->termrid.'" data-vocabID="'.$term->commrid.'" api-host="'.$term->apihost.'" api-path="'.$term->apipath.'" schema-name="'.$term->schemaname.'" table-name="'.$term->tablename.'" data-type="term"><a class="delete" href="javascript:removeFromRequestQueue(\''.$term->termrid.'\')"><img src="/img/icon-delete.gif" width="11" title="delete" /></a>'.$term->termsignifier.'</li>';
							}
							foreach ($arrQueue['concepts'] as $id => $term) {
								echo '<li id="requestItem'.$id.'" data-title"'.$term['term'].'" data-id="'.$id.'"data-vocabID="'.$term['communityId'].'" api-host="'.$term['apiHost'].'" api-path="'.$term['apiPath'].'" data-type="concept"><a class="delete" href="javascript:removeFromRequestQueue(\''.$id.'\')"><img src="/img/icon-delete.gif" width="11" title="delete" /></a>'.$term['term'].'</li>';
							}
							foreach ($arrQueue['emptyApis'] as $path => $api){
								$displayName = strlen($path) > 28 ? substr($path, 0, 28) . "..." : $path;
								$id = preg_replace('/\//', '', $path);
								echo '<li id="requestItem'.$id.'" data-title="'.$path.'" api-host="'.$api['apiHost'].'" data-type="api"><a class="delete" href="javascript:removeFromRequestQueue(\''.$path.'\')"><img src="/img/icon-delete.gif" width="11" title="delete" /></a>'.$displayName.'</li>';
							}
							foreach ($arrQueue['apiFields'] as $fieldPath => $field) {
								$id = preg_replace('/\./', '', $fieldPath);
								echo '<li id="requestItem'.$id.'" data-title="'.$fieldPath.'" api-host="'.$field['apiHost'].'" api-path="'.$field['apiPath'].'" data-type="field"><a class="delete" href="javascript:removeFromRequestQueue(\''.$fieldPath.'\')"><img src="/img/icon-delete.gif" width="11" title="delete" /></a>'.$field['name'].'</li>';
							}
							foreach ($arrQueue['dbColumns'] as $columnName => $column) {
								$id = preg_replace('/ > /', '', $columnName);
								echo '<li id="requestItem'.$id.'" data-title="'.$columnName.'" schema-name="'.$column['schemaName'].'" table-name="'.$column['tableName'].'" data-type="column"><a class="delete" href="javascript:removeFromRequestQueue(\''.$columnName.'\')"><img src="/img/icon-delete.gif" width="11" title="delete" /></a>'.$column['name'].'</li>';
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
						"api",
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

					if (!empty($policies)) {
						echo '<div class="policy-header-wrapper"><label class="headerTab">Data Usage Policies</label>
								  <div class="policies-btn grow">
								  	<span class="policiesHide">Collapse</span>
									<span class="policiesShow">Expand</span>
								  </div>
							  </div>';
						echo '<div class="clear"></div>';
						echo '<div id="policies" class="taBox">
								<div class="info">In accordance with the business terms included in
								your request, the following usage policies will be automatically
								applied and must be complied to.</div>';
						foreach ($policies as $policy) {
							echo '<h5 class="policy-name">'.$policy->policyName.'</h5>'.
								 '<h6 class="applies">'.$policy->inclusionScenario.'</h6>';
							echo '<p>'.$policy->body.'</p><br>';
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
		<div class="mobileHide">*Required field</div>
		<div class="clear"></div>
	</div>
	<div class="clear"></div>
	<div id="saving"></div>
</form>
