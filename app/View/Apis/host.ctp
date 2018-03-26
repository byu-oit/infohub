<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('search', null, array('inline' => false));
?>

<script>
$(document).ready(function() {
	var apis = [<?php foreach ($community->vocabularyReferences->vocabularyReference as $api) { echo '"'.$api->name.'",'; } ?> ""];
	$('#apiSearch').autocomplete({
		source: function(request, response) {
			var results = $.ui.autocomplete.filter(apis, request.term);
			response(results.slice(0, 15));
		},
		search: function() {
			if (this.value.length < 3) {
				$('.autoCompleteApis').hide();
				return false;
			}
		},
		response: function(evt, ui) {
			$('.results').html('');
			$('.autoCompleteApis').show();
		}
	})
	.autocomplete('instance')._renderItem = function( ul, item ) {
		$('.results').show();
		return $('<li>')
			.data('value', item.value)
			.append(item.label)
			.appendTo($('.results'));
	};

	var index = -1;
	$('#apiSearch').keypress(function(event) { return event.keyCode != 13; });
	$('#apiSearch').on({
		keyup: function(e) {
			if (e.which === 38) { // Up-arrow
				e.preventDefault();
				index--;
				if (index < 0) {
					index = $('.autoCompleteApis li').length - 1;
				}
				m = true;
			}
			else if (e.which === 40) { // Down-arrow
				e.preventDefault();
				if (index >= $('.autoCompleteApis li').length - 1) {
					index = 0;
				}
				else {
					index++;
				}
				m = true;
			} else if (e.which === 13) {
				if($('.autoCompleteApis li').hasClass('active')){
					window.location.href = window.location.origin+'/apis/<?=$hostname?>'+$('.autoCompleteApis li.active').data('value');
				}
				else {
					$('#searchInput').parent().submit();
				}
				$('.autoCompleteApis').hide();
			} else {
				index = -1;
				m = false;
			}

			if (m) {
				$('.autoCompleteApis li.active').removeClass('active');
				$('.autoCompleteApis li').eq(index).addClass('active');
			}
		}
	});

	$('.results').on('click', 'li', function() {
		window.location.href = window.location.origin+'/apis/<?=$hostname?>'+$(this).data('value');
	});
});

$(document).on( 'click', function ( e ) {
	if ( $( e.target ).closest('#apiSearch').length === 0 ) {
		$('.autoCompleteApis').hide();
	}
});
</script>

<style>
.ui-autocomplete {
	display: none !important;
}
.autoCompleteApis {
	width: 490px;
}
</style>

<!-- Background image div -->
<div id="searchBg" class="searchBg">
</div>

<div id="searchBody" class="innerLower apis">

	<div id="searchTop">
		<h1 class="headerTab">Search APIs</h1>
		<div class="clear"></div>
		<div id="stLower" class="whiteBox">
			<form action="#" onsubmit="document.location='/apis/view/<?=$hostname?>/'+this.searchInput.value; return false;" method="post">
				<input id="apiSearch" name="searchInput" type="text" class="inputShade" placeholder="Search API name" maxlength="50" autocomplete="off" style="width: 490px;" />
				<div class="autoCompleteApis">
					<ul class="results"></ul>
				</div>
			</form>
			<div class="clear"></div>
		</div>
	</div>

	<?php if (isset($recent)): ?>
		<div id="searchMain" style="padding-bottom: 35px;">
			<h2 class="headerTab">Recently Viewed APIs</h2>
			<div class="clear"></div>
			<div id="smLower" class="whiteBox">
				<ul class="catalogParent" id="catalogList0" data-level="0">
					<?php foreach ($recent as $endpoint): ?>
						<li class="catalogItem">
							<?= $this->Html->link($endpoint, array_merge(['action' => 'view', 'hostname' => $hostname], explode('/', $endpoint))) ?>
						</li>
					<?php endforeach ?>
				</ul>
			</div>
		</div>
	<?php endif ?>

	<div id="searchMain">
		<h2 class="headerTab"><?=$community->name?></h2>
		<div class="clear"></div>
		<div id="smLower" class="whiteBox">
			<ul class="catalogParent">
				<?php if (empty($community->vocabularyReferences->vocabularyReference)): ?>
					No endpoints found
				<?php else: ?>
					<ul>
						<?php foreach ($community->vocabularyReferences->vocabularyReference as $endpoint): ?>
							<?php
								if ($endpoint->meta == 1) {
									continue;
								}
							?>
							<li class="catalogItem">
								<?= $this->Html->link($endpoint->name, array_merge(['action' => 'view', 'hostname' => $hostname], explode('/', $endpoint->name))) ?>
							</li>
						<?php endforeach ?>
					</ul>
				<?php endif ?>
			</ul>
		</div>
	</div>

</div>
