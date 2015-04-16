<div class="quickLinks innerLower">
	<div class="qlTab"><h3>My Quick Links</h3></div>
	<div class="qlBody">
		<div id="QLContainer">
			<ul>
				<li>
					<a class="ql-list ql-remove" href="#" onclick="removeQL(); return false;"><img src="/img/ql-delete.png"></a>
					<a class="quickLink">ABC Title of the Document</a></li>
				<li>
					<a class="ql-list ql-remove" href="#" onclick="removeQL(); return false;"><img src="/img/ql-delete.png"></a>
					<a class="quickLink">ABC Title of the Document</a></li>
				<li>
					<a class="ql-list ql-remove" href="#" onclick="removeQL(); return false;"><img src="/img/ql-delete.png"></a>
					<a class="quickLink">ABC Title of the Document</a></li>
				<li>
					<a class="ql-list ql-remove" href="#" onclick="removeQL(); return false;"><img src="/img/ql-delete.png"></a>
					<a class="quickLink">ABC Title of the Document</a></li>
				<li>
					<a class="ql-list ql-remove" href="#" onclick="removeQL(); return false;"><img src="/img/ql-delete.png"></a>
					<a class="quickLink">ABC Title of the Document</a></li>
				<li>
					<a class="ql-list ql-remove" href="#" onclick="removeQL(); return false;"><img src="/img/ql-delete.png"></a>
					<a class="quickLink">ABC Title of the Document</a></li>
				<li>
					<a class="ql-list ql-remove" href="#" onclick="removeQL(); return false;"><img src="/img/ql-delete.png"></a>
					<a class="quickLink">ABC Title of the Document</a></li>
				<li>
					<a class="ql-list ql-remove" href="#" onclick="removeQL(); return false;"><img src="/img/ql-delete.png"></a>
					<a class="quickLink">ABC Title of the Document</a></li>
				<li>
					<a class="ql-list ql-remove" href="#" onclick="removeQL(); return false;"><img src="/img/ql-delete.png"></a>
					<a class="quickLink">ABC Title of the Document</a></li>
				<li>
					<a class="ql-list ql-remove" href="#" onclick="removeQL(); return false;"><img src="/img/ql-delete.png"></a>
					<a class="quickLink">ABC Title of the Document</a></li>
			</ul>
		</div>
		<div class="ql-edit grow">
			<a class="editQL">Edit My Quick Links&nbsp;</a>
			<a class="saveEdit">Save Edits</a>
			<span class="saveEdit">&nbsp;&nbsp;|&nbsp;&nbsp;</span>
			<a class="resetDefault" onclick="restoreQL(); return false;" href="#">Restore Defaults&nbsp;</a>
		</div>
	</div>
</div>

<script>
	// function removeQL(inQL) {											         								 						 
	// 	$.ajax({
	// 		type: 'GET',
	// 		url: '/ajax/removeQL.php?QL='+inQL,
	// 		data: $(this).serialize()
	// 	})
	// 	.done(function(data){				 
	// 		// show the response
	// 		$('#QLContainer').html(data);
			 
	// 	})
	// 	.fail(function() {			 							 
	// 	});	 
		
	// 	return false;	
	// }

	// function restoreQL() { 						 
	// 	$.ajax({
	// 		type: 'GET',
	// 		url: '/ajax/restoreQL.php',
	// 		data: $(this).serialize()
	// 	})
	// 	.done(function(data){				 
	// 		// show the response
	// 		$('#QLContainer').html(data);
			 
	// 	})
	// 	.fail(function() {			 						 
	// 	});	 		
				
	// 	return false;	
	// }
</script>