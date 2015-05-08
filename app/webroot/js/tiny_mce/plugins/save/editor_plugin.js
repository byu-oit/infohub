/**
 * editor_plugin_src.js
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under LGPL License.
 *
 * License: http://tinymce.moxiecode.com/license
 * Contributing: http://tinymce.moxiecode.com/contributing
 */

(function() {
	tinymce.create('tinymce.plugins.Save', {
		init : function(ed, url) {
			var t = this;

			t.editor = ed;

			// Register commands
			ed.addCommand('mceSave', t._save, t);
			ed.addCommand('mceCancel', t._cancel, t);

			// Register buttons
			ed.addButton('save', {title : 'save.save_desc', cmd : 'mceSave'});
			ed.addButton('cancel', {title : 'save.cancel_desc', cmd : 'mceCancel'});

			ed.onNodeChange.add(t._nodeChange, t);
			ed.onChange.add(t._onChange, t);
			ed.onUndo.add(t._onUndo, t);
			ed.onRedo.add(t._onRedo, t);
			ed.onInit.add(t._onInit, t);
			ed.addShortcut('ctrl+s', ed.getLang('save.save_desc'), 'mceSave');	
		},

		getInfo : function() {
			return {
				longname : 'Save',
				author : 'Moxiecode Systems AB',
				authorurl : 'http://tinymce.moxiecode.com',
				infourl : 'http://wiki.moxiecode.com/index.php/TinyMCE:Plugins/save',
				version : tinymce.majorVersion + "." + tinymce.minorVersion
			};
		},

		// Private methods

		_nodeChange : function(ed, cm, n) {
			var ed = this.editor;

			if (ed.getParam('save_enablewhendirty')) {
				cm.setDisabled('save', !ed.isDirty());
				cm.setDisabled('cancel', !ed.isDirty());
			}
		},
		
		// CUSTOM: DISABLE SAVE BUTTON ON LOAD
		_onInit : function(ed, cm, n) {
			var cm = ed.controlManager;
			cm.setDisabled('save', true);
		},
		// CUSTOM: CHANGE ICON IF ANYTHING HAS CHANGED
		_onChange : function(ed, cm, n) {
			var cm = ed.controlManager;
			cm.setDisabled('save', false);
		},
		_onUndo : function(ed, cm, n) {
			var cm = ed.controlManager;
			cm.setDisabled('save', false);
		},
		_onRedo : function(ed, cm, n) {
			var cm = ed.controlManager;
			cm.setDisabled('save', false);
		},

		// Private methods

		_save : function() {
			var ed = this.editor, formObj, os, i, elementId;

			formObj = tinymce.DOM.get(ed.id).form || tinymce.DOM.getParent(ed.id, 'form');

			if (ed.getParam("save_enablewhendirty") && !ed.isDirty())
				return;

			tinyMCE.triggerSave();

			// Use callback instead
			if (os = ed.getParam("save_onsavecallback")) {
				if (ed.execCallback('save_onsavecallback', ed)) {
					ed.startContent = tinymce.trim(ed.getContent({format : 'raw'}));
					ed.nodeChanged();
				}

				return;
			}

			if (formObj) {
				ed.isNotDirty = true;

				if (formObj.onsubmit == null || formObj.onsubmit() != false)
					// CUSTOM: ADD AJAX SUPPORT FOR SAVE
					//formObj.submit();
					postAjax(ed, formObj);

				ed.nodeChanged();
			} else
				ed.windowManager.alert("Error: No form element found.");
		},

		_cancel : function() {
			var ed = this.editor, os, h = tinymce.trim(ed.startContent);

			// Use callback instead
			if (os = ed.getParam("save_oncancelcallback")) {
				ed.execCallback('save_oncancelcallback', ed);
				return;
			}

			ed.setContent(h);
			ed.undoManager.clear();
			ed.nodeChanged();
		}
	});

	// Register plugin
	tinymce.PluginManager.add('save', tinymce.plugins.Save);
})();

// CUSTOM: ADD AJAX SUPPORT FOR SAVE
function postAjax(ed, formObj){
	ed.controlManager.setDisabled('save', true);
	ed.setProgressState(1);
	var content = ed.getContent();
	var editorID = ed.id;
	var postUrl = $(formObj).attr("action");
	var pageID = formObj.pgID.value;
	$.post(postUrl, { pgID:pageID, pgBody:content },
		function(data){
			ed.setProgressState(0);
			if(data == "1"){
				alert("En error occured while saving.");
			}
	});
}