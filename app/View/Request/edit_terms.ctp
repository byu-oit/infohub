<?php
	$this->Html->css('secondary', null, ['inline' => false]);
	$this->Html->css('search', null, ['inline' => false]);
?>
<script>
	function addTerms(elem) {
		var i = 0;
		var loadingTexts = ['Working on it   ','Working on it.  ','Working on it.. ','Working on it...'];
		var loadingTextInterval = setInterval(function() {
			$(elem).html(loadingTexts[i]);
			i++;
			if (i == loadingTexts.length) i = 0;
		}, 250);

		var dsrId = $(elem).closest('#requestForm').find('h2.headerTab').attr('id');
		var arrApiFields = [];
		var arrDbColumns = [];
		var arrVirtualColumns = [];
		var arrSamlFields = [];
		var arrBusinessTerms = [];
		var arrApis = [];
		$(elem).parent().find('input').each(function() {
			if ($(this).prop('checked')) {
				if ($(this).attr('name') == 'apiFields[]') {
					arrApiFields.push({
						id:$(this).val(),
						fullName:$(this).attr('fullName'),
						apiPath:$(this).attr('apiPath'),
						apiHost:$(this).attr('apiHost')
					});
				}
				else if ($(this).attr('name') == 'dbColumns[]') {
					arrDbColumns.push({
						id:$(this).val(),
						tableName:$(this).attr('tableName'),
						schemaName:$(this).attr('schemaName'),
						databaseName:$(this).attr('databaseName')
					});
				}
				else if ($(this).attr('name') == 'virtualColumns[]') {
					arrVirtualColumns.push({
						id:$(this).val(),
						tableName:$(this).attr('tableName'),
						tableId:$(this).attr('tableId')
					});
				}
				else if ($(this).attr('name') == 'samlFields[]') {
					arrSamlFields.push({
						id:$(this).val(),
						responseName:$(this).attr('responseName')
					});
				}
				else if ($(this).attr('name') == 'businessTerms[]') {
					arrBusinessTerms.push($(this).val());
				}
				else if ($(this).attr('name') == 'apis[]') {
					arrApis.push($(this).val());
				}
			}
		});

		if (
			arrApiFields.length == 0 &&
			arrDbColumns.length == 0 &&
			arrVirtualColumns.length == 0 &&
			arrSamlFields.length == 0 &&
			arrBusinessTerms.length == 0 &&
			arrApis.length == 0
		) {
			alert('No elements to add selected');
			return;
		}

		$.post("/request/editTermsSubmitAdd", {dsrId:dsrId,arrApiFields:arrApiFields,arrDbColumns:arrDbColumns,arrVirtualColumns:arrVirtualColumns,arrSamlFields:arrSamlFields,arrBusinessTerms:arrBusinessTerms,arrApis:arrApis})
			.done(function(data) {
				clearInterval(loadingTextInterval);
				data = JSON.parse(data);
				if (data.success == 1) {
					location.reload();
				} else {
					alert('There was an error adding some of the requested terms.');
					location.reload();
				}
			});
	}

	function removeTerms(elem) {
		var i = 0;
		var loadingTexts = ['Working on it   ','Working on it.  ','Working on it.. ','Working on it...'];
		var loadingTextInterval = setInterval(function() {
			$(elem).html(loadingTexts[i]);
			i++;
			if (i == loadingTexts.length) i = 0;
		}, 250);

        var dsrId = $(elem).closest('#requestForm').find('h2.headerTab').attr('id');
        var arrIds = [];
		var arrNames = [];
		var arrRelIds = [];
        $(elem).parent().find('input').each(function() {
            if ($(this).prop('checked')) {
                arrIds.push($(this).val());
				arrNames.push($(this).data('signifier'));
				arrRelIds.push($(this).data('relationId'));
            }
        });

        $.post("/request/editTermsSubmitRemove", {dsrId:dsrId,arrIds:arrIds,arrNames:arrNames,arrRelIds:arrRelIds})
            .done(function(data) {
				clearInterval(loadingTextInterval);
                data = JSON.parse(data);
                if (data.success == 1) {
                    location.reload();
                } else {
                    alert('There was an error removing some of the requested terms.');
                    location.reload();
                }
            });
    }

	$(document).ready(function() {
		$('.addTerms').click(function() {
			addTerms(this);
		});
		$('.removeTerms').click(function() {
			removeTerms(this);
		});
	});
</script>
<style type="text/css">
#request #searchBody #requestForm #srLower .resultItem .irLower ul h4 {
  margin: 15px;
}
#request #searchBody #requestForm #srLower .resultItem .irLower ul li {
  width: 100%;
  float: none;
}
</style>
<!-- Background image div -->
<div id="searchBg" class="searchBg">
</div>

