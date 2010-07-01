<?php

	/**
	 * AuthenticationPluginUsingFailSafe Class
	 * 
	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
	 * @version 2.0 
	 */
	class AuthenticationPluginUsingFailSafe extends AuthenticationPlugin{
	    private $_properties;
		private static $singleton = NULL;
		
		/**
		 * Constructor da classe
		 * 
		 */
		function __construct(){
			self::$singleton = $this;
		}
		
		/**
		 * Verifica as credenciais do utilizador, comparando o nome de utilizador e senha com valores internos de configuração
		 * 
		 * @access public
		 * @param string $username, com o nome de utilizador a verificar
		 * @param string $password, com a senha a comparar
		 * 
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 2.0
		 */
		final public function authenticate($username,$password){
			$obj = self::getSingleton();
			return (($obj->getUserProperty("SUSERNAME")==$username && $obj->getUserProperty("SPASSWORD")==$password)?true:false);
		}
		
		/**
		 * @see system/classes/AuthenticationPlugin#getUserProperties()
		 */
		public function getUserProperties(){
			$properties = array();
			if(defined("SPROPERTIES") && !$properties = unserialize(SPROPERTIES)){
				$properties = array('id'=>0,'SUSERNAME'=>'cesperanc','SPASSWORD'=>'bcd7d429848e9659652fe757d5be898ea71b1187d30f4ee8bf25a62e778a5d70','email'=>'cesperanc@gmail.com','nome'=>'Cláudio Esperança','perfil'=>'administrador');
			}
			if(isset($this)){
				$this->_properties = $properties;
			}
		    return $properties;
		}
		
		/**
		 * Método que obtem o valor de uma propriedadepara cada propriedade do utilizador
		 * 
		 * @access public
		 * @return array com as propriedades do utilizador
		 * 
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 2.0
		 */
		public function getUserProperty($propertyName="SUSERNAME"){
			$properties = self::getSingleton()->getUserProperties();
			if(isset($properties[$propertyName])){
				return $properties[$propertyName];
			}
			return false;
		}
		
		public static function getSingleton(){
			if(is_null(self::$singleton)){
				self::$singleton = new AuthenticationPluginUsingFailSafe();
			}
			return self::$singleton;
		}
	}
?>