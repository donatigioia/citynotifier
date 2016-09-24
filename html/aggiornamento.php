<?php
	//controlla se ci sono degli eventi da chiudere nel db
	function aggiorna($DATA)
	{	
		$time=time();
		$err= mysqli_connect("localhost",$DATA['DB_USERNAME'],$DATA['DB_PASSWORD'],$DATA['DB_NAME']);
		if (!$err)
		{
			header('X-PHP-Response-Code: 500', true, 500);
			echo "500: Internal Server Error";
			die;
		}
		$time_min=$time-1200;
		$time_arc=$time-604800;//archivio gli eventi chiusi da 7 giorni
		$str="SELECT * FROM ".$DATA['EVENT_TABLE']." WHERE freshness<=".$time_min." AND status<>'closed' AND status<>'archived' AND subtype<>'buca';";
		$str=mysqli_query($err, $str);
		if (!$str)
		{
			header('X-PHP-Response-Code: 500', true, 500);
			echo "500: Internal Server Error";
			die;
		}
		while($risp=mysqli_fetch_array($str))
		{
			$str1=$str1="UPDATE ".$DATA['NOTIFY_TABLE']." SET conferma=NULL, skeptical=NULL WHERE event_id=".$risp['event_id']." AND skeptical=1;";
			$str=mysqli_query($err, $str1);
			if (!$str1)
			{	
				header('X-PHP-Response-Code: 500', true, 500);
				echo "500: Internal Server Error";
				die;
			}
		}
		$str="UPDATE ".$DATA['EVENT_TABLE']." set status='closed' WHERE freshness<=".$time_min." AND status<>'closed' AND status<>'archived' AND subtype<>'buca';";
		$str=mysqli_query($err, $str);
		if (!$str)
		{
			header('X-PHP-Response-Code: 500', true, 500);
			echo "500: Internal Server Error";
			die;
		}
		$str="UPDATE ".$DATA['EVENT_TABLE']." set status='archived' WHERE freshness<=".$time_arc." AND status='closed';";
		$str=mysqli_query($err, $str);
		if (!$str)
		{
			header('X-PHP-Response-Code: 500', true, 500);
			echo "500: Internal Server Error";
			die;
		}
		mysqli_close($err);
	}
	
	//funzione per calcolare la distanza (restituisce la distanza tra due punti in km)
	function getDistanceBetweenPointsNew($latitude1, $longitude1,$latitude2, $longitude2)
	{
		$theta = $longitude1 - $longitude2;
		$distance = (sin(deg2rad($latitude1)) * sin(deg2rad($latitude2))) + (cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * cos(deg2rad($theta)));
		$distance = acos($distance);
		$distance = rad2deg($distance);
		$distance = $distance * 60 * 1.1515;
		$distance = $distance *1.609344;
		return (round($distance,3));
	}
	
	function distanza($latitude1, $longitude1, $latitude2, $longitude2)//torna la distanza in metri tra due punti dati da latitudine e longitudine 1 e 2
	{		
		$latitude1=$latitude1;
		$latitude2=$latitude2;
		$longitude1=$longitude1;
		$longitude2=$longitude2;
		return (1000*(getDistanceBetweenPointsNew($latitude1, $longitude1,$latitude2, $longitude2)));
	}
	
	//controlla se la distanza è minore rispetto ad un raggio dato
	function dis_ok($latitude1, $longitude1, $latitude2, $longitude2, $raggio)
	{
		$raggio=(float)$raggio;
		if(distanza($latitude1, $longitude1, $latitude2, $longitude2)<=$raggio)
			return TRUE;
		else
			return FALSE;
	}
	
	//controllo se il passaggio dallo statos 1 al 2 mi porta in skeptical
	function _skeptical ($status1, $status2)
	{
		if($status1==$status2 or ($status1=='open' and $status2=='closed') or $status2=='archived')
			return FALSE;
		else
			return TRUE;
	}
	
	//controllo se un utente ha già segnalato lo stato di skeptical per una evento
	function _insert_ok($evet_id, $user,$DATA)
	{
		$con = mysqli_connect("localhost",$DATA['DB_USERNAME'],$DATA['DB_PASSWORD'],$DATA['DB_NAME']);
		if (!$con)
		{
			header('X-PHP-Response-Code: 500', true, 500);
			echo "500: Internal Server Error";
			die;
		}	
		$str="SELECT * FROM ".$DATA['NOTIFY_TABLE']." WHERE event_id=".$evet_id." AND username='".$user."' AND skeptical=1;";
		$str=mysqli_query($con, $str);
		 if (!$str)
		 {
			header('X-PHP-Response-Code: 500', true, 500);
			echo "500: Internal Server Error";
			die;
		}
		if(mysqli_num_rows($str)>=1)
			return TRUE;
		else
			return FALSE;
		mysqli_close($con);
	}
	
	function _is_ok($event)
	{
		//controllo se il typo e il sottotipo sono corretti
		$ev_ok=FALSE;
		if($event['type']['type']=='problemi_stradali' and ($event['type']['subtype']=='incidente' or $event['type']['subtype']=='buca' or
			$event['type']['subtype']=='coda' or $event['type']['subtype']=='lavori_in_corso' or $event['type']['subtype']=='strada_impraticabile'))
			$ev_ok=TRUE;
		else if($event['type']['type']=='emergenze_sanitarie' and ($event['type']['subtype']=='incidente' or 
				$event['type']['subtype']=='malore' or $event['type']['subtype']=='ferito'))
			$ev_ok=TRUE;
		else if($event['type']['type']=='reati' and ($event['type']['subtype']=='furto' or $event['type']['subtype']=='attentato'))
			$ev_ok=TRUE;
		else if($event['type']['type']=='problemi_ambientali' and ($event['type']['subtype']=='incendio' 
				or $event['type']['subtype']=='tornado' or $event['type']['subtype']=='neve' or $event['type']['subtype']=='alluvione'))
			$ev_ok=TRUE;
		else if($event['type']['type']=='eventi_pubblici' and ($event['type']['subtype']=='partita' or $event['type']['subtype']=='manifestazione' or 
				$event['type']['subtype']=='concerto'))
			$ev_ok=TRUE;
		if($ev_ok)
		{
			//controllo se gli altri campi sono presenti e/o corretto (gli eventi archiviati vengono scartati)
			if($event['event_id'] and $event['start_time'] and $event['freshness'] and
				($event['status']=='open' or $event['status']=='closed' or $event['status']=='skeptical' or $event['status']=='archived') 
				and $event['reliability'] and $event['number_of_notifications'] and $event['locations'])
				return TRUE;
			else
				return FALSE;
		}
		else
			return FALSE;
	}

	
