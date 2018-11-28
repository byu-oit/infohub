var headIn;
var winOu;
var headFor;
var headFinal;

$(document).ready(function(){
	// Shows mobile menu
	$('#mob-nav').click(function() {
		$('#mainNav').toggle("slide", { direction: "left" }, 300);
	});

	// Browse nav menu
	$('#browse-tab').click(function() {
		$(this).toggleClass('open');
		$('#drop-down-menu').toggleClass('open');
	});

	// Help Pop-out functionality
	$("#deskTopHelp").click(function() {
		$(this).hide();
		widenBorder();
		$("#nhContent").show("slide", { direction: "right" }, 500);
	});
	$(".close").click(function() {
		if ($(window).width() > 750) {
			$("#nhContent").hide("slide", { direction: "right" }, 500, function() {
				$("#deskTopHelp").fadeIn("fast");
			});
		}
		else {
			$("#nhContent").slideUp();
		}
	});

	$("#mobileHelp").click(function() {
		$("#nhContent").slideToggle();
	});
});

$(document).ready(resizeFonts);
$(document).ready(iniVars);
$(window).resize(resizeFonts);
$(window).resize(iniVars);
$(window).load(resizeFonts);
$(window).load(iniVars);

//Get values
function iniVars() {
	headIn = $("#headerInner").width();
	winOut = $(window).width();
	headFor = winOut - headIn;
	headFinal = headFor / 2;
}

//Sets right border for "Need Help" fly-out on larger screens
function widenBorder() {
	$("#nhContent").css("border-right-width", 0);
}

function resizeFonts(){
	var defSize = 10;
	var mobileWidth = 550;

	// reset font size when in mobile view
	if($(window).width()<=mobileWidth){
		$('body').css('fontSize', defSize);
		return false;
	}

	var size = $(document).width() / 1230;
	var maxSize =  10;
	var minSize = 6.5;
	size = defSize * size;
	if(size>maxSize) size=maxSize;
	if(size<minSize) size=minSize;
	$('body').css('fontSize', size);
}

$(document).ready(function(){
	var index = -1;
	$('#searchInput').keypress(function(event) { return event.keyCode != 13; });
	$('#searchInput').on({
		keyup: function(e){
			var m;

			if ($.trim($('#searchInput').val()) == ''){
				$('.autoComplete').hide();
			}
			else if  ( e == true ) {
				$('.autoComplete').hide();
			}
			else if  ( e.which == 27 ) {
				$('.autoComplete').hide();
				index = -1;
			}
			else if(e.which == 13) {
				if($('.autoComplete li').hasClass('active')){
					$('#searchInput').val($('.autoComplete li.active').text());
					$('#searchInput').parent().submit();
				}
				else {
					$('#searchInput').parent().submit();
				}
				$('.autoComplete').hide();
			}
			else if(e.which == 38){
				e.preventDefault();
				if(index == -1){
					index = $('.autoComplete li').length - 1;
				}
				else {
					index--;
				}

				if(index > $('.autoComplete li').length ){
					index = $('.autoComplete li').length + 1;
				}
				m = true;
			}
			else if(e.which === 40){
				e.preventDefault();
				if(index >= $('.autoComplete li').length - 1){
					index = 0;
				}
				else{
					index++;
				}
				m = true;
			}
			else{
				var val = $('#searchInput').val();
				setTimeout(function() {
					if (val != $('#searchInput').val()) {
						//User continued typing, so throw this out
						return;
					}
					$.getJSON( "/search/autoCompleteTerm", { q: val } )
					.done(function( data ) {
						if (val != $('#searchInput').val()) {
							//User continued typing, so throw this out
							return;
						}
						$('.autoComplete .results').html('');
						for (var i in data) {
							$('.autoComplete .results').append($('<li>', {text: data[i].name.val}));
						}
						$('.autoComplete li').click(function(){
							$('#searchInput').val($(this).text());
							$('#searchInput').parent().submit();
							$('.autoComplete').hide();
						});
					});
				}, 300);

				$('.autoComplete').show();
			}

			if(m){
				$('.autoComplete li.active').removeClass('active');
				$('.autoComplete li').eq(index).addClass('active');
			}
		}
	});
});

