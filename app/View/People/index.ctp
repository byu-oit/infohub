<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('people', null, array('inline' => false));
?>
<script>
	$(document).ready(menuSize);
	$(document).ready(mobMenu);
	$(window).resize(menuSize);
	$(window).resize(menuShow);

	$(document).ready(function() {
		$("#findLink").addClass('active');
		
		$(".deptLink").click(function() {
			$(".deptLink").removeClass('active');
			$(this).addClass("active");
			mobMenu();
		});

		$("#subMobMenu").click(function() {
			$('#leftCol ul').slideToggle();
		});
	});

	function menuSize() {
		if($(window).width() < 750) {
			$('#leftCol ul').css('width', '100%').css('width', '-=58px');
		}
		else {
			$('#leftCol ul').css('width', '100%');
		}
	}

	function menuShow() {
		if($(window).width() > 750) {
			$("ul.show").show();
			$("ul.mob").hide();
		}
	}

	function mobMenu() {
		if($(window).width() < 750) {
			$("ul.mob li").empty();
			$("ul.show li a.active").clone().appendTo("ul.mob li");
			$("#leftCol ul.show").hide();
			$("ul.mob").show();
		}
	}
</script>

<!-- Background image div -->
<div id="peopleBg" class="lectureBg">
</div>

<!-- Request list -->
<div id="peopleBody" class="innerLower">
	<div id="peopleTop">
		<h2 class="headerTab">Person Look-Up</h2>
		<div class="clear"></div>
		<div id="ptLower" class="whiteBox">
			<form action="">
				<input type="text" placeholder="First Name" class="inputShade">
				<input type="text" placeholder="Last Name" class="inputShade">
				<input type="submit" value="Search" class="inputButton">
			</form>
			<div class="clear"></div>
		</div>
	</div>
	<div id="peopleBottom">
		<h2 class="headerTab">Directory</h2>
		<div class="clear"></div>
		<div id="peopleMain" class="whiteBox">
			<div id="leftCol">
				<a id="subMobMenu" class="inner">&nbsp;</a>
				<ul class="mob"><li></li></ul>
				<ul class="show">
					<li><a class="deptLink">A-Z Listing</a></li>
					<li><a class="deptLink active">Academia</a></li>
					<li><a class="deptLink">Advancement</a></li>
					<li><a class="deptLink">Financial</a></li>
					<li><a class="deptLink">Human Resources</a></li>
					<li><a class="deptLink">International</a></li>
					<li><a class="deptLink">Master Data</a></li>
					<li><a class="deptLink">Research</a></li>
					<li><a class="deptLink">Student Life</a></li>
				</ul>
			</div>
			<div id="rightCol">
				<h4 class="deptHeader">Trustee</h4>
				<div class="contactBox contactOrange">
					<span class="contactName">Brad Gonzales</span>
					<div class="contactNumber"><a href="tel:8015959845">801.595.9845</a></div>
					<div class="contactEmail"><a href="mailto:bgonzales@byu.edu">bgonzales@byu.edu</a></div>
					<span class="contactTitle">Trustee</span>
				</div>
				<h4 class="deptHeader">Class Scheduling</h4>
				<div class="contactBox">
					<span class="contactName">Brad Gonzales</span>
					<div class="contactNumber"><a href="tel:8015959845">801.595.9845</a></div>
					<div class="contactEmail"><a href="mailto:bgonzales@byu.edu">bgonzales@byu.edu</a></div>
					<span class="contactTitle">Steward</span>
				</div>
				<div class="contactBox">
					<span class="contactName">Brad Gonzales</span>
					<div class="contactNumber"><a href="tel:8015959845">801.595.9845</a></div>
					<div class="contactEmail"><a href="mailto:bgonzales@byu.edu">bgonzales@byu.edu</a></div>
					<span class="contactTitle">Custodian</span>
				</div>
				<h4 class="deptHeader">Faculty Leadership</h4>
				<div class="contactBox">
					<span class="contactName">Brad Gonzales</span>
					<div class="contactNumber"><a href="tel:8015959845">801.595.9845</a></div>
					<div class="contactEmail"><a href="mailto:bgonzales@byu.edu">bgonzales@byu.edu</a></div>
					<span class="contactTitle">Steward</span>
				</div>
			</div>
			<div class="clear"></div>
		</div>
	</div>
</div>