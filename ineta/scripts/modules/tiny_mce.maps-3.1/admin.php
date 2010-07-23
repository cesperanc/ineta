<?php
if(!function_exists("adminEnabled")){
	error_log("Tentativa de acesso directo ao ficheiro ".__FILE__,0);
	// Silence is golden
	die('');
}

if(adminEnabled()):
	// Para carregar o código necessário para o TinyMCE
	getKernelInstance()->actionAddFunction("_init", function(){
		$kernel = getKernelInstance();
		if(!$kernel->actionExecuted("requestJQuery") && !$kernel->actionExecute("requestJQuery")){
			$kernel->logMsg("Não foi possível carregar a biblioteca jquery no módulo tiny_mce.maps.");
		}
		
		// Define o caminho para a extensão
		getKernelInstance()->actionAddFunction("tinymceExtraPlugins", function($tinymceExtraConfigs){
			$tinymceExtraConfigs[]=array('name'=>'GoogleMaps', 'url'=>getBaseUrl().SCRIPTSDIR.getKernelInstance()->getPathFromRoot(__FILE__).'editor_plugin.js');
			return $tinymceExtraConfigs;
		},20,1);
		
		// Adiciona o extensão à lista de extensões
		getKernelInstance()->actionAddFunction("tinymcePlugins", function($tinymcePlugins){
			$tinymcePlugins[]='GoogleMaps';
			return $tinymcePlugins;
		},20,1);
		
		// Adiciona o botão ao editor
		getKernelInstance()->actionAddFunction("tinymceButtons", function($tinymceButtons){
			if(!is_array($tinymceButtons)){
				$tinymceButtons = array();
			}
			if(!is_array($tinymceButtons[3])){
				$tinymceButtons[3] = array();
			}
			$tinymceButtons[3][]='GoogleMaps';
			
			return $tinymceButtons;
		},20,1);
		
		
		if(isset($_REQUEST['tinyMceGoogleMapsRequest'])):
			getKernelInstance()->actionAddFunction("tinyMcePopup", function(){
				?>
					<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
					<html xmlns="http://www.w3.org/1999/xhtml">
						<head>
							<title>{#GoogleMapsDialog.title}</title>
							<?php getKernelInstance()->actionExecute("_head"); ?>
							<script type="text/javascript" src="<?php echo(TINYMCE_BASE_DIR); ?>tiny_mce_popup.js"></script>
							<script type="text/javascript" src="<?php echo(TINYMCE_BASE_DIR); ?>utils/mctabs.js"></script>
							<script type="text/javascript" src="<?php echo(getBaseUrl().SCRIPTSDIR.getKernelInstance()->getPathFromRoot(__FILE__)); ?>js/dialog.js"></script>
							<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
							<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
							<link rel="stylesheet" type="text/css" href="<?php echo(getBaseUrl().SCRIPTSDIR.getKernelInstance()->getPathFromRoot(__FILE__)); ?>css/content.css" media="all" />
						</head>
						<body onload="if(typeof(updateMap) == 'function') updateMap();">
							<form id="GoogleMaps" onsubmit="GoogleMapsDialog.insert();return false;" action="#">
								<div class="tabs">
									<ul>
										<li id="general_tab" class="current"><span><a href="javascript:mcTabs.displayTab('general_tab','general_panel');" onmousedown="return false;">{#GoogleMapsDialog.general}</a></span></li>
										<li id="advanced_tab"><span><a href="javascript:mcTabs.displayTab('advanced_tab','advanced_panel');" onmousedown="return false;">{#GoogleMapsDialog.advanced}</a></span></li>
									</ul>
								</div>
								<div class="panel_wrapper">
									<div id="general_panel" class="panel current">
										<fieldset>
											<legend>{#GoogleMapsDialog.map}</legend>
											<table border="0" cellspacing="0" cellpadding="0">
												<tr>
													<td colspan="2"><div id="map" style="width:575px;height:310px;overflow:hidden"></div></td>
												</tr>
											</table>
										</fieldset>
										<fieldset style="margin-top:10px">
											<legend>{#GoogleMapsDialog.location}</legend>
											<fieldset>
												<legend>{#GoogleMapsDialog.coordinates}</legend>
												<table border="0" cellspacing="0" cellpadding="4">
													<tr>
														<td style="width: 50px; white-space: nowrap;">{#GoogleMapsDialog.coordinates_latitude}:</td><td style="width: 150px;"><input onchange="updateMap();" onblur="this.onchange();" style="width:150px;" id="latitude" name="latitude" type="text" class="text" /></td>
														<td style="width: 50px; white-space: nowrap;">{#GoogleMapsDialog.coordinates_longitude}:</td><td style="width: 150px;"><input onchange="updateMap();" onblur="this.onchange();" style="width:150px;" id="longitude" name="longitude" type="text" class="text" /></td>
													</tr>
												</table>
											</fieldset>
											<table border="0" cellspacing="0" cellpadding="4">
												<tr>
													<td>{#GoogleMapsDialog.address}:</td>
													<td>
														<input size="40" 
															id="address" 
															name="address" 
															value="{#GoogleMapsDialog.defaultaddress}" 
															type="text" 
															onfocus="if(this.value=='{#GoogleMapsDialog.defaultaddress}'){ this.value=''; }" 
															onblur="if(this.value!='' && this.value!='{#GoogleMapsDialog.defaultaddress}'){showAddress(this.value); }else{ this.value='{#GoogleMapsDialog.defaultaddress}'; } return false;" 
														/>
														<input 
															type="button" 
															onclick="showAddress(document.getElementById('address').value); return false;" 
															style="border:1px solid #555; background:white; margin-left: 10px;" 
															value="{#GoogleMapsDialog.search}" 
														/>
													</td>
												</tr>
											</table>
										</fieldset>
									</div>
									<div id="advanced_panel" class="panel">
										<fieldset>
											<legend>{#GoogleMapsDialog.map_size}</legend>
											<table border="0" cellspacing="0" cellpadding="4">
													<tr>
														<td colspan="2">
															<table border="0" cellspacing="0" cellpadding="0" width="575">
																<tr>
																	<td  style="width: 50%;">
																		{#GoogleMapsDialog.width}: <input style="width:180px;" id="width" name="width" type="text" class="text" value="450" /> <abbr title="{#GoogleMapsDialog.pixel}">px</abbr>
																	</td>
																	<td align="right" style="width: 50%;">
																		{#GoogleMapsDialog.height}: <input style="width:180px;" id="height" name="height" type="text" class="text" value="350" /> <abbr title="{#GoogleMapsDialog.pixel}">px</abbr>
																	</td>
																</tr>
															</table>
														</td>
														<td></td>
													</tr>
											</table>
										</fieldset>
										<fieldset style="margin-top:10px">
											<legend>{#GoogleMapsDialog.map_options}</legend>
											<table border="0" cellspacing="0" cellpadding="4" style="width: 100%;">
												<tr>
													<td class="minimalSizedLabel">{#GoogleMapsDialog.zoom_level}:</td>
													<td><input type="text" size="2" value="13" onchange="updateMap();" name="zoomlevel" id="zoomlevel" class="text" /></td>
													<td class="minimalSizedLabel">{#GoogleMapsDialog.map_style}:</td>
													<td>
														<select name="mapstyle" id="mapstyle" onchange="updateMap();" >
															<option value="1" selected="selected">{#GoogleMapsDialog.map_style_normal}</option>
															<option value="2">{#GoogleMapsDialog.map_style_satellite}</option>
															<option value="3">{#GoogleMapsDialog.map_style_hybrid}</option>
															<option value="4">{#GoogleMapsDialog.map_style_physical}</option>
														</select>
													</td>
												</tr>
											</table>
										</fieldset>
										<fieldset style="margin-top:10px">
											<legend>{#GoogleMapsDialog.map_controls}:</legend>
											<table border="0" cellspacing="0" cellpadding="4" style="width: 100%;">
												<tr>
													<td class="minimalSizedLabel">{#GoogleMapsDialog.map_controls_zoom_position}:</td>
													<td>
														<select name="hudzp" id="hudzp" onchange="updateMap();">
															<option value="1" title="{#GoogleMapsDialog.map_controls_zoom_position_default_title}" selected="selected">{#GoogleMapsDialog.map_controls_zoom_position_default}</option>
															<option value="2" title="{#GoogleMapsDialog.map_controls_zoom_position_small_title}">{#GoogleMapsDialog.map_controls_zoom_position_small}</option>
															<option value="3" title="{#GoogleMapsDialog.map_controls_zoom_position_android_title}">{#GoogleMapsDialog.map_controls_zoom_position_android}</option>
															<option value="4" title="{#GoogleMapsDialog.map_controls_zoom_position_zoom_pan_title}">{#GoogleMapsDialog.map_controls_zoom_position_zoom_pan}</option>
															<option value="5" title="{#GoogleMapsDialog.map_controls_zoom_position_none_title}">{#GoogleMapsDialog.map_controls_zoom_position_none}</option>
														</select>
													</td>
													<td class="minimalSizedLabel">{#GoogleMapsDialog.map_controls_map_type}:</td>
													<td>
														<select name="hudmt" id="hudmt" onchange="updateMap();">
															<option value="1" title="{#GoogleMapsDialog.map_controls_map_type_default_title}" selected="selected">{#GoogleMapsDialog.map_controls_map_type_default}</option>
															<option value="2" title="{#GoogleMapsDialog.map_controls_map_type_horizontal_bar_title}">{#GoogleMapsDialog.map_controls_map_type_horizontal_bar}</option>
															<option value="3" title="{#GoogleMapsDialog.map_controls_map_type_dropdown_menu_title}">{#GoogleMapsDialog.map_controls_map_type_dropdown_menu}</option>
															<option value="4" title="{#GoogleMapsDialog.map_controls_map_type_none_title}">{#GoogleMapsDialog.map_controls_map_type_none}</option>
														</select>
													</td>
													<td class="minimalSizedLabel">{#GoogleMapsDialog.map_controls_scale}:</td>
													<td>
														<select name="huds" id="huds" onchange="updateMap();">
															<option value="1" title="{#GoogleMapsDialog.map_controls_scale_standard_title}" selected="selected">{#GoogleMapsDialog.map_controls_scale_standard}</option>
															<option value="2" title="{#GoogleMapsDialog.map_controls_scale_none_title}">{#GoogleMapsDialog.map_controls_scale_none}</option>
														</select>
													</td>
												</tr>
											</table>
										</fieldset>
										<fieldset style="margin-top:10px">
											<legend>{#GoogleMapsDialog.info_window}</legend>
											<table border="0" cellspacing="0" cellpadding="4" style="width: 100%;">
												<tr>
													<td class="minimalSizedLabel">{#GoogleMapsDialog.info_window_title}:</td>
													<td>
														<input size="40" id="icon_title" name="icon_title" value="" type="text" style="width: 100%;" />
													</td>
												</tr>
												<tr>
													<td class="minimalSizedLabel">{#GoogleMapsDialog.info_window_content}:</td>
													<td>
														<textarea id="info_window_content" name="info_window_content" rows="5" cols="30" style="width: 100%;"></textarea>
													</td>
												</tr>
											</table>
										</fieldset>
										<fieldset style="margin-top:10px">
											<legend>{#GoogleMapsDialog.map_icon}</legend>
											<table border="0" cellspacing="0" cellpadding="4" style="width:100%;">
												<tr>
													<td class="minimalSizedLabel">{#GoogleMapsDialog.map_icon_url}:</td>
													<td><input type="text" value="" name="icon_image" id="icon_image" class="text" style="width: 99%;" title="{#GoogleMapsDialog.map_icon_image_title}" /></td>
												</tr>
												<tr>
													<td class="minimalSizedLabel">{#GoogleMapsDialog.map_icon_shadow_url}:</td>
													<td><input type="text" value="" name="icon_image_shadow" id="icon_image_shadow" class="text" style="width: 99%;" title="{#GoogleMapsDialog.map_icon_shadow_image_title}" /></td>
												</tr>
											</table>
										</fieldset>
									</div>
								</div>
								<div class="mceActionPanel">
									<input type="button" id="insert" name="insert" value="{#insert}" onclick="GoogleMapsDialog.insert();" />
									<input type="button" id="cancel" name="cancel" value="{#cancel}" onclick="tinyMCEPopup.close();" />
								</div>
							</form>
						</body>
					</html>
				<?php 
				exit();
			});
		endif;
	});
endif;