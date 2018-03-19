<script type="text/javascript">
	$(document).ready(function() {
		var loadingStatus = {};

		$('.bt-search')
			.each(function() {
				$(this).autocomplete({
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
					response: function( event, ui ) {
						ui.content.push({
							newTab: true
						});
					},
					select: function(evt, selected) {
						if (selected.item === undefined) {
							return false;
						}
						if (selected.item.newTab) {
							window.open(window.location.origin+'/search/results/'+$(this).val(), '_blank');
							return;
						}
						var index = $(this).data('index');

						updateTable(index, selected.item, false);
					}
				})
				.autocomplete("instance")._renderItem = function( ul, item ) {
					if (item.newTab === undefined) {
						return $( "<li>" )
							.append( "<div>" + item.name.val + "<br>" + item.context.val + "</div>" )
							.appendTo( ul );
					}

					return $( "<li>" )
						.addClass("new-tab")
						.append( "<div>Search for \""+$(this)[0].term+"\" in new tab</div>" )
						.appendTo( ul );
				};
			});

		$('.edit-opt').click(function() {
			var index = $(this).data('index');
			$('#tr' + index).removeClass('automatic-match');

			$('#ApiElements' + index + 'SearchCell').toggleClass('display-search');
			$('#ApiElements' + index + 'SearchCell').find('.bt-search').focus();

			$('#ApiElements' + index + 'BusinessTerm').val('');
			$('.view-context' + index).html('');
			$('#view-definition' + index).html('');
		});

		$('.new-check').change(function() {
			var index = $(this).data('index');
			$('#tr'+index).toggleClass('display-new-bt');
		});

		$('.data-label')
			.filter(function() {
				return $(this).data('preLinked');
			})
			.each(function() {
				var index = $(this).data('index');
				var context = $(this).data('orig-context');
				$('.view-context' + index).html(context);

				var def = $(this).data('orig-def');
				insertDefinition(stripTags(def), index);
			});
		$('.data-label')
			.filter(function() {
				return !$(this).data('preLinked');
			})
			.each(function() {
				var $this = $(this);
				var full = $this.val();
				var period = full.lastIndexOf('.');
				var label = full.substring(period + 1);
				$this.data('label', label);

				$this.closest('tr').find('.term-wrapper').removeClass('display-search').addClass('display-loading');
				loadLabel(label);
			});

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

				$('.data-label')
					.filter(function () {
						return $(this).data('label') == label;
					}).each(function() {
						$(this).closest('tr').find('.term-wrapper').removeClass('display-loading').addClass('display-search');
						if (data.length != 0) {
							updateTable($(this).data('index'), data[0], true);
						}
					});
			});
		}

		function updateTable(index, selected, automatic) {
			$('#ApiElements' + index + 'BusinessTerm').val(selected.name.id);

			var $search = $('#ApiElements' + index + 'SearchCell');
			$search.find('.selected-term').find('.term-name').html(selected.name.val);
			$search.toggleClass('display-search');

			$('.view-context' + index).html(selected.context.val);

			if (!selected.hasOwnProperty('definition')) {
				$('#view-definition' + index).html('');
			} else {
				insertDefinition(stripTags(selected.definition.val), index);
			}

			if (automatic) {
				$('#tr' + index).addClass('automatic-match');
			}
		}

		function insertDefinition(text, index) {
			if (text !== undefined) {
				if (text.length > 70) {
					var truncated = text.substring(0, 70);
					$('#view-definition' + index).html(
						'<span class="truncated">'+
							truncated+
						'... <a href="javascript:toggleDefinition('+index+')">See More</a></span>'+

						'<span class="full">'+
							text+
						' <a href="javascript:toggleDefinition('+index+')">See Less</a></span>'
					);
				} else {
					$('#view-definition' + index).html(text);
				}
			}
		}
	});

	function toggleDefinition(index) {
		$('#view-definition'+index).toggleClass('expanded');
	}
	// Collibra puts html tags into business terms' definitions; that causes problems on this page
	function stripTags(html) {
		var tmp = document.createElement("div");
		tmp.innerHTML = html;
		return tmp.textContent || tmp.innerText || "";
	}
</script>
