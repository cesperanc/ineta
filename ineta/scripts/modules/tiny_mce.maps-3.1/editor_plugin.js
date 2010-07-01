(function() {
	var pName = 'GoogleMaps';
	var pFunctionName = 'tinymce.plugins.'+pName+'_plugin';
	var matchTag = new RegExp("<!--"+pName+"(\s{0,})(.*?)\s{0,}-->","ig");
	
	tinymce.PluginManager.requireLangPack(pName);

	tinymce.create(pFunctionName, {
		url: '',
		init : function(ed, url) {
			this.url = url;
			/* Register command */
			ed.addCommand('mce'+pName, function() {
				ed.windowManager.open({
					file : './?adminURL&tinyMceGoogleMapsRequest',
					width : 640,
					height : 515,
					inline : 1
				}, {
					plugin_url : url /* Plugin absolute URL */
				});
			});

			/* Register button */
			ed.addButton(pName, {
				title : ed.getLang('GoogleMaps.desc'),
				cmd : 'mce'+pName,
				image : url + '/img/icon.gif'
			});

			/* plugin auxiliary methods */
			/* Convert the selected tag atributes in a full object */
			ed.getSelectedPluginGoogleMaps = function(tagName){
				if(tagName==undefined){
					tagName = pName;
				}
				var matchTag = new RegExp("<!--"+tagName+"(\s{0,})(.*?)\s{0,}-->","ig");
				var match, content = this.selection.getContent();
				if(match = matchTag.exec(content)){
					if(match[2]!=''){
						return this.parseStringToPluginGoogleMaps(match[2]);
					}
				}
				return false;
			};

			/* Convert the string in a useful object */
			ed.parseStringToPluginGoogleMaps = function(str){
				var obj = tinymce.util.JSON.parse(unescape(str));
				if(typeof(obj)=="object"){
					return obj;
				}
				return false;
			};

			/* Convert the object to a simple string */
			ed.parsePluginGoogleMapsToString = function(obj){
				if(typeof(obj)=="object"){
					var serialized = tinymce.util.JSON.serialize(obj);
					return (serialized!='{}')?escape(serialized):'';
				}
				return false;
			};

			/* Return an HTML image tag for the alt text given */
			ed.getImageHtmlGoogleMaps = function(alt){
				var width=0, height=0;
				if(alt){
					/* restore the image size from the object */
					var map = tinymce.util.JSON.parse(unescape(alt));
					if(map != null){
						if(!isNaN(parseInt(map.width))){
							width = parseInt(map.width);
						}
						if(!isNaN(parseInt(map.height))){
							height = parseInt(map.height);
						}
					}
				}
				return '<img src="'+url+'/img/trans.gif" alt="'+alt+'" class="'+pName+' mceItem mceItemNoResize" title="'+ed.getLang('GoogleMaps.desc')+'"'+(width>0?' width="'+width+'"':'')+(height>0?' height="'+height+'"':'')+' />';
			};
			
			/* Create the args for the item */
			ed.getImageArgsGoogleMaps = function(alt){
				var width=0, height=0, args = {};
				if(alt){
					/* restore the image size from the object */
					var map = tinymce.util.JSON.parse(unescape(alt));
					if(map != null){
						if(!isNaN(parseInt(map.width))){
							tinymce.extend(args, {
								'width' : parseInt(map.width)
							});
						}
						if(!isNaN(parseInt(map.height))){
							tinymce.extend(args, {
								'height' : parseInt(map.height)
							});
						}
					}
				}
				tinymce.extend(args, {
					'src' : url+'/img/trans.gif',
					'alt' : alt,
					'class' : pName+' mceItem',
					'title' : ed.getLang('GoogleMaps.desc')
				});
				
				return args;
			};

			/* Insert the object on the editor */
			ed.insertPluginGoogleMaps = function(obj){
				var data = ed.parsePluginGoogleMapsToString(obj);
				if(data!==false){
					var args = ed.getImageArgsGoogleMaps(data), el;
					
					/* Fixes crash in Safari */
					if (tinymce.isWebKit){
						ed.getWin().focus();
					}

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
				}
				return false;
			};

			this._handle(ed, url);
		},
		getInfo : function() {
			return {
				longname : 'Google Maps',
				author : 'Cláudio Esperança, Diogo Serra',
				authorurl : 'mailto:cesperanc@gmail.com,codedmind@gmail.com',
				version : "3.1"
			};
		},
		_handle : function(ed, url) {
			/* Load plugin specific CSS into editor */
			ed.onInit.add(function() {
				ed.dom.loadCSS(url + '/css/content.css');
			});

			/* Display a name instead if img in element path */
			ed.onPostRender.add(function() {
				if (ed.theme.onResolveName) {
					ed.theme.onResolveName.add(function(th, o) {
						if (o.node.nodeName == 'IMG') {
							if ( ed.dom.hasClass(o.node, pName) ){
								o.name = ed.getLang('GoogleMaps.title');
							}
						}

					});
				}
			});

			/* Replace code with images */
			ed.onBeforeSetContent.add(function(ed, o) {
				var match;
				while (match = matchTag.exec(o.content)){
					_html = ed.getImageHtmlGoogleMaps(((match[2])?match[2]:''));
					o.content = o.content.replace('<!--'+pName+((match[1]!='')?' ':'')+((match[2])?match[2]:'')+'-->', _html);
				}
			});

			/* Replace images with the code */
			ed.onPostProcess.add(function(ed, o) {
				if (o.get){
					o.content = o.content.replace(/<img[^>]+>/g, function(im) {
						if (im.indexOf('class="'+pName) !== -1) {
							var m, text = (m = im.match(/alt="\s{0,}(.*?)\s{0,}"/)) ? m[1] : '', map = tinymce.util.JSON.parse(unescape(text)), s;
							if(map!=null){
								/* Let's check the size of the image on the selection, and update the width and height map field */
								map.width = (m = parseInt((m = im.match(/width="(.*?)"/)) ? m[1] : null)) ? m : map.width;
								map.height = (m = parseInt((m = im.match(/height="(.*?)"/)) ? m[1] : null)) ? m : map.height;
								text = escape(tinymce.util.JSON.serialize(map));
							}
							im = '<!--'+pName+((text!='')?' '+text:'')+'-->';
						}
						return im;
					});
				}
			});

			/* Set active buttons if user selected the image on the editor */
			ed.onNodeChange.add(function(ed, cm, n) {
				cm.setActive(pName, n.nodeName === 'IMG' && ed.dom.hasClass(n, pName));
			});
		}
	});

	/* Register the plugin */
	tinymce.PluginManager.add(pName, eval(pFunctionName));
})();