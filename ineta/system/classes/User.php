<?php
 
	/**
	 * Authenticate Class
	 * 
	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
	 * @version 1.0 
	 */
	class User{
		/**
		 * @access public
		 */
		//-->
		public $username;
	    
	    /**
	     * @access private 
	     */
	    private $_sessionName;
	    private $_userProperties;
	    private $_successfulAuthenticationPlugin;
		
		/**
		 * Constructor da classe
		 * 
		 * @param string $username, com o nome de utilizador
		 */
		final function User($username){
			$this->username=$username;
			$this->_sessionName=md5('authentication_key_'.PRODUCT_NAME."-".PRODUCT_VERSION);
		}
		
		/**
		 * Tenta autenticar o utilizador recorrendo aos plugins de autenticação e em caso de sucesso obtem as propriedades do utilizador
		 * 
		 * @access public
		 * @param string $password, com a senha do utilizador
		 * @param string $passwordEncrypted, caso a senha esteja cifrada
		 * @param string $username, com o nome de utilizador a utilizar para a autenticação no servidor
		 * 
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 3.0
		 */
		final public function authenticate($password,$passwordEncrypted=true,$username=NULL){
		    $this->_userProperties = array();
		    
		    if(!$passwordEncrypted){
		        $password = $this->encryptedPassword($password);
		    }
		    
		    if(!is_null($username)){
			    $this->username = $username;
		    }
		    if(is_null($this->username)||!isset($this)){
		        return false;
		    }
		    
			$result = false;
			$authentication_plugins = preg_split("/[,.;]/",AUTHENTICATION_CLASSES);
			class_exists("AuthenticationPlugin",true);
			foreach ($authentication_plugins as $plugin){
			    if(!$result){
			        if(class_exists($plugin)){
			            $authenticationplugin = new $plugin();
			            if(is_subclass_of($authenticationplugin,"AuthenticationPlugin") && method_exists($authenticationplugin,"authenticate")){
    			            if($result = $authenticationplugin->authenticate($this->username,$password)){
    			            	$this->_successfulAuthenticationPlugin = $authenticationplugin;
    			                $this->updateProperties();
    			                $_SESSION[USER_SESSION] = $this;
    		                }    
			            }else{
			                $this->_logMsg("O plugin $plugin não é um plugin autenticação válido. Isto pode levar problemas de segurança e de autenticação. Por favor desactive o plugin do ficheiro de configuração ou corriga o problema.");
			            }
		            }else{
		                $this->_logMsg("Não foi possível encontrar o plugin de autenticação $plugin. Isto pode levar a resultados inesperados. Por favor desactive o plugin do ficheiro de configuração ou coloque o ficheiro com a classe no directório das classes.");
		            }
			    }
			}
			return $this->_authenticate($result);
		}
		
		/**
    	 * @method responsável por actualizar as propriedades do utilizador autenticado
    	 * 
    	 * @param 
    	 * @return bool com o resultado da operação
    	 * 
    	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 2.0
    	 */
    	final public function updateProperties($plugin=NULL){
    	    if(isset($this) && $this->isAuthenticated()){
        	    if(is_null($plugin)){
        	         $plugin = $this->_successfulAuthenticationPlugin;
        	    }
        	    if(is_subclass_of($plugin,"AuthenticationPlugin") && method_exists($plugin,"getUserProperties")){
                    $this->_userProperties = $plugin->getUserProperties();
                    $this->_userProperties["AuthenticationPlugin"] = $plugin;
                    return true;
                }else{
                    $this->_logMsg("O plugin ".get_class($plugin)." não é um plugin autenticação válido. Isto pode levar problemas de segurança e de autenticação. Por favor desactive o plugin do ficheiro de configuração ou corriga o problema.");    
                }
    	    }
            return false;
    	}
		
		/**
    	 * @method responsável por terminar a sessão do utilizador
    	 * 
    	 * @return bool com o resultado da operação
    	 * 
    	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 2.0
    	 */
    	final public function logout(){
    	    return !$this->_authenticate();
    	}
		
    	/**
    	 * @method responsável pela verificação da validade das sessões autenticadas
    	 * @return boolean true caso a chave esteja correcta ou false nos restantes casos 
    	 * 
    	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 3.0
    	 */
    	final public function isAuthenticated(){
    		return ((isset($this) && isset($_SESSION[$this->_sessionName]) && $_SESSION[$this->_sessionName]==md5($this->_dynamicAuthenticationString()))?true:false);
    	}
	
    	/**
		 * Função responsável pela verificação dos privilégios de um dado utilizador num dado módulo ou tabela
		 * 
		 * @param string $propertyName com o nome do campo a verificar
		 * @param string $propertyValue com o valor do campo a comparar
		 * @return mixed: string com o valor da campo caso o argumento $propertyValue seja NULL; boolean true caso a comparação seja verdadeira, false caso a comparação seja falsa ou o campo não exista
		 * 
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
		 * @version 3.0
		 */
		public function checkProperty($propertyName=USER_TBL,$propertyValue=NULL){
			if(isset($this)){
			    if($this->authenticationPlugin('AuthenticationPluginUsingFailSafe')){
			    	if(is_null($propertyValue)){
			    		return $this->_getProperty($propertyName);
			    	}
			    	return true;
			    }
				if(!is_null($propertyValue)){
					return (($this->_getProperty($propertyName)!==false && $this->_getProperty($propertyName)==$propertyValue)?true:false);
				}else{
					return $this->_getProperty($propertyName);
				}
			}
			return false;
		}
	
		/**
    	 * @method que cifra um texto de acordo com um algoritmo específico
    	 * 
    	 * @param string $password, com o texto a cifrar
    	 * @return string com o texto cifrado
    	 * 
    	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 3.0
    	 */	
	    final public static function encryptedPassword($password){
	        return (function_exists("hash")?hash("sha256",(base64_encode($password))):Kernel::logMsg("O módulo hash não está activo na sua configuração de PHP actual. Isto irá impedir que os utilizadores da plataforma se possam autenticar. Por favor corrija o problema.","Aviso"));
	    }
	    
	    /**
    	 * @method que regista acções do utilizador
    	 * 
    	 * @param string $action, com o texto a guardar
    	 * @param [optional] string $table, com o nome da tabela a guardar os dados
    	 * @return boolean true ou false respectivamente em caso de sucesso ou falha
    	 * 
    	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 2.0
    	 */
	    public function logAction($action,$replaceIfExists=false){
	    	
	    	if(isset($this) && $this->isAuthenticated()){
        	    if(is_null($plugin)){
        	         $plugin = $this->_successfulAuthenticationPlugin;
        	    }
        	    if(is_subclass_of($plugin,"AuthenticationPlugin") && method_exists($plugin,"logAction")){
                    $plugin->logAction($action,NULL,$replaceIfExists);
                    return true;
                }else{
                    $this->_logMsg("O plugin ".get_class($plugin)." não é um plugin autenticação válido. Isto pode levar problemas de segurança e de autenticação. Por favor desactive o plugin do ficheiro de configuração ou corriga o problema.");
                }
    	    }
            return false;
	    }
	    
	    /**
    	 * @method que obtém o ID do utilizador
    	 * 
    	 * @return string com a ID do utilizador
    	 * 
    	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 1.0
    	 */
	    final public function ID(){
	    	return $this->checkProperty(USER_ID_FIELD);
	    }
	    
		/**
    	 * @method que verifica qual o método de autenticação utilizado para o utilizador actual
    	 * 
    	 * @return string com o método de autenticação utilizado ou boolean true se o método de autenticação utilizado foi o fornecido como parâmetro ou false
    	 * 
    	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 3.0
    	 */
		public function authenticationPlugin($authenticationPluginUsed=NULL,$returnPlugin=false){
			if(isset($this) && $this->isAuthenticated()){
				return ((empty($authenticationPluginUsed))?(($returnPlugin)?$this->_successfulAuthenticationPlugin:get_class($this->_successfulAuthenticationPlugin)):((get_class($this->_successfulAuthenticationPlugin)==$authenticationPluginUsed)?true:false));
			}
			return false;
		}
		
		/**
    	 * @method que recorre à função de sistema Kernel::logMsg para enviar informações e mensagens de erro
    	 * 
    	 * @param string $msg com a mensagem a registar  
    	 * 
    	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 1.0
    	 */
		private function _logMsg($msg=""){
		    if(method_exists("Kernel","logMsg")){
                Kernel::logMsg($msg);
            }
		}
		
		/**
    	 * @method responsável pela criação de uma chave de autenticação única para cada sessão
    	 * 
    	 * @return string com a chave a ser utilizada  
    	 * 
    	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 1.0
    	 */		
		final private function _dynamicAuthenticationString(){
		    return base64_encode($this->username."@".PRODUCT_NAME."-".PRODUCT_VERSION.".".session_id()); 
	    }
	    
		/**
    	 * @method responsável pela criação ou destruição das variáveis de autenticação necessárias para validar a autenticação
    	 * 
    	 * @param boolean $bool, true para criar as variáveis necessárias, false para destruir as variáveis de autenticação (e terminar a sessão do utilizador caso exista) 
    	 * 
    	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 1.0
    	 */		
		final private function _authenticate($bool=false){
		    if($bool){
		        $_SESSION[$this->_sessionName]=md5($this->_dynamicAuthenticationString());
		        $this->logAction("O utilizador iniciou a sua sessão");
		    }else{
		        if(isset($_SESSION[$this->_sessionName])){
		        	$this->logAction("O utilizador terminou a sua sessão");
		            $this->_userProperties = array();
		            $_SESSION[$this->_sessionName]="";
    				@session_unset();
    				unset($_SESSION[$this->_sessionName]);
    				@session_regenerate_id();
		        }
		    }
		    return $bool;
	    }
	    
	    /**
    	 * @method que obtém uma dada propriedade a partir do plugin de autenticação
    	 * 
    	 * @return mixed com o valor da propriedade ou false caso esta não exista
    	 * 
    	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 3.0
    	 */
    	final private function _getProperty($name=USERNAME_FIELD){
    		$this->updateProperties();
    	    return ((isset($this) && isset($this->_userProperties[$name]))?$this->_userProperties[$name]:false);
    	}
    	
		/**
		 * Função responsável pela validação das credenciais do utilizador utilizando métodos internos
		 * 
		 * @param string $username, com o nome de utilizador a utilizar para a autenticação no servidor
		 * @param string $password, com a senha do utilizador
		 * @param string $passwordEncrypted, caso a senha esteja cifrada
		 * @return boolean true em caso de sucesso ou false
		 * 
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
	     * @version 1.0
		 */
		public function authenticateUser($username,$password,$encrypted=true){
			if(isset($username)){
				$user = new User($username);
				if($user->authenticate($password,$encrypted)){					
					return $user->isAuthenticated();
				}			
			}
			return false;
		}
		
		/**
		 * Função que devolve a sessão do utilizador
		 * 
		 * @return User autenticado
		 * 
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
	     * @version 1.0
		 */
		public function getUser(){
			if(isset($_SESSION[USER_SESSION])){
				return $_SESSION[USER_SESSION];
			}
			return false;
		}
	
		/**
	     * Função para verificar se um dado utilizador está autenticado
	     *
	     * @return boolean
	     */
	    public static function isUserAuthenticated(){
	    	return (User::getUser() && User::getUser()->isAuthenticated());
	    }
    	
	}
	
?>