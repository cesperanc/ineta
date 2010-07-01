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
		
		// Define o caminho para a extensão
		$kernel->actionAddFunction("tinymceExtraPlugins", function($tinymceExtraConfigs){
			$tinymceExtraConfigs[]=array('name'=>'schedules', 'url'=>getBaseUrl().SCRIPTSDIR.'/'.getKernelInstance()->getPathFromRoot(__FILE__).'editor_plugin.js');
			return $tinymceExtraConfigs;
		},20,1);
		
		// Adiciona o extensão à lista de extensões
		$kernel->actionAddFunction("tinymcePlugins", function($tinymcePlugins){
			$tinymcePlugins[]='schedules';
			return $tinymcePlugins;
		},20,1);
		
		// Adiciona o botão ao editor
		$kernel->actionAddFunction("tinymceButtons", function($tinymceButtons){
			if(!is_array($tinymceButtons)){
				$tinymceButtons = array();
			}
			if(!is_array($tinymceButtons[3])){
				$tinymceButtons[3] = array();
			}
			$tinymceButtons[3][]='schedules';
			
			return $tinymceButtons;
		},20,1);
	});
endif;