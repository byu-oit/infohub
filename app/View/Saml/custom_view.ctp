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
				<h3 class="customSAMLText">Hit the button below if you would like to request custom SAML attributes. You'll be taken to the search page where you can choose the data elements you would like to be delivered. When you submit your request it will be noted that you're requesting a custom SAML response.</h3>
			</div>
		</div>
	</div>
</div>
