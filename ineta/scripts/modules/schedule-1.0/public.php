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
	function scheduleList($order=''){
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
        		`agenda`.`Organizador` AS `organizador`,
        		`agenda`.`Descricao` AS `descricao`,
        		`agenda`.`Rescaldo` AS `rescaldo`
        	FROM `agenda_tbl` `agenda` WHERE `agenda`.`removido` != 1
        	ORDER BY `agenda`.`Em_Destaque`, `agenda`.`Data`,`agenda`.`Tipo_Actividade`, `agenda`.`Ja_Decorreu` ASC")):
			
        	while($schedule = mysql_fetch_assoc($query)):
        		$result.='<h3><span class="ui-icon ui-icon-triangle-1-e">&nbsp;</span><a href="#">'.$schedule['tipo_actividade'].' - '.$schedule['titulo'].' '.$schedule['data'].'</a></h3>';
				$result.='<div>';
				$result.='<strong>Descrição:</strong> '.$schedule['descricao'];
				(!empty($schedule['aceita_inscricao']))?$result.='<p><strong>Aceita inscrições:</strong> '.getString($schedule['aceita_inscricao']).'</p>':'';
				(!empty($schedule['ja_decorreu']))?$result.='<p><strong>Já decorreu:</strong> '.getString($schedule['ja_decorreu']).'</p>':'';
				(!empty($schedule['organizador']))?$result.='<p><strong>Organizador:</strong> '.$schedule['organizador'].'</p>':'';
				(!empty($schedule['rescaldo']))?$result.='<strong>Rescaldo:</strong> '.$schedule['rescaldo'].'':'';
				$result.='</div>';
        	endwhile;
        endif;
        
        return (!empty($result))?$result:'Sem eventos';
	}
	$tagname = "schedule";
	findHtmlCommentInContent($tagname, $content, $contents);
	if(!empty($contents)){
		foreach($contents[1] as $index=>$htmltagname){
			// Se encontrámos a tag pretendida, vamos substituir o comentário por código nosso
			
			$result = '';
			if(ob_start()){
				?>
					<script type="text/javascript">
						/* <![CDATA[ */
							
							$(document).ready(function (event){
								/*Accordion*/
								$("#accordion").accordion({ header: "h3", autoHeight:true});			
								
								
							});
						/* ]]> */
					</script>
					
					
					<div id="accordion" class="ui-accordion ui-widget ui-helper-reset ui-accordion-icons">
					  	<?php 
							echo(scheduleList());
						?>
					</div>

				<?php 
				$result .= ob_get_contents();
				ob_end_clean();
			}
			
			$content = str_ireplace($contents[0][$index],$result,$content);
		}
	}
	return $content;
});