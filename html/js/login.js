function change()
{
/*Quando viene chiamata, modifica il campo "location" di document*/
/*effettuando un redirect sulla pagina main.php*/
document.location.href="./mainpage.php";
}




function login()
{ 						
	var jsonlogin=new Object();		
	jsonlogin.username=$('#username').val(); //recupero i dati di login dai rispettivi campi dell'HTML 
	jsonlogin.password=$('#password').val();
	var sendstr = JSON.stringify(jsonlogin); //creo il JSON di invio al server 
	$.ajax
	({
        type: 'POST',	
        url: 'login.php',	
        async: false,		
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',	
        data: sendstr,		
        success: function(response)
        		 { //in caso di successo della richiesta viene eseguita questa funzione
       			 	if (response['result']=="login effettuato con successo")
       					//cioè se il server ha ritornato che l'utente è stato trovato con successo
            	 		change();
            	    else
           			$("#errlogin").fadeIn(); 
        			setTimeout(function(){$("#errlogin").fadeOut()}, 2000);
			 
			}
    });
}





$(document).ready 
(
	function () 
	{ 	
		$("#errlogin").hide(); //nasconde i div di errore del login
		//funzione che effettua login se bottone viene cliccato
		$("#loginForm").click
		(
			function()
			{
				login();
			}
		);
		//funzione che effettua login se tasto "INVIO" viene cliccato
		$("#password").keyup(
		function(invio)
		{
    		if(invio.keyCode == 13) {
        	login();
        	}
        	
    	});
	}
);
