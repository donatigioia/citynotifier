/***********FUNZIONE punto_medio: CALCOLA IL PUNTO MEDIO TRA DUE latlng*************/
function punto_medio(locations){
 
        var media_lat=0;
        var media_lng=0;
        var lat=[];
        var lng=[];
        $.each(locations, function(key,latlng)
                {
                        lat[key]=this.lat;
                        lng[key]=this.lng;
               
                });
               
        for (var i=0; i<lat.length; i++)
                {
                        media_lat=media_lat+lat[i];
                        media_lng=media_lng+lng[i];
                }      
        media_lat=media_lat/lat.length;
        media_lng=media_lng/lat.length;
        var latlng = new google.maps.LatLng(media_lat, media_lng);
        return latlng;
        }

/*********FUNZIONE initial_query(): ESEGUE LE RICHIESTE LOCALI/REMOTE INIZIALI*********/
function initial_query()
{
		var initialTimeMin=Math.round((new Date().getTime())/1000)-86400;
		var initialTimeMax=Math.round((new Date().getTime())/1000)+5; //aggiungo un errore di 5 secondi in caso di rallentamenti del server
//ajax locale
                $.ajax(
                {
                        url:localServer+"/richieste?scope=local&type=all&subtype=all&lat="+positionMarker.getPosition().lat()+"&lng="+positionMarker.getPosition().lng()+"&radius="+defaultRadius+"&timemin="+initialTimeMin+"&timemax="+initialTimeMax+"&status=all",
                        type:"GET",
                        async: "true",
                        dataType: "json",
                        accepts: {json:"application/json"},
                        success:function(val)
                        {
                                crea_tabella(val);
                                mostra_eventi(val);
                        },
                        error:function(str)
                        {
                        }
                });
//ajax remoto
		   
			requestCounter=requestCounter+1;
                        var test=requestCounter; //setto il requestCounter
			$.ajax(
                {
                        url:localServer+"/richieste?scope=remote&type=all&subtype=all&lat="+positionMarker.getPosition().lat()+"&lng="+positionMarker.getPosition().lng()+"&radius="+defaultRadius+"&timemin="+initialTimeMin+"&timemax="+initialTimeMax+"&status=all",
			type:"GET",
                        async: "true",
                        dataType: "json",
                        accepts: {json:"application/json"},
                        success:function(val)
                        {
			if (requestCounter==test) //non ci sono state richieste successive: posso procedere
				{
				crea_tabella(val);
               	        	mostra_eventi(val);
				}
                      	},
                        error:function(str)
                        {
			
                        }
                });

}

function crea_tabella(eventi)
{
	tableCounter++;
	var test=tableCounter; //setto il tableCounter
	var row=1;
	var row_archived=1;
	var timeout=2000;
        document.getElementById('tabella').innerHTML="";//svuota la tabella da eventuali elementi precedenti
	document.getElementById('tabella_arch').innerHTML="";
	$.each(eventi.events, function(key,val)
        {
                if (val.status!="archived")
                {
                        latlng=punto_medio(val.locations);
                        type=val.type.type.replace(/_/g,' ');
			subtype=val.type.subtype.replace(/_/g,' ');
                        $('#tabella')
                                .append($('<tr>')
                                .append($('<td></td>').text(type))
                                .append($('<td></td>').text(subtype))
                                .append($('<td></td>').text("...")) 
                                .append($('<td></td>').text(val.status))
                                .append($('<td></td>').text(val.description[0]+". "+val.description[1]+". "+val.description[2]+". "+val.description[3]+". "+val.description[4]))
                                        );
                }
                else
                {
                        latlng=punto_medio(val.locations);
                        type=val.type.type.replace(/_/g,' ');
			subtype=val.type.subtype.replace(/_/g,' ');
                        $('#tabella_arch')
                                .append($('<tr>')
                                .append($('<td></td>').text(type))
                                .append($('<td></td>').text(subtype))
                                .append($('<td></td>').text("..."))
                                .append($('<td></td>').text(val.status))
                                .append($('<td></td>').text(val.description[0]+". "+val.description[1]+". "+val.description[2]+". "+val.description[3]+". "+val.description[4]))
                                        );

                }
        });
	/*riempimento indirizzi*/
	$.each(eventi.events, function(key,val)
        {
                    if (val.status!="archived")
                    {
                                setTimeout(function(){
                                if (test==tableCounter) //non ci sono tabelle in creazione: posso procedere
                                {
                                        var position=punto_medio(val.locations);
                                        geocoder.geocode({'latLng': position},function(results,status){
                                	$( "#tabella tr:nth-child("+row+") td:nth-child(3)").text(results[0].formatted_address);
					row++;
                                });}},timeout);
                                timeout=timeout+2000;
                        }
                        else
                        {
                                setTimeout(function(){
                                if (test==tableCounter)
                                {
                                        var position=punto_medio(val.locations);
                                        geocoder.geocode({'latLng': position},function(results,status){
                                	$( "#tabella_arch tr:nth-child("+row_archived+") td:nth-child(3)").text(results[0].formatted_address);
					row_archived++;
				});}},timeout);
                                timeout=timeout+2000;
                        }
        });

}

