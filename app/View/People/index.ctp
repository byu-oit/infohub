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
					<li><a href="/people" class="deptLink active">A-Z Listing</a></li>
					<?php
						foreach($communities->communityReference as $c){
							echo '<li><a href="/people/dept?c='.$c->resourceId.'" class="deptLink">'.$c->name.'</a></li>';
						}
					?>
				</ul>
			</div>
			<div id="rightCol">
				<?php
					// loop through user groups
					foreach($userData as $key => $val){
						echo '<div class="people-list">'.
							'<h4 class="deptHeader">'.$key.'</h4>';
						// display eac user in group
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

							// list roles
							echo '	<div class="roles">';
							if(count($user['stewardRoles'])>0){
								echo '<strong class="orangeText">Steward<div onmouseover="showTermDef(this)" onmouseout="hideTermDef()" data-definition="'.$stewardDef.'" class="info"><img src="/img/iconInfo.png"></div></strong>';
								echo '<ul>';
								foreach($user['stewardRoles'] as $role){
									echo '<li>'.$role.'</li>';
								}
								echo '</ul>';
							}
							if(count($user['custodianRoles'])>0){
								echo '<strong class="greenText">Custodian<div onmouseover="showTermDef(this)" onmouseout="hideTermDef()" data-definition="'.$custodianDef.'" class="info"><img src="/img/iconInfo.png"></div></strong>';
								echo '<ul>';
								foreach($user['custodianRoles'] as $role){
									echo '<li>'.$role.'</li>';
								}
								echo '</ul>';
							}
							echo '	</div>'.
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