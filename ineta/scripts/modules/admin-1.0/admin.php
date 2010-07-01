<?php
if(!function_exists("adminEnabled")){
	error_log("Tentativa de acesso directo ao ficheiro ".__FILE__,0);
	// Silence is golden
	die('');
}
if(adminEnabled()):
	// Para carregar o código necessário para o TinyMCE
	getKernelInstance()->actionAddFunction("_init", function(){
		
		// Select the buttons to show on the editor
		getKernelInstance()->actionAddFunction("tinymceButtons", function($tinymceButtons){
			return array(
							  array(/*'save',
									'newdocument',
									'|',*/
									'bold',
									'italic',
									'underline',
									'strikethrough',
							  		'sub',
									'sup',
									'|',
									'justifyleft',
									'justifycenter',
									'justifyright',
									'justifyfull',
									'|',
									'bullist',
									'numlist',
									'|',
									'outdent',
									'indent',
									'blockquote',
									'|',
									'link',
									'unlink',
									'anchor',
									'image',
									'charmap',
									'emotions',
									'media',
									'advhr',
							  		'pagebreak'
									/*'styleselect',*/),
							  array('formatselect',
									'fontselect',
									'fontsizeselect',
									'forecolor',
									'backcolor',
									'removeformat',
									/*'cut',
									'copy',
									'paste',*/
									'pastetext',
									'pasteword',
									'cleanup',
									'|',
									'search',
									'replace',
									'|',
									'cite',
									'abbr',
									'acronym',
									'del',
									'ins',
									/*'undo',
									'redo',
									'|',*/
									/*'insertdate',
									'inserttime',*/
									),
							  array(/*'hr',
									'visualaid',
									'|',
									'iespell',
									'|',
									'print',
									'|',
									'ltr',
									'rtl',
									'|',*/
							  		'tablecontrols',
									'|',
									'styleprops',
									'attribs',
									'code',
									'|',
									'fullscreen',
									'preview',
									'|',
									'help'),
							  array(/*'insertlayer',
									'moveforward',
									'movebackward',
									'absolute',
									'|',
									'visualchars',
									'nonbreaking',
									'template',*/
									)
			);
			return true;
		},10,1);
		
		// Select the plugins to use in the editor
		getKernelInstance()->actionAddFunction("tinymcePlugins", function($tinymcePlugins){
			return array('pagebreak',
						'style',
						/*'layer',*/
						'table',
						/*'save',*/
						'advhr',
						'advimage',
						'advlink',
						'emotions',
						/*'iespell',*/
						'inlinepopups',
						/*'insertdatetime',*/
						'preview',
						'media',
						'searchreplace',
						'print',
						'contextmenu',
						'paste',
						/*'directionality',*/
						'fullscreen',
						/*'noneditable',*/
						'visualchars',
						'nonbreaking',
						'xhtmlxtras',
						/*'template',*/
						'advlist'
			);
			return true;
		},10,1);
	});
	
	/* Add the admin header */
	getKernelInstance()->actionAddFunction("_head", function(){
		?><link rel="stylesheet" type="text/css" href="./<?php echo adminURL();?>&amp;script=<?php echo(getKernelInstance()->getPathFromRoot(__FILE__)); ?>admin.css" media="all" /><?php
		getKernelInstance()->actionExecute("adminHead"); 
	});
	
	/* Add the body content*/
	getKernelInstance()->actionAddFunction("_body", function(){
		?>
			<div id="adWrap">
				<div id="adContent">
					<div id="adHead">
						<h1><a href="./" title="Clique para voltar ao site"><?php echo(PRODUCT_FULL_NAME); ?></a><small>Voltar ao site</small></h1>
						<div id="adUserInfo">Olá, <?php echo(User::getUser()->checkProperty("nome")); ?>. <a href="./<?php echo adminURL();?>&amp;action=logout" title="Clique para terminar a sua sessão">Terminar Sessão?</a></div>
					</div>
					<div id="adBody">
						<ul id="adMenu">
							<li class="adminStart <?php echo((!isset($_REQUEST['action'])) ?' currentPage':''); ?>"><a href="./<?php echo adminURL();?>">Inicio</a></li>
							<?php getKernelInstance()->actionExecute("adminMenu"); ?>
						</ul>
						<div id="adBodyContent">
							<?php
								if(!getKernelInstance()->actionExecute("adminContent")){
									?>
										<div><?php echo(getKernelInstance()->actionExecute("authenticationMessage")); ?>&nbsp;</div>
										<div>Bem-vindo à área administrativa</div>
									<?php
								}
							?>
						</div>
						<div class="clear"></div>
					</div>
				</div>
			</div>
		<?php 
	});
endif;