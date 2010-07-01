var pName = tinyMCEPopup.getWindowArg("plugin_name");
if(pName!=undefined){
	tinyMCEPopup.requireLangPack(pName);
}

var PicasaDialog = {
	init : function() {
		var ed = tinyMCEPopup.editor, object;
		object = ed.getSelectedPluginPicasa();
		
		if(object){
			
			var form = document.forms[0];
			if(form){
				if(object.username && form.username!=undefined){
					form.username.value = object.username;
					
					if(object.album){
						if(form.album){
							form.album.value = object.album;
						}else{
							form.submit();
						}
					}
				}
			}
		}
	},
	insert : function(file){
		var ed = tinyMCEPopup.editor, f = document.forms[0], strHtml = '';
		var username = f.username.value;
		var album = (f.album)?f.album.value:false;

		if(username==null || username==""){
			tinyMCEPopup.alert(tinyMCEPopup.getLang('PicasaDialog.invalid_name'));
		} else {
			var object = new Object();
			object.username = username;
			if(album){
				object.album = album;
			}
			ed.insertPluginPicasa(object);
			tinyMCEPopup.close();
		}
	}
};

tinyMCEPopup.onInit.add(PicasaDialog.init, PicasaDialog);