function showTermDef(elem){
	var pos = $(elem).offset();
	var data = $(elem).attr('data-definition');
	$('#info-win .info-win-content').html(data);
	$('#info-win').show();
	var winLeft = pos.left - $('#info-win').outerWidth()/2 + 5;
	var winTop = pos.top - $('#info-win').outerHeight() - 5;
	$('#info-win').css('top',winTop).css('left',winLeft);
}
function hideTermDef(){
	$('#info-win').hide();
}

// Request functions
///////////////////////////////
function toggleRequestQueue(){
	if ($('#request-popup').css('display') == 'none'){
		showRequestQueue();
	}else{
		hideRequestQueue();
	}
}
function showRequestQueue(){
	$.get("/request/cartDropdown")
		.done(function(data){
			updateQueueSize();
			if($(window).scrollTop()>50){
				var requestIconPos = $('#request-queue').offset();
				var left = requestIconPos.left - $('#request-popup').width() - 16;
				$('#request-popup').addClass('fixed').css('left', left);
			}else{
				$('#request-popup').removeClass('fixed').css('left', 'auto');
			}
			$('#request-popup').html(data).slideDown('fast');
		});
}
function hideRequestQueue(){
	$('#request-popup').hide();
}
function removeFromRequestQueue(id){
	$.post("/request/removeFromQueue", {id:id})
		.done(function(data){
			// We're using the path to identify empty APIs. However, slashes
			// in selectors cause problems w/ jQuery, so we need to remove them.
			if (id.indexOf('/') > -1) {
				id = id.replace(/\//g, '');
			}

			var elem = $('#requestItem'+id);
			var type = elem.data('type');
			switch (type) {
				case 'term':
					var title = elem.data('title');
					var dataId = elem.data('id');
					var vocabId = elem.data('vocabID');
					var apiHost = elem.attr('api-host');
					var apiPath = elem.attr('api-path');
					var databaseName = elem.attr('database-name');
					var schemaName = elem.attr('schema-name');
					var tableName = elem.attr('table-name');
					var responseName = elem.attr('response-name');

					var undoData = {emptyApi:'false',t:[{title:title,id:dataId,vocabId:vocabId}]};
					break;
				case 'api':
					var title = elem.data('title');
					var apiHost = elem.attr('api-host');

					var undoData = {emptyApi:'true',t:[title],apiHost:apiHost};
					break;
				case 'field':
					var fieldName = elem.data('name');
					var fieldId = elem.data('title')
					var apiHost = elem.attr('api-host');
					var apiPath = elem.attr('api-path');

					var undoData = {emptyApi:'false',f:[fieldName],fi:[fieldId],apiHost:apiHost,apiPath:apiPath};
					break;
				case 'column':
					var columnName = elem.data('name');
					var columnId = elem.data('title');
					var databaseName = elem.attr('database-name');
					var schemaName = elem.attr('schema-name');
					var tableName = elem.attr('table-name');

					var undoData = {emptyApi:'false',c:[columnName],ci:[columnId],databaseName:databaseName,schemaName:schemaName,tableName:tableName};
					break;
				case 'samlField':
					var fieldName = elem.data('name');
					var fieldId = elem.data('title');
					var responseName = elem.attr('response-name');

					var undoData = {emptyApi:'false',s:[fieldName],si:[fieldId],responseName:responseName};
					break;
			}

			$('#request-undo').remove();
			elem.fadeOut('fast', function() {
				var html = '<div id="request-undo">Item removed. Click to undo.</div>';

				$(html).insertBefore('.irLower ul');
				$('#request-undo').click(function(){
					$.post("request/addToQueue", undoData);
					$(this).remove();
					elem.fadeIn('fast');
				});
				updateQueueSize();
			});
	});
}
function clearRequestQueue(){
	$.post("/request/clearQueue")
		.done(function(data){
			$('#request-queue .request-num').text('0').addClass('request-hidden');
			$('#request-popup').find('.clearQueue').fadeOut('fast');
			$('#request-popup').find('h3').html('Requested Items: 0');
			$('.irLower').find('ul.cart').find('li').fadeOut('fast');
			$('.irLower').find('ul.cart').find('h4').fadeOut('fast');
			$('.irLower').find('#request-undo').fadeOut('fast');
			$('<div class="cart-cleared">Request items removed.</div>').insertBefore('.irLower ul.cart');
		});
}
function updateQueueSize(){
	$.get("/request/getQueueSize")
		.done(function(data){
			data = parseInt(data);
			if (data == 0) {
				$('#request-queue .request-num').text(data).addClass('request-hidden');
			} else {
				$('#request-queue .request-num').text(data).removeClass('request-hidden');
			}
	});
}
function addToQueue(elem, displayCart){
	var i = 0;
	var loadingTexts = ['Working on it   ','Working on it.  ','Working on it.. ','Working on it...'];
	var loadingTextInterval = setInterval(function() {
		$(elem).parent().find('.requestAccess').attr('value', loadingTexts[i]);
		i++;
		if (i == loadingTexts.length) i = 0;
	}, 250);

	var arrTerms = [{
		title: $(elem).data('title'),
		id: $(elem).data('rid'),
		vocabId: $(elem).data('vocabid')
	}];
	$(elem).parent().find('.checkBoxes').find('input').each(function() {
		if ($(this).prop("checked")) {

			for (var i = 0; i < arrTerms.length; i++) {
				if (arrTerms[i].id == $(this).val()) {
					return;
				}
			}

			arrTerms.push({
				title: $(this).data('title'),
				id: $(this).val(),
				vocabId: $(this).data('vocabid')
			});
		}
	});

	$.post("/request/addToQueue", {emptyApi:'false',t:arrTerms})
		.done(function(data) {
			clearInterval(loadingTextInterval);
			$(elem).parent().find('.requestAccess').attr('value', 'Added to Request').removeClass('grow').addClass('inactive');
			$(elem).closest('.resultItem').find('input[type=checkbox]').prop('checked', false);
			if (displayCart) {
				showRequestQueue();
			}
			updateQueueSize();
		});
}
function addToQueueAPI(elem, displayCart){
	var i = 0;
	var loadingTexts = ['Working on it   ','Working on it.  ','Working on it.. ','Working on it...'];
	var loadingTextInterval = setInterval(function() {
		$(elem).parent().find('.requestAccess').attr('value', loadingTexts[i]);
		i++;
		if (i == loadingTexts.length) i = 0;
	}, 250);

	if ($(elem).attr('api') == 'false') {
		var arrFields = [];
		var arrFieldIds = [];
		var apiHost = $(elem).attr('data-apiHost');
		var apiPath = $(elem).attr('data-apiPath');

		$(elem).parent().find('.checkBoxes').find('input').each(function(){
			if($(this).prop("checked") && $(this).prop("name") != "toggleCheckboxes"){
				arrFields.push($(this).data('name'));
				arrFieldIds.push($(this).data('fieldId'));
			}
		});
		if (arrFields.length < 500) {
			$.post("/request/addToQueue", {emptyApi:'false', f:arrFields, fi:arrFieldIds, apiHost:apiHost, apiPath:apiPath})
				.done(function(data) {
					clearInterval(loadingTextInterval);
					$(elem).parent().find('.requestAccess').attr('value', 'Added to Request').removeClass('grow').addClass('inactive');
					$(elem).closest('.resultItem').find('input[type=checkbox]').prop('checked', false);
					if (displayCart) {
						showRequestQueue();
					}
					updateQueueSize();
			});
		} else {
			largeAddProgressSetUp(arrFields.length);
			largeAddToQueueAPI(arrFields, arrFieldIds, apiHost, apiPath)
				.then(function(data) {
					clearInterval(loadingTextInterval);
					$(elem).parent().find('.requestAccess').attr('value', 'Added to Request').removeClass('grow').addClass('inactive');
					$(elem).closest('.resultItem').find('input[type=checkbox]').prop('checked', false);
					if (displayCart) {
						showRequestQueue();
					}
					updateQueueSize();
				});
		}
	} else {
		// Add an API without specified fields to cart.
		var arrTitle = [$(elem).attr('data-apiPath')];
		var apiHost = $(elem).attr('data-apiHost');
		$.post("/request/addToQueue", {emptyApi:'true', t:arrTitle, apiHost:apiHost})
			.done(function(data) {
				clearInterval(loadingTextInterval);
				$(elem).parent().find('.requestAccess').attr('value', 'Added to Request').removeClass('grow').addClass('inactive');
				if (displayCart) {
					showRequestQueue();
				}
				updateQueueSize();
		});
	}
}
function largeAddToQueueAPI(arrFields, arrFieldIds, apiHost, apiPath, fieldsStride = 150) {
	if (arrFields.length > fieldsStride) {
		return new Promise(function(resolve) {
			var request = $.post("/request/addToQueue", {emptyApi:'false', f:arrFields.slice(0, fieldsStride), fi:arrFieldIds.slice(0, fieldsStride), apiHost:apiHost, apiPath:apiPath})
				.then(() => {
					largeAddProgressIncrement();
				});
			var recur = largeAddToQueueAPI(arrFields.slice(fieldsStride), arrFieldIds.slice(fieldsStride), apiHost, apiPath, fieldsStride);

			Promise.all([request, recur]).then(() => resolve());
			});
	}
	else {
		return new Promise(function(resolve) {
			$.post("/request/addToQueue", {emptyApi:'false', f:arrFields, fi:arrFieldIds, apiHost:apiHost, apiPath:apiPath})
				.then(() => {
					largeAddProgressIncrement();
					resolve();
				});
			});
	}
}
function largeAddProgressSetUp(fieldsSize, fieldsStride = 150) {
	var denominator = Math.floor(fieldsSize / fieldsStride) + 1;

	var html = '<strong>This will take a moment... (<span id="progress-numerator">0</span>/'+denominator+')</strong>'+
				'<progress id="progress-bar" value="0" max="1" data-numerator="0" data-denominator="'+denominator+'">0% done</progress>';
	if ($(window).scrollTop() > 50) {
		var requestIconPos = $('#request-queue').offset();
		var left = requestIconPos.left - $('#request-popup').width() - 16;
		$('#request-popup').addClass('fixed').css('left', left);
	} else {
		$('#request-popup').removeClass('fixed').css('left', 'auto');
	}
	$('#request-popup').html(html).slideDown('fast');
}
function largeAddProgressIncrement() {
	var bar = $('#progress-bar');
	var numerator = bar.data('numerator') + 1;
	var denominator = bar.data('denominator');

	$('#progress-numerator').html(numerator);
	bar.data('numerator', numerator);
	bar.val(numerator / denominator);
}
function addToQueueDBTable(elem, displayCart) {
	var i = 0;
	var loadingTexts = ['Working on it   ','Working on it.  ','Working on it.. ','Working on it...'];
	var loadingTextInterval = setInterval(function() {
		$(elem).parent().find('.requestAccess').attr('value', loadingTexts[i]);
		i++;
		if (i == loadingTexts.length) i = 0;
	}, 250);

	var arrColumns = [];
	var arrColumnIds = [];
	var databaseName = $(elem).data('databasename');
	var schemaName = $(elem).data('schemaname');
	var tableName = $(elem).data('tablename');

	$(elem).parent().find('.checkBoxes').find('input').each(function(){
		if($(this).prop("checked") && $(this).prop("name") != "toggleCheckboxes"){
			arrColumns.push($(this).data('name'));
			arrColumnIds.push($(this).data('columnId'));
		}
	});
	if (arrColumns.length < 500) {
		$.post("/request/addToQueue", {emptyApi:'false', c:arrColumns, ci:arrColumnIds, databaseName:databaseName, schemaName:schemaName, tableName:tableName})
			.done(function(data) {
				clearInterval(loadingTextInterval);
				$(elem).parent().find('.requestAccess').attr('value', 'Added to Request').removeClass('grow').addClass('inactive');
				$(elem).closest('.resultItem').find('input[type=checkbox]').prop('checked', false);
				if (displayCart) {
					showRequestQueue();
				}
				updateQueueSize();
		});
	} else {
		largeAddProgressSetUp(arrColumns.length);
		largeAddToQueueDBTable(arrColumns, arrColumnIds, databaseName, schemaName, tableName)
			.then(function(data) {
				clearInterval(loadingTextInterval);
				$(elem).parent().find('.requestAccess').attr('value', 'Added to Request').removeClass('grow').addClass('inactive');
				$(elem).closest('.resultItem').find('input[type=checkbox]').prop('checked', false);
				if (displayCart) {
					showRequestQueue();
				}
				updateQueueSize();
			});
	}
}
function largeAddToQueueDBTable(arrColumns, arrColumnIds, databaseName, schemaName, tableName, columnsStride = 150) {
	if (arrColumns.length > columnsStride) {
		return new Promise(function(resolve) {
			var request = $.post("/request/addToQueue", {emptyApi:'false', c:arrColumns.slice(0, columnsStride), ci:arrColumnIds.slice(0, columnsStride), databaseName:databaseName, schemaName:schemaName, tableName:tableName})
				.then(() => {
					largeAddProgressIncrement();
				});
			var recur = largeAddToQueueDBTable(arrColumns.slice(columnsStride), arrColumnIds.slice(columnsStride), databaseName, schemaName, tableName, termsStride, columnsStride);

			Promise.all([request, recur]).then(() => resolve());
			});
	}
	else {
		return new Promise(function(resolve) {
			$.post("/request/addToQueue", {emptyApi:'false', c:arrColumns, ci:arrColumnIds, databaseName:databaseName, schemaName:schemaName, tableName:tableName})
				.then(() => {
					largeAddProgressIncrement();
					resolve();
				});
			});
	}
}
function addToQueueSAMLResponse(elem, displayCart) {
	var i = 0;
	var loadingTexts = ['Working on it   ','Working on it.  ','Working on it.. ','Working on it...'];
	var loadingTextInterval = setInterval(function() {
		$(elem).parent().find('.requestAccess').attr('value', loadingTexts[i]);
		i++;
		if (i == loadingTexts.length) i = 0;
	}, 250);

	var arrFields = [];
	var arrFieldIds = [];
	var responseName = $(elem).data('responsename');

	$(elem).parent().find('.checkBoxes').find('input').each(function(){
		if($(this).prop("checked") && $(this).prop("name") != "toggleCheckboxes"){
			arrFields.push($(this).data('name'));
			arrFieldIds.push($(this).data('fieldId'));
		}
	});
	$.post("/request/addToQueue", {emptyApi:'false', s:arrFields, si:arrFieldIds, responseName:responseName})
		.done(function(data) {
			clearInterval(loadingTextInterval);
			$(elem).parent().find('.requestAccess').attr('value', 'Added to Request').removeClass('grow').addClass('inactive');
			$(elem).closest('.resultItem').find('input[type=checkbox]').prop('checked', false);
			if (displayCart) {
				showRequestQueue();
			}
			updateQueueSize();
	});
}
function toggleAllCheckboxes(elem) {
	$(elem).closest('.resultItem').find('input').each(function(){
		$(this).prop('checked', $(elem).prop('checked'));
	});
}
/////////////////////////////

$(document).on( 'click', function ( e ) {
	if ( $( e.target ).closest('.autoComplete').length === 0 ) {
		$('.autoComplete').hide();
	}
	if ( $( e.target ).closest('.developmentShopAutoComplete').length === 0 ) {
		$('.developmentShopAutoComplete').hide();
		$('.developmentShopAutoComplete .results').html('');
	}
	if ( $( e.target ).closest('.applicationOrProjectNameAutoComplete').length === 0 ) {
		$('.applicationOrProjectNameAutoComplete').hide();
		$('.applicationOrProjectNameAutoComplete .results').html('');
	}
	if ( $( e.target ).closest('#browse-tab').length === 0 && $('#drop-down-menu').hasClass('open') ) {
		$('#browse-tab').removeClass('open');
		$('#drop-down-menu').removeClass('open');
	}
});

$(window).scroll(function(){
	if($(this).scrollTop()>50){
		var requestIconPos = $('#request-queue').offset();
		var left = requestIconPos.left - $('#request-popup').width() - 16;
		$('#request-popup').addClass('fixed').css('left', left);
	}else{
		$('#request-popup').removeClass('fixed').css('left', 'auto');
	}
});
