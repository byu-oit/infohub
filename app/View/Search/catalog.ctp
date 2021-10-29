<?php
	$this->Html->css('secondary', null, ['inline' => false]);
	$this->Html->css('search', null, ['inline' => false]);
?>
<script>
	$(document).ready(function() {
		$("#searchLink").addClass('active');
        loadCommunityData(null, 'catalogList0');
	});

	$(document).ready(listWidth);
	$(window).resize(listWidth);

	function listWidth() {
		$('.catalogChild').css('width', '100%').css('width', '-=11px');
		$('.grandChild').css('width', '100%').css('width', '-=11px');
		$('.greatGrandChild').css('width', '100%').css('width', '-=11px');
	}

    function loadCommunityData(community, listID){
        $.post("/search/loadCommunityData", { c:community })
            .done(function(data) {
                var objDomains = JSON.parse(data);
                var html = '';
                // create community elements
                for(i=0; i<objDomains[0].aaData[0].Subcommunities.length; i++){
                    var comm = objDomains[0].aaData[0].Subcommunities[i];
                    html += '<li class="catalogItem" id="c'+comm.subcommunityid+'">';
                    if(comm.hasNonMetaChildren=='true'){
                        html += '   <a href="#" class="hasChildren">'+comm.subcommunity+'</a>'+
                            '   <ul id="categoryList'+comm.subcommunityid+'" class="subList catalogChild">'+
                            '       <li><a href=""><img src="/img/dataLoading-sm.gif" alt="Loading..."></a></li>'+
                            '   </ul>';
                    }else{
                        html += '   <a href="#">'+comm.subcommunity+'</a>';
                    }
                    html += '</li>';
                }

                // create vocabulary elements
                if(objDomains.length>1){
                    for(i=0; i<objDomains[1].aaData[0].Vocabularies.length; i++){
                        var vocab = objDomains[1].aaData[0].Vocabularies[i];
                        html += '<li class="catalogItem">'+
                            '   <a class="vocab" href="/search/listTerms/'+vocab.vocabularyid+'">'+vocab.vocabulary+'</a>'+
                            '</li>';
                    }
                }

                // add click event to show/hide and load child data
                $('#'+listID).html(html).find('li a').not('.vocab').click(function (e) {
                    $(this).toggleClass('active');
                    e.preventDefault();

                    // load child communities and vocabularies if they haven't been loaded
                    if($(this).parent().find('li').length==1){
                        var cid = $(this).parent().attr('id').substring(1);
                        loadCommunityData(cid, 'categoryList'+cid);
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

<!-- Request list -->
<div id="searchBody" class="innerLower">
	<div id="searchTop">
		<h1 class="headerTab">Search Information</h1>
		<div class="clear"></div>
		<div id="stLower" class="whiteBox">
			<form action="#" onsubmit="document.location='/search/results/'+this.searchInput.value; return false;" method="post">
				<input id="searchInput" name="searchInput" type="text" class="inputShade" onkeyup="searchAutoComplete()" placeholder="Search keyword, topic, or phrase" maxlength="50" autocomplete="off"  />
				<?php echo $this->element('auto_complete'); ?>
				<input type="submit" value="Search" class="inputButton" />
			</form>
			<div class="clear"></div>
		</div>
	</div>

	<div id="searchMain">
		<h2 class="headerTab">Full Catalog</h2>
		<div class="clear"></div>
		<div id="smLower" class="whiteBox">
			<ul class="catalogParent" id="catalogList0">
                <a href=""><img src="/img/dataLoading-sm.gif" alt="Loading..."></a>
			</ul>
		</div>
	</div>
</div>
