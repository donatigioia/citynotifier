<?php
	function chiamata_locale()
	{	
		include 'aggiornamento.php';
		header("Content-type: application/json; charset=utf-8");
		aggiorna();
		error_reporting(E_ALL); //fino alla riga 6 (compresa) sono comandi per la gestione errori
		ini_set('display_error', 1);  
		ini_set('ignore_repeated_errors', 0);  
		ini_set('ignore_repeated_source', 0);
		$time=time();//memorizzo in una variabile il momento in cui mi è arrivata la richiesta
		$radius=(int)$_GET['radius'];//con $_GET['elemento'] indichiamo che andiamo a prendere il valore che ha elemento nella query
		$err = mysqli_connect("localhost", "my1332", "aenuepoo", "my1332");//finzione che serve per connettersi al server, PER IL MOMENTO NON è GESTITO ALCUN ERRRE
		//nella prossima riga iniziamo la costruzione della stringa di domanda che andremo a fare al server
		$str = "SELECT * FROM EVENTI WHERE start_time<=".$_GET['timemax']." AND start_time>=".$_GET['timemin']."";
		if($_GET['status']=='all')//nel caso in cui voglio tutti gli eventi io non devo aggiungere altre clausole quindi ordino gli eventi per id decrescente e invio la domanda
			$str="".$str." ORDER BY event_id DESC;";//se devo cercare tutte le notifiche non specifico lo stato
		else//nel caso in cui invece ho voglio solo gli eventi di un determinato stato allora lo aggiungo alle clausole e ordino tutto in ordine decrescente
			$str="".$str." AND Status='".$_GET['status']."' ORDER BY event_id DESC;";//altrimenti specifico lo stato da selezionare
		echo $str;
		$str=mysqli_query($err, $str);//invio la richiesta al server
		header("Content-type: application/json; charset=utf-8");//specifico l'header del mio json
		$empty=TRUE;
		while($risp=mysqli_query($err,$str))
		{	
		//SPIEGAZIONE: in $str sono contenuti tutti gli eventi che rientrano nel tempo richiesto e che hanno tipo scelto
		//controllo per ogni evento se c'è ALMENO UNA notifica che rientra nel raggio dato, in questo caso aggiungo l'evento nel vettore da tornare
			$empty=FALSE;
			$str1="SELECT * FROM NOTIFICHE WHERE event_id=".$risp['event_id'].";";
			$str1=mysqli_query($err,$str1);
			$aggiungi=FALSE;
			while($risp1=mysqli_fetch_array($str1) and !$aggiungi)
				if(distanza($_GET['lat'],$_GET['lng'],$risp1['lat'],$risp1['lng'])<=$radius)
					$aggiungi=TRUE;
			if($aggiungi)
			{		
				$event_id_tmp=NULL;//dalla riga 26 alla riga 35(compresa) creo dei vettori in cui verranno messi i valori degli eventi che vengono restituiti dalla richiesta
				$event_id;
				$type;
				$description;
				$start_time;
				$freshness;
				$status;
				$reliability;
				$number_of_notification;
				$location;
				$i=0;
				$str2="SELECT * FROM NOTIFICHE INNER JOIN EVENTI ON NOTIFICHE.event_id = EVENTI.event_id WHERE EVENTI.event_id=".$risp['event_id'].";";
				while($risp2=mysqli_fetch_array($str2))
				{
					$event_id[$i]=$risp2['event_id'];
					$event_id_tmp=$risp2['event_id'];
					$type[$i]=array('type'=>$risp2['type'], 'subtype'=>$risp2['subtype']);
					$start_time[$i]=$risp2['start_time'];
					$freshness[$i]=$risp2['freshness'];
					$status[$i]=$risp2['status'];
					$reliability[$i]=$risp2['reliability'];
					$number_of_notification[$i]=$risp2['number_of_notifications'];
					$j=0;
					do
					{
						if($j<5)
							$description[$i][$j]=$risp2['description'];
						$locations[$i][$j]=array('lat'=>$risp2['latitude'],'lng'=>$risp2['longitude']);
						$j++;
					}while($event_id_tmp==$risp2['event_id']);
					while($j<5)
					{
						$description[$i][$j]="";
						$j++;
					}
					$i++;
				}
			}
			$event;
			$events;
			$i--;
			while($i>=0)//dalla riga 63 alla 72 (compresa) vado a creare il vettore eventi come viene richiesto dalle specifiche
			{
				$events['event_id']=$event_id[$i];
				$events['type']=$type[$i];
							$description[$i]=$risp2['description'];
							$i++;
						}
					}
					$locations[]=array('lat'=>floatval($risp2['latitude']),'lng'=>floatval($risp2['longitude']));
				}
				for(;$i<5;$i++)
					$description[$i]="";
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
				unset($decription);
				unset($locations);
