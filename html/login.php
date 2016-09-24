<?php
	include '../data/data.php';
	session_start();				/*Inizia una sessione*/
	$data = file_get_contents('php://input'); /*file_get_contents:data una stringa, recupera i parametri di query. In questo caso viene presa la stringa inviata come dato dal JS*/
	$result = json_decode($data);
	$con=mysqli_connect("localhost",$DATA['DB_USERNAME'],$DATA['DB_PASSWORD'],$DATA['DB_NAME']);
	if ($con==false)
	{
		$arr=array('result'=>"errore nella connessione al database");
		$ris=json_encode($arr);
		echo $ris;
		die;
	}
	else
	{
	$str="SELECT id,username,reputation,assiduity,last_lat,last_lng,superuser FROM ".$DATA['USER_TABLE']." WHERE BINARY username='".$result->username."' AND BINARY password='".$result->password."';";
	$exists= mysqli_query($con,$str);
	if (!($exists))
	{
		$arr=array('result'=>"errore nella ricerca all'interno del database");
		$ris=json_encode(arr);
		echo $ris;
		die;
	}
	$res=mysqli_num_rows($exists);
	if ($res==1)
	{
		$ID=mysqli_fetch_array($exists);
		$_SESSION["superuser"]=$ID['superuser'];
		$_SESSION["rep"]=$ID['reputation'];
		$_SESSION["ass"]=$ID['assiduity'];
		$_SESSION["last_lat"]=$ID['last_lat'];
		$_SESSION["last_lng"]=$ID['last_lng'];
		$_SESSION["ID"] = $ID['id'];
		$_SESSION["username"]=$result->username;
		$arr = array('result'=>"login effettuato con successo");
		$ris=json_encode($arr);
		echo $ris;
	}
	else
	{
		$arr= array('result'=>"Utente non trovato");
		$ris=json_encode($arr);
		echo $ris;
	}
	
	}
	mysqli_close($con)
?>
