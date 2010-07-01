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
			<li class="adminSubscriptionsModule <?php echo(strcasecmp($_REQUEST['action'],'adminSubscriptions')==0?' currentPage':''); ?>"><a href="./<?php echo adminURL();?>&amp;action=adminSubscriptions">Inscrições</a></li>	
		<?php
		return true;
	});
	
	// Carregar itens apenas se estamos na nossa área
	if(strcasecmp($_REQUEST['action'],'adminSubscriptions')==0):
		
		getKernelInstance()->actionAddFunction("_init", function(){
			$kernel = getKernelInstance();
			if(!$kernel->actionExecuted("requestJQuery") && !$kernel->actionExecute("requestJQuery")){
				$kernel->logMsg("Não foi possível carregar a biblioteca jquery no módulo subscriptions.");
			}
			// 	Carregar apenas o que precisamos consoante a secção a apresentar
			switch(strtolower($_REQUEST['operation'])){
				/* Formulário de edição de páginas */
				case 'editpage':
					// Para carregar o código necessário para o TinyMCE
					//$kernel->actionExecute("requestTinyMCE");					
				break;
			}
		});
		
		// Adiciona os estilos específicos do módulo
		getKernelInstance()->actionAddFunction("adminHead", function(){
			
			// Carregar apenas o que precisamos consoante a secção a apresentar
			switch(strtolower($_REQUEST['operation'])){
				
				/* Formulário de edição de páginas */
				case 'viewsubscription':
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
								        if(confirm("Tem a certeza de que pretende eliminar esta inscrição?")){
								        	$("input[name=subOperation]").val('delete');
								        	$("#editsubscriptionform").submit();
								        }
								    });
								    $("input[name=formAction][value=Recuperar]").click(function(event) {
								        if(confirm("Tem a certeza de que pretende recuperar esta inscrição?")){
								        	$("input[name=subOperation]").val('undelete');
								        	$("#editsubscriptionform").submit();
								        }
								    });

									$("input[name=formAction][value=Guardar]").click(function(event) {
										var error = false;
										if($("input[name=typeSubscription]").val()==="full"){
											if($("input[name=data_nasc]").val()==""){
												error = "Têm de introduzir a data de nascimento";
												//return false;
											}else if($("input[name=email]").val()==""){
												error = "Têm de introduzir o email";
												//return false;
											}else if($("input[name=morada]").val()==""){
												error = "Têm de introduzir a morada";
												//return false;
											}else if($("input[name=nome]").val()==""){
												error = "Têm de introduzir a morada";
												//return false;
											}else if($("input[name=telefone1]").val()==""){
												error = "Têm de introduzir o telefone1";
												//return false;
											}
										}
										if($("select[name=estado]").val()==="R"){
											if($("textarea[name=Razao_Rejeicao]").val()==""){
												error = "Para rejeitar a inscrição têm de indicar um motivo!";
											}
										}
										if(error){
											$("#errorPlace").html("<div style='padding: 0pt 0.7em;' class='ui-state-error ui-corner-all'><p><span style='float: left; margin-right: 0.3em;' class='ui-icon ui-icon-alert'><\/span><strong>Erro:<\/strong> "+error+" <\/p><\/div>");
											return false;
										}else{
											return true;
										}
										
										//return false;
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
			function getStringTrueFalse($var){
				switch($var){
					case 1:
        				return "Sim";
        				break;
        			case 0:
        				return "Não";
        				break;
        			case true:
        				return "Sim";
        				break;
        			case false:
        				return "Sim";
        				break;
        			default:
        				return "Erro";
				}
			}
			
			function getEstadoString($estado){
				switch($estado){
        			case "I":
        				return "Pessoa Incontactável";
        				break;
        			case "V":
        				return "Inscrição Válida";
        				break;
        			case "R":
        				return "Inscrição Rejeitada";
        				break;
        			default:
        				return "Ainda não foi tratada pelo Administrador";
        		}
			}
		
			switch(strtolower($_REQUEST['operation'])){
				/* Guarda ou cancela  */
				case 'submitsubscription':
					if(!empty($_POST)){
						if(strcasecmp($_POST['subOperation'],'save')==0){
							$error=false;
							$subscriptionId = $_POST['subscriptionId'];
							$isInsert=false;
							
							if(is_numeric($subscriptionId) && $subscriptionId>0){
								$operations = new MySQLOperations('inscricoes_tbl', 'idInscricao', $subscriptionId);
								if($_POST['typeSubscription']==='full'){
									if(($operations->setProperty('Nome', "'".mysql_escape_string($_POST['nome'])."'"))===false){
										getKernelInstance()->logMsg("Não foi possível definir o nome do inscrito. (".mysql_error().")");
										$error = true;
									}
									if(($operations->setProperty('eMail', "'".mysql_escape_string($_POST['email'])."'"))===false){
										getKernelInstance()->logMsg("Não foi possível definir o e-mail do inscrito. (".mysql_error().")");
										$error = true;
									}else if(($operations->setProperty('Morada', "'".mysql_escape_string($_POST['morada'])."'"))===false){
										getKernelInstance()->logMsg("Não foi possível definir a morada do inscrito. (".mysql_error().")");
										$error = true;
									}else if(($operations->setProperty('Data_Nascimento', "'".mysql_escape_string($_POST['data_nasc'])."'"))===false){
										getKernelInstance()->logMsg("Não foi possível definir a data de nascimento do inscrito. (".mysql_error().")");
										$error = true;
									}else if(($operations->setProperty('Telefone1', "'".mysql_escape_string($_POST['telefone1'])."'"))===false){
										getKernelInstance()->logMsg("Não foi possível definir o telefone 1 do inscrito. (".mysql_error().")");
										$error = true;
									}else if(($operations->setProperty('Telefone2', "'".mysql_escape_string($_POST['telefone2'])."'"))===false){
										getKernelInstance()->logMsg("Não foi possível definir o telefone 2 do inscrito. (".mysql_error().")");
										$error = true;
									}
									$isInsert=false;
								}else{
									if(($operations->setProperty('Estado', "'".mysql_escape_string($_POST['estado'])."'"))===false){
										getKernelInstance()->logMsg("Não foi possível definir o estado do inscrito. (".mysql_error().")");
										$error = true;
									}else if(($operations->setProperty('Razao_Rejeicao', "'".mysql_escape_string($_POST['Razao_Rejeicao'])."'"))===false){
										getKernelInstance()->logMsg("Não foi possível definir o motivo da rejeição do inscrito. (".mysql_error().")");
										$error = true;
									}
									
								}
							}
							
							?>
								<div class="pagesEdit wrap">
									<div class="header">
										<?php if($error): ?>
											<h2>Erro</h2>
											<small>Ocorreu um erro ao processar o seu pedido. Consulte o administrador para mais informações.</small>
										<?php else: ?>
											<h2>Inscrições <?php echo(($isInsert)?'inserida':'actualizada'); ?>...</h2>
											<small>Voltar a <a href="./<?php echo adminURL();?>&amp;action=adminSubscriptions&amp;operation=viewSubscription&amp;subscriptionId=<?php echo($subscriptionId); ?>" title="Clique para voltar a editar a inscrição">editar a inscrição</a> ou à <a href="./<?php echo adminURL();?>&amp;action=adminSubscriptions" title="Clique para voltar à lista de inscrições">lista de inscrições</a></small>
										<?php endif; ?>
									</div>
								</div>
							<?php
						}else if(strcasecmp($_POST['subOperation'],'delete')==0){
							$subscriptionId = $_POST['subscriptionId'];
							
							?>
								<div class="pagesEdit wrap">
									<div class="header">
										<?php
											if(is_numeric($subscriptionId) && $subscriptionId>0){
												$operations = new MySQLOperations('inscricoes_tbl', 'idInscricao', $subscriptionId);
												if(($operations->setProperty('removido', "1"))===false){
													getKernelInstance()->logMsg("Não foi possível marcar a inscrição como eliminada $subscriptionId. (".mysql_error().")");
													?>
														<h2>Erro</h2>
														<small>Ocorreu um erro ao processar o seu pedido. Consulte o administrador para mais informações.</small>
													<?php 
												}else{
													?>
														<h2>Inscrição eliminada</h2>
														<small>Voltar à <a href="./<?php echo adminURL();?>&amp;action=adminSubscriptions" title="Clique para voltar à lista de inscrições">lista de inscrições</a></small>
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
						}else if(strcasecmp($_POST['subOperation'],'undelete')==0){
							$subscriptionId = $_POST['subscriptionId'];
							
							?>
								<div class="pagesEdit wrap">
									<div class="header">
										<?php
											if(is_numeric($subscriptionId) && $subscriptionId>0){
												$operations = new MySQLOperations('inscricoes_tbl', 'idInscricao', $subscriptionId);
												if(($operations->setProperty('removido', "0"))===false){
													getKernelInstance()->logMsg("Não foi possível marcar recuperar a inscrição $subscriptionId. (".mysql_error().")");
													?>
														<h2>Erro</h2>
														<small>Ocorreu um erro ao processar o seu pedido. Consulte o administrador para mais informações.</small>
													<?php 
												}else{
													?>
														<h2>Inscrição recuperada</h2>
														<small>Voltar à <a href="./<?php echo adminURL();?>&amp;action=adminSubscriptions" title="Clique para voltar à lista de inscrições">lista de inscrições</a></small>
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
				
				/* Formulário para alteração de inscrições */
				case 'viewsubscription':
					$subscriptionId = (is_numeric($_REQUEST['subscriptionId']))?$_REQUEST['subscriptionId']:false;
					
					if(!$subscriptionId){
						break;
					}
					
					if($query = MySQLOperations::getQueryResult("
						SELECT `inscricoes`.*
		        		FROM `inscricoes_tbl` `inscricoes`
		        		WHERE (`inscricoes`.`idInscricao` = '$subscriptionId' ) 
		        		LIMIT 1
		        	")){
	        			if($subscription = mysql_fetch_assoc($query)){
	        				$subscriptionidInscricao = $subscription['idInscricao'];
							$subscriptiontsInsercao = $subscription['tsInsercao'];
							$subscriptionremovido = $subscription['removido'];
							$subscriptionTipo_Inscricao = $subscription['Tipo_Inscricao'];
							$subscriptionAutorizaMensagens = $subscription['AutorizaMensagens'];
							$subscriptionEstado = $subscription['Estado'];
							$subscriptionRazao_Rejeicao = $subscription['Razao_Rejeicao'];
							$subscriptionNome = $subscription['Nome'];
							$subscriptionMorada = $subscription['Morada'];
							$subscriptionData_Nascimento = $subscription['Data_Nascimento'];
							$subscriptionTelefone1 = $subscription['Telefone1'];
							$subscriptionTelefone2 = $subscription['Telefone2'];
							$subscriptioneMail = $subscription['eMail'];
							$subscriptionidActividade = $subscription['idActividade'];			
	        			}
		        	}
					
						?>
							<div class="subscriptionView wrap">
								<div class="header">
									<h2><?php echo(((!empty($subscriptionNome))?"A visualizar a inscrição de $subscriptionNome:":"")); ?></h2>
									<small>Preencha o formulário para construir a sua página.</small>
								</div>
								
								<form id="editsubscriptionform" action="./<?php echo adminURL();?>&amp;action=adminSubscriptions&amp;operation=submitsubscription" method="post" enctype="application/x-www-form-urlencoded">
									<div class="content">
										<button id="activeEdit" class="floatright clearall ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only"><span class="ui-button-text">Editar dados da inscrição</span></button>
										<br/>
										<br/>
										<br/>
										<?php 
										if($subscriptionremovido){
											echo "<div style='padding: 0pt 0.7em;' class='ui-state-highlight ui-corner-all'><p><span style='float: left; margin-right: 0.3em;' class='ui-icon ui-icon-info'>&nbsp;</span>Esta inscrição já foi eliminada</p></div>";
										}
										?>
										<div id="errorPlace" class="ui-widget"></div>
										<p>
											<label for="tsInsercao">Inserção: <?php echo($subscriptiontsInsercao);?></label>
										</p>
										<p>
											<label for="tipoinscricao">Tipo inscriçao: <?php echo($subscriptionTipo_Inscricao);?></label>
										</p>
										<p>
											<label for="autoriza">Autoriza Mensagens: <?php echo(getStringTrueFalse($subscriptionAutorizaMensagens));?></label>
										</p>
										<p>
											<label for="actividade">Actividade: <?php echo(MySQLOperations::getProperty("Titulo","agenda_tbl","idActividade",$subscriptionidActividade));?></label>
										</p>
										<p>
											<label for="nome">Nome: </label>
											<input id="nome" name="nome" readonly="readonly" value="<?php echo($subscriptionNome);?>" />
										</p>
										<p>
											<label for="email">E-mail: </label>
											<input id="email" name="email" class="ui-widget" readonly="readonly" value="<?php echo($subscriptioneMail);?>" />
										</p>
										<p>
											<label for="morada">Morada: </label>
											<input id="morada" name="morada" readonly="readonly" value="<?php echo($subscriptionMorada);?>" />
										</p>
										<p>
											<label for="data_nasc">Data Nascimento: </label>
											<input id="data_nasc" name="data_nasc" readonly="readonly" value="<?php echo($subscriptionData_Nascimento);?>" />
										</p>
										<p>
											<label for="telefone1">Telefone 1: </label>
											<input id="telefone1" name="telefone1" readonly="readonly" value="<?php echo($subscriptionTelefone1);?>" />
										</p>
										<p>
											<label for="telefone2">Telefone 2: </label>
											<input id="telefone2" name="telefone2" readonly="readonly" value="<?php echo($subscriptionTelefone2);?>" />
										</p>
										<p>
											<label for="estado">Estado: </label>
											<select id="estado" name="estado">
												<option value="N" <?php if($subscriptionEstado=="N"){ echo "selected='selected'";}?> ><?php echo getEstadoString("N");?></option>
												<option value="I" <?php if($subscriptionEstado=="I"){ echo "selected='selected'";}?>><?php echo getEstadoString("I");?></option>
												<option value="V" <?php if($subscriptionEstado=="V"){ echo "selected='selected'";}?>><?php echo getEstadoString("V");?></option>
												<option value="R" <?php if($subscriptionEstado=="R"){ echo "selected='selected'";}?>><?php echo getEstadoString("R");?></option>
											</select>
											<br/>
											<textarea id="Razao_Rejeicao" name="Razao_Rejeicao" rows="5" cols="50" style="width: 50%;display:none;"><?php echo(clearTagsFrom($subscriptionRazao_Rejeicao)); ?></textarea>
										</p>
										<input type="hidden" name="subscriptionId" value="<?php echo($subscriptionidInscricao); ?>" />
										<input type="hidden" name="subOperation" value="save" />
										<input type="hidden" name="typeSubscription" value="notfull" />
										<input type="submit" name="formAction" value="Guardar" />
										<input type="button" name="formAction" value="Cancelar" />
										<?php 
										if($subscriptionremovido){
											echo '<input type="button" name="formAction" value="Recuperar" />';
										}else{
											echo '<input type="button" name="formAction" value="Eliminar" />';
										}
										?>
										
										<input type="reset" name="formReset" value="Repor" />
										<script type="text/javascript">
											/* <![CDATA[ */ 
												$(document).ready(function (event){
													if($('#estado').val()=="R"){
														$('#Razao_Rejeicao').removeAttr('disabled');
	  													$('#Razao_Rejeicao').show();
													}
													$("#activeEdit").click(function() {
														$("#editsubscriptionform input").each(function(){
														    if(this.readOnly){
																this.readOnly = false;
															}
													      });
														$("#activeEdit:parent").hide();
	  	  												$("input[name=typeSubscription]").val('full');

	  	  												return false;													
													 });
																											
													$('#estado').change(function(){
  														if(this.value=="R"){
  															$('#Razao_Rejeicao').removeAttr('disabled');
  	  														$('#Razao_Rejeicao').show();
  	  	  												}else{
  	  	  													$('#Razao_Rejeicao').attr('disabled', 'disabled');
  	  	  													$('#Razao_Rejeicao').hide();
  	  	  												}
  													});
												});
											/* ]]> */
										</script>
									</div>
								</form>
							</div>
						<?php										
					break;
				
				/* Apresentação da lista de inscrições na área administrativa */
				default:
					
					
					function adminSubscriptionsList($order=''){
						$result='';
						
						if($query = MySQLOperations::getQueryResult("
							SELECT `inscricoes`.`idInscricao` AS `subscriptionid`,
			        			`inscricoes`.`Nome` AS `nome`,
			        			`inscricoes`.`eMail` AS `email`,
			        			`inscricoes`.`estado` AS `estado`,
			        			`inscricoes`.`removido` AS `removido`,
			        			`inscricoes`.`Tipo_Inscricao` AS `tipo_inscricao`
			        		FROM `inscricoes_tbl` `inscricoes`
			        		ORDER BY `inscricoes`.`removido`, `inscricoes`.`idInscricao` ASC")):
							
			        		while($subscription = mysql_fetch_assoc($query)):
			        			if($subscription['removido']){
			        				$removido = "deleted";	
			        			}else{
			        				$removido ="";
			        			}
			        			$result.='<tr class="'.$removido.'">';
			        			$result.='<td class="subscriptionId"><a title="Clique para ver '.$subscription['subscriptionid'].'" href="./'.adminURL().'&amp;action=adminSubscriptions&amp;operation=viewSubscription&amp;subscriptionId='.$subscription['subscriptionid'].'">'.$subscription['nome'].'</a></td>';
			        			$result.='<td class="subscriptionEmail ">'.$subscription['email'].'</td>';
			        			$result.='<td class="subscriptionEstado ">'.getEstadoString($subscription['estado']).'</td>';
			        			$result.='<td class="subscriptionTipo ">'.$subscription['tipo_inscricao'].'</td>';
			        			$result.='</tr>';
			        			
			        		endwhile;
			        	endif;
			        	
			        	return (!empty($result))?$result:'<tr><td colspan="4">Sem inscrições</td></tr>';
					}
					
					?>
						<div class="usersList wrap">
							<div class="header">
								<h2>Inscrições</h2>
								<small>Seleccione a inscrição para ver em detalhe</small>
							</div>
							<table class="insideAdminTable" cellspacing="0" summary="Lista de inscrições">
								 <thead>
									  <tr>
										<th>Nome</th>
									  	<th style="width: 200px;">Email</th>
										<th style="width: 300px;">Estado</th>
										<th style="width: 100px;">Tipo Inscrição</th>
									  </tr>
								  </thead>
								  <tbody>
								  	<?php 
										echo(adminSubscriptionsList());
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