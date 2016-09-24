<?php
		header("Content-type: application/json; charset=utf-8");
		include 'util.php';//file creato da stefano, se non sbaglio serve per la funzione __json_encode
		include 'ricezione.php';
		include '../data/data.php';
		if (!GET_check())
		{
                header('X-PHP-Response-Code: 406', true, 406);
                echo "Errore 406: Parametri della richiesta non compatibili";
                die;
		}
		if (!($_SERVER['REQUEST_METHOD'] === 'GET'))
		{
	       		header('X-PHP-Response-Code: 405', true, 405);
                echo "405: Method not allowed.";
                exit;
	    }
		if($_GET['scope']=='local')			
			echo chiamata_locale();
		else
		{
			$locale=json_decode(chiamata_locale(),TRUE);
			if($locale['events']=="")
				$locale['events']=array();
			$xml = simplexml_load_file("catalogo.xml"); //prendo i gruppi dal catalogo XML
			$gruppi=$xml->xpath('//server'); //carico in $gruppi gli URL dei gruppi
			$time=time();//memorizzo in una variabile il momento in cui mi è arrivata la richiesta
			$mh=curl_multi_init(); //inizia la multi_curl
			$ch=array(); //creo un array di channel locali delle curl
			$opt = array
			(  //opzioni della curl
				CURLOPT_RETURNTRANSFER => TRUE, //returntransfer:restituisce il controllo ad una variabile
				CURLOPT_HEADER => FALSE, //niente header
				CURLOPT_TIMEOUT=>5, //timeout, momentaneamente settato ad un valore volutamente ridicolo
				CURLOPT_FAILONERROR => FALSE, //in caso di errore, prosegui pure (verrà tolto a fine debug)
				CURLOPT_HTTPHEADER => array('Accept: application/json') //header per la richiesta
			); 
			for ($i=0;$i<count($gruppi);$i++)
			{
				$queryurl=$gruppi[$i]['url']."/richieste?scope=local&type=".$_GET['type']."&subtype=".$_GET['subtype']."&lat=".$_GET['lat']."&lng=".$_GET['lng']."&radius=".$_GET['radius']."&timemin=".$_GET['timemin']."&timemax=".$_GET['timemax']."&status=".$_GET['status'];
				$ch[$i]=curl_init($queryurl); //assegno l'URL al channel
				curl_setopt_array($ch[$i], $opt); //assegno le opzioni al channel
			}
			for ($i=0;$i<count($ch);$i++)
				curl_multi_add_handle($mh,$ch[$i]); //aggiungo il channel alla multi_curl
			$running=null; //un valore che serve per indicare quando termina la multi_curl
			do 
			{
				curl_multi_exec($mh,$running); //esecuzione multi_curl
			} while($running>0);
			$reply_json=array(); //conterrà i JSON di risposta ricevuti
			for ($i=0;$i<count($ch);$i++)
			{
				$reply_json[$i]=curl_multi_getcontent($ch[$i]); //riempio reply_json con le risposte ricevute
				curl_multi_remove_handle($mh, $ch[$i]); //rimuovo il channel che ormai non serve più XD
			}
			curl_multi_close($mh);
			$eventi_esterni=array(); //eventi_esterni conterrà l'array di eventi esterni
			/*for ($i=0;$i<count($reply_json);$i++)
			{
				$reply_json[$i]=json_decode($reply_json[$i],TRUE); //faccio la json encode sull'array
				if ($reply_json[$i] and isset($reply_json[$i]['events'][0]['type'])) //cioè se ho ricevuto un json sensato e non vuoto
					$eventi_esterni[]=$reply_json[$i]; //riempio l'array di eventi esterni
			}*/
			for ($i=0;$i<count($reply_json);$i++)
			{
				$tmp_reply_json=json_decode($reply_json[$i],TRUE); //faccio la json encode sull'array
				if ($tmp_reply_json and isset($tmp_reply_json['events'][0]['type'])) //cioè se ho ricevuto un json sensato e non vuoto
					$eventi_esterni[]=$tmp_reply_json; //riempio l'array di eventi esterni
				unset($tmp_reply_json);
			}
			$con=mysqli_connect("localhost","my1332","aenuepoo","my1332") or die("fallita connessione al DB");
			$str="SELECT * FROM EVENTI;";
			$str=mysqli_query($con, $str);
			$num_event=mysqli_num_rows($str)+1;
			for($i=0;$i<count($eventi_esterni);$i++)
			{
				if (isset($eventi_esterni[$i]['events']))
				{
					for($j=0;$j<count($eventi_esterni[$i]['events']);$j++)
					{
						if(_is_ok($eventi_esterni[$i]['events'][$j]))
						{
							$new=TRUE;
							$str="SELECT * FROM SOTTOTIPI WHERE subtype='".$eventi_esterni[$i]['events'][$j]['type']['subtype']."';";
							$str=mysqli_query($con, $str) or die("fallita connessione al database SOTTOTIPI per ottenere il raggio");
							$risp=mysqli_fetch_array($str);
							for($k=0;$k<count($locale['events']) and $new;$k++)
							{
								for($z=0; $z<count($eventi_esterni[$i]['events'][$j]['locations']) and $new; $z++)
								{
									for($y=0; $y<count($locale['events'][$k]['locations']) and $new; $y++)
									{
										$lat1=$eventi_esterni[$i]['events'][$j]['locations'][$z]['lat'];
										$lng1=$eventi_esterni[$i]['events'][$j]['locations'][$z]['lng'];
										$lat2=$locale['events'][$k]['locations'][$y]['lat'];
										$lng2=$locale['events'][$k]['locations'][$y]['lng'];
										if(distanza($lat1, $lng1, $lat2, $lng2)<=$risp['metri'] and 
										$eventi_esterni[$i]['events'][$j]['type']['type']==$locale['events'][$y]['type']['type'] and
										$eventi_esterni[$i]['events'][$j]['type']['subtype']==$locale['events'][$y]['type']['subtype'] )
										{
											echo "sottotipo ".$locale['events'][$y]['type']['subtype']." si accorpa a ".$eventi_esterni[$i]['events'][$j]['type']['subtype']." ad una distanza di ".distanza($lat1, $lng1, $lat2, $lng2)." metri        ";
											$new=FALSE;
											/*
											$locale['events'][$y]['locations'][]=$eventi_esterni[$i]['events'][$j]['locations'];
											$locale['events'][$y]['description'][]=$eventi_esterni[$i]['events'][$j]['description'];*/
											$locale['events'][$y]['number_of_notifications']=$locale['events'][$y]['number_of_notifications']+
																							$eventi_esterni[$i]['events'][$j]['number_of_notifications'];
											$locale['events'][$y]['reliability']=($locale['events'][$y]['reliability']+
																					$eventi_esterni[$i]['events'][$j]['reliability'])/2;
											$stat_loc=$locale['events'][$k]['status'];
											$stat_est=$eventi_esterni[$i]['events'][$j]['status'];
											
											
											$description=NULL;
											for($h=0;$h<count($locale['events'][$y]['locations']);$h++)
												$locations[]=$locale['events'][$y]['locations'][$h];
											
											for($h=0;$h<count($locale['events'][$y]['description']);$h++)
												if($locale['events'][$y]['description'][$h])
													$description[]=$locale['events'][$y]['description'][$h];
											
											for($h=0;$h<count($eventi_esterni[$i]['events'][$j]['description']);$h++)
												if($eventi_esterni[$i]['events'][$j]['description'][$h])
													$description[]=$eventi_esterni[$i]['events'][$j]['description'][$h];
											
											for($h=0;$h<count($eventi_esterni[$i]['events'][$j]['locations']);$h++)
												$locations[]=$eventi_esterni[$i]['events'][$j]['locations'][$h];
											
											
											$locale['events'][$y]['description']=$description;
											
											$locale['events'][$y]['locations']=$locations;
											unset($description);
											unset($locations);
											
											while(count($locale['events'][$y]['description'])<5)
												$locale['events'][$y]['description'][]="";
							
											
											if($stat_loc=$locale['events'][$k]['freshness']<=$stat_est=$eventi_esterni[$i]['events'][$j]['freshness'])
											{
												if(_skeptical($stat_loc,$stat_est))
													$locale['events'][$k]['status']='skeptical';
												else
													$locale['events'][$k]['status']=$stat_est;
											}
											else
											{
												$locale['events'][$k]['freshness']=$stat_est=$eventi_esterni[$i]['events'][$j]['freshness'];
												if(_skeptical($stat_est,$stat_loc))
													$locale['events'][$k]['status']='skeptical';	
											}
										}
									}
								}
							}
							if($new)
							{	
									while(count($eventi_esterni[$i]['events'][$j]['description'])<5)
										$eventi_esterni[$i]['events'][$j]['description'][]="";
									$new_ev=$eventi_esterni[$i]['events'][$j];
									$new_ev['event_id']=$num_event;
									$num_event++;
									array_push($locale['events'],$new_ev);
									unset($new_ev);
							}
						}
					}
				}
			}
			$events=NULL;
			for($k=0;$k<count($locale['events']);$k++)
			{
				$event_id=$locale['events'][$k]['event_id'];
				$type=$locale['events'][$k]['type']['type'];
				$subtype=$locale['events'][$k]['type']['subtype'];
				$start_time=$locale['events'][$k]['start_time'];
				$freshness=$locale['events'][$k]['freshness'];
				$status=$locale['events'][$k]['status'];
				$reliability=$locale['events'][$k]['reliability'];
				$number_of_notifications=$locale['events'][$k]['number_of_notifications'];
				$locations=$locale['events'][$k]['locations'];
				$description=$locale['events'][$k]['description'];
				$event['event_id']=$event_id;
				$event['type']=array('type'=>$type, 'subtype'=>$subtype);
				$event['description']=$description;
				$event['start_time']=$start_time;
				$event['freshness']=$freshness;
				$event['status']=$status;
				$event['reliability']=$reliability;
				$event['number_of_notifications']=$number_of_notifications;
				$event['locations']=$locations;
				$events[]=$event;
				unset($event);
				unset($description);
				unset($locations);
			}
			$sendjson=array('request_time'=>$time,'result'=>'Un JSON per domarli, un JSON per trovarli, Un JSON per ghermirli e nel buio incatenarli.','from_server'=>'http://ltw1326.web.cs.unibo.it','events'=>$events);//creo la stringa che devo restituire come json
			echo __json_encode($eventi_esterni,TRUE);
			//a questo punto del codice $eventi_esterni contiene gli eventi esterni di tutti i server a cui ho fatto richiesta
		  }
			
?>