/****************FUNZIONE close_infowindows(): chiude le infowindows del vettore markers[]*********/
			function close_infowindows()
			{
                	for (var i = 0; i < markers.length; i++)
				{
                    		markers[i].infowindow.close();
                		}
					positionMarker.infowindow.close();  
			}

/************FUNZIONE getCity(): CONVERTE LA POSIZIONE DEL MARKER IN INDIRIZZI E COORDINATE*******/

//funzione getCity: prende delle coordinate di Google Maps (LatLng googleCoords) e le inserisce all'interno dei rispettivi tag DIV nell'HTML.
//  getCity trasforma anche le coordinate in indirizzo, anch'esso inserito nell'opportuno tag DIV

function getCity(googleCoords) 
{
	geocoder.geocode({'latLng': googleCoords}, function(results, status) 
	{
		if (status == google.maps.GeocoderStatus.OK) 
		{
			if (results[0]) 
			{
				//se la chiamata geocode è terminata correttamente vengono inseriti i vari campi nei rispettivi DIV
				var a=document.getElementsByClassName("posADDR");
				i=a.length;     
				while (i--)
				{
					a[i].innerHTML=results[0].formatted_address;
				}
				var b=document.getElementsByClassName("posLAT");
				i=b.length;
				while (i--)
				{
					b[i].innerHTML=googleCoords.lat();
				}

				var c=document.getElementsByClassName("posLON");
				i=c.length;
				while (i--)
				{
					c[i].innerHTML=googleCoords.lng();
				}

			} 
		}
	});
}

/********FUNZIONE cerchio(): modifica il raggio del cerchio al variare del div radius*********/
function cerchio()
{
	var preRaggio=$("#radius").val();
	modRaggio=Number(preRaggio);
	circle.setRadius(modRaggio);
}

