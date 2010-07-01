(function() {
	var pName = 'news';
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
					file : './?adminURL&tinyMceNewsRequest',
					width : 570,
					height : 170,
					inline : 1
				}, {
					plugin_url : url,
					plugin_name : pName
				});
			});

			/* Register button */
			ed.addButton(pName, {
				title : ed.getLang(pName+'.desc'),
				cmd : 'mce'+pName,
				image : url + '/img/icon.png'
			});

			/* plugin auxiliary methods */
			/* Convert the selected tag atributes in a full object */
			ed.getSelectedPluginNews = function(tagName){
				if(tagName==undefined){
					tagName = pName;
				}
				var matchTag = new RegExp("<!--"+tagName+"(\s{0,})(.*?)\s{0,}-->","ig");
				var match, content = this.selection.getContent();
				if(match = matchTag.exec(content)){
					if(match[2]!=''){
						return this.parseStringToPluginNews(match[2]);
					}
				}
				return false;
			};

			/* Convert the string in a useful object */
			ed.parseStringToPluginNews = function(str){
				var obj = tinymce.util.JSON.parse(unescape(str));
				if(typeof(obj)=="object"){
					return obj;
				}
				return false;
			};

			/* Convert the object to a simple string */
			ed.parsePluginNewsToString = function(obj){
				if(typeof(obj)=="object"){
					var serialized = tinymce.util.JSON.serialize(obj);
					return (serialized!='{}')?escape(serialized):'';
				}
				return false;
			};

			/* Return an HTML image tag for the alt text given */
			ed.getImageHtmlNews = function(alt){
				return '<img src="'+url+'/img/trans.gif" alt="'+alt+'" class="'+pName+' mceItem mceItemNoResize" title="'+ed.getLang(pName+'.desc')+'" />';
			};
			
			/* Create the args for the item */
			ed.getImageArgsNews = function(alt){
				var args = {};
				tinymce.extend(args, {
					'src' : url+'/img/trans.gif',
					'alt' : alt,
					'class' : pName+' mceItem mceItemNoResize',
					'title' : ed.getLang(pName+'.desc')
				});
				
				return args;
			};

			/* Insert the object on the editor */
			ed.insertPluginNews = function(obj){
				var data = ed.parsePluginNewsToString(obj);
				if(data!==false){
					var args = ed.getImageArgsNews(data), el;
					
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
				longname : 'Site News',
				author : 'Cláudio Esperança, Diogo Serra',
				authorurl : 'mailto:cesperanc@gmail.com,codedmind@gmail.com',
				version : "1.0"
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
								o.name = ed.getLang(pName+'.title');
							}
						}

					});
				}
			});

			/* Replace code with images */
			ed.onBeforeSetContent.add(function(ed, o) {
				var match;
				var content = o.content;
				while (match = matchTag.exec(o.content)){
					_html = ed.getImageHtmlNews(((match[2])?match[2]:''));
					content = content.replace('<!--'+pName+((match[1]!='')?' ':'')+((match[2])?match[2]:'')+'-->', _html);
				}
				o.content = content;
			});

			/* Replace images with the code */
			ed.onPostProcess.add(function(ed, o) {
				if (o.get){
					o.content = o.content.replace(/<img[^>]+>/g, function(im) {
						if (im.indexOf('class="'+pName) !== -1) {
							var m, text = (m = im.match(/alt="\s{0,}(.*?)\s{0,}"/)) ? m[1] : '';
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