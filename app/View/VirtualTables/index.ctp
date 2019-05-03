<?php
	$this->Html->css('secondary', null, ['inline' => false]);
	$this->Html->css('search', null, ['inline' => false]);
?>
<script>
	$(document).ready(function() {
		$("#searchLink").addClass('active');
        loadSpaceData('', 'catalogList0');
	});

	$(document).ready(listWidth);
	$(window).resize(listWidth);

	function listWidth() {
		$('.catalogChild').css('width', '100%').css('width', '-=11px');
		$('.grandChild').css('width', '100%').css('width', '-=11px');
		$('.greatGrandChild').css('width', '100%').css('width', '-=11px');
	}

    function loadSpaceData(spaceId, listID){
        $.get("/virtualTables/loadDremioSpace/"+spaceId)
            .done(function(data) {
                var spaces = JSON.parse(data);
                var html = '';
                var level = 0;
                var grandChildClass = '';
                if($('#'+listID).size()){
                    level = parseInt($('#'+listID).attr('data-level'))+1;
                }
                if(level > 1){
                    grandChildClass = 'grandChild';
                }
                // create space elements
				if (spaceId == '') {
					for (i=0; i < spaces.length; i++) {
						var space = spaces[i];
						html += '<li class="catalogItem" id="'+space.spaceId+'">'+
						'	   <a href="#" class="hasChildren">'+space.spaceName+'</a>'+
						'	   <ul data-level="'+level+'" id="categoryList'+space.spaceId+'" class="subList catalogChild '+grandChildClass+'">'+
						'       	<li><a href=""><img src="/img/dataLoading-sm.gif" alt="Loading..."></a></li>'+
						'		</ul>'+
						'	</li>';
					}
				} else {
					for (i=0; i < spaces[0].subspaces.length; i++) {
						var space = spaces[0].subspaces[i];
						html += '<li class="catalogItem" id="'+space.subspaceId+'">'+
						'	   <a href="#" class="hasChildren">'+space.subspaceName+'</a>'+
						'	   <ul data-level="'+level+'" id="categoryList'+space.subspaceId+'" class="subList catalogChild '+grandChildClass+'">'+
						'       	<li><a href=""><img src="/img/dataLoading-sm.gif" alt="Loading..."></a></li>'+
						'		</ul>'+
						'	</li>';
					}

					// create table elements
					if (spaces[0].tables !== undefined) {
						for (i=0; i < spaces[0].tables.length; i++) {
							var table = spaces[0].tables[i];
							html += '<li class="catalogItem">'+
							'   	<a class="table" href="/virtualTables/view/'+table.tableId+'">'+table.tableName+'</a>'+
							'	</li>';
						}
					}
				}


                // add click event to show/hide and load child data
                $('#'+listID).html(html).find('li a').not('.table').click(function (e) {
                    $(this).toggleClass('active');
                    e.preventDefault();

                    // load child spaces and tables if they haven't been loaded
                    if ($(this).parent().find('li').length == 1) {
                        var thisSpaceId = $(this).parent().attr('id');
                        loadSpaceData(thisSpaceId, 'categoryList'+thisSpaceId);
                    }

                    var ullist = $(this).parent().children('ul:first');
                    ullist.slideToggle();
                    listWidth();
                });

        });
    }
</script>

<!-- Background image div -->
<div id="searchBg" class="searchBg">
</div>

<div id="searchBody" class="innerLower">

	<?php if (isset($recent)): ?>
		<div id="searchMain" style="padding-top: 35px;">
			<h2 class="headerTab">Recently Viewed Tables</h2>
			<div class="clear"></div>
			<div id="smLower" class="whiteBox">
				<ul class="catalogParent">
					<?php foreach ($recent as $table): ?>
						<li class="catalogItem">
							<?= $this->Html->link($table['tableName'], ['action' => 'view', $table['tableId']]) ?>
						</li>
					<?php endforeach ?>
				</ul>
			</div>
		</div>
	<?php endif ?>

	<div id="searchMain" style="padding-top: 35px;">
		<h1 class="headerTab">Select Table</h2>
		<div class="clear"></div>
		<div id="smLower" class="whiteBox">
			<ul class="catalogParent" id="catalogList0" data-level="0">
                <a href=""><img src="/img/dataLoading-sm.gif" alt="Loading..."></a>
			</ul>
		</div>
	</div>
	<!-- <?php if ($matchAuthorized): ?>
		<div style="padding-top: 35px;">
			<div style="float: right">
				<?= $this->Html->link(
					'Update a Table',
					array_merge(['controller' => 'virtualTableAdmin', 'action' => 'syncDatasource']),
					['class' => 'btn-db-sync grow', 'id' => 'admin']) ?>
			</div>
		</div>
	<?php endif ?> -->
</div>
