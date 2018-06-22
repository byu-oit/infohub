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
		$('#request input').each(function() {
			if($(this).val()==''){
				isValid = false;
				$(this).focus();
				return false;
			}
		});
		if(!isValid) {
			if ('<?=$parent?>' == '1') {
				alert('Requester and Sponsor Information and Application Name are required.');
			} else {
				alert('Application Name is required.');
			}
		}
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
<form action="/request/editSubmit/<?= $asset->id ?><?= $parent ? '' : '/false' ?>?g=<?= $guest ? '1' : '0' ?>" method="post" id="request" onsubmit="return validate();">
	<div id="searchBody" class="innerLower">

		<div id="requestForm">
			<h2 class="headerTab">Edit Request Form</h2>

			<div id="srLower" class="whiteBox">
				<?php if ($parent): ?>
				<h3 class="headerTab">Requester Information</h3>
				<div class="clear"></div>
				<div class="fieldGroup">
					<!-- <div class="infoCol"> -->
						<div class="field-container">
							<label for="name">Requester Name*</label>
							<input type="text" id="name" name="<?= Configure::read('Collibra.formFields.name') ?>" class="inputShade noPlaceHolder" value="<?= h($asset->attributes['Requester Name']->attrValue) ?>">
						</div>
						<div class="field-container">
							<label for="phone">Requester Phone*</label>
							<input type="text" id="phone" name="<?= Configure::read('Collibra.formFields.phone') ?>" class="inputShade noPlaceHolder" value="<?= h($asset->attributes['Requester Phone']->attrValue) ?>">
						</div>
					<!-- </div>
					<div class="infoCol"> -->
						<div class="field-container">
							<label for="role">Requester Role*</label>
							<input type="text" id="role" name="<?= Configure::read('Collibra.formFields.role') ?>" class="inputShade noPlaceHolder" value="<?= h($asset->attributes['Requester Role']->attrValue) ?>">
						</div>
						<div class="field-container">
							<label for="email">Requester Email*</label>
							<input type="text" id="email" name="<?= Configure::read('Collibra.formFields.email') ?>" class="inputShade noPlaceHolder" value="<?= h($asset->attributes['Requester Email']->attrValue) ?>">
						</div>
						<div class="field-container">
							<label for="requestingOrganization">Requester Organization*</label>
							<input type="text" id="requestingOrganization" name="<?= Configure::read('Collibra.formFields.requestingOrganization') ?>" class="inputShade noPlaceHolder" value="<?= h($asset->attributes['Requesting Organization']->attrValue) ?>">
						</div>
					<!-- </div> -->
				</div>

				<h3 class="headerTab">Sponsor Information</h3>
				<div class="clear"></div>
				<div class="fieldGroup">
					<div class="field-container">
						<label for="sponsorName">Sponsor Name*</label>
						<input type="text" id="sponsorName" name="<?= Configure::read('Collibra.formFields.sponsorName') ?>" class="inputShade noPlaceHolder" value="<?= h($asset->attributes['Sponsor Name']->attrValue) ?>">
					</div>
					<div class="field-container">
						<label for="sponsorPhone">Sponsor Phone*</label>
						<input type="text" id="sponsorPhone" name="<?= Configure::read('Collibra.formFields.sponsorPhone') ?>" class="inputShade noPlaceHolder" value="<?= h($asset->attributes['Sponsor Phone']->attrValue) ?>">
					</div>
					<div class="field-container">
						<label for="sponsorRole">Sponsor Role*</label>
						<input type="text" id="sponsorRole" name="<?= Configure::read('Collibra.formFields.sponsorRole') ?>" class="inputShade noPlaceHolder" value="<?= h($asset->attributes['Sponsor Role']->attrValue) ?>">
					</div>
					<div class="field-container">
						<label for="sponsorEmail">Sponsor Email*</label>
						<input type="text" id="sponsorEmail" name="<?= Configure::read('Collibra.formFields.sponsorEmail') ?>" class="inputShade noPlaceHolder" value="<?= h($asset->attributes['Sponsor Email']->attrValue) ?>">
					</div>

				</div>
				<?php endif;

					foreach($formFields as $field){
						echo '<label class="headerTab" for="'.$field->id.'">'.$field->name;
						if ($field->id == 'applicationName') echo '*';
						echo '</label>'.
							'<div class="clear"></div>'.
							'<div class="taBox">';

						$placeholderText = $field->value;
                        $val = preg_replace('/<br\/>/', "\n", $asset->attributes[$field->name]->attrValue);

						if($field->type == 'textarea'){
							echo '<textarea name="'.Configure::read('Collibra.formFields.'.$field->id).'" id="'.$field->id.'" class="inputShade noPlaceHolder" placeholder="'.$placeholderText.'">'.$val.'</textarea>';
						}else{
							echo '<input type="text" name="'.Configure::read('Collibra.formFields.'.$field->id).'" id="'.$field->id.'" value="'.$val.'" class="inputShade full noPlaceHolder" placeholder="'.$placeholderText.'" />';
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
		<input type="submit" value="Save Changes" id="requestSubmit" name="requestSubmit" class="grow">
		<label for="requestSubmit" class="mobileHide">*Required field</label>
		<div class="clear"></div>
	</div>
	<div class="clear"></div>
</form>
