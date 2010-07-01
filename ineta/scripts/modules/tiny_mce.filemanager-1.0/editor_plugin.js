(function() {
	var pName = 'FileManager';
	var pFunctionName = 'tinymce.plugins.'+pName+'_plugin';
	
	/* Load plugin specific language pack */
	tinymce.PluginManager.requireLangPack(pName);

	tinymce.create(pFunctionName, {
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 * This call is done before the editor instance has finished it's initialization so use the onInit event
		 * of the editor instance to intercept that event.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
		init : function(ed, url) {
			
			ed.customFileBrowser = function(field_name, url2, type, win) {
				ed.windowManager.open({
					file : './?adminURL&tinyMceFileManagerRequest&type='+type,
					width : 700,
					height : 350,
					inline : 1,
					close_previous : "no"
				}, {
					plugin_url : url,
					plugin_name : pName,
					window : win,
			        input : field_name
				});
			    return false;
			};

			/* Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceFileManager'); */
			/*
			ed.addCommand('mce'+pName, function() {
				ed.customFileBrowser('');
			});
			*/

			/* Register example button */
			/*
			ed.addButton(pName, {
				title : pName+'.desc',
				cmd : 'mce'+pName,
				image : url + '/img/icon.png'
			});
			*/
		},

		/**
		 * Creates control instances based in the incomming name. This method is normally not
		 * needed since the addButton method of the tinymce.Editor class is a more easy way of adding buttons
		 * but you sometimes need to create more complex controls like listboxes, split buttons etc then this
		 * method can be used to create those.
		 *
		 * @param {String} n Name of the control to create.
		 * @param {tinymce.ControlManager} cm Control manager to use inorder to create new control.
		 * @return {tinymce.ui.Control} New control instance or null if no control was created.
		 */
		createControl : function(n, cm) {
			return null;
		},

		/**
		 * Returns information about the plugin as a name/value array.
		 * The current keys are longname, author, authorurl, infourl and version.
		 *
		 * @return {Object} Name/value array containing information about the plugin.
		 */
		getInfo : function() {
			return {
				longname : 'File Manager',
				author : 'Cláudio Esperança, Diogo Serra',
				authorurl : 'mailto:cesperanc@gmail.com,codedmind@gmail.com',
				version : "1.0"
			};
		}
	});

	// Register the plugin
	tinymce.PluginManager.add(pName, eval(pFunctionName));
})();