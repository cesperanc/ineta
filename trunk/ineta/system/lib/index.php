<?php
	/**
	 * kernel init file
	 *
	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
	 * @version 4.0
	 */

// configuração do directório de sistema ------------------------------------------------------------------------------------------------------------------------
	define('SYSTEMDIR','./system/');
	if(!is_readable(SYSTEMDIR)){
		die('Não é possível aceder ao directório '.SYSTEMDIR.'. Por favor verifique se o directório existe e/ou se é acessível ao serviço Web.');
	}

	// configurações de directórios ---------------------------------------------------------------------------------------------------------------------------------
	define('SCRIPTSDIR', 'scripts/');
	define('MODULESDIR', SCRIPTSDIR.'modules/');
	define('CLASSPATH', SYSTEMDIR.'classes/'.':'.MODULESDIR.'common/classes/');

// ficheiro de configuração e plataforma ------------------------------------------------------------------------------------------------------------------------
	$file = SYSTEMDIR.'.config.php';
	if(!(is_readable($file) && require_once($file))){
		?>
			<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
			<html xmlns="http://www.w3.org/1999/xhtml">
				<head>
					<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
					<title>Configuração da plataforma</title>
					<style type="text/css">
						form label{
							clear: left;
							display:block;
							float:left;
							width:450px;
						}
					</style>
				</head>
				<body>
					<?php
						$skipConfig=false;
						$message = false;
						
						if(!empty($_POST)){
							if(!empty($_POST['HNAME']) && !empty($_POST['DBASE']) && !empty($_POST['UNAME'])){
								$hostname = $_POST['HNAME'];
								$username = $_POST['UNAME'];
								$password = $_POST['PWORD'];
								$database = $_POST['DBASE'];
								if(!$ligacao = mysql_connect($hostname, $username, $password)){
									$message = "A ligação ao servidor <strong>$hostname</strong> com o nome de utilizador <strong>$username</strong> falhou.";
								}else if(!mysql_select_db($database, $ligacao)){
									$message = "A selecção da base de dados <strong>$database</strong> falhou.";
								}else{
				    				if(file_put_contents($file,"<?php \n/**\n * Ficheiro de configuração da plataforma\n * Data: ".gmdate("Y-m-d H:i:s")."\n */// Definições de acesso ao MySQL\ndefine(\"HNAME\",\"$hostname\");\ndefine(\"UNAME\",\"$username\");\ndefine(\"PWORD\",\"$password\");\ndefine(\"DBASE\",\"$database\");\n?>")!==false){
				    					?>
				    						<h1>Configuração concluída</h1>
				    						<p>A configuração foi concluída com sucesso. Já pode visitar o <a href="./" title="Clique para visitar o site">site</a>.</p> 
				    					<?php 
				    					$skipConfig=true;
				    				}else{
				    				    $message .= "A ligação à base de dados foi executada com sucesso, no entanto não foi possível criar o ficheiro <strong>$file</strong>. Verifique se o daemon do servidor web tem permissões de escrita no directório deste ficheiro.";
				    				}
								}
							}else{
								$message = "Os dados submetidos são inválidos. Por favor, verifique as definições.";
							}
						}else{
							$message = "Não foi possível aceder ao ficheiro de configuração <strong>$file</strong>.";
						}
						if(!$skipConfig):
							?>
								<?php if($message): ?><p><small><?php echo($message); ?>&nbsp;</small></p><?php endif; ?>
								<h1>Configuração:</h1>
								<p>Por favor, utilize o formulário abaixo para especificar as definições de acesso à base de dados.</p>
								<form action="./" method="post">
									<p><label for="HNAME">Nome ou endereço do servidor da base de dados: </label><input type="text" id="HNAME" name="HNAME" value="<?php echo(empty($_POST['HNAME'])?'localhost':$_POST['HNAME']); ?>" /></p>
									<p><label for="DBASE">Nome da base de dados: </label><input type="text" id="DBASE" name="DBASE" value="<?php echo(empty($_POST['DBASE'])?'project_db':$_POST['DBASE']); ?>" /></p>
									<p><label for="UNAME">Nome do utilizador para acesso à base de dados: </label><input type="text" id="UNAME" name="UNAME" value="<?php echo(empty($_POST['UNAME'])?'user':$_POST['UNAME']); ?>" /></p>
									<p><label for="PWORD">Senha do utilizador para acesso à base de dados: </label><input type="password" id="PWORD" name="PWORD" value="<?php echo(empty($_POST['PWORD'])?'':$_POST['PWORD']); ?>" /></p>
									<p><input type="submit" value="Guardar" /> <input type="reset" value="Repor" /></p>
								</form>
							<?php 
						endif;
					?>
				</body>
			</html>
		<?php 
		exit();
	}
	$file = SYSTEMDIR.'lib/system.php';
	if(!(is_readable($file) && require_once($file))){
		die("Não foi possível aceder ao ficheiro de sistema. Por favor verifique se o ficheiro $file existe e as respectivas permissões.");
	}
	unset($file);

	$kernel = new Kernel();

	$kernel->appendToConfig('PRODUCT_FULL_NAME', 'Associação Fátima Cultural', 'Nome completo do projecto', true, false);
	$kernel->appendToConfig('PRODUCT_NAME', 'AFAC', 'Nome curto para a plataforma', true, false);
	$kernel->appendToConfig('PRODUCT_VERSION', '1.0', 'Versão da plataforma', true, false);
	$kernel->appendToConfig('PRODUCT_CONFIGURED', 'false', 'false para referenciar o produto como não estando configurado, true para que o sistema execute os procedimentos de instalação (nomeadamente a execução das queries de instalação dos módulos)', true, true);
	
	$kernel->appendToConfig('DEBUG_MODE', 'false', 'false para activar o modo de produção, true para activar o modo de depuração.', true, true);
	$kernel->appendToConfig('CHARS_TO_REMOVE_FROM_BUFFER', "\'/[\n\t\r]/\'", 'Expressão regular com os caracteres que devem ser retirados na limpeza do código.', true, true);
	
	$kernel->appendToConfig('FROM_EMAIL', 'AFAC <root@afac.pt>', 'Endereço de correio electrónico de envio de mensagens da plataforma', false, false);
	$kernel->appendToConfig('SUPPORT_EMAILS', 'Cláudio Esperança <2070030@student.estg.ipleiria.pt>, Diogo Serra <2081008@student.estg.ipleiria.pt>', 'Endereços de correio electrónico dos técnicos responsáveis pela plataforma.', true, false);
	
	$kernel->appendToConfig('SCRIPTSDIR', 'scripts/', 'Endereço do directório dos scripts do projecto', true, false);
	$kernel->appendToConfig('IMAGESDIR', 'images/', 'Endereço do directório das imagens', true, false);
	$kernel->appendToConfig('UPLOADSDIR', 'uploads/', 'Endereço do directório dos uploads', true, false);
	
	$kernel->appendToConfig('AUTHENTICATION_CLASSES', 'AuthenticationPluginUsingFailSafe,AuthenticationPluginUsingMySQL', 'Extensões de autenticação (separadas por \'\',\'\' ou por \'\';\'\')', true, false);
	
	if(class_exists('AuthenticationPluginUsingFailSafe')){
		$kernel->appendToConfig('SPROPERTIES', serialize(AuthenticationPluginUsingFailSafe::getSingleton()->getUserProperties()), 'Propriedades do super utilizador', true, false);
	}
	$kernel->appendToConfig('LOG_TO', 'database', 'Para onde devem ser enviados os registos de erros. Aceita um dos seguintes valores: \r\ndatabase\r\nfile\r\nmail\r\nsyslog', true, false);
	$kernel->appendToConfig('LOG_TO_TABLE', 'logs_tbl', 'Qual a tabela MySQL onde devem ser guardados os registos.', true, false);
	$kernel->appendToConfig('LOG_TO_FILE', 'SYSTEMDIR.\"logs/log.txt\"', 'Configuração do ficheiro de destino dos registos', true, true);
	
	if(class_exists('User')){
		$kernel->appendToConfig('LOG_USER_ACTIONS', 'sessoes_tbl', 'Qual a tabela MySQL para onde devem enviados os registos ou false para desactivar o registo.', false, false);
		$kernel->appendToConfig('USER_TBL', 'utilizadores_tbl', 'Nome da tabela de utilizadores', true, false);
		$kernel->appendToConfig('USER_ID_FIELD', 'id', 'Nome do campo índice da tabela de utilizadores', true, false);
		$kernel->appendToConfig('USERNAME_FIELD', 'utilizador', 'Nome do campo a utilizar como campo de nomes de utilizador da tabela de utilizadores', true, false);
		$kernel->appendToConfig('USER_PASSWORD_FIELD', 'senha', 'Nome do campo da tabela de utilizadores para armazenamento da senha', true, false);
	}
	
	$kernel->appendToConfig('ALWAYS_EXECUTE_MYSQL_ACTION', 'false', 'true para activar executar sempre a action _MySQL (por exemplo, para criação de tabelas), false para activar consoante o valor de PRODUCT_CONFIGURED.', true, true);
	
	$kernel->appendToConfig('USER_SESSION', '_user_', 'Nome do índice para armazenamento da cookie de sessão.', true, false);
	$kernel->appendToConfig('PING_TIME', '180', 'Número de segundos entre pings cliente - servidor', true, false);

			
	$kernel->init();
	
	unset($kernel);
?>