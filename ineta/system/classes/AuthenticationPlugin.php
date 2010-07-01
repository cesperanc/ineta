<?php
    interface AuthenticationPluginInterface{
        public function authenticate($username,$password);
        public function getUserProperties();
        public function logAction($action);
    }
	/**
	 * Classe base de modelo de extensão de autenticação
	 * 
	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
	 * @version 2.0 
	 */
	class AuthenticationPlugin implements AuthenticationPluginInterface{
	    
		/**
		 * Constructor da classe
		 * 
		 */
		function AuthenticationPlugin(){}
		
		/**
		 * Verifica as credenciais do utilizador, comparando o nome de utilizador com a senha numa tabela SQL
		 * 
		 * @access public
		 * @param string $username, com o nome de utilizador a verificar
		 * @param string $password, com a senha a comparar
		 * @return boolean true ou false
		 * 
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 2.0
		 */
		public function authenticate($username,$password){
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
		    return array();
		}
		
		/**
    	 * @method que regista acções do utilizador
    	 * 
    	 * @param string $action, com o texto a guardar
    	 * @return boolean true ou false respectivamente em caso de sucesso ou falha
    	 * 
    	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 3.0
    	 */
		public function logAction($action){
			return false;
		}
	}
?>