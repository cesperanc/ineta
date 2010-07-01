<?php
if(!function_exists("getKernelInstance")){
	error_log("Tentativa de acesso directo ao ficheiro ".__FILE__,0);
	// Silence is golden
	die('');
}
date_default_timezone_set('Europe/Lisbon');
/**
 * Àrea administrativa
 */
if(adminEnabled()):
	// Adiciona os itens ao menu na área administrativa
	getKernelInstance()->actionAddFunction("adminMenu", function(){
		?>
			<li class="adminNewsModule <?php echo(strcasecmp($_REQUEST['action'],'adminNews')==0?' currentPage':''); ?>"><a href="./<?php echo adminURL();?>&amp;action=adminNews">Notícias</a></li>	
		<?php
		return true;
	});
	
	// Carregar itens apenas se estamos na nossa área
	if(strcasecmp($_REQUEST['action'],'adminNews')==0):
		getKernelInstance()->actionAddFunction("_init", function(){
			$kernel = getKernelInstance();
			
			if(!$kernel->actionExecuted("requestJQuery") && !$kernel->actionExecute("requestJQuery")){
				$kernel->logMsg("Não foi possível carregar a biblioteca jquery no módulo notícias.");
			}
			
			// 	Carregar apenas o que precisamos consoante a secção a apresentar
			switch(strtolower($_REQUEST['operation'])){
				/* Formulário de edição de notícias */
				case 'editnews':
					// Para carregar o código necessário para o TinyMCE
					$kernel->actionExecute("requestTinyMCE");					
				break;
			}
		});
		
		// Adiciona os estilos específicos do módulo
		getKernelInstance()->actionAddFunction("adminHead", function(){
			?><link rel="stylesheet" type="text/css" href="./<?php echo adminURL();?>&amp;script=<?php echo(getKernelInstance()->getPathFromRoot(__FILE__)); ?>admin.css" media="all" /><?php
			
			// Carregar apenas o que precisamos consoante a secção a apresentar
			switch(strtolower($_REQUEST['operation'])){
				
				/* Formulário de edição de notícias */
				case 'editnews':
					?>
						<script type="text/javascript">
							/* <![CDATA[ */
								$(document).ready(function (event){
									
									$("input[name=formAction][value=Cancelar]").click(function(event) {
								        if(confirm("Tem a certeza de que pretende cancelar? Quaisquer alterações que tenha efectuado serão perdidas.")){
								        	window.location = "./<?php echo adminURL();?>&amp;action=adminNews";
								        }
								    });

									$("input[name=formAction][value=Eliminar]").click(function(event) {
								        if(confirm("Tem a certeza de que pretende eliminar esta notícia? Esta acção é permanente e não pode ser anulada.")){
								        	$("input[name=subOperation]").val('delete');
								        	$("#editnewsform").submit();
								        }
								    });

									/* Corrigir a data para um formato mais simpático */
									$("input[name=data]").blur(function(event) {
										var val = new Date($(this).val());
								        
								        if(!/Invalid|NaN/.test(val)){
									        $(this).val(
											    val.getFullYear()+'/'+
												(((val.getMonth()+1)<10)?'0':'')+(val.getMonth()+1)+'/'+
												(((val.getDate()<10)?'0':'')+val.getDate())+' '+
												(((val.getHours()<10)?'0':'')+val.getHours())+':'+
												(((val.getMinutes()<10)?'0':'')+val.getMinutes())+':'+
												(((val.getSeconds()<10)?'0':'')+val.getSeconds())
											);
								        }
								    });

									$("input[name=formAction][value=Guardar]").click(function(event) {
										var error = false;
										
										$("#editnewsform").validate({
											rules: {
												titulo: {
										    		required: true
										    	},
										    	data:{
										    		required: true,
													date:true
												}
										  	}
										});
									});
								});
							/* ]]> */
						</script>
					<?php							
				break;
			}
		});
	
		// Adiciona o conteúdo à notícia administrativa
		getKernelInstance()->actionAddFunction("adminContent", function(){
			switch(strtolower($_REQUEST['operation'])){
				/* Guarda ou cancela  */
				case 'submitnews':
					if(!empty($_POST)){
						if(strcasecmp($_POST['subOperation'],'save')==0){
							$error=false;
							$newsId = $_POST['newsId'];
							$isInsert=false;
							
							if(is_numeric($newsId) && $newsId>0){
								$operations = new MySQLOperations('noticias_tbl', 'id', $newsId);
								if(($operations->setProperty('titulo', "'".mysql_escape_string($_POST['titulo'])."'"))===false){
									getKernelInstance()->logMsg("Não foi possível definir o titulo da notícia. (".mysql_error().")");
									$error = true;
								}
								$isInsert=false;
							}else{
								$operations = new MySQLOperations('noticias_tbl');
								if(($newsId = $operations->insert('titulo', "'".mysql_escape_string($_POST['titulo'])."'"))===false){
									getKernelInstance()->logMsg("Não foi possível inserir a notícia com o titulo especificado. (".mysql_error().")");
									$error = true;
								}
								$isInsert=true;
							}
					
							if(($operations->setProperty('data_noticia', "'".mysql_escape_string(gmdate("Y-m-d H:i:s", strtotime($_POST['data'])))."'"))===false){
								getKernelInstance()->logMsg("Não foi possível definir a data da notícia. (".mysql_error().")");
								$error = true;
							}
							
							if(($operations->setProperty('conteudo',"'".mysql_escape_string($_POST['conteudo'])."'"))===false){
								getKernelInstance()->logMsg("Não foi possível definir o conteúdo da notícia. (".mysql_error().")");
								$error = true;
							}
							
							if(($operations->setProperty('destaque',"'".($_POST['destaque']==1?'1':'0')."'"))===false){
								getKernelInstance()->logMsg("Não foi possível definir o destaque da notícia. (".mysql_error().")");
								$error = true;
							}
							
							if(($operations->setProperty('removido',"'".($_POST['eliminada']==1?'1':'0')."'"))===false){
								getKernelInstance()->logMsg("Não foi possível definir o destaque da notícia. (".mysql_error().")");
								$error = true;
							}
							
							?>
								<div class="newsEdit wrap">
									<div class="header">
										<?php if($error): ?>
											<h2>Erro</h2>
											<small>Ocorreu um erro ao processar o seu pedido. Consulte o administrador para mais informações.</small>
										<?php else: ?>
											<h2>Notícia <?php echo(($isInsert)?'inserida':'actualizada'); ?>...</h2>
											<small>Deseja voltar a <a href="./<?php echo adminURL();?>&amp;action=adminNews&amp;operation=editnews&amp;newsId=<?php echo($newsId); ?>" title="Clique para voltar a editar a notícia">editar a notícia</a>,  <a href="./?action=showNews&newsId=<?php echo($newsId); ?>" title="Clique para visualizar a notícia">ver a notícia</a> ou voltar <a href="./<?php echo adminURL();?>&amp;action=adminNews" title="Clique para voltar à lista de notícias">lista de notícias</a>.</small>
										<?php endif; ?>
									</div>
								</div>
							<?php
						}else if(strcasecmp($_POST['subOperation'],'delete')==0){
							$newsId = $_POST['newsId'];
							
							?>
								<div class="newsEdit wrap">
									<div class="header">
										<?php
											if(is_numeric($newsId) && $newsId>0){
												
												if((MySQLOperations::setProperty('removido','1', 'noticias_tbl', 'id', $newsId))===false){
													getKernelInstance()->logMsg("Não foi possível marcar a notícia $newsId como eliminada (".mysql_error().").");
													?>
														<h2>Erro</h2>
														<small>Ocorreu um erro ao processar o seu pedido. Consulte o administrador para mais informações.</small>
													<?php 
												}else{
													?>
														<h2>Notícia marcada como eliminada</h2>
														<small>Voltar à <a href="./<?php echo adminURL();?>&amp;action=adminNews" title="Clique para voltar à lista de notícias">lista de notícias</a></small>
													<?php 	
												}
											}else{
												?>
													<h2>Erro</h2>
													<small>A identificação da notícia é inválida.</small>
												<?php 
											}
										?>
										
									</div>
								</div>
							<?php
						}else{
							?>
								<div class="newsEdit wrap">
									<div class="header">
										<h2>Erro!</h2>
										<small>A operação não foi reconhecida.</small>
									</div>
								</div>
							<?php
						}
					}else{
						?>
							<div class="newsEdit wrap">
								<div class="header">
									<h2>Erro!</h2>
									<small>Sem dados para submeter. Voltar à <a href="./<?php echo adminURL();?>&amp;action=adminNews" title="Clique para voltar à lista de notícias">lista de notícias</a></small>
								</div>
							</div>
						<?php
					}
					break;
				
				/* Formulário de edição de notícias */
				case 'editnews':
					$newsId = (is_numeric($_REQUEST['newsId']))?$_REQUEST['newsId']:false;
					$newsTitle = '';
					$newsContent = '';
					$newsDate = gmdate("Y/m/d H:i:s");
					$deletedNews = '';
					$highlightedNews = '';
					
					if($query = MySQLOperations::getQueryResult("
						SELECT `noticias`.`id` AS `newsid`,
		        			`noticias`.`titulo` AS `title`,
		        			`noticias`.`conteudo` AS `content`,
		        			`noticias`.`data_noticia` AS `date`,
		        			`noticias`.`removido` AS `deleted`,
		        			`noticias`.`destaque` AS `highlighted`
		        		FROM `noticias_tbl` `noticias`
		        		WHERE (`noticias`.`id` = '$newsId' ) 
		        		LIMIT 1
		        	")){
	        			if($news = mysql_fetch_assoc($query)){
							$newsTitle = $news['title'];
							$newsContent = $news['content'];
							$newsDate = gmdate("Y/m/d H:i:s", strtotime($news['date']));
							$deletedNews = $news['deleted'];
							$highlightedNews = $news['highlighted'];
	        			}
		        	}
					
					if(!empty($_POST)){
						$newsTitle = $_POST['titulo'];
						$newsContent = $_POST['conteudo'];
						$newsDate = $_POST['data'];
						$deletedNews = $_POST['eliminar'];
						$highlightedNews = $_POST['destacada'];
					}
						?>
							<div class="newsEdit wrap">
								<div class="header">
									<h2><?php 
											// TODO alterar a URL para mostrar a notícia
											echo(((!empty($newsTitle))?"A editar notícia <a href=\"./?action=showNews&amp;newsId={$newsId}\" title=\"Clique para visualizar a notícia\">{$newsTitle}</a>:":"A inserir uma nova notícia")); 
										?></h2>
									<small>Preencha o formulário para construir a sua notícia.</small>
								</div>
								<form id="editnewsform" action="./<?php echo(adminURL()); ?>&amp;action=adminNews&amp;operation=submitnews" method="post" enctype="application/x-www-form-urlencoded">
									<div class="content">
										<input type="hidden" name="newsId" value="<?php echo($newsId); ?>" />
										<input type="hidden" name="subOperation" value="save" />
										<p>
											<label for="titulo">Título: </label>
											<input id="titulo" name="titulo" value="<?php echo($newsTitle);?>" title="Especifique o título" />
										</p>
										<p>
											<label for="data">Data: </label>
											<input id="data" name="data" value="<?php echo($newsDate);?>" title="Especifique a data no formato AAAA/MM/DD HH:MM:SS" />
										</p>
										<fieldset>
											<legend>Em destaque</legend>
												<input type="radio" name="destaque" id="destaque1" value="1"<?php echo($highlightedNews=="1"?' checked="checked"':''); ?> />
												<label for="destaque1">Sim</label><br />
												<input type="radio" name="destaque" id="destaque0" value="0"<?php echo($highlightedNews!="1"?' checked="checked"':''); ?> />
												<label for="destaque0">Não</label>
										</fieldset>
										
										<p><textarea id="conteudo" name="conteudo" rows="15" cols="80" style="width: 80%" class="tinymce"><?php echo($newsContent); ?></textarea></p>
										<fieldset>
											<legend>Eliminada</legend>
												<input type="radio" name="eliminada" id="eliminada1" value="1"<?php echo($deletedNews=="1"?' checked="checked"':''); ?> />
												<label for="eliminada1">Sim</label><br />
												<input type="radio" name="eliminada" id="eliminada0" value="0"<?php echo($deletedNews!="1"?' checked="checked"':''); ?> />
												<label for="eliminada0">Não</label>
										</fieldset>
										<p>
											<input type="submit" name="formAction" value="Guardar" />
											<input type="button" name="formAction" value="Cancelar" />
											<?php if($newsId!==false && is_numeric($newsId) && $newsId>0 && $deletedNews!="1"): ?>
												<input type="button" name="formAction" value="Eliminar" />
											<?php endif; ?>
											<input type="reset" name="formReset" value="Repor" />
										</p>
									</div>
								</form>
							</div>
						<?php										
					break;
				
				/* Apresentação da lista de notícias na área administrativa */
				default:
					
					?>
						<div class="newsList wrap">
							<div class="header">
								<h2>Notícias</h2>
								<small>Seleccione a notícia a editar, ou pressione <cite>Inserir notícia</cite> para inserir uma nova notícia:</small>
							</div>
							<table class="newsList" cellspacing="0" summary="Lista de notícias">
								 <thead>
									  <tr>
										<th>Título</th>
									  	<th style="width: 20px;">ID</th>
									  	<th style="width: 100px;">Em destaque</th>
										<th style="width: 130px;">Data</th>
									  </tr>
								  </thead>
								  <tfoot>
									  	<tr>
									  		<th colspan="4"><a href="./<?php echo adminURL();?>&amp;action=adminNews&amp;operation=editnews&amp;newsId=-1" title="Clique para adicionar uma nova notícia">Inserir notícia</a></th>
								  		</tr>
								  </tfoot>
								  <tbody>
								  	<?php 
								  		$result='';
										
										if($query = MySQLOperations::getQueryResult("
											SELECT `noticias`.`id` AS `newsid`,
							        			`noticias`.`titulo` AS `title`,
							        			`noticias`.`conteudo` AS `content`,
							        			`noticias`.`data_noticia` AS `date`,
							        			`noticias`.`removido` AS `deleted`,
							        			`noticias`.`destaque` AS `highlighted`
							        		FROM `noticias_tbl` `noticias`
							        		ORDER BY `noticias`.`data_noticia` DESC")):
											
							        		while($news = mysql_fetch_assoc($query)):
							        			$result.='<tr>';
							        			$result.='<td class="newsTitle'.(($news['deleted']=='1')?' deleted':'').'"><a title="Clique para editar '.$news['title'].'" href="./'.adminURL().'&amp;action=adminNews&amp;operation=editnews&amp;newsId='.$news['newsid'].'">'.$news['title'].'&nbsp;</a></td>';
							        			$result.='<td class="newsId'.(($news['deleted']=='1')?' deleted':'').'">'.$news['newsid'].'</td>';
							        			$result.='<td class="newsState'.(($news['deleted']=='1')?' deleted':'').'">'.(($news['highlighted']=='1')?'Sim':'Não').'</td>';
							        			$result.='<td class="newsDate'.(($news['deleted']=='1')?' deleted':'').'">'.$news['date'].'</td>';
							        			$result.='</tr>';
							        		endwhile;
							        	endif;
							        	
										echo((!empty($result))?$result:'<tr><td colspan="4">Sem notícias</td></tr>');
									?>
								  </tbody>
							</table>
						</div>
					<?php
			}
			 
			return true; 
		});
	endif;
endif;