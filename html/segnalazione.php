<?php
	include 'util.php';//file creato da stefano, se non sbaglio serve per la funzione __json_encode
	include 'aggiornamento.php';
	include '../data/data.php';
	session_start(); //inizio della nuava sessione
	if (!($_SERVER['REQUEST_METHOD'] === 'POST')) {
	        header('X-PHP-Response-Code: 405', true, 405);	
        	echo "405: Method not allowed.";
        	exit;
	}
	$time=time(); //memorizzo il tempo attuale
	$data=file_get_contents('php://input');//recupera i dati della richiesta POST
	$result=json_decode($data,FALSE);//costruisce un array a partire dal json della richiesta
	
	if (!POST_check($result))
	{
		header('X-PHP-Response-Code: 406', true, 406);
		echo "Errore 406: Parametri della richiesta non compatibili";
		die;
	}
	$con = mysqli_connect("localhost",$DATA['DB_USERNAME'],$DATA['DB_PASSWORD'],$DATA['DB_NAME']);//creo la connessione al server
	if (!$con)
	{
		header('X-PHP-Response-Code: 500', true, 500);
		echo "500: Internal Server Error10";
		die;
	}	
	//recupero le informazioni dell'utente dal database 
	$str="SELECT * FROM ".$DATA['USER_TABLE']."  WHERE username='".$_SESSION['username']."';";
	$str=mysqli_query($con,$str);
	if (!$str)
	{
		header('X-PHP-Response-Code: 500', true, 500);
		echo "500: Internal Server Error11";
		die;
	}
	$risp=mysqli_fetch_array($str);
	$u_reputation=$risp['reputation'];
	$u_assiduity=$risp['assiduity'];
	//trovo il raggio di validità relativo all'evento arrivato e calcolo latitudine e longitudine massime e minime
	$str="SELECT * FROM ".$DATA['SUBTYPE_TABLE']." WHERE subtype='".$result->type->subtype."';";
	$str=mysqli_query($con, $str);
	if (!$str)
	{
		header('X-PHP-Response-Code: 500', true, 500);
		echo "500: Internal Server Error12";
		die;
	}
	$risp=mysqli_fetch_array($str);
	$raggio=(float)$risp['metri'];
	//controllo se nel database ci sono degli eventi a cui la richiesta arrivata può essere accorpata
	$str="SELECT * FROM ".$DATA['EVENT_TABLE']." WHERE type='".$result->type->type."' AND subtype='".$result->type->subtype."' AND status<>'archived';";
	$str=mysqli_query($con, $str);
	if (!$str)
	{
		header('X-PHP-Response-Code: 500', true, 500);
		echo "500: Internal Server Error13";
		die;
	}
	$t_id=NULL;
	$status_db=NULL;
	$min_dis=NULL;
	while($risp=mysqli_fetch_array($str))
	{
		$str1="SELECT * FROM ".$DATA['NOTIFY_TABLE']." WHERE event_id=".$risp['event_id'].";";
		$str1=mysqli_query($con, $str1);
		if (!$str1)
		{
			header('X-PHP-Response-Code: 500', true, 500);
			echo "500: Internal Server Error14";
			die;
		}
		while($risp1=mysqli_fetch_array($str1))
		{
			$new_dist=distanza($result->lat,$result->lng,$risp1['latitude'],$risp1['longitude']);
			if($new_dist<=$raggio)
			{
				if(!$min_dis or $new_dist<$min_dis)
				{
					$min_dis=$new_dist;
					$t_id=$risp['event_id'];
					$status_db=$risp['status'];
				}
			}
		}
	}
	if($t_id)
		if(_skeptical($status_db, 'open'))
			$event_id=gest_skeptical($t_id, 'open', $result->lat, $result->lng, $result->description, $DATA );
		else
			$event_id=aggiorna_ev($t_id, 'open', $result->lat, $result->lng, $result->description, $DATA);
	else
		$event_id=aggiungi($_SESSION['username'], $result->type->type, $result->type->subtype, 'open', $result->lat, $result->lng, $result->description, $DATA);
	header("Content-type: application/json; charset=utf-8");        		
	$return_string=array('event_id'=>$event_id,'result'=>'nuova segnalazione aperta con successo');
	$return_json=__json_encode($return_string);
	echo $return_json;
	mysqli_close($con);
?>
