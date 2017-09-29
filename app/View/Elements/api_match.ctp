<div class="searchDialog" title="Search">
	<input type="text" id="searchAutocomplete">
	<div style="height: 200px">&nbsp;</div>
</div>
<script type="text/javascript">
	$(document).ready(function() {
		var loadingStatus = {};
		var labelCache = <?= empty($this->request->data['Api']['labelCache']) ? '{}' : $this->request->data['Api']['labelCache'] ?>;
		var idCache = <?= empty($this->request->data['Api']['idCache']) ? '{}' : $this->request->data['Api']['idCache'] ?>;
		var termSelect;
		$('#apiForm')
			.append($('<input>', {type: 'hidden', name: 'data[Api][labelCache]', id: 'ApiLabelCache', val: JSON.stringify(labelCache)}))
			.append($('<input>', {type: 'hidden', name: 'data[Api][idCache]', id: 'ApiIdCache', val: JSON.stringify(idCache)}));

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
					$.getJSON("/search/autoCompleteTerm/1", {
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
					var $input = $('#ApiElements' + index + 'Name');
					var label = $input.data('label');
					if (selected.item === undefined) {
						return false;
					}
					addToCache(label, selected.item);
					$('#ApiElements' + index + 'BusinessTerm').data('origTerm', selected.item.name.id);
					$('.data-label')
						.filter(function () {
							return $(this).data('label') == label;
						}).each(function() {
							setOptions($(this));
						});
				}
				$('.searchDialog').dialog({
					height: 450,
					width: 350
				});
				$('.searchDialog').dialog('open');
			}

			if (idCache[selected] === undefined) {
				$('.view-context' + index).html('');
				$('#view-definition' + index).html('');
			} else {
				$('.view-context' + index).html(idCache[selected].context);

				if (idCache[selected].definition !== undefined) {
					if (idCache[selected].definition.length > 70) {
						var truncated = idCache[selected].definition.substring(0, 70);
						$('#view-definition' + index).html(
							'<span class="truncated">'+
								truncated+
							'... <a href="javascript:toggleDefinition('+index+')">See More</a></span>'+

							'<span class="full">'+
								idCache[selected].definition+
							' <a href="javascript:toggleDefinition('+index+')">See Less</a></span>'
						);
					} else {
						$('#view-definition' + index).html(idCache[selected].definition);
					}
				}
			}
		});

		$('.data-label').change(function() {
			var $this = $(this);
			var full = $this.val();
			var period = full.lastIndexOf('.');
			var label = full.substring(period + 1);
			$this.data('label', label);

			if ($this.data('preLinked')) {
				$this.data('preLinked', false);
				labelCache[label] = [];
				labelCache[label][0] = {
					id: $this.data('origId'),
					name: $this.data('origName'),
					context: $this.data('origContext'),
					definition: $this.data('origDef')
				};
				idCache[$this.data('origId')] = labelCache[label][0];
				$('#ApiLabelCache').val(JSON.stringify(labelCache));
				$('#ApiIdCache').val(JSON.stringify(idCache));
			}
			setOptions($this);
		}).change();

		function setOptions($name) {
			var index = $name.data('index');
			var label = $name.data('label');
			var $select = $('#ApiElements' + index + 'BusinessTerm');
			var alreadySelected = $select.val();

			$select.html('');

			if (label == '') {
				return;
			}

			if (labelCache[label] === undefined) {
				$select.html('<option value="">Loading...</option>');
				loadLabel(label);
				return;
			}

			$select.append($('<option>', {value: '', text: ''}));

			var origTerm;
			if (alreadySelected) {
				origTerm = alreadySelected;
			} else {
				origTerm = $('#ApiElements' + index + 'BusinessTerm').data('origTerm');
			}

			var matched = false;
			for (var i in labelCache[label]) {
				var option = labelCache[label][i]
				var attributes = {value: option.id, text: option.name};
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
			$.post('/swagger/find_business_term', {label: label}, function(data) {
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
			var context = '';
			if (data.context !== undefined && data.context.val) {
				context = data.context.val
			}
			if (data.definition === undefined) {
				data.definition = {
					val: '(No definition exists)'
				};
			}
			var i = labelCache[label].length;
			labelCache[label][i] = {
				id: data.name.id,
				name: data.name.val,
				context: context,
				definition: data.definition.val
			};
			idCache[data.name.id] = labelCache[label][i];
			$('#ApiLabelCache').val(JSON.stringify(labelCache));
			$('#ApiIdCache').val(JSON.stringify(idCache));
		}
	})

	function toggleDefinition(index) {
		$('#view-definition'+index).toggleClass('expanded');
	}
</script>
