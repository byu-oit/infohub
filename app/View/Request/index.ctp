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

		var developmentShopIndex = -1;
		$('#developmentShop').keypress(function(event) { return event.keyCode != 13; });
		$('#developmentShop').on({
			keyup: function(e) {
				var move;

				if ($.trim($('#developmentShop').val()) == '') {
					$('.developmentShopAutoComplete').hide();
					$('.developmentShopAutoComplete .results').html('');
				} else if  (e == true) {
					$('.developmentShopAutoComplete').hide();
					$('.developmentShopAutoComplete .results').html('');
				} else {
					switch (e.which) {

						case 27: // escape
							$('.developmentShopAutoComplete').hide();
							$('.developmentShopAutoComplete .results').html('');
							developmentShopIndex = -1;
							break;

						case 13: // enter
							if ($('.developmentShopAutoComplete li').hasClass('active')) {
								$('.developmentShopAutoComplete li.active').click();
							} else {
								$('.developmentShopAutoComplete li').eq(0).click();
							}
							break;

						case 38: // up
							e.preventDefault();
							if (developmentShopIndex == -1) {
								developmentShopIndex = $('.developmentShopAutoComplete li').length - 1;
							} else {
								developmentShopIndex--;
							}

							if (developmentShopIndex > $('.developmentShopAutoComplete li').length ) {
								developmentShopIndex = $('.developmentShopAutoComplete li').length + 1;
							}
							move = true;
							break;

						case 40: // down
							e.preventDefault();
							if (developmentShopIndex >= $('.developmentShopAutoComplete li').length - 1) {
								developmentShopIndex = 0;
							} else {
								developmentShopIndex++;
							}
							move = true;
							break;

						default:
							var val = $('#developmentShop').val();
							setTimeout(function() {
								if (val != $('#developmentShop').val()) {
									// User continued typing, so throw this out
									return;
								}
								$.getJSON( "/developmentShop/search/"+val )
									.done(function( data ) {
										if (val != $('#developmentShop').val()) {
											// User continued typing, so throw this out
											return;
										}
										$('.developmentShopAutoComplete .results').html('');
										for (var i in data) {
											$('.developmentShopAutoComplete .results').append('<li>'+data[i].name+'</li>');
										}
										if ($('.developmentShopAutoComplete li').size()) {
											$('.developmentShopAutoComplete').show();
										} else {
											$('.developmentShopAutoComplete').hide();
										}
									});
							}, 300);

							break;
					}
				}

				if (move) {
					$('.developmentShopAutoComplete li.active').removeClass('active');
					$('.developmentShopAutoComplete li').eq(developmentShopIndex).addClass('active');
				}
			}
		});
		$('.developmentShopAutoComplete').on('click', 'li', function() {
			$('#developmentShop').val($(this).text());
			$('#developmentShop').focusout();
			$('.developmentShopAutoComplete').hide();
			$('.developmentShopAutoComplete .results').html('');
		});

		var applicationOrProjectNameIndex = -1;
		$('#applicationOrProjectName').keypress(function(event) { return event.keyCode != 13; });
		$('#applicationOrProjectName').on({
			keyup: function(e) {
				if ($('#developmentShop').val() == '') {
					return;
				}
				var move;

				if ($.trim($('#applicationOrProjectName').val()) == '') {
					$('.applicationOrProjectNameAutoComplete').hide();
					$('.applicationOrProjectNameAutoComplete .results').html('');
				} else if  (e == true) {
					$('.applicationOrProjectNameAutoComplete').hide();
					$('.applicationOrProjectNameAutoComplete .results').html('');
				} else {
					switch (e.which) {

						case 27: // escape
							$('.applicationOrProjectNameAutoComplete').hide();
							$('.applicationOrProjectNameAutoComplete .results').html('');
							applicationOrProjectNameIndex = -1;
							break;

						case 13: // enter
							if ($('.applicationOrProjectNameAutoComplete li').hasClass('active')) {
								$('.applicationOrProjectNameAutoComplete li.active').click();
							} else {
								$('.applicationOrProjectNameAutoComplete li').eq(0).click();
							}
							break;

						case 38: // up
							e.preventDefault();
							if (applicationOrProjectNameIndex == -1) {
								applicationOrProjectNameIndex = $('.applicationOrProjectNameAutoComplete li').length - 1;
							} else {
								applicationOrProjectNameIndex--;
							}

							if (applicationOrProjectNameIndex > $('.applicationOrProjectNameAutoComplete li').length ) {
								applicationOrProjectNameIndex = $('.applicationOrProjectNameAutoComplete li').length + 1;
							}
							move = true;
							break;

						case 40: // down
							e.preventDefault();
							if (applicationOrProjectNameIndex >= $('.applicationOrProjectNameAutoComplete li').length - 1) {
								applicationOrProjectNameIndex = 0;
							} else {
								applicationOrProjectNameIndex++;
							}
							move = true;
							break;

						default:
							var val = $('#applicationOrProjectName').val();
							setTimeout(function() {
								if (val != $('#applicationOrProjectName').val()) {
									// User continued typing, so throw this out
									return;
								}
								$.getJSON( "/developmentShop/getDetails/"+$('#developmentShop').val() )
									.done(function( data ) {
										if (val != $('#applicationOrProjectName').val()) {
											// User continued typing, so throw this out
											return;
										}
										$('.applicationOrProjectNameAutoComplete .results').html('');
										for (var i in data[0].applications) {
											if (data[0].applications[i].appName.toLowerCase().search(val.toLowerCase()) !== -1) {
												$('.applicationOrProjectNameAutoComplete .results').append('<li>'+data[0].applications[i].appName+'</li>');
											}
										}
										if ($('.applicationOrProjectNameAutoComplete li').size()) {
											$('.applicationOrProjectNameAutoComplete').show();
										} else {
											$('.applicationOrProjectNameAutoComplete').hide();
										}
									});
							}, 300);

							break;
					}
				}

				if (move) {
					$('.applicationOrProjectNameAutoComplete li.active').removeClass('active');
					$('.applicationOrProjectNameAutoComplete li').eq(applicationOrProjectNameIndex).addClass('active');
				}
			}
		});
		$('.applicationOrProjectNameAutoComplete').on('click', 'li', function() {
			$('#applicationOrProjectName').val($(this).text());
			$('#applicationOrProjectName').focusout();
			$('.applicationOrProjectNameAutoComplete').hide();
			$('.applicationOrProjectNameAutoComplete .results').html('');
		});

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
			$('#srLower').find('input, textarea').each(function() {
				postData[$(this).prop('name')] = $(this).prop('value');
			});

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

	function validate() {
		var isValid = true;
		$('#request input').each(function() {
			if (!$(this).val()) {
				isValid = false;
				$(this).focus();
				return false;
			}
		});
		if (!isValid) alert('Requester and Sponsor Information, Development Shop, and Application or Project Name are required.');
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
					if(!empty($termDetails) || !empty($arrQueue['concepts']) || !empty($arrQueue['emptyApis']) || !empty($arrQueue['apiFields']) || !empty($arrQueue['dbColumns']) || !empty($arrQueue['samlFields'])) {
							foreach ($termDetails as $term){
								echo '<li id="requestItem'.$term->termrid.'" data-title="'.$term->termsignifier.'" data-id="'.$term->termrid.'" data-vocabID="'.$term->commrid.'" api-host="'.$term->apihost.'" api-path="'.$term->apipath.'" schema-name="'.$term->schemaname.'" table-name="'.$term->tablename.'" response-name="'.$term->responsename.'" data-type="term"><a class="delete" href="javascript:removeFromRequestQueue(\''.$term->termrid.'\')"><img src="/img/icon-delete.gif" width="11" title="delete" /></a>'.$term->termsignifier.'</li>';
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
							foreach ($arrQueue['samlFields'] as $fieldName => $field) {
								$id = preg_replace('/\./', '', $fieldName);
								echo '<li id="requestItem'.$id.'" data-title="'.$fieldName.'" response-name="'.$field['responseName'].'" data-type="samlField"><a class="delete" href="javascript:removeFromRequestQueue(\''.$fieldName.'\')"><img src="/img/icon-delete.gif" width="11" title="delete" /></a>'.$field['name'].'</li>';
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

				<label class="headerTab" for="developmentShop">Development Shop*</label>
				<div class="clear"></div>
				<div class="taBox">
					<input type="text" name="developmentShop" id="developmentShop" class="inputShade full noPlaceHolder" placeholder="Type to search existing development shops or name a new one." value="<?= empty($preFilled['developmentShop']) ? '' : h($preFilled['developmentShop']) ?>" autocomplete="off" />
					<div class="developmentShopAutoComplete">
						<ul class="results"></ul>
					</div>
				</div>

				<label class="headerTab" for="applicationOrProjectName">Application or Project Name*</label>
				<div class="clear"></div>
				<div class="taBox">
					<input type="text" name="applicationOrProjectName" id="applicationOrProjectName" class="inputShade full noPlaceHolder" placeholder="Type to search existing applications or name a new one. This name will be included in the title of this request to help you easily find it in the future." value="<?= empty($preFilled['applicationOrProjectName']) ? '' : h($preFilled['applicationOrProjectName']) ?>" autocomplete="off" />
					<div class="applicationOrProjectNameAutoComplete">
						<ul class="results"></ul>
					</div>
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
						"developmentShop",
						"applicationOrProjectName",
						"readWriteAccess",
						"requestedInformationMap",
						"technologyType",
						"api",
						"tables",
						"saml",
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
						echo '<div class="policy-header-wrapper"><label class="headerTab">Data Usage Policies</label>
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