<!-- Request Form -->
<form action="/request/submit" method="post" id="request" onsubmit="return validate();">
	<div id="searchBody" class="innerLower">

		<div id="requestForm">
			<h2 class="headerTab" id="<?= $request->id ?>"><a href="/request/view/<?=$request->id?>"><?= $request->assetName ?></a></h2>

			<div id="srLower" class="whiteBox">
				<h3 class="headerTab">Add Information</h3>
				<div class="clear"></div>
				<div class="resultItem">
					<?php
					$cartEmpty = empty($organizedApiFields) && empty($organizedDbColumns) && empty($organizedSamlFields) && empty($filteredApis) && empty($filteredCartTerms);
					if (!$cartEmpty): ?>
					<div class="checkAll"><input type="checkbox" onclick="toggleAllCheckboxes(this)" checked="checked">Check/Uncheck all</div>
					<div class="irLower"><ul class="cart">
						<?php
							if (!empty($organizedApiFields)) {
								foreach ($organizedApiFields as $apiPath => $fields) {
									echo '<h4>/'.$apiPath.'</h4>';
									foreach ($fields as $id => $field) {
										echo '<li id="requestItem'.$id.'"><input type="checkbox" name="apiFields[]" value="'.$id.'" fullName="'.$field['fullName'].'" apiHost="'.$field['apiHost'].'" apiPath="'.$field['apiPath'].'" checked="checked">'.$field['fullName'].'</li>';
									}
								}
							}
							if (!empty($organizedDbColumns)) {
								foreach ($organizedDbColumns as $tableName => $columns) {
									echo '<h4>'.$tableName.'</h4>';
									foreach ($columns as $id => $column) {
										echo '<li id="requestItem'.$id.'"><input type="checkbox" name="dbColumns[]" value="'.$id.'" databaseName="'.$column['databaseName'].'" schemaName="'.$column['schemaName'].'" tableName="'.$column['tableName'].'" checked="checked">'.$column['name'].'</li>';
									}
								}
							}
							if (!empty($organizedVirtualColumns)) {
								foreach ($organizedVirtualColumns as $virtualTableName => $columns) {
									echo '<h4>'.$virtualTableName.'</h4>';
									foreach ($columns as $id => $column) {
										echo '<li id="requestItem'.$id.'"><input type="checkbox" name="virtualColumns[]" value="'.$id.'" tableName="'.$column['tableName'].'" tableId="'.$column['tableId'].'" checked="checked">'.$column['name'].'</li>';
									}
								}
							}
							if (!empty($organizedSamlFields)) {
								foreach ($organizedSamlFields as $responseName => $fields) {
									echo '<h4>'.$responseName.'</h4>';
									foreach ($fields as $id => $field) {
										echo '<li id="requestItem'.$id.'"><input type="checkbox" name="samlFields[]" value="'.$id.'" responseName="'.$field['responseName'].'" checked="checked">'.$field['name'].'</li>';
									}
								}
							}
							if ((!empty($organizedApiFields) || !empty($organizedDbColumns) || !empty($organizedSamlFields)) && (!empty($filteredApis) || !empty($filteredCartTerms))) {
								echo '<h4>&nbsp;</h4>';
							}
							if (!empty($filteredApis)) {
								foreach ($filteredApis as $path => $api) {
									$displayName = strlen($path) > 28 ? substr($path, 0, 28) . "..." : $path;
									$id = preg_replace('/\//', '', $path);
									echo '<li id="requestItem'.$id.'"><input type="checkbox" name="apis[]" value="'.$path.'" checked="checked">'.$displayName.'</li>';
								}
							}
							if (!empty($filteredCartTerms)) {
								foreach ($filteredCartTerms as $id => $term) {
									echo '<li id="requestItem'.$id.'"><input type="checkbox" name="businessTerms[]" value="'.$id.'" checked="checked">'.$term['term'].'</li>';
								}
							}

							echo '</ul><a class="addTerms grow">Add to this DSR</a>';
						?>
					</div>
					<?php else:
						echo '<div class="irLower noCart">To add items to this request, first add the desired information to your cart and then return to this page.</div>';
					endif; ?>
				</div>
                <div class="clear"></div>

                <h3 class="headerTab">Remove Data Elements</h3>
                <div class="clear"></div>
                <div class="resultItem">
					<?php if (!empty($requestedData)): ?>
					<div class="checkAll"><input type="checkbox" onclick="toggleAllCheckboxes(this)">Check/Uncheck all</div>
					<?php endif; ?>
					<div class="irLower"><ul>
						<?php
							if (!empty($requestedData)) {
								foreach ($requestedData as $vocab => $dataAssets) {
									echo '<h4>'.$vocab.'</h4>';
									foreach ($dataAssets as $data) {
										echo '<li id="requestItem'.$data->reqDataId.'"><input type="checkbox" name="requestedTerms" value="'.$data->reqDataId.'" data-signifier="'.$data->reqDataSignifier.'" data-relation-id="'.$data->reqDataRelationId.'">'.$data->reqDataSignifier.'</li>';
									}
								}
								echo '</ul><a class="removeTerms grow">Remove from this DSR</a>';
							} else {
								echo 'No request items found.</ul>';
							} ?>
					</div>
                </div>
            </div>
        </div>
    </div>
    <div class="clear"></div>
</form>
