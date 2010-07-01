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
			<li class="adminSchedulesModule <?php echo(strcasecmp($_REQUEST['action'],'adminUsers')==0?' currentPage':''); ?>"><a href="./<?php echo adminURL();?>&amp;action=adminUsers">Utilizadores</a></li>	
		<?php
		return true;
	});
	
	// Carregar itens apenas se estamos na nossa área
	if(strcasecmp($_REQUEST['action'],'adminUsers')==0):
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
				case 'submituser':
					
				break;
				/* Formulário para alteração de evento */
				case 'viewuser':
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
								        	$("#edituserform").submit();
								        }
								    });
								    $("input[name=formAction][value=Recuperar]").click(function(event) {
								        if(confirm("Tem a certeza de que pretende recuperar este evento?")){
								        	$("input[name=subOperation]").val('undelete');
								        	$("#edituserform").submit();
								        }
								    });

									$("input[name=formAction][value=Guardar]").click(function(event) {
										var error = false;
										
										$("#edituserform").validate({
											rules: {
										    	utilizador: {
								    				required: true
										    	},
										    	nome: {
								    				required: true
										    	},
										    	senha1: {
										    		required: true
										    	},
												senha2:{
										    		required: true,
										    		equalTo:$("#senha1")
												}
										  	},
										  	messages: {
										  	    senha1: "Por favor introduza a password do utilizador",
										  		senha2: {
											  		required: "Por favor introduza a password do utilizador",
											  		equalTo: "A password têm de ser igual à introduzia no campo anterior"
										  	    },
										  	     utilizador: {
													required: "É necessário introduzir o username para que se possa autenticar"
										  		},
										  		nome:{
											  		required: "Têm de indicar o nome do utilizador"
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
				case 'submituser':
					if(!empty($_POST)){
						if(strcasecmp($_POST['subOperation'],'save')==0){
							$error=false;
							$userId = $_POST['userId'];
							$isInsert=false;
 
							if(is_numeric($userId) && $userId>0){
								$operations = new MySQLOperations('utilizadores_tbl', 'id', $userId);
								if(($operations->setProperty('utilizador', "'".mysql_escape_string($_POST['utilizador'])."'"))===false){
										getKernelInstance()->logMsg("Não foi possível definir o nome de utilizador. (".mysql_error().")");
										$error = true;
								}
									
									$isInsert=false;
							}else{
								$operations = new MySQLOperations('utilizadores_tbl');
								if(($userId = $operations->insert('utilizador', "'".mysql_escape_string($_POST['utilizador'])."'",'utilizadores_tbl','id'))===false){
									getKernelInstance()->logMsg("Não foi possível definir o nome de utilizador - insert. (".mysql_error().")");
									$error = true;
								}
								$isInsert=true;
							}
							
							if(($operations->setProperty('senha',"'".User::encryptedPassword($_POST['senha1'])."'"))===false){
								getKernelInstance()->logMsg("Não foi possível definir a senha do utilizador. (".mysql_error().")");
								$error = true;
							}else if(($operations->setProperty('nome', "'".mysql_escape_string($_POST['nome'])."'"))===false){
								getKernelInstance()->logMsg("Não foi possível definir o nome do utilizador. (".mysql_error().")");
								$error = true;
							}else if(($operations->setProperty('perfil', "'".mysql_escape_string($_POST['perfil'])."'"))===false){
								getKernelInstance()->logMsg("Não foi possível definir o perfil do utilizador. (".mysql_error().")");
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
											<small>Voltar a <a href="./<?php echo adminURL();?>&amp;action=adminUsers&amp;operation=viewUser&amp;userId=<?php echo($userId); ?>" title="Clique para voltar a editar o utilizador">editar o utilizador</a> ou à <a href="./<?php echo adminURL();?>&amp;action=adminUsers" title="Clique para voltar à lista de utilizadores">lista de utilizadores</a></small>
										<?php endif; ?>
									</div>
								</div>
							<?php
						}else if(strcasecmp($_POST['subOperation'],'delete')==0){
							$userId = $_POST['userId'];
							
							?>
								<div class="pagesEdit wrap">
									<div class="header">
										<?php
											if(is_numeric($userId) && $userId>0){
												$operations = new MySQLOperations('utilizadores_tbl', 'id', $userId);
												if(($operations->delete('id', $userId))===false){
													getKernelInstance()->logMsg("Não foi possível eliminar o utilizador $userId. (".mysql_error().")");
													?>
														<h2>Erro</h2>
														<small>Ocorreu um erro ao processar o seu pedido. Consulte o administrador para mais informações.</small>
													<?php 
												}else{
													?>
														<h2>Utilizador eliminado</h2>
														<small>Voltar à <a href="./<?php echo adminURL();?>&amp;action=adminUsers" title="Clique para voltar à lista de utilizadores">lista de utilizadores</a></small>
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
				case 'viewuser':
					$userId = (is_numeric($_REQUEST['userId']))?$_REQUEST['userId']:false;
					if($userId > 0){
						$newUser = 0;
					}else{
						$newUser = 1;
					}
					if(!$userId){
						break;
					}
					
					if($query = MySQLOperations::getQueryResult("
						SELECT `utilizador`.*
		        		FROM `utilizadores_tbl` `utilizador`
		        		WHERE (`utilizador`.`id` = '$userId' ) 
		        		LIMIT 1
		        	")){
	        			if($user = mysql_fetch_assoc($query)){
							$userId = $user['id'];
							$username = $user['utilizador'];
							$userNome = $user['nome'];
							$userProfile = $user['perfil'];
	        			}
		        	}
					
						?>
							<div class="userView wrap">
								<div class="header">
									<h2><?php echo(((!empty($username))?"A visualizar o utilizador $username:":"A inserir novo utilizador")); ?></h2>
									<small>Preencha o formulário.</small>
								</div>
								
								<form id="edituserform" action="./<?php echo adminURL();?>&amp;action=adminUsers&amp;operation=submituser" method="post" enctype="application/x-www-form-urlencoded">
									<div class="content">
										<br/>
										<br/>
										<div id="errorPlace" class="ui-widget"></div>
										<p>
											<label for="utilizador">Utilizador: </label>
											<input id="utilizador" name="utilizador" value="<?php echo($username);?>" />
										</p>
										<p>
											<label for="nome">Nome: </label>
											<input id="nome" name="nome" value="<?php echo($userNome);?>" />
										</p>
										<p>
											<label for="perfil">Perfil: </label>
											<select id="perfil" name="perfil">
												<option value="utilizador" <?php if($userProfile=="utilizador"){ echo "selected='selected'";}?> >Utilizador</option>
												<option value="administrador" <?php if($userProfile=="administrador"){ echo "selected='selected'";}?>>Administrador</option>
											</select>
										</p>
										<p>
											<label for="senha1">Senha: </label>
											<input id="senha1" type="password" name="senha1" value="" />
										</p>
										<p>
											<label for="senha2">Confirmar senha: </label>
											<input id="senha2" type="password" name="senha2" value="" />
										</p>
										
										<input type="hidden" name="userId" value="<?php echo($userId); ?>" />
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
					
					
					function adminUserList($order=''){
						$result='';
						
						if($query = MySQLOperations::getQueryResult("
							SELECT `utilizador`.`utilizador` AS `utilizador`,
							`utilizador`.`id` AS `idUtilizador`,
							`utilizador`.`nome` AS `nome`,
							`utilizador`.`perfil` AS `perfil`
			        		FROM `utilizadores_tbl` `utilizador`")):
							
			        		while($user = mysql_fetch_assoc($query)):
			        			$result.='<tr>';
			        			$result.='<td class="utilizador"><a title="Clique para ver '.$user['idUtilizador'].'" href="./'.adminURL().'&amp;action=adminUsers&amp;operation=viewUser&amp;userId='.$user['idUtilizador'].'">'.$user['utilizador'].'</a></td>';
			        			$result.='<td class="nome">'.$user['nome'].'</td>';
			        			$result.='<td class="perfil">'.$user['perfil'].'</td>';
			        			$result.='</tr>';
			        		endwhile;
			        	endif;
			        	
			        	return (!empty($result))?$result:'<tr><td colspan="3">Sem utilizadores</td></tr>';
					}
					
					?>
						<div class="usersList wrap">
							<div class="header">
								<h2>Utilizadores</h2>
								<small>Seleccione o utilizador para ver em detalhe, ou pressione <cite>Adicionar utilizador</cite> para adicionar um novo utilizador:</small>
							</div>
							<table class="insideAdminTable" cellspacing="0" summary="Lista de utilizadores">
								 <thead>
									  <tr>
										<th>Utilizador</th>
										<th>Nome</th>
										<th>Perfil</th>
									  </tr>
								  </thead>
								  <tbody>
								  	<?php 
										echo(adminUserList());
									?>
								  </tbody>
								  <tfoot>
									  	<tr>
									  		<th colspan="3"><a href="./<?php echo adminURL();?>&amp;action=adminUsers&amp;operation=viewUser&amp;userId=-1" title="Clique para adicionar um novo utilizador">Adicionar utilizador</a></th>
								  		</tr>
								  </tfoot>
							</table>
						</div>
					<?php
			}
			return true;
		});
	endif;
	
endif;