<?php
	/**
	 * Upload Class
	 * 
	 * @example $upload = new Upload(ficheiros); //onde ficheiros é o nome do(s) input(s) do tipo file com o nome ficheiros[]
				<input type="file" name="ficheiros[]">
				<input type="file" name="ficheiros[]">
				...
	 * 
	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
	 * @version 3.0 
	 */

	class Upload{
		/**
		 * @access private
		 */
		private $_files_array;
		private $_files_uploaded;
		
		/**
		 * Constructor da classe
		 * 
		 * @param [string $files], com o nome do input do tipo file no formato vector (ex.: imagens[])
		 */
		function Upload($files=NULL){
			$this->_files_array = $_FILES[$files];
			$this->_files_uploaded = array();
		}
		
		/**
		 * @method para retirar os caracteres não alpha-numéricos de uma string (utilizado para limpar o nome dos ficheiros)
		 * 
		 * @param string $name, a rectificar
		 * @return string com os caracteres válidos
		 * 
    	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 2.0
		 */
		private function _clearText($name){
			$imagem = stripslashes($name);
			$filename = basename($imagem);
			if(strrpos($filename,".")===false){
				$extension = ".null";
			}else{
				$extension = substr($filename,strrpos($filename,"."));
				$filename = basename($imagem, $extension);
			}
			$filename = strtolower(preg_replace("/[^a-zA-Z0-9]/", "",$filename)).$extension;
			return $filename;
		}
		
		/**
		 * @method para redimensionar as imagens, alterar o tipo do ficheiro e inserir um logotipo
		 * 
		 * @param [integer $maxSideSize], com o tamanho mázimo a utilizar para a imagem
		 * @param [string $imageType], com o tipo de imagem final pretendida (ex: image/jpeg)
		 * @param [string $logo], com o endereço da imagem da utilizar como logotipo
		 * @param [string $image], com o endereço da imagem a editar
		 * @param [boolean $cancelOnError], caso a edição de imagens deva ser cancelada ao encontrar um erro
		 * 
    	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 3.0
		 */
		public function editImages($maxSideSize=600,$imageType=NULL,$logo=NULL,$image=NULL,$cancelOnError=false){
			if(isset($this)&&isset($this->_files_array)){
				$files_array = $this->_files_array;
			}else{
				$files_array = $_FILES[$image];
			}
			for($a=0;$a<count($files_array['tmp_name']);$a++){
				if(is_readable($files_array['tmp_name'][$a])){
					if(is_null($imageType)){
						$imageType = $this->_files_array['type'][$a];
					}
					if (function_exists('finfo_file')){
						if(!$finfomime = finfo_open(FILEINFO_MIME)){
		    				$finfomime = finfo_open(FILEINFO_MIME,SYSTEMDIR."lib.3rd/magic");
		    			}
    		            switch(finfo_file($finfomime,$files_array['tmp_name'][$a])){
    		            	case "image/x-png":{
    		            	if(!$image = @imagecreatefrompng($files_array['tmp_name'][$a])){
									Kernel::logMsg("A imagem foi detectada como sendo do tipo PNG, mas não foi possível trabalhar a imagem neste formato.");
									if($cancelOnError) return false;
								}
								break;
							}
							case "image/gif":{
								if(!$image = @imagecreatefromgif($files_array['tmp_name'][$a])){
									Kernel::logMsg("A imagem foi detectada como sendo do tipo GIF, mas não foi possível trabalhar a imagem neste formato.");
									if($cancelOnError) return false;
								}
								break;
							}
    		            	case "image/jpeg":{
								if(!$image = @imagecreatefromjpeg($files_array['tmp_name'][$a])){
									Kernel::logMsg("A imagem foi detectada como sendo do tipo JPEG, mas não foi possível trabalhar a imagem neste formato.");
									if($cancelOnError) return false;
								}
								break;
							}
							case "image/bmp":{
							if(!$image = @imagecreatefromwbmp($files_array['tmp_name'][$a])){
									Kernel::logMsg("A imagem foi detectada como sendo do tipo BMP, mas não foi possível trabalhar a imagem neste formato.");
									if($cancelOnError) return false;
								}
								break;
							}
							default:{
								if(!$image = @imagecreatefromstring(file_get_contents($files_array['tmp_name'][$a]))){
									Kernel::logMsg("Impossivel assinar o ficheiro (formato não suportado)");
									if($cancelOnError) return false;
								}
							} 
    		            	
    		            }
    		        }else{
    		        	Kernel::logMsg("O módulo fileinfo não está activo na sua configuração de PHP actual. Isto irá impedir que algumas das imagens do template sejam devidamente reconhecidas e convenientemente codificadas. Por favor corrija o problema.","Aviso");
    		        	if(!$image = @imagecreatefromgif($files_array['tmp_name'][$a])){
							if(!$image = @imagecreatefromjpeg($files_array['tmp_name'][$a])){
								if(!$image = @imagecreatefrompng($files_array['tmp_name'][$a])){
									if(!@imagecreatefromwbmp($files_array['tmp_name'][$a])){
										if(!$image = @imagecreatefromstring(file_get_contents($files_array['tmp_name'][$a]))){
											Kernel::logMsg("Impossivel assinar o ficheiro (formato não suportado)");
											if($cancelOnError) return false;
										}
									}
    		        			}
							}
    		        	}
    		        }
					
					//insere o logo e redimensiona a imagem
					if ($image){
						@list($width, $height, $type, $attr) = @getimagesize($files_array['tmp_name'][$a]);
						if($logo!=NULL){
							@list($lwidth, $lheight, $ltype, $lattr) = @getimagesize($logo);
							$llogo = @imagecreatefrompng ($logo);
						}
						if($width>=$height){
							$maxFindedSize = $width;
							$largura = $maxSideSize;
							$altura = $largura*$height/$width;
						}else{
							$maxFindedSize = $height;
							$altura = $maxSideSize;
							$largura = $altura*$width/$height;
						}
						if ($maxFindedSize>$maxSideSize){
							$imgr = @imagecreatetruecolor($largura,$altura);
							@imagecopyresampled($imgr,$image,0,0,0,0,$largura,$altura,$width,$height);
							$image = $imgr;
							if($logo){
								$logo = @imagecreatetruecolor($largura,$lheight);
								@imagefilledrectangle($logo, 0, 0, $largura, $altura, @imagecolorallocate($logo,0x31,0x73,0xAC));
								@imagecopy($logo,$llogo,$largura-$lwidth,0,0,0,$lwidth,$lheight);
								@imagecopymerge($image,$logo, 0, $altura-$lheight,0,0,$largura,$lheight,60);
							}
						}else{
							if($width>=$lwidth&&$height>=$lheight&&isset($logo)){
								$logo = @imagecreatetruecolor($width,$lheight);
								@imagefilledrectangle($logo, 0, 0, $width, $lheight, @imagecolorallocate($logo,0x31,0x73,0xAC));
								@imagecopy($logo,$llogo,$width-$lwidth,0,0,0,$lwidth,$lheight);
								@imagecopymerge($image,$logo, 0, $height-$lheight,0,0,$width,$lheight,60);
							}
						}
						//grava o resultado
						switch($imageType){
							case "image/x-png":{
								if(!@imagepng($image,$files_array['tmp_name'][$a]) && $cancelOnError){
									return false;
								}
								break;
							}
							case "image/gif":{
								if(!@imagegif($image,$files_array['tmp_name'][$a]) && $cancelOnError){
									return false;
								}
								break;
							}
							case "image/bmp":{
								if(!@imagewbmp($image,$files_array['tmp_name'][$a],@imagecolorallocate($image,255,255,255)) && $cancelOnError){
									return false;
								}
								break;
							}
							default:{
								if(!@imagejpeg($image,$files_array['tmp_name'][$a],80) && $cancelOnError){
									return false;
								}
							}
						}
					}
				}
			}
			return true;
		}
		
		/**
		 * @method para mover os ficheiros enviados para os respectivos directórios de destino
		 * 
		 * @param [string $dir], com o endereço do directório final para colocar os ficheiros transferidos
		 * @param [string $prefix], com o prefixo a colocar no inicio do nome de cada um dos ficheiros
		 * @return array com os ficheiros transferidos
		 * 
    	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 2.0
		 */
		public function copyFilesToDir($dir="../temp/",$prefix=""){
			if(isset($this)&&isset($this->_files_array)){
				for($a=0;$a<count($this->_files_array['name']);$a++){
					$this->copyFileToDir($this->_files_array['name'][$a],$dir,$prefix);
				}
			}else{
				Kernel::logMsg("Nada para fazer upload... Parece que se esqueceram de alguma coisa...");
			}
			return $this->_files_uploaded;
		}
		
		/**
		 * @method para mover o ficheiro transferido especificado para o respectivo directório de destino
		 * 
		 * @param [string $tmpfilename], com o nome temporário do ficheiro
		 * @param [string $dir], com o endereço do directório final para colocar o ficheiro transferido
		 * @param [string $prefix], com o prefixo a colocar no inicio do nome do ficheiro
		 * @return string com o nome final do ficheiro ou boolean false em caso de erro
		 * 
    	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 2.0
		 */
		public function copyFileToDir($tmpfilename=NULL,$dir="../temp/",$prefix=""){
			if(!is_null($tmpfilename)&&is_readable($tmpfilename)){
				for($a=0;$a<count($this->_files_array['tmp_name']);$a++){
					if($this->_files_array['tmp_name'][$a]==$tmpfilename){
						$filename=$this->_files_array['name'][$a];
						break;
					}
				}
				$filename = $prefix.$this->_clearText($filename);
				$extension = "";
				if (function_exists('finfo_file')){
					if(!$finfomime = finfo_open(FILEINFO_MIME)){
	    				$finfomime = finfo_open(FILEINFO_MIME,SYSTEMDIR."lib.3rd/magic");
	    			}
    	            switch(finfo_file($finfomime,$tmpfilename)){
    	            	case "image/x-png":{
    	            		$extension = ".png";
							break;
						}
						case "image/gif":{
							$extension = ".gif";
							break;
						}
    	            	case "image/jpeg":{
							$extension = ".jpg";
							break;
						}
						case "image/bmp":{
							$extension = ".bmp";
							break;
						}
						default:{}
    	            }
				}else{
					Kernel::logMsg("O módulo fileinfo não está activo na sua configuração de PHP actual. Isto irá impedir que algumas das imagens do template sejam devidamente reconhecidas e convenientemente codificadas. Por favor corrija o problema.","Aviso");
				}
				$filename = $prefix.$this->_clearText($filename).$extension;
				
				if(@move_uploaded_file($tmpfilename,$dir.$filename)){
					array_push($this->_files_uploaded,$filename);
					return $filename;
				}else{
					if(is_readable($tmpfilename)){
						Kernel::logMsg("Ocorreu um erro ao transferir o ficheiro ".$tmpfilename."");
					}else{
						Kernel::logMsg("Nada para fazer upload... Parece que se esqueceram de alguma coisa...");
					}
				}
			}
			return false;
		}
		
		/**
		 * @method para mover os ficheiros transferidos especificados para a tabela MySQL de destino
		 * 
		 * @param string $table, com o nome da data de destino
		 * @param string $fileDataField, com o nome do campo de dados na tabela destino
		 * @param string $fileTypeField, com o nome do campo do tipo de ficheiro na tabela destino
		 * @param string $keyField, com o nome do campo a pesquisar para actualizar o registo
		 * @param string $keyValue, com o valor do campo a comparar com $keyField
		 * @return boolean true ou false com o resultado da operação
		 * 
    	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 2.0
		 */
		public function copyFilesToMySqlTable($table=NULL,$fileDataField=NULL,$fileTypeField=NULL,$keyField=NULL,$keyValue=NULL){
			if(isset($this)&&isset($this->_files_array)){
				for($a=0;$a<count($this->_files_array['name']);$a++){
					$this->copyFileToMySqlTable($this->_files_array['tmp_name'][$a],$table,$fileDataField,$fileTypeField,$keyField,$keyValue);
				}
			}else{
				Kernel::logMsg("Nada para fazer upload... Parece que se esqueceram de alguma coisa...");
			}
			return $this->_files_uploaded;
		}
		
		/**
		 * @method para mover o ficheiro transferido especificado para a tabela MySQL de destino
		 * 
		 * @param string $tmpfilename, com o nome temporário do ficheiro
		 * @param string $table, com o nome da tabela de destino
		 * @param string $fileDataField, com o nome do campo de dados na tabela destino
		 * @param string $fileTypeField, com o nome do campo do tipo de ficheiro na tabela destino
		 * @param string $keyField, com o nome do campo a pesquisar para actualizar o registo
		 * @param string $keyValue, com o valor do campo a comparar com $keyField
		 * @return boolean true ou false com o resultado da operação
		 * 
    	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 2.0
		 */
		public function copyFileToMySqlTable($tmpfilename=NULL,$table=NULL,$fileDataField=NULL,$fileTypeField=NULL,$keyField=NULL,$keyValue=NULL){
			if(!is_null($tmpfilename) && is_readable($tmpfilename)){
				if(!is_null($table) && !is_null($fileDataField) && !is_null($fileTypeField) && !is_null($keyField) && !is_null($keyValue) && class_exists("MySQL")){
					for($a=0;$a<count($this->_files_array['tmp_name']);$a++){
						if($this->_files_array['tmp_name'][$a]==$tmpfilename){
							$filetype=$this->_files_array['type'][$a];
							
						    if (function_exists('finfo_file')){
							    if(!$finfomime = finfo_open(FILEINFO_MIME)){
				    				$finfomime = finfo_open(FILEINFO_MIME,SYSTEMDIR."lib.3rd/magic");
				    			}
				    		    if($finfomime){
				    		        if($type_new = finfo_file($finfomime,$tmpfilename)){
				    		            $filetype = $type_new;
				    		        }else{
				    		            Kernel::logMsg("O ficheiro não foi reconhecido. Por favor corrija o problema.","Aviso");
				    		        }
				    		        finfo_close($finfomime);
				    		    }else{
				    		        Kernel::logMsg("Não foi possível a base de dados de mimetypes o que impedirá o reconhecimento automático do mime type do ficheiro. Por favor corrija o problema.","Aviso");
				    		    }
                    		}else{
                    			Kernel::logMsg("O módulo fileinfo não está activo na sua configuração de PHP actual. Isto irá impedir que algumas das imagens do template sejam devidamente reconhecidas e convenientemente codificadas. Por favor corrija o problema.","Aviso");
                    			$filetype=$this->_files_array['type'][$a];
                    		}
							break;
						}
					}
					MySQL::doQuery("UPDATE `".DBASE."`.`$table` SET ".
								  		"`$fileDataField` = '".addslashes(fread(fopen($tmpfilename, "r"),filesize($tmpfilename)))."', ".
										"`$fileTypeField` = '$filetype' ".
								   "WHERE `$table`.`$keyField` = '$keyValue' LIMIT 1 ;");
					
					array_push($this->_files_uploaded,$tmpfilename);
					@delFile($tmpfilename);
					return true;
				}
			}
			return false;
		}
	}
?>
