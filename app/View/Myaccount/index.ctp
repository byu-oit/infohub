<?php
	$this->Html->css('secondary', null, array('inline' => false));
	$this->Html->css('account', null, array('inline' => false));
	$this->assign('title', 'My Requests');
?>
<script>
	$(window).resize(colSize);

	function colSize() {
		if($(window).width() > 650) {
			//$('.riLeft').css('width', '100%').css('width', '-=270px');
		}
		else {
			//$('.riLeft').css('width', '100%');
		}
	}

	function loadCoordinatorPhones() {
		$('.approver').each(function() {
			var thisElem = $(this);
			$.get('/myaccount/getCoordinatorPhoneNumber/' + $(this).attr('user-id'))
				.done(function(data) {
					if (data) {
						var html = '<div class="contactNumber"><div class="icon"></div>'+data+'</div>';
						thisElem.find('.info').append(html);
					}
				});
		});
	}

	function displayHelpText(elem) {
		$('#container')
			.append('<div id="helpText">'+$(elem).attr('helpText')+'</div>');
		$('#helpText').offset({top:$(elem).offset().top - 58, left:$(elem).offset().left - 112});
	}

	function hideHelpText() {
		$('#helpText').remove();
	}

	$(document).ready(function() {
		colSize();
		loadCoordinatorPhones();

		$('.atTab').click(function() {
			var thisElem = $(this);
			$('.accountMain[id!="'+thisElem.attr('id')+'View"]').slideUp(700).promise().done(function() {
				$('#'+thisElem.attr('id')+'View').slideDown();
			});

			$('.atTab').removeClass('active');
			$(this).addClass('active');
		});

		$('.details-btn').click(function() {
			var rid = $(this).attr('data-rid');
			$('#'+rid).slideToggle();
			$(this).toggleClass('active');
		});
		$('.policies-btn').click(function() {
			var rid = $(this).attr('data-rid');
			$('#'+rid+'policies').slideToggle();
			$(this).toggleClass('active');
		});
		$('.share').click(function() {
			window.location.href = '/request/view/' + $(this).attr('data-rid');
		});
		$('.print').click(function() {
			window.open('/request/printView/' + $(this).attr('data-rid'), '_blank').focus();
		});
		$('.edit').click(function() {
			window.location.href = '/request/edit/' + $(this).attr('data-rid');
		});
		$('.delete').click(function() {
			if (confirm("Are you sure you would like to delete this request?")) {
				window.location.href = '/request/delete/' + $(this).attr('data-rid');
			}
		});

		$('.detailsBody').on('click', '.remove', function() {
			if (confirm("Are you sure you'd like to remove this person? (They can still be re-added to the list at any time.)")) {
				$.post('/request/removeCollaborator/' + $(this).attr('data-dsrid') + '/' + $(this).attr('data-netid'))
					.done(function(data) {
						var data = JSON.parse(data);
						alert(data.message);
						window.location.reload(false);
					});
			}
		});
		$('.collaborators').click(function() {
			$(this).parent().find('.collaborators-input-wrapper').fadeIn('fast');
			$(this).parent().find('.remove').removeClass('hidden');
			var inputElement = $(this).parent().find('.collaborators-input');
			$('html, body').animate({scrollTop: inputElement.offset().top - 150}, 'medium', function() {
				inputElement.focus();
			});
		});
		$('.close').click(function() {
			$(this).parent().parent().find('.collaborators-search-result').remove();
			$(this).parent().parent().find('.remove').addClass('hidden');
			$(this).parent().fadeOut('fast');
			$(this).parent().find('.collaborators-input').val('');
		});

		$('.collaborators-input').on('input', function() {
			var inputElement = $(this);
			var inputWrapper = $(this).parent();

			// Delay the autocomplete suggestions for .1 seconds to prevent
			// excessive API calls while typing
			window.clearTimeout($(this).data('timeout'));
			$(this).data('timeout', setTimeout(function () {

				if (inputElement.val().length < 3) {
					inputWrapper.parent().find('.collaborators-search-result').remove();
					return;
				}

				$.get("/directory/lookup?term="+inputElement.val())
					.done(function(data) {
						inputWrapper.parent().find('.collaborators-search-result').remove();
						data = JSON.parse(data);
						data.forEach(function(element) {
							inputWrapper.after(element);
						});
					});

			}, 300));
		});
		$('.detailsBody').on('click', '.collaborators-search-result', function() {
			var thisElem = $(this);
			$.post("/request/addCollaborator/"+$(this).parent().attr('id')+"/"+$(this).attr('person-id'))
				.done(function(data) {
					var data = JSON.parse(data);
					if (data.success == 0) {
						alert(data.message);
						return;
					}
					var html = '<strong>'+data.person.names.preferred_name+':</strong> '
								+data.person.employee_information.job_title+', '
								+data.person.contact_information.email_address+'&nbsp;&nbsp;&nbsp;&nbsp;'
								+'<div class="remove" data-dsrid="'+thisElem.parent().attr('id')+'" data-netid="'+data.person.identifiers.net_id+'">X</div><br>';
					thisElem.parent().find('.collaborators-view').append(html);
				});
		});

		$('.approver .user-icon').on('mouseover click', function(){
			$(this).parent().find('.info').css('z-index', 20).toggle();
		});
		$('.approver .user-icon').mouseout(function(){
			$(this).parent().find('.info').hide();
		});

		<?php if (!empty($expand)): ?>
			$('#<?=$expand?>').slideToggle();
			$('#<?=$expand?>').parent().find('.details-btn[data-rid="<?=$expand?>"]').toggleClass('active');
		<?php endif ?>
	});
