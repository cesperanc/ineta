<?php
	/**
	 * MySQL Class
	 * 
	 * @example $mysql = new MySQL(hostname:String,username:String,password:String,database:String[,email:String]);
	 * @example $result = MySQL::doQuery(query:String[,hostname:String,username:String,password:String,database:String]);
	 * 
	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
	 * @version 4.1
	 */
	class MySQL{
		/**
		 * @access private
		 */
		private $hostname;
		/**
		 * @access private
		 */
		private $username;
		/**
		 * @access private
		 */
		private $password;
		/**
		 * @access private
		 */
		private $database;
		/**
		 * @access private
		 */
		private $email;
		/**
		 * @access private
		 */
		private $connection;
		/**
		 * @access private
		 */
		private $_connectionStatus;
		/**
		 * @access private
		 */
		private $_ignoreErrors;
		
		/**
		 * Constructor da classe
		 * 
		 * @param string $hostname, com o nome ou endereço do servidor com o serviço MySQL a utilizar
		 * @param string $username, com o nome de utilizador a utilizar para a autenticação no servidor
		 * @param string $password, com a senha do utilizador a utilizar para a autenticação no servidor
		 * @param string $database, com o nome da base de dados a seleccionar
		 * @param string $email, com os endereços de email a utilizar para reportar problemas
		 * @param string $ignoreErrors, desactiva o registo de problemas na utilização desta classe
		 */
		function MySQL($hostname=NULL,$username=NULL,$password=NULL,$database=NULL,$email=NULL,$ignoreErrors=false){
			$hostname = ((!empty($hostname))?$hostname: HNAME);
			$username = ((!empty($username))?$username: UNAME);
			$password = ((!empty($password))?$password: PWORD);
			$database = ((!empty($database))?$database: DBASE);
			$email = ((!empty($email))?$email: defined('SUPPORT_EMAILS')?SUPPORT_EMAILS:'');
			$ignoreErrors = ((!empty($ignoreErrors))?$ignoreErrors: false);
			
			$this->setHostname($hostname);
			$this->setUsername($username);
			$this->setPassword($password);
			$this->setDatabase($database);
			$this->setEmail($email);
			$this->_connectionStatus = false;
			$this->_ignoreErrors = $ignoreErrors;
			$this->setConnection();
		}
		/**
		 * Define a propriedade hostname do objecto
		 * @access public
		 * @param string $hostname, com o nome ou endereço do servidor com o serviço MySQL a utilizar
		 */
		public function setHostname($hostname=HNAME){
			$this->hostname = $hostname;
		}
		/**
		 * Define a propriedade username do objecto
		 * 
		 * @access public
		 * @param string $username, com o nome de utilizador a utilizar para a autenticação no servidor
		 */
		public function setUsername($username=UNAME){
			$this->username = $username;
		}
		/**
		 * Define a propriedade password do objecto
		 * 
		 * @access public
		 * @param string $password, com a senha do utilizador a utilizar para a autenticação no servidor
		 */
		public function setPassword($password=PWORD){
			$this->password = $password;
		}
		/**
		 * Define a propriedade database do objecto
		 * 
		 * @access public
		 * @param string $database, com o nome da base de dados a seleccionar
		 */
		public function setDatabase($database=DBASE){
			$this->database = $database;
		}
		/**
		 * Define a propriedade email do objecto
		 * 
		 * @access public
		 * @param string $email, com os endereços de email a utilizar para reportar problemas
		 */
		public function setEmail($email=SUPPORT_EMAILS){
			$this->email = $email;
		}
		/**
		 * Cria a ligação à base de dados e guarda o handler da ligação
		 * 
		 * @access public
		 */
		public function setConnection(){
			$this->connection = $this->dbLink($this->hostname, $this->username, $this->password, $this->database);
		}
		
		/**
		 * Retorna o hostname
		 * 
		 * @access public
		 * @return string com o nome ou endereço do servidor com o serviço MySQL
		 */
		public function getHostname(){
			return $this->hostname;
		}
		/**
		 * Retorna o username
		 * 
		 * @access public
		 * @return string $username, com o nome de utilizador a utilizar para a autenticação no servidor
		 */
		public function getUsername(){
			return $this->username;
		}
		/**
		 * Retorna a senha
		 * 
		 * @access public
		 * @return string $password, com a senha do utilizador a utilizar para a autenticação no servidor
		 * @note por razões de segurança este método irá devolver NULL
		 */
		public function getPassword(){
			//return $this->password;
			return NULL;
		}
		/**
		 * Retorna o nome da base de dados
		 * 
		 * @access public
		 * @return string $database, com o nome da base de dados a seleccionar
		 */
		public function getDatabase(){
			return $this->database;
		}
		/**
		 * Retorna o endereço de email
		 * 
		 * @access public
		 * @return string $email, com os endereços de email a utilizar para reportar problemas
		 */
		public function getEmail(){
			return $this->email;
		}
		
		/**
		 * @method retorna a ligação a uma base de dados com os dados enviados aquando a construção do objecto. Este método é autonomo, ou seja, pode ser chamado directamente sem necessidade de um objecto próprio.
		 * 
		 * @access public
		 * @param string $hostname, com o nome ou endereço do servidor com o serviço MySQL a utilizar
		 * @param string $username, com o nome de utilizador a utilizar para a autenticação no servidor
		 * @param string $password, com a senha do utilizador a utilizar para a autenticação no servidor
		 * @param string $database, com o nome da base de dados a seleccionar
		 * @return handler de ligação mysql
		 */
		public function dbLink($hostname=HNAME,$username=UNAME,$password=PWORD,$database=DBASE){
			if(!$ligacao = @mysql_connect($hostname,$username,$password)){
				if(isset($this) && !$this->_ignoreErrors){
					Kernel::logMsg("A ligação ao servidor ".$hostname." falhou.","Erro","syslog");
				}
				return false;
			}
			if(!@mysql_select_db($database, $ligacao)){
				if(isset($this) && !$this->_ignoreErrors){
					Kernel::logMsg("A ligação à base de dados ".$database." falhou.","Erro","syslog");
				}
				return false;
			}
			if(isset($this)){
				$this->_connectionStatus = true;
			}
			return ($ligacao);
		}
		
		/**
		 * @method executa uma dada pesquisa utilizando a ligação do contructor. Este método é autonomo, ou seja, pode ser chamado directamente sem necessidade de um objecto próprio.
		 * 
		 * @access public
		 * @param string $query, com a query SQL a executar após a ligação
		 * @param string $hostname, com o nome ou endereço do servidor com o serviço MySQL a utilizar
		 * @param string $username, com o nome de utilizador a utilizar para a autenticação no servidor
		 * @param string $password, com a senha do utilizador a utilizar para a autenticação no servidor
		 * @param string $database, com o nome da base de dados a seleccionar
		 * @return object com o resultado da query
		 */
		public function doQuery($query,$hostname=HNAME,$username=UNAME,$password=PWORD,$database=DBASE,$utf8=true){
			if(isset($this)&&isset($this->connection)){
				$database = $this->getDatabase();
				$conection = $this->connection;
			}else{
				$conection = MySQL::dbLink($hostname,$username,$password,$database);
			}
			if(is_resource($conection) && $utf8){
			    mysql_query("SET NAMES utf8", $conection);
			}
			if(is_resource($conection) && $result = mysql_query($query, $conection)){
				return $result;
			}
			if(isset($this) && !$this->_ignoreErrors && method_exists("Kernel","logMsg")){
				Kernel::logMsg("Ocorreu um erro (Query: $query\n<br/>MySQL Error: ".mysql_error().").");
			}
			return false;
		}
		
		/**
		 * @method conta o número de linhas de uma operação. Este método é autonomo, ou seja, pode ser chamado directamente sem necessidade de um objecto próprio.
		 * 
		 * @access public
		 * @param object $result, com o resultado da query SQL executada
		 * @return int com o número de linhas
		 */
		public function numRows($result){
			$numero = mysql_num_rows($result);
			return $numero;
		}
		public function connectionStatus(){
			return $this->_connectionStatus;
		}
	}
?>
