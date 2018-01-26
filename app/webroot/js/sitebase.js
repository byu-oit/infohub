var headIn;
var winOu;
var headFor;
var headFinal;

$(document).ready(function(){
	// Shows mobile menu
	$('#mob-nav').click(function() {
		$('#mainNav').toggle("slide", { direction: "left" }, 300);
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
			// Likewise for unlinked API fields
			if (id.indexOf('.') > -1) {
				id = id.replace(/\./g, '');
			}
			if ($('#requestItem'+id).attr('api') == 'false') {
				var title = $('#requestItem'+id).attr('data-title');
				var rID = $('#requestItem'+id).attr('data-rid');
				var vocabID = $('#requestItem'+id).attr('data-vocabID');
				var apiHost = $('#requestItem'+id).attr('api-host');
				var apiPath = $('#requestItem'+id).attr('api-path');
			} else {
				var title = $('#requestItem'+id).attr('data-title');
				var apiHost = $('#requestItem'+id).attr('api-host');
			}

			$('#request-undo').remove();
			$('#requestItem'+id).fadeOut('fast',function(){
				if ($('#requestItem'+id).attr('api') == 'false') {
					var html = '<div id="request-undo" data-title="' + title + '" data-rid="' + rID + '" data-vocabID="' + vocabID + '" data-apiHost="' + apiHost + '" data-apiPath="' + apiPath + '" api="false">Item removed. Click to undo.</div>';
				} else {
					var html = '<div id="request-undo" data-apiHost="' + apiHost + '" data-apiPath="' + title + '" api="true">Item removed. Click to undo.</div>';
				}
				$(html).insertBefore('.irLower ul');
				$('#request-undo').click(function(){
					addToQueue(this, false);
					$(this).remove();
					$('#requestItem'+id).fadeIn('fast');
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

	if ($(elem).attr('api') == 'false') {
		var arrTerms = [{
			title: $(elem).data('title'),
			id: $(elem).data('rid'),
			vocabId: $(elem).data('vocabid')
		}];
		var arrFields = [];
		var apiHost = $(elem).attr('data-apiHost');
		var apiPath = $(elem).attr('data-apiPath');

		$(elem).parent().find('.checkBoxes').find('input').each(function(){
			if($(this).prop("checked") && $(this).prop("name") != "toggleCheckboxes"){

				if (!$(this).val()) {		// For an API field with no Business Term:
					arrFields.push($(this).data('title'));
				}

				else {						// For a Business Term:
					for (var i = 0; i < arrTerms.length; i++) {
						if (arrTerms[i].id == $(this).val()) {
							return;		// continue;
						}
					}

					arrTerms.push({
						title: $(this).data('title'),
						id: $(this).val(),
						vocabId: $(this).data('vocabid')
					});
				}
			}
		});
		$.post("/request/addToQueue", {emptyApi:'false', t:arrTerms, f:arrFields, apiHost:apiHost, apiPath:apiPath})
			.done(function(data){
				clearInterval(loadingTextInterval);
				$(elem).parent().find('.requestAccess').attr('value', 'Added to Request').removeClass('grow').addClass('inactive');
				$(elem).closest('.resultItem').find('input[type=checkbox]').prop('checked', false);
				data = parseInt(data);
				if(data>0){
					if (displayCart) {
						showRequestQueue();
					}
					updateQueueSize();
				}
		});
	} else {
		// Add an API without specified fields to cart.
		var arrTitle = [$(elem).attr('data-apiPath')];
		var apiHost = $(elem).attr('data-apiHost');
		$.post("/request/addToQueue", {emptyApi:'true', t:arrTitle, apiHost:apiHost})
			.done(function(data){
				clearInterval(loadingTextInterval);
				$(elem).parent().find('.requestAccess').attr('value', 'Added to Request').removeClass('grow').addClass('inactive');
				data = parseInt(data);
				if(data>0){
					if (displayCart) {
						showRequestQueue();
					}
					updateQueueSize();
				}
		});
	}
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

$(function() {
	if(!$.support.placeholder) {
		var active = document.activeElement;
		$('textarea').each(function(index, element) {
			if($(this).val().length == 0 && !$(this).hasClass('noPlaceHolder')) {
				$(this).html($(this).attr('id')).addClass('hasPlaceholder');
			}
		});
		$('input, textarea').focus(function () {
			if ($(this).attr('placeholder') != '' && $(this).val() == $(this).attr('placeholder') && !$(this).hasClass('noPlaceHolder')) {
				$(this).val('').removeClass('hasPlaceholder');
			}
		}).blur(function () {
			if (($(this).attr('placeholder') != '' && ($(this).val() == '' || $(this).val() == $(this).attr('placeholder')) && !$(this).hasClass('noPlaceHolder'))) {
				$(this).val($(this).attr('placeholder')).addClass('hasPlaceholder');
				//$(this).css('background', 'red');
			}
		});
		$(':text').blur();
		$(active).focus();
		$('form').submit(function () {
			$(this).find('.hasPlaceholder').each(function() { $(this).val(''); });
		});
	}
});
