<?php

// Desactivar o acesso directo ao ficheiro
if(!function_exists("adminEnabled")){
	error_log("Tentativa de acesso directo ao ficheiro ".__FILE__,0);
	// Silence is golden
	die('');
}
if(!adminEnabled()){
	getKernelInstance()->actionAddFunction("_init", function(){
		$kernel = getKernelInstance();
		if(!$kernel->actionExecuted("requestJQuery") && !$kernel->actionExecute("requestJQuery")){
			$kernel->logMsg("Não foi possível carregar a biblioteca jquery no módulo public.");
		}
		
		
	});
	getKernelInstance()->actionAddFunction("_head", function(){
			
		?>
			<?php /* Reset Browser Default */ ?>
			<link rel="stylesheet" type="text/css" href="./<?php echo adminURL();?>&amp;script=<?php echo(getKernelInstance()->getPathFromRoot(__FILE__)); ?>css/reset.css" media="all" />
			<?php /* Typography */ ?>
			<link rel="stylesheet" type="text/css" href="./<?php echo adminURL();?>&amp;script=<?php echo(getKernelInstance()->getPathFromRoot(__FILE__)); ?>css/typography.css" media="all" />
			<?php /* Template */ ?>
			<link rel="stylesheet" type="text/css" href="./<?php echo adminURL();?>&amp;script=<?php echo(getKernelInstance()->getPathFromRoot(__FILE__)); ?>css/template.css" media="all" />
			<?php /* Menu */ ?>
			<link rel="stylesheet" type="text/css" href="./<?php echo adminURL();?>&amp;script=<?php echo(getKernelInstance()->getPathFromRoot(__FILE__)); ?>css/menu.css" media="all" />
			<?php /* Public */ ?>
			<link rel="stylesheet" type="text/css" href="./<?php echo adminURL();?>&amp;script=<?php echo(getKernelInstance()->getPathFromRoot(__FILE__)); ?>public.css" media="all" />
			<script type="text/javascript">
					$(document).ready(function (event){
						$("#usermenu div.tab").click(function () {
							$("#usermenu div.caption").slideToggle("normal");
							$("#usermenu div.tab a img").toggle();
						}).show();
						$("#usermenu div.caption").hide();

						/*Accordion*/
						$("#accordionSidebar").accordion({ header: "h3", autoHeight:true});
						
					});
			</script>
		<?php 
	});
	
	getKernelInstance()->actionAddFunction("_body", function(){
		
		?>
			
			<!-- Layout -->
			
			<div id="page">
		<div id="wrapper">
			<div id="header">
				<div id="head">
					<div id="usermenu">
						<div class="caption">
								<?php if(User::isUserAuthenticated()): ?>
									<ul>
										<li><a href="./?adminURL&amp;view=admin">Aceder à área administrativa</a></li>
										<li><a href="./?adminURL&amp;action=logout">Terminar Sessão</a></li>
									</ul>
								<?php else: ?>
									<form id="login" method="post" action="./?action=login">
										<div>
											<label for="username">Utilizador:</label> <input type="text" id="username" name="username" value="" />
											<label for="password">Password:</label> <input type="password" id="password" name="password" value="" />
											<input type="submit" value="Autenticar" name="Submit" />
										</div>
									</form>
								<?php endif; ?>
						</div>
						<div class="clear tab">
							<a href="#" title="Users">
								<img src="./images/icons/arrow-dn.png" alt="btn" />
								<img src="./images/icons/arrow-up.png" alt="btn" style="display: none" />
							</a>
						</div>
					</div>
					<div class="logotext"><h1><a href="./" title="<?php echo(PRODUCT_FULL_NAME); ?>"><img src="<?php echo(IMAGESDIR); ?>logo.png" alt="<?php echo(PRODUCT_NAME); ?>" /></a></h1></div>
				</div>

				<div id="menubar">
					<div id="mainmenu">
						<?php getKernelInstance()->actionExecute("publicMenu");?>
					</div>

				</div>
				<!-- 
				<div id="searchform">
					<form action="./" method="post" id="search-box">
						<div class="search-wrapper">
							<input type="text" value="Search..." class="textfield" name="s" id="search-text" onblur="if(this.value=='') this.value='Search...';" onfocus="if(this.value=='Search...') this.value='';" />
						</div>
					</form>
				</div>
				-->
			</div><!-- End header --> 

			<div id="wrap">
				<div id="container">
					<div id="content">
						
					<?php
						if(isset($_REQUEST['action'])){
							getKernelInstance()->actionExecute("showPage");
							if(!getKernelInstance()->actionExecuted("showPage")){
								getKernelInstance()->actionExecute("show404Page");
							}
						}else{
							getKernelInstance()->actionExecute("showDefaultPage");
						}
					?>
			
					</div><!-- end #content -->

					<div id="sidebar">
					
						<ul>
							<li class="widget_meta">
								<h2>Ligações</h2>
								<div><?php getKernelInstance()->actionExecute("authenticationMessage"); ?>&nbsp;</div>
								<ul>
									<?php if(User::isUserAuthenticated()): ?>
										<li><a href="./?adminURL">Aceder à área administrativa</a></li>
										<li><a href="./<?php echo adminURL();?>&amp;action=logout">Terminar Sessão</a></li>
									<?php else: ?>
										<li><a href="#">Uma opção na barra lateral</a></li>
									<?php endif; ?>
								</ul>
							</li>
							
						</ul>
						
						<?php 
							if(!function_exists('getString')){
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
							}
							function scheduleListSidebar(){
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
						        	WHERE `agenda`.`removido` != 1 AND `agenda`.`Ja_decorreu` != 1
						        	ORDER BY `agenda`.`Data` ASC
						        	LIMIT 0 , 3")):
									
						        	while($schedule = mysql_fetch_assoc($query)):
						        		$result.='<h3><span class="ui-icon ui-icon-triangle-1-e">&nbsp;</span><a href="#">'.$schedule['titulo'].' '.$schedule['data'].'</a></h3>';
										$result.='<div>';
										(!empty($schedule['aceita_inscricao']))?$result.='<p><strong>Aceita inscrições:</strong> '.getString($schedule['aceita_inscricao']).'</p>':'';
										(!empty($schedule['organizador']))?$result.='<p><strong>Organizador:</strong> '.$schedule['organizador'].'</p>':'';
										$result.='</div>';
						        	endwhile;
						        endif;
						        
						        return (!empty($result))?"<h2>Eventos em destaque</h2>".$result:'';
							}
							function extLinksListSidebar(){
								$result='';
								
								if($query = MySQLOperations::getQueryResult("
									SELECT `extlink`.*
		        					FROM `extlinks_tbl` `extlink`")):
									
						        	while($link = mysql_fetch_assoc($query)):
						        		$result.='<li>';
										$result.='<a href="http://'.$link['link'].'">';
										$result.=$link['link'];
										$result.='</a>';
										$result.='</li>';
						        	endwhile;
						        endif;
						        
						        return (!empty($result))?"<h2>Links externos</h2><ul><li  class=\"widget_meta\"><ul>".$result."</ul></li></ul>":'';
							}
						?>
						<ul>
							<li id="accordionSidebar" class="widget_meta ui-accordion ui-widget ui-helper-reset ui-accordion-icons">
							  	<?php 
									echo(scheduleListSidebar());
								?>
							</li>
						</ul>
						
					  	<?php 
							echo(extLinksListSidebar());
						?>
					</div>

					</div><!-- end #container -->
				<div class="clear">&nbsp;</div>
			</div><!-- end #wrap -->

			<div id="bottom">
				<div id="footer">&copy;<?php echo(licenseDate('2010').' '.PRODUCT_FULL_NAME); ?>, alguns direitos reservados<div class="small">Desenvolvido por Cláudio Esperança e Diogo Serra</div></div>
			</div>

		</div><!-- end #wrapper -->
	</div><!-- end #page -->
		<?php 
	});
}