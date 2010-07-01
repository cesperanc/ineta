<?php
// Desactivar o acesso directo ao ficheiro
if(!function_exists("getKernelInstance")){
	error_log("Tentiva de acesso directo ao ficheiro ".__FILE__,0);
	// Silence is golden
	die('');
}

// Executa operações que estejam pendentes
doOperations();

// Carrega os módulos php do site
getKernelInstance()->actionAddFunction("_loadModules", function(){
	// Carregar os módulos da pasta modules
	Kernel::autoLoadFrom(dirname(__FILE__).'/modules/', '/.php$/', 2);
});


/**
 * Função para verificar se um dado utilizador está autenticado como administrador.
 *
 * @return boolean
 */
function isUserAnAdmin(){
	return (isUserAn("administrador"));
}

/**
 * Verifica se o modo de administração está activo
 */
function adminEnabled(){
	if(isUserAnAdmin() && isset($_REQUEST['adminURL'])){
		return true;
	}
	return false;
}

/**
 * Mostra uma mensagem de autenticação
 * 
 * @param mixed com um parametro opcional enviado pelo metodo Kernel::executeAction
 * @param array $message, com o objecto com a mensagem a apresentar
 * @return String com a mensagem
 * 
 */
function authenticationMessage($value=NULL, $message=array()){
	if(!empty($message) && isset($message['message'])){
		return $message;
	}
	return '';
}

/**
 * Executa operações pendentes (antigo common.php)
 */
function doOperations(){
	$m_error_start = "<div style='padding: 0pt 0.7em;' class='ui-state-error ui-corner-all'><p><span style='float: left; margin-right: 0.3em;' class='ui-icon ui-icon-alert'>&nbsp;</span>";
	$m_success_start = "<div style='padding: 0pt 0.7em;' class='ui-state-highlight ui-corner-all'><p><span style='float: left; margin-right: 0.3em;' class='ui-icon ui-icon-info'>&nbsp;</span>";
	$m_end = "</p></div>";
	switch($_REQUEST['action']){
	    case 'login':
	    	if(User::authenticateUser($_POST["username"], User::encryptedPassword($_POST["password"]))){
				getKernelInstance()->actionAddFunction("authenticationMessage", "authenticationMessage", 10, 0, array('title'=>'Bem-vindo', "message"=>"$m_success_start A autenticação foi concluída com sucesso. <a href=\"./?adminURL\">Aceder à área administrativa</a>?$m_end"));
			}else{
				getKernelInstance()->actionAddFunction("authenticationMessage", "authenticationMessage", 10, 0, array('title'=>'Erro', "message"=>"$m_error_start A autenticação falhou.$m_end"));
			}
			break;
		case 'logout':
		    if(User::getUser() && User::getUser()->logout()){
    			getKernelInstance()->actionAddFunction("authenticationMessage", "authenticationMessage", 10, 0, array('title'=>'Obrigado', "message"=>"$m_success_start A sessão foi terminada.$m_end"));
    		}else{
    			getKernelInstance()->actionAddFunction("authenticationMessage", "authenticationMessage", 10, 0, array('title'=>'Erro', "message"=>"$m_error_start Não foi possível terminar a sessão.$m_end"));
    		}
			break;
    }
    
    /**
     * Replace the page title for the error message (if any)
     */
    getKernelInstance()->actionAddFunction("parseTitle",function($title){
    	$authenticationMessage = getKernelInstance()->actionExecute("authenticationMessage", false, true);
    	if(!empty($authenticationMessage) && isset($authenticationMessage['title'])){
    		return $authenticationMessage['title'];
    	}
    	return $title;
	});
    /**
     * Replace the page content for an authentication message if any
     */
    getKernelInstance()->actionAddFunction("parseContent",function($content){
    	$authenticationMessage = getKernelInstance()->actionExecute("authenticationMessage", false, true);
    	if(!empty($authenticationMessage) && isset($authenticationMessage['message'])){
    		return $authenticationMessage['message'];
    	}
    	return $content;
	});
}

