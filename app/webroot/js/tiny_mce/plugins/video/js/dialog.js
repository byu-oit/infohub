tinyMCEPopup.requireLangPack();

var VideoDialog = {
	init : function() {
		var f = document.forms[0];

		// Get the selected contents as text and place it in the input
		//f.someval.value = tinyMCEPopup.editor.selection.getContent({format : 'text'});
		//f.somearg.value = tinyMCEPopup.getWindowArg('some_custom_arg');
	},

	insert : function() {
		// Insert the contents from the input into the document
		tinyMCEPopup.editor.execCommand('mceInsertContent', false, document.forms[0].youtube_embed.value);
		tinyMCEPopup.close();
	}
}

function insertYoutube(){
    // make video size responsive
    var embedCode = document.forms[0].youtube_embed.value;
    var w = embedCode.match(/width\s*=\s*\"?(\d+)\"?/gi).toString();
    w = w.match(/\d+/);
    var h = embedCode.match(/height\s*=\s*\"?(\d+)\"?/gi).toString();
    h = h.match(/\d+/);
    var ratio = Math.round(h/w*100)+'%';
    embedCode = '<div class="videoWrapper" style="padding-bottom:'+ratio+'">'+embedCode+'</div>';
    embedCode = embedCode.replace(/\bwidth="(\d+)"/g, '');
    embedCode = embedCode.replace(/\bheight="(\d+)"/g, '');
	// add html code to editor
    tinyMCEPopup.editor.execCommand('mceInsertContent', false, embedCode);
	tinyMCEPopup.close();
}

function insertFromList(file){
	var embedW = 320;
	var embedH = 240;
	//if(!minVersion) minVersion = 9;
	//var rnd = Math.floor(Math.random()*1000).toString() + Math.floor(Math.random()*1000).toString();
	/*var embedCode = '<div id="flash_' + rnd + '"> </div>' +
		'<script  type="text/javascript">' +
		'var so = new SWFObject("test.swf", "flash_' + rnd + '", "' + embedW + '", "' + embedW + '", "' + minVersion + '", "#ffffff");so.write("flash_' + rnd + '");' +
		'</script>';
	*/
	var embedCode = '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" width="'+embedW+'" height="'+embedH+'" id="flash" align="middle">' +
		'<param name="allowScriptAccess" value="sameDomain" />' +
		'<param name="allowFullScreen" value="false" />' +
		'<param name="movie" value="/cms/flvplayer.swf?filePath='+file+'" />' +
		'<param name="quality" value="high" />' +
		'<param name="bgcolor" value="#ffffff" />' +
		'<embed src="/cms/flvplayer.swf?filePath='+file+'" quality="high" bgcolor="#ffffff" width="'+embedW+'" height="'+embedH+'" name="flash" align="middle" allowScriptAccess="sameDomain" allowFullScreen="false" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />' +
		'</object>';
	tinyMCEPopup.editor.execCommand('mceInsertContent', false, embedCode);
	tinyMCEPopup.close();
}

tinyMCEPopup.onInit.add(VideoDialog.init, VideoDialog);
