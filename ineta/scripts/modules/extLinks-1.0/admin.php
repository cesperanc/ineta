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
			<li class="adminLinksModule <?php echo(strcasecmp($_REQUEST['action'],'adminLinks')==0?' currentPage':''); ?>"><a href="./<?php echo adminURL();?>&amp;action=adminLinks">Links Externos</a></li>	
		<?php
		return true;
	});
	
	// Carregar itens apenas se estamos na nossa área
	if(strcasecmp($_REQUEST['action'],'adminLinks')==0):
		getKernelInstance()->actionAddFunction("_init", function(){
			$kernel = getKernelInstance();
			if(!$kernel->actionExecuted("requestJQuery") && !$kernel->actionExecute("requestJQuery")){
				$kernel->logMsg("Não foi possível carregar a biblioteca jquery no módulo users.");
			}
			// 	Carregar apenas o que precisamos consoante a secção a apresentar
			switch(strtolower($_REQUEST['operation'])){
				/* Formulário de edição de páginas */
				case 'viewsuser':
					// Para carregar o código necessário para incluir o TinyMCE
					$kernel->actionExecute("requestTinyMCE");					
				break;
			}
		});
		
		// Adiciona os estilos específicos do módulo
		getKernelInstance()->actionAddFunction("adminHead", function(){
			switch(strtolower($_REQUEST['operation'])){
				/* Guarda ou cancela  */
				case 'submitlink':
					
				break;
				/* Formulário para alteração de evento */
				case 'viewlink':
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
								        if(confirm("Tem a certeza de que pretende eliminar este evento?")){
								        	$("input[name=subOperation]").val('delete');
								        	$("#editextlinksform").submit();
								        }
								    });
								    $("input[name=formAction][value=Recuperar]").click(function(event) {
								        if(confirm("Tem a certeza de que pretende recuperar este evento?")){
								        	$("input[name=subOperation]").val('undelete');
								        	$("#editextlinksform").submit();
								        }
								    });

									$("input[name=formAction][value=Guardar]").click(function(event) {
										var error = false;
										
										$("#editextlinksform").validate({
											rules: {
										    	titulo: {
								    				required: true
										    	},
										    	link: {
								    				required: true
										    	}
										  	},
										  	messages: {
										  	    titulo: {
													required: "É necessário introduzir o titulo da ligação"
										  		},
										  		link:{
											  		required: "Têm de indicar a hiperligação"
										  		}
										  	}
										});
									});
								});
								
							/* ]]> */
						</script>
					<?php							
				break;
				/* Apresentação da lista de eventos na área administrativa */
				default:
			}
		});
	
		// Adiciona o conteúdo à página administrativa
		getKernelInstance()->actionAddFunction("adminContent", function(){
			
			switch(strtolower($_REQUEST['operation'])){
				/* Guarda ou cancela  */
				case 'submitextlink':
					if(!empty($_POST)){
						if(strcasecmp($_POST['subOperation'],'save')==0){
							$error=false;
							$linkId = $_POST['linkId'];
							$isInsert=false;
 
							if(is_numeric($linkId) && $linkId>0){
								$operations = new MySQLOperations('extlinks_tbl', 'id', $linkId);
								if(($operations->setProperty('titulo', "'".mysql_escape_string($_POST['titulo'])."'"))===false){
										getKernelInstance()->logMsg("Não foi possível definir o titulo da ligação. (".mysql_error().")");
										$error = true;
								}
									
									$isInsert=false;
							}else{
								$operations = new MySQLOperations('extlinks_tbl');
								if(($linkId = $operations->insert('titulo', "'".mysql_escape_string($_POST['titulo'])."'",'extlinks_tbl','id'))===false){
									getKernelInstance()->logMsg("Não foi possível definir o titulo da ligação - insert. (".mysql_error().")");
									$error = true;
								}
								$isInsert=true;
							}
							
							if(($operations->setProperty('link', "'".mysql_escape_string($_POST['link'])."'"))===false){
								getKernelInstance()->logMsg("Não foi possível definir a hiperligação. (".mysql_error().")");
								$error = true;
							}
							?>
								<div class="pagesEdit wrap">
									<div class="header">
										<?php if($error): ?>
											<h2>Erro</h2>
											<small>Ocorreu um erro ao processar o seu pedido. Consulte o administrador para mais informações.</small>
										<?php else: ?>
											<h2>Utilizador <?php echo(($isInsert)?'inserido':'actualizado'); ?>...</h2>
											<small>Voltar a <a href="./<?php echo adminURL();?>&amp;action=adminLinks&amp;operation=viewLink&amp;linkId=<?php echo($linkId); ?>" title="Clique para voltar a editar o link">editar o link</a> ou à <a href="./<?php echo adminURL();?>&amp;action=adminLinks" title="Clique para voltar à lista de Links">lista de Links</a></small>
										<?php endif; ?>
									</div>
								</div>
							<?php
						}else if(strcasecmp($_POST['subOperation'],'delete')==0){
							$linkId = $_POST['linkId'];
							
							?>
								<div class="pagesEdit wrap">
									<div class="header">
										<?php
											if(is_numeric($userId) && $userId>0){
												$operations = new MySQLOperations('extlinks_tbl', 'id', $userId);
												if(($operations->delete('id', $linkId))===false){
													getKernelInstance()->logMsg("Não foi possível eliminar a hiperligação $linkId. (".mysql_error().")");
													?>
														<h2>Erro</h2>
														<small>Ocorreu um erro ao processar o seu pedido. Consulte o administrador para mais informações.</small>
													<?php 
												}else{
													?>
														<h2>Link eliminado</h2>
														<small>Voltar à <a href="./<?php echo adminURL();?>&amp;action=adminLinks" title="Clique para voltar à lista de links">lista de links</a></small>
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
									<small>Sem dados para submeter. Voltar à <a href="./<?php echo adminURL();?>&amp;action=adminPage" title="Clique para voltar à página de incial">página de incial</a></small>
								</div>
							</div>
						<?php
					}
				
				break;
				/* Formulário para alteração de evento */
				case 'viewlink':
					$linkId = (is_numeric($_REQUEST['linkId']))?$_REQUEST['linkId']:false;
					if($linkId > 0){
						$newLink = 0;
					}else{
						$newLink = 1;
					}
					if(!$linkId){
						break;
					}
					
					if($query = MySQLOperations::getQueryResult("
						SELECT `extlink`.*
		        		FROM `extlinks_tbl` `extlink`
		        		WHERE (`extlink`.`id` = '$linkId' ) 
		        		LIMIT 1
		        	")){
	        			if($link = mysql_fetch_assoc($query)){
							$linkId = $link['id'];
							$titulo = $link['titulo'];
							$link = $link['link'];
	        			}
		        	}
					
						?>
							<div class="linkView wrap">
								<div class="header">
									<h2><?php echo(((!empty($titulo))?"A visualizar o a hiperligacao $titulo:":"A inserir nova hiperligação")); ?></h2>
									<small>Preencha o formulário.</small>
								</div>
								
								<form id="editextlinksform" action="./<?php echo adminURL();?>&amp;action=adminLinks&amp;operation=submitextlink" method="post" enctype="application/x-www-form-urlencoded">
									<div class="content">
										<br/>
										<br/>
										<div id="errorPlace" class="ui-widget"></div>
										<p>
											<label for="titulo">Titulo: </label>
											<input id="titulo" name="titulo" value="<?php echo($titulo);?>" />
										</p>
										<p>
											<label for="link">Link: </label>
											http://<input id="link" name="link" value="<?php echo($link);?>" />
										</p>
										
										<input type="hidden" name="linkId" value="<?php echo($linkId); ?>" />
										<input type="hidden" name="subOperation" value="save" />
										<input type="submit" name="formAction" value="Guardar" />
										<input type="button" name="formAction" value="Cancelar" />
										
										<input type="reset" name="formReset" value="Repor" />
									</div>
								</form>
							</div>
						<?php			
		        break;
				
				/* Apresentação da lista de eventos na área administrativa */
				default:
					
					
					function adminLinkList($order=''){
						$result='';
						
						if($query = MySQLOperations::getQueryResult("
							SELECT `extlink`.`titulo` AS `titulo`,
							`extlink`.`id` AS `idLink`,
							`extlink`.`link` AS `link`
			        		FROM `extlinks_tbl` `extlink`")):
							
			        		while($link = mysql_fetch_assoc($query)):
			        			$result.='<tr>';
			        			$result.='<td class="utilizador"><a title="Clique para ver '.$link['idLink'].'" href="./'.adminURL().'&amp;action=adminLinks&amp;operation=viewLink&amp;linkId='.$link['idLink'].'">'.$link['titulo'].'</a></td>';
			        			$result.='<td class="link">'.$link['link'].'</td>';
			        			$result.='</tr>';
			        		endwhile;
			        	endif;
			        	
			        	return (!empty($result))?$result:'<tr><td colspan="2">Sem links</td></tr>';
					}
					
					?>
						<div class="usersList wrap">
							<div class="header">
								<h2>Links externos</h2>
								<small>Seleccione o link para ver em detalhe, ou pressione <cite>Adicionar link</cite> para adicionar um novo link:</small> Este links vão aparecer na lado direito do site, em tods as páginas
							</div>
							<table class="insideAdminTable" cellspacing="0" summary="Lista de links">
								 <thead>
									  <tr>
										<th>Titulo</th>
										<th>link</th>
									  </tr>
								  </thead>
								  <tfoot>
									  	<tr>
									  		<th colspan="2"><a href="./<?php echo adminURL();?>&amp;action=adminLinks&amp;operation=viewLink&amp;linkId=-1" title="Clique para adicionar um novo link">Adicionar link</a></th>
								  		</tr>
								  </tfoot>
								  <tbody>
								  	<?php 
										echo(adminLinkList());
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