/**********FUNZIONE updatePosition(): DATE LATITUDINE E LONGITUDINE, SETTA LA POSIZIONE DEL MARKER A QUELLE COORDINATE**********/
function updatePosition(latitude,longitude)
{
    var latlng = new google.maps.LatLng(latitude, longitude);
    positionMarker.setPosition(latlng);
    getCity(latlng);
    map.panTo(latlng);
}
/***********FUNZIONE addMarker(): inserisce il marker iniziale sulla mappa alla posizione specificata da googleCoords************/
function addMarker(googleCoords)  
{
		var markerOpts = 
		{
	  		draggable:true,
	  		animation: google.maps.Animation.DROP, 
	 		position: googleCoords,
	  		map: map,
			
		};
		
		positionMarker = new google.maps.Marker(markerOpts); //viene creato un nuovo oggetto marker con le opzioni precedentemente impostate
		
		//Variabile per circonferenza intorno a marker iniziale
		circle = new google.maps.Circle(
		{
			strokeColor: "#A8A8A8",
			strokeOpacity: 0.8,
			strokeWeight: 2,
			map: map,
			radius: 400,    
			fillColor: '#C8C8C8'
		
		});	
		circle.bindTo('center', positionMarker, 'position');
		
		// Creazione infowindow per marker iniziale

		
		var descrizione0 = 
                        	   '<div class="infowindow">'+
                       	 		  '<center>'+ 
                        			  '<h3>'+'<b> La tua posizione</b>'+'</h3>'+
                        			  '<h5 class="well">Spostami dove vuoi per segnalare nuovi eventi</h5>'+
                        		  '</center>'+
                        	   '</div>'

		var infowindow0 = new google.maps.InfoWindow(
       		 {
      	          content: descrizione0,
      	          maxWidth: 200
      		  });
		positionMarker.infowindow=infowindow0;
		//Viene legato il maker alla funzione getCity in questo modo ogni volta che il marker viene spostato i DIV vengono aggiornati
		google.maps.event.addListener(positionMarker, 'dragend', function() 
		{ 	
			var point = new google.maps.LatLng(positionMarker.getPosition().lat(), positionMarker.getPosition().lng());
			getCity(point);
		});
		google.maps.event.addListener(positionMarker, 'click', function()  
    		{	
			for (var i = 0; i < markers.length; i++)
				markers[i].infowindow.close();
			positionMarker.infowindow.open(map,positionMarker);

  		});

		//spostare il marker iniziale col tasto destro 
		google.maps.event.addListener(map, 'rightclick', function(MouseEvent) 
			{
				positionMarker.setPosition(MouseEvent.latLng);
				map.panTo(MouseEvent.latLng);
				var point = new google.maps.LatLng(positionMarker.getPosition().lat(), positionMarker.getPosition().lng());
				getCity(point);
				delete_marker();
				initial_query();
			});			
		//centrare la mappa quando il marker viene spostato
		google.maps.event.addListener(positionMarker, 'dragend', function() 
		{
				map.panTo(positionMarker.getPosition());
		});

/************FUNZIONE PER IL CERCA***********/

		var input = (document.getElementById('pac-input'));
		map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);
		var searchBox = new google.maps.places.SearchBox(input);
		
  		google.maps.event.addListener(searchBox, 'places_changed', function() 
  		{
			var places= searchBox.getPlaces();
			var place = places[0];	
			var position= place.geometry.location;
			positionMarker.setPosition(position);
			map.panTo(position);
			map.fitBounds(circle.getBounds());
			getCity(position);
			delete_marker();
			initial_query();
		})	

}

/********FUNZIONE notifica():  per notificare lo stato di un evento*********/

function notifica(status,id)
{
	var type,subtype;
	for (i=0;i<markers.length;i++)
	{
		if (markers[i].id==id)
		{
			event_lat=markers[i].getPosition().lat();
			event_lng=markers[i].getPosition().lng();
			type=markers[i].type;
			subtype=markers[i].subtype;
			break;
		}
	}
	var notifica=new Object();
	notifica.event_id=id;
	notifica.type=new Object();
	notifica.type.type=type;
	notifica.type.subtype=subtype;
	notifica.status=status;
	notifica.lat= positionMarker.getPosition().lat();
	notifica.lng= positionMarker.getPosition().lng();
	notifica.event_lat=event_lat;
	notifica.event_lng=event_lng;
	notifica.description=$("#descNotifica").val();
	var strNotifica= JSON.stringify(notifica);
	$.ajax(
	{
		//Inizia una richiesta ajax, ovvero effettua una richiesta ad uno script
		type: 'POST',	//Tipo della richiesta (GET/POST)
		url: 'notifica',	//URL della risorsa (script, server...) a cui effettuiamo la richiesta
		async: true,		//La richiesta è ASINCRONA
		contentType: 'application/json; charset=utf-8', //invio alla risorsa del JSON scritto in UTF-8
		dataType: 'json',	//mi aspetto che il dato ricevuto come risposta sarâˆšâ€  anch'esso di tipo json
		data: strNotifica,		//il dato inviato dalla richiesta ajax
		success: function(response)
		{ //in caso di successo della richiesta viene eseguita questa funzione
		if (response['result']=="notifica evento avvenuta con successo")			
			{
			$("#sucNotifica").fadeIn(); 
        		setTimeout(function(){$("#sucNotifica").fadeOut()}, 2000);
			delete_marker();
			initial_query();
			}
		else if(response['result']=="impossibile notificare, evento troppo distante")
			{
			$("#errNotificaDist").fadeIn();
                        setTimeout(function(){$("#errNotificaDist").fadeOut()}, 2000);
			}
		else if(response['result']=="non hai i permessi per effettuare questa operazione")
			{
			$("#errNotificaPermessi").fadeIn();
                        setTimeout(function(){$("#errNotificaPermessi").fadeOut()}, 2000);
			}
		 else if(response['result']=="evento già archiviato")
                        {
                        $("#errArchiviato").fadeIn();
                        setTimeout(function(){$("#errArchiviato").fadeOut()}, 2000);
			}
		else
			{
			$("#errNotifica").fadeIn();
                         setTimeout(function(){$("#errNotifica").fadeOut()}, 2000);
			}
		},
			error: function(response)
			{
			 $("#errNotifica").fadeIn(); 
        		 setTimeout(function(){$("#errNotifica").fadeOut()}, 2000);

			}
	})			
}	
	
