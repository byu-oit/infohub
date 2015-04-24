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
        
        // populare form fields for testing
        $('#request input, #request textarea').each(function(i) {
            if($(this).val()==''){
                $(this).val('TEST DATA '+i);
            }
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
    
    function validate(){
        var isValid = true;
        $('#request input, #request textarea').each(function() {
            if($(this).val()==''){
                isValid = false;
                $(this).focus();
                return false;
            }
        });
        if(!isValid) alert('All fields are requried.');
        return isValid;
    }
</script>

<!-- Background image div -->
<div id="searchBg" class="deskBg">
</div>

<!-- Request Form -->
<form action="/request/submit" method="post" id="request" onsubmit="return validate();">
	<div id="searchBody" class="innerLower">

		<div id="requestForm">
			<h2 class="headerTab">Request Form</h2>

			<div id="srLower" class="whiteBox">
				<h3 class="headerTab">Requester</h3>
				<div class="clear"></div>
				<div id="requesterInfo">
					<!-- <div class="infoCol"> -->
						<input type="text" id="fname" name="fname" class="inputShade" placeholder="First name">
						<input type="text" id="lname" name="lname" class="inputShade" placeholder="Last Name">
						<input type="text" id="phone" name="phone" class="inputShade" placeholder="Phone Number">
					<!-- </div>
					<div class="infoCol"> -->
						<input type="text" id="email" name="email" class="inputShade" placeholder="Email">
						<input type="text" id="company" name="company" class="inputShade" placeholder="Company">
						<select type="text" id="supervisor" name="supervisor" class="inputShade">
							<option value="1" selected disabled style='display:none;'>Your Supervisor</option>
							<option value="2">John Doe</option>
							<option value="3">Jane Doe</option>
						</select>
					<!-- </div> -->
				</div>
            <?php
                if(sizeof($termDeatils->aaData)>0){
                    $term = $termDeatils->aaData[0];
                    $createdDate = $term->createdOn/1000;
                    $createdDate = date('m/d/Y', $createdDate);
            ?>
                <h3 class="headerTab">Information Requested</h3>
				<div class="clear"></div>
				<div class="resultItem highlyClassified">
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
                        </div>
					</div>
					<div class="irLower">
						<h5>Also included in this selection (check all that apply to your request).</h5>
                        <div class="checkBoxes">
                            <div class="checkCol">
                        <?php 
                            for($i=0; $i<sizeof($siblingTerms->termReference)-1; $i++){
                                $sibling = $siblingTerms->termReference[$i]->signifier;
                                $siblingID = $siblingTerms->termReference[$i]->resourceId;
                                if($i>0 && $i%2==0){
                                    echo '</div>';
                                    echo '<div class="checkCol">';
                                }
                                $checked = '';
                                if(isset($termsSelected[$siblingID])){
                                    $checked = 'checked';
                                }
                                echo '    <input type="checkbox" name="terms[]" id="'.$siblingID.'" '.$checked.'>'.
                                    '    <label for="'.$siblingID.'">'.$sibling.'</label>';
                                if($i%2==0){
                                    echo '<br/>';
                                }

                            }
                        ?>
                            </div>
                            <div class="clear"></div>
                        </div>
					</div>
				</div>
            <?php
                }
            ?>
                <?php
                    foreach($formFields->formProperties as $field){
                        echo '<label class="headerTab" for="'.$field->id.'">'.$field->name.'</label>'.
                            '<div class="clear"></div>'.
                            '<div class="taBox">';
                        
                        if($field->type == 'textarea'){
                            $val = '';
                            if($field->id == 'dataNeeded'){
                                $val = $dataNeeded;
                            }
                            echo '<textarea name="'.$field->id.'" id="'.$field->id.'"  class="inputShade">'.$val.'</textarea>';
                        }elseif($field->type == 'user'){
                            echo '<select name="'.$field->id.'" id="'.$field->id.'">';
                            foreach($sponsors->user as $sponsor){
                                if($sponsor->enabled==1){
                                    echo '<option value="'.$sponsor->resourceId.'">'.$sponsor->firstName.' '.$sponsor->lastName.'</option>';
                                }
                            }
                            echo '</select>';
                        }else{
                            echo '<input type="text" name="'.$field->id.'" id="'.$field->id.'"  class="inputShade" />';
                        }
                        
                        echo '</div>';
                    }
                ?>
				<label for="requestSubmit" id="mobileReqd">*All Fields Required</label>
				<div class="clear"></div>
			</div>
		</div>
		<div class="clear"></div>
	</div>
	<div id="formSubmit" class="innerLower">
		<input type="submit" value="Place Request" id="requestSubmit" name="requestSubmit" class="grow">
		<label for="requestSubmit" class="mobileHide">*All Fields Required</label>
		<div class="clear"></div>
	</div>
	<div class="clear"></div>
</form>

<!-- Quick links -->
<?php echo $this->element('quick_links'); ?>
