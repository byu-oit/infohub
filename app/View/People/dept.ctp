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
			$(".deptLink").parent().removeClass('active');
			//$(this).addClass("active");
			mobMenu();
		});

		$("#subMobMenu").click(function() {
			$('#leftCol ul').slideToggle();
		});
		
		$(".deptLink-sub").click(function() {
			if($(window).width() < 750) {
				$('#leftCol ul').hide();
			}
		});

		// add padding to role title to make sure contact boxes are the same height
		var maxBoxH = 0;
		$('.contactBox').each(function(){
			if(!$(this).find('.contactNumber').size() || !$(this).find('.contactEmail').size()){
				$(this).find('.contactTitle').css('margin-top', '32px');
			}
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
		<h2 class="headerTab">Directory Look-Up</h2>
		<div class="clear"></div>
		<div id="ptLower" class="whiteBox">
			<form action="/people/lookup" method="post">
				<input type="text" placeholder="Search keywords" class="inputShade" value="" name="query" maxlength="50">
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
                            echo '<li '.$cssClass.'><a href="/people/dept?c='.$c->resourceId.'" class="deptLink">'.$c->name.'</a>';
                            // build sub navigation
                            if($c->resourceId == $community){
			                    foreach($navDomains as $nd){
			                    	if(!empty($nd['subdomains'])){
			                    		echo '<div class="deptLink-sub">'.$nd['name'].'<img class="deptLink-arrow" src="/img/down-arrow.gif"></div>';
				                    	foreach($nd['subdomains'] as $sub){
				                    		echo '<a class="deptLink-sub-sub" href="#'.$sub[0]->subcommunityid.'">'.$sub[0]->subcommunity.'</a>';
				                    	}
				                    }else{
				                    	echo '<a class="deptLink-sub" href="#'.$nd['id'].'">'.$nd['name'].'</a>';
				                    }
			                    }
			                }
 	                        echo '</li>';
                        }
                    ?>
				</ul>
			</div>
			<div id="rightCol">
			    <?php
                    foreach($domains as $d){
                    	echo '<div class="people-list">'.
                        	'<a name="'.$d['id'].'"></a>'.
                            '<h4 class="deptHeader">'.$d['name'];
                        if($d['description'] != ''){
                        	echo '<div onmouseover="showTermDef(this)" onmouseout="hideTermDef()" data-definition="'.$d['description'].'" class="info"><img src="/img/iconInfo.png"></div>';
                        }
                        echo '</h4>';
                        if(count($d['stewardData'])>0){
                            $phone = '';
                            if(isset($d['stewardData']->phonenumber[0]->phonephonenumber)){
                                $phone = $d['stewardData']->phonenumber[0]->phonephonenumber;
                                $phone = '<div class="contactNumber"><a href="tel:'.$phone.'">'.$phone.'</a></div>';
                            }
                            $deptTitle = 'Steward';
                            $cssClass = '';
                            echo '<div class="contactBox contactOrange">'.
                                '    <span class="contactName">'.$d['stewardData']->userfirstname.' '.$d['stewardData']->userlastname.'</span>'.
                                $phone.
                                '    <div class="contactEmail"><a href="mailto:'.$d['stewardData']->emailemailaddress.'">'.$d['stewardData']->emailemailaddress.'</a></div>'.
                                '    <span class="contactTitle orange-text">'.$deptTitle.'<div onmouseover="showTermDef(this)" onmouseout="hideTermDef()" data-definition="'.$stewardDef.'" class="info"><img src="/img/iconInfo.png"></div></span>'.
                                '</div>';
                        }
                        if(count($d['custodianData'])>0){
                            $phone = '';
                           if(isset($d['custodianData']->phonenumber[0]->phonephonenumber)){
                                $phone = $d['custodianData']->phonenumber[0]->phonephonenumber;
                                $phone = '<div class="contactNumber"><a href="tel:'.$phone.'">'.$phone.'</a></div>';
                            }
                            $deptTitle = 'Custodian';
                            $cssClass = '';
                            echo '<div class="contactBox">'.
                                '    <span class="contactName">'.$d['custodianData']->userfirstname.' '.$d['custodianData']->userlastname.'</span>'.
                                $phone.
                                '    <div class="contactEmail"><a href="mailto:'.$d['custodianData']->emailemailaddress.'">'.$d['custodianData']->emailemailaddress.'</a></div>'.
                                '    <span class="contactTitle green-text">'.$deptTitle.'<div onmouseover="showTermDef(this)" onmouseout="hideTermDef()" data-definition="'.$custodianDef.'" class="info"><img src="/img/iconInfo.png"></div></span>'.
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