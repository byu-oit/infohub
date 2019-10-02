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
			var dataset = $('#dataset').val() != 'new' ? $('#dataset').val() : $('#newDataset').val();

			var i = 0;
			var loadingTexts = ['Importing   ','Importing.  ','Importing.. ','Importing...'];
			var loadingTextInterval = setInterval(function() {
				thisElem.html(loadingTexts[i]);
				i++;
				if (i == loadingTexts.length) i = 0;
			}, 250);

			if (!path || !dataset) {
				alert('Path and dataset are required.');
				clearInterval(loadingTextInterval);
				thisElem.html('Import');
				if (!path) { $('#path').focus(); } else { $('#dataset').focus(); }
				return;
			}
			alert("Starting Import");

			$.post('/virtualDatasetAdmin/import', {path:path, dataset:dataset})
				.done(function(data) {
					clearInterval(loadingTextInterval);
					thisElem.html('Import');
					alert(data);
					var data = JSON.parse(data);
					alert(data.message);

					if (data.redirect) {
						window.location.href = '/virtualDatasets';
					}
				});
		});

		$('#path, #newDataset').on({
			keyup: function(e) {
				if (e.which === 13) {
					$('.import-btn').click();
				}
			}
		});

		$('#dataset').change(function() {
			if ($(this).val() == 'new') {
				$('#newDataset').show();
			} else {
				$('#newDataset').hide();
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
	#dataset {
		width: 30%;
		margin-bottom: 10px;
	}
	#newDataset {
		display: none;
		width: 48%;
		margin-left: 2%;
	}
</style>
<div id="apiBody" class="innerLower">
	<div id="searchResults" style="margin-bottom:0px;">
		<h1 class="headerTab">Update Virtual Datasets</h1>
		<div class="clear"></div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem">
                <p class="instructions">Input the full path to the space, folder, or table you would like to import to InfoHub. If you provide a space or folder, all contained folders and tables will be imported and included in the selected dataset.</p>
				<p class="instructions"><strong>If you are updating a table(s),</strong> note that any tables that are part of an active Data Sharing Request will not be updated. You can contact <span class="contact" director-name="<?= $director->firstName.' '.$director->lastName ?>" director-email="<?= $director->emailAddress ?>" director-phone="<?= $director->phoneNumbers->phone[0]->number ?>">our Governance Director</span> to request that these tables be updated manually.</p>
                <input type="text" id="path" placeholder="Path separated by dots (e.g., 'Space.Folder.Table')">
				<div class="import-btn grow">Import</div>
				<div class="clear"></div>
				<select id="dataset">
					<option value="">Select a dataset</option>
					<option value="new">Create new dataset</option>
					<?php foreach ($datasets as $ds) {
						echo '<option value="'.$ds->datasetName.'">'.$ds->datasetName.'</option>';
					} ?>
				</select>
				<input type="text" id="newDataset" placeholder="Enter name of new dataset" style="display:none;">
			</div>
		</div>
	</div>
</div>
