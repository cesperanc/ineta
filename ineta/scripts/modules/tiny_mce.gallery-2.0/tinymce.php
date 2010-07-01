<?php
if(!function_exists("adminEnabled")){
	error_log("Tentativa de acesso directo ao ficheiro ".__FILE__,0);
	// Silence is golden
	die('');
}
// Este módulo foi substituído pelo filemanager
if(false && adminEnabled()):
	// Para carregar o código necessário para o TinyMCE
	getKernelInstance()->actionAddFunction("_init", function(){
		$kernel = getKernelInstance();
		if(!$kernel->actionExecuted("requestJQuery") && !$kernel->actionExecute("requestJQuery")){
			$kernel->logMsg("Não foi possível carregar a biblioteca jquery no módulo tiny_mce.gallery.");
		}
		
		// Define o caminho para a extensão
		getKernelInstance()->actionAddFunction("tinymceExtraPlugins", function($tinymceExtraConfigs){
			$tinymceExtraConfigs[]=array('name'=>'ImgManager', 'url'=>getBaseUrl().SCRIPTSDIR.'/'.getKernelInstance()->getPathFromRoot(__FILE__).'editor_plugin.js');
			return $tinymceExtraConfigs;
		},20,1);
		
		// Adiciona o extensão à lista de extensões
		getKernelInstance()->actionAddFunction("tinymcePlugins", function($tinymcePlugins){
			$tinymcePlugins[]='ImgManager';
			return $tinymcePlugins;
		},20,1);
		
		// Adiciona o botão ao editor
		getKernelInstance()->actionAddFunction("tinymceButtons", function($tinymceButtons){
			if(!is_array($tinymceButtons)){
				$tinymceButtons = array();
			}
			if(!is_array($tinymceButtons[3])){
				$tinymceButtons[3] = array();
			}
			$tinymceButtons[3][]='ImgManager';
			
			return $tinymceButtons;
		},20,1);
		
		
		if(isset($_REQUEST['tinyMceImgManagerRequest'])):
		
			getKernelInstance()->actionAddFunction("tinyMcePopup", function(){
				$imageSize = function($file, $maxSize = 100){
					if(is_readable($file) && is_file($file) && list($width, $height, $type, $attr) = getimagesize($file)){
						if($width>=$height){
							$maxFindedSize = $width;
							$w = $maxSize;
							$h = $w*$height/$width;
						}else{
							$maxFindedSize = $height;
							$h = $maxSize;
							$w = $h*$width/$height;
						}
						if($maxFindedSize>$maxSize){
							$width = $w;
							$height = $h;
						}
					
						return array('width'=>round($width),'height'=>round($height));
					}
					return NULL;
				};
				
				// Lista de páginas para o tinymce
				define('deleteImage', 'deleteImage');
				define('uploadImage', 'uploadImage');
				define('IMAGESUPLOADDIR', UPLOADSDIR.'images/');
				
				$msg=false;
				switch($_REQUEST['action']){
					case deleteImage:
						if(!empty($_REQUEST['image'])){
							$image = realpath(base64_decode($_REQUEST['image']));
							
							//echo("delete: $image");
							if(is_file($image) && is_writable($image) && unlink($image)){
								//$msg = "A imagem foi eliminada com sucesso!";
							}else{
								$msg = "Não foi possível eliminar a imagem!";
							}
						}
						break;
						
					case uploadImage:
						if(!empty($_FILES['filetoupload'])){
							
							$imageupload = new Upload("filetoupload");
							if(!$imageupload->copyFileToDir($_FILES['filetoupload']['tmp_name'][0],IMAGESUPLOADDIR)){
								$msg = "Não foi possivel copiar o ficheiro";
							}
						}
						break;
						
				}
					?>
						<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
						<html xmlns="http://www.w3.org/1999/xhtml">
							<head>
								<title>{#ImgManagerDialog.title}</title>
								<?php getKernelInstance()->actionExecute("_head"); ?>
								<script type="text/javascript" src="<?php echo(TINYMCE_BASE_DIR); ?>tiny_mce_popup.js"></script>
								<script type="text/javascript" src="<?php echo(TINYMCE_BASE_DIR); ?>utils/mctabs.js"></script>
								<script type="text/javascript" src="<?php echo(getBaseUrl().SCRIPTSDIR.'/'.getKernelInstance()->getPathFromRoot(__FILE__)); ?>js/dialog.js"></script>
								<script type="text/javascript">
									/* <!-- */
										<?php 
											if($msg){
												echo("alert(\"$msg\")");
											}
										?>
									/* --> */
								</script>
								<link rel="stylesheet" type="text/css" href="<?php echo(getBaseUrl().SCRIPTSDIR.'/'.getKernelInstance()->getPathFromRoot(__FILE__)); ?>css/styles.css" media="all" />
							</head>
							<body>
								<div class="tabs">
									<ul>
										<li id="gallery_tab" class="current"><span><a href="javascript:mcTabs.displayTab('gallery_tab','gallery_panel');" onmousedown="return false;">{#ImgManagerDialog.gallery_tab}</a></span></li>
										<li id="upload_tab"><span><a href="javascript:mcTabs.displayTab('upload_tab','upload_panel');" onmousedown="return false;">{#ImgManagerDialog.upload_tab}</a></span></li>
										<li id="remove_tab"><span><a href="javascript:mcTabs.displayTab('remove_tab','remove_panel');" onmousedown="return false;">{#ImgManagerDialog.remove_tab}</a></span></li>
									</ul>
								</div>
								<div class="panel_wrapper">
									<div id="gallery_panel" class="panel current">
										<fieldset>
											<legend>{#ImgManagerDialog.gallery_hint}:</legend>
											<div class="content" style="text-align:center">
												<ul class="gallery">
													<?php 
														$dir = IMAGESUPLOADDIR;
														// Open a known directory, and proceed to read its contents
														if(is_readable($dir) && is_dir($dir)){
															foreach(glob("$dir*") as $file) {
																if(($size = $imageSize($file))!=NULL){
																	?>
																		<li>
																		    <a href="#" title="{#ImgManagerDialog.gallery_insert_image_title} <?php echo(basename($file)); ?>">
																		        <?php echo("<img src=\"$file\" alt=\"".basename($file)."\" onclick=\"ImgManagerDialog.insert(this.src);\" width=\"{$size['width']}\" height=\"{$size['height']}\" />"); ?>
																		    </a>
																		</li>
																	<?php 
																}
															}
														}
													?>
												</ul>
											</div>
										</fieldset>
									</div>
									<div id="upload_panel" class="panel">
										<fieldset>
											<legend>{#ImgManagerDialog.upload_hint}:</legend>
											<div class="content" style="text-align: left;">
												<form enctype="multipart/form-data" action="./?adminURL&tinyMceImgManagerRequest&action=<?php echo(uploadImage); ?>" method="post">
													<div>
														<label for="filetoupload">{#ImgManagerDialog.choose_file_to_upload}: </label>
														<input id="filetoupload" name="filetoupload[]" type="file" />
													</div>
													<div class="mceActionPanel">
														<input type="submit" value="{#ImgManagerDialog.send_file}" />
													</div>
												</form>
											</div>
										</fieldset>
									</div>
									<div id="remove_panel" class="panel">
										<fieldset>
											<legend>{#ImgManagerDialog.remove_hint}:</legend>
											<div class="content" style="text-align:center">
												<ul class="gallery">
													<?php 
														$dir = IMAGESUPLOADDIR;
														if(is_readable($dir) && is_dir($dir)){
															foreach(glob("$dir*") as $file) {
																if(($size = $imageSize($file))!=NULL){
																	?>
																		<li>
																		    <a href="#" title="{#ImgManagerDialog.delete_image_title} <?php echo(basename($file)); ?>">
																		        <?php echo("<img src=\"$file\" alt=\"".basename($file)."\" onclick=\"if(confirm('{#ImgManagerDialog.confirm_image_delete} ".basename($file)."?')) window.location='./?adminURL&tinyMceImgManagerRequest&action=".deleteImage."&image=".base64_encode($file)."';\" width=\"{$size['width']}\" height=\"{$size['height']}\" />"); ?>
																		    </a>
																		</li>
																	<?php 
																}
															}
														}
													?>
												</ul>
											</div>
										</fieldset>
									</div>
								</div>
								<div class="mceActionPanel">
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