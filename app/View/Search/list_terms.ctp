<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('search', null, array('inline' => false));
?>
<script>
	$(document).ready(function() {
		$("#searchLink").addClass('active');
		resultsWidth();

		$('.detailsTab').click(function() {
			$(this).siblings('.resultContent').children('.resultBody').slideToggle();
			$(this).toggleClass('active');
            
            if($(this).parent().find('.checkBoxes').html() == ''){
                var thisElem = $(this);
                var rid = $(this).attr('data-rid');
                $.get( "/search/getFullVocab", { rid: rid } )
                    .done(function( data ) {
                        thisElem.parent().find('.resultBodyLoading').hide()
                        thisElem.parent().find('.checkBoxes').html(data);
                });
            }
		})
	});

	$(window).resize(resultsWidth);

	function resultsWidth() {
		if ($(window).width() > 680) {
			//$('.resultContent').css('width', '100%').css('width', '-=200px');	
		}	
		else {
			//$('.resultContent').css('width', '95%').css('width', '-=60px');	
		}
	}
		

</script>

<!-- Background image div -->
<div id="searchBg" class="deskBg">
</div>

<!-- Request list -->
<div id="searchBody" class="innerLower">
	<div id="searchTop">
		<h1 class="headerTab" >Search Information</h1>
		<div class="clear"></div>
		<div id="stLower" class="whiteBox">
			<form action="#" onsubmit="document.location='/search/results/'+this.searchInput.value; return false;" method="post">
				<input id="searchInput" type="text" class="inputShade" onkeyup="searchAutoComplete()" value="<?php echo $searchInput ?>" placeholder="Search keyword, topic, or phrase" maxlength="50" autocomplete="off" >
				<?php echo $this->element('auto_complete'); ?>
				<input type="submit" value="Search" class="inputButton">
			</form>
			<div class="clear"></div>
		</div>
	</div>

	<a href="/search/catalog" id="catalogLink" class="grow"><img src="/img/catalogLink2.png" alt="See full catealog"></a>
	<div class="clear"></div>

	<div id="searchResults">
		<h2 class="headerTab" >Results</h2>
		<div class="clear"></div>
		<div id="srLower" class="whiteBox">
<?php
    if(is_array($communities)){
?>
			<div id="searchFilters">
				<label for="filerBy">Filter By:</label>
				<select name="filterBy" id="filerBy" class="inputShade" onchange="document.location='/search/results/<?php echo $searchInput ?>?s=<?php echo $pageNum ?>&f='+this.value">
				    <option value="">All</option>
<?php
        for($i=0; $i<sizeof($communities->communityReference); $i++){
            $community = $communities->communityReference[$i];
            $selected = '';
            if($filter == $community->resourceId){
                $selected = 'selected';
            }
            echo '<option value="'.$community->resourceId.'" '.$selected.'>'.$community->name.'</option>';
        }
?>
				</select>
				<label for="filerBy">Sort By:</label>
				<select name="filterBy" id="filerBy" class="inputShade" onchange="document.location='/search/results/<?php echo $searchInput ?>?s='+this.value+'&f=<?php echo $filter ?>'">
					<option value="0" <?php if($sort==0) echo 'selected'; ?>>Alphabetical</option>
					<option value="1" <?php if($sort==1) echo 'selected'; ?>>Date Added</option>
					<option value="2" <?php if($sort==2) echo 'selected'; ?>>Classification</option>
				</select>
			</div>
			
<?php
    }

    if(sizeof($terms->aaData)==0){
        echo '<h1>No results found.</h1><h3>Please try a different search term.</h3>';
    }else{
        for($i=0; $i<sizeof($terms->aaData); $i++){
            $term = $terms->aaData[$i];
            $createdDate = $term->createdOn/1000;
            $createdDate = date('m/d/Y', $createdDate);
?>
			<div id="term<?php echo $term->termrid; ?>" class="resultItem highlyClassified">
			    <form action="submit">
                    <h4><?php echo $term->termsignifier; ?></h4>
                    <h5 class="blueText"><?php echo $term->communityname.'/'.$term->domainname; ?></h5>
                    <div class="resultContent">
                        <ul>
                           <?php
                                if(sizeof($term->Role00000000000000000000000000005016)>0){
                                    $stewardName = $term->Role00000000000000000000000000005016[0]->userRole00000000000000000000000000005016fn.' '.$term->Role00000000000000000000000000005016[0]->userRole00000000000000000000000000005016fn;
                            ?>
                            <li><span class="listLabel">Data Steward:&nbsp;</span><?php echo $stewardName; ?></li>
                            <?php
                                }
                            ?>
                            <li><span class="listLabel">Date Created:&nbsp;</span><?php echo $createdDate; ?></li>
                            <li><span class="listLabel">Classification: </span><span class="redText">Highly Classified</span></li>
                        </ul>
                        <div class="resultBody">
                            <p><?php echo stripslashes(strip_tags($term->Attr00000000000000000000000000000202longExpr)); ?></p>
                            <h5>Also included in this selection (check all that apply to your request).</h5>
                            <img class="resultBodyLoading" src="/img/dataLoading.gif" alt="Loading...">
                            <div class="checkBoxes"></div>
                            <div class="clear"></div>
                        </div>
                    </div>
                    <a href="javascript:addQL('<?php echo $term->termsignifier; ?>', '<?php echo $term->termrid; ?>')" class="addQuickLink grow">
                    <?php
                        if($term->saved == '1'){
                            echo '<img src="/img/iconStarOrange.gif" alt="Quick Link">';
                        }else{
                            echo '<img src="/img/iconStarBlue.gif" alt="Quick Link">';
                        }
                    ?>
                            
                    </a>
                    <a href="/search/request" class="requestAccess grow">Request Access</a>
                    <a class="detailsTab" data-rid="<?php echo $term->domainrid; ?>"><span class="detailsLess">Fewer</span><span class="detailsMore">More</span>&nbsp;Details</a>
				</form>
			</div>
<?php
        }
    }
?>
			<div class="clear"></div>
			<?php
                if($totalPages>1){
                    echo '<ul class="page-nav">';
                    for($i=0; $i<$totalPages; $i++){
                        $cssClass = (($i+1) == $pageNum)?'class="active"':'';
                        echo '<li '.$cssClass.'><a href="/search/listTerms/'.$domain.'/'.($i+1).'/?s='.$sort.'&f='.$filter.'">'.($i+1).'</a></li>';   
                    }
                    echo '</ul>';
                }
            ?>
            <div class="clear"></div>
		</div>
	</div>
</div>

<!-- Quick links -->
<?php echo $this->element('quick_links'); ?>
