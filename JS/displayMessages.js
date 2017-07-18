display = {
		log: function(message){
			var div = this.createDIV("log", message, 3000);
		},
		info: function(message){
			var div = this.createDIV("info", message, 10000);
		},
		error: function(message){
			var div = this.createDIV("error", message, 10000);
		},
		createDIV: function(type, message, time){
			if(!this.styleSheet) this.init();

			var div = document.createElement("div");
				div.classList.add("message");
				div.classList.add(type);
				div.innerHTML = message;
			document.getElementById("messages").appendChild(div);
			this.arrange();

			setTimeout(function(){
				display.removeDIV(div);
			}, time);
			return div;
		},
		removeDIV: function(div){
			div.remove();
			display.arrange();
		},
		loading: function(){
			if(!this.styleSheet) this.init();

			if(!document.getElementById("loading")){
				var div = document.createElement("div");
					div.id = "loading";
					div.classList.add("message");
				document.getElementById("messages").appendChild(div);
				div.innerHTML = "Trwa lączenie z bazą danych...";
				this.changeCursor("progress");
				this.arrange();
			}
		},
		loadingEnd: function(){
			display.removeDIV(document.getElementById("loading"));
			this.changeCursor("initial");
		},
		arrange: function(){
			setTimeout(function(){
				var messages = document.getElementById("messages");
				var messagesList = messages.children;
				var position = 0;
				for(i=0; i<messagesList.length; i++){
					messagesList[i].style.top = position;
					position += messagesList[i].clientHeight;
					messages.style.height = position;
				}
				if(messagesList.length == 0){
					messages.style.height = 0;
				}
			},20);
		},
		styleSheet: null,
		changeCursor: function(value){
			var actSelector;
			for (j = 0; j < this.styleSheet.cssRules.length; j++){ // Loop through all properties ('p', 'div', '.', '#', etc.)
				actSelector = this.styleSheet.cssRules[j].selectorText;
				if(actSelector == "*"){
					this.styleSheet.cssRules[j].style["cursor"] = value;
					break;
				}
			}
		},
		init: function(){
				var messagesDiv = document.createElement("div");
				messagesDiv.id = "messages";
				document.body.appendChild(messagesDiv);

				var styleElem = document.createElement("style");
				document.head.appendChild(styleElem);

				styleElem.innerHTML =
				"*{"+

				"}"+
				"#messages{"+
					"z-index: 100;"+
					"opacity: .8;"+
					"position: fixed;"+
					"top: 0;"+
					"margin: auto;"+
					"left: 0;"+
					"right: 0;"+
					"width: 500px;"+
					"height: 0;"+
					"overflow: hidden;"+
					"border-radius: 0 0 20px 20px;"+
					"transition-duration: .5s;"+
					"font-size: 14px;"+
					"font-weight: bolder;"+
				"}"+
				".message{"+
					"position: absolute;"+
					"margin: auto;"+
					"left: 0;"+
					"padding: 0 5%;"+
					"right: 0;"+
					"top: -30px;"+
					"width: 90%;"+
					"background-color: #FFF;"+
					"background-size: 16px;"+
					"background-position-x: 10px;"+
					"background-repeat: no-repeat;"+
					"text-align: center;"+
					"transition-duration: .5s;"+
					"color: #FFF;"+
					"background-color: #000;"+
				"}"+
				"#messages .log{"+

				"}"+
				"#messages .info{"+
					"color: #72ADFF;"+
					"background-image: url('images/info.png');"+
				"}"+
				"#messages .error{"+
					"color: #FF6464;"+
					"background-color: #520000;"+
					"background-image: url('images/error.png');"+
				"}"+
				"#messages #loading{"+
					"background-image: url('images/loading.gif');"+
				"}";

				this.styleSheet = styleElem.sheet;
		},
	}
