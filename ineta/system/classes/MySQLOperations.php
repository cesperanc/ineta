<?php
	/**
	 * MySQLOperations Class
	 * 
	 * Classe para facilitar a execução de algumas operações SQL
	 * 
	 * @example 
	 			constructor:
					$operations = new MySQLOperations(tabela:String[,propriedade_referencia:String,valor_propriedade_referencia:String]);

				para inserir um novo registo:
				e	$operations->insert(nome_da_propriedade:String,valor_da_propriedade:String[,tabela:String,nome_do_id:String]);
				ou	MySQLOperations::insert(nome_da_propriedade:String,valor_da_propriedade:String,tabela:String[,nome_do_id:String]);

				para obter uma propriedade:
				e	$operations->getProperty(nome_da_propriedade_a_obter:String[,tabela:String,propriedade_referencia:String,valor_propriedade_referencia:String]);
				ou	MySQLOperations::getProperty(nome_da_propriedade_a_obter:String,tabela:String,propriedade_referencia:String,valor_propriedade_referencia:String);

				para definir uma propriedade:
				e	$operations->setProperty(nome_da_propriedade_a_definir:String,valor_da_propriedade_a_definir:String[,tabela:String,propriedade_referencia:String,valor_propriedade_referencia:String]);
				ou	MySQLOperations::setProperty(nome_da_propriedade_a_definir:String,valor_da_propriedade_a_definir:String,tabela:String,propriedade_referencia:String,valor_propriedade_referencia:String);

				para eliminar um registo:
				e	$operations->delete([propriedade_referencia:String,valor_propriedade_referencia:String,tabela:String]);
				ou	MySQLOperations::delete(propriedade_referencia:String,valor_propriedade_referencia:String,tabela:String);

				para criar uma combobox no html para executar a ordenação de alguns ou de todos os elementos de uma tabela:
				e	$operations->getOrder(nome_da_ordem:String,nome_do_campo_titulo_da_tabela:String,tabela:String||NULL,propriedade_referencia:String||NULL,valor_propriedade_referencia||NULL:String,nome_do_campo_nivel:String,valor_do_campo_nivel:String);
				ou	MySQLOperations::getOrder(nome_da_ordem:String,nome_do_campo_titulo_da_tabela:String,tabela:String,propriedade_referencia:String,valor_propriedade_referencia:String,nome_do_campo_nivel:String,valor_do_campo_nivel:String);
	 * 
	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
	 * @version 5.0 
	 */
	class MySQLOperations{
		/**
		 * @access private
		 */
		private $__propertyName;
		/**
		 * @access private
		 */
		private $__propertyValue;
		/**
		 * @access private
		 */
		private $__table;
		/**
		 * @access private
		 */
		private $__mysql;
		
		/**
		 * Constructor
		 * @param String $table with the MySQL table name
		 * @param String $propertyName with the property name to check for
		 * @param String $propertyValue with the property value to check for
		 */
		function MySQLOperations($table,$propertyName="id",$propertyValue=NULL){
			if(!(function_exists("getKernelInstance") &&  $this->__mysql = getKernelInstance()->getSqlOperator())){
				$this->__mysql = new MySQL(HNAME,UNAME,PWORD,DBASE,SUPPORT_EMAILS);
			}
			$this->__table = $table;
			$this->__propertyName = $propertyName;
			$this->__propertyValue = $propertyValue;
		}
		
		/**
		 * Método para inserir um nova linha na tabela
		 * 
		 * @access public
		 * @param string $propertyName, com o(s) nome(s) do(s) campo(s) a ser(em) inserido(s)
		 * @param string $propertyValue, com o(s) valor(es) do(s) campo(s) a ser(em) inserido(s)
		 * @param string $table, com o nome da tabela a inserir o registo
		 * @param string $idName, com o nome do campo da chave primária
		 * @return o id do registo inserido ou false
		 */
		public function insert($propertyName="",$propertyValue="",$table=NULL,$idName="id"){
			if(is_null($table)){
				$table = $this->__table;
			}
			if(isset($this)){
				$msql = $this->__mysql;
			}else{
				$msql = new MySQL(HNAME,UNAME,PWORD,DBASE,SUPPORT_EMAILS);
			}
			$result = $msql->doQuery("INSERT INTO `".mysql_escape_string($table)."` (`".mysql_escape_string($propertyName)."`) VALUES (".$propertyValue.");");
			if($result && mysql_insert_id()){
				if(isset($this)){
					$this->__propertyValue = mysql_insert_id();
					if(!is_null($idName)&&$this->__propertyName!=$idName&&isset($idName)){
						$this->__propertyName = $idName;
					}
				}
				return mysql_insert_id();
			}else{
				if(isset($this)){
					$this->__propertyValue = false;
				}
				return false;
			}
		}
		
		/**
		 * Método para obter uma dada propriedade
		 * 
		 * @access public
		 * @param string $property, com o nome do campo a ser devolvido
		 * @param string $table, com o nome da tabela a efectuar a pesquisa
		 * @param string $itemname, com o nome do campo a procurar
		 * @param string $item, com o valor do campo a procurar
		 * @return o valor do campo ou false
		 */
		public function getProperty($property,$table=NULL,$itemname=NULL,$item=NULL){
			if((is_null($table)&&isset($this))){
				$table = $this->__table;
			}elseif(is_null($table)){
				return false;
			}
			if(is_null($itemname)&&isset($this)){
				$itemname = $this->__propertyName;
			}elseif(is_null($itemname)){
				return false;
			}
			if(is_null($item)&&isset($this)){
				$item = $this->__propertyValue;
			}elseif(is_null($item)){
				return false;
			}
			if(isset($this)){
				$msql = $this->__mysql;
			}else{
				$msql = new MySQL(HNAME,UNAME,PWORD,DBASE,SUPPORT_EMAILS);
			}
			
			if($result = $msql->doQuery("SELECT `".mysql_escape_string($property)."` FROM `".mysql_escape_string($table)."` WHERE `".mysql_escape_string($itemname)."`<=>".$item.";")){
				$linha = mysql_fetch_assoc($result);
				return $linha[$property];
			}
			return false;
		}
		
		/**
		 * 
		 * @param String $query
		 * @return Mixed with the result of the query, false 
		 */
		public function getQueryResult($query){
			if(!empty($query)):
				if(isset($this)){
					$msql = $this->__mysql;
				}else{
					if(!(function_exists("getKernelInstance") && $msql = getKernelInstance()->getSqlOperator())){
						$msql = new MySQL(HNAME,UNAME,PWORD,DBASE,SUPPORT_EMAILS);
					}
				}
				if($result = $msql->doQuery($query)){
					return $result;
				}
			endif;
			
			return false;
		}
		
		/**
		 * Método para definir uma dada propriedade
		 * 
		 * @access public
		 * @param string $property, com o nome do campo a ser definido
		 * @param string $value, com o valor do campo a ser definido
		 * @param string $table, com o nome da tabela a efectuar a pesquisa
		 * @param string $itemname, com o nome do campo a procurar
		 * @param string $item, com o valor do campo a procurar
		 * @return boolean true em caso de sucesso ou false
		 */
		public function setProperty($property,$value,$table=NULL,$itemname=NULL,$item=NULL){
			if(is_null($table)){
				$table = $this->__table;
			}
		    if(is_null($itemname)){
			    if(isset($this)){
				    $itemname = $this->__propertyName;
			    }else{
			        return false;
			    }
			}
			if(is_null($item)){
			    if(isset($this)){
				    $item = $this->__propertyValue;
			    }else{
			        return false;
			    }
			}
			if(isset($this)){
				$msql = $this->__mysql;
			}else{
				$msql = new MySQL(HNAME,UNAME,PWORD,DBASE,SUPPORT_EMAILS);
			}
			$result = $msql->doQuery("UPDATE `".mysql_escape_string($table)."` SET `".mysql_escape_string($property)."`=".$value." WHERE `".mysql_escape_string($itemname)."`<=>".$item.";");
			if($result){
				return $result;
			}
			return false;
		}
		
		/**
		 * Método para eliminar um registo de uma tabela
		 * 
		 * @access public
		 * @param string $property, com o nome do campo a procurar
		 * @param string $value, com o valor do campo a procurar
		 * @param string $table, com o nome da tabela a efectuar a pesquisa e a eliminar o registo
		 * @return boolean true em caso de sucesso ou false
		 */
		public function delete($property=NULL,$value=NULL,$table=NULL){
			if(is_null($table)&&isset($this)){
				$table = $this->__table;
			}elseif(is_null($table)){
				return false;
			}
			if(is_null($property)&&isset($this)){
				$property = $this->__propertyName;
			}elseif(is_null($property)){
				return false;
			}
			if(is_null($value)&&isset($this)){
				$value = $this->__propertyValue;
			}elseif(is_null($value)){
				return false;
			}
			if(isset($this)){
				$msql = $this->__mysql;
			}else{
				$msql = new MySQL(HNAME,UNAME,PWORD,DBASE,SUPPORT_EMAILS);
			}
			
			if($result = $msql->doQuery("DELETE FROM `".mysql_escape_string($table)."` WHERE `".mysql_escape_string($property)."`<=>".$value.";")){
				return $result;
			}
			return false;
		}
		
		/**
		 * Método para obter um array com os valores do campo enum de uma dada tabela
		 * 
		 * @access public
		 * @param string $field, com o nome do campo do tipo enum
		 * @param [string $table], com o nome da tabela
		 * @param [boolean $encodeInJSONFormat] true para codificar o array num formato que possa ser codificado pela função json_encode
		 * @return array com os valores do enum
		 */
    	public function getEnumValues($field=NULL, $table=NULL,$encodeInJSONFormat=false) {
    	    if(is_null($table)&&isset($this)){
				$table = $this->__table;
			}elseif(is_null($table)){
				return false;
			}
			if(is_null($field)&&isset($this)){
				$field = $this->__propertyName;
			}elseif(is_null($field)){
				return false;
			}
			if(isset($this)){
				$msql = $this->__mysql;
			}else{
				$msql = new MySQL(HNAME,UNAME,PWORD,DBASE,SUPPORT_EMAILS);
			}
			
			$enum_array = array();
			if($results = $msql->doQuery("SHOW COLUMNS FROM `".mysql_escape_string($table)."` LIKE '".mysql_escape_string($field)."';")){
	            $row = mysql_fetch_row($results);
	            
	            preg_match_all('/\'(.*?)\'/', $row[1], $enum_array);
	            if(!empty($enum_array[1])) {
	                // reordena o array para a ordem do enum original
	                foreach($enum_array[1] as $mkey => $mval) $enum_fields[$mkey+1] = $mval;
	                if($encodeInJSONFormat){
	                    $js_enum_fields = array();
	                    foreach($enum_fields as $key=>$value){
	                        $js_enum_fields[] = array($key,$value);
	                    }
	                    return $js_enum_fields;
	                }
	                return $enum_fields;
	                
	            }
			}
            return array();
        }
	}
?>