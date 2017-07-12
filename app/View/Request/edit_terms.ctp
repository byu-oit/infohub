<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('search', null, array('inline' => false));
?>
<script>
	function editTerms(elem, action, progressiveVerb) {
		var dsrId = $(elem).closest('#requestForm').find('h2.headerTab').attr('id');
		var arrIds = [];
		$(elem).parent().find('input').each(function() {
			if ($(this).prop('checked')) {
				arrIds.push($(this).val());
			}
		});

		$.post("/request/editTermsSubmit", {action:action,dsrId:dsrId,arrIds:arrIds})
			.done(function(data) {
				data = JSON.parse(data);
				if (data.success == 1) {
					location.reload();
				} else {
					alert('There was an error ' + progressiveVerb + ' some of the requested terms.');
					location.reload();
				}
			});
	}

	$(document).ready(function() {
		$('.addTerms').click(function() {
			editTerms(this, "add", "adding");
		});
		$('.removeTerms').click(function() {
			editTerms(this, "remove", "removing");
		});
	});
</script>
<!-- Background image div -->
<div id="searchBg" class="searchBg">
</div>

<!-- Request Form -->
<form action="/request/submit" method="post" id="request" onsubmit="return validate();">
	<div id="searchBody" class="innerLower">

		<div id="requestForm">
			<h2 class="headerTab" id="<?= $request->resourceId ?>"><?= $request->signifier ?></h2>

			<div id="srLower" class="whiteBox">
				<h3 class="headerTab">Add Terms</h3>
				<div class="clear"></div>
				<div class="resultItem">
					<?php if (!empty($arrQueue->businessTerms)): ?>
					<div class="checkAll"><input type="checkbox" onclick="toggleAllCheckboxes(this)" checked="checked">Check/Uncheck all</div>
					<?php endif; ?>
					<div class="irLower"><ul>
						<?php
						if(!empty($arrQueue->businessTerms) /*|| !empty($arrQueue->concepts) || !empty($arrQueue->emptyApis) || !empty($arrQueue->apiFields)*/) {
							foreach ($arrQueue->businessTerms as $id => $term){
								echo '<li id="requestItem'.$id.'"><input type="checkbox" name="businessTerms[]" value="'.$id.'" checked="checked">'.$term['term'].'</li>';
							}
							// foreach ($arrQueue->concepts as $id => $term) {
							// 	echo '<li id="requestItem'.$id.'"><input type="checkbox" name="concepts[]" value="'.$id.'" checked="checked">'.$term['term'].'</li>';
							// }
							// foreach ($arrQueue->emptyApis as $path => $api){
							// 	$displayName = strlen($path) > 28 ? substr($path, 0, 28) . "..." : $path;
							// 	$id = preg_replace('/\//', '', $path);
							// 	echo '<li id="requestItem'.$id.'"><input type="checkbox" name="apis[]" value="'.$path.'" checked="checked">'.$displayName.'</li>';
							// }
							// foreach ($arrQueue->apiFields as $fieldPath => $field) {
							// 	echo '<li id="requestItem'.$fieldPath.'"><input type="checkbox" name="apiFields[]" value="'.$fieldPath.'" checked="checked">'.$field['name'].'</li>';
							// }
							echo '</ul><a class="addTerms grow">Add to this DSR</a>';
						}else{
							echo 'No request items found.</ul>';
						} ?>
					</div>
				</div>
                <div class="clear"></div>

                <h3 class="headerTab">Remove Terms</h3>
                <div class="clear"></div>
                <div class="resultItem">
					<?php if (!empty($request->terms)): ?>
					<div class="checkAll"><input type="checkbox" onclick="toggleAllCheckboxes(this)">Check/Uncheck all</div>
					<?php endif; ?>
					<div class="irLower"><ul>
						<?php
							if (!empty($request->terms)) {
								foreach($request->terms as $term) {
									echo '<li id="requestItem'.$term->termrid.'"><input type="checkbox" name="requestedTerms" value="'.$term->relationrid.'">'.$term->termsignifier.'</li>';
								}
								echo '</ul><a class="removeTerms grow">Remove from this DSR</a>';
							}else{
								echo 'No request items found.</ul>';
							} ?>
					</div>
                </div>
            </div>
        </div>
    </div>
    <div class="clear"></div>
</form>

<!-- Quick links -->
<?php echo $this->element('quick_links'); ?>
