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
	$tagname = "pageslist";
	findHtmlCommentInContent($tagname, $content, $contents);
	if(!empty($contents)){
		foreach($contents[1] as $index=>$htmltagname){
			// Se encontrámos a tag pretendida, vamos substituir o comentário por código nosso
			$result = '';
			if(ob_start()){
				if($query = MySQLOperations::getQueryResult("
					SELECT `pages`.`id` AS `pageid`,
			        	`pages`.`titulo` AS `title`
			        FROM `paginas_tbl` `pages`
			        WHERE (`pages`.`id_pai` ".(is_numeric($_REQUEST['pageId'])?"={$_REQUEST['pageId']}":"is NULL").") 
			        ORDER BY `pages`.`ordem` ASC")){
						if(mysql_num_rows($query)>0){
							?>
								<ul class="subpageslist">
									<?php 
										while($page = mysql_fetch_assoc($query)):
											echo("<li><a href=\"./?action=showPage&amp;pageId={$page['pageid']}\" title=\"Clique para visitar {$page['title']}\">{$page['title']}</a></li>");
								        endwhile;
									?>
								</ul>
							<?php 
						}
					}
				$result .= ob_get_contents();
				ob_end_clean();
			}
			
			$content = str_ireplace($contents[0][$index],$result,$content);
		}
	}
	return $content;
});