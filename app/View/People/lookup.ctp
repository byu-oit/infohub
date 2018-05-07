<?php
	$this->Html->css('secondary', null, ['inline' => false]);
	$this->Html->css('people', null, ['inline' => false]);
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
					//////////////////////////////////
					// loop through users
					//////////////////////////////////
					foreach($userData as $key => $val){
						foreach($val as $key2 => $val2){
							$user = $val[$key2];
							echo '<div class="contactBox contactBlue">'.
								'	<span class="contactName">'.$user['fname'].' '.$user['lname'].'</span>'.
								'	<div class="contact-info">';
							if($user['phone'] != '&nbsp;'){
								echo '		<div class="contactNumber"><a href="tel:'.$user['phone'].'">'.$user['phone'].'</a></div>';
							}
							echo '		<div class="contactEmail"><a href="mailto:'.$user['email'].'">'.$user['email'].'</a></div>'.
								'	</div>';

							// list communities with steward role
							echo '	<div class="roles">';
							if(count($user['stewardRoles'])>0){
								echo '<strong class="orangeText">Steward<div onmouseover="showTermDef(this)" onmouseout="hideTermDef()" data-definition="'.$stewardDef.'" class="info"><img src="/img/iconInfo.png"></div></strong>';
								echo '<ul>';
								foreach($user['stewardRoles'] as $role){
									if ($role->hasNonMetaChildren == 'true') {
										echo '<li><a href="/search/listTerms/'.$role->vocabularyid.'">';
									} else {
										echo '<li><a href="/search/noGlossary">';
									}
									foreach($role->parents as $parent){
										echo $parent->subcommunity.' <span class="arrow-separator">&gt;</span> ';
									}
									echo $role->subcommunity;
									echo '</a></li>';
								}
								echo '</ul>';
							}

							// list communities with steward role
							if(count($user['custodianRoles'])>0){
								echo '<strong class="greenText">Custodian<div onmouseover="showTermDef(this)" onmouseout="hideTermDef()" data-definition="'.$custodianDef.'" class="info"><img src="/img/iconInfo.png"></div></strong>';
								echo '<ul>';
								foreach($user['custodianRoles'] as $role){
									if($role->hasNonMetaChildren == 'true'){
										echo '<li><a href="/search/listTerms/'.$role->vocabularyid.'">';
									}else{
										echo '<li><a href="/search/noGlossary">';
									}
									foreach($role->parents as $parent){
										echo $parent->subcommunity.' <span class="arrow-separator">&gt;</span> ';
									}
									echo $role->subcommunity;
									echo '</a></li>';
								}
								echo '</ul>';
							}
							echo '	</div>'.
								'</div>';
						}
					}
				?>
				<div class="clear"></div><br><br>
			    <?php
			    	//////////////////////////////////
			    	// loop through communities
			    	//////////////////////////////////
                    if(count($communities->aaData[0]->Subcommunities)==0 && count($userData)==0){
                        echo '<h1>No results found.</h1><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>';
                    }else{
                    	// loop through user groups
                        foreach($communities->aaData[0]->Subcommunities as $c){
                        	$title = '';
                        	foreach($c->parents as $parent){
								$title .= $parent->subcommunity.' <span class="arrow-separator">&gt;</span> ';
							}
							$title .= $c->subcommunity;

                            echo '<div class="people-list">'.
                                '<h4 class="deptHeader">'.$title;
                            if($c->description != ''){
	                        	echo '<div onmouseover="showTermDef(this)" onmouseout="hideTermDef()" data-definition="'.$c->description.'" class="info"><img src="/img/iconInfo.png"></div>';
	                        }
	                        echo '</h4>';

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
                                	'    <span class="contactTitle orangeText">Steward<div onmouseover="showTermDef(this)" onmouseout="hideTermDef()" data-definition="'.$stewardDef.'" class="info"><img src="/img/iconInfo.png"></div></span>'.
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
                                	'    <span class="contactTitle greenText">Custodian<div onmouseover="showTermDef(this)" onmouseout="hideTermDef()" data-definition="'.$custodianDef.'" class="info"><img src="/img/iconInfo.png"></div></span>'.
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