//*******************************************************************************************************************************************************************
//*******************************************************************************************************************************************************************


//***********************************************AGGIUNGI UN NUOVO EVENTO*****************************************************
	function aggiungi($user, $type, $subtype, $status, $lat, $lng, $description, $DATA)
	{
		include '../data/data.php';
		session_start();
		$time=time(); 
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
		$str="SELECT * FROM ".$DATA['SUBTYPE_TABLE']." WHERE subtype='".$subtype."';";
		$str=mysqli_query($con, $str);
		if (!$str)
		{
			header('X-PHP-Response-Code: 500', true, 500);
			echo "500: Internal Server Error";
			die;
		}
		$u_assiduity=$u_assiduity+0.1;
		if($u_assiduity>=1)
			$u_assiduity=1;
		$reliability_event=($u_assiduity*$u_reputation+1)/2; //visto che il numero di notifiche è sicuramente uno il denominatore è sicuramente due	
		$str="INSERT INTO ".$DATA['EVENT_TABLE']." (type, subtype, start_time, freshness, status, reliability, number_of_notifications) VALUES ('".$type."','".$subtype."',".$time.",".$time.",'".$status."',".$reliability_event.",1);";
		
		$str=mysqli_query($con, $str);
		if (!$str)
		{
			header('X-PHP-Response-Code: 500', true, 500);
			echo "500: Internal Server Error";
			die;
		}
		$str="SELECT * FROM ".$DATA['EVENT_TABLE']." WHERE start_time=".$time.";";
		$str=mysqli_query($con, $str);
		if (!$str)
		{
			header('X-PHP-Response-Code: 500', true, 500);
			echo "500: Internal Server Error";
			die;
		}
		$risp=mysqli_fetch_array($str);
		$str="INSERT INTO ".$DATA['NOTIFY_TABLE']." (event_id, ";
		$str1="latitude, longitude, username, reputation, assiduity, time) VALUES ('".$risp['event_id']."', ";
		if($description)
		{
			$str=$str."description, ";
			$str1=$str1."'".mysql_escape_string($description)."', ";
		}
		$str=$str.$str1."".$lat.",".$lng.",'".$_SESSION['username']."', ".$u_reputation.", ".$u_assiduity.", ".$time.");";
		$str=mysqli_query($con, $str);
		if (!$str)
		{
			header('X-PHP-Response-Code: 500', true, 500);
			echo "500: Internal Server Error";
			die;
		}
		$str="UPDATE ".$DATA['USER_TABLE']." SET assiduity=".$u_assiduity.",last_lat=".$lat.", last_lng=".$lng." WHERE username='".$_SESSION['username']."';";
		$str=mysqli_query($con, $str);
		if (!$str)
		{
			header('X-PHP-Response-Code: 500', true, 500);
			echo "500: Internal Server Error";
			die;
		}
		$_SESSION['last_lat']=$lat;
		$_SESSION['last_lng']=$lng;
		mysqli_close($con);
		return $risp['event_id'];
	}
	
	
