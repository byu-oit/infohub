<style type="text/css">
	table.swagger {
		width: 100%;
	}
	table.swagger td input {
		width: 100%;
	}
	.searchDialog {
		display: none;
	}
	.ui-autocomplete-loading {
		background: white url("<?= $this->Html->url($this->webroot . 'img/ui-anim_basic_16x16.gif') ?>") right center no-repeat;
	}
</style>
<div class="innerLower">
	<?= $this->Form->create() ?>
		<?= $this->Form->input('host') ?>
		<?= $this->Form->input('basePath') ?>
		<table class="swagger">
			<tr>
				<th>Field</th>
				<th width="1%">Business Term</th>
				<th width="1%">Context</th>
			</tr>
			<?php foreach ($this->request->data['Swagger']['elements'] as $index => $element): ?>
				<tr>
					<td><?= $this->Form->input("Swagger.elements.{$index}.name", ['label' => false, 'class' => 'data-label', 'data-index' => $index]) ?></td>
					<td>
						<?= $this->Form->input("Swagger.elements.{$index}.business_term", ['type' => 'hidden', 'id' => "origTerm{$index}"]) ?>
						<?= $this->Form->input("Swagger.elements.{$index}.business_term", ['label' => false, 'class' => 'bt-select', 'data-index' => $index, 'type' => 'select']) ?>
					</td>
					<td class="view-context<?= $index ?>" style="white-space: nowrap"></td>
					<td class="xview-definition<?= $index ?>"></td>
				</tr>
			<?php endforeach ?>
		</table>
		<?= $this->Form->input('labelCache', ['type' => 'hidden']) ?>
		<?= $this->Form->input('idCache', ['type' => 'hidden']) ?>
		<?= $this->Form->submit() ?>
	<?= $this->Form->end() ?>
</div>
<div class="searchDialog" title="Search">
	<input type="text" id="searchAutocomplete">
	<div style="height: 200px">&nbsp;</div>
</div>
<script type="text/javascript">
	$(document).ready(function() {
		var loadingStatus = {};
		var labelCache = <?= empty($this->request->data['Swagger']['labelCache']) ? '{}' : $this->request->data['Swagger']['labelCache'] ?>;
		var idCache = <?= empty($this->request->data['Swagger']['idCache']) ? '{}' : $this->request->data['Swagger']['idCache'] ?>;
		var termSelect;

		$('.searchDialog').dialog({
			autoOpen: false,
			modal: true,
			buttons: {
				Cancel: function() {
					$(this).dialog('close');
				}
			}
		});

		$('#searchAutocomplete')
			.autocomplete({
				source: function( request, response ) {
					$.getJSON( "<?= $this->Html->url(['controller' => 'search', 'action' => 'autoCompleteTerm', true]) ?>", {
						q: request.term
					}, response );
				},
				search: function() {
					// custom minLength
					if ( this.value.length < 2 ) {
						return false;
					}
				},
				select: function(evt, selected) {
					termSelect(evt, selected);
				}
			})
			.autocomplete("instance")._renderItem = function( ul, item ) {
				return $( "<li>" )
				  .append( "<div>" + item.name.val + "<br>" + item.context.val + "</div>" )
				  .appendTo( ul );
			};

		$('.data-label').change(function() {
			var $this = $(this);
			var full = $this.val();
			var period = full.lastIndexOf('.');
			var label = full.substring(period + 1);
			$this.data('label', label);
			setOptions($this);
		}).change();

		$('.bt-select').change(function() {
			var $this = $(this);
			var selected = $this.val();
			var index = $this.data('index');
			$('.temp-view' + index).html('');
			if (!selected) {
				return;
			}

			if (selected === 'search') {
				$this.val('');
				$('#searchAutocomplete').val('');
				termSelect = function(evt, selected) {
					$('.searchDialog').dialog('close');
					var $input = $('#SwaggerElements' + index + 'Name');
					var label = $input.data('label');
					if (selected.item === undefined) {
						return false;
					}
					addToCache(label, selected.item);
					$('#origTerm' + index).val(selected.item.name.id);
					$('.data-label')
						.filter(function () {
							return $(this).data('label') == label;
						}).each(function() {
							setOptions($(this));
						});
				}
				$('.searchDialog').dialog('open');
			}

			if (idCache[selected] === undefined) {
				$('.view-context' + index).html('');
				$('.view-definition' + index).html('');
			} else {
				$('.view-context' + index).html(idCache[selected].context);
				$('.view-definition' + index).html(idCache[selected].definition);
			}
		});

		function setOptions($name) {
			var index = $name.data('index');
			var label = $name.data('label');
			var $select = $('#SwaggerElements' + index + 'BusinessTerm');
			var alreadySelected = $select.val();

			$select.html('');

			if (label == '') {
				return;
			}

			if (labelCache[label] === undefined) {
				$select.html('<option value="">Loading...<option>');
				loadLabel(label);
				return;
			}

			$select.append($('<option>', {value: '', text: ''}));

			var origTerm;
			if (alreadySelected) {
				origTerm = alreadySelected;
			} else {
				origTerm = $('#origTerm' + index).val();
			}

			var matched = false;
			for (var i in labelCache[label]) {
				var option = labelCache[label][i]
				var attributes = {value: option.id, text: option.name, title: option.title};
				if (option.id == origTerm) {
					matched = true;
					attributes.selected = 'selected';
				}
				$select.append($('<option>', attributes));
			}

			$select.append($('<option>', {value: 'search', text: 'Search...'}));

			if (!matched && labelCache[label].length > 0) {
				$select.val(labelCache[label][0].id); //default select first option
			}
			$select.change();
		}

		function loadLabel(label) {
			if (loadingStatus[label] !== undefined) {
				return;
			}
			loadingStatus[label] = true;
			$.post('<?= $this->Html->url(['action' => 'find_business_term']) ?>', {label: label}, function(data) {
				delete loadingStatus[label];
				if (!data instanceof Array) {
					return;
				}
				labelCache[label] = [];
				for (var i in data) {
					addToCache(label, data[i]);
				}
				$('.data-label')
					.filter(function () {
						return $(this).data('label') == label;
					}).each(function() {
						setOptions($(this));
					});
			});
		}

		function addToCache(label, data) {
			var title = '';
			var context = '';
			var definition = '';
			if (data.context !== undefined && data.context.val) {
				context = data.context.val
				title = context + ' - ';
			}
			if (data.definition !== undefined) {
				definition = data.definition.val
				title += definition;
			}
			var i = labelCache[label].length;
			labelCache[label][i] = {
				id: data.name.id,
				name: data.name.val,
				title: title,
				context: context,
				definition: definition
			};
			idCache[data.name.id] = labelCache[label][i];
			$('#SwaggerLabelCache').val(JSON.stringify(labelCache));
			$('#SwaggerIdCache').val(JSON.stringify(idCache));
		}
	})
</script>