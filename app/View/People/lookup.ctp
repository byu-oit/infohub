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
			//$(this).addClass("active");
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
		<h2 class="headerTab">Directory Look-Up</h2>
		<div class="clear"></div>
		<div id="ptLower" class="whiteBox">
			<form action="/people/lookup" method="post">
				<input type="text" placeholder="Search keywords" class="inputShade" value="<?php echo $query ?>" name="query" maxlength="50">
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
                        foreach($parentCommunities->communityReference as $c){
                            echo '<li><a href="/people/dept?c='.$c->resourceId.'" class="deptLink">'.$c->name.'</a></li>';
                        }
                    ?>
				</ul>
			</div>
			<div id="rightCol">
			    <?php
                    if(count($communities->aaData[0]->Subcommunities)==0){
                        echo '<h1>No results found.</h1><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>';
                    }else{
                    	// loop through user groups
                        foreach($communities->aaData[0]->Subcommunities as $c){
                        	$title = $c->subcommunity;
                        	if($c->parentCommunity != ''){
                        		$title = $c->parentCommunity.' <span class="arrow-separator">&gt;</span> '.$title;
                        	}

                            echo '<div class="people-list">'.
                                '<h4 class="deptHeader">'.$title.'</h4>';
                            // display stewards
                           	if(isset($c->steward)){
                           		$name = $c->steward->userfirstname.' '.$c->steward->userlastname;
                           		if(strpos(strtolower($name), strtolower($query)) !== false){
                           			$name = '<strong>'.$name.'</strong>';
                           		}
                           		$email = $c->steward->emailemailaddress;
                           		$phone = '';
                           		if(count($c->steward->phonenumber>0)){
                           			$phone = $c->steward->phonenumber[0]->phonephonenumber;
                           		}
                           		echo '<div class="contactBox contactOrange">'.
                                    '    <span class="contactName">'.$name.'</span>';
                                if($phone != ''){
                                    echo '    <div class="contactNumber"><a href="tel:'.$phone.'">'.$phone.'</a></div>';
                                }
                                echo '    <div class="contactEmail"><a href="mailto:'.$email.'">'.$email.'</a></div>'.
                                	'    <span class="contactTitle">Steward<div onmouseover="showTermDef(this)" onmouseout="hideTermDef()" data-definition="'.$stewardDef.'" class="info"><img src="/img/iconInfo.png"></div></span>'.
                                    '</div>'; 
                           	}
                           	// display custodians
                           	if(isset($c->custodian)){
                           		$name = $c->custodian->userfirstname.' '.$c->custodian->userlastname;
                           		if(strpos(strtolower($name), strtolower($query)) !== false){
                           			$name = '<strong>'.$name.'</strong>';
                           		}
                           		$email = $c->custodian->emailemailaddress;
                           		$phone = '';
                           		if(count($c->custodian->phonenumber>0)){
                           			$phone = $c->custodian->phonenumber[0]->phonephonenumber;
                           		}
                           		echo '<div class="contactBox">'.
                                    '    <span class="contactName">'.$name.'</span>';
                                if($phone != ''){
                                    echo '    <div class="contactNumber"><a href="tel:'.$phone.'">'.$phone.'</a></div>';
                                }
                                echo '    <div class="contactEmail"><a href="mailto:'.$email.'">'.$email.'</a></div>'.
                                	'    <span class="contactTitle">Custodian<div onmouseover="showTermDef(this)" onmouseout="hideTermDef()" data-definition="'.$custodianDef.'" class="info"><img src="/img/iconInfo.png"></div></span>'.
                                    '</div>'; 
                           	}

                            echo '</div>';
                        }
                    }
                ?>
			</div>
			<div class="clear"></div>
		</div>
	</div>
</div>