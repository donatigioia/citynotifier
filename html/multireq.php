<?php
		header("Content-type: application/json; charset=utf-8");
		//includo i file di supporto
		include 'util.php';
		include 'ricezione.php';
		include '../data/data.php';
		
		if (!($_SERVER['REQUEST_METHOD'] === 'GET'))//controllo se il metodo della richiesta è get
		{
	       		header('X-PHP-Response-Code: 405', true, 405);
                echo "405: Method not allowed.";
                exit;
	    }
		
		if (!GET_check())//controllo se la query è corretta
		{
                header('X-PHP-Response-Code: 406', true, 406);
                echo "Errore 406: Parametri della richiesta non compatibili";
                die;
		}
		
		if($_GET['scope']=='local') 			
			echo chiamata_locale($DATA);//funzione che viene chiamata per cercare gli eventi locali
		else
		{
			//inizio richieste multiple
			$locale=json_decode(chiamata_locale($DATA),TRUE);//effettuo la chiamata degli eventi locali
			if($locale['events']=="")
				$locale['events']=array();//inizializzo il vettore nel caso in cui non ci sono eventi locali
			$xml = simplexml_load_file("http://vitali.web.cs.unibo.it/twiki/pub/TechWeb13/Catalogo/catalogoXML.xml"); //prendo i gruppi dal catalogo XML
			$gruppi=$xml->xpath('//server'); //carico in $gruppi gli URL dei gruppi
			$time=time();//memorizzo in una variabile il momento in cui mi è arrivata la richiesta
			$mh=curl_multi_init(); //inizia la multi_curl
			$ch=array(); //creo un array di channel locali delle curl
			$opt = array
			(  //opzioni della curl
				CURLOPT_RETURNTRANSFER => TRUE, //returntransfer:restituisce il controllo ad una variabile
				CURLOPT_HEADER => FALSE, //niente header
				CURLOPT_TIMEOUT=>5, //timeout
				CURLOPT_FAILONERROR => FALSE, //in caso di errore, prosegui pure 
				CURLOPT_HTTPHEADER => array('Accept: application/json') //header per la richiesta
			); 
			for ($i=0;$i<count($gruppi);$i++)
				if($gruppi[$i]['url']==$DATA['LOCAL_URL'])//elimino l'url del server locale
					unset($gruppi[$i]['url']);
			for ($i=0;$i<count($gruppi);$i++)
			{
				$queryurl=$gruppi[$i]['url']."/richieste?scope=local&type=".$_GET['type']."&subtype=".$_GET['subtype']."&lat=".$_GET['lat']."&lng=".$_GET['lng']."&radius=".$_GET['radius']."&timemin=".$_GET['timemin']."&timemax=".$_GET['timemax']."&status=".$_GET['status'];
				$ch[$i]=curl_init($queryurl); //assegno l'URL al channel
				curl_setopt_array($ch[$i], $opt); //assegno le opzioni al channel
			}
			for ($i=0;$i<count($ch);$i++)
				curl_multi_add_handle($mh,$ch[$i]); //aggiungo il channel alla multi_curl
			$running=NULL; //un valore che serve per indicare quando termina la multi_curl
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
			for ($i=0;$i<count($reply_json);$i++)
			{
				$tmp_reply_json=json_decode($reply_json[$i],TRUE); //faccio la json encode sull'array
				if ($tmp_reply_json and isset($tmp_reply_json['events'][0]['type'])) //cioè se ho ricevuto un json sensato e non vuoto
					$eventi_esterni[]=$tmp_reply_json; //riempio l'array di eventi esterni
				unset($tmp_reply_json);
			}
			$con = mysqli_connect("localhost", $DATA['DB_USERNAME'], $DATA['DB_PASSWORD'], $DATA['DB_NAME']);
			if (!$con)//controllo di errore nel caso in cui fallisce la connessione al DB
			{	
				header('X-PHP-Response-Code: 500', true, 500);
				echo "500: Internal Server Error";
				die;
			}
			$str="SELECT * FROM EVENTI;";
			$str=mysqli_query($con, $str);
			if (!$str)//controllo di errore nel caso in cui fallisce la query (il controllo è ripetuto per ogni query fatta al db)
			{
				header('X-PHP-Response-Code: 500', true, 500);
				echo "500: Internal Server Error";
				die;
			}
			$num_event=mysqli_num_rows($str)+1;//l'id degli eventi interni è un numero, l'evento n ha id n per evitare che un evento esterno non presente
												//nel DB venga scambiato per un altro evento gli verra assegnato un id maggiore del'id massimo presente nel vettor
			
			//***********************************************************AGGREGAZIONE**********************************************************
			for($i=0;$i<count($eventi_esterni);$i++)//1)per ogni server esterno
			{
					for($j=0;$j<count($eventi_esterni[$i]['events']);$j++)//2)controllo se ogno sua notifica
					{
						if(_is_ok($eventi_esterni[$i]['events'][$j]))//controllo se l'evento j rispetta il protocollo
						{
							$new=TRUE;//serve per vedere se l'evento viene aggregato
							//vado a selezionare il raggio di aggregazione
							$str="SELECT * FROM ".$DATA['SUBTYPE_TABLE']." WHERE subtype='".$eventi_esterni[$i]['events'][$j]['type']['subtype']."';";
							$str=mysqli_query($con, $str);
							if (!$str)
							{
								header('X-PHP-Response-Code: 500', true, 500);
								echo "500: Internal Server Error";
								die;
							}
							$risp=mysqli_fetch_array($str);
							for($k=0;$k<count($locale['events']) and $new;$k++)//3)può unirsi a una notifica presente nel server locale
							{
								for($z=0; $z<count($eventi_esterni[$i]['events'][$j]['locations']) and $new; $z++)
								{
										for($y=0; $y<count($locale['events'][$k]['locations']) and $new; $y++)
										{
												$lat1=$eventi_esterni[$i]['events'][$j]['locations'][$z]['lat'];
												$lng1=$eventi_esterni[$i]['events'][$j]['locations'][$z]['lng'];
												$lat2=$locale['events'][$k]['locations'][$y]['lat'];
												$lng2=$locale['events'][$k]['locations'][$y]['lng'];
												//controllo se la distanza il tipo e il sottotipo sono corretti per effettuare l'aggregazione
												if(distanza($lat1, $lng1, $lat2, $lng2)<=$risp['metri'] and 
												$eventi_esterni[$i]['events'][$j]['type']['type']==$locale['events'][$k]['type']['type'] and
												$eventi_esterni[$i]['events'][$j]['type']['subtype']==$locale['events'][$k]['type']['subtype'] )
												{
													$new=FALSE;
													if($locale['events'][$k]['freshness']<$eventi_esterni[$i]['events'][$j]['freshness'] and
														$locale['events'][$k]['status']=='archived')
														$new=TRUE;
													else if($locale['events'][$k]['freshness']>$eventi_esterni[$i]['events'][$j]['freshness'] and
															$eventi_esterni[$i]['events'][$j]['status']=='archived')
														$new=TRUE;
													if(!$new)
													{
														//calcolo il numero di notifiche
														$locale['events'][$k]['number_of_notifications']=$locale['events'][$k]['number_of_notifications']+
																										$eventi_esterni[$i]['events'][$j]['number_of_notifications'];
														//calcolo la nuova reliability
														//ps: in mancanza dei dati dei singoli eventi viene calcolata come somma 
														//delle realiability dei due eventi e divisa per due
														$locale['events'][$k]['reliability']=($locale['events'][$k]['reliability']+
																								$eventi_esterni[$i]['events'][$j]['reliability'])/2;
														$stat_loc=$locale['events'][$k]['status'];
														$stat_est=$eventi_esterni[$i]['events'][$j]['status'];
														
														//aggiorno il vettore delle descrizioni e delle posizioni
														$description=NULL;
														for($h=0;$h<count($locale['events'][$k]['locations']);$h++)
															$locations[]=array('lat'=>floatval($locale['events'][$k]['locations'][$h]['lat']),
																				'lng'=>floatval($locale['events'][$k]['locations'][$h]['lng']));
														
														for($h=0;$h<count($locale['events'][$k]['description']);$h++)
															if($locale['events'][$k]['description'][$h])
																$description[]=$locale['events'][$k]['description'][$h];
														
														for($h=0;$h<count($eventi_esterni[$i]['events'][$j]['description']);$h++)
															if($eventi_esterni[$i]['events'][$j]['description'][$h])
																$description[]=$eventi_esterni[$i]['events'][$j]['description'][$h];
														
														for($h=0;$h<count($eventi_esterni[$i]['events'][$j]['locations']);$h++)
															$locations[]=array('lat'=>floatval($eventi_esterni[$i]['events'][$j]['locations'][$h]['lat']),
																				'lng'=>floatval($eventi_esterni[$i]['events'][$j]['locations'][$h]['lng']));
														
														
														$locale['events'][$k]['description']=$description;
														
														$locale['events'][$k]['locations']=$locations;
														unset($description);
														unset($locations);
														
														//nel caso in cui ci sono meno di 5 notifiche vengono aggiunge delle notifiche nulle 
														while(count($locale['events'][$k]['description'])<5)
															$locale['events'][$k]['description'][]="";
										
														//controllo se l'aggregazione cambia lo stato della notifica
														if($locale['events'][$k]['freshness']<=$eventi_esterni[$i]['events'][$j]['freshness'])
														{
															$locale['events'][$k]['freshness']=$eventi_esterni[$i]['events'][$j]['freshness'];
															if(_skeptical($stat_loc,$stat_est))
																$locale['events'][$k]['status']='skeptical';
															else
																$locale['events'][$k]['status']=$stat_est;
														}
														else
														{
															if(_skeptical($stat_est,$stat_loc))
																$locale['events'][$k]['status']='skeptical';
														}
													}
												}
											
										}
									
								}
							}
							if($new)//l'evento non si è unito a nessun evento presente nel vettore degli eventi locali
									//viene quindi messo in coda al vettore e gli viene assegnato un nuovo event_id
							{	
									while(count($eventi_esterni[$i]['events'][$j]['description'])<=5)
										$eventi_esterni[$i]['events'][$j]['description'][]="";
									$eventi_esterni[$i]['events'][$j]['event_id']=$num_event;
									$num_event++;
									for($h=0;$h<count($eventi_esterni[$i]['events'][$j]['locations']);$h++)
										$locations[]=array('lat'=>floatval($eventi_esterni[$i]['events'][$j]['locations'][$h]['lat']),
															'lng'=>floatval($eventi_esterni[$i]['events'][$j]['locations'][$h]['lng']));
									unset($eventi_esterni[$i]['events'][$j]['locations']);
									$eventi_esterni[$i]['events'][$j]['locations']=$locations;
									$f=count($locale['events']);
									$locale['events'][$f]=$eventi_esterni[$i]['events'][$j];
									unset($locations);
							}
						}
					}
				
			}
			//costruisco il vettore da restituire
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
				
				if(count($locale['events'][$k]['description'])>5)
					for($q=count($locale['events'][$k]['description']);$q>5;$q--)
						unset($locale['events'][$k]['description'][$q-1]);
				
				$description=$locale['events'][$k]['description'];
				$event['event_id']=$event_id;
				$event['type']=array('type'=>$type, 'subtype'=>$subtype);
				$event['description']=$description;
				$event['start_time']=intval($start_time);
				$event['freshness']=intval($freshness);
				$event['status']=$status;
				$event['reliability']=floatval($reliability);
				$event['number_of_notifications']=intval($number_of_notifications);
				$event['locations']=$locations;
				$events[]=$event;
				unset($event);
				unset($description);
				unset($locations);
			}
			if(!$events)
				$events="";
			$sendjson=array('request_time'=>$time,'result'=>'Un JSON per domarli, un JSON per trovarli, Un JSON per ghermirli e nel buio incatenarli.','from_server'=>'http://ltw1326.web.cs.unibo.it','events'=>$events);//creo la stringa che devo restituire come json
			
			$sendjson=__json_encode($sendjson,TRUE);
			$sendjson=str_replace("\r"," ",$sendjson);
			echo str_replace("\n"," ",$sendjson);
			mysqli_close($con);
		  }
			
?>
