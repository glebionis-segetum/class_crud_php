<?php

	include("class_crud.php");

	//Amosa erros e warnings
	error_reporting(E_ALL);
	ini_set("display_errors", 1);
	
	//Conexión, obxecto da clase PDO
	$c = new PDO("mysql:host=localhost; dbname=shop; charset=utf8mb4", "root", ""); //dsn, user, pass
	
	//Obxecto da clase Crud para traballar sobre a táboa admins
	$pampullo = new Crud($c, "admins");
	
	//Inicializamos un array cos valores que queremos impactar na táboa admins (correspondéndose aos campos name, email e password da mesma)
	//Isto farase a través do front, polo que só está aquí como proba
	$nome = "Pampullo";
	$correo = "pampullo@mail.com";
	$contrasinal = "1234";
	$val = array($nome, $apelido, $contrasinal);
	$valores = json_encode($val);
	
	//Insert admin pampullo VALUES array
	$pampullo->create_tuple($valores);


?>
