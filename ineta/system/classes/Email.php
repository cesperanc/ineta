<?php

	/**
	 * Classe base de modelo de extensão de autenticação
	 *
	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
	 * @version 2.0
	 */
	class Email{
	    private $_htmlmailtemplate;
		/**
		 * Constructor da classe
		 *
		 */
	    function Email($htmlmailtemplate=false, $loadFromDBfield=false){
	    	if($loadFromDBfield){
	    		$this->_htmlmailtemplate = $this->getTemplateFromDB($loadFromDBfield);
	    	}else{
			    $this->_htmlmailtemplate = $htmlmailtemplate;
	    	}
		}

		/**
		 * Envia o email
		 *
		 * @access public
		 * @param string $from, com o endereço de email do remetente
    	 * @param string $to, com os endereços de email de destino separados com virgulas
    	 * @param string $cc, com os endereços de email CC de destino separados com virgulas
    	 * @param string $bcc, com os endereços de email BCC de destino separados com virgulas
    	 * @param string $subject, com o assunto
    	 * @param string $content, com o conteúdo
    	 * @param boolean $htmlmailtemplate, com o endereço do ficheiro a utilizar como template HTML ou false para enviar a mensagem apenas como texto simples
    	 * @return boolean true se o email foi enviado ou false
		 *
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 2.0
		 */
		public function sendEmail($from,$to,$cc,$bcc,$subject,$content,$htmlmailtemplate=false){
    	    set_time_limit(120);

    	    $uniqueId = md5(uniqid(time()));
    	    $boundary1 = "b1_".$uniqueId;
    	    $boundary2 = "b2_".$uniqueId;

    	    if(!$htmlmailtemplate && isset($this)){
    	    	$htmlmailtemplate = $this->_htmlmailtemplate;
    	    }

    	    if(defined('FROM_EMAIL')){
    	    	$from = FROM_EMAIL;
    	    }

    	    //standart headers
    	    $header.=    "Return-Path: ".trim($from)."\n".
    	                 "From: $from\n".
    	                 (($cc!="")?"Cc: $cc\n":"").
    	                 (($bcc!="")?"Bcc: $bcc\n":"").
    	                 "Message-ID: <$uniqueId@".PRODUCT_NAME."\n".
    	                 "X-Mailer: ".PRODUCT_NAME."\n".
    	                 "MIME-Version: 1.0\n";

    	    if($htmlmailtemplate){
    	        //inline images header
    		    $header.=    "Content-Type: multipart/related;\n\ttype=\"text/html\";\n\tboundary=\"$boundary1\"\n";
    	        $header.=    "\n\n";

    		    $url = (((!isset($_SERVER['HTTPS']) || is_null($_SERVER['HTTPS']))?"http://":"https://").$_SERVER['HTTP_HOST'].dirname($_SERVER["PHP_SELF"]).'/');

    	        /*//template is now a string
    	        $tmpcontent = @file_get_contents($htmlmailtemplate);
    	        */
    		    $tmpcontent = $htmlmailtemplate;
    	        $tmpcontent = str_ireplace('[[SITE]]',$subject,$tmpcontent);
    		    $tmpcontent = str_ireplace('[[TEMPLATEIMAGESDIR]]',IMAGESDIR,$tmpcontent);
    		    $tmpcontent = str_ireplace('[[TEMPLATEIMAGESURL]]',$url.IMAGESDIR,$tmpcontent);

    		    $attachments = "";
    		    $image=0;

    	        do{
    	            $imagetoattach = Email::extractInnerElements($tmpcontent);

    	            $handle = false;
    			    if(is_readable($imagetoattach)){
    			        $handle = fopen($imagetoattach, 'rb');
    			        $magic_quotes = get_magic_quotes_runtime();
        	            set_magic_quotes_runtime(0);
        			    $f_contents = @fread($handle, @filesize($imagetoattach));
        			    $f_contents = @base64_encode($f_contents);
        			    set_magic_quotes_runtime($magic_quotes);
    			    }

    			    if($handle){
    			        $fileatt_name = explode("/", $imagetoattach);
    			        $fileatt_name = $fileatt_name[count($fileatt_name)-1];
    			        $fileatt_name = explode("\\", $fileatt_name);
    			        $fileatt_name = $fileatt_name[count($fileatt_name)-1];
    			        $fileatt_type = "image/jpeg";
    			        //$fileatt_type = "application/octet-stream";

    			        if (function_exists('finfo_file')){
	    			        if(!$finfomime = finfo_open(FILEINFO_MIME)){
			    				$finfomime = finfo_open(FILEINFO_MIME,SYSTEMDIR."lib.3rd/magic");
			    			}
			    		    if($finfomime){
			    		        if(! $fileatt_type = finfo_file($finfomime,$imagetoattach)){
			    		            Kernel::logMsg("O ficheiro não foi reconhecido. Por favor corrija o problema.","Aviso");
			    		        }
			    		        finfo_close($finfomime);
			    		    }else{
			    		        Kernel::logMsg("Não foi possível a base de dados de mimetypes o que impedirá o reconhecimento automático do mime type do ficheiro. Por favor corrija o problema.","Aviso");
			    		    }
    			        }else{
    			        	Kernel::logMsg("O módulo fileinfo não está activo na sua configuração de PHP actual. Isto irá impedir que algumas das imagens do template sejam devidamente reconhecidas e convenientemente codificadas. Por favor corrija o problema.","Aviso");
    			        }

    			        $attachments.=    "--$boundary1\n".
    			                          "Content-Type: $fileatt_type; name=\"$fileatt_name\"\n".
    			                          "Content-Transfer-Encoding: base64\n".
    			                          "Content-ID: <image_$image.jpg@".PRODUCT_NAME."\n".
    			                          "Content-Disposition: inline; filename=\"$fileatt_name\"\n\n".
    			                          chunk_split($f_contents, 76, "\n")."\n\n";

    		            $tmpcontent = str_ireplace('[[IMAGETOATTACH]]'.$imagetoattach.'[[/IMAGETOATTACH]]',"cid:image_$image.jpg@".PRODUCT_NAME,$tmpcontent);
    				    if(stripos(Email::extractInnerElements($content),$fileatt_name)){
    				        $content = str_ireplace('[[IMAGETOATTACH]]'.Email::extractInnerElements($content).'[[/IMAGETOATTACH]]',"cid:image_$image.jpg@".PRODUCT_NAME,$content);
    				    }
    					$image++;
    			    }else{
    			        $tmpcontent = str_ireplace('[[IMAGETOATTACH]]'.$imagetoattach.'[[/IMAGETOATTACH]]',"null.jpg@".PRODUCT_NAME,$tmpcontent);
    			    }
    			    @fclose($handle);
    	        }while (stripos($tmpcontent,'[[IMAGETOATTACH]]'.Email::extractInnerElements($tmpcontent).'[[/IMAGETOATTACH]]'));

    	        $attachments .= "--$boundary1--\n";

    	        $message=     "--$boundary1\n".
    	                      "Content-Type: multipart/alternative;\n\tboundary=\"$boundary2\"\n\n";

    	        //pain text section
    		    $message.=    "--$boundary2\n".
    		                  "Content-Type: text/plain; charset = \"UTF-8\"\n".
    		                  "Content-Transfer-Encoding: 8bit\n\n\n".
    		                  Email::fixEndOfLine(strip_tags($content))."\n\n";

    	        //html text section
    		    $message.=    "--$boundary2\n".
    		                  "Content-Type: text/html; charset = \"UTF-8\"\n".
    		                  "Content-Transfer-Encoding: 8bit\n\n\n".
    		                  Email::fixEndOfLine(eregi_replace("\[\[WEBCONTENT\]\](.*)\[\[/WEBCONTENT\]\]",$content,$tmpcontent))."\n\n".
    		                  "--$boundary2--\n".
    		                  $attachments;

    	    }else{
    	        //plain text header
    		    $header.=    "Content-Transfer-Encoding: 8bit\n";
    	        $header.=    "Content-Type: text/plain; charset=\"UTF-8\"";

    	        $message = Email::fixEndOfLine($content);
    	    }

    	    //hide errors, thus the @
    	    $plainemails = extractEmailsFrom($from);
    	    $value = @mail($to, $subject, $message, $header, "-f".$plainemails[0]);

    	    return $value;
    	}

    	/**
		 * Extrai o conteúdo de uma dada tag no seguinte formato [[$tag]]content[[/$tag]]
		 *
		 * @access public
		 * @param string $content, com o conteúdo
    	 * @param string $tag, da qual devem ser extraídos os elementos
    	 * @return string com o conteúdo interno
		 *
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 2.0
		 */
    	public function extractInnerElements($content, $tag="IMAGETOATTACH"){
    	     return str_ireplace(array('[['.$tag.']]','[[/'.$tag.']]'),array('',''), substr($content,stripos($content,'[['.$tag.']]'),stripos($content,'[[/'.$tag.']]')-stripos($content,'[['.$tag.']]')));
    	}

    	/**
		 * Codifica um string para 8bit
		 *
		 * @access public
		 * @param string $string, a codificar
    	 * @return string codificada
		 *
		 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 2.0
		 */
    	public function fixEndOfLine($string){
    	    $string = str_replace("\r\n", "\n", $string);
    	    $string = str_replace("\r", "\n", $string);
    	    return $string.((substr($string, -(strlen("\n"))) != "\n")?"\n":"");
    	}

    	public function getTemplateFromDB($templateName='htmlmailtemplate', $idfield="id", $namefield="name", $templatefield="template", $table="mail_templates_tbl", $hostname=HNAME, $username=UNAME, $password=PWORD, $database=DBASE){
    		$template = "<html>\n	<head>\n		<title>[[SITE]]</title>\n		<style type=\"text/css\">\n            body {\n                margin: 0px;\n                background: #FFF;\n                font-family: Verdana, Times, serif;\n                font-size: 62.5%;\n				text-align: justify;\n            }\n            h1 {\n                font-size: 1.4em;\n                font-family: Verdana, Georgia, serif;\n                color: #556B2F;\n            }\n            h2 {\n                font-size: 1.2em;\n                font-family: Verdana, Georgia, serif;\n                font-weight: bold;\n                color: #556B2F;\n            }\n            h3 {\n                font-size: 1.0em;\n                font-weight: bold;\n            }\n            a:link {\n                color: #036C9C;\n                text-decoration: none;\n            }\n            a:hover {\n                color: #036C9C;\n                text-decoration: underline;\n            }\n			ul{\n				text-align: justify;\n			}\n		</style>\n	</head>\n	<body style=\"margin: 0 0 0 0;\">\n		<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"height: 100%;\">\n			<tr>\n				<td valign=\"top\" style=\"background-repeat: no-repeat; width: 100%; height: 120px; background-image: url(''[[IMAGETOATTACH]][[TEMPLATEIMAGESDIR]]banner.jpg[[/IMAGETOATTACH]]'')\">\n					<img src=\"[[IMAGETOATTACH]][[TEMPLATEIMAGESDIR]]banner.jpg[[/IMAGETOATTACH]]\" title=\"\" alt=\"\" width=\"750\" height=\"200\" style=\"border: 0px;\"/>\n				</td>\n			</tr>\n			<tr>\n				<td valign=\"top\">[[WEBCONTENT]]Conteúdo[[/WEBCONTENT]]</td>\n			</tr>\n		</table>\n	</body>\n</html>";

		    if(class_exists("MySQL")){
		        $conection =  new MySQL($hostname,$username,$password,$database);

		        $query = @$conection->doQuery("CREATE TABLE IF NOT EXISTS `$table` ( ".
		        							  " `$idfield` bigint(20) NOT NULL auto_increment COMMENT 'Chave primária da tabela', ".
		        							  "`$namefield` varchar(255) NOT NULL COMMENT 'Nome do template', ".
		        							  "`$templatefield` longtext NOT NULL COMMENT 'Conteúdo do template',  ".
		                                      "PRIMARY KEY  (`$idfield`), ".
		                                      "UNIQUE KEY `$namefield` (`$namefield`) ".
		                                      ") ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Tabela com os templates existentes para o envio de mensagens';".
		        							  "") or Kernel::logMsg("Ocorreu um erro (".mysql_error().")...");
    			$query = @$conection->doQuery("INSERT IGNORE INTO `$table` (`$namefield`, `$templatefield`) VALUES ('$templateName', '$template')") or Kernel::logMsg("Ocorreu um erro (".mysql_error().")...");

    			$query = @$conection->doQuery("	SELECT $templatefield FROM `$table`
												WHERE `$namefield` = '$templateName';");
				if(@mysql_num_rows($query)){
					while($templateData = mysql_fetch_assoc($query)){
						$template = $templateData[$templatefield];
					}
				}
		    }
		    return $template;
    	}

		public function sendEmailToUsers($from,$subject="",$message="",$to=NULL,$cc=NULL,$bcc=NULL,$template='htmlmailtemplate'){
			$email = ((isset($this))?$this:new Email(false, $template));

			$email->buildUserMailString($tousers,$to);
			$email->buildUserMailString($ccusers,$cc);
			$email->buildUserMailString($bccusers,$bcc);


		    if($email->sendEmail($from, $tousers, $ccusers, $bccusers, $subject, $message)){
		    	return true;
		    }
		    return false;
		}

		public function buildUserMailString(&$usersString="", $usersArray=array()){
			if(is_array($usersArray)){
				foreach($usersArray as $key=>$user){
					if($usersString!=""){
						$usersString.=", ";
					}
					$usersString.=$user['nome']." <".trim($user['email']).">";
				}
			}
			return $usersString;
		}
	}
?>