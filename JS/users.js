users = {
  getCurrentUser: function(){
    ajax.onload = function(){
      if(ajax.responseData){
        localStorage["currentUser"] = ajax.responseData;
        users.load.userContent();
      }
    }
    ajax.GET("getUser");
  },

  load:{
    userContent: function(){
      users.load.background();
      icons.load.size();
      icons.load.folder(localStorage["lastActiveFolder"] || "Start");
      folders.load();
      popupWindow.turnOFF();
    },

    background: function(){
      ajax.onload = function(){
        if(ajax.responseData)
          document.body.style.backgroundImage = "url('images/"+ajax.responseData+"?test')";
      }
      ajax.GET("loadBG");
    },

    defaultBackground: function(){
      ajax.onload = function(){
        document.body.style.backgroundImage = "url('images/bg.jpg')";
      }
      ajax.POST('restoreDefaultBG');
    },
  },

  save:{
    bgFILE: function(file){
			ajax.onload = function(){
				if(ajax.responseData)
					document.body.style.backgroundImage = "url('images/"+ajax.responseData+"')";
			}
			var data = new FormData();
				data.append("image", file);
			ajax.POST("saveBgFILE", data);
		},
		bgURL: function(URL){
			ajax.onload = function(){
				if(ajax.responseData)
					document.body.style.backgroundImage = "url('images/"+ajax.responseData+"')";
			}
			var data = new FormData();
				data.append("URL", URL);
			ajax.POST("saveBgURL", data);
		},
  },

  removeUserContent: function(){
    localStorage.removeItem("currentUser");
    localStorage.removeItem("lastActiveFolder");

    document.getElementById("mainFolders").innerHTML = '';
    folders.edit.disable();
    icons.clear();
    icons.size = 0;
    icons.edit.disable();

    settings.hide();
  },

  signUpCheck: function(){
    var user = document.getElementById("w_SignUpUser").value;
    var pass = document.getElementById("w_SignUpPass").value;
    var passConf = document.getElementById("w_SignUpConfPass").value;
    if(user && pass && passConf)
      if(pass == passConf)
        users.signUp(user, pass);
      else
        display.error("Passwords don't match");
    else
      display.info("Please fill required fields");
  },

  signUp: function(user, pass){
    ajax.onload = function(){
      if(ajax.responseData){
        localStorage["currentUser"] = ajax.responseData;
        users.load.userContent();
      }
    }
    var data = new FormData();
      data.append("user", user);
      data.append("pass", pass);
    ajax.POST("sign-up", data);
  },

  logInCheck: function(){
    var user = document.getElementById("w_LogInUser").value;
    var pass = document.getElementById("w_LogInPass").value;
    if(user && pass)
      users.logIn(user, pass);
    else
      display.info("Please fill required fields");
  },

  logIn: function(user, pass){
    ajax.onload = function(){
      if(ajax.responseData)
        localStorage["currentUser"] = ajax.responseData;
        users.load.userContent();
    }
    var data = new FormData();
      data.append("user", user);
      data.append("pass", pass);
    ajax.POST("log-in", data);
  },

  logOut: function(){
    ajax.onload = function(){
      users.removeUserContent();
      popupWindow.turnON("log-in");
      document.body.style.backgroundImage = "url('images/bg.jpg')";
    }
    ajax.POST("log-out");
  }
}




// this code executes on inactive tabs, everytime localStorage is changed
window.addEventListener('storage', function(event){
  if(event.key == 'currentUser'){
    if(event.newValue == null)
      users.logOut();
    else
      users.load.userContent();
  }
});
