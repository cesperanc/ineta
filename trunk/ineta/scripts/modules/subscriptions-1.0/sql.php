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
						CREATE TABLE IF NOT EXISTS `inscricoes_tbl` (
				  			`idInscricao` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Nº Automático que identifica a inscrição',
				  			`tsInsercao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Timestamp que guarda o momento em que foi inserido o registo.',
				  			`removido` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Campo que indica que a linha foi removida, mas mantendo-a na BD. Esta coluna pode ser útil para: \n- remover linhas que não podem ser apagadas devido às chaves estrangeiras; \n- implementar a opção de undelete;\n- Manter um histórico com as linhas apagadas.',
				  			`Tipo_Inscricao` enum('Sócio', 'Actividade', 'Subscritor da newsletter') NOT NULL COMMENT 'Indica se é uma inscrição (ou pré-inscrição) relativa a um sócio, relativa a uma actividade, ou à subscrição da newsletters:\n\nS- Sócio\nA- Actividade\nN-Subscritor da newsletter\n',
				  			`AutorizaMensagens` tinyint(1) DEFAULT '0' COMMENT 'Este campo indica se a pessoa autorizou ou não a recepção de mensagens automáticas (newsletters).\nPara aplicar quanto  a inscrição é relativa a sócios ou actividade/evento.\nNo caso das subscrições de newsletter, este campo deve ser sempre TRUE (1)',
				  			`Estado` ENUM('N', 'I', 'V', 'R') NOT NULL DEFAULT 'N' COMMENT 'Indica o estado da inscrição (ou pré-inscrição). Os valores possíveis são (N, I, V, R):\nN - Ainda não foi tratada pelo Administrador\nI - Pessoa Incontactável. \nV - Inscrição Válida.\nR - Inscrição Rejeitada.',
							`Razao_Rejeicao` varchar(255) DEFAULT NULL COMMENT 'Razão pela qual a inscrição foi rejeitada.',
							`Nome` varchar(80) NOT NULL COMMENT 'Nome da pessoa que pretende inscrever-se (que preencher a pré-inscrição)',
							`Morada` varchar(255) DEFAULT NULL COMMENT 'Morada da pessoa que pretende inscrever-se (que preencher a pré-inscrição)',
							`Data_Nascimento` date DEFAULT NULL COMMENT 'Data de Nascimento da pessoa que pretende inscrever-se (que preencher a pré-inscrição)',
							`Telefone1` varchar(20) DEFAULT NULL COMMENT '1º Telefone (ou telemóvel) da pessoa que pretende inscrever-se (que preencher a pré-inscrição)',
							`Telefone2` varchar(20) DEFAULT NULL COMMENT '2º Telefone (ou telemóvel) da pessoa que pretende inscrever-se (que preencher a pré-inscrição)',
							`eMail` varchar(255) DEFAULT NULL COMMENT 'E-Mail da pessoa que pretende inscrever-se (que preencher a pré-inscrição)',
							`idActividade` int(11) DEFAULT NULL COMMENT 'Chave Estrangeira. Identifica qual a actividade a que se pretende inscrever, ou então terá o valor de NULL se for uma inscrição para Sócio ou para Newsletters.\n',
							PRIMARY KEY (`idInscricao`)
						)ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='tabela com as inscrições do site';	
					")===false){
				$error = true;
				$kernel->logMsg("Ocorreu um erro (".mysql_error().")...");
			}
		}
	}
	
	return !$error;
});