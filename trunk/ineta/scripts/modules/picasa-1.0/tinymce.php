<?php
if(!function_exists("adminEnabled")){
	error_log("Tentativa de acesso directo ao ficheiro ".__FILE__,0);
	// Silence is golden
	die('');
}
if(adminEnabled()):
	// Para carregar o código necessário para o TinyMCE
	getKernelInstance()->actionAddFunction("_init", function(){
		$kernel = getKernelInstance();
		
		// Define o caminho para a extensão
		$kernel->actionAddFunction("tinymceExtraPlugins", function($tinymceExtraConfigs){
			$tinymceExtraConfigs[]=array('name'=>'picasa', 'url'=>getBaseUrl().SCRIPTSDIR.getKernelInstance()->getPathFromRoot(__FILE__).'editor_plugin.js');
			return $tinymceExtraConfigs;
		},20,1);
		
		// Adiciona o extensão à lista de extensões
		$kernel->actionAddFunction("tinymcePlugins", function($tinymcePlugins){
			$tinymcePlugins[]='picasa';
			return $tinymcePlugins;
		},20,1);
		
		// Adiciona o botão ao editor
		$kernel->actionAddFunction("tinymceButtons", function($tinymceButtons){
			if(!is_array($tinymceButtons)){
				$tinymceButtons = array();
			}
			if(!is_array($tinymceButtons[3])){
				$tinymceButtons[3] = array();
			}
			$tinymceButtons[3][]='picasa';
			
			return $tinymceButtons;
		},20,1);
		
		
		if(isset($_REQUEST['tinyMcePicasaAlbumRequest'])):
			getKernelInstance()->actionAddFunction("tinyMcePopup", function(){
				$albuns = array();
				$msg = false;
				if($_REQUEST['username']){
					$feedURL = "http://picasaweb.google.com/data/feed/api/user/".urlencode(strip_tags($_REQUEST['username']))."?kind=album";
					
					libxml_use_internal_errors(true);
				    if($sxml = @simplexml_load_file($feedURL)){
				    	if($sxml->entry){
						    foreach($sxml->entry as $entry){
						    	$gphoto = $entry->children('http://schemas.google.com/photos/2007');
						    	$albuns[] = array('title'=> (string)$entry->title, 'link'=> (string)$entry->link[0]->attributes()->{'href'}, 'numphotos'=>$gphoto->numphotos);
						    }
				    	}
				    }
					if(libxml_get_last_error()){
				    	$msg = "Ocorreu um erro";
				    }
					libxml_clear_errors();
					    
				}
				?>
					<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
					<html xmlns="http://www.w3.org/1999/xhtml">
						<head>
							<title>{#PicasaDialog.title}</title>
							<?php getKernelInstance()->actionExecute("_head"); ?>
							<script type="text/javascript" src="<?php echo(TINYMCE_BASE_DIR); ?>tiny_mce_popup.js"></script>
							<script type="text/javascript" src="<?php echo(getBaseUrl().SCRIPTSDIR.getKernelInstance()->getPathFromRoot(__FILE__)); ?>js/dialog.js"></script>
							<link rel="stylesheet" type="text/css" href="<?php echo(getBaseUrl().SCRIPTSDIR.getKernelInstance()->getPathFromRoot(__FILE__)); ?>css/content.css" media="all" />
						</head>
						<body class="popup">
							<div>
								<fieldset>
									<legend>{#PicasaDialog.title}:</legend>
									<div class="content" style="text-align: left;">
										<form enctype="multipart/form-data" action="./?adminURL&tinyMcePicasaAlbumRequest" method="post">
											<div class="table">
												<div class="row">
													<div class="cell title">
														<label for="username">{#PicasaDialog.specify_username}: </label>
													</div>
													<div class="cell">
														<input id="username" name="username" type="text" value="<?php echo($_REQUEST['username']); ?>" />
														<input type="submit" value="{#PicasaDialog.get_albuns}" />
													</div>
												</div>
												<?php if(!empty($albuns)): ?>
														<div class="row">
															<div class="cell title">
																<label for="album">{#PicasaDialog.albuns}: </label>
															</div>
															<div class="cell">
																<select name="album" id="album">
																	<?php 
																		foreach($albuns as $album){
																			echo("<option value=\"{$album['link']}\">{$album['title']} ({$album['numphotos']} imagens)</option>");
																		}
																	?>
																</select>
															</div>
														</div>
													</div>
												<?php endif; ?>
											<?php if($msg){ echo("<div>$msg</div>"); } ?>
										</form>
									</div>
								</fieldset>
							</div>
							<div class="mceActionPanel">
								<input type="button" id="insert" value="{#PicasaDialog.insert}" onclick="PicasaDialog.insert();" />
								<input type="button" id="cancel" name="cancel" value="{#cancel}" onclick="tinyMCEPopup.close();" />
							</div>
						</body>
					</html>
				<?php 
				exit();
			});
		endif;
	});
	
endif;