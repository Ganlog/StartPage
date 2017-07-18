ajax = {
	onload: null,
	responseData: null,
	POST: function(action, data){
		this.connect("POST", action, data);
	},
	GET: function(action, data){
		ajax.connect("GET", action, data);
	},
	connect: function(method, action, data=''){
		this.connectionsCount++;
		display.loading();

		var onloadFunction = this.onload;	//zapisuje funkcje, która zostanie wykonana po zakończeniu połączenia
		this.onload = null;

		var xmlhttp = new XMLHttpRequest();
		xmlhttp.onload = function(){
			if (xmlhttp.status == 200){
				var response;
				if(xmlhttp.responseText.indexOf("<!--") != -1)	response = xmlhttp.responseText.substring(0, xmlhttp.responseText.indexOf("<!--"));	// Zabezpieczenie przed automatycznie dodawanym komentarzem na serwerze
				else response = xmlhttp.responseText;

				var correctResponse = true;
				try{ response = JSON.parse(response) }
				catch (e){
					display.error("Niepoprawna odpowiedź serwera (treść poniżej):<br>"+xmlhttp.responseText);
					correctResponse = false;
				}

				if(correctResponse){
					if(response.error) display.error(response.error);
					if(response.log) display.log(response.log);
					if(response.info) display.info(response.info);
					if(response.responseData) ajax.responseData = response.responseData;
					if(onloadFunction) onloadFunction();
					ajax.responseData = null;
				}
			}
			else{
				display.error("Problem z połączeniem poprzez ajax:");
				display.error("Status: "+xmlhttp.status);
			}
			ajax.connectionsCount--;
			if(ajax.connectionsCount == 0)		// wykonuje sie po zakończeniu ostatniego polaczenia
				display.loadingEnd();
		}

		if(method == "POST"){
			xmlhttp.open("POST", "ajax/ajaxPOST.php?"+action, true);
			xmlhttp.send(data);
		}
		else if(method == "GET"){
			xmlhttp.open("GET", "ajax/ajaxGET.php?"+action+"="+data, true);
			xmlhttp.send();
		}
	},
	connectionsCount: 0,
}
