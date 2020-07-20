<?php
	$this->Html->css('secondary', null, ['inline' => false]);
	$this->Html->css('search', null, ['inline' => false]);
?>
<script type="text/javascript" src="/js/jquery.serialize-object.min.js"></script>
<script>

    $(document).ready(function() {
		$("#browse-tab").addClass('active');

        $('.sync-btn').click(function() {
            var thisElem = $(this);
			var database = $('#databaseName').val();
			var schema = $('#schemaName').val();
			var table = $('#tableName').val();

			if (!table && !confirm('Did you mean to leave the table name blank? This will result in syncing the entire schema, which will take some time.')) return;

            var i = 0;
            var loadingTexts = ['Syncing   ','Syncing.  ','Syncing.. ','Syncing...'];
            var loadingTextInterval = setInterval(function() {
                thisElem.html(loadingTexts[i]);
                i++;
                if (i == loadingTexts.length) i = 0;
            }, 250);

            if (!schema) {
				alert('Schema name is required.');
				clearInterval(loadingTextInterval);
				$('#schemaName').focus();
				return;
            }

            schema = schema.toUpperCase();
            table = table.toUpperCase();
            $.post('/databaseAdmin/syncDatabase', {database:database, schema:schema, table:table})
                .done(function(data) {
                    clearInterval(loadingTextInterval);
                    thisElem.html('Sync');
                    var data = JSON.parse(data);
					if (data.diff) {
						window.location.href = '/databaseAdmin/diff/'+database+'/'+schema+'/'+table;
						return;
					}
                    alert(data.message);

                    if (data.redirect) {
                        window.location.href = table ? '/databases/view/'+database+'/'+schema+'/'+schema+' > '+table : '/databases/schema/'+database+'/'+schema;
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
	#schemaName {
		width: 100px;
	}
	#tableName {
		width: 250px;
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
					<?php
						foreach ($databases as $db) {
							echo '<option value="'.$db.'">'.$db.'</option>';
						}
					?>
				</select>
                <input type="text" id="schemaName" placeholder="Schema name">
                <input type="text" id="tableName" placeholder="Table name">
                <div class="sync-btn grow">Sync</div>
            </div>
            <?php if(strpos($_SERVER['HTTP_HOST'],'dev') === false) : ?>
				<a href="https://support.byu.edu/ih?id=sync_database">
					<span>
							<input type="button" value="Service Portal IH Beta">
					</span>
				</a> 
			<?php else : ?>
				<a href="https://supoport-test.byu.edu/ih?id=sync_database">
					<span>
							<input type="button" value="Service Portal IH Beta">
					</span>
				</a>
			<?php endif; ?>
        </div>
        
	</div>
</div>
