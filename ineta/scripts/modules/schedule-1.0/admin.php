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
			<li class="adminSchedulesModule <?php echo(strcasecmp($_REQUEST['action'],'adminSchedules')==0?' currentPage':''); ?>"><a href="./<?php echo adminURL();?>&amp;action=adminSchedules">Agenda</a></li>	
		<?php
		return true;
	});
	
	// Carregar itens apenas se estamos na nossa área
	if(strcasecmp($_REQUEST['action'],'adminSchedules')==0):
		getKernelInstance()->actionAddFunction("_init", function(){
			$kernel = getKernelInstance();
			if(!$kernel->actionExecuted("requestJQuery") && !$kernel->actionExecute("requestJQuery")){
				$kernel->logMsg("Não foi possível carregar a biblioteca jquery no módulo schedule.");
			}
			// 	Carregar apenas o que precisamos consoante a secção a apresentar
			switch(strtolower($_REQUEST['operation'])){
				/* Formulário de edição de páginas */
				case 'viewschedule':
					// Para carregar o código necessário para incluir o TinyMCE
					$kernel->actionExecute("requestTinyMCE");					
				break;
			}
		});
		
		// Adiciona os estilos específicos do módulo
		getKernelInstance()->actionAddFunction("adminHead", function(){
			switch(strtolower($_REQUEST['operation'])){
				/* Guarda ou cancela  */
				case 'submitschedule':
					
				break;
				/* Formulário para alteração de evento */
				case 'viewschedule':
					?>
						<script type="text/javascript">
							/* <![CDATA[ */
								function activateForm(){
									$("#editscheduleform textarea").each(function(){
										if(this.disabled){
											this.disabled = false;
										}
									});
									$("#editscheduleform select").each(function(){
										if(this.disabled){
											this.disabled = false;
										}
									});
									$("#editscheduleform input").each(function(){
										if(this.disabled){
											this.disabled = false;
										}
									});
									$("#editscheduleform input").each(function(){
									    if(this.readOnly){
											this.readOnly = false;
										}
								      });
									$("#activeEdit:parent").hide();

    									return false;												
								}

								$(document).ready(function (event){
									$("input[name=formAction][value=Cancelar]").click(function(event) {
								        if(confirm("Tem a certeza de que pretende cancelar? Quaisquer alterações que tenha efectuado serão perdidas.")){
								        	window.location = "./<?php echo adminURL();?>&amp;action=adminPage";
								        }
								    });

									$("input[name=formAction][value=Eliminar]").click(function(event) {
								        if(confirm("Tem a certeza de que pretende eliminar este evento?")){
								        	$("input[name=subOperation]").val('delete');
								        	$("#editscheduleform").submit();
								        }
								    });
								    $("input[name=formAction][value=Recuperar]").click(function(event) {
								        if(confirm("Tem a certeza de que pretende recuperar este evento?")){
								        	$("input[name=subOperation]").val('undelete');
								        	$("#editscheduleform").submit();
								        }
								    });

									$("input[name=formAction][value=Guardar]").click(function(event) {
										var error = false;
										
										$("#editscheduleform").validate({
											rules: {
										    	Titulo: {
										    		required: true
										    	},
										    	Descricao: {
										    		required: true
										    	},
												Data:{
										    		required: true,
													date:true
												},
										    	Organizador: {
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
				/* Apresentação da lista de eventos na área administrativa */
				default:
			}
		});
	
		// Adiciona o conteúdo à página administrativa
		getKernelInstance()->actionAddFunction("adminContent", function(){
			function getString($var){
				switch($var){
        			case "EA":
        				return "Evento da AFAC";
        				break;
        			case "AA":
        				return "Actividade da AFAC";
        				break;
        			case "EO":
        				return "Evento de outra entidade";
        				break;
        			case "AO":
        				return "Actividade de outra entidade";
        				break;
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
        				return "Não";
        				break;
        			default:
        				return "Erro";
				}
			}
			
			switch(strtolower($_REQUEST['operation'])){
				/* Guarda ou cancela  */
				case 'submitschedule':
					if(!empty($_POST)){
						if(strcasecmp($_POST['subOperation'],'save')==0){
							$error=false;
							$scheduleId = $_POST['scheduleId'];
							$isInsert=false;
 
							if(is_numeric($scheduleId) && $scheduleId>0){
								$operations = new MySQLOperations('agenda_tbl', 'idActividade', $scheduleId);
								if(($operations->setProperty('Titulo', "'".mysql_escape_string($_POST['Titulo'])."'"))===false){
										getKernelInstance()->logMsg("Não foi possível definir o titulo do evento - edit. (".mysql_error().")");
										$error = true;
								}
									
									$isInsert=false;
							}else{
								$operations = new MySQLOperations('agenda_tbl');
								if(($scheduleId = $operations->insert('Titulo', "'".mysql_escape_string($_POST['Titulo'])."'",'agenda_tbl','idActividade'))===false){
									getKernelInstance()->logMsg("Não foi possível definir o titulo do evento - insert. (".mysql_error().")");
									$error = true;
								}
								$isInsert=true;
							}
							
							if(($operations->setProperty('Tipo_Actividade',"'".mysql_escape_string($_POST['Tipo_Actividade'])."'"))===false){
								getKernelInstance()->logMsg("Não foi possível identificar o tipo de actividade. (".mysql_error().")");
								$error = true;
							}else if(($operations->setProperty('Ja_Decorreu', "'".mysql_escape_string($_POST['Ja_Decorreu'])."'"))===false){
								getKernelInstance()->logMsg("Não foi possível definir se o evento já decorreu. (".mysql_error().")");
								$error = true;
							}else if(($operations->setProperty('Em_Destaque', "'".mysql_escape_string($_POST['Em_Destaque'])."'"))===false){
								getKernelInstance()->logMsg("Não foi possível definir se o evento está em destaque. (".mysql_error().")");
								$error = true;
							}else if(($operations->setProperty('Aceita_Inscricao', "'".mysql_escape_string($_POST['Aceita_Inscricao'])."'"))===false){
								getKernelInstance()->logMsg("Não foi possível definir se o evento aceita inscrição. (".mysql_error().")");
								$error = true;
							}else if(($operations->setProperty('Data', "'".mysql_escape_string($_POST['Data'])."'"))===false){
								getKernelInstance()->logMsg("Não foi possível definir a data do evento. (".mysql_error().")");
								$error = true;
							}else if(($operations->setProperty('Organizador', "'".mysql_escape_string($_POST['Organizador'])."'"))===false){
								getKernelInstance()->logMsg("Não foi possível definir o organizador do evento. (".mysql_error().")");
								$error = true;
							}else if(($operations->setProperty('Descricao', "'".mysql_escape_string($_POST['Descricao'])."'"))===false){
								getKernelInstance()->logMsg("Não foi possível definir a descrição do evento. (".mysql_error().")");
								$error = true;
							}else if(($operations->setProperty('Rescaldo', "'".mysql_escape_string($_POST['Rescaldo'])."'"))===false){
								getKernelInstance()->logMsg("Não foi possível definir o rescaldo do evento. (".mysql_error().")");
								$error = true;
							}
							
							?>
								<div class="pagesEdit wrap">
									<div class="header">
										<?php if($error): ?>
											<h2>Erro</h2>
											<small>Ocorreu um erro ao processar o seu pedido. Consulte o administrador para mais informações.</small>
										<?php else: ?>
											<h2>Evento <?php echo(($isInsert)?'inserido':'actualizado'); ?>...</h2>
											<small>Voltar a <a href="./<?php echo adminURL();?>&amp;action=adminSchedules&amp;operation=viewSchedule&amp;scheduleId=<?php echo($scheduleId); ?>" title="Clique para voltar a editar o evento">editar o evento</a> ou à <a href="./<?php echo adminURL();?>&amp;action=adminSchedules" title="Clique para voltar à lista de eventos">lista de eventos</a></small>
										<?php endif; ?>
									</div>
								</div>
							<?php
						}else if(strcasecmp($_POST['subOperation'],'delete')==0){
							$scheduleId = $_POST['scheduleId'];
							
							?>
								<div class="pagesEdit wrap">
									<div class="header">
										<?php
											if(is_numeric($scheduleId) && $scheduleId>0){
												$operations = new MySQLOperations('agenda_tbl', 'idActividade', $scheduleId);
												if(($operations->setProperty('removido', "1"))===false){
													getKernelInstance()->logMsg("Não foi possível marcar a inscrição como eliminada $scheduleId. (".mysql_error().")");
													?>
														<h2>Erro</h2>
														<small>Ocorreu um erro ao processar o seu pedido. Consulte o administrador para mais informações.</small>
													<?php 
												}else{
													?>
														<h2>Evento eliminada</h2>
														<small>Voltar à <a href="./<?php echo adminURL();?>&amp;action=adminSchedules" title="Clique para voltar à lista de eventos">lista de eventos</a></small>
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
							$scheduleId = $_POST['scheduleId'];
							
							?>
								<div class="pagesEdit wrap">
									<div class="header">
										<?php
											if(is_numeric($scheduleId) && $scheduleId>0){
												$operations = new MySQLOperations('agenda_tbl', 'idActividade', $scheduleId);
												if(($operations->setProperty('removido', "0"))===false){
													getKernelInstance()->logMsg("Não foi possível recuperar o evento $scheduleId. (".mysql_error().")");
													?>
														<h2>Erro</h2>
														<small>Ocorreu um erro ao processar o seu pedido. Consulte o administrador para mais informações.</small>
													<?php 
												}else{
													?>
														<h2>Evento recuperada</h2>
														<small>Voltar à <a href="./<?php echo adminURL();?>&amp;action=adminSchedules" title="Clique para voltar à lista de eventos">lista de eventos</a></small>
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
				case 'viewschedule':
					$scheduleId = (is_numeric($_REQUEST['scheduleId']))?$_REQUEST['scheduleId']:false;
					if($scheduleId > 0){
						$newSchedule = 0;
					}else{
						$newSchedule = 1;
					}
					if(!$scheduleId){
						break;
					}
					
					if($query = MySQLOperations::getQueryResult("
						SELECT `agenda`.*
		        		FROM `agenda_tbl` `agenda`
		        		WHERE (`agenda`.`idActividade` = '$scheduleId' ) 
		        		LIMIT 1
		        	")){
	        			if($schedule = mysql_fetch_assoc($query)){
							$scheduletsInsercao = $schedule['tsInsercao'];
							$scheduleremovido = $schedule['removido'];
							$scheduleTipoActividade = $schedule['Tipo_Actividade'];
							$scheduleJaDecorreu = $schedule['Ja_Decorreu'];
							$scheduleEmDestaque = $schedule['Em_Destaque'];
							$scheduleAceitaInscricao = $schedule['Aceita_Inscricao'];
							$scheduleTitulo = $schedule['Titulo'];
							$scheduleDescricao = $schedule['Descricao'];
							$scheduleData = $schedule['Data'];
							$scheduleOrganizador = $schedule['Organizador'];
							$scheduleRescaldo = $schedule['Rescaldo'];
	        			}
		        	}
					
						?>
							<div class="userView wrap">
								<div class="header">
									<h2><?php echo(((!empty($scheduleTitulo))?"A visualizar o evento $scheduleTitulo:":"A inserir novo evento")); ?></h2>
									<small>Preencha o formulário.</small>
								</div>
								
								<form id="editscheduleform" action="./<?php echo adminURL();?>&amp;action=adminSchedules&amp;operation=submitschedule" method="post" enctype="application/x-www-form-urlencoded">
									<div class="content">
										<?php if($newSchedule==0):?>
										<button id="activeEdit" class="floatright clearall ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only"><span class="ui-button-text">Editar dados do evento</span></button>
										<?php endif;?>
										<br/>
										<br/>
										<br/>
										<?php 
										if($scheduleremovido){
											echo "<div style='padding: 0pt 0.7em;' class='ui-state-highlight ui-corner-all'><p><span style='float: left; margin-right: 0.3em;' class='ui-icon ui-icon-info'>&nbsp;</span>Este evento já foi eliminado</p></div>";
										}
										?>
										<div id="errorPlace" class="ui-widget"></div>
										<p>
											<label>Inserção: <?php echo($scheduletsInsercao);?></label>
										</p>
										<p>
											<label for="Tipo_Actividade">Tipo actividade: </label>
											<select id="Tipo_Actividade" name="Tipo_Actividade" disabled="disabled" >
												<option value="EA" <?php if($scheduleTipoActividade=="EA"){ echo "selected='selected'";}?> ><?php echo getString("EA");?></option>
												<option value="AA" <?php if($scheduleTipoActividade=="AA"){ echo "selected='selected'";}?>><?php echo getString("AA");?></option>
												<option value="EO" <?php if($scheduleTipoActividade=="EO"){ echo "selected='selected'";}?>><?php echo getString("EO");?></option>
												<option value="AO" <?php if($scheduleTipoActividade=="AO"){ echo "selected='selected'";}?>><?php echo getString("AO");?></option>
											</select>
											<br/>
										</p>
										<p>
											<label for="Ja_Decorreu">Já Decorreu: </label>
											<select id="Ja_Decorreu" name="Ja_Decorreu" disabled="disabled" >
												<option value="0" <?php if($scheduleJaDecorreu=="0"){ echo "selected='selected'";}?> >Não</option>
												<option value="1" <?php if($scheduleJaDecorreu=="1"){ echo "selected='selected'";}?>>Sim</option>
											</select>
											<br/>
										</p>
										<p>
											<label for="Aceita_Inscricao">Aceita Inscrição: </label>
											<select id="Aceita_Inscricao" name="Aceita_Inscricao" disabled="disabled" >
												<option value="0" <?php if($scheduleAceitaInscricao=="0"){ echo "selected='selected'";}?> >Não</option>
												<option value="1" <?php if($scheduleAceitaInscricao=="1"){ echo "selected='selected'";}?>>Sim</option>
											</select>
											<br/>
										</p>
										<p>
											<label for="Titulo">Titulo: </label>
											<input id="Titulo" name="Titulo" readonly="readonly" value="<?php echo($scheduleTitulo);?>" />
										</p>
										<p>
											<label for="Descricao">Descrição: </label>
											<br/>											
											<textarea id="Descricao" name="Descricao" rows="5" cols="50"  class="tinymce" style="width: 50%;"><?php echo($scheduleDescricao); ?></textarea>
										</p>
										<p>
											<label for="Data">Data: </label>
											<input id="Data" name="Data" readonly="readonly" value="<?php echo($scheduleData);?>" />
										</p>
										<p>
											<label for="Em_Destaque">Em Destaque: </label>
											<select id="Em_Destaque" name="Em_Destaque" disabled="disabled" >
												<option value="0" <?php if($scheduleEmDestaque=="0"){ echo "selected='selected'";}?> >Não</option>
												<option value="1" <?php if($scheduleEmDestaque=="1"){ echo "selected='selected'";}?>>Sim</option>
											</select>
											<br/>
										</p>
										<p>
											<label for="Organizador">Organizador: </label>
											<input id="Organizador" name="Organizador" readonly="readonly" value="<?php echo($scheduleOrganizador);?>" />
										</p>
										<p>
											<label for="Rescaldo">Rescaldo: </label>
											<br/>											
											<textarea id="Rescaldo" name="Rescaldo" rows="5" cols="50" disabled="disabled" style="width: 50%;"><?php echo($scheduleRescaldo); ?></textarea>
										</p>
										
										<input type="hidden" disabled="disabled" name="scheduleId" value="<?php echo($scheduleId); ?>" />
										<input type="hidden" disabled="disabled" name="subOperation" value="save" />
										<input type="submit" disabled="disabled" name="formAction" value="Guardar" />
										<input type="button" disabled="disabled" name="formAction" value="Cancelar" />
										<?php 
										if($scheduleremovido){
											echo '<input type="button" disabled="disabled" name="formAction" value="Recuperar" />';
										}else{
											echo '<input type="button" disabled="disabled" name="formAction" value="Eliminar" />';
										}
										?>
										
										<input type="reset" disabled="disabled" name="formReset" value="Repor" />
										<script type="text/javascript">
											/* <![CDATA[ */ 
												$(document).ready(function (event){
													var activeForm = "<?php echo $newSchedule;?>";
													$("#activeEdit").click(function() {
														activateForm();
														return false;
													});
													if(activeForm=="1"){
														activateForm();
													}
																											
													
												});
											/* ]]> */
										</script>
									</div>
								</form>
							</div>
						<?php			
		        break;
				
				/* Apresentação da lista de eventos na área administrativa */
				default:
					
					
					function adminScheduleList($order=''){
						$result='';
						
						if($query = MySQLOperations::getQueryResult("
							SELECT `agenda`.`idActividade` AS `idActividade`,
			        			`agenda`.`removido` AS `removido`,
			        			`agenda`.`Tipo_Actividade` AS `tipo_actividade`,
			        			`agenda`.`Ja_Decorreu` AS `ja_decorreu`,
			        			`agenda`.`Em_Destaque` AS `em_destaque`,
			        			`agenda`.`Aceita_Inscricao` AS `aceita_inscricao`,
			        			`agenda`.`Titulo` AS `titulo`,
			        			`agenda`.`Data` AS `data`,
			        			`agenda`.`Organizador` AS `organizador`
			        		FROM `agenda_tbl` `agenda`
			        		ORDER BY `agenda`.`removido`, `agenda`.`Ja_Decorreu`, `agenda`.`Data` ASC")):
							
			        		while($schedule = mysql_fetch_assoc($query)):
			        			if($schedule['removido']){
			        				$removido = "deleted";	
			        			}else{
			        				$removido ="";
			        			}
			        			$result.='<tr class="'.$removido.'">';
			        			$result.='<td class="scheduleId"><a title="Clique para ver '.$schedule['idActividade'].'" href="./'.adminURL().'&amp;action=adminSchedules&amp;operation=viewSchedule&amp;scheduleId='.$schedule['idActividade'].'">'.$schedule['titulo'].'</a></td>';
			        			$result.='<td class="scheduleEmail ">'.$schedule['organizador'].'</td>';
			        			$result.='<td class="scheduleEstado ">'.getString($schedule['aceita_inscricao']).'</td>';
			        			$result.='<td class="scheduleTipo ">'.$schedule['tipo_actividade'].'</td>';
			        			$result.='<td class="scheduleTipo ">'.getString($schedule['em_destaque']).'</td>';
			        			$result.='<td class="scheduleTipo ">'.$schedule['data'].'</td>';
			        			$result.='</tr>';
			        			
			        		endwhile;
			        	endif;
			        	
			        	return (!empty($result))?$result:'<tr><td colspan="6">Sem eventos</td></tr>';
					}
					
					?>
						<div class="usersList wrap">
							<div class="header">
								<h2>Agenda</h2>
								<small>Seleccione o evento para ver em detalhe, ou pressione <cite>Inserir evento</cite> para inserir um novo evento:</small>
							</div>
							<table class="insideAdminTable" cellspacing="0" summary="Lista de eventos">
								 <thead>
									  <tr>
										<th>Titulo</th>
									  	<th style="width: 200px;">Organizador</th>
										<th style="width: 115px;">Aceita Inscricao</th>
										<th style="width: 105px;">Tipo Actividade</th>
										<th style="width: 100px;">Em Destaque</th>
										<th style="width: 100px;">Data</th>
									  </tr>
								  </thead>
								  <tfoot>
									  	<tr>
									  		<th colspan="6"><a href="./<?php echo adminURL();?>&amp;action=adminSchedules&amp;operation=viewSchedule&amp;scheduleId=-1" title="Clique para adicionar um novo evento">Inserir evento</a></th>
								  		</tr>
								  </tfoot>
								  <tbody>
								  	<?php 
										echo(adminScheduleList());
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