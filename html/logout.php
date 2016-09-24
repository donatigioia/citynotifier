<?php
session_start();
unset($_SESSION['ID']);
	$arr=array('result'=>"logout effettuato con successo");
	$ris=json_encode($arr);
	echo $ris;
?>