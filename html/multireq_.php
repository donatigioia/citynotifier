<?php
		header("Content-type: application/json; charset=utf-8");
		include 'util.php';//file creato da stefano, se non sbaglio serve per la funzione __json_encode
		include 'ricezione.php';
		if($_GET['scope']=='local')
			echo chiamata_locale();
		else
		{
			$locale=chiamata_locale();
			$xml = simplexml_load_file("catalogo.xml"); //prendo i gruppi dal catalogo XML
			$gruppi=$xml->xpath('//server'); //carico in $gruppi gli URL dei gruppi
			$time=time();//memorizzo in una variabile il momento in cui mi è arrivata la richiesta
			$mh=curl_multi_init(); //inizia la multi_curl
			$ch=array(); //creo un array di channel locali delle curl
			$opt = array
			(  //opzioni della curl
				CURLOPT_RETURNTRANSFER => TRUE, //returntransfer:restituisce il controllo ad una variabile
				CURLOPT_HEADER => FALSE, //niente header
				CURLOPT_TIMEOUT=>500000, //timeout, momentaneamente settato ad un valore volutamente ridicolo
				CURLOPT_FAILONERROR => FALSE, //in caso di errore, prosegui pure (verrà tolto a fine debug)
				CURLOPT_HTTPHEADER => array('Accept: application/json') //header per la richiesta
			); 
			for ($i=0;$i<count($gruppi);$i++)
			{
				$queryurl=$gruppi[$i]['url']."/richieste?".$_SERVER['QUERY_STRING']; //creo l'URL di invio
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
			$eventi_esterni=array(); //eventi_esterni conterrà l'array di eventi esterni
			for ($i=0;$i<count($reply_json);$i++)
			{
				$reply_json[$i]=json_decode($reply_json[$i],true); //faccio la json encode sull'array
				if ($reply_json[$i] && $reply_json[$i]['result']) //cioè se ho ricevuto un json sensato e non vuoto
					$eventi_esterni[]=$reply_json[$i]; //riempio l'array di eventi esterni
	
			}
			for($i=0;$i<count($eventi_esterni);$i++)
			{
				for($j=0;$j<count($eventi_esterni[$i]['events']);$j++)
				{
					$new=TRUE;
					$str="SELECT * FROM SOTTOTIPI WHERE subtype='".$eventi_esterni[$i]['events'][$j]['type']['subtype']."';";
					$str=mysqli_query($con, $str) or die("fallita connessione al database SOTTOTIPI per ottenere il raggio");
					$risp=mysqli_fetch_array($str);
					for($k=0;$k<count($locale['events']) and $new;$k++)
					{
						if(($eventi_esterni[$i]['events'][$j]['type']['type']==$locale['events'][$k]['type']['tyoe'])and($eventi_esterni[$i]['events'][$j]['type']['subtype']==$locale['events'][$k]['type']['subtyoe']))						
						{		
							for($z=0;$z<count($eventi_esterni[$i]['events'][$j]['location'] and $new);$z++)
							{
								$lat_max=max(($eventi_esterni[$i]['events'][$j]['location'][$z]['lat'])+$risp['metri'],($eventi_esterni[$i]['events'][$j]['location'][$z]['lat'])-$risp['metri']);
								$lng_max=max(($eventi_esterni[$i]['events'][$j]['location'][$z]['lng'])+($risp['metri']/cos(($eventi_esterni[$i]['events'][$j]['location'][$z]['lng'])*180/M_PI)),($eventi_esterni[$i]['events'][$j]['location'][$z]['lng'])+($risp['metri']/cos(($eventi_esterni[$i]['events'][$j]['location'][$z]['lng'])*180/M_PI)));
								$lat_min=min(($eventi_esterni[$i]['events'][$j]['location'][$z]['lat'])+$risp['metri'],($eventi_esterni[$i]['events'][$j]['location'][$z]['lat'])-$risp['metri']);
								$lng_min=min(($eventi_esterni[$i]['events'][$j]['location'][$z]['lng'])-($risp['metri']/cos(($eventi_esterni[$i]['events'][$j]['location'][$z]['lng'])*180/M_PI)),($eventi_esterni[$i]['events'][$j]['location'][$z]['lng'])+($risp['metri']/cos(($eventi_esterni[$i]['events'][$j]['location'][$z]['lng'])*180/M_PI)));
								for($x=0;$x<count($locale['events'][$k]['location']) and $new;$x++)
								{
									if($locale['events'][$k]['location']['lat']>=$lat_min and $locale['events'][$k]['location']['lat']<=$lat_max and $locale['events'][$k]['location']['lng']>=$lng_min and $locale['events'][$k]['location']['lng']<=$lng_max)
									{
										$locale['events'][$k]['location']=$eventi_esterni['events'][$i]['location'];
										$new=FALSE;
									}
								}
							}
						}
						if($new)
							$locale['events'][]=$eventi_esterni['events'][$i];
					}
				}
			}
			echo $locale;
			//a questo punto del codice $eventi_esterni contiene gli eventi esterni di tutti i server a cui ho fatto richiesta
		}
			
?>
