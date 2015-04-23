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
			<form action="/people/lookup" method="post">
				<input type="text" placeholder="First Name" class="inputShade" name="fname" maxlength="50">
				<input type="text" placeholder="Last Name" class="inputShade" name="lname" maxlength="50">
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
					<li><a href="/people/fulllist" class="deptLink">A-Z Listing</a></li>
					<?php
                        foreach($communities->communityReference as $c){
                            $cssClass = '';
                            if($c->resourceId == $community){
                                $cssClass = 'class="active"';
                            }
                            echo '<li><a '.$cssClass.' href="/people?c='.$c->resourceId.'" class="deptLink">'.$c->name.'</a></li>';
                        }
                    ?>
				</ul>
			</div>
			<div id="rightCol">
			    <?php
                    foreach($domains->aaData[1]->Vocabularies as $v){
                        echo '<div class="people-list">'.
                            '<h4 class="deptHeader">'.$v->vocabulary.'</h4>';
                        
                        if(sizeof($v->Role734327ec9eaa41329fea1a4f2b9568ddVOCSUFFIX)>0){
                            $userrid = $v->Role734327ec9eaa41329fea1a4f2b9568ddVOCSUFFIX[0]->userRole734327ec9eaa41329fea1a4f2b9568ddVOCSUFFIXrid;
                            $deptTitle = 'Trustee';
                            $cssClass = 'contactOrange';
                            $name = $v->Role734327ec9eaa41329fea1a4f2b9568ddVOCSUFFIX[0]->userRole734327ec9eaa41329fea1a4f2b9568ddVOCSUFFIXfn.' '.$v->Role734327ec9eaa41329fea1a4f2b9568ddVOCSUFFIX[0]->userRole734327ec9eaa41329fea1a4f2b9568ddVOCSUFFIXln;
                            echo '<div class="contactBox '.$cssClass.'">'.
                                '    <span class="contactName">'.$name.'</span>'.
                                '    <div class="contactNumber"><a href="tel:'.$users[$userrid]['phone'].'">'.$users[$userrid]['phone'].'</a></div>'.
                                '    <div class="contactEmail"><a href="mailto:'.$users[$userrid]['email'].'">'.$users[$userrid]['email'].'</a></div>'.
                                '    <span class="contactTitle">'.$deptTitle.'</span>'.
                                '</div>';
                        }
                        if(sizeof($v->Role615829186613486a9d8261f7d1c0c014VOCSUFFIX)>0){
                            $userrid = $v->Role615829186613486a9d8261f7d1c0c014VOCSUFFIX[0]->userRole615829186613486a9d8261f7d1c0c014VOCSUFFIXrid;
                            $deptTitle = 'Steward';
                            $cssClass = '';
                            $name = $v->Role615829186613486a9d8261f7d1c0c014VOCSUFFIX[0]->userRole615829186613486a9d8261f7d1c0c014VOCSUFFIXfn.' '.$v->Role615829186613486a9d8261f7d1c0c014VOCSUFFIX[0]->userRole615829186613486a9d8261f7d1c0c014VOCSUFFIXln;
                            echo '<div class="contactBox '.$cssClass.'">'.
                                '    <span class="contactName">'.$name.'</span>'.
                                '    <div class="contactNumber"><a href="tel:'.$users[$userrid]['phone'].'">'.$users[$userrid]['phone'].'</a></div>'.
                                '    <div class="contactEmail"><a href="mailto:'.$users[$userrid]['email'].'">'.$users[$userrid]['email'].'</a></div>'.
                                '    <span class="contactTitle">'.$deptTitle.'</span>'.
                                '</div>';
                        }
                        if(sizeof($v->Role7865d2c3405a459db11edf061adec84bVOCSUFFIX)>0){
                            $userrid = $v->Role7865d2c3405a459db11edf061adec84bVOCSUFFIX[0]->userRole7865d2c3405a459db11edf061adec84bVOCSUFFIXrid;
                            $deptTitle = 'Custodian';
                            $cssClass = '';
                            $name = $v->Role7865d2c3405a459db11edf061adec84bVOCSUFFIX[0]->userRole7865d2c3405a459db11edf061adec84bVOCSUFFIXfn.' '.$v->Role7865d2c3405a459db11edf061adec84bVOCSUFFIX[0]->userRole7865d2c3405a459db11edf061adec84bVOCSUFFIXln;
                            echo '<div class="contactBox '.$cssClass.'">'.
                                '    <span class="contactName">'.$name.'</span>'.
                                '    <div class="contactNumber"><a href="tel:'.$users[$userrid]['phone'].'">'.$users[$userrid]['phone'].'</a></div>'.
                                '    <div class="contactEmail"><a href="mailto:'.$users[$userrid]['email'].'">'.$users[$userrid]['email'].'</a></div>'.
                                '    <span class="contactTitle">'.$deptTitle.'</span>'.
                                '</div>';
                        }
                        echo '</div>';
                        
                    }
                ?>
			</div>
			<div class="clear"></div>
		</div>
	</div>
</div>