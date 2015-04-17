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

	<a href="/catalog" id="catalogLink" class="grow"><img src="/img/catalogLink2.png" alt="See full catealog"></a>
	<div class="clear"></div>

	<div id="searchResults">
		<h2 class="headerTab" >Results</h2>
		<div class="clear"></div>
		<div id="srLower" class="whiteBox">
			<div id="searchFilters">
				<label for="filerBy">Filter By:</label>
				<select name="filterBy" id="filerBy" class="inputShade">
					<option value="0" selected>All Results</option>
					<option value="academia">Academia</option>
					<option value="advancement">Advancement</option>
					<option value="financial">Financial</option>
					<option value="human Resources">Human Resources</option>
					<option value="international">International</option>
					<option value="master-data">Master Data</option>
					<option value="research">Research</option>
					<option value="student Life">Student Life</option>
				</select>
				<label for="filerBy">Sort By:</label>
				<select name="filterBy" id="filerBy" class="inputShade">
					<option value="0" selected>Date Added</option>
					<option value="1">This</option>
					<option value="2">That</option>
					<option value="3">The Other</option>
				</select>
			</div>
			
<?php
    for($i=1; $i<sizeof($terms)-1; $i++){
        $term = $terms[$i];
?>
			<div id="term<?php echo $i?>" class="resultItem highlyClassified">
			    <form action="submit">
                    <h4><?php echo $term[2]; ?></h4>
                    <h5 class="blueText"><?php echo $term[14].'/'.$term[16]; ?></h5>
                    <div class="resultContent">
                        <ul>
                            <li><span class="listLabel">Data Steward:&nbsp;</span><?php echo $term[8].' '.$term[9]; ?></li>
                            <li><span class="listLabel">Date Created:&nbsp;</span><?php echo $term[0]; ?></li>
                            <li><span class="listLabel">Classification: </span><span class="redText">Highly Classified</span></li>
                        </ul>
                        <div class="resultBody">
                            <p><?php echo stripslashes(strip_tags($term[4])); ?></p>
                            <h5>Also included in this selection (check all that apply to your request).</h5>
                            <img class="resultBodyLoading" src="/img/dataLoading.gif" alt="Loading...">
                            <div class="checkBoxes"></div>
                            <div class="clear"></div>
                        </div>
                    </div>
                    <a href="" class="addQuickLink grow"><img src="/img/iconStarBlue.gif" alt="Quick Link"></a>
                    <a href="" class="requestAccess grow">Request Access</a>
                    <a class="detailsTab" data-rid="<?php echo $term[17]; ?>"><span class="detailsLess">Fewer</span><span class="detailsMore">More</span>&nbsp;Details</a>
				</form>
			</div>
<?php
    }
            ?>
			<!--
			<div class="resultItem highlyClassified">
				<h4>Definition Title</h4>
				<h5 class="blueText">Acedemic/Lorem/Lorem</h5>
				<div class="resultContent">
					<ul>
						<li><span class="listLabel">Data Steward:&nbsp;</span>Julie Emerson</li>
						<li><span class="listLabel">Date Created:&nbsp;</span>9/18/14</li>
						<li><span class="listLabel">DataType:&nbsp;</span>Report</li>
						<li><span class="listLabel">Expiration Date:&nbsp;</span>1/15/16</li>
						<li><span class="listLabel">Classification: </span><span class="redText">Highly Classified</span></li>
					</ul>
					<div class="resultBody">
						<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labare et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Lorem ipsum dolor sit amet, re magna aliqua.Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.Duis aute irure dolor perspiciatis. Sed do eiusmod tempor incididunt ut labare et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco</p>
						<h5>Also included in this selection (check all that apply to your request).</h5>
						<form action="submit">
							<div class="checkCol">
								<input type="checkbox" name="american-leadership">
								<label for="american-leadership">American Leadership</label><br>
								<input type="checkbox" name="curriculum-management">
								<label for="curriculum-management">Curriculum Management</label>
							</div>
							<div class="checkCol">
								<input type="checkbox" name="ces-admissions">
								<label for="ces-admissions">CES Admissions</label><br>
								<input type="checkbox" name="faculty-compensation">
								<label for="faculty-compensation">Faculty Compensation</label>
							</div>
							<div class="checkCol">
								<input type="checkbox" name="class-scheduling">
								<label for="class-scheduling">Class Scheduling</label><br>
								<input type="checkbox" name="faculty-hiring">
								<label for="faculty-hiring">Faculty Hiring</label>
							</div>
							<div class="checkCol">
								<input type="checkbox" name="ci-rating">
								<label for="ci-rating">Course and Instructor Rating</label><br>
								<input type="checkbox" name="faculty-performance">
								<label for="faculty-performance">Faculty Performance</label>
							</div>
							<div class="clear"></div>
						</form>
					</div>
				</div>
				<a href="" class="addQuickLink grow"><img src="/img/iconStarBlue.gif" alt="Quick Link"></a>
				<a href="" class="requestAccess grow">Request Access</a>
				<a class="detailsTab"><span class="detailsLess">Fewer</span><span class="detailsMore">More</span>&nbsp;Details</a>
			</div>
			<div class="resultItem classified">
				<h4>Definition Title</h4>
				<h5   class="greenText">Financial/Lorem/Lorem</h5>
				<div class="resultContent">
					<ul>
						<li><span class="listLabel">Data Steward:&nbsp;</span>Julie Emerson</li>
						<li><span class="listLabel">Date Created:&nbsp;</span>9/18/14</li>
						<li><span class="listLabel">DataType:&nbsp;</span>Report</li>
						<li><span class="listLabel">Expiration Date:&nbsp;</span>1/15/16</li>
						<li><span class="listLabel">Classification: </span><span class="orangeText">Classified</span></li>
					</ul>
					<div class="resultBody">
						<img class="resultBodyLoading" src="/img/dataLoading.gif" alt="Loading...">
					</div>
				</div>
				<a href="" class="addQuickLink grow"><img src="/img/iconStarBlue.gif" alt="Quick Link"></a>
				<a href="" class="requestAccess grow">Request Access</a>
				<a class="detailsTab"><span class="detailsLess">Fewer</span><span class="detailsMore">More</span> Details</a>
			</div>
			<div class="resultItem public">
				<h4>Definition Title</h4>
				<h5 class="blueText">Acedemic/Lorem/Lorem</h5>
				<div class="resultContent">
					<ul>
						<li><span class="listLabel">Data Steward:&nbsp;</span>Julie Emerson</li>
						<li><span class="listLabel">Date Created:&nbsp;</span>9/18/14</li>
						<li><span class="listLabel">DataType:&nbsp;</span>Report</li>
						<li><span class="listLabel">Expiration Date:&nbsp;</span>1/15/16</li>
						<li><span class="listLabel">Classification: </span><span class="greenText">Public</span></li>
					</ul>
				</div>
				<a href="" class="addQuickLink grow ql-added"><img src="/img/iconStarOrange.gif" alt="Quick Link"></a>
				<a class="detailsTab"><span class="detailsLess">Fewer</span><span class="detailsMore">More</span> Details</a>
			</div>
			<div class="resultItem internal">
				<h4>Definition Title</h4>
				<h5 class="orangeText">Advancement/Lorem/Lorem</h5>
				<div class="resultContent">
					<ul>
						<li><span class="listLabel">Data Steward:&nbsp;</span>Julie Emerson</li>
						<li><span class="listLabel">Date Created:&nbsp;</span>9/18/14</li>
						<li><span class="listLabel">DataType:&nbsp;</span>Report</li>
						<li><span class="listLabel">Expiration Date:&nbsp;</span>1/15/16</li>
						<li><span class="listLabel">Classification: </span><span class="blueText">Internal</span></li>
					</ul>
				</div>
				<a href="" class="addQuickLink grow"><img src="/img/iconStarBlue.gif" alt="Quick Link"></a>
				<a href="" class="requestAccess grow">Request Access</a>
				<a class="detailsTab"><span class="detailsLess">Fewer</span><span class="detailsMore">More</span> Details</a>
			</div>-->
			<div class="clear"></div>
		</div>
	</div>
</div>

<!-- Quick links -->
<?php echo $this->element('quick_links'); ?>
