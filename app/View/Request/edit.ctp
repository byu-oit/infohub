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
			if($(this).val()==''){
				isValid = false;
				$(this).focus();
				return false;
			}
		});
		if(!isValid) alert('All fields are requried.');
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
<form action="/request/editSubmit/<?= $request->resourceId ?>" method="post" id="request" onsubmit="return validate();">
	<div id="searchBody" class="innerLower">

		<div id="requestForm">
			<h2 class="headerTab">Edit Request Form</h2>

			<div id="srLower" class="whiteBox">
				<?php if ($isaRequest): ?>
				<h3 class="headerTab">Requester Information</h3>
				<div class="clear"></div>
				<div class="fieldGroup">
					<!-- <div class="infoCol"> -->
						<div class="field-continer">
							<label for="name">Requester Name</label>
							<input type="text" id="name" name="name" class="inputShade noPlaceHolder" value="<?= h($request->attributeReferences->attributeReference['Requester Name']->value) ?>">
						</div>
						<div class="field-continer">
							<label for="phone">Requester Phone</label>
							<input type="text" id="phone" name="phone" class="inputShade noPlaceHolder" value="<?= h($request->attributeReferences->attributeReference['Requester Phone']->value) ?>">
						</div>
					<!-- </div>
					<div class="infoCol"> -->
						<div class="field-continer">
							<label for="role">Requester Role</label>
							<input type="text" id="role" name="role" class="inputShade noPlaceHolder" value="<?= h($request->attributeReferences->attributeReference['Requester Role']->value) ?>">
						</div>
						<div class="field-continer">
							<label for="email">Requester Email</label>
							<input type="text" id="email" name="email" class="inputShade noPlaceHolder" value="<?= h($request->attributeReferences->attributeReference['Requester Email']->value) ?>">
						</div>
						<div class="field-continer">
							<label for="requestingOrganization">Requester Organization</label>
							<input type="text" id="requestingOrganization" name="requestingOrganization" class="inputShade noPlaceHolder" value="<?= h($request->attributeReferences->attributeReference['Requesting Organization']->value) ?>">
						</div>
					<!-- </div> -->
				</div>

				<h3 class="headerTab">Sponsor Information</h3>
				<div class="clear"></div>
				<div class="fieldGroup">
					<div class="field-continer">
						<label for="sponsorName">Sponsor Name</label>
						<input type="text" id="sponsorName" name="sponsorName" class="inputShade noPlaceHolder" value="<?= h($request->attributeReferences->attributeReference['Sponsor Name']->value) ?>">
					</div>
					<div class="field-continer">
						<label for="sponsorPhone">Sponsor Phone</label>
						<input type="text" id="sponsorPhone" name="sponsorPhone" class="inputShade noPlaceHolder" value="<?= h($request->attributeReferences->attributeReference['Sponsor Phone']->value) ?>">
					</div>
					<div class="field-continer">
						<label for="sponsorRole">Sponsor Role</label>
						<input type="text" id="sponsorRole" name="sponsorRole" class="inputShade noPlaceHolder" value="<?= h($request->attributeReferences->attributeReference['Sponsor Role']->value) ?>">
					</div>
					<div class="field-continer">
						<label for="sponsorEmail">Sponsor Email</label>
						<input type="text" id="sponsorEmail" name="sponsorEmail" class="inputShade noPlaceHolder" value="<?= h($request->attributeReferences->attributeReference['Sponsor Email']->value) ?>">
					</div>

				</div>
				<?php endif;
					$arrNonDisplay = [
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
                    ];


					foreach($formFields->formProperties as $field){
						if(in_array($field->id, $arrNonDisplay)){
							continue;
						}
						echo '<label class="headerTab" for="'.$field->id.'">'.$field->name.'</label>'.
							'<div class="clear"></div>'.
							'<div class="taBox">';

                        $val = '';
                        foreach($request->attributeReferences->attributeReference as $ref) {
                            if ($field->name == $ref->labelReference->signifier) {
                                $val = preg_replace('/<br\/>/', "\n", $ref->value);
                                break;
                            }
                        }

						if($field->type == 'textarea'){
							echo '<textarea name="'.$field->name.'" id="'.$field->id.'" class="inputShade noPlaceHolder">'.$val.'</textarea>';
						}else{
							echo '<input type="text" name="'.$field->name.'" id="'.$field->id.'" value="'.$val.'" class="inputShade full noPlaceHolder" placeholder="'.$val.'" />';
						}

						echo '</div>';
					}
				?>
				<label for="requestSubmit" id="mobileReqd">*All Fields Required</label>
				<div class="clear"></div>
			</div>
		</div>
		<div class="clear"></div>
	</div>
	<div id="formSubmit" class="innerLower">
		<input type="submit" value="Save Changes" id="requestSubmit" name="requestSubmit" class="grow">
		<label for="requestSubmit" class="mobileHide">*All Fields Required</label>
		<div class="clear"></div>
	</div>
	<div class="clear"></div>
</form>

<!-- Quick links -->
<?php echo $this->element('quick_links'); ?>
