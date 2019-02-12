<?php
	$this->Html->css('secondary', null, ['inline' => false]);
	$this->Html->css('search', null, ['inline' => false]);
?>
<script>
	$(document).ready(function() {
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
		$('#srLower').find('select').on('change', function() {
			autoSave();
		});
		$('.irLower').find('a').on('click', function() {
			autoSave();
		});
		$('.irLower').on('click', '#request-undo', function() {
			autoSave();
		});

		$('#net-id').click(function() {
			if ($('#net-id-input-wrapper').css('display') == 'none') {
				$('#net-id-input-wrapper').fadeIn('fast');
				var inputElement = $('#net-id-input');
			} else {
				$('#net-id-input-wrapper').fadeOut('fast');
			}
		});
		$('.save').click(function() {
			var netIdInput = $('#net-id-input');
			if (netIdInput.val() != netIdInput.data('lastSave')) {
				$.get('/directory/requesterAndSupervisorLookup?netId='+netIdInput.val())
					.done(function(data) {
						data = JSON.parse(data);
						if (data.message) {
							alert(data.message);
						}
						if (data.success) {
							netIdInput.data('lastSave', netIdInput.val());

							$('#name').val(data.requester.name);
							$('#phone').val(data.requester.phone);
							$('#role').val(data.requester.role);
							<?php if (Configure::read('debug') == 0): ?>
								$('#email').val(data.requester.email);
							<?php endif ?>

							$('#sponsorName').val(data.supervisor.name);
							$('#sponsorPhone').val(data.supervisor.phone);
							$('#sponsorRole').val(data.supervisor.role);
							<?php if (Configure::read('debug') == 0): ?>
								$('#sponsorEmail').val(data.supervisor.email);
							<?php endif ?>
							$('#requestingOrganization').val(data.supervisor.department);

							$('#net-id-input-wrapper').fadeOut('fast');
						}
					});
			} else {
				$('#net-id-input-wrapper').fadeOut('fast');
			}
		});
		$('#net-id-input').keypress(function(event) { return event.keyCode != 13; });
		$('#net-id-input').on({
			keyup: function(e) {
				if (e.which === 13) {
					$('.save').click();
				}
			}
		});

		var applicationOrProjectSelectInnerHTML = '<option value="">Select an application or project...</option>'+
												  '<option value="new">Name a new application or project</option>';
		$('#applicationOrProjectSelect').html(applicationOrProjectSelectInnerHTML);
		$('#developmentShopToggle').click(function() {
			if (!$(this).hasClass('onText')) {
				$(this).addClass('onText');
				$('#developmentShopSelect').hide().val('');
				$('#developmentShop').val('').show();

				$('#applicationOrProjectToggle').hide().addClass('onText');
				$('#applicationOrProjectSelect').hide().html(applicationOrProjectSelectInnerHTML).prop('disabled', true);
				$('#applicationOrProjectName').val('').show();
			} else {
				$(this).removeClass('onText');
				$('#developmentShop').hide().val('');
				$('#developmentShopSelect').val('').show();

				$('#applicationOrProjectToggle').hide().removeClass('onText');
				$('#applicationOrProjectSelect').html(applicationOrProjectSelectInnerHTML).prop('disabled', true).show();
				$('#applicationOrProjectName').hide().val('');
			}
		});
		$('#developmentShopSelect').change(function() {
			if (!$(this).val()) {
				$('#applicationOrProjectToggle').hide().removeClass('onText');
				$('#applicationOrProjectSelect').html(applicationOrProjectSelectInnerHTML).prop('disabled', true).show();
				$('#applicationOrProjectName').hide().val('');
			} else if ($(this).val() === 'new') {
				$('#developmentShopToggle').addClass('onText');
				$('#developmentShopSelect').hide().val('');
				$('#developmentShop').val('').show();

				$('#applicationOrProjectToggle').hide().addClass('onText');
				$('#applicationOrProjectSelect').hide().html(applicationOrProjectSelectInnerHTML).prop('disabled', true);
				$('#applicationOrProjectName').val('').show();
			} else {
				$('#applicationOrProjectToggle').removeClass('onText').show();
				$('#applicationOrProjectSelect').html(applicationOrProjectSelectInnerHTML).prop('disabled', false).show();
				$.getJSON('/developmentShop/getDetails/'+$('#developmentShopSelect option:selected').text())
					.done(function(data) {
						data[0].applications.forEach(function(app) {
							$('#applicationOrProjectSelect').append('<option value="'+app.appId+'">'+app.appName+'</option>');
						});
					});
				$('#applicationOrProjectName').hide().val('');
			}
		});

		$('#applicationOrProjectToggle').click(function() {
			$(this).toggleClass('onText');
			$('#applicationOrProjectSelect').toggle();
			$('#applicationOrProjectSelect').val('');
			$('#applicationOrProjectName').toggle();
			$('#applicationOrProjectName').val('');
		});
		$('#applicationOrProjectSelect').change(function() {
			if ($(this).val() === 'new') {
				$('#applicationOrProjectToggle').click();
			}
		});

		setupDevelopmentShopAndApplicationOrProject();

		$('.radioBox').click(function() {
			if ($(this).hasClass('selected')) {
				return;
			}
			$('.radioBox').each(function() {
				$(this).toggleClass('selected');
			});
			$('#readWriteAccessInput').val($(this).data('value'));

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
			$('#srLower').find('input, textarea, select').each(function() {
				postData[$(this).prop('name')] = $(this).prop('value');
			});
			if (postData.developmentShopId === 'new') delete postData.developmentShopId;
			if (postData.applicationOrProjectId === 'new') delete postData.applicationOrProjectId;

			$.post('/request/saveDraft', postData)
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

	function setupDevelopmentShopAndApplicationOrProject() {
		<?php if (!empty($preFilled['developmentShopId'])):	?>

			$('#developmentShopSelect').val('<?=$preFilled['developmentShopId']?>');
			$('#applicationOrProjectToggle').show();
			$('#applicationOrProjectSelect').prop('disabled', false);
			$.getJSON('/developmentShop/getDetails/'+$('#developmentShopSelect option:selected').text())
				.done(function(data) {
					data[0].applications.forEach(function(app) {
						$('#applicationOrProjectSelect').append('<option value="'+app.appId+'">'+app.appName+'</option>');
					});
					<?php if (!empty($preFilled['applicationOrProjectId'])): ?>
						$('#applicationOrProjectSelect').val('<?=$preFilled['applicationOrProjectId']?>');
					<?php endif ?>
				});

			<?php if (!empty($preFilled['applicationOrProjectName'])): ?>
				$('#applicationOrProjectToggle').addClass('onText');
				$('#applicationOrProjectSelect').hide().val('');
				$('#applicationOrProjectName').val('<?=$preFilled['applicationOrProjectName']?>').show();
			<?php endif ?>

		<?php elseif (!empty($preFilled['developmentShop'])): ?>

			$('#developmentShopToggle').addClass('onText');
			$('#developmentShopSelect').hide();
			$('#developmentShop').val('<?=$preFilled['developmentShop']?>').show();

			$('#applicationOrProjectToggle').hide().addClass('onText');
			$('#applicationOrProjectSelect').hide();
			$('#applicationOrProjectName').val('<?= empty($preFilled['applicationOrProjectName']) ? '' : $preFilled['applicationOrProjectName']?>').show();

		<?php endif ?>
	}

	function validate() {
		var isValid = true;
		$('#request input').each(function() {
			if ($(this).attr('id') == 'developmentShop' || $(this).attr('id') == 'applicationOrProjectName') return true;
			if (!$(this).val()) {
				isValid = false;
				$(this).focus();
				return false;
			}
		});
		if (!isValid) {
			alert('Requester and Sponsor Information are required.');
			return false;
		}

		if (!($('#developmentShopSelect').val() || $('#developmentShop').val())) {
			if ($('#developmentShopToggle').hasClass('onText')) {
				$('#developmentShop').focus();
			} else {
				$('#developmentShopSelect').focus();
			}
			alert('Development Shop is a required field.');
			return false;
		}

		if (!($('#applicationOrProjectSelect').val() || $('#applicationOrProjectName').val())) {
			if ($('#applicationOrProjectToggle').hasClass('onText')) {
				$('#applicationOrProjectName').focus();
			} else {
				$('#applicationOrProjectSelect').focus();
			}
			alert('Application or Project is a required field.');
			return false;
		}
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
					if (!empty($arrQueue['apiFields']) || !empty($arrQueue['dbColumns']) || !empty($arrQueue['samlFields']) || !empty($termDetails) || !empty($arrQueue['emptyApis'])) {
							foreach ($arrQueue['apiFields'] as $fieldId => $field) {
								echo '<li id="requestItem'.$fieldId.'" data-title="'.$fieldId.'" data-name="'.$field['fullName'].'" data-api-host="'.$field['apiHost'].'" data-api-path="'.$field['apiPath'].'" data-authorized-by-fieldset="'.$field['authorizedByFieldset'].'" data-type="field"><a class="delete" href="javascript:removeFromRequestQueue(\''.$fieldId.'\')"><img src="/img/icon-delete.gif" width="11" title="delete" /></a>'.$field['name'].'</li>';
							}
							foreach ($arrQueue['dbColumns'] as $columnId => $column) {
								echo '<li id="requestItem'.$columnId.'" data-title="'.$columnId.'" data-name="'.$column['name'].'" database-name="'.$column['databaseName'].'" schema-name="'.$column['schemaName'].'" table-name="'.$column['tableName'].'" data-type="column"><a class="delete" href="javascript:removeFromRequestQueue(\''.$columnId.'\')"><img src="/img/icon-delete.gif" width="11" title="delete" /></a>'.$column['name'].'</li>';
							}
							foreach ($arrQueue['samlFields'] as $fieldId => $field) {
								echo '<li id="requestItem'.$fieldId.'" data-title="'.$fieldId.'" data-name="'.$field['name'].'" response-name="'.$field['responseName'].'" data-type="samlField"><a class="delete" href="javascript:removeFromRequestQueue(\''.$fieldId.'\')"><img src="/img/icon-delete.gif" width="11" title="delete" /></a>'.$field['name'].'</li>';
							}
							foreach ($termDetails as $term){
								echo '<li id="requestItem'.$term->termrid.'" data-title="'.$term->termsignifier.'" data-id="'.$term->termrid.'" data-vocabID="'.$term->commrid.'" data-type="term"><a class="delete" href="javascript:removeFromRequestQueue(\''.$term->termrid.'\')"><img src="/img/icon-delete.gif" width="11" title="delete" /></a>'.$term->termsignifier; if ($customSAML) echo " (SAML)"; echo '</li>';
							}
							foreach ($arrQueue['emptyApis'] as $path => $api){
								$displayName = strlen($path) > 28 ? substr($path, 0, 28) . "..." : $path;
								$id = preg_replace('/\//', '', $path);
								echo '<li id="requestItem'.$id.'" data-title="'.$path.'" api-host="'.$api['apiHost'].'" data-type="api"><a class="delete" href="javascript:removeFromRequestQueue(\''.$path.'\')"><img src="/img/icon-delete.gif" width="11" title="delete" /></a>'.$displayName.'</li>';
							}
							echo '</ul><a class="clearQueue" href="javascript: clearRequestQueue()">Clear All Items</a>';
						}else{
							echo 'No request items found.</ul>';
						} ?>
					</div>
				</div>

				<h3 class="headerTab">Requester Information*</h3><div class="edit-btn grow" id="net-id" title="Enter a Net ID to populate all fields"></div>
				<div id="net-id-input-wrapper">
					<label for="requesterNetId">Requester Net Id*</label>
					<input type="text" id="net-id-input" name="requesterNetId" class="inputShade" placeholder="Input requester's Net ID" value="<?= empty($preFilled['requesterNetId']) ? h($netId) : h($preFilled['requesterNetId']) ?>" data-last-save="<?= empty($preFilled['requesterNetId']) ? h($netId) : h($preFilled['requesterNetId']) ?>">
					<div class="save grow">Save</div>
				</div>
				<div class="clear"></div>
				<div class="fieldGroup">
						<div class="field-container">
							<label for="name">Requester Name*</label>
							<input type="text" id="name" name="name" class="inputShade noPlaceHolder" value="<?= empty($preFilled['name']) ? h($psName) : h($preFilled['name']) ?>">
						</div>
						<div class="field-container">
							<label for="phone">Requester Phone*</label>
							<input type="text" id="phone" name="phone" class="inputShade noPlaceHolder" value="<?= empty($preFilled['phone']) ? h($psPhone) : h($preFilled['phone']) ?>">
						</div>
						<div class="field-container">
							<label for="role">Requester Role*</label>
							<input type="text" id="role" name="role" class="inputShade noPlaceHolder" value="<?= empty($preFilled['role']) ? h($psRole) : h($preFilled['role']) ?>">
						</div>
						<div class="field-container">
							<label for="email">Requester Email*</label>
							<input type="text" id="email" name="email" class="inputShade noPlaceHolder" value="<?= (Configure::read('debug') == 0) ? ( empty($preFilled['email']) ? h($psEmail) : h($preFilled['email']) ) : h('null@example.com') ?>">
						</div>
				</div>

				<h3 class="headerTab">Sponsor Information*</h3>
				<div class="clear"></div>
				<div class="orgNotification">If the organization requesting the application is not the organization developing it, change the information below to that of the requesting party.</div>
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
						<input type="text" id="sponsorEmail" name="sponsorEmail" class="inputShade noPlaceHolder" value="<?= (Configure::read('debug') == 0) ? ( empty($preFilled['sponsorEmail']) ? ( empty($supervisorInfo->email) ? '' : h($supervisorInfo->email) ) : $preFilled['sponsorEmail'] ) : h('null@example.com') ?>">
					</div>
					<div class="field-container">
						<label for="requestingOrganization">Requesting Organization*</label>
						<input type="text" id="requestingOrganization" name="requestingOrganization" class="inputShade noPlaceHolder" value="<?= empty($preFilled['requestingOrganization']) ? h($psDepartment) : h($preFilled['requestingOrganization']) ?>">
					</div>
				</div>

				<div class="request-form-header-wrapper">
					<label class="headerTab" for="developmentShop">Development Shop*</label>
					<div class="select-toggle-btn grow" id="developmentShopToggle">
						<span class="toText">New value</span>
						<span class="toSelect">Select existing</span>
					</div>
				</div>
				<div class="clear"></div>
				<div class="taBox">
					<select name="developmentShopId" id="developmentShopSelect" class="inputShade">
						<option value="">Select a development shop...</option>
						<option value="new">Name a new development shop</option>
						<?php foreach ($developmentShops as $devShop): ?>
							<option value="<?= $devShop->id ?>"><?= $devShop->name ?></option>
						<?php endforeach ?>
					</select>
					<input type="text" name="developmentShop" id="developmentShop" class="inputShade full noPlaceHolder" placeholder="Type to name a new development shop." value="" autocomplete="off" style="display:none;" />
				</div>

				<div class="request-form-header-wrapper" style="width:375px;">
					<label class="headerTab" for="applicationOrProjectName">Application or Project Name*</label>
					<div class="select-toggle-btn grow" id="applicationOrProjectToggle" style="display:none;">
						<span class="toText">New value</span>
						<span class="toSelect">Select existing</span>
					</div>
				</div>
				<div class="clear"></div>
				<div class="taBox">
					<select name="applicationOrProjectId" id="applicationOrProjectSelect" class="inputShade" disabled></select>
					<input type="text" name="applicationOrProjectName" id="applicationOrProjectName" class="inputShade full noPlaceHolder" placeholder="Type to name a new application or project. This name will be included in the title of this request to help you easily find it in the future." value="" autocomplete="off" style="display:none;" />
				</div>

				<?php
					$arrNonDisplay = [
						"requesterName",
						"requesterEmail",
						"requesterPhone",
						"requesterRole",
						"requesterPersonId",
						"requesterNetId",
						"sponsorName",
						"sponsorRole",
						"sponsorEmail",
						"sponsorPhone",
						"requestingOrganization",
						"developmentShopId",
						"developmentShop",
						"applicationOrProjectId",
						"applicationOrProjectName",
						"readWriteAccess",
						"requestedInformationMap",
						"technologyType",
						"api",
						"tables",
						"saml",
						Configure::read('Collibra.requiredTermsString'),
						Configure::read('Collibra.additionalTermsString'),
						Configure::read('Collibra.requiredElementsString'),
						Configure::read('Collibra.additionalElementsString')
					];
					foreach($formFields->formProperties as $field){
						if(in_array($field->id, $arrNonDisplay)){
							continue;
						}
						echo '<label class="headerTab" for="'.$field->id.'">'.$field->name;
						if ($field->name == 'Application or Project Name') echo '*';
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
					} ?>

					<label class="headerTab" for="readWriteAccess">Read-Write Access</label>
					<div class="clear"></div>
					<div class="taBox">
						<div class="info">Most requests need read-only access to the data, but let us know if you need write access as well.</div><br/>
						<div style="height:65px;">
							<input type="hidden" id="readWriteAccessInput" name="readWriteAccess" value="<?= empty($preFilled['readWriteAccess']) ? 'false' : $preFilled['readWriteAccess'] ?>">
							<div class="radioBox <?= empty($preFilled['readWriteAccess']) ? 'selected' : ( $preFilled['readWriteAccess'] === 'false' ? 'selected' : '' ) ?> grow" data-value="false">Read-only</div>
							<div class="radioBox <?= empty($preFilled['readWriteAccess']) ? '' : ( $preFilled['readWriteAccess'] === 'true' ? 'selected' : '' ) ?> grow" data-value="true">Read-write</div>
						</div>
					</div>

					<?php
					if (!empty($policies)) {
						echo '<div class="request-form-header-wrapper"><label class="headerTab">Data Usage Policies</label>
								  <div class="policies-btn grow">
								  	<span class="policiesHide">Collapse</span>
									<span class="policiesShow">Expand</span>
								  </div>
							  </div>';
						echo '<div class="clear"></div>';
						echo '<div id="policies" class="taBox">
								<div class="info">Because of the business terms included in
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
