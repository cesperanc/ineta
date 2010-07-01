var pName = tinyMCEPopup.getWindowArg("plugin_name");
if(pName!=undefined){
	tinyMCEPopup.requireLangPack(pName);
}

var FileManagerDialog = {
	init : function() {},
	insert : function(URL){
        var win = tinyMCEPopup.getWindowArg("window");

        /* insert information now */
        win.document.getElementById(tinyMCEPopup.getWindowArg("input")).value = URL;

        /* are we an image browser */
        if (typeof(win.ImageDialog) != "undefined") {
            /* we are, so update image dimensions... */
            if (win.ImageDialog.getImageData)
                win.ImageDialog.getImageData();

            /* ... and preview if necessary */
            if (win.ImageDialog.showPreviewImage)
                win.ImageDialog.showPreviewImage(URL);
        }

        /* close popup window */
        tinyMCEPopup.close();
	}
};

tinyMCEPopup.onInit.add(FileManagerDialog.init, FileManagerDialog);
