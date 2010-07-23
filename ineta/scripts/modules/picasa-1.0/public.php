<?php
if(!function_exists("getKernelInstance")){
	error_log("Tentativa de acesso directo ao ficheiro ".__FILE__,0);
	// Silence is golden
	die('');
}

/**
 * Àrea pública
 */
if(!adminEnabled()){
	getKernelInstance()->actionAddFunction("_head", function(){
		?>
			<link rel="stylesheet" type="text/css" href="<?php echo(getBaseUrl().SCRIPTSDIR.getKernelInstance()->getPathFromRoot(__FILE__)); ?>css/gallery.css" media="all" />
			<script type="text/javascript">
				$(document).ready(function (event){
					$("a.newWindow").attr('target','_blank');
				});
			</script>
		<?php 
	});
		
	getKernelInstance()->actionAddFunction("parseContent",function($content){
		$tagname = "picasa";
		findHtmlCommentInContent($tagname, $content, $contents);
		if(!empty($contents)){
			$replace = array();
			$by = array();
			
			foreach($contents[2] as $index=>$data){
				// Se encontrámos a tag pretendida, vamos substituir o comentário por código nosso
				$result = '';
				
				$picasaInfo = json_decode(html_entity_decode(preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\1;",urldecode($data)),null,'UTF-8'), true);
				if(ob_start()){
				    libxml_use_internal_errors(true);
				    $feedURL = ((!empty($picasaInfo['album']))?$picasaInfo['album']:"http://picasaweb.google.com/data/feed/api/user/".$picasaInfo['username']."?kind=photo");
				    
				    $sxml = @simplexml_load_file($feedURL);
				    
				    echo("<ul class=\"gallery\">");
					    foreach ($sxml->entry as $entry) {
							$title = $entry->title;
							$summary = $entry->summary;
							$media = $entry->children('http://search.yahoo.com/mrss/');
							$thumbnail = $media->group->thumbnail[1];
							
							echo("
								<li>
								    <a class=\"newWindow\" href=\"{$entry->link[1]->attributes()->{'href'}}\" title=\"{$title}\">
								       <img src=\"{$thumbnail->attributes()->{'url'}}\" alt=\"{$summary}\" />
								    </a>
								</li>
							");
					    }
				    echo("</ul>");
				    
				    libxml_clear_errors();
					
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
}