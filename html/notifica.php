<?php
		session_start();
		//recupero informazioni utente
  	    include '../data/data.php';
		include 'util.php';//file creato da stefano, se non sbaglio serve per la funzione __json_encode
  		include 'aggiornamento.php';
		$time=time(); //memorizzo il tempo attuale
       	$data=file_get_contents('php://input');//recupera i dati della richiesta POST
       	$result=json_decode($data);//costruisce un array a partire dal json della richiesta
		$con = mysqli_connect("localhost",$DATA['DB_USERNAME'],$DATA['DB_PASSWORD'],$DATA['DB_NAME']);//creo la connessione al server
		if (!$con)           
		{
			header('X-PHP-Response-Code: 500', true, 500);
			echo "500: Internal Server Error";
			die;
		}
		$str="SELECT * FROM ".$DATA['USER_TABLE']."  WHERE username='".$_SESSION['username']."';";
		$str=mysqli_query($con,$str);
		if (!$str)
		{
			header('X-PHP-Response-Code: 500', true, 500);
			echo "500: Internal Server Error";
			die;
		}
		$risp=mysqli_fetch_array($str);
		$u_reputation=$risp['reputation'];
		$u_assiduity=$risp['assiduity'];
		$str="SELECT * FROM ".$DATA['SUBTYPE_TABLE']." WHERE subtype='".$result->type->subtype."';";
		$str=mysqli_query($con, $str);
		if (!$str)
		{
			header('X-PHP-Response-Code: 500', true, 500);
			echo "500: Internal Server Error";
			die;
		}
		$risp=mysqli_fetch_array($str);
		$raggio=(float)$risp['metri'];
		$str="SELECT * FROM ".$DATA['EVENT_TABLE']." WHERE event_id=".$result->event_id.";";
		$str=mysqli_query($con, $str);
		if (!$str)
		{
			header('X-PHP-Response-Code: 500', true, 500);
			echo "500: Internal Server Error";
			die;
		}
		$risp=mysqli_fetch_array($str);
		if (distanza($result->lat, $result->lng, $result->event_lat, $result->event_lng)>$raggio) //utente troppo lontano
		{
			header("Content-type: application/json; charset=utf-8");
			$return_string=array('event_id'=>$result->event_id,'result'=>'impossibile notificare, evento troppo distante');
			$return_json=__json_encode($return_string);
			echo $return_json;
		}
		else if($result->status=='archived' and $_SESSION['superuser']!=1)//l'utente non è superuser
		{
			header("Content-type: application/json; charset=utf-8");
			$return_string=array('event_id'=>$result->event_id,'result'=>'non hai i permessi per effettuare questa operazione');
			$return_json=__json_encode($return_string);
			echo $return_json;
		}
		else if($result->status=='archived')//l'utente super user tenta di archiviare una notifica
		{
			if($risp)
				$event_id=aggiorna_ev($result->event_id, 'archived', $result->lat, $result->lng, $result->description, $DATA);
			else
				$event_id=aggiungi($_SESSION['user'], $result->type->type, $result->type->subtype, 'archived', $result->lat, $result->lng, $result->description, $DATA);

			header("Content-type: application/json; charset=utf-8");        		
			$return_string=array('event_id'=>$event_id,'result'=>'notifica evento avvenuta con successo');
			$return_json=__json_encode($return_string);
			echo $return_json;
		}
		else
		{
			if($risp)//l'evento è nel db
				if ($risp['status']=='archived')
				{
					header("Content-type: application/json; charset=utf-8");
					$return_string=array('event_id'=>$result->event_id,'result'=>'evento già archiviato');
					$return_json=__json_encode($return_string);
					echo $return_json;
				}
				else if (!_skeptical($risp['status'],$result->status))
					$event_id=aggiorna_ev($result->event_id, $result->status, $result->lat, $result->lng, $result->description, $DATA);
				else
					$event_id=gest_skeptical($result->event_id, $result->status, $result->lat, $result->lng, $result->description, $result->DATA);
			else
				$event_id=aggiungi($_SESSION['user'], $result->type->type, $result->type->subtype, $result->status, $result->lat, $result->lng, $result->description, $DATA);
			if($event_id)
			{
				header("Content-type: application/json; charset=utf-8");        		
				$return_string=array('event_id'=>$event_id,'result'=>'notifica evento avvenuta con successo');
				$return_json=__json_encode($return_string);
				echo $return_json;
			}
		}
		mysqli_close($con);
?>