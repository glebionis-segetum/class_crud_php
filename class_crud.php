<?php

	class Crud{
		/**************************************************************************************
		* Atributos: declaración                                                              *
		**************************************************************************************/
		private $connection;		//Conexión é un obxecto dqa clase PDO
		private $table;			//O nome da táboa sobre a que faremos o crud
		private $array_fields;		//Os campos da táboa nun array. Formato: [(campo)(campo)]
		
		//Para a sentencia sql preparada (usando a clase PDO), campos e valores
		private $string_fields;		//Necesitámolo para facer a sentencia sql preparada Formato: "campo,campo"
		private $string_values;		//Non son os valores do insert senón a parte correspondente a values da sentencia preparada con PDO. Formato "?,?"
		
		//private $string_values_dicionario;	//Necesitámolo no VALUES da sentencia sql preparada se facemos o enlace por dicionario. Formato: ":nomecampo, :nomecampo"
		//Facemos o enlace por index, polo que substituímos esta variable pola anterior ($string_values)
		
		//Para o enlace por dicionario de campos e valores (co método bindParam() da clase PDO)
		//private $array_fields_bind;	//Necesitámolo como 1º parámetro de bindParam() para o enlace por dicionario. Formato: [(':campo')(':campo')]
		//No lugar desta variable usaremos o index do campo ao que lle enlazar o valor (1,2,3...) !Importante, o index do bindParam() comeza en 1, non en 0
		
		//Array que contén o tipo de dato de cada campo da táboa
		private $array_fields_type;
		
		/**************************************************************************************
		* Construtor                                                                          *
		**************************************************************************************/
		public function __construct($connection, $table){
			$this->connection = $connection;
			$this->table = $table;
			$this->get_fields();
		}
		
		/**************************************************************************************
		* Métodos                                                                            *
		**************************************************************************************/
		//Con esta función inicializamos todos os atributos da clase. Isto faise no momento no que construímos un obxecto da mesma
		private function get_fields(){
			//Inicializamos baleiros todos os campos, pero marcando xa o tipo de dato
			$this->array_fields = array();
			$this->string_fields = "";
			//$this->string_values_dicionario = "";
			$this->string_values = "";
			//$this->array_fields_bind = array();
			$this->array_fields_type = array();
			
			//Esta consulta non necesita facerse de forma preparada
			$sql = "SHOW COLUMNS FROM ".$this->table;
			$fields = $this->connection->query($sql);
			
			//A consulta dos campos da táboa permítenos asignarlle un valor aos atributos anteriores
			foreach($fields as $index => $field){ 
				//Inicializamos os strings e os arrays de campos e valores evitando a id
				if($index != 0){
					//String dos campos separados por comas. Formato: "campo, campo" !Desta forma o string remata en ,
					$this->string_fields .= $field["Field"].",";
					//String do VALUES da sentencia sql preparada. Formato: "?,?". !Un ? por cada campo. Esta é a forma de enlace por index !Desta forma o string remata en ,
					$this->string_values .= "?,";
					//Array dos campos. Formato: [(campo)(campo)]
					array_push($this->array_fields, $field["Field"]);
					//Array dos campos para o primeiro parámetro de bindParam(). Formato: [(':campo')(':campo')]. Isto só o necesitamos no enlace por dicionario
					//array_push($this->array_fields_bind, "':".$field["Field"]."'");

					//Sacamos o tipo de dato de cada campo da táboa para crear un array con eles e usalo como 3º parámetro de bindParam()
					//Cun var_dump() podemos ver que o tipo de dato se amosa como int(3), por exemplo. De aí o explode pola (
					$type = explode("(", $field["Type"]);
					
					//Discriminamos segundo o tipo de dato para adaptalo á sintaxe do 3º parámetro de bindParam() (PDO::PARAM_INT/STR/BOOL)
					switch($type[0]){
						case "int":
							$type = "INT";
							break;
						case "varchar":
							$type = "STR";
							break;
						case "tinyint":
							$type = "BOOL";
							break;
						default:
							echo "erro no switch";
					}
					//O array resultante
					array_push($this->array_fields_type, $type);
				}
			}
			//Eliminación das comas finais dos dous strings
			$this->string_fields = substr($this->string_fields, 0, -1);
			$this->string_values = substr($this->string_values, 0, -1);
		}
		
		//INSERT
		public function create_tuple($val){
			try{
				//Os datos que recibimos para impactar na táboa (VALUES, en array para o bindParam()
				$array_values = json_decode($val);	//Decodificamos o parámetro values

				//Hash do contrasinal, se o hai
				for($i=0;$i<count($this->array_fields);$i++){
					if($this->array_fields[$i] == "password"){
						$array_values[$i] = password_hash($array_values[$i], PASSWORD_DEFAULT);
					}
				}
				
				//SINTAXE das sentencias sql preparadas e dos enlaces entre compos e valores, tanto por dicionario como poor index
				
				/* DICIONARIO----------------------------------------------
				$sql = "INSERT INTO ".$this->table." (campo1,campo2,campo3) VALUES (:valor1,:valor2,:valor3)";
				
				$stmt = $pdo->prepare($sql);
				
				$stmt->bindParam(':campo1', $valor1, PDO::PARAM_INT);
				$stmt->bindParam(':campo2', $valor2, PDO::PARAM_STR);
				$stmt->bindParam(':campo2', $valor2, PDO::PARAM_BOOL);
				
				$stmt->execute();
				---------------------------------------------------------*/
				
				/* INDEX--------------------------------------------------
				$sql = "INSERT INTO ".$this->table." (campo1,campo2,campo3) VALUES (?,?,?)";
				
				$stmt = $pdo->prepare($sql);
				
				$stmt->bindParam(1, $valor1, PDO::PARAM_INT);
				$stmt->bindParam(2, $valor2, PDO::PARAM_STR);
				$stmt->bindParam(3, $valor2, PDO::PARAM_BOOL);
				
				$stmt->execute();
				---------------------------------------------------------*/

				//bindParam() enlaza os parámetros que lle estamos a dar (campo e valor)
				
				$sql = "INSERT INTO ".$this->table." (".$this->string_fields.") VALUES (".$this->string_values.")";
				$stmt = $this->connection->prepare($sql);		//Preparas a sentencia sql anterior nesta conexión
				
				//Bucle para chamar a bindParam() tantas veces como campos haxa na táboa
				for($i=0;$i<count($this->array_fields);$i++){
					
					$j=$i+1; //índice do bindParam(), xa que non grava por dicionario gravamos por índice
					
					//Enlace por dicionario non funciona !por que????
					//$stmt->bindParam($this->array_fields_bind[$i], $array_values[$i]);
					
					//bindParam() sen o 3º parámetro de tipo de dato é STR por defecto, o cal non é o óptimo
					//$stmt->bindParam($j, $array_values[$i]);
					
					//Esta forma de engadir STR / INT / BOOL non funciona
					//$stmt->bindParam($j, $array_values[$i], PDO::PARAM_$this->array_fields_type[$i]);
					
					//Segundo o tipo de dato do campo sexa INT, STR ou BOOL usamos un (3º) parámetro ou outro ao chamar a bindParam() co noso obxecto $stmt, que vén a ser a conexión coa sentencia sql preparada
					switch($this->array_fields_type[$i]){
						case "INT":
							$stmt->bindParam($j, $array_values[$i], PDO::PARAM_INT);
							break;
						case "STR":
							$stmt->bindParam($j, $array_values[$i], PDO::PARAM_STR);
							break;
						case "BOOL":
							$stmt->bindParam($j, $array_values[$i], PDO::PARAM_BOOL);
							break;
						default:
							echo "erro no switch";
					}
				}
				//Execución !Devolve true ou false
				$stmt->execute();
			}catch(PDOException $e){
				echo "Erro: ".$e->getMessage();
			}
		}
		public function read_tuple(){
			try{
				$sql = "SELECT * FROM ".$this->table;
				$stmt = $this->connection->prepare($sql);
				$stmt->execute();								//Devolve true ou false
				return $stmt->fetchAll(PDO::FETCH_ASSOC);		//Devolve un array asociativo / diccionario / hash map (coa posición e o nome do campo)
			}catch(PDOException $e){
				echo "Erro: ".$e->getMessage();
			}
		}
		public function update_tuple(){
			
		}
		public function delete_tuple(){
			$sql = "DELETE FROM ".$this->table." WHERE ".$this->array_fields[0]." = :id";
			$stmt = $this->connection->prepare($sql);
			$stmt->bindParam(":id", $id, PDO::PARAM_INT);
			return $stmt->execute();
		}
		
		
		
		/**************************************************************************************
		* Getters                                                                         *
		**************************************************************************************/

		public function get_connection(){
			return $this->connection;
		}
		
		public function get_table(){
			return $this->table;
		}

		public function get_array_fields(){
			return $this->array_fields;
		}
		
		public function get_string_fields(){
			return $this->string_fields;
		}
		
		public function get_string_values(){
			return $this->string_values;
		}

		public function get_array_fields_type(){
			return $this->array_fields_type;
		}

		public function get_string_fields_bind(){
			return $this->string_fields_bind;
		}

		public function get_array_fields_bind(){
			return $this->array_fields_bind;
		}
	}
?>
