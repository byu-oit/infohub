<?php
	$this->Html->css('secondary', null, ['inline' => false]);
	$this->Html->css('search', null, ['inline' => false]);
?>

<script>
$(document).ready(function() {
	$("#browse-tab").addClass('active');

	var responses = [<?php foreach ($responses as $response) { echo '"'.$response->responseName.'",'; } ?> ""];
	$('#responseFilter').on('input', function() {
		var filterValue = $(this).val().toLowerCase();
		for (var i = 0; i < responses.length; i++) {
			if (!responses[i].toLowerCase().includes(filterValue)) {
				$('#catalogIndex-'+i).css('display', 'none');
			} else {
				$('#catalogIndex-'+i).css('display', 'block');
			}
		}
	});
	$('#saml_help_btn').click(function() {
		$('#saml_help').toggle(500);
	});

	$('#responseFilter').keypress(function(event) { return event.keyCode != 13; });
	$('#responseFilter').on({
		keyup: function(e) {
			if (e.which === 13) {
				var filterValue = $(this).val().toLowerCase();
				for (var i = 0; i < responses.length; i++) {
					if (responses[i].toLowerCase().includes(filterValue)) {
						window.location.href = window.location.origin+'/saml/view/'+$('#catalogIndex-'+i).data('name');
						break;
					}
				}
			}
		}
	});
});
</script>

<style>
.ui-autocomplete {
	display: none !important;
}
.autoCompleteResponses {
	width: 490px;
}
</style>

<!-- Background image div -->
<div id="searchBg" class="searchBg">
</div>

<div id="searchBody" class="innerLower">

	<div id="searchMain" style="padding-top: 35px;">
		<h1 class="headerTab">SAML Responses</h2>
		<div class="clear"></div>
		<div id="smLower" class="whiteBox">
			<div id="saml_help_btn" class="samlHelp">Not seeing the SAML response you need?</div>
			<div id="saml_help" class="samlHelpSml">If you would like to request a custom SAML response, you can <a href="/search">search for the terms you need here</a>. Once you've found the data you need, <a href="/request">fill out the request form</a> and note in the "Additional Information Requested" field that you need the data via SAML.</div>
			<ul class="catalogParent">
				<?php if (empty($responses)): ?>
					No responses found
				<?php else: ?>
					<ul>
						<?php $i = 0;
						foreach ($responses as $response): ?>
							<li id="catalogIndex-<?=$i?>" class="catalogItem" data-name="<?=$response->responseName?>">
								<?= $this->Html->link($response->responseName, ['action' => 'view', $response->responseName]) ?>
							</li>
						<?php $i++;
						endforeach; ?>
					</ul>
				<?php endif ?>
			</ul>
		</div>
	</div>

</div>
