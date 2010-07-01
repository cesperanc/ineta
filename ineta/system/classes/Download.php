<?php
	/**
	 * Download Class
	 * 
	 * 
	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
	 * @version 1.0 
	 */

	class Download{
		
		/**
		 * Constructor da classe
		 * 
		 */
		function Download(){
			
		}

		/**
		 * @method para construir um ficheiro a partir de um campo da tabela MySQL
		 * 
		 * @param string $table, com o nome da tabela de origem
		 * @param string $fileDataField, com o nome do campo de dados na tabela de origem
		 * @param string $fileTypeField, com o nome do campo do tipo de ficheiro na tabela de origem
		 * @param string $keyField, com o nome do campo a pesquisar
		 * @param string $keyValue, com o valor do campo a comparar com $keyField
		 * @param [string $database], com o nome da base de dados da tabela de origem
		 * @return boolean true ou false com o resultado da operação
		 * 
    	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
    	 * @version 2.0
    	 * 
		 */
    	public function writeMySqlImage($table=NULL,$fileDataField=NULL,$fileTypeField=NULL,$keyField=NULL,$keyValue=NULL,$database=DBASE){
    		if(!headers_sent()){
    		    $imgdata = Download::getMySqlImageRawData($table,$fileDataField,$fileTypeField,$keyField,$keyValue,$database);
    		    if($imgdata["success"]){
        			Header ("Content-type: {$imgdata[$fileTypeField]}");
        			print($imgdata[$fileDataField]);
        			return true;
    		    }
    		}
    		return false;
    	}
    	
    	public function getMySqlImageRawData($table=NULL,$fileDataField=NULL,$fileTypeField=NULL,$keyField=NULL,$keyValue=NULL,$database=DBASE){
    	    if(!is_null($table) && !is_null($fileDataField) && !is_null($fileTypeField) && !is_null($keyField) && !is_null($keyValue) && class_exists("MySQL")){
    			$result = MySQL::doQuery("SELECT `$fileDataField`, `$fileTypeField` FROM `".$database."`.`$table` ".
    						   			 "WHERE `$table`.`$keyField` = '$keyValue' LIMIT 1 ;");
    			if(@mysql_num_rows($result)>0) {
                    $row = @mysql_fetch_array ($result);
                    $img = array("success"=>true,"$fileDataField"=>$row[$fileDataField],"$fileTypeField"=>$row[$fileTypeField]);
                    return $img;
    			}
    		}
    		return false;
    	}
    	
    	public function getRawImageAttributes($data=false){
            $imagesize = false;
            
            if($data && $tempfile = @tempnam(sys_get_temp_dir(),PRODUCT_NAME."_img")){
                $temphandle = @fopen($tempfile, "w");
                @fwrite($temphandle, $data);
                @fclose($temphandle);
                $imagesizetmp = @getimagesize($tempfile);
                
                $imagesize = array();
                $imagesize["width"] = $imagesizetmp[0];
                $imagesize["height"] = $imagesizetmp[1];
                $imagesize["type"] = $imagesizetmp[2];
                $imagesize["attr"] = $imagesizetmp[3];
                $imagesize["bits"] = $imagesizetmp["bits"];
                $imagesize["channels"] = $imagesizetmp["channels"];
                $imagesize["mime"] = $imagesizetmp["mime"];
                
                @unlink($tempfile);
            }
            return $imagesize;
        }
    	
	}
?>
