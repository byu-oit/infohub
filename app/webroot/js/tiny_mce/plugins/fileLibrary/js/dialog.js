tinyMCEPopup.requireLangPack();

var fileLibraryDialog = {
    init : function() {
		var f = document.forms[0];
        
        // Setup browse button
		document.getElementById('srcbrowsercontainer').innerHTML = getBrowserHTML('srcbrowser','src','image','theme_advanced_image');
        if (isVisible('srcbrowser'))
			document.getElementById('src').style.width = '180px';

		// Get the selected contents as text and place it in the input
		//f.someval.value = tinyMCEPopup.editor.selection.getContent({format : 'text'});
		//f.somearg.value = tinyMCEPopup.getWindowArg('some_custom_arg');
	},

	insert : function() {
		// Insert the contents from the input into the document
		tinyMCEPopup.editor.execCommand('mceInsertContent', false, document.forms[0].youtube_embed.value);
		tinyMCEPopup.close();
	},
	
	getImageData : function(file) {
		this.file = file;
		if(file.type == 'image'){

		}else{
			$('#btnSubmit1, #dimensions').hide();
			$('#btnSubmit2').show();
		}
	}
}

function insertVideo(file, title){
	var embedW = 320;
	var embedH = 240;
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

function insertImage(){
    var f = document.forms[0];
    var src = f.src.value;
    var desc = f.imgDesc.value;
    var imgW = f.width.value;
    var imgH = f.height.value;
    if(src.substring(0,1) != '/'){
    	src = "/"+src;
    }
	var html = '<img src="'+src+'" width="'+imgW+'" alt="'+desc+'" />';
	
	tinyMCEPopup.editor.execCommand('mceInsertContent', false, html);
	tinyMCEPopup.editor.addVisual()
	tinyMCEPopup.close();
}

function insertDocument(){
	var f = document.forms[0];
    var src = f.src.value;
    var desc = f.imgDesc.value;

    var file = fileLibraryDialog.file.fullPath;
    file = file.replace('/app/webroot', '');
    if(file.substring(0,1) != '/'){
    	file = "/"+file;
    }

    if(fileLibraryDialog.file.type == 'pdf'){
    	desc = '<img src="/js/tiny_mce/plugins/fileLibrary/img/pdf_icon.gif" />';
    }

    if(desc == ''){
		desc = fileLibraryDialog.file.name;
    }

	console.log(fileLibraryDialog.file)
	tinyMCEPopup.restoreSelection();
	var selectedText = tinyMCE.activeEditor.selection.getContent();
	if(selectedText != "") title = selectedText;
	var html = '<a href="'+file+'" target="_blank">'+desc+'</a>';
	tinyMCEPopup.editor.execCommand('mceInsertContent', false, html);
	tinyMCEPopup.editor.addVisual()
	tinyMCEPopup.close();
}


tinyMCEPopup.onInit.add(fileLibraryDialog.init, fileLibraryDialog);
