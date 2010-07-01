<?php
if(!function_exists("getKernelInstance")){
	error_log("Tentativa de acesso directo ao ficheiro ".__FILE__,0);
	// Silence is golden
	die('');
}

/**
 * Àrea pública
 */
getKernelInstance()->actionAddFunction("parseContent",function($content){
	$tagname = "subscriptions";
	findHtmlCommentInContent($tagname, $content, $contents);
	if(!empty($contents)){
		foreach($contents[1] as $index=>$htmltagname){
			// Se encontrámos a tag pretendida, vamos substituir o comentário por código nosso
			$result = '';
			if(ob_start()){
				
				switch(strtolower($_REQUEST['operation'])){
				/* Guarda ou cancela  */
				
				case 'submitsubscription':
					if(!empty($_POST)){
						if(strcasecmp($_POST['subOperation'],'save')==0){
							$error=false;
							$subscriptionId = $_POST['subscriptionId'];
							$isInsert=false;
							if($_POST['tipoInscricao']=="Subscritor da newsletter"){
								$newsletter = 1; 
							}else{
								$newsletter = $_POST['tipoInscricao'];
							}
							$operations = new MySQLOperations('inscricoes_tbl');
							if(($operations->insert('nome', "'".mysql_escape_string($_POST['nome'])."'",'inscricoes_tbl','idInscricao'))===false){
								getKernelInstance()->logMsg("Não foi possível inserir o nome do inscrito. (".mysql_error().")");
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
							}else if(($operations->setProperty('Tipo_Inscricao', "'".mysql_escape_string($_POST['tipoInscricao'])."'"))===false){
								getKernelInstance()->logMsg("Não foi possível definir o tipo de inscrição. (".mysql_error().")");
								$error = true;
							}else if(($operations->setProperty('AutorizaMensagens', "'".mysql_escape_string($newsletter)."'"))===false){
								getKernelInstance()->logMsg("Não foi possível identificar se pretende ou não newsletter. (".mysql_error().")");
								$error = true;
							}else if(($operations->setProperty('idActividade', "'".mysql_escape_string($_POST['actividade'])."'"))===false){
								getKernelInstance()->logMsg("Não foi possível identificar se pretende ou não newsletter. (".mysql_error().")");
								$error = true;
							}
							$isInsert=true;
							
							
							?>
								<div class="pagesEdit wrap">
									<div class="header">
										<?php if($error): ?>
											<h2>Erro</h2>
											<small>Ocorreu um erro ao processar o seu pedido. Tente novamente, se o erro persistir consulte o administrador para mais informações.</small>
										<?php else: ?>
											<h2>Inscrição <?php echo(($isInsert)?'inserida':'actualizada'); ?>...</h2>
										<?php endif; ?>
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
									<small>Sem dados para submeter. </small>
								</div>
							</div>
						<?php
					}
					break;
				
				
				/* Vamos mostrar o formulário de inscrição */
				default:
					
					?>
					<script type="text/javascript">
						/* <![CDATA[ */
							
							$(document).ready(function (event){
								function hideAll(){
									$("#authorization").hide();
									$("#activity").hide();
									$("#personaldata").hide();
									$("#formAction").hide();
								}
								hideAll();
								$("input[name=formAction][value=Inscrever]").click(function(){
									$("#addsubscriptionform").validate({
										rules: {
									    	email: {
									    		required: true,
									    		email: true
									    	},
											data_nasc:{
												date:true
											},
							    			telefone1: {
						    					digits: true,
						    					maxlength:9,
						    					minlength:9
						    				}
									  	}
									});
									
									
								});
								
								
								$('#tipoInscricao').change(function(){

									$("#nome").removeClass('required');
									$("#email").removeClass('required');
									$("#morada").removeClass('required');
									$("#data_nasc").removeClass('required');
									$("#telefone1").removeClass('required');
									$("#actividade").removeClass('required');
									$("#simautoriza").removeClass('required');

									$("#simautoriza").attr('checked', false);
									$("#naoautoriza").removeAttr('disabled');

									if(this.value==''){
										hideAll();
									}else if(this.value=='Sócio'){
										$("#activity").hide();
										$("#authorization").show();
										$("#personaldata").show();
										$("#formAction").show();

										$("#nome").addClass('required');
										$("#email").addClass('required');
										$("#morada").addClass('required');
										$("#data_nasc").addClass('required');
										$("#telefone1").addClass('required');
									}else if(this.value=='Subscritor da newsletter'){
										$("#activity").hide();
										$("#authorization").show();
										$("#personaldata").show();
										$("#formAction").show();

										$("#nome").addClass('required');
										$("#email").addClass('required');
										$("#simautoriza").attr('checked', true);
										$("#naoautoriza").attr('disabled', 'disabled');
									}else if(this.value=='Actividade'){
										$("#authorization").show();
										$("#activity").show();
										$("#personaldata").show();
										$("#formAction").show();

										$("#nome").addClass('required');
										$("#email").addClass('required');
										$("#morada").addClass('required');
										$("#data_nasc").addClass('required');
										$("#telefone1").addClass('required');
										$("#actividade").addClass('required');
									}
								});
							});
						/* ]]> */
					</script>
					<div id="errorPlace" class="ui-widget"></div>
					<form id="addsubscriptionform" action="./?action=showPage&amp;pageId=<?php echo $_REQUEST['pageId']?>&amp;operation=submitsubscription" method="post" enctype="application/x-www-form-urlencoded">
						<fieldset>
	    					<legend>Tipo inscrição:</legend>
							<label for='tipoinscricao'>Inscrever em:</label>
							<select id="tipoInscricao" name="tipoInscricao" class="ui-widget">
								<option value="" selected="selected">&nbsp;</option>
								<option value="Sócio" >Sócios</option>
								<option value="Actividade" >Actividades</option>
								<option value="Subscritor da newsletter" >Newsletters</option>
							</select>
							<br/>
						</fieldset>
						<fieldset id="authorization">
	    					<legend>Autorizações:</legend>
							<label>Receber newsletter:</label>
							<input type="radio" name="autoriza" id="simautoriza" class="ui-widget"  value="1" /> <label for="simautoriza">Sim</label> 
							<input type="radio" name="autoriza" id="naoautoriza" class="ui-widget"  value="0" checked="checked" /> <label for="naoautoriza">Não</label><br />
						</fieldset>
						<?php 
							$query = MySQLOperations::getQueryResult("
										SELECT `agenda`.*
        								FROM `agenda_tbl` `agenda`
        								WHERE (`agenda`.`Aceita_Inscricao` = '1' AND `agenda`.`Ja_Decorreu`!='0' AND `agenda`.`removido`!='0' )
        							");
							
							if($query){
								?>
								<fieldset id="activity">
			    					<legend>Actividades:</legend>
									<label for="actividade">Actividade:</label>
									<select id="actividade" name="actividade">
										<option value="" >&nbsp;</option>
										<?php
										while($agenda = mysql_fetch_assoc($query)){
											echo "<option value='".$agenda["idActividade"]."'>".$agenda["Titulo"]."</option>";
										}
										?>
									</select>
								</fieldset>
								
								<?php
							}
						?>
						
						<fieldset id="personaldata">
	    					<legend>Dados pessoais:</legend>
							<label for="nome">Nome: </label>
							<input id="nome" name="nome" class="ui-widget" value="" />
						<p>
							<label for="email">E-mail: </label>
							<input id="email" name="email" class="ui-widget" value="" />
						</p>
						<p>
							<label for="morada">Morada: </label>
							<input id="morada" name="morada" class="ui-widget" value="" />
						</p>
						<p>
							<label for="data_nasc">Data Nasc.: </label>
							<input id="data_nasc" name="data_nasc" class="ui-widget" value="" />
						</p>
						<p>
							<label for="telefone1">Telefone 1: </label>
							<input id="telefone1" name="telefone1" class="ui-widget" value="" />
						</p>
							<label for="telefone2">Telefone 2: </label>
							<input id="telefone2" name="telefone2" class="ui-widget" value="" />
						</fieldset>
						<input type="hidden" name="subOperation" value="save" />
						<input type="submit" id="formAction" name="formAction" value="Inscrever" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-onlyui-button ui-widget " />
					</form>
				<?php 
					
				}
				
				$result .= ob_get_contents();
				ob_end_clean();
			}
			
			$content = str_ireplace($contents[0][$index],$result,$content);
		}
	}
	return $content;
});