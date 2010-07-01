tinyMCEPopup.requireLangPack("ImgManager");

var ImgManagerDialog = {
	init : function() {
		var ed = tinyMCEPopup.editor, dom = ed.dom, n = ed.selection.getNode();
	
		tinyMCEPopup.resizeToInnerSize();
	},
	insert : function(file){
		var ed = tinyMCEPopup.editor, args = {}, el;
		
		tinyMCEPopup.restoreSelection();

		/* Fixes crash in Safari */
		if (tinymce.isWebKit){
			ed.getWin().focus();
		}
		tinymce.extend(args, {
			src : file
		});

		el = ed.selection.getNode();
		/* Insert the contents from the input into the document */
		if (el && el.nodeName == 'IMG') {
			ed.dom.setAttribs(el, args);
		} else {
			ed.execCommand('mceInsertContent', false, '<img id="__mce_tmp" />', {skip_undo : 1});
			ed.dom.setAttribs('__mce_tmp', args);
			ed.dom.setAttrib('__mce_tmp', 'id', '');
			ed.undoManager.add();
		}

		tinyMCEPopup.close();
	}
};

tinyMCEPopup.onInit.add(ImgManagerDialog.init, ImgManagerDialog);