/**************FUNZIONE mostra_eventi(): Dato un array di eventi, li visualizza come marker sulla mappa*************/
	
function mostra_eventi(val) 
{
	var i=0;
	var img;
	var found=0;
	if (markers.length==0) //se l'array dei marker è vuoto metto tutto nell'array
	{
		$.each (val.events, function(key, val)
		{
			if(val.status!='archived')
			{
				if (val.type.type=='problemi_stradali')
					img= image1;
				else if (val.type.type=='emergenze_sanitarie')
					img= image2;
				else if (val.type.type=='reati')
					img= image3;
				else if (val.type.type=='problemi_ambientali')
					img= image4;
				else if (val.type.type=='eventi_pubblici')
					img= image5;

				var posiz = punto_medio(val.locations);
				var newMarker = new google.maps.Marker(
				{
					id: val.event_id,
					type: val.type.type,
					subtype: val.type.subtype,
					draggable: false,
					animation: google.maps.Animation.DROP,
					position: posiz,
					map: map,
					icon: img	
				});
			
				type=val.type.type.replace(/_/g,' '); //toglie i _
				subtype=val.type.subtype.replace(/_/g,' '); //toglie i _
			
				/*info window per eventi locali*/
				
				var descrizione =
							'<div class="infowindow finestra finestra_overflow">'+    
									'<center><h3 class="finestra_divisore_top"><b>Info eventi</b></h3></center>'+
									'<div class="row">'+	
										'<div class="col-md-6">'+
											'<div>'+
												'<h6><b>Tipo :</b></h6></dt><dd><pre>'+type+'</pre>'+
												'<h6><b>Sottotipo :</b></h6><pre>'+subtype+'</pre>'+
												'<h6><b>N° notifiche : </b><span class="badge">'+val.number_of_notifications+'</span></h6>'+
												'<h6><b>Affidabilità : </b><span class="badge">'+val.reliability+'</span></h6>'+
											'</div>'+
											
										'</div>'+
										'<div class="col-md-6">'+
											'<h6><b>Stato : </b><span class="badge">'+val.status+'</span></h6>'+
											'<div class="textarea_mobile">'+
												'<h6><b>Descrizioni :</b></h6><pre>'+
												'<ul class="finestra_descrizioni ">'+
													'<li>'+val.description[0]+'</li>'+
													'<li>'+val.description[1]+'</li>'+
													'<li>'+val.description[2]+'</li>'+
													'<li>'+val.description[3]+'</li>'+
													'<li>'+val.description[4]+'</li>'+ 
												'</ul></pre>'+
											'</div>'+
										'</div>'+
									'</div>'+
										'<div class="finestra_divisore_bot">'+
											'<div class="row">'+
												'<div class="col-md-6 textarea_mobile">'+
													'<div>'+'<center><h6><b>Aggiungi descrizione :</b></h6>'+
														'<textarea rows="3" class="form-control finestra_textarea " placeholder="Descrizione" id="descNotifica"></textarea>'+	
													'</center></div>'+
												'</div>'+
												'<div class="col-md-6">'+
													'<div><center><h6><b>Segnala come :</b></h6>'+
														'<button type="button" class="btn btn-default btn-xs btn-block btn btn-success" id="'+val.event_id+'" onclick="notifica(\'open\',id)">Aperto</button>'+
														'<button type="button" class="btn btn-default btn-xs btn-block btn btn-danger" id="'+val.event_id+'" onclick="notifica(\'closed\',id)">Chiuso</button>'+
														'<button type="button" class="btn btn-default btn-xs btn-block btn btn-warning" id="'+val.event_id+'" onclick="notifica(\'archived\',id)">Archivia</button>'+
													'</center></div>'+
												'</div>'+
											'</div>'+	
										'</div>'+	
								'</div>'

				var infowindow = new google.maps.InfoWindow(
				{
					content: descrizione
				});
				newMarker.infowindow=infowindow;
				google.maps.event.addListener(newMarker, 'click', function()  //ascoltatore per aprire l event window relativa
					{
						close_infowindows();
						newMarker.infowindow.open(map,newMarker);
					});
				markers.push(newMarker);
			}	
		});	
	}
	else
	{
		$.each (val.events, function(key, val)
		{
			found=0;
			for (i=0;i<markers.length;i++)
			{
				if(markers[i].id==val.event_id) //ho trovato un marker da aggiornare
				{
					type=val.type.type.replace(/_/g,' '); //toglie i _
					subtype=val.type.subtype.replace(/_/g,' '); //toglie i _
						
					/*aggiornamento infowindow eventi locali*/
				
					var descrizione =
							  '<div class="infowindow finestra finestra_overflow">'+    
								'<center><h3 class="finestra_divisore_top"><b>Info eventi</b></h3></center>'+
								'<div class="row">'+	
									'<div class="col-md-6">'+
										'<div>'+
											'<h6><b>Tipo :</b></h6></dt><dd><pre>'+type+'</pre>'+
											'<h6><b>Sottotipo :</b></h6><pre>'+subtype+'</pre>'+
											'<h6><b>N° notifiche : </b><span class="badge">'+val.number_of_notifications+'</span></h6>'+
											'<h6><b>Affidabilità : </b><span class="badge">'+val.reliability+'</span></h6>'+
										'</div>'+
										
									'</div>'+
									'<div class="col-md-6">'+
										'<h6><b>Stato : </b><span class="badge">'+val.status+'</span></h6>'+
										'<div class="textarea_mobile">'+
											'<h6><b>Descrizioni :</b></h6><pre>'+
											'<ul class="finestra_descrizioni ">'+
												'<li>'+val.description[0]+'</li>'+
												'<li>'+val.description[1]+'</li>'+
												'<li>'+val.description[2]+'</li>'+
												'<li>'+val.description[3]+'</li>'+
												'<li>'+val.description[4]+'</li>'+ 
											'</ul></pre>'+
										'</div>'+
									'</div>'+
								'</div>'+
									'<div class="finestra_divisore_bot">'+
										'<div class="row">'+
											'<div class="col-md-6 textarea_mobile">'+
												'<div>'+'<center><h6><b>Aggiungi descrizione :</b></h6>'+
													'<textarea rows="3" class="form-control finestra_textarea " placeholder="Descrizione" id="descNotifica"></textarea>'+	
												'</center></div>'+
											'</div>'+
											'<div class="col-md-6">'+
												'<div><center><h6><b>Segnala come :</b></h6>'+
													'<button type="button" class="btn btn-default btn-xs btn-block btn btn-success" id="'+val.event_id+'" onclick="notifica(\'open\',id)">Aperto</button>'+
													'<button type="button" class="btn btn-default btn-xs btn-block btn btn-danger" id="'+val.event_id+'" onclick="notifica(\'closed\',id)">Chiuso</button>'+
													'<button type="button" class="btn btn-default btn-xs btn-block btn btn-warning" id="'+val.event_id+'" onclick="notifica(\'archived\',id)">Archivia</button>'+
												'</center></div>'+
											'</div>'+
										'</div>'+	
									'</div>'+	
							'</div>'

					var infowindow = new google.maps.InfoWindow(
					{
						content: descrizione
					});
					markers[i].infowindow=infowindow;
					found=1;
					break;
				}
			}
			if (found==0) //non ho trovato marker da aggiornare: faccio la push del marker
			{
				if(val.status!='archived')
				{
					if (val.type.type=='problemi_stradali')
						img= image1;
					else if (val.type.type=='emergenze_sanitarie')
						img= image2;
					else if (val.type.type=='reati')
						img= image3;
					else if (val.type.type=='problemi_ambientali')
						img= image4;
					else if (val.type.type=='eventi_pubblici')
						img= image5;
					var posiz = new google.maps.LatLng(val.locations[0].lat, val.locations[0].lng);
					var newMarker = new google.maps.Marker(
					{
						id: val.event_id,
						type: val.type.type,
						subtype: val.type.subtype,
						draggable: false,
						animation: google.maps.Animation.DROP,
						position: posiz,
						map: map,
						icon: img	
					});
					type=val.type.type.replace(/_/g,' '); //toglie i _
					subtype=val.type.subtype.replace(/_/g,' '); //toglie i _

					/* creazione infowindow per eventi remoti */
					
					var descrizione =
							   '<div class="infowindow finestra finestra_overflow">'+    
									'<center><h3 class="finestra_divisore_top"><b>Info eventi</b></h3></center>'+
									'<div class="row">'+	
										'<div class="col-md-6">'+
											'<div>'+
												'<h6><b>Tipo :</b></h6></dt><dd><pre>'+type+'</pre>'+
												'<h6><b>Sottotipo :</b></h6><pre>'+subtype+'</pre>'+
												'<h6><b>N° notifiche : </b><span class="badge">'+val.number_of_notifications+'</span></h6>'+
												'<h6><b>Affidabilità : </b><span class="badge">'+val.reliability+'</span></h6>'+
											'</div>'+
											
										'</div>'+
										'<div class="col-md-6">'+
											'<h6><b>Stato : </b><span class="badge">'+val.status+'</span></h6>'+
											'<div class="textarea_mobile">'+
												'<h6><b>Descrizioni :</b></h6><pre>'+
												'<ul class="finestra_descrizioni ">'+
													'<li>'+val.description[0]+'</li>'+
													'<li>'+val.description[1]+'</li>'+
													'<li>'+val.description[2]+'</li>'+
													'<li>'+val.description[3]+'</li>'+
													'<li>'+val.description[4]+'</li>'+ 
												'</ul></pre>'+
											'</div>'+
										'</div>'+
									'</div>'+
										'<div class="finestra_divisore_bot">'+
											'<div class="row">'+
												'<div class="col-md-6 textarea_mobile">'+
													'<div>'+'<center><h6><b>Aggiungi descrizione :</b></h6>'+
														'<textarea rows="3" class="form-control finestra_textarea " placeholder="Descrizione" id="descNotifica"></textarea>'+	
													'</center></div>'+
												'</div>'+
												'<div class="col-md-6">'+
													'<div><center><h6><b>Segnala come :</b></h6>'+
														'<button type="button" class="btn btn-default btn-xs btn-block btn btn-success" id="'+val.event_id+'" onclick="notifica(\'open\',id)">Aperto</button>'+
														'<button type="button" class="btn btn-default btn-xs btn-block btn btn-danger" id="'+val.event_id+'" onclick="notifica(\'closed\',id)">Chiuso</button>'+
														'<button type="button" class="btn btn-default btn-xs btn-block btn btn-warning" id="'+val.event_id+'" onclick="notifica(\'archived\',id)">Archivia</button>'+
													'</center></div>'+
												'</div>'+
											'</div>'+	
										'</div>'+	
								'</div>'

							
					var infowindow = new google.maps.InfoWindow(
					{
						content: descrizione
					});
					newMarker.infowindow=infowindow;
					google.maps.event.addListener(newMarker, 'click', function()  //ascoltatore per aprire l event window relativa
					{
						close_infowindows();
						newMarker.infowindow.open(map,newMarker);
					});
					markers.push(newMarker);
				}
			}
		});
	}
}

