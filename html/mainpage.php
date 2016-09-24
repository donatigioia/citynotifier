<?php
	session_start();
//se non c'è la sessione registrata

	if (0)#!isset($_SESSION['ID'])) 
	{
		header("location: index.html");
	}
?>
<!DOCTYPE HTML>
<html>
	<head>
		<!--<script src="http://maps.googleapis.com/maps/api/js?key=AIzaSyDr4I11qsJn4sGgpIQbDkq6zQHc2hF5P_U&sensor=true"></script>-->
		<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=places"></script>
		<link rel="stylesheet" type="text/css" media="all" href="dist/css/bootstrap.css"></link>
		<link rel="stylesheet" type="text/css" media="all" href="dist/css/bootstrap-theme.css"></link>
		<link rel="stylesheet" type="text/css" media="all" href="css/bootstrap-datetimepicker.min.css"></link>
		<link rel="stylesheet" type="text/css" media="all" href="css/classi.css"></link>
		<script type="text/javascript"
			src="http://cdnjs.cloudflare.com/ajax/libs/jquery/1.8.3/jquery.min.js">
		</script> 
        <link rel="icon" href="http://ltw1326.web.cs.unibo.it/img.ico" />		
		<script type="text/javascript" src="dist/js/bootstrap.min.js"></script>
		<script type="text/javascript" src="js/global.js"></script>
		<script type="text/javascript" src="js/moment.js"></script>
		<script type="text/javascript" src="js/bootstrap-datetimepicker.min.js"></script>
		<script type="text/javascript" src="js/mapHandler.js"></script>
		<script type="text/javascript" src="js/logout.js"></script>
		<script type="text/javascript" src="js/support.js"></script>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta charset="utf-8">
		<title>CityNotifier</title>
	</head>
	<body id="log">
		<!--NAVBAR-->
			<div class="navbar navbar-default navbar-margin" role="navigation ">
				<div class="container-fluid">  
					<div class="navbar-header">
						<!-- bottone navbar per il mobile -->		
						<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navmobile_main">
        					<span class="icon-bar"></span>
       						<span class="icon-bar"></span>
       						<span class="icon-bar"></span>
     					</button>
						<!-- ricarica la pagina al click su CityNotifier -->
			       		<a class="navbar-brand" onclick="refresh()" href="#">CityNotifier</a>
					</div>
					<!-- NAVBAR MOBILE -->
					<div class="collapse navbar-collapse navbar-right" id="navmobile_main">
						<ul class="nav navbar-nav">
							<!-- INZIO INFO UNTENTE -->
							<li class="dropdown">
								<a href="#" class="dropdown-toggle" data-toggle="dropdown"><span class="bianco glyphicon glyphicon-user"></span><span id="username" class="bianco"><?php session_start(); echo $_SESSION['username'];?></span></a>
								<ul class="dropdown-menu mobile-bianco">
									<div class="infoutente"><b>Assiduità: </b ><span class="label label-default"><?php session_start(); echo $_SESSION["ass"];?></span></div>
									<div class="divider"></div>
									<div class="infoutente"><b>Reputazione: </b><span class="label label-default"><?php session_start(); echo $_SESSION["rep"];?></span></div>
									<div class="divider"></div>
							        <div class="infoutente">
										<p><b>Ultima posizione: </b></p>
										<div class="well grigio_scuro">
											<p><b>Indirizzo: </b><span id="lastAddr"></span></p>
											<p><b>Latitudine : </b><span id="lastLat"><?php session_start(); echo $_SESSION["last_lat"];?></span></p>
											<p><b>Longitudine: </b><span id="lastLng"><?php session_start(); echo $_SESSION["last_lng"];?></span></p>
										</div>	
									</div>
									<div class="divider"></div>
									<div class="bottone_logout">
										<button onclick="logout()" type="button" class="btn btn-default btn-default btn-block"><b>Esci</b></button>
									</div>
								</ul>
										
							</li>
							<!-- FINE INFO UTENTE -->
							<!--FORM CERCA EVENTO-->   
							<li class="dropdown">
								<a href="#" class="dropdown-toggle " data-toggle="dropdown"><span class="bianco">Cerca evento</span></a>
								<div class="tenda_menu dropdown-menu">
									<form>
										<div class="form-group mobile-bianco tenda_menu2">		
											<select name="tiporec" id="tiporec" class="form-control menu_cercaesegnala">
												<option value="all" selected>Qualunque tipo</option>
												<option value="problemi_stradali">Problemi Stradali</option>
												<option value="emergenze_sanitarie">Emergenze Sanitarie</option>
												<option value="reati">Reati</option>
												<option value="problemi_ambientali">Problemi Ambientali</option>
												<option value="eventi_pubblici">Eventi Pubblici </option>
											</select>
											<select name="sottotiporec" id="sottotiporec" class="form-control menu_cercaesegnala">
												<option selected="" value="all">Qualunque sottotipo</option>
											</select>
											<div class="form-group">
												<div class='input-group date' id='datetimepicker1'>
													<input type='text' class="form-control" />
													<span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span>
													</span>
												</div>
											</div>
											<script type="text/javascript">
												$(function () 
												{
													var yesterday=new Date();
													yesterday.setDate(yesterday.getDate()-1);
													$('#datetimepicker1').datetimepicker({defaultDate:yesterday});
												});
											</script>
											<div class="menu_cercaesegnala"><strong>Posizione:</strong> <span class="posADDR" id="recADDR"> </span></div>
											<div class="menu_cercaesegnala"><strong>Latitudine:</strong> <span class="posLAT" id="recLAT"></span></div>
											<div class="menu_cercaesegnala"><strong>Longitudine:</strong> <span class="posLON" id="recLON"></span></div>
											<div class="menu_cercaesegnala"><strong>Raggio (metri):</strong> <input type="number" id="radius" onchange="cerchio()" min="100" max="5000" step="50" class="form-control" ></div> 
											<div><strong>Stato</strong>:</div>
											<select name="stato" id="stato" class="form-control menu_cercaesegnala">
												<option value="all">All</option>
												<option value="open"> Open</option>
												<option value="closed">Closed</option>
												<option value="skeptical"> Skeptical</option>
											</select>
											<button type="button" id="cercaEventi" class="btn btn-default btn-block btn-success" >Ricerca</button>
										</div>
									</form>
								</div>
							</li>
							<!-- FINE FORM CERCA-->

							<!-- INIZIO FORM SEGNALA-->
							<li class="dropdown" >
								<a href="#" class="dropdown-toggle" data-toggle="dropdown"><span class="bianco">Segnala evento</span></a>
								<div class="dropdown-menu tenda_menu">
									<form>
										<div class="form-group mobile-bianco tenda_menu2">
											<select name="tipo" id="tipo" class="form-control menu_cercaesegnala">
												<option value="default" selected>Seleziona tipo</option>
												<option value="problemi_stradali">Problemi Stradali</option>
												<option value="emergenze_sanitarie">Emergenze Sanitarie</option>
												<option value="reati">Reati</option>
												<option value="problemi_ambientali">Problemi Ambientali</option>
												<option value="eventi_pubblici">Eventi Pubblici </option>
											</select>											
											<div>
												<select name="sottotipo" id="sottotipo" class="form-control menu_cercaesegnala">
													<option selected="" value="0">Seleziona sottotipo</option>
												</select>
											</div>
											<div class="menu_cercaesegnala"><b>Posizione:</b> <span class="posADDR" id="sendADDR"> </span></div>
											<div class="menu_cercaesegnala"><b>Latitudine:</b> <span class="posLAT" id="sendLAT"></span></div>		
											<div class="menu_cercaesegnala"><b>Longitudine:</b> <span class="posLON" id="sendLON"></span></div>
											<div class="menu_cercaesegnala"><textarea rows="3" placeholder="Descrizione" id="descrizione" class="form-control" ></textarea></div>
											<button type="button" id="invia_notifica" class="btn btn-default btn-block btn-success" >Invia</button>
										</div>
									</form>
								</div>
							</li>
							<!-- INIZIO TABELLA-->
							<li class="dropdown">
								<a href="#" class="dropdown-toggle" data-toggle="dropdown"><span class="bianco">Tabelle</span></a>
								<ul class="dropdown-menu " id="minimo">
									<li><a href="#" class="dropdown-toggle apri" data-toggle="dropdown" data="overlay"><span class="mobile-bianco">Eventi</span></a></li>
									<li class="divider"></li>
									<li><a href="#" class="dropdown-toggle apri2" data-toggle="dropdown" data="overlay2"><span class="mobile-bianco">Eventi archiviati</span></a></li>
								</ul>
							</li>								
								
								<!-- tabella overlay eventi -->

								<div class="overlay" id="overlay"></div>
									<div id="box">
										<a class="chiudi" href="#">X</a> 
										<div>
										<table class="table table-hover ">
											
												<thead> 
													<tr>  
														<th>Tipo</th>  
														<th>Sottotipo</th>   
														<th>Indirizzo</th>
														<th>Stato</th>
														<th>Descrizione</th> 
													</tr> 
												</thead>
											
												<tbody id="tabella"  ></tbody>  
										</table>
										</div>
        							
									</div>
								</div>  

							<!-- fine tabella overlay eventi -->
						<!-- FINE TABELLE-->
						<!-- INIZIO TABELLA2-->
								

								<!-- tabella overlay eventi archiviati -->
								<div class="overlay2" id="overlay2"></div>
									<div id="box2">
										<a class="chiudi2" href="#">X</a> 
										<div>
										<table class="table table-hover ">
											
												<thead> 
													<tr>  
														<th>Tipo</th>  
														<th>Sottotipo</th>   
														<th>Indirizzo</th>
														<th>Stato</th>
														<th>Descrizione</th> 
													</tr> 
												</thead>
											
												<tbody id="tabella_arch"></tbody>  
										</table>
										</div>
        							
									</div>
								</div>  

							<!-- fine tabella overlay eventi archiviati-->
						<!-- FINE TABELLE2-->

						</ul>   		
					</div> 
				</div>
			</div>
			<!--  Inizio divisore mappa --> 
			<div id="googleMap" class="mappa"></div>
			<!-- fine divisore mappa-->
			
			<input id="pac-input" class="controls pacstyle" type="text" placeholder="Cerca localita'" autocomplete="off"></input>
			
			<!-- Inizio alert errori -->
			<div class="alert alert-danger alertdiv errori" id="errselect">
                <strong>Errore:</strong> Campi obbligatori non settati
            </div>
			<div class="alert alert-danger alertdiv errori" id="errInvio">
			    <strong>Errore:</strong>Invio fallito 
			</div>
			<div class="alert alert-danger alertdiv errori" id="errCerca">
                <strong>Errore:</strong>Ricerca fallita 
            </div>
			<div class="alert alert-danger alertdiv errori" id="errNotifica">
				<strong>Errore:</strong>Notica fallita 
            </div>
			<div class="alert alert-danger alertdiv errori" id="errNotificaDist">
				<strong>Errore:</strong> evento troppo distante per la notifica
			</div>

			<div class="alert alert-danger alertdiv errori" id="errNotificaPermessi">
				<strong>Errore:</strong> non hai i permessi per archiviare eventi	
			</div>
			<div class="alert alert-danger alertdiv errori" id="errGeolocation">
                <strong>Errore:</strong> geolocalizzazione non disponibile       
            </div>
			<div class="alert alert-danger alertdiv errori" id="errNoFlood">
                <strong>Errore:</strong> hai già notificato questo evento       
            </div>
	<div class="alert alert-danger alertdiv errori" id="errArchiviato">
                <strong>Errore:</strong> evento archiviato o non più disponibile       
            </div>


			<!--Fine alert errori -->
			
			<!--Inizio alert successi -->
			<div class="alert alert-success alertdiv errori" id="sucInvio0">
                <strong>Successo:</strong> Invio effettuato con successo
            </div>
			<div class="alert alert-success alertdiv errori" id="sucNotifica">
                <strong>Successo:</strong> Notifica Inviata
            </div>
			<!--Fine alert successi-->

	</body>
</html>