</script>
<!-- Background image div -->
<div id="accountBody" class="libOnebg">
</div>

<!-- Request list -->
<div id="accountMid" class="innerLower">
	<div id="accountTop">
		<h1 class="headerTab">My Requests</h1>
		<div id="atLower" class="whiteBox">
			<h2><?php echo $psName ?></h2>
			<div id="aiDept" class="accountInfo"><span class="aiLabel">Department:&nbsp;</span><?php echo $psDepartment ?></div>
			<div id="aiRole" class="accountInfo"><span class="aiLabel">Role:&nbsp;</span><?php echo $psRole ?></div>
			<a class="logout" href="/myaccount/logout">Logout</a>
			<div class="clear"></div>
		</div>
	</div>
	<div class="accountTabs">
		<div id="inProgress" class="atTab active">In Progress</div>
		<div id="completed" class="atTab">Completed</div>
		<div id="canceled" class="atTab">Canceled</div>
	</div>
	<div class="clear"></div>
	<!-- <div class="accountMain whiteBox"> -->
<?php
	foreach ($requestStatuses as $status => $requests) {
		echo '<div id="'.$status.'View" class="accountMain whiteBox">';
		if(sizeof($requests)==0){
			echo '<div class="requestItem"><div class="riLeft"><h4 class="riTitle">No Requests Found</h4></div></div>';
		}else{
			foreach($requests as $req){
				echo '<div class="requestItem">'.
					'    <div class="riLeft">'.
					'        <a class="riTitle" href="/request/view/'.$req->resourceId.'">'.$req->signifier.'</a>'.
					'        <p class="riDate"><span>Date Created:&nbsp;</span>'.date('n/j/Y', ($req->createdOn)/1000).'</p>';
				echo '<div class="status-details-flex"><div class="status-wrapper">';
				switch ($req->statusReference->signifier) {
					case 'In Progress':
						echo '<div class="status-cell green-border left active">In Progress</div><div class="status-cell light-green-border right">Completed</div>';
						break;
					case 'Completed':
						echo '<div class="status-cell light-green-border left">In Progress</div><div class="status-cell green-border right active">Completed</div>';
						break;
					case 'Obsolete':
						echo '<div class="status-cell obsolete">Obsolete</div>';
						break;
					case 'Canceled':
						echo '<div class="status-cell canceled">Canceled</div>';
						break;
				}
				echo '</div>';

				echo '	<a class="details-btn grow" data-rid="'.$req->resourceId.'"><span class="detailsLess">Hide</span><span class="detailsMore">Show</span>&nbsp;Details</a>';

				echo '</div></div>';

				// display approvers and their info
				////////////////////////////////////////
				echo '<div class="riRight">'.
					'<h4 class="riTitle">Coordinators for this Request</h4>'.
					'<div class="approverPics">';
				foreach($req->roles['Request Cordinator'] as $rc){			//Yes, 'cordinator' is misspelled here, but that's how the data comes out
					$approverName = $rc->firstName . " " . $rc->lastName;
					if($approverName != ''){
						$approverImage = '../photos/collibraview/'.$rc->resourceId;
						$approverEmail = $rc->emailAddress;
						echo '<div class="approver" user-id="'.$rc->resourceId.'">'.
							'	<div class="user-icon" style="background-image: url('.$approverImage.');"></div>'.
							'	<div class="info">'.
							'		<div class="contactName">'.$approverName.'</div>'.
							'		<div class="contactEmail"><div class="icon"></div><a href="mailto:'.$approverEmail.'">'.$approverEmail.'</a></div>'.
							'	</div>'.
							'</div>';
					}
				}
				echo '</div></div>';
				echo '	<div class="detailsBody" id="'.$req->resourceId.'">';
	?>
				<h3 class="headerTab">Requested Data</h3>
				<?php if ($req->statusReference->signifier != 'Completed'): ?>
					<a class="edit-btn grow" href="/request/editTerms/<?=$req->resourceId?>" title="Add/Remove Terms"></a>
				<?php endif ?>
				<div class="clear"></div>
				<div class="attrValue">
					<?php $glossaryCount = 0;
					foreach ($req->termGlossaries as $glossaryName => $terms) {
						echo '<em>'.$glossaryName.'&nbsp;-&nbsp;</em>';
						$glossaryCount++;
						$termCount = 0;
						foreach ($terms as $term) {
							echo $term->termsignifier;
							$termCount++;
							if ($termCount < sizeof($terms)) {
								echo ',&nbsp;&nbsp;';
							}
						}
						if ($glossaryCount < sizeof($req->termGlossaries)) {
							echo '<br>';
						}
					} ?>
				</div>
				<?php if (isset($req->additionallyIncluded)): ?>
					<h3 class="headerTab">Additionally Included Data</h3><img class="infoIcon" src="/img/icon-question.png" onmouseover="displayHelpText(this)" onmouseout="hideHelpText()" helpText="These are data elements that you didn't request but are included in the APIs to which you requested access.">
					<div class="clear"></div>
					<div class="attrValue">
						<?php $glossaryCount = 0;
						$termCount = 0;
						foreach ($req->additionallyIncluded->termGlossaries as $glossaryName => $terms) {
							echo '<em>'.$glossaryName.'&nbsp;-&nbsp;</em>';
							$glossaryCount++;
							$termCount = 0;
							foreach ($terms as $term) {
								echo $term->termsignifier;
								$termCount++;
								if ($termCount < sizeof($terms)) {
									echo ',&nbsp;&nbsp;';
								}
							}
							if ($glossaryCount < sizeof($req->additionallyIncluded->termGlossaries)) {
								echo '<br>';
							}
						} ?>
					</div>
				<?php endif ?>
				<h3 class="headerTab">Requester</h3>
				<div class="clear"></div>
				<div class="data-col">
					<h5>Name:</h5>
					<div class="attrValue"><?php echo $req->attributeReferences->attributeReference['Requester Name']->value ?></div>
				</div>
				<div class="data-col">
					<h5>Phone Number:</h5>
					<div class="attrValue"><?php echo $req->attributeReferences->attributeReference['Requester Phone']->value ?></div>
				</div>
				<div class="data-col">
					<h5>Email:</h5>
					<div class="attrValue"><?php echo $req->attributeReferences->attributeReference['Requester Email']->value ?></div>
				</div>
				<div class="data-col">
					<h5>Role:</h5>
					<div class="attrValue"><?php echo $req->attributeReferences->attributeReference['Requester Role']->value ?></div>
				</div>
				<div class="data-col">
					<h5>Requesting Organization:</h5>
					<div class="attrValue"><?php echo $req->attributeReferences->attributeReference['Requesting Organization']->value ?></div>
				</div>
				<div class="clear"></div>

				<h3 class="headerTab">Sponsor</h3>
				<div class="clear"></div>
				<div class="data-col">
					<h5>Sponsor Name:</h5>
					<div class="attrValue"><?php echo $req->attributeReferences->attributeReference['Sponsor Name']->value ?></div>
				</div>
				<div class="data-col">
					<h5>Sponsor Role:</h5>
					<div class="attrValue"><?php echo $req->attributeReferences->attributeReference['Sponsor Role']->value ?></div>
				</div>
				<div class="data-col">
					<h5>Sponsor Email:</h5>
					<div class="attrValue"><?php echo $req->attributeReferences->attributeReference['Sponsor Email']->value ?></div>
				</div>
				<div class="data-col">
					<h5>Sponsor Phone:</h5>
					<div class="attrValue"><?php echo $req->attributeReferences->attributeReference['Sponsor Phone']->value ?></div>
				</div>
				<div class="clear"></div>

				<h3 class="headerTab">Collaborators</h3><div class="edit-btn grow collaborators" title="Add Collaborators"></div><img class="infoIcon" src="/img/icon-question.png" onmouseover="displayHelpText(this)" onmouseout="hideHelpText()" helpText="People on the collaborators list will see this request listed on their 'My Requests' page.">
				<div class="clear"></div>
				<div class="attrValue collaborators-view">
					<?php foreach ($req->attributeReferences->attributeReference['Collaborators'] as $col) {
						echo '<strong>'.$col->names->preferred_name.':</strong> '.
							$col->employee_information->job_title.', '.
							$col->contact_information->email_address;
						echo str_repeat("&nbsp;", 4);
						echo '<div class="remove hidden" data-dsrid="'.$req->resourceId.'" data-netid="'.$col->identifiers->net_id.'">X</div><br>';
					}
					?>
				</div>
				<div class="collaborators-input-wrapper">
					<input type="text" class="collaborators-input" placeholder="Search by name (last, first) or Net ID">
					<div class="lower-btn close grow">Close</div>
				</div>
				<div class="clear"></div>

				<h3 class="headerTab">Application Name</h3>
				<div class="clear"></div>
				<div class="attrValue"><?= $req->attributeReferences->attributeReference['Application Name']->value ?></div>

	<?php
				$arrOrderedFormFields = [
					"Description of Intended Use",
					"Access Rights",
					"Access Method",
					"Impact on System",
					"Application Identity",
					"Additional Information Requested"
				];
				$completedStatuses = ['Completed', 'Approved', 'Obsolete'];
				if (empty($req->dataUsages)) {
					foreach ($arrOrderedFormFields as $field) {
						foreach ($req->attributeReferences->attributeReference as $attrRef) {
							if (!empty($attrRef->value) && $attrRef->labelReference->signifier == $field) {
								echo '<h3 class="headerTab">'.$attrRef->labelReference->signifier.'</h3><div class="clear"></div>'.
									'<div class="attrValue">'.$attrRef->value.'</div>';
								break;
							}
						}
					}

					if (!empty($req->policies)) {
						echo '<div class="policy-header-wrapper"><h3 class="headerTab">Data Usage Policies</h3><a class="policies-btn grow" data-rid="'.$req->resourceId.'"><span class="policiesHide">Hide</span><span class="policiesShow">Show</span>&nbsp;Policies</a></div>';
						echo '<div class="clear"></div><div class="policies" id="'.$req->resourceId.'policies" style="display:none;">';
						foreach ($req->policies as $policy) {
							echo '<h5>'.$policy->policyName.'</h5>';
							echo '<div class=attrValue>'.$policy->policyDescription.'</div><div class="clear"></div>';
						}
						echo '</div>';
					}

					echo '<div class="lower-btn delete grow" data-rid="'.$req->resourceId.'">Delete</div>';
					echo '<div class="lower-btn edit grow" data-rid="'.$req->resourceId.'">Edit</div>';
				}
				echo '<div class="lower-btn print grow" data-rid="'.$req->resourceId.'">Print</div>';
				echo '<div class="lower-btn share grow" data-rid="'.$req->resourceId.'">Share</div>';
				echo '</div>';

				foreach($req->dataUsages as $du) {
					echo '<div class="riBelow">';
					$dsaName = $du->signifier;
					$dsaStatus = strtolower($du->status);
					echo '<div class="subrequestNameWrapper"><a class="riTitle subrequestName" href="/request/view/'.$du->id.'">'.$dsaName.'</a></div>';
					echo '<div class="approverPics">';
					$oneApprover = (
						$du->roles['Steward'][0]->firstName . " " . $du->roles['Steward'][0]->lastName
						== $du->roles['Custodian'][0]->firstName . " " . $du->roles['Custodian'][0]->lastName
					);
					$approverName = $du->roles['Steward'][0]->firstName . " " . $du->roles['Steward'][0]->lastName;
					if($approverName != ''){
						$approverImage = '../photos/collibraview/'.$du->roles['Steward'][0]->resourceId;
						$approverEmail = $du->roles['Steward'][0]->emailAddress;
						echo '<div class="approver steward" user-id="'.$du->roles['Steward'][0]->resourceId.'">'.
							'	<div class="user-icon" style="background-image: url('.$approverImage.');"></div>'.
							'	<div class="info">'.
							'		<div class="contactName">'.$approverName.'</div>';
							if (!$oneApprover) {
								echo '<div class="approverRole"><div class="icon"></div>Steward';
							} else {
								echo '<div class="approverRole"><div class="icon"></div>Custodian and Steward';
							}
							echo '</div>'.
							'		<div class="contactEmail"><div class="icon"></div><a href="mailto:'.$approverEmail.'">'.$approverEmail.'</a></div>'.
							'	</div>'.
							'</div>';
					}
					if(!$oneApprover){
						$approverName = $du->roles['Custodian'][0]->firstName . " " . $du->roles['Custodian'][0]->lastName;
						if($approverName != ''){
							$approverImage = '../photos/collibraview/'.$du->roles['Custodian'][0]->resourceId;
							$approverEmail = $du->roles['Custodian'][0]->emailAddress;
							echo '<div class="approver custodian" user-id="'.$du->roles['Custodian'][0]->resourceId.'">'.
								'	<div class="user-icon" style="background-image: url('.$approverImage.');"></div>'.
								'	<div class="info">'.
								'		<div class="contactName">'.$approverName.'</div>'.
								'		<div class="approverRole"><div class="icon"></div>Custodian</div>'.
								'		<div class="contactEmail"><div class="icon"></div><a href="mailto:'.$approverEmail.'">'.$approverEmail.'</a></div>'.
								'	</div>'.
								'</div>';
						}
					}
					echo '</div>';
					echo '<br />';
					echo '<div class="status-details-flex"><div class="status-wrapper">';
					switch ($dsaStatus) {
						case 'pending custodian':
							echo '<div class="status-cell green-border left active">Pending Custodian</div><div class="status-cell light-green-border">Pending Steward</div><div class="status-cell light-green-border right">Approved</div>';
							break;
						case 'pending steward':
							echo '<div class="status-cell light-green-border left">Pending Custodian</div><div class="status-cell green-border active">Pending Steward</div><div class="status-cell light-green-border right">Approved</div>';
							break;
						case 'approved':
							echo '<div class="status-cell light-green-border left">Pending Custodian</div><div class="status-cell light-green-border">Pending Steward</div><div class="status-cell green-border right active">Approved</div>';
							break;
						case 'rejected':
							echo '<div class="status-cell light-red-border left">Pending Custodian</div><div class="status-cell light-red-border">Pending Steward</div><div class="status-cell red-border right active">Rejected</div>';
							break;
						case 'canceled':
							echo '<div class="status-cell canceled">Canceled</div>';
							break;
						case 'obsolete':
							echo '<div class="status-cell obsolete">Obsolete</div>';
							break;
						case 'deleted':
							echo '<div class="status-cell deleted">Deleted</div>';
							break;
					}
					echo '</div>';
					echo '	<a class="details-btn grow" data-rid="'.$du->id.'"><span class="detailsLess">Hide</span><span class="detailsMore">Show</span>&nbsp;Details</a></div></div>';

					echo '<div class="detailsBody" id="'.$du->id.'">';
					echo '<p class="riDate"><strong>Requested Data:</strong>';
					foreach ($req->termGlossaries as $glossaryName => $terms) {
						if ($terms[0]->commrid != $du->communityId) {
							continue;
						}
						echo '<br><em>'.$glossaryName.'&nbsp;-&nbsp;</em>';
						$termCount = 0;
						foreach ($terms as $term) {
							echo $term->termsignifier;
							$termCount++;
							if ($termCount < sizeof($terms)) {
								echo ',&nbsp;&nbsp;';
							}
						}
						break;
					}
					echo '</p>';
					foreach ($arrOrderedFormFields as $field) {
						foreach ($du->attributeReferences->attributeReference as $attrRef) {
							if (!empty($attrRef->value) && $attrRef->labelReference->signifier == $field) {
								echo '<h3 class="headerTab">'.$attrRef->labelReference->signifier.'</h3><div class="clear"></div>'.
									'<div class="attrValue">'.$attrRef->value.'</div>';
								break;
							}
						}
					}

					if (!empty($du->policies)) {
						echo '<div class="policy-header-wrapper"><h3 class="headerTab">Data Usage Policies</h3><a class="policies-btn grow" data-rid="'.$du->id.'"><span class="policiesHide">Hide</span><span class="policiesShow">Show</span>&nbsp;Policies</a></div>';
						echo '<div class="clear"></div><div class="policies" id="'.$du->id.'policies" style="display:none;">';
						foreach ($du->policies as $policy) {
							echo '<h5>'.$policy->policyName.'</h5>';
							echo '<div class=attrValue>'.$policy->policyDescription.'</div><div class="clear"></div>';
						}
						echo '</div>';
					}

					if (!in_array($du->status, $completedStatuses)) {
						echo '<div class="lower-btn delete grow" data-rid="'.$du->id.'">Delete</div>';
						echo '<div class="lower-btn edit grow" data-rid="'.$du->id.'">Edit</div>';
					}
					echo '<div class="lower-btn print grow" data-rid="'.$du->id.'">Print</div>';
					echo '<div class="lower-btn share grow" data-rid="'.$du->id.'">Share</div>';
					echo '</div>';
				}

				echo '</div>';
			}
		}
		echo '</div>';
	}
?>
	</div>
</div>
