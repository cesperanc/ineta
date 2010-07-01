var pName = tinyMCEPopup.getWindowArg("plugin_name");
if(pName!=undefined){
	tinyMCEPopup.requireLangPack(pName);
}

var NewsDialog = {
	init : function() {
		var ed = tinyMCEPopup.editor, object;
		object = ed.getSelectedPluginNews();
		
		if(object){
			
			var form = document.forms[0];
			if(form){
				if(object.how_many && form.how_many!=undefined){
					form.how_many.value = object.how_many;
				}
				if(object.how_many_per_page && form.how_many_per_page!=undefined){
					form.how_many_per_page.value = object.how_many_per_page;
				}
				if(object.show && form.show!=undefined){
					form.show.value = object.show;
					
					updateContext(form.show);
				}
			}
		}
	},
	insert : function(file){
		var ed = tinyMCEPopup.editor, f = document.forms[0], strHtml = '';
		var show = (f.show)?f.show.value:-1;
		var how_many = (f.how_many)?f.how_many.value:0;
		var how_many_per_page = (f.how_many_per_page)?f.how_many_per_page.value:0;

		var object = new Object();
		object.show = show;
		object.how_many = how_many;
		object.how_many_per_page = how_many_per_page;
		ed.insertPluginNews(object);
		tinyMCEPopup.close();
	}
};

tinyMCEPopup.onInit.add(NewsDialog.init, NewsDialog);

function updateContext(select){
	updateFields(select, document.getElementById("how_many"));
	updateFields(select, document.getElementById("how_many_per_page"));
}

function updateFields(select, input){
	if(select && input){
		input.disabled = (select.value>0);
		forceDigits(input);
	}
}

function forceDigits(input){
	if(input && (isNaN(input.value) || input.value<0)){
		input.value = 0;
	}
}
