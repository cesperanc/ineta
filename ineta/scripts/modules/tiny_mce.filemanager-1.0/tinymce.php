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
		if(!$kernel->actionExecuted("requestJQuery") && !$kernel->actionExecute("requestJQuery")){
			$kernel->logMsg("Não foi possível carregar a biblioteca jquery no módulo tiny_mce.filemanager.");
		}
		
		// Define o caminho para a extensão
		getKernelInstance()->actionAddFunction("tinymceExtraPlugins", function($tinymceExtraConfigs){
			$tinymceExtraConfigs[]=array('name'=>'FileManager', 'url'=>getBaseUrl().SCRIPTSDIR.'/'.getKernelInstance()->getPathFromRoot(__FILE__).'editor_plugin.js');
			return $tinymceExtraConfigs;
		},20,1);
		
		// Adiciona o extensão à lista de extensões
		getKernelInstance()->actionAddFunction("tinymcePlugins", function($tinymcePlugins){
			$tinymcePlugins[]='FileManager';
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
			$tinymceButtons[3][]='FileManager';
			
			return $tinymceButtons;
		},20,1);
		
		// Adicionar a referência ao gestor de ficheiros ao tinymce
		getKernelInstance()->actionAddFunction("tinymceFileBrowserCallback", function($tinymceFileBrowserCallback=''){
			return 'tinyMCE.activeEditor.customFileBrowser';
		});
		
		
		if(isset($_REQUEST['tinyMceFileManagerRequest'])):
		
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
				
				$isImage = function(){
					switch(strtolower($_REQUEST['type'])){
						case 'image':
							return true;
						case 'media':
						case 'file':
						default: 
							
					}
					return false;
				};
				
				// Lista de páginas para o tinymce
				define('deleteFile', 'deleteFile');
				define('uploadFile', 'uploadFile');
				define('browseDir', 'browseDir');
				define('IMAGESUPLOADDIR', UPLOADSDIR.'images/');
				$dir = UPLOADSDIR;
				
				$msg=false;
				switch($_REQUEST['action']){
					case deleteFile:
						if(!empty($_REQUEST['file'])){
							$file = realpath(base64_decode($_REQUEST['file']));
							
							//echo("delete: $file");
							if(is_file($file) && is_writable($file) && unlink($file)){
								//$msg = "O ficheiro foi eliminado com sucesso!";
							}else{
								$msg = "Não foi possível eliminar o ficheiro!";
							}
						}
						break;
						
					case uploadFile:
						if(!empty($_FILES['filetoupload'])){
							if(!empty($_REQUEST['destinationDir'])){
								$tmpDir = base64_decode($_REQUEST['destinationDir']);
														
								if(is_readable($tmpDir) && is_dir($tmpDir) && stripos(realpath($tmpDir), realpath(UPLOADSDIR))===0){
									$dir = "$tmpDir/";
								}
							}
							$fileupload = new Upload("filetoupload");
							if(!$fileupload->copyFileToDir($_FILES['filetoupload']['tmp_name'][0], $dir)){
								$msg = "Não foi possivel copiar o ficheiro";
							}
						}
						break;
						
				}
				
				
					?>
						<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
						<html xmlns="http://www.w3.org/1999/xhtml">
							<head>
								<title>{#FileManagerDialog.title}</title>
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
										<?php if($isImage()): ?>
											<li id="gallery_tab" class="current"><span><a href="javascript:mcTabs.displayTab('gallery_tab','gallery_panel');" onmousedown="return false;">{#FileManagerDialog.gallery_tab}</a></span></li>
										<?php else: ?>
											<li id="files_tab" class="current"><span><a href="javascript:mcTabs.displayTab('files_tab','files_panel');" onmousedown="return false;">{#FileManagerDialog.files_tab}</a></span></li>
										<?php endif; ?>
										
										<li id="upload_tab"><span><a href="javascript:mcTabs.displayTab('upload_tab','upload_panel');" onmousedown="return false;">{#FileManagerDialog.upload_tab}</a></span></li>
										
										<?php if($isImage()): ?>
											<li id="remove_tab"><span><a href="javascript:mcTabs.displayTab('remove_tab','remove_panel');" onmousedown="return false;">{#FileManagerDialog.remove_tab}</a></span></li>
										<?php endif; ?>
									</ul>
								</div>
								<div class="panel_wrapper">
									<?php if($isImage()): ?>
										<div id="gallery_panel" class="panel current">
											<fieldset>
												<legend>{#FileManagerDialog.gallery_hint}:</legend>
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
																			    <a href="#" title="{#FileManagerDialog.gallery_insert_image_title} <?php echo(basename($file)); ?>">
																			        <?php echo("<img src=\"$file\" alt=\"".basename($file)."\" onclick=\"FileManagerDialog.insert(this.src);\" width=\"{$size['width']}\" height=\"{$size['height']}\" />"); ?>
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
									<?php else: ?>
										<div id="files_panel" class="panel current">
											<fieldset>
												<?php
													$iconsDir = getBaseUrl().SCRIPTSDIR.'/'.getKernelInstance()->getPathFromRoot(__FILE__)."img/"; 
													$dir = UPLOADSDIR;
													if($_REQUEST['action']==browseDir && !empty($_REQUEST['dir'])){
														$tmpDir = base64_decode($_REQUEST['dir']);
														
														if(is_readable($tmpDir) && is_dir($tmpDir) && stripos(realpath($tmpDir), realpath(UPLOADSDIR))===0){
															$dir = "$tmpDir/";
														}
													}
												?>
												<legend>{#FileManagerDialog.directory_files} <?php echo($dir); ?>:</legend>
												<div class="content" style="text-align:center">
													<ul class="files">
														<?php 
															// Open a known directory, and proceed to read its contents
															if(is_readable($dir) && is_dir($dir)){
																$ite=new DirectoryIterator($dir);
																
																foreach ($ite as $cur) {
																	$rootDir = strcasecmp(realpath($cur->getPath()),realpath(UPLOADSDIR))==0;
																	$allowedDir = stripos(realpath($cur->getPath()), realpath(UPLOADSDIR))===0;
																	if($cur->isReadable() && ($cur->isFile() || $cur->isDir())):
																		$file = $cur->getPathName();
																		?>
																			<li>
																				<?php if($cur->isFile()): ?>
																				    <a class="filelabel" href="<?php echo($file); ?>" onclick="FileManagerDialog.insert(this.href); return false;" title="{#FileManagerDialog.gallery_insert_file_title} <?php echo(basename($file)); ?>">
																				        <img src="<?php echo($iconsDir); ?>add.png" alt="{#FileManagerDialog.gallery_insert_file_alt}" /> <?php echo(basename($file)); ?>
																				    </a>
																				    <a class="fileoperations filedelete" href="#" onclick="if(confirm('{#FileManagerDialog.confirm_file_delete} <?php echo(basename($file)); ?>?')) window.location='./?adminURL&tinyMceFileManagerRequest&action=<?php echo(deleteFile); ?>&file=<?php echo(base64_encode($file)); ?>&type=<?php echo($_REQUEST['type']); ?>'; return false;" title="{#FileManagerDialog.gallery_delete_file_title} <?php echo(basename($file)); ?>">
																				        <img src="<?php echo($iconsDir); ?>delete.png" alt="{#FileManagerDialog.gallery_delete_file_alt}" />
																				    </a>
																				    <a class="fileoperations filepreview" href="<?php echo($file); ?>" title="{#FileManagerDialog.gallery_preview_file_title} <?php echo(basename($file)); ?>">
																				        <img src="<?php echo($iconsDir); ?>preview.png" alt="{#FileManagerDialog.gallery_file_preview_alt}" />
																				    </a>
																			    <?php elseif($allowedDir && $cur->getFilename()!='.' && (($rootDir && $cur->getFilename()!='..')||!$rootDir)): ?>
																			    	<?php 
																			    		$realPath = realpath($file); 
																			    		$realPath = UPLOADSDIR.substr($realPath, strlen(realpath(UPLOADSDIR))+1-strlen($realPath) ,strlen($realPath));
																			    	?>
																			    	<a class="dirlabel" href="./?adminURL&tinyMceFileManagerRequest&action=<?php echo(browseDir); ?>&dir=<?php echo(base64_encode($realPath)); ?>&type=<?php echo($_REQUEST['type']); ?>" title="{#FileManagerDialog.gallery_browse_directory_title} <?php echo(basename($file)); ?>">
																				        <img src="<?php echo($iconsDir); ?>folder.png" alt="{#FileManagerDialog.gallery_browse_directory_alt}" /><?php echo(basename($file)); ?>
																				    </a>
																				    
																			    <?php endif; ?>
																			</li>
																		<?php 
																	endif;
																}
															}
														?>
													</ul>
												</div>
											</fieldset>
										</div>
									<?php endif; ?>
									
									<div id="upload_panel" class="panel">
										<fieldset>
											<legend>{#FileManagerDialog.upload_hint}:</legend>
											<div class="content" style="text-align: left;">
												<form enctype="multipart/form-data" action="./?adminURL&tinyMceFileManagerRequest&action=<?php echo(uploadFile); ?>&type=<?php echo($_REQUEST['type']); ?>" method="post">
													<div>
														<input type="hidden" name="destinationDir" value="<?php echo(base64_encode($dir)); ?>" />
														<label for="filetoupload">{#FileManagerDialog.choose_file_to_upload}: </label>
														<input id="filetoupload" name="filetoupload[]" type="file" />
													</div>
													<div class="mceActionPanel">
														<input type="submit" value="{#FileManagerDialog.send_file}" />
													</div>
												</form>
											</div>
										</fieldset>
									</div>
									
									<?php if($isImage()): ?>
										<div id="remove_panel" class="panel">
											<fieldset>
												<legend>{#FileManagerDialog.remove_hint}:</legend>
												<div class="content" style="text-align:center">
													<ul class="gallery">
														<?php 
															$dir = IMAGESUPLOADDIR;
															if(is_readable($dir) && is_dir($dir)){
																foreach(glob("$dir*") as $file) {
																	if(($size = $imageSize($file))!=NULL){
																		?>
																			<li>
																			    <a href="#" title="{#FileManagerDialog.delete_image_title} <?php echo(basename($file)); ?>">
																			        <?php echo("<img src=\"$file\" alt=\"".basename($file)."\" onclick=\"if(confirm('{#FileManagerDialog.confirm_image_delete} ".basename($file)."?')) window.location='./?adminURL&tinyMceFileManagerRequest&action=".deleteFile."&file=".base64_encode($file)."&type=".$_REQUEST['type']."';\" width=\"{$size['width']}\" height=\"{$size['height']}\" />"); ?>
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
									<?php endif; ?>
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