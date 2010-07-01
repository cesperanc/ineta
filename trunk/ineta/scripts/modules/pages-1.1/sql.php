<?php
if(!function_exists("getKernelInstance")){
	error_log("Tentativa de acesso directo ao ficheiro ".__FILE__,0);
	// Silence is golden
	die('');
}

// Cria a tabela de páginas, caso a mesma não exista
getKernelInstance()->actionAddFunction("_MySQL", function(){
	$error = false;
	
	if($kernel = getKernelInstance()){
		if($operator = $kernel->getSqlOperator()){
			
			// Cria a tabela para as páginas
			if($operator->doQuery("
				CREATE TABLE IF NOT EXISTS `paginas_tbl` ( 
					`id` bigint(20) NOT NULL auto_increment COMMENT 'id da página', 
					`id_pai` bigint(20) default NULL COMMENT 'id da página pai', 
					`ordem` bigint(20) NOT NULL default '0' COMMENT 'ordem da página', 
					`titulo` varchar(255) NOT NULL COMMENT 'título da página', 
					`conteudo` text NOT NULL COMMENT 'conteúdo da página', 
					`data` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP COMMENT 'data da última actualização', 
					PRIMARY KEY  (`id`), 
					KEY `id_pai` (`id_pai`), 
					CONSTRAINT `paginas_tbl_fk`  
				    	FOREIGN KEY (`id_pai`)  
				    	REFERENCES `paginas_tbl` (`id`)  
				    	ON DELETE SET NULL  
				    	ON UPDATE CASCADE 
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='tabela com as páginas do projecto';
			")===false){
				$error = true;
				$kernel->logMsg("Ocorreu um erro (".mysql_error().")...");
			}
			
			// Elimina o trigger de inserção de paǵinas, caso exista
			if($operator->doQuery("
				drop trigger if exists `paginas_tbl_update_order_before_insert`;
			")===false){
				$error = true;
				$kernel->logMsg("Ocorreu um erro (".mysql_error().")...");
			}
			
			// Cria o trigger para incremento da ordem das páginas
			if($operator->doQuery("
				create trigger `paginas_tbl_update_order_before_insert` before insert on `paginas_tbl` 
				for each row begin
				  declare tordem integer default NEW.ordem;
				  if NEW.ordem<1 then 
				    SELECT ifnull(max(`paginas_tbl`.`ordem`),0) INTO tordem FROM `paginas_tbl` WHERE (`paginas_tbl`.`id_pai` is null OR `paginas_tbl`.`id_pai`=NEW.id_pai);
				    set NEW.ordem := tordem+1;
				  end if; 
				end;
			")===false){
				$error = true;
				$kernel->logMsg("Ocorreu um erro (".mysql_error().")...");
			}
			
			// Elimina o procedimento de reordenação
			if($operator->doQuery("
				DROP PROCEDURE IF EXISTS paginas_tbl_reorder;
			")===false){
				$error = true;
				$kernel->logMsg("Ocorreu um erro (".mysql_error().")...");
			}
			
			// Cria o procedimento de reordenação de páginas
			if($operator->doQuery("
				CREATE PROCEDURE paginas_tbl_reorder(IN oldId int, IN oldParentId int, IN oldOrder int, IN newId int, IN newParentId int, IN newOrder int)
				BEGIN 
				  declare tordem integer default 0;
				
				  IF newId is not null AND oldId is not null THEN 
				    /* Enable the recursive call */
				    SET @@session.max_sp_recursion_depth=1;
				    /* it's an update, so verify if is an update within the same parent or the order is diferent and update things accordingly */
				    IF(!(oldParentId<=>newParentId) or oldOrder<>newOrder) THEN
				      UPDATE `paginas_tbl` SET `paginas_tbl`.`id` = newId, `paginas_tbl`.`id_pai` = newParentId, `paginas_tbl`.`ordem` = newOrder WHERE `paginas_tbl`.`id` = oldId;
				      CALL paginas_tbl_reorder(oldId, oldParentId, oldOrder, NULL, NULL, NULL);
				      CALL paginas_tbl_reorder(NULL, NULL, NULL, newId, newParentId, newOrder);
				    END IF;
				  ELSE
				    IF newId is not null THEN
				      /* it's an insert, so verify if we have more than one element within the newOrder, and update the elements accordingly */
				      SELECT count(*) INTO tordem FROM `paginas_tbl` WHERE `paginas_tbl`.`ordem`=newOrder AND (`paginas_tbl`.`id_pai`<=>newParentId) GROUP BY `paginas_tbl`.`ordem`;
				      IF(tordem>1) THEN
					UPDATE `paginas_tbl` SET `paginas_tbl`.`ordem` =  (`paginas_tbl`.`ordem`+1) WHERE `paginas_tbl`.`ordem`>=newOrder AND `paginas_tbl`.`id` <> newId AND (`paginas_tbl`.`id_pai`<=>newParentId);
				      END IF;
				    END IF;
				    IF oldId is not null THEN 
				      /* it's a delete, so verify if we really don't have any element in the oldOrder and move the elements above one order down */
				      SELECT count(*) INTO tordem FROM `paginas_tbl` WHERE `paginas_tbl`.`ordem`=oldOrder AND (`paginas_tbl`.`id_pai`<=>oldParentId) GROUP BY `paginas_tbl`.`ordem`;
				      
				      IF(tordem<1) THEN
					UPDATE `paginas_tbl` SET `paginas_tbl`.`ordem` =  (`paginas_tbl`.`ordem`-1) WHERE `paginas_tbl`.`ordem`>oldOrder AND `paginas_tbl`.`id` <> oldId AND (`paginas_tbl`.`id_pai`<=>oldParentId);
				      END IF;
				    END IF;
				  END IF; 
				END;	
			")===false){
				$error = true;
				$kernel->logMsg("Ocorreu um erro (".mysql_error().")...");
			}
		}
	}
	
	return !$error;
});