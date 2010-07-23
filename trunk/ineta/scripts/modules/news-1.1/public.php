<?php
if(!function_exists("getKernelInstance")){
	error_log("Tentativa de acesso directo ao ficheiro ".__FILE__,0);
	// Silence is golden
	die('');
}

/**
 * Área pública
 */
if(!adminEnabled()){
	getKernelInstance()->actionAddFunction("_head", function(){
		?>
			<link rel="stylesheet" type="text/css" href="<?php echo(getBaseUrl().SCRIPTSDIR.getKernelInstance()->getPathFromRoot(__FILE__)); ?>css/news.css" media="all" />
		<?php 
	});

	getKernelInstance()->actionAddFunction("parseContent",function($content){
		$tagname = "news";
		findHtmlCommentInContent($tagname, $content, $contents);
		if(!empty($contents)){
			$replace = array();
			$by = array();
			
			foreach($contents[2] as $index=>$data){
				// Se encontrámos a tag pretendida, vamos substituir o comentário por código nosso
				$result = '';
				
				$parsedObject = json_decode(html_entity_decode(preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\1;",urldecode($data)),null,'UTF-8'), true);
				if(ob_start()){
					
					$where = (($parsedObject['show']>0)?" `noticias`.`id` = '{$parsedObject['show']}' AND ":'').
			        		 (($parsedObject['show']==-2)?" `noticias`.`destaque`='1' AND ":'').
			        		 " `noticias`.`removido`<>'1' ";
			        
					if($parsedObject['show']<0 && $parsedObject['how_many_per_page']>0){
						$how_many = $parsedObject['how_many'];
						$how_many_per_page = $parsedObject['how_many_per_page'];
						$start = $_REQUEST['start'];
						
				        if($how_many>0){
							if($how_many_per_page>0){
								$how_many_per_page = (($how_many_per_page+$start>$how_many)?$how_many-$start:$how_many_per_page);
								$start = (($start>$how_many)?$how_many-$how_many_per_page:$start);
								$start = ($start<0)?0:$start;
								
								$limit = (is_numeric($start)?($start>$how_many?(($start)-($start-$how_many)):$start):0).", ".($how_many_per_page);
							}else{
								$start = 0;
								$how_many_per_page = $how_many;
								$limit = "$start, ".($how_many_per_page);
							}
				        }else{
				        	if($how_many_per_page>0){
								$limit = (is_numeric($start)?$start:0).", ".($how_many_per_page);
							}else{
								$limit = '';
							}
				        }
					}
			        
			        $sql = "
						SELECT `noticias`.`id` AS `newsid`,
			        		`noticias`.`titulo` AS `title`,
			        		`noticias`.`conteudo` AS `content`,
			        		`noticias`.`data_noticia` AS `date`
			        	FROM `noticias_tbl` `noticias`
			        		WHERE $where".
						"ORDER BY `noticias`.`data_noticia` DESC".
			        	((!empty($limit))?" LIMIT $limit ":'');
					
					$totals = 0;
					if($query = MySQLOperations::getQueryResult($sql)){
						if(mysql_num_rows($query)>0){
							?>
							<div><?php 
								while($news = mysql_fetch_assoc($query)){
									$newsId = $news['newsid'];
									$newsTitle = $news['title'];
									$newsContent = $news['content'];
									
									if(($pos = stripos($newsContent,"<!-- pagebreak -->"))!==false){
										$newsContent = substr($newsContent, 0, $pos)." <a href=\"./?action=showNews&amp;newsId={$newsId}\" title=\"Continuar a ler a notícia\"><small>(ler mais...)</small></a>";
									}
									$newsDate = gmdate("Y-m-d", strtotime($news['date']));
									?>
										<div class="content-header news">
											<h3 class="news"><a href="./?action=showNews&amp;newsId=<?php echo($newsId); ?>" title="Clique para visualizar a notícia"><?php echo($newsTitle); ?></a><?php if(isUserAnAdmin()){ echo("<small><a href=\"./?adminURL&amp;action=adminNews&amp;operation=editnews&amp;newsId=$newsId\" title=\"Clique para editar a notícia\">editar</a></small>"); } ?></h3>
											<small class="date"><?php echo($newsDate); ?></small>
										</div>
										<div class="post news">
											<div class="entry news">
												<?php echo($newsContent); ?>
											</div>
										</div>
									<?php
					        	}
								if($totalsQuery = MySQLOperations::getQueryResult("SELECT count(*) AS `total` FROM `noticias_tbl` `noticias` WHERE $where ")){
									if($totalsRow = mysql_fetch_assoc($totalsQuery)){
										$totals = (($how_many>0 && $totalsRow['total']>$how_many)?$how_many:$totalsRow['total']);
									}
								}
								
					        ?></div><?php 
			        			if($parsedObject['show']<0 && $parsedObject['how_many_per_page']>0){ 
			        			?>
						        	<div class="news nav">
						        		<?php 
						        			if(($start<($totals-(($how_many_per_page>0)?$how_many_per_page:0)))) : 
							        			$requests = $_GET;
							        			$requests['start'] = $start+$how_many_per_page;
							        			
							        			?>
							        				<div class="left"><a href="./?<?php echo(htmlentities(http_build_query($requests))); ?>">Notícias anteriores</a></div>
						        				<?php 
						        			endif; 
						        			
						        			$start = ((is_numeric($start))?$start:0);
						        			if($start>0): 
							        			$requests = $_GET;
			        							
							        			$requests['start'] = (($start-$parsedObject['how_many_per_page'])<0?0:$start-$parsedObject['how_many_per_page']);
							        			?>
							        				<div class="right"><a href="./?<?php echo(htmlentities(http_build_query($requests))); ?>">Notícias seguintes</a></div>
						        				<?php 
						        			endif; 
						        		?>
						        		<div class="clearer"></div>
						        	</div>
			        			<?php 
			        		}
						}else{
							?>
								<div class="content-header news">
									<h3 class="news">Sem notícias</h3>
								</div>
								<div class="post news">
									<div class="entry news">
										Sem notícias a apresentar.
									</div>
								</div>
							<?php
						}
			        }
				    
					$result .= ob_get_contents();
					ob_end_clean();
					
					$replace[] = $contents[0][$index];
					$by[] = $result;
				}
				
				$content = str_ireplace($replace,$by,$content);
			}
		}
		return $content;
	});
	
	
	/* Para mostrar uma notícia quando solicitado */
	getKernelInstance()->actionAddFunction("showPage",function(){
		$pageId = $_REQUEST['newsId'];
		if($_REQUEST['action']=='showNews' && is_numeric($pageId) && $pageId>0){
			getKernelInstance()->actionExecute("showNews", $pageId);
			return getKernelInstance()->actionExecuted("showNews");
		}
		return false;
	},11);
	
	/* Para mostrar uma notícia */
	getKernelInstance()->actionAddFunction("showNews",function($newsId){
		if(is_numeric($newsId) && $newsId>0){
			if($query = MySQLOperations::getQueryResult("
				SELECT `noticias`.`id` AS `newsid`,
	        		`noticias`.`titulo` AS `title`,
	        		`noticias`.`conteudo` AS `content`,
	        		`noticias`.`data_noticia` AS `date`,
	        		`noticias`.`destaque` AS `highlighted`
	        	FROM `noticias_tbl` `noticias`
	        	WHERE (`noticias`.`id` = '$newsId' AND `noticias`.`removido`<>'1' ) 
	        	LIMIT 1
	        ")){
				if($news = mysql_fetch_assoc($query)){
					$newsTitle = $news['title'];
					$newsContent = $news['content'];
					$newsDate = gmdate("Y-m-d", strtotime($news['date']));
					$highlightedNews = $news['highlighted'];
					?>
						<div class="content-header">
							<h2><?php echo(getKernelInstance()->actionExecute("parseTitle",$newsTitle)); ?><?php if(isUserAnAdmin()){ echo("<small><a href=\"./?adminURL&amp;action=adminNews&amp;operation=editnews&amp;newsId=$newsId\" title=\"Clique para editar a notícia\">editar</a></small>"); } ?></h2><br />
							<?php if($newsTitle!=getKernelInstance()->actionExecute("parseTitle",$newsTitle)): ?><small class="date"><?php echo($newsDate); ?></small><?php endif; ?>
						</div>
						<div class="post">
							<div class="entry">
								<?php echo(getKernelInstance()->actionExecute("parseContent",$newsContent)); ?>
							</div>
						</div>
					<?php
					return true;
	        	}
	        }
		}
		return false;
	},10,1);

}