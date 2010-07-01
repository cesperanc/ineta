<?php
if(!function_exists("getKernelInstance")){
	error_log("Tentativa de acesso directo ao ficheiro ".__FILE__,0);
	// Silence is golden
	die('');
}

// Cria a tabela de notícias, caso a mesma não exista
getKernelInstance()->actionAddFunction("_MySQL", function(){
	$error = false;
	
	if($kernel = getKernelInstance()){
		if($operator = $kernel->getSqlOperator()){
			
			// Cria a tabela para as notícias
			if($operator->doQuery("
				CREATE TABLE IF NOT EXISTS `noticias_tbl` ( 
					`id` bigint(20) NOT NULL auto_increment COMMENT 'id da notícia', 
					`titulo` varchar(255) NOT NULL COMMENT 'título da notícia', 
					`conteudo` text NOT NULL COMMENT 'conteúdo da notícia', 
					`data_modificacao` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP COMMENT 'data da última actualização', 
					`data_noticia` timestamp NOT NULL COMMENT 'data da notícia', 
					`removido` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'flag para marcar uma notícia removida',
  					`destaque` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'flag para marcar um noticia em destaque',
					PRIMARY KEY  (`id`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='tabela com as notícias do site';
			")===false){
				$error = true;
				$kernel->logMsg("Ocorreu um erro (".mysql_error().")...");
			}
			
		}
	}
	
	return !$error;
});