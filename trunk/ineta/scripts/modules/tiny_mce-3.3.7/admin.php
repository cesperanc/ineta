<?php
if(!function_exists("getKernelInstance")){
	error_log("Tentiva de acesso directo ao ficheiro ".__FILE__,0);
	// Silence is golden
	die('');
}

if(adminEnabled()):

	define('TINYMCE_BASE_DIR', getBaseUrl().SCRIPTSDIR.getKernelInstance()->getPathFromRoot(__FILE__));
	
	getKernelInstance()->actionAddFunction("requestTinyMCE", function(){
		// Para adicionar o c¨®digo necess¨¢rio no head da p¨¢gina
		getKernelInstance()->actionAddFunction("_head", function(){
			$tinymcePlugins = array('pagebreak',
									'style',
									'layer',
									'table',
									'save',
									'advhr',
									'advimage',
									'advlink',
									'emotions',
									'iespell',
									'inlinepopups',
									'insertdatetime',
									'preview',
									'media',
									'searchreplace',
									'print',
									'contextmenu',
									'paste',
									'directionality',
									'fullscreen',
									'noneditable',
									'visualchars',
									'nonbreaking',
									'xhtmlxtras',
									'template',
									'advlist'
							  );
			$tinymceButtons = array(
								  array('save',
										'newdocument',
										'|',
										'bold',
										'italic',
										'underline',
										'strikethrough',
										'|',
										'justifyleft',
										'justifycenter',
										'justifyright',
										'justifyfull',
										'styleselect',
										'formatselect',
										'fontselect',
										'fontsizeselect'),
								  array('cut',
										'copy',
										'paste',
										'pastetext',
										'pasteword',
										'|',
										'search',
										'replace',
										'|',
										'bullist',
										'numlist',
										'|',
										'outdent',
										'indent',
										'blockquote',
										'|',
										'undo',
										'redo',
										'|',
										'link',
										'unlink',
										'anchor',
										'image',
										'cleanup',
										'help',
										'code',
										'|',
										'insertdate',
										'inserttime',
										'preview',
										'|',
										'forecolor',
										'backcolor'),
								  array('tablecontrols',
										'|',
										'hr',
										'removeformat',
										'visualaid',
										'|',
										'sub',
										'sup',
										'|',
										'charmap',
										'emotions',
										'iespell',
										'media',
										'advhr',
										'|',
										'print',
										'|',
										'ltr',
										'rtl',
										'|',
										'fullscreen'),
								  array('insertlayer',
										'moveforward',
										'movebackward',
										'absolute',
										'|',
										'styleprops',
										'|',
										'cite',
										'abbr',
										'acronym',
										'del',
										'ins',
										'attribs',
										'|',
										'visualchars',
										'nonbreaking',
										'template',
										'pagebreak')
							  );
			$tinymceLinkListURL = '';
			$tinymceImageListURL = '';
			$tinymceMediaListURL = '';
			$tinymceExtraConfigs = array();
			
			?>
				<script type="text/javascript" src="<?php echo(getBaseUrl().SCRIPTSDIR.getKernelInstance()->getPathFromRoot(__FILE__)); ?>tiny_mce.js"></script>
				<script type="text/javascript">
					tinyMCE.init({
						base : "<?php echo(getBaseUrl().getKernelInstance()->getPathFromRoot(__FILE__)); ?>",
						mode : "textareas",
						theme : "advanced",
						forced_root_block : false,
						convert_urls : false,
						theme_advanced_toolbar_location : "top",
						theme_advanced_toolbar_align : "left",
						theme_advanced_statusbar_location : "bottom",
						theme_advanced_resizing : true,
						dialog_type : "modal", 
						<?php
							// Plugins 
							$tinymcePlugins = getKernelInstance()->actionExecute("tinymcePlugins", $tinymcePlugins);
							echo(((!empty($tinymcePlugins))?"plugins : \"".implode(",",$tinymcePlugins):'')."\",");
							
							// Buttons
							$tinymceButtons = getKernelInstance()->actionExecute("tinymceButtons", $tinymceButtons);
							$i=1;
							foreach($tinymceButtons as $buttons){
								echo(((!empty($tinymcePlugins))?"\ntheme_advanced_buttons".($i++).": \"".implode(",",$buttons)."\",":''));	
							}
							
							// Extra configs
							$tinymceExtraConfigs = getKernelInstance()->actionExecute("tinymceExtraConfigs", $tinymceExtraConfigs);
							foreach($tinymceExtraConfigs as $tinymceConfig){
								if(!empty($tinymceConfig['name']) && isset($tinymceConfig['value'])){
									echo("\n".$tinymceConfig['name'].": ".$tinymceConfig['value'].",");	
								}	
							}
							
							// Links
							$tinymceLinkListURL = getKernelInstance()->actionExecute("tinymceLinkListURL", $tinymceLinkListURL);
							echo(((!empty($tinymceLinkListURL))?"external_link_list_url: \"$tinymceLinkListURL\",":''));
							
							// Images
							$tinymceImageListURL = getKernelInstance()->actionExecute("tinymceImageListURL", $tinymceImageListURL);
							echo(((!empty($tinymceImageListURL))?"external_image_list_url: \"$tinymceImageListURL\",":''));
							
							// Media
							$tinymceMediaListURL = getKernelInstance()->actionExecute("tinymceMediaListURL", $tinymceMediaListURL);
							echo(((!empty($tinymceMediaListURL))?"external_link_list_url: \"$tinymceMediaListURL\",":''));
							
							// file manager
							$tinymceFileBrowserCallback = getKernelInstance()->actionExecute("tinymceFileBrowserCallback", $tinymceFileBrowserCallback);
							echo(((!empty($tinymceFileBrowserCallback))?"file_browser_callback: \"$tinymceFileBrowserCallback\",":''));
						?>
						language : "pt"
					});
					<?php 
						// Extra plugins
						$tinymceExtraplugins = getKernelInstance()->actionExecute("tinymceExtraPlugins", $tinymceExtraConfigs);
						foreach($tinymceExtraplugins as $tinymceExtraplugin){
							if(!empty($tinymceExtraplugin['name']) && isset($tinymceExtraplugin['url'])){
								echo("\ntinymce.PluginManager.load('".$tinymceExtraplugin['name']."', '".$tinymceExtraplugin['url']."');");	
							}	
						}
						
					?>
				</script>
			<?php 
			return true;
		}, 10);
	});
	
	getKernelInstance()->actionAddFunction("_init", function(){
		getKernelInstance()->actionExecute("tinyMcePopup");
	}, 50);
	
endif;