//********************************************AGGIORNA UN EVENTO ESISTENTE*****************************************************************	
	function aggiorna_ev($event_id, $status, $lat, $lng, $description, $DATA)
	{
		
		include '../data/data.php';
		session_start();
		$time=time(); 
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
		$u_assiduity=$u_assiduity+0.1; //incremento assiduità dell'utente
		if($u_assiduity>=1)
			$u_assiduity=1;
		$str="INSERT INTO ".$DATA['NOTIFY_TABLE']." (event_id, "; //inserisco la nuova notifica nella tabella delle notifiche
		$str1="latitude, longitude, username, reputation, assiduity, time) VALUES ('".$event_id."', ";
		if($description)
		{
			$str=$str."description, ";
			$str1=$str1."'".mysql_escape_string($description)."', ";
		}
		$str=$str.$str1."".$lat.",".$lng.", '".$_SESSION['username']."', ".$u_reputation.", ".$u_assiduity.", ".$time.");";
		$str=mysqli_query($con, $str);
		if (!$str)
		{
			header('X-PHP-Response-Code: 500', true, 500);
			echo "500: Internal Server Error";
			die;
		}
		$str="SELECT * FROM ".$DATA['NOTIFY_TABLE']." WHERE event_id=".$event_id.";"; //aggiornamento dell'evento corrispondente
		$str=mysqli_query($con, $str);
		if (!$str)
		{
			header('X-PHP-Response-Code: 500', true, 500);
			echo "500: Internal Server Error";
			die;
		}
		$num_not=mysqli_num_rows($str);
		$tot_rel=0;
		while($risp=mysqli_fetch_array($str))
			$tot_rel=$tot_rel+(1+($risp['reputation']*$risp['assiduity']));
		$tot_rel=$tot_rel/(2*$num_not);
		$str="UPDATE ".$DATA['EVENT_TABLE']." SET reliability=".$tot_rel.", status='".$status."', number_of_notifications=".$num_not.", freshness=".$time." WHERE event_id=".$event_id.";";
		$str=mysqli_query($con, $str);
		if (!$str)
		{
			header('X-PHP-Response-Code: 500', true, 500);
			echo "500: Internal Server Error";
			die;
		}
		//update assiduity dell'utente
		$str="UPDATE ".$DATA['USER_TABLE']." SET assiduity=".$u_assiduity.", last_lat=".$lat.", last_lng=".$lng." WHERE username='".$_SESSION['username']."';";
		$str=mysqli_query($con, $str);
		if (!$str)
		{
			header('X-PHP-Response-Code: 500', true, 500);
			echo "500: Internal Server Error";
			die;
		}
		$_SESSION['last_lat']=$lat;
		$_SESSION['last_lng']=$lng;
		mysqli_close($con);		
		return $event_id;							
	}
	
	
