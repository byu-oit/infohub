<?php
	$this->Html->css('secondary', null, ['inline' => false]);
	$this->Html->css('search', null, ['inline' => false]);
?>
<script type="text/javascript" src="/js/jquery.serialize-object.min.js"></script>
<script>

    $(document).ready(function() {
        $('.sync-btn').click(function() {
            var thisElem = $(this);

            var i = 0;
            var loadingTexts = ['Syncing   ','Syncing.  ','Syncing.. ','Syncing...'];
            var loadingTextInterval = setInterval(function() {
                thisElem.html(loadingTexts[i]);
                i++;
                if (i == loadingTexts.length) i = 0;
            }, 250);

			var database = $('#databaseName').val();
            var schema = $('#schemaName').val();
            var table = $('#tableName').val();

            var requiredErrorString = 'Schema and table names are both required.';
            if (!schema) {
				alert(requiredErrorString);
				clearInterval(loadingTextInterval);
				$('#schemaName').focus();
				return;
            }
            if (!table) {
				alert(requiredErrorString);
				clearInterval(loadingTextInterval);
				$('#tableName').focus();
				return;
            }

            schema = schema.toUpperCase();
            table = table.toUpperCase();
            $.post('/databaseAdmin/syncDatabase', {database:database, schema:schema, table:table})
                .done(function(data) {
                    clearInterval(loadingTextInterval);
                    thisElem.html('Sync');
                    var data = JSON.parse(data);
                    alert(data.message);

                    if (data.redirect) {
                        window.location.href = '/databases/view/'+schema+'/'+schema+' > '+table;
                    }
                });
        });

        $('#schemaName').on({
            keyup: function(e) {
                if (e.which === 13) {
                    $('.sync-btn').click();
                }
            }
        });
        $('#tableName').on({
            keyup: function(e) {
                if (e.which === 13) {
                    $('.sync-btn').click();
                }
            }
        });
    });

</script>
<style type="text/css">
    .sync-btn {
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
        box-shadow: -2px 2px 7px 1px rgba(50, 50, 50, 0.22)
    }
</style>
<div id="apiBody" class="innerLower">
	<div id="searchResults" style="margin-bottom:0px;">
		<h1 class="headerTab">Update Table</h1>
		<div class="clear"></div>
        <div class="tableHelp" style="cursor:default;"></div>
		<div id="srLower" class="whiteBox">
			<div class="resultItem">
				<select name="database" id="databaseName">
					<option value="DWPRD">DWPRD</option>
					<option value="CESPRD">CESPRD</option>
					<option value="DWHRPRD">DWHRPRD</option>
				</select>
                <input type="text" id="schemaName" placeholder="Schema name" style="width:100px;">
                <input type="text" id="tableName" placeholder="Table name" style="width:250px;">
				<div class="sync-btn grow">Sync</div>
			</div>
		</div>
	</div>
</div>
