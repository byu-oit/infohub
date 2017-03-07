<div class="quickLinks innerLower">
	<div class="qlTab"><h3>My Quick Links</h3></div>
	<div class="qlBody">
		<div id="QLContainer">
			<ul>
			    <?php
                    foreach($quickLinks as $ql){
                ?>
				<li>
					<a class="ql-list ql-remove" href="#" onclick="removeQL(this,'<?php echo $ql[1]; ?>'); return false;"><img src="/img/ql-delete.png"></a>
					<a class="quickLink" href="/search/term/<?php echo $ql[1]; ?>"><?php echo $ql[0]; ?></a>
				</li>
				<?php
                    }
                ?>
			</ul>
		</div>
		<div class="ql-edit grow">
			<a class="editQL">Edit My Quick Links&nbsp;</a>
			<a class="saveEdit">Save Edits</a>
		</div>
	</div>
</div>
