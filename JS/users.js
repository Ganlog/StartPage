users = {
  getCurrentUser: function(){
    ajax.onload = function(){
      if(ajax.responseData){
        localStorage["currentUser"] = ajax.responseData;
      }
    }
    ajax.GET("getUser");
  },
  loadUserContent: function(){
    icons.load.size();
    icons.load.folder("Start");
    folders.load();
    popupWindow.turnOFF();
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
        users.loadUserContent();
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
        users.loadUserContent();
    }
    var data = new FormData();
      data.append("user", user);
      data.append("pass", pass);
    ajax.POST("log-in", data);
  },

  logOut: function(){
    ajax.onload = function(){
      localStorage.removeItem("currentUser");

      //remove folders, icons and settings
      localStorage.removeItem("lastActiveFolder");
      folders.edit.disable();
      document.getElementById("mainFolders").innerHTML = '';

      icons.edit.disable();
      icons.clear();
      icons.size = 0;
      icons.edit.disable();
      settings.hide();
      popupWindow.turnON("log-in");
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
      users.loadUserContent();
  }
});
