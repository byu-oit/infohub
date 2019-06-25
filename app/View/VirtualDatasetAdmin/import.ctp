<?php
	$this->Html->css('secondary', null, ['inline' => false]);
	$this->Html->css('search', null, ['inline' => false]);
?>
<script type="text/javascript" src="/js/jquery.serialize-object.min.js"></script>
<script>

	$(document).ready(function() {
		$("#browse-tab").addClass('active');

		$('.instructions .contact').on('mouseover', function(){
			var pos = $(this).offset();
			var data = '<strong>'+$(this).attr('director-name')+'</strong><br/>'+$(this).attr('director-email')+'<br/>'+$(this).attr('director-phone');
			$('#info-win .info-win-content').html(data);
			$('#info-win').show();
			var winLeft = pos.left + $(this).outerWidth()/2 - $('#info-win').outerWidth()/2 + 5;
			var winTop = pos.top - $('#info-win').outerHeight() - 5;
			$('#info-win').css('top',winTop).css('left',winLeft);
		});
		$('.instructions .contact').mouseout(function(){
			$('#info-win').hide();
		});

		$('.import-btn').click(function() {
			var thisElem = $(this);
			var path = $('#path').val();
			var space = path.split('.')[0];

			var i = 0;
			var loadingTexts = ['Importing   ','Importing.  ','Importing.. ','Importing...'];
			var loadingTextInterval = setInterval(function() {
				thisElem.html(loadingTexts[i]);
				i++;
				if (i == loadingTexts.length) i = 0;
			}, 250);

			if (!path) {
				alert('Path is required.');
				clearInterval(loadingTextInterval);
				thisElem.html('Import');
				$('#path').focus();
				return;
			}

			$.post('/virtualDatasetAdmin/import', {path:path})
				.done(function(data) {
					clearInterval(loadingTextInterval);
					thisElem.html('Import');
					var data = JSON.parse(data);
					alert(data.message);

					if (data.redirect) {
						window.location.href = '/virtualDatasets';
					}
				});
		});

		$('#path').on({
			keyup: function(e) {
				if (e.which === 13) {
					$('.import-btn').click();
				}
			}
		});
	});

</script>
<style type="text/css">
    .import-btn {
        display: inline-block;
        padding: 4px 14px;
        margin-left: 15px;
        font-size: 13px;
        font-weight: 600;
        color: white;
        background-color: #114477;
        cursor: pointer;
        -webkit-box-shadow: -2px 2px 7px 1px rgba(50, 50, 50, 0.22);
        -moz-box-shadow: -2px 2px 7px 1px rgba(50, 50, 50, 0.22);
        box-shadow: -2px 2px 7px 1px rgba(50, 50, 50, 0.22);
    }
    .instructions {
        font-size: 14px;
    }
	.contact {
		text-decoration: underline;
		cursor: pointer;
	}
    #path {
        width: 80%;
        margin: 14px 0px;
    }
</style>
<div id="apiBody" class="innerLower">
	<div id="searchResults" style="margin-bottom:0px;">
		<h1 class="headerTab">Update Virtual Datasets</h1>
		<div class="clear"></div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem">
                <p class="instructions">Input the full path to the space, folder, or dataset you would like to import to InfoHub. If you provide a space or folder, all contained folders and datasets will be imported.</p>
				<p class="instructions"><strong>If you are updating a dataset(s),</strong> note that any datasets that are part of an active Data Sharing Request will not be updated. You can contact <span class="contact" director-name="<?= $director->firstName.' '.$director->lastName ?>" director-email="<?= $director->emailAddress ?>" director-phone="<?= $director->phoneNumbers->phone[0]->number ?>">our Governance Director</span> to request that these datasets be updated manually.</p>
                <input type="text" id="path" placeholder="Path separated by dots (e.g., 'Space.Folder.Dataset')">
				<div class="import-btn grow">Import</div>
			</div>
		</div>
	</div>
</div>
