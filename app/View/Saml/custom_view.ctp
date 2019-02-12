<?php
	$this->Html->css('secondary', null, ['inline' => false]);
	$this->Html->css('search', null, ['inline' => false]);
?>
<script>
	$(document).ready(function() {
		$("#browse-tab").addClass('active');
	});

	function displayPendingApproval(elem) {
		$('#searchResults').append('<div id="pendingApprovalMessage">The classification of this element is pending approval.</div>');
		$('#pendingApprovalMessage').offset({top:$(elem).offset().top - 45, left:$(elem).offset().left - 77});
	}

	function hidePendingAproval() {
		$('#pendingApprovalMessage').remove();
	}
</script>
<div id="apiBody" class="innerLower">
	<div id="searchResults">
		<h1 class="headerTab">SAML Custom Attributes</h1>
		<div class="clear"></div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem">
			<form action="/request/addCustomSAML" method="post">
				<input type="submit" class="requestAccess grow mainRequestBtn" value="Request Custom SAML">
			</form>
				<h3 class="customSAMLText">If you would like to request a custom SAML response, you can <a href="/search">search for the terms you need here</a>. Once you've found the data you need, <a href="/request">fill out the request form</a> and note in the "Additional Information Requested" field that you need the data via SAML.</h3>
			</div>
		</div>
	</div>
</div>
