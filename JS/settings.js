settings = {
	visible: false,
	toggle: function(){
		if(this.visible)
			this.hide();
		else
			this.show();
	},
	show: function(){
		this.visible = true;
		var settingsList = document.getElementById("settings").children;
		var position = 0;
		for(i=settingsList.length-1; i>=0; i--){
			settingsList[i].style.top = position;
			position += settingsList[i].outerHeight;
		}
		document.getElementById("folder_BIN").style.display = "block";
	},
	hide: function(){
		this.visible = false;
		this.hideDetailed();
		var settingsList = document.getElementById("settings").children;
		for(i=settingsList.length-1; i>=0; i--){
			settingsList[i].style.top = 0;
		}
		document.getElementById("folder_BIN").style.display = "none";
	},
	visibleDetailed: null,
	toggleDetailed: function(which){
		if(this.visibleDetailed == which)
			this.hideDetailed();
		else
			this.showDetailed(which);
	},
	showDetailed: function(detailedSettings){
		this.hideDetailed();
		this.visibleDetailed = detailedSettings;
		var settings = detailedSettings;
		var settingsList = settings.children;
		var position = 0;
		for(i=settingsList.length-1; i>=0; i--){
			settingsList[i].style.right = position;
			position += settingsList[i].outerWidth+5;
		}
		settings.style.width = position;
	},
	hideDetailed: function(){
		if(this.visibleDetailed){
			var settings = this.visibleDetailed;
			var settingsList = settings.children;
			for(i=settingsList.length-1; i>=0; i--){
				settingsList[i].style.right = 0;
			}
			settings.style.width = document.getElementById("settingsSwitch").outerWidth;
		}
		this.visibleDetailed = null;
	},
	changeColor: function(colorHex){
		tools.changeCSS(".folder","background-color",colorHex);
		tools.changeCSS(".setting","background-color",colorHex);
	},
	settingsClickListener: (function(){
		document.getElementById("settings").addEventListener('click', function(e){
			if(localStorage["currentUser"] == null){
				popupWindow.turnON("log-in");
				return;
			}

			// expand list of settings if "settingsSwitch" clicked
			if(e.target.id == "settingsSwitch")
				settings.toggle();

			// expand detailed settings if representative settings button clicked
			if(e.target.classList.contains("groupSwitch"))
				settings.toggleDetailed(e.target.parentNode);

			// actions for every button in settings list
			switch(e.target.id){
				case "sett_editFolders":
					if(!folders.edit.enabled) folders.edit.enable();
					else folders.edit.disable();
				break;
				case "sett_editIcons":
					if(!icons.edit.enabled) icons.edit.enable();
					else icons.edit.disable();
				break;
				case "sett_account":
					popupWindow.turnON("manageAccount");
				break;
			}
		});

		mousedown: false,
		document.getElementById("settings").addEventListener('mousedown', function(e){
			settings.mousedown = true;
			var inc_decreaseRatio;

			// decide action on mousedown over selected element
			switch(e.target.id){
				case "sett_plus": inc_decreaseRatio = 1; break;
				case "sett_minus": inc_decreaseRatio = -1; break;
			}

			// change icons size in 20-minisecond interval while mouse is down
			var inc_decreaseInterval = setInterval(function(){
				if(inc_decreaseRatio)
					icons.size += inc_decreaseRatio;
				if(!settings.mousedown){
					clearInterval(inc_decreaseInterval);
					inc_decreaseRatio = 0;
				}
			},20);
		});
		document.getElementById("settings").addEventListener('mouseup', function(e){
			settings.mousedown = false;

			// decide action on mouseup over selected element
			switch(e.target.id){
				case "sett_plus": icons.save.size(); break;
				case "sett_minus": icons.save.size(); break;
			}
		});
	})(),
	documentClickListener: (function(){
		document.addEventListener("click", function(e){
			if((icons.edit.enabled) && ((e.target.id == "background") || (e.target.id == "iconContainer")))
				icons.edit.disable();

			if((folders.edit.enabled) && ((e.target.id == "background") || (e.target.id == "iconContainer")))
				folders.edit.disable();

			if((settings.visible) && ((e.target.id == "background") || (e.target.id == "iconContainer")))
				settings.hide();
		});
	})(),
}
