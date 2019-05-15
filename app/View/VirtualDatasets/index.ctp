<?php
	$this->Html->css('secondary', null, ['inline' => false]);
	$this->Html->css('search', null, ['inline' => false]);
?>
<script>
	$(document).ready(function() {
		$("#searchLink").addClass('active');
        loadSpaceOrFolder('', 'catalogList0');
	});

	$(document).ready(listWidth);
	$(window).resize(listWidth);

	function listWidth() {
		$('.catalogChild').css('width', '100%').css('width', '-=11px');
		$('.grandChild').css('width', '100%').css('width', '-=11px');
		$('.greatGrandChild').css('width', '100%').css('width', '-=11px');
	}

    function loadSpaceOrFolder(unitId, listID, isFolder = false){
		var url = isFolder ? '/virtualDatasets/loadFolder/' : '/virtualDatasets/loadDremioSpace/';
        $.get(url+unitId)
            .done(function(data) {
                var data = JSON.parse(data);
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
				if (unitId == '') {
					for (i=0; i < data.length; i++) {
						var space = data[i];
						html += '<li class="catalogItem" id="'+space.spaceId+'">'+
						'	   <a href="#" class="hasChildren">'+space.spaceName.split(' > ').pop()+'</a>'+
						'	   <ul data-level="'+level+'" id="categoryList'+space.spaceId+'" class="subList catalogChild '+grandChildClass+'">'+
						'       	<li><a href=""><img src="/img/dataLoading-sm.gif" alt="Loading..."></a></li>'+
						'		</ul>'+
						'	</li>';
					}
				} else {
					for (i=0; i < data[0].subfolders.length; i++) {
						var folder = data[0].subfolders[i];
						html += '<li class="catalogItem" id="'+folder.subfolderId+'" isFolder="true">'+
						'	   <a href="#" class="hasChildren">'+folder.subfolderName.split(' > ').pop()+'</a>'+
						'	   <ul data-level="'+level+'" id="categoryList'+folder.subfolderId+'" class="subList catalogChild '+grandChildClass+'">'+
						'       	<li><a href=""><img src="/img/dataLoading-sm.gif" alt="Loading..."></a></li>'+
						'		</ul>'+
						'	</li>';
					}

					// create dataset elements
					if (data[0].datasets !== undefined) {
						for (i=0; i < data[0].datasets.length; i++) {
							var dataset = data[0].datasets[i];
							html += '<li class="catalogItem">'+
							'   	<a class="dataset" href="/virtualDatasets/view/'+dataset.datasetId+'">'+dataset.datasetName.split(' > ').pop()+'</a>'+
							'	</li>';
						}
					}
				}


                // add click event to show/hide and load child data
                $('#'+listID).html(html).find('li a').not('.dataset').click(function (e) {
                    $(this).toggleClass('active');
                    e.preventDefault();

                    // load child folders and datasets if they haven't been loaded
                    if ($(this).parent().find('li').length == 1) {
                        var thisUnitId = $(this).parent().attr('id');
						var thisUnitIsFolder = $(this).parent().attr('isFolder');
                        loadSpaceOrFolder(thisUnitId, 'categoryList'+thisUnitId, thisUnitIsFolder);
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
			<h2 class="headerTab">Recently Viewed Datasets</h2>
			<div class="clear"></div>
			<div id="smLower" class="whiteBox">
				<ul class="catalogParent">
					<?php foreach ($recent as $dataset): ?>
						<li class="catalogItem">
							<?= $this->Html->link($dataset['datasetName'], ['action' => 'view', $dataset['datasetId']]) ?>
						</li>
					<?php endforeach ?>
				</ul>
			</div>
		</div>
	<?php endif ?>

	<div id="searchMain" style="padding-top: 35px;">
		<h1 class="headerTab">Select Dataset</h2>
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
					'Update a Dataset',
					array_merge(['controller' => 'virtualDatasetAdmin', 'action' => 'syncDatasource']),
					['class' => 'btn-db-sync grow', 'id' => 'admin']) ?>
			</div>
		</div>
	<?php endif ?> -->
</div>
