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
			$tinymceExtraConfigs[]=array('name'=>'news', 'url'=>getBaseUrl().SCRIPTSDIR.'/'.getKernelInstance()->getPathFromRoot(__FILE__).'editor_plugin.js');
			return $tinymceExtraConfigs;
		},20,1);
		
		// Adiciona o extensão à lista de extensões
		$kernel->actionAddFunction("tinymcePlugins", function($tinymcePlugins){
			$tinymcePlugins[]='news';
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
			$tinymceButtons[3][]='news';
			
			return $tinymceButtons;
		},20,1);
		
		
		if(isset($_REQUEST['tinyMceNewsRequest'])):
			getKernelInstance()->actionExecuteIfNotExecuted("requestJQuery", "Não foi possível carregar a biblioteca jquery no módulo notícias.");
				
			getKernelInstance()->actionAddFunction("tinyMcePopup", function(){
				?>
					<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
					<html xmlns="http://www.w3.org/1999/xhtml">
						<head>
							<title>{#NewsDialog.title}</title>
							<?php getKernelInstance()->actionExecute("_head"); ?>
							<script type="text/javascript" src="<?php echo(TINYMCE_BASE_DIR); ?>tiny_mce_popup.js"></script>
							<script type="text/javascript" src="<?php echo(getBaseUrl().SCRIPTSDIR.'/'.getKernelInstance()->getPathFromRoot(__FILE__)); ?>js/dialog.js"></script>
							<link rel="stylesheet" type="text/css" href="<?php echo(getBaseUrl().SCRIPTSDIR.'/'.getKernelInstance()->getPathFromRoot(__FILE__)); ?>css/content.css" media="all" />
						</head>
						<body class="popup">
							<div>
								<fieldset>
									<legend>{#NewsDialog.title}:</legend>
									<div class="content" style="text-align: left;">
										<form enctype="multipart/form-data" action="#" method="post">
											<div class="table">
												<div class="row">
													<div class="cell title">
														<label for="show">{#NewsDialog.show}: </label>
													</div>
													<div class="cell">
														<select name="show" id="show" onchange="updateContext(this);">
															<option value="-2">{#NewsDialog.lastHighlightedNews}</option>
															<option value="-1" selected="selected">{#NewsDialog.lastNews}</option>
															<?php 
																$result='';
								
																if($query = MySQLOperations::getQueryResult("
																	SELECT `noticias`.`id` AS `newsid`,
													        			`noticias`.`titulo` AS `title`,
													        			`noticias`.`data_noticia` AS `date`
													        		FROM `noticias_tbl` `noticias`
													        			WHERE `noticias`.`removido` <> '1'
													        		ORDER BY `noticias`.`data_noticia` DESC")):
																	
													        		while($news = mysql_fetch_assoc($query)):
													        			echo("<option value=\"{$news['newsid']}\">{$news['title']} ({$news['date']})</option>");
													        		endwhile;
													        	endif;
															?>
														</select>
													</div>
												</div>
												<div class="row">
													<div class="cell title">
														<label for="how_many">{#NewsDialog.how_many} </label>
													</div>
													<div class="cell">
														<input id="how_many" name="how_many" type="text" value="0" onchange="forceDigits(this);" />&nbsp;{#NewsDialog.how_many_info}
													</div>
												</div>
												<div class="row">
													<div class="cell title">
														<label for="how_many_per_page">{#NewsDialog.how_many_per_page} </label>
													</div>
													<div class="cell">
														<input id="how_many_per_page" name="how_many_per_page" type="text" value="5" onchange="forceDigits(this);" />&nbsp;{#NewsDialog.how_many_per_page_info}
													</div>
												</div>
											</div>
										</form>
									</div>
								</fieldset>
							</div>
							<div class="mceActionPanel">
								<input type="button" id="insert" value="{#NewsDialog.insert}" onclick="NewsDialog.insert();" />
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