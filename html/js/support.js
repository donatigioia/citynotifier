//effettua il refresh della pagina
function refresh()
{
document.location.href=mainpage;
}

//funzione di supporto a delete_marker
function setAllMap(map) 
{
  for (var i = 0; i < markers.length; i++) 
	{
		markers[i].setMap(map);
	}
}
//funzione di supporto a delete_marker 
function clear()
{
	setAllMap(null);
}

//rimuove tutti i marker dalla mappa
function delete_marker()
{
	clear();
	infowindows=[];
	markers=[];
}

$(document).ready(function () 
{
	$(".alertdiv").hide();//nasconde tutti i div di alert

	$(function() //setta il calendario
	{
		$('#datetimepicker1').datetimepicker(
		{
		language: 'pt-BR'
		});
	});
	$("#tipo,#tiporec").change(function() //popola la select
	{			
		var type =$(this).attr('id');
		var $el;
		if (type=="tipo") 
		$el = $("#sottotipo");
		else
		$el= $("#sottotiporec");
		$el.empty(); // remove old options
		type=$(this).val();	
		switch (type)
		{
			case"problemi_stradali":
			$.each(problemi_stradali, function(key, value) 
			{
				$el.append($("<option></option>").attr("value", value).text(key));
			});
			break;
			case "emergenze_sanitarie":
			$.each(emergenze_sanitarie, function(key, value) 
			{
				$el.append($("<option></option>").attr("value", value).text(key));
			});
			break;
			case "reati":
			$.each(reati, function(key, value) 
			{
				$el.append($("<option></option>").attr("value", value).text(key));
			});
			break;
			case "problemi_ambientali":
			$.each(problemi_ambientali, function(key, value) 
			{
				$el.append($("<option></option>").attr("value", value).text(key));
			});
			break;
			case "eventi_pubblici":
			$.each(eventi_pubblici, function(key, value) 
			{
				$el.append($("<option></option>").attr("value", value).text(key));
			});
			break;
			case "default":
			$.each(default_t, function(key, value) 
			{
				$el.append($("<option></option>").attr("value", value).text(key));
			});
			break;
			case "all":
			$.each(all_t,function(key,value) 
			{
				$el.append($("<option></option>").attr("value",value).text(key));

			});
		};

	});


		//invio informazioni delle notifiche al script invio.php

	$("#invia_notifica").click(function()
	{
		//prendo da file main.php le coordinate,tipo notifica e descrizione
		var notifica= new Object();
		notifica.type= new Object();
		notifica.type.type=$("#tipo").val();
		if (notifica.type.type=="default") 
		{
			$("#errselect").fadeIn(); 
        		setTimeout(function(){$("#errselect").fadeOut()}, 2000);

			return;
		}
		notifica.type.subtype= $("#sottotipo").val();
		notifica.lat= document.getElementById("sendLAT").innerHTML;
		notifica.lng= document.getElementById("sendLON").innerHTML;
		notifica.description=$("#descrizione").val();
		var strNotifica= JSON.stringify(notifica);

		$.ajax(
		{
			type: 'POST',	
			url: 'segnalazione',
			async: true,	
			contentType: 'application/json; charset=utf-8', 
			dataType: 'json',	
			data: strNotifica,	
			success: function(response)
			{ //in caso di successo della richiesta viene eseguita questa funzione
				if (response['result']=="hai già notificato questo evento")
				{
				$("#errNoFlood").fadeIn();
				setTimeout(function(){$("#errNoFlood").fadeOut()},2000);
				}
				if (response['result']=="nuova segnalazione aperta con successo")
				{
					
					$("#sucInvio0").fadeIn(); 
        				setTimeout(function(){$("#sucInvio0").fadeOut()}, 2000);

				}
				else if (response['result']=="segnalazione di un evento già in memoria avvenuta con successo")
				{
					$("#sucInvio1").fadeIn(); 
        				setTimeout(function(){$("#sucInvio1").fadeOut()}, 2000);
				}
				delete_marker();
				initial_query();
			},
			error: function(errcode,robba,altrarobba)
			{		
				$("#errInvio").fadeIn(); 
        			setTimeout(function(){$("#errInvio").fadeOut()}, 2000);
			}
		})
	});
	//ricezione notifiche
	$("#cercaEventi").click(function()
	{
		var tipo_r=$("#tiporec").val();
		var sottotipo_r=$("#sottotiporec").val();
		var lat_r=document.getElementById("recLAT").innerHTML;
		var lon_r=document.getElementById("recLON").innerHTML;
		var radius_r=$("#radius").val();
		var stato_r=$("#stato").val();
		var prova=new Date();
		var now=Math.round(prova.getTime()/1000);
		var years=$("#datetimepicker1").data("DateTimePicker").date.year();
		var months=$("#datetimepicker1").data("DateTimePicker").date.month();
		var days=$("#datetimepicker1").data("DateTimePicker").date.date();
		var hours=$("#datetimepicker1").data("DateTimePicker").date.hour();
		var minutes=$("#datetimepicker1").data("DateTimePicker").date.minute();
		var seconds=$("#datetimepicker1").data("DateTimePicker").date.second();
		var date=new Date(years,months,days,hours,minutes,seconds);
		var t_req=Math.round(date.getTime()/1000);
		var query = localServer + "/richieste" + "?scope=" + "local" + "&type=" + tipo_r + 
		"&subtype=" + sottotipo_r + "&lat=" + lat_r + "&lng=" + 
		lon_r + "&radius=" + radius_r + "&timemin=" + t_req + 
		"&timemax=" + now + "&status=" + stato_r;
		requestCounter=requestCounter+1;
		var test=requestCounter;
		$.ajax(
		{
			url:query,
			type:"GET",
			async: "true",
			dataType: "json",
			accepts: {json:"application/json"},
			success:function(val)
			{
				if (test==requestCounter){
				delete_marker();
				mostra_eventi(val);
				crea_tabella(val);}
			},
			error:function(str)
			{
				$("#errCerca").fadeIn(); 
        			setTimeout(function(){$("#errCerca").fadeOut()}, 2000);
			}
		});
		query = localServer + "/richieste" + "?scope=" + "remote" + "&type=" + tipo_r +
                "&subtype=" + sottotipo_r + "&lat=" + lat_r + "&lng=" +
                lon_r + "&radius=" + radius_r + "&timemin=" + t_req +
                "&timemax=" + now + "&status=" + stato_r;
		requestCounter++;
		var test=requestCounter;
		$.ajax(
                {
                        url:query,
                        type:"GET",
                        async: "true",
                        dataType: "json",
                        accepts: {json:"application/json"},
                        success:function(val)
                        {
			if(test==requestCounter){
                                mostra_eventi(val);
                                crea_tabella(val);}
                        },
                        error:function(str)
                        {
                                $("#errCerca").fadeIn();
                                setTimeout(function(){$("#errCerca").fadeOut()}, 2000);
                        }
                });

	});
});













