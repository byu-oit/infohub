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
		})
	});

	$(window).resize(resultsWidth);

	function resultsWidth() {
		if ($(window).width() > 680) {
			$('.resultContent').css('width', '100%').css('width', '-=200px');	
		}	
		else {
			$('.resultContent').css('width', '95%').css('width', '-=60px');	
		}
	}
		

</script>

<!-- Background image div -->
<div id="searchBg" class="deskBg">
</div>

<!-- Request list -->
<div id="searchBody" class="innerLower">

	<div id="requestForm">
		<h2 class="headerTab" >Request Form</h2>

		<div id="srLower" class="whiteBox">
			<h3 class="headerTab">Requester</h3>
			<div class="clear"></div>
			<div id="requesterInfo"></div>

			<h3 class="headerTab">Information Requested</h3>
			<div class="clear"></div>
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
						<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labare et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Lorem ipsum dolor sit amet, re magna aliqua.</p>
						
					</div>
				</div>
				<div class="irLower">
					<h5>Also included in this selection (check all that apply to your request).</h5>
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
					<h5>Additional details around information requested:</h5>
					<textarea name="" id=""  rows="10" class="inputShade"></textarea>
					<div class="clear"></div>
				</div>
				<a href="" class="quickLink grow"><img src="/img/iconStarBlue.gif" alt="Quick Link"></a>
			</div>

			<h3 class="headerTab">Description of intended use:</h3>
			<div class="clear"></div>
			<h3 class="headerTab">Access Rights (Who will be allowed to acces the information?)</h3>
			<div class="clear"></div>
			<h3 class="headerTab">Access Method (How access is expected to be granted and managed to ensure compliance.)</h3>
			<div class="clear"></div>
			<h3 class="headerTab">Impact on System (How often the information is expected to be updated.)</h3>
			<div class="clear"></div>
			

			
			<!-- <div class="resultItem classified">
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
				</div>
				<a href="" class="quickLink grow"><img src="/img/iconStarBlue.gif" alt="Quick Link"></a>
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
				<a href="" class="quickLink grow"><img src="/img/iconStarBlue.gif" alt="Quick Link"></a>
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
				<a href="" class="quickLink grow"><img src="/img/iconStarBlue.gif" alt="Quick Link"></a>
				<a class="detailsTab"><span class="detailsLess">Fewer</span><span class="detailsMore">More</span> Details</a>
			</div> -->
			<div class="clear"></div>
		</div>
	</div>
	<div class="clear"></div>
</div>

<!-- Quick links -->
<?php echo $this->element('quick_links'); ?>
