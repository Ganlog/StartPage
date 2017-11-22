users = {
  getCurrentUser: function(){
    if(!localStorage["lastUserFetchTime"]) localStorage["lastUserFetchTime"] = 1;

    // if user was fetched less than a minute ago load his content without checking it on server and generating new sessionID
    if((Number(localStorage["lastUserFetchTime"])+60*1000) > Number(new Date())){
      users.load.userContent();
    }
    else{
      localStorage["lastUserFetchTime"] = Number(new Date());
      ajax.onload = function(){
        if(ajax.responseData){
          var oldSessID = ajax.responseData.pop();
          localStorage["currentUser"] = ajax.responseData;

          ajax.onload = function(){ users.load.userContent(); }
          var data = new FormData();
            data.append("oldSessID", oldSessID);
          ajax.POST("confirmNewSessID", data);
        }
      }
      ajax.GET("getUser");
    }
  },

  load:{
    userContent: function(){
      users.load.background();
      icons.load.size();
      icons.load.folder(localStorage["lastActiveFolder"]);
      folders.load();
      popupWindow.turnOFF();
    },

    background: function(){
      ajax.onload = function(){
        if(ajax.responseData)
          document.getElementById("background").style.backgroundImage = "url('"+ajax.responseData+"')";
      }
      ajax.GET("loadBG");
    },

    defaultBackground: function(){
      ajax.onload = function(){
        document.getElementById("background").style.backgroundImage = "url('images/bg.jpg')";
      }
      ajax.POST('restoreDefaultBG');
    },
  },

  save:{
    bgFILE: function(file){
			ajax.onload = function(){
				if(ajax.responseData)
					document.getElementById("background").style.backgroundImage = "url('"+ajax.responseData+"')";
			}
			var data = new FormData();
				data.append("image", file);
			ajax.POST("saveBgFILE", data);
		},
		bgURL: function(URL){
			ajax.onload = function(){
				if(ajax.responseData)
					document.getElementById("background").style.backgroundImage = "url('"+ajax.responseData+"')";
			}
			var data = new FormData();
				data.append("URL", URL);
			ajax.POST("saveBgURL", data);
		},
    foldersColor: function(color){
      ajax.onload = function(){
    		tools.changeCSS(".folder","background-color", color);
      }
      var data = new FormData();
        data.append("foldersColor", color);
      ajax.POST("saveFoldersColor", data);
    }
  },

  removeUserContent: function(){
    localStorage.removeItem("currentUser");
    localStorage.removeItem("lastActiveFolder");

    document.getElementById("background").style.backgroundImage = "url('images/bg.jpg')";
    document.getElementById("mainFolders").innerHTML = '';
    folders.edit.disable();
    icons.clear();
    icons.size = 0;
    icons.edit.disable();

    settings.hide();
  },

  signUpCheck: function(){
    var username = document.getElementById("w_SignUpUser").value;
    var pass = document.getElementById("w_SignUpPass").value;
    var passConf = document.getElementById("w_SignUpConfPass").value;
    if(username && pass && passConf)
      if(pass == passConf)
        users.signUp(username, pass);
      else
        display.error("Passwords don't match");
    else
      display.info("Please fill required fields");
  },

  signUp: function(username, pass){
    ajax.onload = function(){
      if(ajax.responseData){
        localStorage["currentUser"] = ajax.responseData;
        users.load.userContent();
      }
    }
    var data = new FormData();
      data.append("username", username);
      data.append("pass", pass);
    ajax.POST("sign-up", data);
  },

  logInCheck: function(){
    var username = document.getElementById("w_LogInUser").value;
    var pass = document.getElementById("w_LogInPass").value;
    if(username && pass)
      users.logIn(username, pass);
    else
      display.info("Please fill required fields");
  },

  logIn: function(username, pass){
    ajax.onload = function(){
      if(ajax.responseData){
        localStorage["currentUser"] = ajax.responseData;
        users.load.userContent();
      }
    }
    var data = new FormData();
      data.append("username", username);
      data.append("pass", pass);
    ajax.POST("log-in", data);
  },

  logOut: function(){
    ajax.onload = function(){
      popupWindow.turnON("log-in");
      document.getElementById("background").style.backgroundImage = "url('images/bg.jpg')";
    }
    ajax.POST("log-out");
  }
}




// this code executes on inactive tabs, everytime localStorage is changed
window.addEventListener('storage', function(event){
  if(event.key == 'currentUser')
    if(event.newValue != null)
      users.load.userContent();
    else
      popupWindow.turnON("log-in");
});
