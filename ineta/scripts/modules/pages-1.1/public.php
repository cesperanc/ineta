<?php
if(!function_exists("getKernelInstance")){
	error_log("Tentativa de acesso directo ao ficheiro ".__FILE__,0);
	// Silence is golden
	die('');
}

/**
 * Área pública
 */
getKernelInstance()->actionAddFunction("publicMenu",function(){
	$pageId = (is_numeric($_REQUEST['pageId']))?$_REQUEST['pageId']:false;
		
	function publicPagesTreeList($parentId=NULL, $level=0, $order=''){
		$result='<ul class="sf-menu">';
		
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
	        	$result.='<li class="page_item page-item-'.$page['pageid'].'">';
				if($dash){
					$aclass = "sf-with-ul";
				}
	        	$result.='<a class="'.$aclass.'" title="Clique para visitar '.$page['title'].'" href="./?action=showPage&amp;pageId='.$page['pageid'].'">'.$page['title'].'</a>';
	        	
	        	if($page['leaf']==='false'){
	        		$result.=publicPagesTreeList($page['pageid'], $level+1, $order.$page['ordem'].'.');
	        	}
	        	
	        	$result.='</li>';
	        endwhile;
	        if(!$dash){
	        	$result.='<li class="page_last">&nbsp;</li>';
	        }
	        $result.='</ul>';
        endif;
        return (!empty($result))?$result:"<li>Sem paǵinas</li>";
	}
	echo(publicPagesTreeList());
});

function showPage($pageId){
	if(is_numeric($pageId) && $pageId>0){
		$mysqlOperations = new MySQLOperations('paginas_tbl','id',$pageId);
		if($title = $mysqlOperations->getProperty('titulo')){
			?>
				<div class="content-header">
					<h2><?php echo(getKernelInstance()->actionExecute("parseTitle",$title)); ?><?php if(isUserAnAdmin()){ echo("<small><a href=\"./?adminURL&amp;action=adminPage&amp;operation=editpage&amp;pageId=$pageId\" title=\"Clique para editar a página\">editar</a></small>"); } ?></h2>
				</div>
				<div class="post">
					<div class="entry">
						<?php echo(getKernelInstance()->actionExecute("parseContent",$mysqlOperations->getProperty('conteudo'))); ?>
					</div>
				</div>
			<?php
			return true;
		}
	}
	return false;
};

getKernelInstance()->actionAddFunction("showPage",function(){
	if($_REQUEST['action']=='showPage'){
		return showPage($_REQUEST['pageId']);
	}
	return false;
},10);

getKernelInstance()->actionAddFunction("showDefaultPage",function(){
	if($query = MySQLOperations::getQueryResult("
		SELECT `pages`.`id` AS `pageid`
        FROM `paginas_tbl` `pages`
        WHERE `pages`.`id_pai` is NULL 
        ORDER BY `pages`.`ordem`, `pages`.`id` ASC LIMIT 0,1")){

		while($page = mysql_fetch_assoc($query)):
			return showPage($page['pageid']);
        endwhile;
	}
	return false;
});

getKernelInstance()->actionAddFunction("show404Page",function(){
	?>
		<div class="content-header">
			<h2><?php echo(getKernelInstance()->actionExecute("parseTitle", '404 not found!')); ?></h2>
		</div>
		<div class="post">
			<div class="entry">
				<?php echo(getKernelInstance()->actionExecute("parseContent", 'Página não encontrada!')); ?>
			</div>
		</div>
	<?php
	return true;
});

/**
 * Limpeza de paragrafos extra do tinymce
 */
/*
getKernelInstance()->actionAddFunction("parseContent",function($content){
	$content = preg_replace('/^<p[^>]*>/', '', $content); // Remove the start <p> or <p attr="">
	$content = preg_replace('/<\/p>$/', '<br />', $content); // Replace the end
	
	return $content;
}, 99);
*/