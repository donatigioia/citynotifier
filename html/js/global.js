/* VARIABILI GLOBALI*/
var pinguMode=false;
var myTimeout=5000; //timeout per la geolocalizzazione
var localServer="http://ltw1326.web.cs.unibo.it"; //server locale
var defaultRadius=2000; //raggio della richiesta iniziale
var mainpage="http://ltw1326.web.cs.unibo.it/mainpage.php"; //pagina principale
var LatBO=44.494887; //latitudine di bologna
var LngBO=11.342616; //longitudine di bologna

/*VARIABILI PER LA SELECT DI TIPI/SOTTOTIPI*/

var problemi_ambientali=
{
        "Alluvione":"alluvione",
        "Incendio":"incendio",
        "Neve":"neve",
        "Tornado":"tornado"
};

var eventi_pubblici=
{
        "Concerto":"concerto",
        "Manifestazione":"manifestazione",
        "Partita":"partita"
};

var reati=
{
        "Attentato":"attentato",
        "Furto":"furto"
};

var emergenze_sanitarie=
{
        "Incidente":"incidente",
        "Ferito":"ferito",
        "Malore":"malore"
};

var problemi_stradali=
{
        "Buca":"buca",
        "Coda":"coda",
        "Incidente":"incidente",
        "Lavori in corso":"lavori_in_corso",
        "Strada impraticabile":"strada_impraticabile"
};

var default_t=
{
        "Seleziona sottotipo":"default"
};
var all_t=
{
        "Qualunque sottotipo":"all"
};
/*FINE VARIABILI SELECT*/

var tableCounter=0; //concorrenza tra creazioni di tabelle: se tableCounter viene incrementato tutte le tabelle precedentemente in creazione vengono bloccate
var requestCounter=0; //concorrenza tra richieste remote: se requestCounter aumenta, tutte le richieste remote più vecchie vengono ignorate
var map; //variabile globale per la mappa
var markers=[]; //array di marker
var positionMarker; //marker iniziale
var infowindows=[]; //array di infowindow
var circle; //variabile globale per il circle del marker
var geocoder = new google.maps.Geocoder();  //creo un nuovo oggetto geoCoder, una classe di googleMaps che serve per convertire indirizzi in stringhe e viceversa

/*VARIABILI PER LE IMMAGINI*/

var image1 = {
        url: 'iconefinali/markerstrada2.png',           //problemi stradali
        scaledSize: new google.maps.Size(60, 60),
};
var image2 = {
        url: 'iconefinali/markerambulanza.png',         //Emergenze sanitarie
        scaledSize: new google.maps.Size(60, 60),
};
var image3 = {
        url: 'iconefinali/markerpolice2.png',           //reati
        scaledSize: new google.maps.Size(60, 60),
};
var image4 = {
        url: 'iconefinali/markerambiente.png',          //problemi ambientali
        scaledSize: new google.maps.Size(60, 60),
};
var image5 = {
        url: 'iconefinali/markereventi.png',            //eventi pubblici
        scaledSize: new google.maps.Size(60, 60),
};
/*FINE VARIABILI PER LE IMMAGINI*/


