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
					<li><a href="/people" class="deptLink">A-Z Listing</a></li>
					<?php
                        foreach($communities->communityReference as $c){
                            $cssClass = '';
                            if($c->resourceId == $community){
                                $cssClass = 'class="active"';
                            }
                            echo '<li><a '.$cssClass.' href="/people/dept?c='.$c->resourceId.'" class="deptLink">'.$c->name.'</a></li>';
                        }
                    ?>
				</ul>
			</div>
			<div id="rightCol">
			    <?php
                    foreach($domains as $d){
                        echo '<div class="people-list">'.
                            '<h4 class="deptHeader">'.$d['name'].'</h4>';
                        if(sizeof($d['steward'])>0){
                            $phone = '&nbsp;';
                            if(isset($d['steward']->phoneNumbers->phone)){
                                $phone = $d['steward']->phoneNumbers->phone[0]->number;
                            }
                            $deptTitle = 'Steward';
                            $cssClass = '';
                            echo '<div class="contactBox contactOrange">'.
                                '    <span class="contactName">'.$d['steward']->firstName.' '.$d['steward']->lastName.'</span>'.
                                '    <div class="contactNumber"><a href="tel:'.$phone.'">'.$phone.'</a></div>'.
                                '    <div class="contactEmail"><a href="mailto:'.$d['steward']->emailAddress.'">'.$d['steward']->emailAddress.'</a></div>'.
                                '    <span class="contactTitle">'.$deptTitle.'<div onmouseover="showTermDef(this)" onmouseout="hideTermDef()" data-definition="'.$stewardDef.'" class="info"><img src="/img/iconInfo.png"></div></span>'.
                                '</div>';
                        }
                        if(sizeof($d['custodian'])>0){
                            $phone = '&nbsp;';
                            if(isset($d['custodian']->phoneNumbers->phone)){
                                $phone = $d['custodian']->phoneNumbers->phone[0]->number;
                            }
                            $deptTitle = 'Custodian';
                            $cssClass = '';
                            echo '<div class="contactBox">'.
                                '    <span class="contactName">'.$d['custodian']->firstName.' '.$d['custodian']->lastName.'</span>'.
                                '    <div class="contactNumber"><a href="tel:'.$phone.'">'.$phone.'</a></div>'.
                                '    <div class="contactEmail"><a href="mailto:'.$d['custodian']->emailAddress.'">'.$d['custodian']->emailAddress.'</a></div>'.
                                '    <span class="contactTitle">'.$deptTitle.'<div onmouseover="showTermDef(this)" onmouseout="hideTermDef()" data-definition="'.$custodianDef.'" class="info"><img src="/img/iconInfo.png"></div></span>'.
                                '</div>';
                        }
                        echo '</div>';
                    }
                    /*foreach($domains->aaData[1]->Vocabularies as $v){
                        
                        if(sizeof($v->Role615829186613486a9d8261f7d1c0c014VOCSUFFIX)>0){
                            $userrid = $v->Role615829186613486a9d8261f7d1c0c014VOCSUFFIX[0]->userRole615829186613486a9d8261f7d1c0c014VOCSUFFIXrid;
                            $deptTitle = 'custodian';
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
                            $cssClass = 'contactOrange';
                            $name = $v->Role7865d2c3405a459db11edf061adec84bVOCSUFFIX[0]->userRole7865d2c3405a459db11edf061adec84bVOCSUFFIXfn.' '.$v->Role7865d2c3405a459db11edf061adec84bVOCSUFFIX[0]->userRole7865d2c3405a459db11edf061adec84bVOCSUFFIXln;
                            echo '<div class="contactBox '.$cssClass.'">'.
                                '    <span class="contactName">'.$name.'</span>'.
                                '    <div class="contactNumber"><a href="tel:'.$users[$userrid]['phone'].'">'.$users[$userrid]['phone'].'</a></div>'.
                                '    <div class="contactEmail"><a href="mailto:'.$users[$userrid]['email'].'">'.$users[$userrid]['email'].'</a></div>'.
                                '    <span class="contactTitle">'.$deptTitle.'</span>'.
                                '</div>';
                        }
                        echo '</div>';
                        
                    }*/
                ?>
			</div>
			<div class="clear"></div>
		</div>
	</div>
</div>