/*********FUNZIONE displayError(): in caso di mancata geolocalizzazione mostra il relativo DIV di errore***********/

function displayError()
{
$("#errGeolocation").fadeIn();
setTimeout(function(){$("#errGeolocation").fadeOut()}, 2000);
};


/***************FUNZIONE createMap(): Crea la mappa e il marker iniziale**********/
function createMap(googleCoords) 
{
		// crea un oggetto LatLng
		// opzioni da passare per la creazione della mappa
		var mapOpts = 
		{
			zoom: 15, //zoom della mappa
			center: googleCoords, // centro mappa
			mapTypeId: google.maps.MapTypeId.ROADMAP //tipo mappa [ROADMAP - SATELLITE - HYBRID]
		};
			var mapDiv = document.getElementById("googleMap");
			//crea oggetto mappa
			map = new google.maps.Map(mapDiv, mapOpts);
			addMarker(googleCoords);
			initial_query();		
			getCity(googleCoords);
}

/********************FUNZIONE displayLocation(): in caso di geolocalizzazione, sposta il marker nella posizione rilevata***********/

function displayLocation (position) 
{
	
	updatePosition(position.coords.latitude,position.coords.longitude);
	delete_marker()
	initial_query();
}


/****************FUNZIONE initialize(): DETERMINA LA POSIZIONE E CHIAMA LA CREAZIONE DELLA MAPPA***********/
function initialize()
{
	var lastLat=document.getElementById("lastLat").innerHTML;	
	var lastLng=document.getElementById("lastLng").innerHTML;
	if(lastLat && lastLng && lastLat!=0 && lastLng!=0){ //se esiste ultima posizione
		/*creiamo GoogleCoords*/
		var googleCoords= new google.maps.LatLng(lastLat,lastLng);
		geocoder.geocode({'latLng': googleCoords}, function(results, status){
			if( status == google.maps.GeocoderStatus.OK){
				if(results[0]){
					document.getElementById("lastAddr").innerHTML=results[0].formatted_address;
				}
				else
					document.getElementById("lastAddr").innerHTML="Non disponibile";
			}
		});
		//creo il marker all'ultima lat/lng
		createMap(googleCoords);
	}
	else
	{
		var googleCoords= new google.maps.LatLng(LatBO,LngBO); //se non esiste l'ultima posizione viene scelta quella del centro di Bologna
		createMap(googleCoords);
	}
	var usr=document.getElementById("username").innerHTML;
	if (usr=="pingu")
	{
	pinguMode=true;
	image1 = {
        url: 'pingu.jpg',           //problemi stradali
        scaledSize: new google.maps.Size(60, 60),
};
 image2 = {
        url: 'pingu.jpg', 
	scaledSize: new google.maps.Size(60, 60),
};
 image3 = {
	url: 'pingu.jpg', 
	scaledSize: new google.maps.Size(60, 60),
};
image4 = {
	url: 'pingu.jpg', 
	scaledSize: new google.maps.Size(60, 60),
};
image5 = {
	url: 'pingu.jpg', 
	scaledSize: new google.maps.Size(60, 60),
};
	}
	// individuare il supporto del browser per Geolocation
	if (!(navigator.geolocation == 'undefined')) 
	{
		navigator.geolocation.getCurrentPosition(displayLocation, displayError,{timeout:myTimeout});
		
	} 
	
}
$(document).ready(
function(){

	initialize();
	$("#overlay").hide();
	$(".apri").click(
    	 function(){
         	$('#overlay').fadeIn('fast');
         	$('#box').fadeIn('slow');
    	 });
 
	$(".chiudi").click(
     		function(){
     		$('#overlay').fadeOut('fast');
     		$('#box').hide();
     	});

	$("#overlay2").hide();
	$(".apri2").click(
    	 function(){
         	$('#overlay2').fadeIn('fast');
         	$('#box2').fadeIn('slow');
    	 });
 
	$(".chiudi2").click(
     		function(){
     		$('#overlay2').fadeOut('fast');
     		$('#box2').hide();
     	});


 });




