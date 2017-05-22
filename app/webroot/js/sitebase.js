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

	$('.editQL').click(function() {
		$('.ql-edit').addClass('active');
		$('.ql-list').addClass('ql-active');
		$('.quickLink').addClass('active-link')
	});
	$('.saveEdit').click(function() {
		$('.ql-edit').removeClass('active');
		$('.ql-list').removeClass('ql-active');
		$('.quickLink').removeClass('active-link')
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
		$.get("/request/listQueue")
			.done(function(data){
				if($(window).scrollTop()>50){
					var requestIconPos = $('#request-queue .request-num').offset();
					var left = requestIconPos.left - $('#request-popup').width() - 16;
					$('#request-popup').addClass('fixed').css('left', left);
				}else{
					$('#request-popup').removeClass('fixed').css('left', 'auto');
				}
				$('#request-popup').html(data).slideDown('fast');
			});
	}else{
		$('#request-popup').hide();
	}
}
function showRequestQueue(){
	$.get("/request/listQueue")
		.done(function(data){
			if($(window).scrollTop()>50){
				var requestIconPos = $('#request-queue .request-num').offset();
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
			var title = $('#requestItem'+id).attr('data-title');
			var rID = $('#requestItem'+id).attr('data-rid');
			var vocabID = $('#requestItem'+id).attr('data-vocabID');
			var apiHost = $('#requestItem'+id).attr('api-host');
			var apiPath = $('#requestItem'+id).attr('api-path');

			$('#request-undo').remove();
			$('#requestItem'+id).fadeOut('fast',function(){
				if ($('#requestItem'+id).attr('api') == 'false') {
					var html = '<div id="request-undo" data-title="' + title + '" data-rid="' + rID + '" data-vocabID="' + vocabID + '" data-apiHost="' + apiHost + '" data-apiPath="' + apiPath + '" api="false">Item removed. Click to undo.</div>';
				} else {
					var html = '<div id="request-undo" data-apiHost="' + apiHost + '" data-apiPath="' + title + '" api="true">Item removed. Click to undo.</div>';
				}
				$(html).insertBefore('#request-popup ul');
				$('#request-undo').click(function(){
					addToQueue(this, false);
					$(this).remove();
				})
				getCurrentRequestTerms();
			});
	});
}
function clearRequestQueue(){
	$.post("/request/clearQueue")
		.done(function(data){
			$('#request-queue .request-num').text('0').addClass('request-hidden');
			$('#request-popup').find('li').fadeOut('fast');
			$('#request-popup').find('#request-undo').fadeOut('fast');
			$('<div class="cart-cleared">Request items removed.</div>').insertBefore('#request-popup ul');
		});
}
function getCurrentRequestTerms(){
	$.get("/request/getQueueJSArray")
		.done(function(data){
			var originalData = data;
			data = data.split(',');
			$('#request-queue .request-num').text(data.length).removeClass('request-hidden');
			$('input[type=checkbox]').prop('checked', false);

			for(i=0; i<data.length; i++){
				$('.chk'+data[i]).prop('checked', true);
			}
			if(originalData.length <= 0){
				$('#request-queue .request-num').addClass('request-hidden');
				hideRequestQueue();
			}
	});
}
function addToQueue(elem, clearRelated){
	if ($(elem).attr('api') == 'false') {
		var arrTitles = [$(elem).attr('data-title')];
		var arrIDs = [$(elem).attr('data-rid')];
		var arrVocabIDs = [$(elem).attr('data-vocabID')];
		var apiHost = $(elem).attr('data-apiHost');
		var apiPath = $(elem).attr('data-apiPath');

		$(elem).parent().find('.checkBoxes').find('input').each(function(){
			if($(this).prop("checked")){
				arrTitles.push($(this).attr('data-title'));
				arrIDs.push($(this).val());
				arrVocabIDs.push($(this).attr('data-vocabID'));
			}
		});
		$.post("/request/addToQueue", {t:arrTitles, id:arrIDs, vocab:arrVocabIDs, clearRelated:clearRelated, apiHost: apiHost, apiPath: apiPath})
			.done(function(data){
				$(elem).attr('value', 'Added to Request').removeClass('grow').addClass('inactive');
				data = parseInt(data);
				if(data>0){
					showRequestQueue();
					getCurrentRequestTerms();
				}
		});
	} else {
		//Add an API with unspecified fields to cart.
		var arrTitle = [$(elem).attr('data-apiPath')];
		var arrVocab = ['emptyApi'];
		var apiHost = $(elem).attr('data-apiHost');
		$.post("/request/addToQueue", {t:arrTitle, vocab:arrVocab, apiHost: apiHost})
			.done(function(data){
				$(elem).attr('value', 'Added to Request').removeClass('grow').addClass('inactive');
				data = parseInt(data);
				if(data>0){
					showRequestQueue();
					getCurrentRequestTerms();
				}
		});
	}
}
/////////////////////////////

// QuickLinks functions
/////////////////////////////
function removeQL(li, id, starClick){
	$.ajax({
		type: 'POST',
		url: '/quickLinks/remove',
		data: {'id':id}
	});
	if (!starClick) {
		$(li).parent().remove();
	} else {
		$(li).remove();
	}
	$('#term'+id+' .addQuickLink img').attr('src', '/img/iconStarBlue.gif');
}

function addQL(t, id) {
	$.ajax({
		type: 'POST',
		url: '/quickLinks/add',
		data: {'ql':t, 'id':id}
	})
	.done(function(data){
		$('#term'+id+' .addQuickLink img').attr('src', '/img/iconStarOrange.gif');
		if(data==1){
			var html = '<li id="term-' + id + '-list-item">'+
				'    <a class="ql-list ql-remove" href="#" onclick="removeQL(this,\''+id+'\'); return false;"><img src="/img/ql-delete.png"></a>'+
				'    <a class="quickLink" href="/search/term/'+id+'">'+t+'</a>'+
				'</li>';
			$('#QLContainer ul').append(html);
		}else{
			removeQL('#term-'+id+'-list-item', id, 1);
		}
	});
}

$(document).on( 'click', function ( e ) {
	if ( $( e.target ).closest('.autoComplete').length === 0 ) {
		$('.autoComplete').hide();
	}
});

$(window).scroll(function(){
	if($(this).scrollTop()>50){
		var requestIconPos = $('#request-queue .request-num').offset();
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
