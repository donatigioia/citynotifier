/*variabili globali*/
var geocoder = new google.maps.Geocoder();
var img_buca="news/bucaMod.jpg";
var img_coda="news/coda-autostradaMod.jpg";
var img_incidente="news/incidenteMod.jpg";
var img_lavori="news/Lavori-in-corsoMod.jpg";
var img_strada_impraticabile="news/stradainterrottaMod.jpg";
var img_incendio="news/incendioMod.jpg";
var img_alluvione="news/alluvioniMod.jpg";
var img_neve="news/neveMod.jpg";
var img_tornado="news/tornadoMod.jpg";
var img_concerto="news/concertiMod.jpg";
var img_manifestazione="news/manifestazioneMod.jpg";
var img_partita="news/partitaMod.jpg";
var img_attentato="news/reatiMod.jpg";
var img_furto="news/reatiMod.jpg";
var img_ferito="news/sanitaMod.jpg";
var img_malore="news/sanitaMod.jpg";

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


function crea_news(eventi)
{
var attuale;
var image;
if (eventi.events.length>0)
{
	$("#newstitolo").fadeIn();
	$.each (eventi.events, function(key, val)
	                {
			switch(val.type.subtype){
			case "buca":
				image=img_buca;
				break;
			case "coda":
				image=img_coda;
				break;
			case "incidente":
				image=img_incidente;
				break;
			case "lavori_in_corso":
				image=img_lavori;
				break;
			case "strada_impraticabile":
				image=img_strada_impraticabile;
				break;
			case "incendio":
				image=img_incendio;
				break;
			case "alluvione":
				image=img_alluvione;
				break;
			case "neve":
				image=img_neve;
				break;
			case "tornado":
				image=img_tornado;
				break;
			case "concerto":
				image=img_concerto;
				break;
			case "manifestazione":
				image=img_manifestazione;
				break;
			case "partita":
				image=img_partita;
				break;
			case "attentato":
				image=img_attentato;
				break;
			case "furto":
				image=img_furto;
				break;
			case "ferito":
				image=img_ferito;
				break;
			case "malore":
				image=img_malore;
				break;
			};
				
				if(key<=2)
				{
	             	        attuale="newsinfo"+(key+1);
				$("#"+attuale).fadeIn("slow"); 
				var posiz = punto_medio(val.locations);
				attuale="news"+(key+1);
				document.getElementById(attuale).innerHTML=val.type.type.replace(/_/g,' ');
				attuale="sub"+attuale;
				document.getElementById(attuale).innerHTML=val.type.subtype.replace(/_/g,' ');
				attuale="img"+(key+1);
				$('#'+attuale).attr('src',image);
				attuale="rep"+(key+1);
				document.getElementById(attuale).innerHTML=Math.round(val.reliability * 100) / 100;
				  geocoder.geocode({'latLng': posiz}, function(results, status){
	                        if( status == google.maps.GeocoderStatus.OK){
	                               	attuale="addr"+(key+1); 
					if(results[0]){
	                                        document.getElementById(attuale).innerHTML=results[0].formatted_address;
	                                }
	                                else
	                                        document.getElementById(attuale).innerHTML="Non disponibile";
	                        }
	                });
				}
			});
	}
}
	
	
$(document).ready(function()
	{
		$("#newstitolo").hide();
		$("#newsinfo1").hide();
		$("#newsinfo2").hide();
		$("#newsinfo3").hide();
		var initialTimeMin=Math.round((new Date().getTime())/1000)-86400;
                var initialTimeMax=Math.round((new Date().getTime())/1000);
                $.ajax(
                {
                        url:localServer+"/richieste?scope=local&type=all&subtype=all&lat="+LatBO+"&lng="+LngBO+"&radius=5000&timemin="+initialTimeMin+"&timemax="+initialTimeMax+"&status=all",
                        type:"GET",
                        async: "true",
                        dataType: "json",
                        accepts: {json:"application/json"},
                        success:function(val)
                        {
                      		crea_news(val);
			},
                        error:function(str)
                        {
                        }
                });
	}
);	
