<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('search', null, array('inline' => false));
	$this->Html->css('account', null, array('inline' => false));
?>
<script>
function submitProposedTerms() {
	var arrFields = [];
	var api = '<?=$basePath?>';

	$('tbody tr').each(function() {
		if ($(this).find('input:checkbox').attr('name') == 'toggleCheckboxes') {
			return;
		}
		if (!$(this).find('input:checkbox').prop('checked')) {
			return;
		}

		var inField = $(this).find('input:text');
		arrFields.push({
			id:inField.attr('field-id'),
			name:inField.attr('field-name'),
			desc:inField.val()
		});
	});

	if (!arrFields.length) {
		alert('You haven\'t selected any fields to submit.');
		return;
	}

	$.post('/api_admin/proposeTerms', {api:api,fields:arrFields})
		.done(function(data) {
			data = JSON.parse(data);
			if (data.success) {
				alert('Business terms successfully submitted.');
				window.location.href = '/api_admin/update/<?=$hostname.$basePath?>';
			} else {
				alert('An error occurred trying to submit the proposal.');
			}
		});
}
</script>
<style type="text/css">
    table.api-terms {
        width: 100%;
    }
	table.api-terms tr:hover {
		background-color: #eee
	}
	table.api-terms tr.header:hover {
		background-color: inherit;
	}
	.resultItem .lower-btn {
	  top: 25px !important;
	  text-decoration: none;
	}
	.resultItem .lower-btn.submit {
	  background-color: #ffa900 !important;
	}
    table.api-terms tr td input {
      width: 100%;
    }
</style>
<div id="searchBody" class="innerLower">
	<div id="searchResults">
		<h1 class="headerTab"><?= $hostname . '/' . trim($basePath, '/') ?></h1>
		<div class="clear"></div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem">
				<table class="api-terms">
                    <colgroup>
                        <col style="width: 5%;">
                        <col style="width: 20%;">
                        <col style="width: 75%;">
                    </colgroup>
					<tr class="header">
                        <th><input type="checkbox" onclick="toggleAllCheckboxes(this)" name="toggleCheckboxes"/></th>
						<th>Field</th>
						<th>Proposed description (Optional)</th>
					</tr>
					<?php foreach ($terms as $index => $term):
                            if (!empty($term->businessTerm)) continue; ?>
						<tr>
                            <td><input type="checkbox"></td>
							<td><?=$term->name?></td>
							<td>
								<input type="text" field-id="<?=$term->id?>" field-name="<?=$term->name?>" placeholder="">
							</td>
						</tr>
					<?php endforeach ?>
				</table>
				<a class="lower-btn submit grow" href="javascript:submitProposedTerms()">Submit</a>
				<a class="lower-btn grow" href="/apis/<?=$hostname.$basePath?>">Cancel</a>
			</div>
		</div>
	</div>
</div>
