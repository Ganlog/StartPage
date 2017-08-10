ajax = {
	onload: null,
	responseData: null,
	POST: function(action, data){
		this.connect("POST", action, data);
	},
	GET: function(action, data){
		ajax.connect("GET", action, data);
	},
	connect: function(method, action, data='_'){
		this.connectionsCount++;
		display.loading();

		var onloadFunction = this.onload;	// save onload function to execute it after receiving response
		this.onload = null;

		var xmlhttp = new XMLHttpRequest();
		xmlhttp.onload = function(){
			if (xmlhttp.status == 200){
				var response = xmlhttp.responseText;
				var correctResponse = true;
				try{ response = JSON.parse(response) }
				catch (e){
					display.error("Wrong server response (content below):<br>"+xmlhttp.responseText);
					correctResponse = false;
				}

				if(correctResponse){
					if(response.error) display.error(response.error);
					if(response.log) display.log(response.log);
					if(response.info) display.info(response.info);
					if(response.responseData){
						if(response.responseData == "log-in")
							popupWindow.turnON("log-in");
						else
							ajax.responseData = response.responseData;
					 }
					if(onloadFunction) onloadFunction();
					ajax.responseData = null;
				}
			}
			else{
				display.error("Problem with AJAX connection:");
				display.error("Status: "+xmlhttp.status);
			}
			ajax.connectionsCount--;
			if(ajax.connectionsCount == 0)		// executes after the end of last connection
				display.loadingEnd();
		}

		if(method == "POST"){
			xmlhttp.open("POST", "AJAX/ajaxPOST.php?"+action, true);
			xmlhttp.send(data);
		}
		else if(method == "GET"){
			xmlhttp.open("GET", "AJAX/ajaxGET.php?"+action+"="+data, true);
			xmlhttp.send();
		}
	},
	connectionsCount: 0,
}
