<?php
if(!function_exists("adminEnabled")){
	error_log("Tentativa de acesso directo ao ficheiro ".__FILE__,0);
	// Silence is golden
	die('');
}

getKernelInstance()->actionAddFunction("parseContent",function($content){
	$tagname = "GoogleMaps";
	findHtmlCommentInContent($tagname, $content, $contents);
	if(!empty($contents)){
		$replace = array();
		$by = array();
		$functionCache='';
		foreach($contents[2] as $index=>$map){
			$result = '';
			
			$mapObj = json_decode(html_entity_decode(preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\1;",urldecode($map)),null,'UTF-8'), true);
			// Se encontrámos a tag pretendida, vamos substituir o comentário por código nosso
			if(ob_start()){
				echo("<div id=\"map_canvas_$index\" style=\"width:{$mapObj['width']}px; height:{$mapObj['height']}px\"></div>");
				
				switch($mapObj['mapstyle']){
					case 2:
						$mapTypeId = 'google.maps.MapTypeId.SATELLITE';
						break;
		
					case 3:
						$mapTypeId = 'google.maps.MapTypeId.HYBRID';
						break;
		
					case 4:
						$mapTypeId = 'google.maps.MapTypeId.TERRAIN';
						break;
		
					default:
						$mapTypeId = 'google.maps.MapTypeId.ROADMAP';
				}
				
				switch($mapObj['hudmt']){
					case 2:
						$mapTypeControl = ' mapTypeControl : true, mapTypeControlOptions : { style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR } ';
						break;
		
					case 3:
						$mapTypeControl = ' mapTypeControl : true, mapTypeControlOptions : { style: google.maps.MapTypeControlStyle.DROPDOWN_MENU } ';
						break;
		
					case 4:
						$mapTypeControl = ' mapTypeControl : false ';
						break;
		
					default:
						$mapTypeControl = ' mapTypeControl : true, mapTypeControlOptions : { style: google.maps.MapTypeControlStyle.DEFAULT } ';
				}
				
				switch($mapObj['hudzp']){
					case 2:
						$navigationControl = ' navigationControl : true, navigationControlOptions : { style: google.maps.NavigationControlStyle.SMALL } ';
						break;
		
					case 3:
						$navigationControl = ' navigationControl : true, navigationControlOptions : { style: google.maps.NavigationControlStyle.ANDROID } ';
						break;
		
					case 4:
						$navigationControl = ' navigationControl : true, navigationControlOptions : { style: google.maps.NavigationControlStyle.ZOOM_PAN } ';
						break;
		
					case 5:
						$navigationControl = ' navigationControl : false ';
						break;
		
					default:
						$navigationControl = ' navigationControl : true, navigationControlOptions : { style: google.maps.NavigationControlStyle.DEFAULT } ';
				}
				
				switch($mapObj['huds']){ 
					case 2:
						$scaleControl = ' scaleControl : false ';
						break;
		
					default:
						$scaleControl = ' scaleControl : true, scaleControlOptions : { style: google.maps.ScaleControlStyle.STANDARD } ';
				}
				
				$functionCache.="
					var map$index = new google.maps.Map(document.getElementById(\"map_canvas_$index\"), {
						zoom: {$mapObj['zoom']},
						center: new google.maps.LatLng({$mapObj['latitude']}, {$mapObj['longitude']}),
						mapTypeId: $mapTypeId,
						$mapTypeControl,
						$navigationControl,
						$scaleControl,
						scrollwheel: true,
						draggable: true
				    });
			    ";
				$functionCache.="
					var marker$index = new google.maps.Marker({
						position: map$index.getCenter(), 
						map: map$index,
						cursor: 'pointer',
						draggable: false,
						".(!empty($mapObj['iconTitle'])?'title: \''.htmlspecialchars($mapObj['iconTitle'],ENT_QUOTES).'\',':'')."
						".(!empty($mapObj['iconImage'])?'icon: '.$mapObj['iconImage'].',':'')."
						".(!empty($mapObj['iconShadowImage'])?'shadow: '.$mapObj['iconShadowImage'].',':'')."
						visible: true
					});
				    
				";
				if(!empty($mapObj['infoWindowContent'])){
					$functionCache.="
						var infowindow$index = new google.maps.InfoWindow({
							content: '".addslashes(preg_replace('/[\n\t\r]/',"",$mapObj['infoWindowContent']))."'
						});
						google.maps.event.addListener(marker$index, 'click', function() {
							infowindow$index.open(map$index ,marker$index);
						});
					";
				}
				$result .= ob_get_contents();
				ob_end_clean();
			}
			$replace[] = $contents[0][$index];
			$by[] = $result;
		}
		$by[count($by)-1].="
			<script type=\"text/javascript\">
				/* <![CDATA[ */
					function mapsApiReady(){
						$functionCache
					}
				/* ]]> */
			</script>
			<script type=\"text/javascript\" src=\"http://maps.google.com/maps/api/js?sensor=false&amp;callback=mapsApiReady\"></script>
		";
		$content = str_ireplace($replace,$by,$content);
	}
	return $content;
});