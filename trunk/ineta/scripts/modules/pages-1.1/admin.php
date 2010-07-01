<?php
if(!function_exists("getKernelInstance")){
	error_log("Tentativa de acesso directo ao ficheiro ".__FILE__,0);
	// Silence is golden
	die('');
}

/**
 * Àrea administrativa
 */
if(adminEnabled()):
	// Adiciona os itens ao menu na área administrativa
	getKernelInstance()->actionAddFunction("adminMenu", function(){
		?>
			<li class="adminPagesModule <?php echo(strcasecmp($_REQUEST['action'],'adminPage')==0?' currentPage':''); ?>"><a href="./<?php echo adminURL();?>&amp;action=adminPage">Páginas</a></li>	
		<?php
		return true;
	});
	
	getKernelInstance()->actionAddFunction("_init", function(){
		// Adicionar a lista de páginas aos links do tinymce
		getKernelInstance()->actionAddFunction("tinymceLinkListURL", function($tinymceLinkListURL=''){
			return './'.adminURL().'&amp;script='.getKernelInstance()->getPathFromRoot(__FILE__).basename(__FILE__).'&amp;tinyMcePagesList';
		});
	});
	
	// Carregar itens apenas se estamos na nossa área
	if(strcasecmp($_REQUEST['action'],'adminPage')==0):
		getKernelInstance()->actionAddFunction("_init", function(){
			$kernel = getKernelInstance();
			
			if(!$kernel->actionExecuted("requestJQuery") && !$kernel->actionExecute("requestJQuery")){
				$kernel->logMsg("Não foi possível carregar a biblioteca jquery no módulo pages.");
			}
			
			// 	Carregar apenas o que precisamos consoante a secção a apresentar
			switch(strtolower($_REQUEST['operation'])){
				/* Formulário de edição de páginas */
				case 'editpage':
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
				
				/* Formulário de edição de páginas */
				case 'editpage':
					?>
						<script type="text/javascript">
							/* <![CDATA[ */
								$(document).ready(function (event){
									$("input[name=formAction][value=Cancelar]").click(function(event) {
								        if(confirm("Tem a certeza de que pretende cancelar? Quaisquer alterações que tenha efectuado serão perdidas.")){
								        	window.location = "./<?php echo adminURL();?>&amp;action=adminPage";
								        }
								    });

									$("input[name=formAction][value=Eliminar]").click(function(event) {
								        if(confirm("Tem a certeza de que pretende eliminar esta página? Esta acção é permanente e não pode ser anulada.")){
								        	$("input[name=subOperation]").val('delete');
								        	$("#editpageform").submit();
								        }
								    });

									$("select[name=pai]").change(function(event) {
										$("#editpageform").attr('action','./<?php echo adminURL();?>&action=adminPage&operation=editpage');
							        	$("input[name=subOperation]").val('changeParent');
							        	$("#editpageform").submit();
								    });

									$("input[name=formAction][value=Guardar]").click(function(event) {
										var error = false;
										
										$("#editpageform").validate({
											rules: {
												titulo: {
										    		required: true
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
	
		// Adiciona o conteúdo à página administrativa
		getKernelInstance()->actionAddFunction("adminContent", function(){
			switch(strtolower($_REQUEST['operation'])){
				/* Guarda ou cancela  */
				case 'submitpage':
					if(!empty($_POST)){
						if(strcasecmp($_POST['subOperation'],'save')==0){
							$error=false;
							$pageId = $_POST['pageId'];
							$isInsert=false;
							
							if(is_numeric($pageId) && $pageId>0){
								$operations = new MySQLOperations('paginas_tbl', 'id', $pageId);
								if(($operations->setProperty('titulo', "'".mysql_escape_string($_POST['titulo'])."'"))===false){
									getKernelInstance()->logMsg("Não foi possível definir o titulo da página. (".mysql_error().")");
									$error = true;
								}
								if(($oldParentId = $operations->getProperty('id_pai'))===false){
									getKernelInstance()->logMsg("Não foi possível obter o id do pai anterior da página. (".mysql_error().")");
									$error = true;
								}else if(($oldOrder = $operations->getProperty('ordem'))===false){
									getKernelInstance()->logMsg("Não foi possível obter a ordem anterior da página. (".mysql_error().")");
									$error = true;
								}else if(($operations->setProperty('id_pai', (is_null($_POST['pai'])?'NULL':mysql_escape_string($_POST['pai']))))===false){
									getKernelInstance()->logMsg("Não foi possível definir o pai da página na actualização. (".mysql_error().")");
									$error = true;
								}else if(($operations->getQueryResult("CALL paginas_tbl_reorder(".$pageId.", ".(is_null($oldParentId)?'NULL':$oldParentId).", $oldOrder, ".$pageId.", ".(is_null($_POST['pai'])?'NULL':$_POST['pai']).", ".(!is_numeric($_POST['ordem'])?'NULL':$_POST['ordem']).");"))===false){
									getKernelInstance()->logMsg("Não foi possível executar o procedimento para a reordenação das páginas na actualização. (".mysql_error().")");
									$error = true;
								}
								$isInsert=false;
							}else{
								$operations = new MySQLOperations('paginas_tbl');
								if(($pageId = $operations->insert('titulo', "'".mysql_escape_string($_POST['titulo'])."'"))===false){
									getKernelInstance()->logMsg("Não foi possível inserir a página com o titulo especificado. (".mysql_error().")");
									$error = true;
								}
								if(($operations->setProperty('id_pai', (is_null($_POST['pai'])?'NULL':mysql_escape_string($_POST['pai']))))===false){
									getKernelInstance()->logMsg("Não foi possível definir o pai da página na inserção. (".mysql_error().")");
									$error = true;
								}else if(($operations->getQueryResult("CALL paginas_tbl_reorder(NULL, NULL, NULL, $pageId, ".(is_null($_POST['pai'])?'NULL':$_POST['pai']).", ".(!is_numeric($_POST['ordem'])?'NULL':$_POST['ordem']).");"))===false){
									getKernelInstance()->logMsg("Não foi possível executar o procedimento para a reordenação das páginas na inserção. (".mysql_error().")");
									$error = true;
								}
								$isInsert=true;
							}
							if(($operations->setProperty('conteudo',"'".mysql_escape_string($_POST['conteudo'])."'"))===false){
								getKernelInstance()->logMsg("Não foi possível definir o conteúdo da página. (".mysql_error().")");
								$error = true;
							}
							
							?>
								<div class="pagesEdit wrap">
									<div class="header">
										<?php if($error): ?>
											<h2>Erro</h2>
											<small>Ocorreu um erro ao processar o seu pedido. Consulte o administrador para mais informações.</small>
										<?php else: ?>
											<h2>Página <?php echo(($isInsert)?'inserida':'actualizada'); ?>...</h2>
											<small>Deseja voltar a <a href="./<?php echo adminURL();?>&amp;action=adminPage&amp;operation=editpage&amp;pageId=<?php echo($pageId); ?>" title="Clique para voltar a editar a página">editar a página</a>,  <a href="./?action=showPage&pageId=<?php echo($pageId); ?>" title="Clique para visualizar a página">ver a página</a> ou voltar <a href="./<?php echo adminURL();?>&amp;action=adminPage" title="Clique para voltar à lista de páginas">lista de páginas</a>.</small>
										<?php endif; ?>
									</div>
								</div>
							<?php
						}else if(strcasecmp($_POST['subOperation'],'delete')==0){
							$pageId = $_POST['pageId'];
							
							?>
								<div class="pagesEdit wrap">
									<div class="header">
										<?php
											if(is_numeric($pageId) && $pageId>0){
												$operations = new MySQLOperations('paginas_tbl', 'id', $pageId);
												if(($operations->delete())===false){
													getKernelInstance()->logMsg("Não foi possível eliminar a página $pageId. (".mysql_error().")");
													?>
														<h2>Erro</h2>
														<small>Ocorreu um erro ao processar o seu pedido. Consulte o administrador para mais informações.</small>
													<?php 
												}else{
													?>
														<h2>Página eliminada</h2>
														<small>Voltar à <a href="./<?php echo adminURL();?>&amp;action=adminPage" title="Clique para voltar à lista de páginas">lista de páginas</a></small>
													<?php 	
												}
											}else{
												?>
													<h2>Erro</h2>
													<small>A identificação da página é inválida.</small>
												<?php 
											}
										?>
										
									</div>
								</div>
							<?php
						}else{
							?>
								<div class="pagesEdit wrap">
									<div class="header">
										<h2>Erro!</h2>
										<small>A operação não foi reconhecida.</small>
									</div>
								</div>
							<?php
						}
					}else{
						?>
							<div class="pagesEdit wrap">
								<div class="header">
									<h2>Erro!</h2>
									<small>Sem dados para submeter. Voltar à <a href="./<?php echo adminURL();?>&amp;action=adminPage" title="Clique para voltar à lista de páginas">lista de páginas</a></small>
								</div>
							</div>
						<?php
					}
					break;
				
				/* Formulário de edição de páginas */
				case 'editpage':
					$pageId = (is_numeric($_REQUEST['pageId']))?$_REQUEST['pageId']:false;
					$pageOrder = 'NULL';
					$pageParent = 'NULL';
					$pageTitle = '';
					$pageContent = '';
					
					if($query = MySQLOperations::getQueryResult("
						SELECT `pages`.`id` AS `pageId`,
		        			`pages`.`ordem` AS `pageOrder`,
		        			IF(`pages`.`id_pai`<=>NULL,'NULL',`pages`.`id_pai`) AS `pageParent`,
		        			`pages`.`titulo` AS `pageTitle`,
		        			`pages`.`conteudo` AS `pageContent`
		        		FROM `paginas_tbl` `pages`
		        		WHERE (`pages`.`id` = '$pageId' ) 
		        		LIMIT 1
		        	")){
	        			if($page = mysql_fetch_assoc($query)){
	        				$pageOrder = $page['pageOrder'];
							$pageParent = $page['pageParent'];
							$pageTitle = $page['pageTitle'];
							$pageContent = $page['pageContent'];
	        			}
		        	}
					
					if(!empty($_POST)){
						if(strcasecmp($_POST['subOperation'],'changeParent')==0){
							if($pageParent!=$_POST['pai']){
								$pageOrder = 'NULL';
							}
						}else{
							$pageOrder = $_POST['ordem'];
						}
						$pageParent = $_POST['pai'];
						$pageTitle = $_POST['titulo'];
						$pageContent = $_POST['conteudo'];
					}
						?>
							<div class="pagesEdit wrap">
								<div class="header">
									<h2><?php echo(((!empty($pageTitle))?"A editar página <a href=\"./?action=showPage&amp;pageId={$pageId}\" title=\"Clique para visualizar a página\">{$pageTitle}</a>:":"A inserir uma nova página")); ?></h2>
									<small>Preencha o formulário para construir a sua página.</small>
								</div>
								<form id="editpageform" action="./<?php echo(adminURL()); ?>&amp;action=adminPage&amp;operation=submitpage" method="post" enctype="application/x-www-form-urlencoded">
									<div class="content">
										<input type="hidden" name="pageId" value="<?php echo($pageId); ?>" />
										<input type="hidden" name="subOperation" value="save" />
										<p>
											<label for="titulo">Título: </label>
											<input id="titulo" name="titulo" value="<?php echo($pageTitle);?>" />
										</p>
										<?php 
											// Função para criar o campo de selecção do pai da página
											function adminPagesTreeOptions($parentId, $pageId){
												$result='';
												static $initialParentId='NULL';
												static $level=0;
												
												if($initialParentId=='NULL'){
													$level++;
								        			$result .= "<option value='NULL'".('NULL'==$parentId?' selected="selected"':'').">Raiz</option>";
												}
													
												if($query = MySQLOperations::getQueryResult("
													SELECT `pages`.`id` AS `pageid`,
									        			`pages`.`titulo` AS `title`,
									        			`pages`.`id_pai` AS `pageparent`, 
									        			IF((SELECT COUNT(`subpages`.`id`) FROM `paginas_tbl` `subpages` WHERE `subpages`.`id_pai`=`pages`.`id`)>0,'false','true') AS `leaf`
									        		FROM `paginas_tbl` `pages`
									        		WHERE (`pages`.`id_pai` <=> $initialParentId )
									        			AND `pages`.`id` <> $pageId 
									        		ORDER BY `pages`.`ordem` ASC")):

													$dash = '';
													for($a=0; $a<$level; $a++){
														$dash .= '&mdash;';
													}
									        		while($page = mysql_fetch_assoc($query)):
									        			$result .= "<option value='".$page['pageid']."'".($page['pageid']==$parentId?' selected="selected"':'').">".$dash.$page['title']."</option>";
									        			
									        			if($page['leaf']==='false'){
									        				$initialParentId = $page['pageid'];
									        				$level++;
									        				$result.=adminPagesTreeOptions($parentId, $pageId);
									        			}
									        		endwhile;
									        	endif;
									        	
									        	return $result;
											}
											
											$result = adminPagesTreeOptions($pageParent, $pageId);
											if(!empty($result)):
												?>
													<p>
														<label for="pai">Página pai: </label>
														<select id="pai" name="pai">
															<?php echo($result); ?>
														</select>
													</p>
												<?php 
											endif;
										?>
										<p>
											<label for="ordem">Ordem: </label>
											<?php 
												$selectOptions = array();
												$select = "<select id=\"ordem\" name=\"ordem\">";
												if($query = MySQLOperations::getQueryResult("
													SELECT  `pages`.`id` AS `pageId`,
															`pages`.`titulo` AS `pageTitle`,
															`pages`.`ordem` AS `pageOrder`
													FROM `paginas_tbl` `pages`
									        		WHERE (`pages`.`id_pai` <=> $pageParent )
									        		ORDER BY `pages`.`ordem` ASC
									        	")){
													while($page = mysql_fetch_assoc($query)):
														$selectOptions[] = $page;
									        		endwhile;
									        		$select .= "<option value='".($selectOptions[0]['pageOrder'])."'>no inicio</option>";
									        		if($pageOrder=='NULL'){
									        			$pageOrder = (isset($selectOptions[count($selectOptions)-1])?$selectOptions[count($selectOptions)-1]['pageOrder']+1:1);
									        		}
									        		for($a=0; $a<count($selectOptions); $a++){
									        			$order=$selectOptions[$a]['pageOrder'];
									        			if($pageOrder!=$order){
										        			$selected = ($pageOrder==(isset($selectOptions[$a+1])?$selectOptions[$a+1]['pageOrder']:(isset($selectOptions[$a])?$selectOptions[$a]['pageOrder']+1:-1)))?true:false;
									        				$orderName='depois de '.$selectOptions[$a]['pageTitle'];
										        			$select .= "<option value=\"".($order+1)."\"".($selected?' selected="selected"':'').">$orderName</option>";
									        			}
									        		}	
									        	}else{
													$select .= "<option value=\"1\">no inicio</option>";
												}
												$select .= "</select>";
												echo($select);
											?>
										</p>
										<p><textarea id="conteudo" name="conteudo" rows="15" cols="80" style="width: 80%" class="tinymce"><?php echo($pageContent); ?></textarea></p>
										<p>
											<input type="submit" name="formAction" value="Guardar" />
											<input type="button" name="formAction" value="Cancelar" />
											<?php if($pageId!==false && is_numeric($pageId) && $pageId>0): ?>
												<input type="button" name="formAction" value="Eliminar" />
											<?php endif; ?>
											<input type="reset" name="formReset" value="Repor" />
										</p>
										<script type="text/javascript">
											/* <![CDATA[ */
												/* // Permite preencher o tinymce com o respectivo conteúdo; foi desactivado por incompatibilidade com o firefox, falhando assim a validação
												$(document).ready(function (event){
													$('textarea.tinymce[name=conteudo]').val('<?php //echo(addcslashes($pageContent, "\\\'\"&\n\r<>/")); ?>');
												});
												*/
											/* ]]> */
										</script>
									</div>
								</form>
							</div>
						<?php										
					break;
				
				/* Apresentação da lista de páginas na área administrativa */
				default:
					$pageId = (is_numeric($_REQUEST['pageId']))?$_REQUEST['pageId']:false;
		
					function adminPagesTreeList($parentId=NULL, $level=0, $order=''){
						$pageId = (is_numeric($_REQUEST['pageId']))?$_REQUEST['pageId']:false;
						$result='';
						
						if($query = MySQLOperations::getQueryResult("
							SELECT `pages`.`id` AS `pageid`,
			        			`pages`.`ordem` AS `ordem`,
			        			`pages`.`titulo` AS `title`,
			        			`pages`.`data` AS `data`,
			        			IF((SELECT COUNT(`subpages`.`id`) FROM `paginas_tbl` `subpages` WHERE `subpages`.`id_pai`=`pages`.`id`)>0,'false','true') AS `leaf`
			        		FROM `paginas_tbl` `pages`
			        		WHERE (`pages`.`id_pai` ".(is_numeric($parentId)?"=$parentId":"is NULL").") 
			        		ORDER BY `pages`.`ordem` ASC")):
							
							$dash = '';
							for($a=0; $a<$level; $a++){
								$dash .= '&mdash;';
							}
			        		while($page = mysql_fetch_assoc($query)):
			        			$result.='<tr>';
			        			$result.='<td class="pageTitle"><a title="Clique para editar '.$page['title'].'" href="./'.adminURL().'&amp;action=adminPage&amp;operation=editpage&amp;pageId='.$page['pageid'].'">'.$dash.$page['title'].'&nbsp;</a></td>';
			        			$result.='<td class="pageId">'.$page['pageid'].'</td>';
			        			$result.='<td class="pageId">'.$order.$page['ordem'].'</td>';
			        			$result.='<td class="pageDate">'.$page['data'].'</td>';
			        			$result.='</tr>';
			        			
			        			if($page['leaf']==='false'){
			        				$result.=adminPagesTreeList($page['pageid'], $level+1, $order.$page['ordem'].'.');
			        			}
			        		endwhile;
			        	endif;
			        	
			        	return (!empty($result))?$result:'<tr><td colspan="4">Sem páginas</td></tr>';
					}
					
					?>
						<div class="pagesList wrap">
							<div class="header">
								<h2>Páginas</h2>
								<small>Seleccione a página a editar, ou pressione <cite>Inserir página</cite> para inserir uma nova página:</small>
							</div>
							<table class="pagesList" cellspacing="0" summary="Lista de páginas">
								 <thead>
									  <tr>
										<th>Título</th>
									  	<th style="width: 20px;">ID</th>
									  	<th style="width: 40px;">Ordem</th>
										<th style="width: 130px;">Data</th>
									  </tr>
								  </thead>
								  <tfoot>
									  	<tr>
									  		<th colspan="4"><a href="./<?php echo adminURL();?>&amp;action=adminPage&amp;operation=editpage&amp;pageId=-1" title="Clique para adicionar uma nova página">Inserir página</a></th>
								  		</tr>
								  </tfoot>
								  <tbody>
								  	<?php 
										echo(adminPagesTreeList());
									?>
								  </tbody>
							</table>
						</div>
					<?php
			}
			 
			return true; 
		});
	endif;
	
	// Lista de páginas para o tinymce
	if(isset($_REQUEST['tinyMcePagesList'])):
		function jsPagesTreeList(){
			static $parentId='NULL';
			static $level=0;
			$result='';
			
			if($query = MySQLOperations::getQueryResult("
				SELECT `pages`.`id` AS `pageid`,
		        	`pages`.`titulo` AS `title`,
		        	IF((SELECT COUNT(`subpages`.`id`) FROM `paginas_tbl` `subpages` WHERE `subpages`.`id_pai`=`pages`.`id`)>0,'false','true') AS `leaf`
		        FROM `paginas_tbl` `pages`
		        WHERE (`pages`.`id_pai` <=> ".(is_numeric($parentId)?"$parentId":"NULL").") 
		        ORDER BY `pages`.`ordem` ASC")):
			
				$dash = '';
				for($a=0; $a<$level; $a++){
					$dash .= '&mdash;';
				}
				while($page = mysql_fetch_assoc($query)):
		        	$result.=', ["'.addcslashes($dash.$page['title'], "\\\'\"&\n\r<>/").'", "./?action=showPage&amp;pageId='.$page['pageid'].'"]';
		        	
		        	if($page['leaf']==='false'){
		        		$parentId = $page['pageid'];
        				$level++;
		        		$result.=jsPagesTreeList();
		        	}
		        endwhile;
	        endif;
	        return $result;
		}
		?>
			var tinyMCELinkList = new Array(
				["Raiz de <?php echo(addcslashes(PRODUCT_NAME, "\\\'\"&\n\r<>/")); ?>", "./"]
				<?php echo(jsPagesTreeList()); ?>
			);
		<?php 
	endif;
endif;