//******************************************************************INSERIMENTO DI UN EVENTO SKEPTICAL*********************************************************
	function gest_skeptical($t_id, $status, $lat, $lng, $description, $DATA)
	{	
		include '../data/data.php';
		session_start();
		$time=time(); 
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
		//**************************************************GESTIONE SKEPTICAL**************************************
		if(_insert_ok($t_id, $_SESSION['username'],$DATA) and FALSE)
		{
			//l'utente ha già inserito una notifica skeptical quindi non è possibile inserirne un'altra
			mysqli_close($con);
			return $t_id;
		}
		else
		{
			
			//*************************************INIZIO GESTIONE SKEPTICAL*****************************************
			$assiduity=$assiduity+0.1;
			if($assiduity>1)
				$assiduity=1;
			$str="INSERT INTO ".$DATA['NOTIFY_TABLE']." (event_id, "; //inserisco la nuova notifica e aggiorno lo stato dell'evento e il numero di notifiche del medesimo
			$str1="latitude, longitude, username, reputation, assiduity, skeptical, conferma, time) VALUES (".$t_id.", ";
			if($description)
			{
				$str=$str."description, ";
				$str1=$str1."'".$description."', ";
			}
			$str=$str.$str1."".$lat.",".$lng.", '".$_SESSION['username']."', ".$u_reputation.", ".$u_assiduity.", 1,'".$status."', ".$time.");";
			$str=mysqli_query($con, $str);
			if (!$str)
			{
				header('X-PHP-Response-Code: 500', true, 500);
				echo "500: Internal Server Error";
				die;
			}
			$str="SELECT * FROM ".$DATA['NOTIFY_TABLE']." WHERE event_id=".$t_id." AND skeptical=1;";
			$str=mysqli_query($con, $str);
			if (!$str)
			{
				header('X-PHP-Response-Code: 500', true, 500);
				echo "500: Internal Server Error";
				die;
			}
			$num_not=(int)mysqli_num_rows($str);
			$strk="SELECT * FROM ".$DATA['EVENT_TABLE']." WHERE event_id=".$t_id.";";
			$strk=mysqli_query($con, $strk);
			if (!$strk)
			{
				header('X-PHP-Response-Code: 500', true, 500);
				echo "500: Internal Server Error";
				die;
			}
			$risp=mysqli_fetch_array($strk);
			$n_o_n=$risp['number_of_notifications']+1;
			$str1="UPDATE ".$DATA['EVENT_TABLE']." SET number_of_notifications=".$n_o_n.", freshness=".$time." WHERE event_id=".$t_id.";";
			$str1=mysqli_query($con, $str1);
			if (!$str1)
			{
				header('X-PHP-Response-Code: 500', true, 500);
				echo "500: Internal Server Error";
				die;
			}
			if($num_not==1)
			{
				$str="UPDATE ".$DATA['EVENT_TABLE']." SET status='skeptical', freshness=".$time." WHERE event_id=".$t_id.";";
				$str=mysqli_query($con, $str);
				if (!$str)
				{
					header('X-PHP-Response-Code: 500', true, 500);
					echo "500: Internal Server Error";
					die;
				}
			}
			else if($num_not>=$DATA['SKEPTICAL_NUMBER'])
			{
				$open=0;
				$close=0;
				while($risp=mysqli_fetch_array($str))
				{
					if($risp['conferma']=='open')
						$open=$open+1;
					else
						$close=$close+1;
				}
				if($open>$close)
				{
					$str="UPDATE ".$DATA['EVENT_TABLE']." SET status='open', freshness=".$time." WHERE event_id=".$t_id.";";
					$str=mysqli_query($con, $str);
					if (!$str)
					{
						header('X-PHP-Response-Code: 500', true, 500);
						echo "500: Internal Server Error";
						die;
					}
					$str="SELECT * FROM ".$DATA['NOTIFY_TABLE']." WHERE event_id=".$t_id." AND skeptical=1;";
					$str=mysqli_query($con, $str);
					if (!$str)
					{
						header('X-PHP-Response-Code: 500', true, 500);
						echo "500: Internal Server Error";
						die;
					}
					while($risp=mysqli_fetch_array($str))
					{
						$str1="SELECT * FROM ".$DATA['USER_TABLE']." WHERE username='".$risp['username']."';";
						$str1=mysqli_query($con, $str1);

						if (!$str1)
						{
							header('X-PHP-Response-Code: 500', true, 500);
							echo "500: Internal Server Error";
							die;
						}
						$risp1=mysqli_fetch_array($str1);
						$assiduity=$risp1['assiduity'];
						$reputation=$risp1['reputation'];
						if($risp['conferma']=='open')
						{
							$reputation=$reputation+0.1;
							if($reputation>1)
							$reputation=1;
						}
						else
						{
							$reputation=$reputation-0.2;
							if($reputation<-1)
							$reputation=-1;
						}
						$str1="UPDATE ".$DATA['USER_TABLE']." SET reputation=".$reputation." WHERE username='".$risp['username']."';";
						$str1=mysqli_query($con, $str1);
						if (!$str1)
						{
							header('X-PHP-Response-Code: 500', true, 500);
							echo "500: Internal Server Error";
							die;
						}
						$str1="UPDATE ".$DATA['NOTIFY_TABLE']." SET reputation=".$reputation.", conferma=NULL, skeptical=NULL WHERE username='".$risp['username']."' and time=".$risp['time'].";";
						$str1=mysqli_query($con, $str1);
						if (!$str1)
						{
							header('X-PHP-Response-Code: 500', true, 500);
							echo "500: Internal Server Error";
							die;
						}
					}
				}
				else
				{
				$str="UPDATE ".$DATA['EVENT_TABLE']." SET status='close', freshness=".$time." WHERE event_id=".$t_id.";";
				$str=mysqli_query($con, $str);
				if (!$str)
				{
					header('X-PHP-Response-Code: 500', true, 500);
					echo "500: Internal Server Error";
					die;
				}
				$str="SELECT * FROM ".$DATA['NOTIFY_TABLE']." WHERE event_id=".$t_id." AND skeptical=1;";
				$str=mysqli_query($con, $str);
				if (!$str)
				{
					header('X-PHP-Response-Code: 500', true, 500);
					echo "500: Internal Server Error";
					die;
				}
				while($risp=mysqli_fetch_array($str))
				{
					$str1="SELECT * FROM ".$DATA['USER_TABLE']." WHERE username='".$risp['username']."';";
					$str1=mysqli_query($con, $str1);
					if (!$str1)
					{
						header('X-PHP-Response-Code: 500', true, 500);
						echo "500: Internal Server Error";
						die;
					}
					$risp1=mysqli_fetch_array($str1);
					$assiduity=$risp1['assiduity'];
					$reputation=$risp1['reputation'];
					if($risp['conferma']=='close')
					{
						$reputation=$reputation+0.1;
						if($reputation>1)
							$reputation=1;
					}
					else
					{
						$reputation=$reputation-0.2;
						if($reputation<-1)
							$reputation=-1;
					}
					$str1="UPDATE ".$DATA['USER_TABLE']." SET reputation=".$reputation." WHERE username='".$risp['username']."';";
					$str1=mysqli_query($con, $str1);
					if (!$str1)
					{
						header('X-PHP-Response-Code: 500', true, 500);
						echo "500: Internal Server Error";
						die;
					}
					$str1="UPDATE ".$DATA['NOTIFY_TABLE']." SET reputation=".$reputation.", conferma=NULL, skeptical=NULL WHERE username='".$risp['username']."' AND time=".$risp['time'].";";
					$str1=mysqli_query($con, $str1);
					if (!$str1)
					{
						header('X-PHP-Response-Code: 500', true, 500);
						echo "500: Internal Server Error";
						die;
					}
				}	
				}
			}
			$str="SELECT * FROM ".$DATA['NOTIFY_TABLE']." WHERE event_id=".$t_id.";";
			$str=mysqli_query($con, $str);
			if (!$str)
			{
			header('X-PHP-Response-Code: 500', true, 500);
			echo "500: Internal Server Error";
			die;
			}
			$num_not=mysqli_num_rows($str);
			$tot_rel=0;
			while($risp=mysqli_fetch_array($str))
				$tot_rel=$tot_rel+(1+($risp['reputation']*$risp['assiduity']));
			$tot_rel=$tot_rel/(2*$num_not);
			$str="UPDATE ".$DATA['EVENT_TABLE']." SET reliability=".$tot_rel.", freshness=".$time." WHERE event_id=".$t_id.";";
			$str=mysqli_query($con, $str);
			if (!$str)
			{
				header('X-PHP-Response-Code: 500', true, 500);
				echo "500: Internal Server Error";
				die;
			}
			
		}
		return $t_id;
		mysqli_close($con);		
	}
?>