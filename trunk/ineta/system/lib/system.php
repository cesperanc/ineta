<?php
	/**
	 * Kernel base system class for the platform
	 *
	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
	 * @version 5.0
	 *
	 * @todo: suporte multi-idioma para a plataforma
	 */
	class Kernel{

	    /**
	     * @access private
	     */
	    private $_mySqlConfigValues;
	    private $_mySqlNewConfigValues;
	    private $_actions=array();
	    private $_sqlOperator;
	    private static $singleton = NULL;

		/**
		 * Constructor da classe
		 */
		function __construct($hostname=HNAME,$username=UNAME,$password=PWORD,$database=DBASE){
			self::$singleton = $this;
			
			$this->_sqlOperator = false;

			$this->_setClassAutoLoader();

			if(class_exists('MySQL')){
				$this->_sqlOperator = new MySQL($hostname,$username,$password,$database,NULL,true);
				if(!$this->getSqlOperator()){
					$this->_sqlOperator = false;
				}
			}
			$this->_connectToDB();

			$this->_mySqlConfigValues = array();
			$this->_mySqlNewConfigValues = array();

			$this->_getConfigsFromDB();
		}
		
		/**
		 * Connect to the DB using the given parameters
		 * @param unknown_type $hostname
		 * @param unknown_type $username
		 * @param unknown_type $password
		 * @param unknown_type $database
		 */
		private function _connectToDB($hostname=HNAME,$username=UNAME,$password=PWORD,$database=DBASE){
			if($this->getSqlOperator()){
				$this->getSqlOperator()->doQuery("SET NAMES utf8;");
			}
		}
		
		
		/**
		 * Get the MySQL operator
		 * 
		 * @return MySQL object with the operator
		 */
		public function getSqlOperator(){
			if($this->_sqlOperator && $this->_sqlOperator->connectionStatus()){
				return $this->_sqlOperator;
			}
			return false;
		}
		
		/**
		 * Object initializer
		 */
		public function init(){
			$this->_syncDbConfig();
			
			$this->_publishConfig();

			$this->_init();
		}

		/**
		 * Função que executa alguns procedimentos especiais tais como:
		 * 		- Registo da função de limpeza de código
		 * 		- Inicio do registo de sessões e definição do texto por omissão da janela de autenticação
		 * 		- Carregamento de um ficheiro de script da directoria (caso seja especificado por um request "script" - ex: ./?script=teste.php)
		 * 		- Oculta o doctype caso o navegador do cliente seja apresentado como sendo da família Microsoft
		 *
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
		 * @version 4.0
		 */
		private function _init(){
			// Limpeza e compressão do código produzido ------------------------------------------------------------------------------------------------------------------------------
			if(!defined('DEBUG_MODE') || !DEBUG_MODE){
				error_reporting(0);
				if(!isset($_REQUEST['originalFile'])){
					ob_start(array(__CLASS__, "clearOutput"));
					ob_implicit_flush(true);
				}
			}else{
				error_reporting(E_ALL ^ E_NOTICE);
				if(function_exists("errorHandler")){
					set_error_handler(array(__CLASS__,"errorHandler"), E_ALL ^ E_NOTICE);
				}
			}
			// Inicio da sessões ----------------------------------------------------------------------------------------------------------------------------------------------------------
			if(!headers_sent()){
				session_start();
			}
			
			// Load all PHP files from the SCRIPTSDIR and the system classes dir
			$this->autoLoadFrom(SCRIPTSDIR, '/.php$/');
			$this->autoLoadFrom(SYSTEMDIR.'classes', '/.php$/');
			
			// Execute the system base actions
			$this->actionExecute("_loadModules");
			// Install the mysql tables for the modules, and disable the associated commands after it
			if(!defined('PRODUCT_CONFIGURED') || (defined('PRODUCT_CONFIGURED') && PRODUCT_CONFIGURED!=true) || (defined('ALWAYS_EXECUTE_MYSQL_ACTION') && ALWAYS_EXECUTE_MYSQL_ACTION==true)){
				$this->actionExecute("_MySQL");
				if($this->getSqlOperator() && (defined('PRODUCT_CONFIGURED') && PRODUCT_CONFIGURED!=true)){
					$query = $this->getSqlOperator()->doQuery("UPDATE `config_tbl` SET value='true' WHERE `name`='PRODUCT_CONFIGURED';");
				}
			}
			// Pass control to the modules
			$this->actionExecute("_init");

			// Inclusão automática de um script -------------------------------------------------------------------------------------------------------------------------------------------
			if(isset($_REQUEST["file"])){
				$this->getFile(SCRIPTSDIR.$_REQUEST["file"]);
				exit();
			}
			if(isset($_REQUEST["script"])){
				$this->getFile(SCRIPTSDIR.$_REQUEST["script"]);
				exit();
			}
			if(isset($_REQUEST["class"])){
				$classFile = $this->getClassFileName($_REQUEST["class"]);
				if($classFile){
					$this->getFile($classFile);
				}
				exit();
			}
			if(!headers_sent()){
				header("Content-Type: text/html; charset=utf-8",true);
			}
		}

		public function appendToConfig($name=NULL,$value="",$description="",$enabled=false,$eval=false){
			if(!empty($name)){
				if(!isset($this->_mySqlConfigValues[$name])){
					$this->_mySqlConfigValues[$name] = array("name"=>$name,"value"=>stripcslashes($value),"description"=>$description,"enabled"=>(($enabled)?'true':'false'),"eval"=>(($eval)?'true':'false'),"type"=>"local");
					$this->_mySqlNewConfigValues[$name] = array("name"=>$name,"value"=>$value,"description"=>$description,"enabled"=>(($enabled)?'true':'false'),"eval"=>(($eval)?'true':'false'));
				}
				return true;
			}
			return false;
		}

		private function _syncDbConfig(){
			if(!empty($this->_mySqlNewConfigValues) && $this->getSqlOperator()){
			
				$query = "INSERT INTO `config_tbl` (`name`, `value`, `description`, `enabled`, `eval`) VALUES ";
				$notfirst = false;
				foreach($this->_mySqlNewConfigValues as $name=>$config){
					if($notfirst){
						$query .= ", ";
					}else{
						$notfirst=true;
					}
					$query .= "('".$config["name"]."', '".$config["value"]."', '".$config["description"]."', '".$config["enabled"]."', '".$config["eval"]."')";
				}
				$query .= ";";
				$this->getSqlOperator()->doQuery($query);
			}
		}

		private function _publishConfig(){
			foreach($this->_mySqlConfigValues as $name=>$defines){
				if($defines["enabled"]=='true'){
					if($defines["eval"]!='true' || ($defines["eval"]=='true' && !@eval("define('".$defines['name']."',".$defines['value']."); return true;"))){
						@define($defines['name'],$defines['value']);
					}
				}
			}
		}

		/**
		 * Função que define variáveis de configuração a partir de uma tabela de configurações da base de dados
		 *
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
		 * @version 1.0
		 */
		private function _getConfigsFromDB(){
			if($this->getSqlOperator()){
				$query = $this->getSqlOperator()->doQuery(" -- query to create the config_tbl MySQL table
												    	CREATE TABLE IF NOT EXISTS `config_tbl` (
														  `id` int(20) NOT NULL auto_increment COMMENT 'Primary key index',
														  `modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP COMMENT 'last modified',
														  `enabled` enum('true','false') collate utf8_bin NOT NULL default 'false' COMMENT 'is this configuration enabled?',
														  `eval` enum('true','false') collate utf8_bin NOT NULL default 'false' COMMENT 'eval this field as PHP code',
														  `name` varchar(255) collate utf8_bin NOT NULL default '' COMMENT 'configuration name',
														  `value` text collate utf8_bin NOT NULL COMMENT 'configuration php code value',
														  `description` text collate utf8_bin NOT NULL COMMENT 'configuration description',
														  PRIMARY KEY  (`id`),
														  UNIQUE KEY `name` (`name`)
														) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;
				");

				$results = $this->getSqlOperator()->doQuery("SELECT `config_tbl`.`name` AS 'name',
								                            `config_tbl`.`value` AS 'value',
								                            `config_tbl`.`eval` AS 'eval'
								                         FROM `config_tbl`
								                            WHERE `config_tbl`.`enabled` = 'true';
				");
				
				if(@mysql_num_rows($results)>0){
					while($defines = @mysql_fetch_assoc($results)){
						$this->_mySqlConfigValues[$defines['name']] = array("name"=>$defines['name'],"value"=>$defines['value'],"eval"=>$defines['eval'],"description"=>"","enabled"=>'true',"type"=>"db");
					}
					return true;
				}
			}
			return false;
		}

		/**
		 * Define a função para carregamento automático dos ficheiros com as classes utilizando o respectivo nome da classe
		 */
		private function _setClassAutoLoader(){
			function __autoload($classname){
	            $classfile = Kernel::getInstance()->getClassFileName($classname);
	            if($classfile){
	                if(require_once($classfile)){
	                    return true;
	    		    }
	    		}
	    		self::logMsg("O ficheiro que define a classe ".$classname." não foi encontrado.", "Error", LOG_TO, false);
				return false;
			}
		}

		/**
		 * Função que verifica se um existe um valor definido; em caso negativo é retornado o valor por omissão
		 * @param string $classname com o nome da classe a ser incluída
		 * @param string $classpath com o(s) nome(s) dos directório(s) a ser feita a pesquisa
		 * @return string com o nome do ficheiro que define a classe ou boolean false
		 *
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
		 * @version 2.0
		 */
		public static function getClassFileName($classname,$classpath=CLASSPATH){
		    $result = false;
		    $paths = preg_split("/[,;:]/", $classpath);
			foreach ($paths as $path){
			    if(!$result){
			        $classfile = $path.$classname.".php";
			        if(is_readable($classfile) && is_file($classfile)){
			            return $classfile;
			        }
			    }
			}
			return $result;
		}
		
		/**
		 * Add a function to an action to be executed
		 * 
		 * @param String $action with the action name to be performed
		 * @param String or String[] $function the function name to be executed
		 * @param int $priority with the priority of the function in the action
		 * @param int $num_args number of arguments to pass to the function
		 * @param array with extra arguments for the function
		 * 
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
		 * @version 1.0
		 */
		public function actionAddFunction($action=false, $function=false, $priority=10, $num_args=0, $extra_args=array()){
		    if(isset($this) 
		    	&& is_int($priority) 
		    	&& $action 
		    	&& $function 
		    	&& is_callable($function, true, $null)
		    	&& $this->actionExistsFunctionInPriority($action, $function, $priority)===false){
		    	// Check if we already have this function under this priority
		    	/*
		    	foreach($this->_actions[$action][$priority] as $functionIndex=>$functionArray){
	    			if($functionArray['function']===$function){
	    				return false;
	    			}
	    		}*/
		    	
	    		static $funtionindex=0;
		    	$this->_actions[$action][$priority][$funtionindex++] = array('function' => $function, 'accepted_args' => $num_args, 'extra_args' => $extra_args, 'executed'=>0);
	    		return true;
		    }
			return false;
		}
		
		/**
		 * Verify if an action exists
		 * 
		 * @param String $action to search in
		 * @return boolean true if the action exists, false otherwise
		 * 
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
		 * @version 1.0
		 */
		public function actionExists($action=false) {
			if(isset($this) && $action && !empty($this->_actions[$action])){
				return true;
			}
			return false;
		}
		
		/**
		 * Search for a function in the given action
		 * 
		 * @param String $action to search in
		 * @param Mixed $function to search for
		 * @return array('priority','functionIndex') with the priority and functionIndex
		 * 
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
		 * @version 1.0
		 */
		public function actionExistsFunction($action=false, $function = false) {
			if(!isset($this) || !$this->actionExists($action) || !$function || !is_callable($function, true, $null)){
				return false;
			}
			$results = array();
			foreach($this->_actions[$action] as $priority=>$priorityArray){
				if($functionIndex = $this->actionExistsFunctionInPriority($action, $function, $priority)){
					$results[] = array('priority'=>$priority, 'functionIndex'=>$functionIndex);
				}
			}
			return $results;
		}
		
		/**
		 * Search for a the function in the given action and priority
		 * 
		 * @param String $action to search in
		 * @param Mixed $function to search for
		 * @param int $priority with the priority
		 * @return array('priority','functionIndex') with the priority and functionIndex
		 * 
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
		 * @version 1.0
		 */
		public function actionExistsFunctionInPriority($action=false, $function = false, $priority = 10) {
			if(!isset($this) 
				|| !is_int($priority) 
				|| !$this->actionExists($action) 
				|| empty($this->_actions[$action][$priority]) 
				|| !$function 
				|| !is_callable($function, true, $null)){
				return false;
			}
			foreach($this->_actions[$action][$priority] as $functionIndex=>$functionArray){
    			if($functionArray['function']===$function){
    				return $functionIndex;
    			}
    		}
			return false;
		}
		
		/**
		 * Execute the functions associated with an action
		 * 
		 * @param String $action with the action name
		 * @param Mixed $value with the value to be processed by the associated functions
		 * @param boolean $forceExecution to force the execution (e.g. if the action was already executed)
		 * @return mixed with the processed value
		 * 
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
		 * @version 1.0
		 */
		public function actionExecute($action, $value=false, $forceExecution=false) {
			if(!$this->actionExists($action)){
				return $value;
			}
			
			$args = func_get_args();
			
			// Sort by priority
			ksort($this->_actions[$action]);
			
			foreach($this->_actions[$action] as $priorityId=>$priority ){
				foreach( $priority as $functionIndex => $function ){
					if ( !is_null($function['function']) ){
						$args[1] = $value;
						$args[2] = $function['extra_args'];
						if($forceExecution || $this->_actions[$action][$priorityId][$functionIndex]['executed']<1){
							if(($value = call_user_func_array($function['function'], array_slice($args, 1, (int) $function['accepted_args']+2)))!==false){
								$this->_actions[$action][$priorityId][$functionIndex]['executed']++;
							}
						}
					}
				}
			}
			
			return $value;
		}
		
		/**
		 * Verify if an action was executed or not
		 * 
		 * @param String $action with the action name
		 * @return boolean true if executed, false otherwise
		 * 
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
		 * @version 1.0
		 */
		public function actionExecuted($action) {
			if(!$this->actionExists($action)){
				return false;
			}
			
			foreach($this->_actions[$action] as $priorityId=>$priority ){
				
				foreach( $priority as $functionIndex => $function ){
					if ( !is_null($function['function']) ){
						if($this->_actions[$action][$priorityId][$functionIndex]['executed']>0){
							return true;
						}
					}
				}
			}
			
			return false;
		}
		
		/**
		 * Execute the functions associated with an action, if it hasn't been executed already
		 * 
		 * @param String $action with the action name
		 * @param String $failMsg with the message to log on fail
		 * @param Mixed $value with the value to be processed by the associated functions
		 * @param boolean $forceExecution to force the execution (e.g. if the action was already executed)
		 * @return true if the action was executed, false otherwise
		 * 
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
		 * @version 1.0
		 */
		public function actionExecuteIfNotExecuted($action, $failMsg='', $value=false, $forceExecution=false) {
			if(!$this->actionExecuted($action) && !$this->actionExecute($action, $value, $forceExecution) && !empty($failMsg)){
				$this->logMsg($failMsg);
				return false;
			}
			return true;
		}
		
		/**
		 * Remove a function from an action
		 * 
		 * @param String $action with the action name
		 * @param Mixed $function the function name to be removed
		 * @param int $priority with the priority of the function in the action
		 * 
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
		 * @version 1.0
		 */
		public function actionRemoveFunction($action=false, $function=false, $priority=10){
			$result = false;
			if(!isset($this) 
				|| !is_int($priority) 
				|| !$this->actionExists($action) 
				|| empty($this->_actions[$action][$priority]) 
				|| !$function 
				|| !is_callable($function, true, $null)){
				return false;
			}
	    	if($functionIndex = $this->actionExistsFunctionInPriority($action, $function, $priority)){
		    	if(isset($this->_actions[$action])){
			    	if(isset($this->_actions[$action][$priority])){
				    	if(isset($this->_actions[$action][$priority][$functionIndex])){
							unset($this->_actions[$action][$priority][$functionIndex]);
							$result = true;
		    			}
			    		if(count($this->_actions[$action][$priority])==0){
			    			unset($this->_actions[$action][$priority]);	
			    		}
			    	}
		    		if(count($this->_actions[$action])==0){
		    			unset($this->_actions[$action]);	
		    		}
		    	}
			}
	    	
			return $result;
		}
		
		/**
		 * Return the current $kernel instance
		 * 
		 * @return Kernel
		 *
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
		 * @version 1.0
		 */
		public static function getInstance(){
			if(is_null(self::$singleton)){
				self::$singleton = new self();
			}
			return self::$singleton;
		}
		
		/**
		 * Função responsável pela criação e pelo registo (se activado) das mensagens de erro de toda a plataforma
		 * @param string $string com a mensagem de erro a gerar
		 * @param string $email com os endereços electrónicos dos contactos de suporte
		 * @param string $site com o nome do site
		 * @return string com uma mensagem de erro normalizada segundo o autor do pedido
		 * @uses Kernel::logMsg para guardar o registo
		 *
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
		 * @version 3.0
		 */
		public static function errorMsg($string="",$email=SUPPORT_EMAILS,$site=PRODUCT_NAME){
			self::logMsg($string,"Error",LOG_TO,$email);
		
			return "<h1><hr>Erro</h1>".$string."\n<br><hr>";
		}
		
		/**
		 * Função responsável pelo registo de mensagens em ficheiro, base de dados, registo de sistema ou envio de emails
		 *
		 * @param string $message com a mensagem de erro a guardar
		 * @param string $type com o tipo de mensagem a enviar
		 * @param string $email com a lista de emails separados por ';' a utilizar no caso de envio de emails de logs
		 *
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
		 * @version 5.2
		 *
		 */
		public static function logMsg($message,$type="Erro",$logto=LOG_TO,$email=SUPPORT_EMAILS){
		    $url = (((!isset($_SERVER['HTTPS']) || is_null($_SERVER['HTTPS']))?"http://":"https://").$_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"]);
	
		    $error_msg= "<font style='font-family: Verdana,Tahoma; font-size: 10px;'>Foi enviado um $type do <strong>".PRODUCT_FULL_NAME." v".PRODUCT_VERSION."</strong>: <br/>\n\t".
		    			"Data: ".gmdate("Y-m-d H:i:s")." <br/>\n\t".
		                ((User::isUserAuthenticated())?"Utilizador: ".User::getUser()->username." <br/>\n\t":"").
		                "URL: <a href=\"$url\">$url</a><br/>\n\t".
		                "Ficheiro: ".$_SERVER["PHP_SELF"]." <br/>\n\t".
		                "$type: $message<br/></font>\n";
	
	    	$logplugins = preg_split("/[,.;]/",$logto);
			foreach ($logplugins as $plugin){
	    		switch(strtolower($plugin)){
	    			case "echo":{
	    				echo($error_msg);
	    				break;
	    			}
	    			case "file":{
	    				if(!file_exists(LOG_TO_FILE)){
	    					$ficheiro = @fopen (LOG_TO_FILE, "w+");
	    					@fwrite($ficheiro, "--Ficheiro de registo de anomalias do ".PRODUCT_FULL_NAME." v".PRODUCT_VERSION."--\n-------------------------------------------------------\n");
	    					@fclose($ficheiro);
	    				}
	    				if(is_writable(LOG_TO_FILE)){
	    				    $error_msg.="-------------------------------------------------------<br/>\n\t";
	    					file_put_contents(LOG_TO_FILE,clearTagsFrom($error_msg),FILE_APPEND);
	    				}else{
	    				    self::logMsg("Não foi possível registar o $type no ficheiro ".LOG_TO_FILE.". Por favor verifique as permissões do ficheiro ou desactive o plugin de registo file no ficheiro de configuração. A mensagem de $type original foi: <br/>\n<br/>\n$message","Erro","syslog");
	    				}
	
	    				break;
	    			}
	    			case "database":{
	    				if(class_exists(MySQL)){
	    					$operator = new MySQL(NULL,NULL,NULL,NULL,NULL,true);
	    					$query = $operator->doQuery("CREATE TABLE IF NOT EXISTS `".LOG_TO_TABLE."` (`id` int(11) NOT NULL auto_increment, " .
	    								"`data` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP, " .
	    								"`utilizador` varchar(255) NOT NULL default '', " .
	    								"`ficheiro` varchar(255) NOT NULL default '', " .
	    								"`url` varchar(255) NOT NULL default '', " .
	    								"`mensagem` text NOT NULL default '', " .
	    								"PRIMARY KEY  (`id`));");
	
	    					if($query = $operator->doQuery("INSERT INTO `".mysql_escape_string(LOG_TO_TABLE)."` ( `id` , `data` , `utilizador` , `ficheiro` , `url` , `mensagem` ) " ."VALUES (NULL , '".mysql_escape_string(gmdate("Y-m-d H:i:s"))."', '".mysql_escape_string(User::getUser()->username)."', '".mysql_escape_string($_SERVER["PHP_SELF"])."', '".mysql_escape_string($_SERVER["REQUEST_URI"])."', '".mysql_escape_string(clearTagsFrom($message))."');")){
	    					    return true;
	    					}
	    				}
	    				self::logMsg("Não foi possível registar o $type na tabela ".LOG_TO_TABLE." devido ao seguinte erro de SQL: <br/>\n".mysql_error().". Por favor tente corrigir a anomalia ou desactive o plugin de registo database no ficheiro de configuração. A mensagem de $type original foi: <br/>\n<br/>\n$message","Erro","syslog");
	
	    				return false;
	    			}
	    		    case "mail":{
	    		        $headers  = "MIME-Version: 1.0\r\n";
	                    $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
	
	                    $headers .= "From: ".PRODUCT_FULL_NAME."_v".PRODUCT_VERSION."\r\n";
	
	                    error_log(utf8_decode($error_msg),1,$email,$headers);
	    				break;
	    			}
	    			default:{
	    			    error_log(PRODUCT_FULL_NAME."\t Ocorreu um $type: $message",0);
	    			} 
	    		}
			}
			return false;
		}
		
		/**
		 * Função para capturar e registar os erros internos
		 *
		 * @param $errno
		 * @param $errstr
		 * @param $errfile
		 * @param $errline
		 * @return unknown_type
		 */
		public static function errorHandler($errno, $errstr, $errfile, $errline){
			switch ($errno) {
			    case E_USER_ERROR:
			    	$message = "<strong>ERRO</strong> [$errno] $errstr<br />\n";
			    	$message .= "Erro fatal na linha $errline no ficheiro $errfile";
			        $message .= ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
			        $message .= "A cancelar...<br />\n";
			        self::logMsg($message);
	
			        exit(1);
	
			        break;
	
			    case E_USER_WARNING:
			        $message = "<strong>Aviso</strong> [$errno] $errstr<br />\n";
			        self::logMsg($message, "Aviso");
	
			        break;
	
			    case E_USER_NOTICE:
			        $message = "<strong>Recomendação</strong> [$errno] $errstr<br />\n";
			        self::logMsg($message, "Noticia");
	
			        break;
	
			    default:
			        $message = "<strong>ERRO</strong> [$errno] $errstr<br />\n";
			    	$message .= "Erro fatal na linha $errline no ficheiro $errfile";
			        $message .= ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
	
			        self::logMsg($message);
		    }
	    	return false;
		}
		
		/**
		 * Get a MimeType of a FIle
		 * @param File location $file
		 * @param Default $type
		 */
		public static function getFileMimeType($file,$type=false){
			if(is_readable($file)){
	    		if(function_exists('finfo_file')){
	    			if(!$finfomime = finfo_open(FILEINFO_MIME)){
	    				$finfomime = finfo_open(FILEINFO_MIME,SYSTEMDIR."lib.3rd/magic");
	    			}
	    		    if($finfomime){
	    		        if($type_new = finfo_file($finfomime,$file)){
	    		            $type = $type_new;
	    		        }else{
	    		            self::logMsg("O ficheiro não foi reconhecido. Por favor corrija o problema.","Aviso");
	    		        }
	    		        finfo_close($finfomime);
	    		    }else{
	    		        self::logMsg("Não foi possível a base de dados de mimetypes o que impedirá o reconhecimento automático do mime type do ficheiro. Por favor corrija o problema.","Aviso");
	    		    }
	    		}else{
	    			self::logMsg("O módulo fileinfo não está activo na sua configuração de PHP actual. Isto irá impedir que mime type do ficheiro seja reconhecível. Por favor corrija o problema.","Aviso");
	    		}
			}else{
				self::logMsg("O ficheiro $file não foi encontrado.","Aviso");
			}
			return $type;
		}
	    
		/**
		 * Função responsável pelo carregamento de ficheiros (caso seja possível)
		 * @param string $file com o endereço do ficheiro a carregar
		 * @param boolean $outputError caso se pretenda que seja feito um output do erro ou não
		 * @return boolean com true em caso de sucesso ou false em caso de falha
		 *
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
		 * @version 2.0
		 */
		public static function getFile($file,$outputError=true){
			if(is_readable($file) && defined('BASE_DIR') && stripos(dirname(realpath($file)),BASE_DIR)===0){
				if(require_once($file)){
					return true;
				}
			}
			$error = "Não foi possível ler o ficheiro ".$file;
			if($outputError && function_exists("errorMsg")){
				echo(errorMsg($error));
			}else{
				self::logMsg($error);
			}
			return false;
		}
		
		/**
		 * Função responsável pela limpeza de todo o código gerado no servidor antes de este ser enviado para o cliente
		 * @param string $buffer com os conteúdos processados
		 * @return string com o buffer formatado
		 *
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
		 * @version 3.0
		 */
		public static function clearOutput($buffer){
			return (((!defined('DEBUG_MODE') || !DEBUG_MODE) && !(isset($_POST["file"]) || isset($_REQUEST["file"])))?(preg_replace('/\s\s+/',' ',preg_replace(CHARS_TO_REMOVE_FROM_BUFFER,'',((!isset($_POST["script"])&&!isset($_REQUEST["script"])&&!isset($_POST["class"])&&!isset($_REQUEST["class"]))?("<!-- Cláudio Esperança <2070030@student.estg.ipleiria.pt>, Diogo Serra <2081008@student.estg.ipleiria.pt> ".((date("Y")<="2005")?date("Y"):("2005-".date("Y"))).", todos os direitos reservados //-->\n"):"").$buffer))):$buffer);
		}

		/**
	     * Função para carregar automaticamente ficheiros que cumpram uma determinada regra baseada no nome do ficheiro.
	     *
	     * @param string $dir, com o nome do directório a efectuar a pesquisa
	     * @param [string $thatMatch], com a expressão regular a utilizar para a comparação insensitiva
	     * @param int $recursiveCount with the number of subdirectories to follow
	     *
	     * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
	     * @version 3.0
	     */
	    public static function autoLoadFrom($dir, $thatMatch='/.load.php$/', $recursiveCount=0){
	    	$dircontents = scandir($dir);
			foreach ($dircontents as $content){
				if($content!="." && $content!=".."){
					if(preg_match($thatMatch,strtolower($content))>0 && is_file("$dir/$content")){
						self::getFile("$dir/$content");
					}
					if($recursiveCount>0 && is_dir("$dir/$content")){
						self::autoLoadFrom("$dir/$content", $thatMatch, $recursiveCount-1);
					}
				}
			}
	    }
	    
	    /**
	     * Return the relative path from the script called to the script $file specified
	     * 
	     * @param String $file with the file (try with __FILE__)
	     * @param String $remove with the itens from the path to remove from the begining of the string
	     * @return String with the result (empty on error)
	     */
	    public static function getPathFromRoot($file, $remove=SCRIPTSDIR){
	    	//return (empty($file) or !is_string($file))?'':trim(str_replace(dirname(realpath($_SERVER['SCRIPT_FILENAME'])).'/'.trim($remove,'./'), '', dirname(realpath($file))), '/').'/';
		return (empty($file) or !is_string($file))?'':str_replace("\\", "/", trim(str_replace(realpath($remove), '', dirname(realpath($file))), '/').'/');
	    }

	}

// ----------------------------------------------------------------------------------------------------------------------
// Auxiliary Functions
// ----------------------------------------------------------------------------------------------------------------------
	
	/**
	 * Return the current kernel instance object
	 * 
	 * @return Kernel
	 */
	function getKernelInstance(){
		return Kernel::getInstance();
	}

	/**
	 * Função que verifica se um existe um valor definido; em caso negativo é retornado o valor por omissão
	 * @param mixed $var com a variável a ser verificada
	 * @param mixed $default com o valor a retornar em caso de não definição
	 * @return mixed $var or $default
	 *
	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
	 * @version 2.0
	 */
	function setValue($var,$default=NULL){
		if(!isset($var)){
			return $default;
		}else{
			return $var;
		}
	}

	/**
	 * Função responsável pela eliminação de ficheiros
	 * @param string $file com o endereço do ficheiro a eliminar
	 * @return boolean com o resultado da eliminação ou false
	 *
	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
	 * @version 2.0
	 */
	function delFile($ficheiro){
		if(is_writable($ficheiro)){
			return @unlink($ficheiro);
		}
		return false;
	}

	/**
	 * Função responsável pela limitação de os caracteres de uma string e colocação de reticências no caso de excesso de caracteres
	 * @param string $string com a string a verificar
	 * @param int $limit com o tamanho máximo de caracteres a permitir
	 * @return string com um tamanho até ao especificado
	 *
	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
	 * @version 1.1
	 */
	function subString($string,$limit){
		$string = clearTagsFrom($string);
		if(strlen($string)>$limit){
			$string = substr($string,0,$limit);
			$string = substr($string,0,strrpos($string," "))."...";
		}
		return $string;
	}

	/**
	 * Função que permite extrair apenas o texto de uma string html formatada
	 * @param string $string com a string a limpar
	 * @return string sem tags html
	 *
	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
	 * @version 2.0
	 */
	function clearTagsFrom($string){
		$search = array ("'<script[^>]*?>.*?</script>'si","'<[\/\!]*?[^<>]*?>'si","'([\r\n])[\s]+'","'&(quot|#34);'i","'&(amp|#38);'i","'&(lt|#60);'i","'&(gt|#62);'i","'&(nbsp|#160);'i","'&(iexcl|#161);'i","'&(cent|#162);'i","'&(pound|#163);'i","'&(copy|#169);'i","'&#(\d+);'e");
		$replace = array ("","","\\1","\"","&","<",">"," ",chr(161),chr(162),chr(163),chr(169),"chr(\\1)");
		return preg_replace ($search, $replace,$string);
	}

	/**
	 * Função que permite extrair apenas o(s) endereço(s) de correio electrónico de uma string composta
	 * @param string $string com a string com os endereços a extrair
	 * @param string $extrachars com os caracteres extras que um endereço de correio electrónico pode conter
	 * @return array com o(s) endereço(s) de correio electrónico encontrado(s)
	 *
	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
	 * @version 2.0
	 */
    function extractEmailsFrom($string="",$extrachars="çáàãâéèêíìîĩóòõôúùũûÇÁÀÃÂÉÈÊÍÌĨÎÓÒÕÔÚÙŨÛ"){
        preg_match_all("/[\._a-zA-Z0-9-$extrachars]+@[\._a-zA-Z0-9-$extrachars]+/i", $string, $matches);
        return $matches[0];
    }

	/**
	 * Função que permite gerar uma cadeia de caracteres aleatórios com um dado comprimento
	 * @param int $length com o tamanho da string a devolver
	 * @return string aleatório com o número de caracteres especificado
	 *
	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
	 * @version 2.0
	 */
	function generateRandomString($length){
		return substr(base64_encode(base64_encode(mt_rand(1000,100000))),0,$length);
	}

	/**
	 * Permite a inclusão de um icone embebido num página (quando suportado)
	 * @param string $icon com o endereço do ficheiro
	 * @param string $type com a especificação do tipo de ficheiro
	 *
	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
	 * @version 3.0
	 */
	function iconPageHere($icon,$type="image/png"){
		if($mimeType = Kernel::getFileMimeType($icon)){
    		echo("<link rel=\"icon\" type=\"$mimeType\" href=\"data:$mimeType;base64,".base64_encode(file_get_contents($icon))."\" />");
		}
	}

	/**
	 * Função que codifica um array multidimensional numa string no formato json para ser utilizada, por exemplo, em funções javascript
	 *
	 * @param array $data
	 * @return string codificada no formato json com os dados do array, ou false caso a conversão tenha falhado
	 */
	function js_encode($data){
	    if(function_exists("json_encode")){
	        return json_encode($data);
        }else{
            Kernel::logMsg("O módulo JSON não está activo na configuração actual do PHP. Isto poderá fazer com que os módulos que dependem destes dados falhem. Por favor instale esta extensão no seu servidor web.");
        }
        return false;
	}

	/**
	 * Função que descodifica uma string no formato json  para um array multidimensional
	 *
	 * @param string $data
	 * @return array com os dados ou false caso não seja possível fazer a conversão
	 */
	function js_decode($data){
	    if(function_exists("json_decode")){
	        return json_decode($data,true);
        }else{
            Kernel::logMsg("O módulo JSON não está activo na configuração actual do PHP. Isto poderá fazer com que os módulos que dependem destes dados falhem. Por favor instale esta extensão no seu servidor web.");
        }
        return false;
	}

	/**
	 * Função que codifica um array (e subarrays) como UTF8
	 *
	 * @param array $array com o array a recodificar
	 * @return array recodificado
	 */
	function encodeArrayAsUTF8($array){
	    if(is_array($array)){
	        foreach($array as $field=>$value){
	            if(is_array($value)){
	                $array[$field] = encodeArrayAsUTF8($value);
	            }else{
	                $array[$field]=utf8_encode($value);
	            }
	        }
	    }
	    return $array;
	}

	/**
	 * Função responsável pela escrita de um ficheiro a partir de um campo da tabela MySQL
	 *
	 * @param string $table, com o nome da tabela de origem
	 * @param string $fileDataField, com o nome do campo de dados na tabela de origem
	 * @param string $fileTypeField, com o nome do campo do tipo de ficheiro na tabela de origem
	 * @param string $keyField, com o nome do campo a pesquisar
	 * @param string $keyValue, com o valor do campo a comparar com $keyField
	 * @return boolean true ou false com o resultado da operação
	 *
	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
	 * @version 2.0
	 *
	 */
	function writeMySqlImage($table=NULL,$fileDataField=NULL,$fileTypeField=NULL,$keyField=NULL,$keyValue=NULL){
	    return Download::writeMySqlImage($table,$fileDataField,$fileTypeField,$keyField,$keyValue);
	}


	/**
     * Função para verificar se um dado utilizador tem um determinado papel atribuído. Esta função é mais rápida a processar do que a função 'canUserBe' - que depende desta função -
     * (pois ao contrário desta última não executa uma query mysql para carregar os perfis do utilizador) pois utiliza os dados associados ao utilizador e armazenados em cache.
     * Sempre que for necessário saber qual o perfil do utilizador actual, deve ser utilizado este método; por outro lado, se se quiser saber se um dado tem um perfil com permissões
     * superiores ou iguais às do perfil especificado, deve ser utilizado a função 'canUserBe'.
     *
     * @param [string $userprofile], com o tipo de utilizador a testar. Caso este parâmetro não seja fornecido, será retornado o tipo do utilizador do utilizador ou false
     * @param [string $profile_field], com o nome do campo com o perfil do utilizador
     * @return boolean
     */
    function isUserAn($userprofile,$profile_field="perfil"){
        if(User::isUserAuthenticated()){
            return ((!is_null($userprofile))?User::getUser()->checkProperty($profile_field,$userprofile):User::getUser()->checkProperty($profile_field));
        }
        return false;
    }


	/**
     * Função para determinar quais os papeis que o utilizador autenticado pode assumir, a partir um campo enum da tabela utilizadores. A verificação é feita pela ordem das enumerações do campo, ou seja
     * se a estrutura de dados da enumeração é pela ordem 'administrador','utilizador','convidado', um utilizador com um perfil de utilizador terá permissões de convidado. Por outro lado
     * se a estrutura de dados da enumeração é 'utilizador','convidado','administrador', um utilizador com um perfil de utilizador, terá, neste caso as permissões do perfil utilizador (a que pertence),
     * convidado e administrador. Optou-se por não colocar estes métodos e associá-los ao utilizador, pois são métodos especificos deste projecto que a serem integrados
     * possivelmente farão mais sentido na classe AuthenticationPluginUsingMySql.
     *
     * @param string $userprofile, com o tipo de utilizador a testar. Caso este parâmetro não seja fornecido, será retornado o tipo do utilizador do utilizador ou false
     * @param [string $table_profile_field], com o nome do campo com o perfil do utilizador na tabela utilizadores
     * @return boolean true caso o perfil do utilizador tenha sido encontrado e esteja num nivel superior ou igual ao especificado
     *
     * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
     * @version 2.0
     */
    function canUserBe($userprofile,$table_profile_field="perfil"){
    	$usertypes = MySQLOperations::getEnumValues($table_profile_field,USER_TBL);
    	if(in_array($userprofile,$usertypes)){
	    	foreach($usertypes as $key=>$profile){
	    		if(isUserAn($profile,$table_profile_field)){
	    			return true;
	    		}
	    		if($profile==$userprofile){
	    			return false;
	    		}
	    	}
    	}
    	return false;
    }

    /**
     * Função que tenta obter uma url com base nas variáveis de ambiente do servidor
     *
     * @return string com a URL
     */
    function getBaseUrl(){
    	return (((!isset($_SERVER['HTTPS']) || is_null($_SERVER['HTTPS']))?"http://":"https://").$_SERVER['HTTP_HOST'].dirname($_SERVER["PHP_SELF"]).'/');
    }

    /**
     * Create a year date interval string
     * @param $year with the start year
     * @return string
     */
    function licenseDate($year){
		return (is_integer($year)?(gmdate("Y")<=$year)?gmdate("Y"):("$year-".gmdate("Y")):gmdate("Y"));
	}
	
	/**
	 * Procura por todas as ocorrências de um comentário HTML e devolve-as no $results
	 * @param $tagname String com o nome da tag a procurar
	 * @param $content String com o conteúdo HTML a ser pesquisado
	 * @param $results array com os resultados
	 */
	function findHtmlCommentInContent($tagname, $content, &$results=array()){
		preg_match_all("(\<!--($tagname)[\s]{0,}(.*?)[\s]{0,}--\>)", $content, $results);
		return $results;
	}
	
	/**
	 * 
	 */
	function adminURL(){
		if(isset($_REQUEST['adminURL'])){
			return "?adminURL";
		}
		return "?index";
	}
	
	/**
	 * json_decode substitute for those that are using a PHP version bellow 5.2
	 *
	 * @author www@walidator.info
	 * @see http://pt2.php.net/manual/en/function.json-decode.php#91216 for reference
	 */
	if(!function_exists('json_decode') ){
		function json_decode($json){
			getKernelInstance()->logMsg("A função json_decode não foi encontrada. Tem a certeza que a extensão JSON está activa?");
		}
	}
?>
