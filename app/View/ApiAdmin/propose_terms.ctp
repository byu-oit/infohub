<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('search', null, array('inline' => false));
	$this->Html->css('account', null, array('inline' => false));
?>
<script>
	function submitProposedTerms() {
		var arrFields = [];
		var api = '<?=$basePath?>';

		var valid = true;
		$('tbody tr').each(function() {
			var thisElem = $(this);
			if (thisElem.find('input:checkbox').attr('name') == 'toggleCheckboxes') {
				return;
			}
			if (!thisElem.find('input:checkbox').prop('checked')) {
				return;
			}

			if (!thisElem.find('.proposed-name').val() || !thisElem.find('.proposed-def').val()) {
				alert('You must propose a name and definition for each business term selected.');
				valid = false;
				return false;
			}

			arrFields.push({
				id:thisElem.attr('field-id'),
				fieldName:thisElem.attr('field-name'),
				propName:thisElem.find('.proposed-name').val(),
				desc:thisElem.find('.proposed-def').val()
			});
		});
		if (!valid) {
			return;
		}

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

	$(document).ready(function() {

		$('.proposed-def').on('input', function() {
			var tableRow = $(this).parent().parent();
			var nonEmpty = $(this).val() != '';

			tableRow.find('input:checkbox')
				.prop('checked', nonEmpty);
		});

	});
</script>
<style type="text/css">
    table.new-terms {
        width: 100%;
    }
	table.new-terms th {
		font-size: 12px;
	}
	table.new-terms tr:hover {
		background-color: #eee
	}
	table.new-terms tr.header:hover {
		background-color: inherit;
	}
	.resultItem .lower-btn {
	  top: 25px !important;
	  text-decoration: none;
	}
	.resultItem .lower-btn.submit {
	  background-color: #ffa900 !important;
	}
    table.new-terms tr td input {
      width: 100%;
    }
</style>
<div id="apiBody" class="innerLower">
	<div id="searchResults">
		<h1 class="headerTab"><?= $hostname . '/' . trim($basePath, '/') ?></h1>
		<div class="clear"></div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem">
				<table class="new-terms">
                    <colgroup>
                        <col style="width: 5%;">
                        <col style="width: 17%;">
						<col style="width: 20%;">
						<col style="width: 1%;"><?php // For space between the two text fields ?>
                        <col style="width: 57%;">
                    </colgroup>
					<tr class="header">
                        <th><input type="checkbox" onclick="toggleAllCheckboxes(this)" name="toggleCheckboxes"/></th>
						<th>Field</th>
						<th>Proposed name</th>
						<th></th>
						<th>Proposed definition</th>
					</tr>
					<?php foreach ($terms as $index => $term):
                            if (!empty($term->businessTerm)) continue; ?>
						<tr field-id="<?=$term->id?>" field-name="<?=$term->name?>">
                            <td><input type="checkbox"></td>
							<td><?php
								$termPath = explode('.', $term->name);
								for ($i = 0; $i < count($termPath) - 1; $i++) {
									echo str_repeat('&nbsp;', 12);
								}
								echo end($termPath);
							?></td>
							<td>
								<?php $name = end($termPath);
								$name = ucwords(str_replace("_", " ", $name)); ?>
								<input type="text" class="proposed-name" value="<?=$name?>">
							</td>
							<td></td>
							<td>
								<input type="text" class="proposed-def" placeholder="">
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
