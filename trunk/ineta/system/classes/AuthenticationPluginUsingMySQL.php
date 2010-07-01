<?php

	/**
	 * MySQLAuthentication Class
	 * 
	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
	 * @version 3.0 
	 */
	class AuthenticationPluginUsingMySQL extends AuthenticationPlugin{
	    private $_username;
	    private $_table;
	    private $_userfield;
	    private $_pwdfield;
	    private $_properties;
	    
		/**
		 * Constructor da classe
		 * 
		 */
		function __construct($idfield=USER_ID_FIELD,$userfield=USERNAME_FIELD,$pwdfield=USER_PASSWORD_FIELD,$table=USER_TBL,$hostname=HNAME,$username=UNAME,$password=PWORD,$database=DBASE){
		    //$this->_initializeDb($idfield,$userfield,$pwdfield,$table,$hostname,$username,$password,$database);
		}
		
		/**
		 * Executa os procedimentos necessários para criar uma tabela de utilizadores com os campos mínimos
		 * 
		 * @access public
		 * @param string $idfield, com o nome do campo de chave primária da tabela
		 * @param string $userfield, com o nome do campo do nome de utilizador
		 * @param string $pwdfield, com o nome do campo da senha
		 * @param string $table, com o nome da tabela a verificar/criar
		 * @param string $hostname, com o nome o servidor MySQL onde está alojada a base de dados
		 * @param string $username, com o nome de utilizador a utilizar para a ligação ao servidor MySQL onde está alojada a base de dados
		 * @param string $password, com a senha a utilizar para a ligação ao servidor MySQL onde está alojada a base de dados
		 * @param string $database, com o nome da base de dados para procurar/criar a tabela
		 * 
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 2.0
    	 * 
    	 * @deprecated by the system _MySQL action handler
		 */
		final private function _initializeDb($idfield=USER_ID_FIELD,$userfield=USERNAME_FIELD,$pwdfield=USER_PASSWORD_FIELD,$table=USER_TBL,$hostname=HNAME,$username=UNAME,$password=PWORD,$database=DBASE){
		    if(class_exists("MySQL")){
		        $conection =  new MySQL($hostname,$username,$password,$database);
    			
		        $query = $conection->doQuery("CREATE TABLE IF NOT EXISTS `$table` ( ".
		        							  " `$idfield` bigint(20) NOT NULL auto_increment COMMENT 'chave primária, e identificador do utilizador', ".
		        							  "`$userfield` varchar(255) collate utf8_general_ci NOT NULL COMMENT 'campo que será utilizado como nome de utilizador', ".
		        							  "`$pwdfield` varchar(255) collate utf8_general_ci NOT NULL COMMENT 'senha do utilizador', ".
		                                      "PRIMARY KEY  (`$idfield`), ".
		                                      "UNIQUE KEY `$userfield` (`$userfield`) ".
		                                      ") ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='tabela de utilizadores';".
		        							  "") or Kernel::logMsg("Ocorreu um erro (".mysql_error().")...");
    			
		    }
		}
		
		/**
		 * Verifica as credenciais do utilizador, comparando o nome de utilizador com a senha numa tabela SQL
		 * 
		 * @access public
		 * @param string $username, com o nome de utilizador a verificar
		 * @param string $password, com a senha a comparar
		 * @param string $table, com o nome da tabela a efectuar a comparação
		 * @param string $userfield, com o nome do campo do nome de utilizador
		 * @param string $pwdfield, com o nome do campo da senha a verificar
		 * 
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 1.0
		 */
		final public function authenticate($username,$password,$table=USER_TBL,$userfield=USERNAME_FIELD,$pwdfield=USER_PASSWORD_FIELD){
			$this->_username = NULL;
			if(isset($username) && class_exists("MySQL") && $result = MySQL::doQuery("SELECT `".$userfield."` FROM `".$table."` WHERE `".$userfield."` = '".addslashes($username)."' AND `".$pwdfield."` = '".addslashes($password)."'")){
		        if(is_resource($result) && mysql_num_rows($result)){
    			    $this->_username = $username;
    			    $this->_table = $table;
				    $this->_userfield = $userfield;
				    $this->_pwdfield = $pwdfield;
				    $this->_properties = $this->getUserProperties();
				    
		        	return true;
    			}
		    }
			return false;
		}
		
		/**
		 * Método que obtem os valores de cada campo da tabela de utilizadores para o utilizador autenticado
		 * 
		 * @access public
		 * @return array com as propriedades do utilizador
		 * 
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 1.0
		 */
		public function getUserProperties(){
		    if(!is_null($this->_username)&&class_exists("MySQL")){
		        $operator = new MySQL();
		        $query = $operator->doQuery("SHOW COLUMNS FROM `".$this->_table."`");
		        if(is_resource($query) && mysql_num_rows($query)>0){
		        	$cols = array();
					while($col = mysql_fetch_assoc($query)){
						if($this->_pwdfield!=$col['Field']){
							array_push($cols,$col['Field']);
						}
					}
					if(count($cols)>0){
						
						$query = $operator->doQuery("SELECT `".join("`,`",$cols)."` FROM `".$this->_table."` WHERE `".$this->_userfield."` = '$this->_username'");
						if(is_resource($query) && mysql_num_rows($query)>0){
				        	$properties = array();
							while($property = mysql_fetch_assoc($query)){
								array_push($properties,$property);
							}
							
							return $properties[0];
						}
						
					}
				}
				
		    }
		    return array();
		}
		
		/**
    	 * @method que regista acções do utilizador
    	 * 
    	 * @param string $action, com o texto a guardar
    	 * @param [optional] string $table, com o nome da tabela a guardar os dados
    	 * @return boolean true ou false respectivamente em caso de sucesso ou falha
    	 * 
    	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 3.0
    	 */
	    public function logAction($action,$table=LOG_USER_ACTIONS,$replaceIfExists=false){
	    	$table = (empty($table)?LOG_USER_ACTIONS:$table);
	    	if(!empty($table) && $table != 'LOG_USER_ACTIONS' && class_exists(MySQL) && isset($this) && User::getUser() && User::getUser()->isAuthenticated() && User::getUser()->authenticationPlugin(get_class($this))){
		    	if(!empty($this->_properties[USER_ID_FIELD])){
		    		$operator = new MySQL();
		    		
		    		/*//Deprecated
		    		$query = $operator->doQuery("CREATE TABLE IF NOT EXISTS `".mysql_escape_string($table)."` ( ".
		        			                      "`id` int(20) NOT NULL auto_increment, ".
		        			                      "`data` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP, ".
		        			                      "`id_utilizador` bigint(20) NOT NULL,". 
		        			                      "`id_sessao` varchar(255) collate utf8_general_ci NOT NULL default '', ".
		        			                      "`url` varchar(255) collate utf8_general_ci NOT NULL default '', ".
		        			                      "`mensagem` text collate utf8_general_ci NOT NULL, ".
		        			                      "PRIMARY KEY  (`id`), ".
		        			                      "KEY `id_utilizador` (`id_utilizador`), ".
		        			                      "FOREIGN KEY (`id_utilizador`) REFERENCES `".mysql_escape_string(USER_TBL)."` (`".mysql_escape_string(USER_ID_FIELD)."`) ON DELETE CASCADE ON UPDATE CASCADE ".
		    			                      ") ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ;") or Kernel::logMsg("Ocorreu um erro (".mysql_error().")...");
		    		*/
	    			
	    			if($replaceIfExists){
	    				$query = $operator->doQuery("UPDATE `".mysql_escape_string($table)."` SET 
	    												`data` = NOW()
	    											 WHERE 		`id_utilizador` = '".mysql_escape_string($this->_properties[USER_ID_FIELD])."'
	    											 		AND `id_sessao` = '".session_id()."'
	    											 		AND `mensagem` LIKE '".mysql_escape_string($action)."'
	    											 LIMIT 1;") or Kernel::logMsg("Ocorreu um erro (".mysql_error().")...");
	    				if(is_resource($query) && mysql_affected_rows($query)<1){
	    					$replaceIfExists = false;
	    				}
	    				
	    			}
		    		if(!$replaceIfExists){
	    				$query = $operator->doQuery("INSERT IGNORING INTO `".mysql_escape_string($table)."` (".
			    									"`id_utilizador`, ".
			    									"`id_sessao`, ".
			    									"`url`, ".
			    									"`mensagem` ".
		    									") ".
			    									"VALUES (".
			    									"'".mysql_escape_string($this->_properties[USER_ID_FIELD])."', ".
			    									"'".session_id()."', ".
			    									"'".mysql_escape_string((((is_null($_SERVER['HTTPS']))?"http://":"https://").$_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"]))."', ".
			    									"'".mysql_escape_string($action)."'".
			    								");") or Kernel::logMsg("Ocorreu um erro (".mysql_error().")...");
	    				
	    			
	    				
	    			}
	    			
	    			return true;
	    		}
    		}
    		return false;
	    }
	}
	
	if(function_exists('getKernelInstance')){
		getKernelInstance()->actionAddFunction("_MySQL", function(){
			$kernel = getKernelInstance();
			
			if(defined('USER_TBL') && $kernel->getSqlOperator()){
				if(!$kernel->getSqlOperator()->doQuery( "CREATE TABLE IF NOT EXISTS `".USER_TBL."` ( ".
				        							  	" `".USER_ID_FIELD."` bigint(20) NOT NULL auto_increment COMMENT 'chave primária, e identificador do utilizador', ".
				        							  	"`".USERNAME_FIELD."` varchar(255) collate utf8_general_ci NOT NULL COMMENT 'campo que será utilizado como nome de utilizador', ".
				        							  	"`".USER_PASSWORD_FIELD."` varchar(255) collate utf8_general_ci NOT NULL COMMENT 'senha do utilizador', ".
														"`nome` varchar(255) character set utf8 collate utf8_bin NOT NULL COMMENT 'nome próprio do utilizador', ".
														"`perfil` enum('administrador','utilizador') character set utf8 collate utf8_bin NOT NULL default 'administrador' COMMENT 'especifica o perfil do utilizador da plataforma', ".
				                                      	"PRIMARY KEY  (`".USER_ID_FIELD."`), ".
				                                      	"UNIQUE KEY `".USERNAME_FIELD."` (`".USERNAME_FIELD."`) ".
				                                      	") ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='tabela de utilizadores';".
				"")){
					$kernel->logMsg("Ocorreu um erro (".mysql_error().")...");
					return false;
				}
			}
			
			if(defined('LOG_USER_ACTIONS') && $kernel->getSqlOperator()){
				if(!$kernel->getSqlOperator()->doQuery("CREATE TABLE IF NOT EXISTS `".mysql_escape_string(LOG_USER_ACTIONS)."` ( ".
			        			                       "`id` int(20) NOT NULL auto_increment, ".
			        			                       "`data` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP, ".
			        			                       "`id_utilizador` bigint(20) NOT NULL,". 
			        			                       "`id_sessao` varchar(255) collate utf8_general_ci NOT NULL default '', ".
			        			                       "`url` varchar(255) collate utf8_general_ci NOT NULL default '', ".
			        			                       "`mensagem` text collate utf8_general_ci NOT NULL, ".
			        			                       "PRIMARY KEY  (`id`), ".
			        			                       "KEY `id_utilizador` (`id_utilizador`), ".
			        			                       "FOREIGN KEY (`id_utilizador`) REFERENCES `".mysql_escape_string(USER_TBL)."` (`".mysql_escape_string(USER_ID_FIELD)."`) ON DELETE CASCADE ON UPDATE CASCADE ".
			    			                      	   ") ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ;".
				"")){
					$kernel->logMsg("Ocorreu um erro (".mysql_error().")...");
					return false;
				}
			}
			
			return true;
		});
	}
?>