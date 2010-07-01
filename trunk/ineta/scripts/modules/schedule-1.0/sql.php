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
						CREATE  TABLE IF NOT EXISTS `agenda_tbl` (
							  `idActividade` INT NOT NULL AUTO_INCREMENT COMMENT 'Nº Automático que identifica a actividade ou evento' ,
							  `tsInsercao` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP COMMENT 'Timestamp que guarda o momento em que foi inserido o registo.' ,
							  `removido` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Campo que indica que a linha foi removida, mas mantendo-a na BD. Esta coluna pode ser útil para: \n- remover linhas que não podem ser apagadas devido às chaves estrangeiras; \n- implementar a opção de undelete;\n- Manter um histórico com as linhas apagadas.' ,
							  `Tipo_Actividade` VARCHAR(2) NOT NULL COMMENT 'Indica qual o tipo de actividade:\nEA-Evento da AFAC\nAA-Actividade da AFAC\nEO-Evento de outra entidade\nAO-Actividade de outra entidade\n\nNota: Em termos de implementação no WebSite, os eventos e as actividades são similares.\n\n\n' ,
							  `Ja_Decorreu` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Indica se a actividade (ou evento) já decorreu. Nesse caso, já pode haver um rescaldo. ' ,
							  `Em_Destaque` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Indica se a actividade (ou evento) está ou não em destaque. \nNota: A implementação (ou não) de um sistema que destaque as actividades é livre e a forma de implementar também.' ,
							  `Aceita_Inscricao` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Indica se a actividade (ou evento) aceita ou não inscrições (pré-inscrições) online.\n' ,
							  `Titulo` VARCHAR(80) NOT NULL COMMENT 'Indica o título da actividade (ou evento).' ,
							  `Data` DATE NOT NULL COMMENT 'Indica a data em que a actividade (ou evento) ocorre. Se a actividade (ou evento) ocupar vários dias, esta data refere-se à data do primeiro dia.' ,
							  `Organizador` VARCHAR(80) NULL COMMENT 'Indica quem é o responsável pela organização da actividade (ou evento).\nSe forem actividade do tipo:\nEA-Evento da AFAC\nAA-Actividade da AFAC\neste campo deve conter o valor \"AFAC\"\n' ,
							  `Descricao` TEXT NULL COMMENT 'Texto principal sobre a actividade (ou evento).\nNota: \nPode aceitar só texto simples ou texto com formatação HTML (depende da implementação do WebSite)' ,
							  `Rescaldo` TEXT NULL COMMENT 'Texto principal sobre o rescaldo da actividade (ou evento).\nNota: \nPode aceitar só texto simples ou texto com formatação HTML (depende da implementação do WebSite).\n\nSó tem sentido este campo estar acessível, se o campo Ja_Decorreu for igual a 1 (TRUE ) ' ,
							  PRIMARY KEY (`idActividade`)
						) ENGINE = InnoDB DEFAULT CHARSET=utf8 COMMENT = 'Actividade ou evento.';	
					")===false){
				$error = true;
				$kernel->logMsg("Ocorreu um erro (".mysql_error().")...");
			}
		}
	}
	
	return !$error;
});