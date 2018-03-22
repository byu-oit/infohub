<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('search', null, array('inline' => false));
?>

<script>
$(document).ready(function() {
	var tables = [<?php foreach ($schema->tables as $table) { echo '"'.$table->tableName.'",'; } ?> ""];
	$('#tableSearch').autocomplete({
		source: function(request, response) {
			var results = $.ui.autocomplete.filter(tables, request.term);
			response(results.slice(0, 15));
		},
		search: function() {
			if (this.value.length < 3) {
				$('.autoCompleteTables').hide();
				return false;
			}
		},
		response: function(evt, ui) {
			$('.results').html('');
			$('.autoCompleteTables').show();
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
	var m = false;
	$('#tableSearch').keypress(function(event) { return event.keyCode != 13; });
	$('#tableSearch').on({
		keyup: function(e) {
			if (e.which === 38) { // Up-arrow
				e.preventDefault();
				index--;
				if (index < 0) {
					index = $('.autoCompleteTables li').length - 1;
				}
				m = true;
			}
			else if (e.which === 40) { // Down-arrow
				e.preventDefault();
				if (index >= $('.autoCompleteTables li').length - 1) {
					index = 0;
				}
				else {
					index++;
				}
				m = true;
			} else if (e.which === 13) {
				if($('.autoCompleteTables li').hasClass('active')){
					window.location.href = window.location.origin+'/databases/view/<?=$schema->schemaName?>/'+$('.autoCompleteTables li.active').data('value');
				}
				else {
					$('#searchInput').parent().submit();
				}
				$('.autoCompleteTables').hide();
			} else {
				index = -1;
				m = false;
			}

			if (m) {
				$('.autoCompleteTables li.active').removeClass('active');
				$('.autoCompleteTables li').eq(index).addClass('active');
			}
		}
	});

	$('.results').on('click', 'li', function() {
		window.location.href = window.location.origin+'/databases/view/<?=$schema->schemaName?>/'+$(this).data('value');
	});
});

$(document).on( 'click', function ( e ) {
	if ( $( e.target ).closest('#tableSearch').length === 0 ) {
		$('.autoCompleteTables').hide();
	}
});
</script>

<style>
.ui-autocomplete {
	display: none !important;
}
.autoCompleteTables {
	width: 490px;
}
</style>

<!-- Background image div -->
<div id="searchBg" class="searchBg">
</div>

<div id="searchBody" class="innerLower">

	<div id="searchTop">
		<h1 class="headerTab">Search Tables</h1>
		<div class="clear"></div>
		<div id="stLower" class="whiteBox">
			<form action="#" onsubmit="document.location='/databases/view/<?=$schema->schemaName?>/'+this.searchInput.value; return false;" method="post">
				<input id="tableSearch" name="searchInput" type="text" class="inputShade" placeholder="Search table name" maxlength="50" autocomplete="off" style="width: 490px;" />
				<div class="autoCompleteTables">
					<ul class="results"></ul>
				</div>
			</form>
			<div class="clear"></div>
		</div>
	</div>

	<?php if (isset($recent)): ?>
		<div id="searchMain" style="padding-bottom: 35px;">
			<h2 class="headerTab">Recently Viewed</h2>
			<div class="clear"></div>
			<div id="smLower" class="whiteBox">
				<ul class="catalogParent" id="catalogList0" data-level="0">
					<?php foreach ($recent as $tableName):
						$schemaName = rtrim(substr($tableName, 0, strpos($tableName, '>'))); ?>
						<li class="catalogItem">
							<?= $this->Html->link($tableName, ['action' => 'view', $schemaName, $tableName]) ?>
						</li>
					<?php endforeach ?>
				</ul>
			</div>
		</div>
	<?php endif ?>

	<div id="searchMain">
		<h2 class="headerTab"><?=$schema->schemaName?></h2>
		<div class="clear"></div>
		<div id="smLower" class="whiteBox">
			<ul class="catalogParent">
				<?php if (empty($schema->tables)): ?>
					No tables found
				<?php else: ?>
					<ul>
						<?php foreach ($schema->tables as $table): ?>
							<li class="catalogItem">
								<?= $this->Html->link($table->tableName, ['action' => 'view', $schema->schemaName, $table->tableName]) ?>
							</li>
						<?php endforeach ?>
					</ul>
				<?php endif ?>
			</ul>
		</div>
	</div>

</div>
