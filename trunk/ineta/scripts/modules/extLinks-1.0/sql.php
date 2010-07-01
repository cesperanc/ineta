<?php
if(!function_exists("getKernelInstance")){
	error_log("Tentativa de acesso directo ao ficheiro ".__FILE__,0);
	// Silence is golden
	die('');
}

// Actualiza a tabela de especifica utilizadores
getKernelInstance()->actionAddFunction("_MySQL", function(){
	$error = false;
	
	if($kernel = getKernelInstance()){
		if($operator = $kernel->getSqlOperator()){
			
			// Cria a tabela para as inscrições
			if($operator->doQuery("
						CREATE  TABLE IF NOT EXISTS `extlinks_tbl` (
							  `id` INT NOT NULL AUTO_INCREMENT COMMENT 'Nº Automático que identifica o link',
							  `titulo` VARCHAR(80) NOT NULL COMMENT 'Indica o título da hiperligação.' ,
							  `link` VARCHAR(255) NULL COMMENT 'Url do link',
							  PRIMARY KEY (`id`)
						) ENGINE = InnoDB DEFAULT CHARSET=utf8 COMMENT = 'Links externos.';	
					")===false){
				$error = true;
				$kernel->logMsg("Ocorreu um erro (".mysql_error().")...");
			}
		}
	}
	
	return !$error;
});