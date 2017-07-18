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
			position += settingsList[i].outerWidth;
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
	settingsClickListener: (function(){
		document.getElementById("settings").addEventListener('click', function(e){
			if(e.target.id == "settingsSwitch")
				settings.toggle();
			if(e.target.classList.contains("detailedSettingsSwitch"))
				settings.toggleDetailed(e.target.parentNode);

			switch(e.target.id){
				case "sett_editBack":
					popupWindow.turnON("editBg");
				break;
				case "sett_editFolders":
					if(!folders.edit.enabled)
						folders.edit.enable();
					else
						folders.edit.disable();
				break;
				case "sett_editIcons":
				if(!icons.edit.enabled)
					icons.edit.enable();
				else
					icons.edit.disable();
				break;
			}
		});
		mousedown: false,
		document.getElementById("settings").addEventListener('mousedown', function(e){
			settings.mousedown = true;
			switch(e.target.id){
				case "sett_plus":
					var growInterval = setInterval(function(){
						console.log("loop");
						icons.size++;
						if(!settings.mousedown)
							clearInterval(growInterval);
					},20);
				break;
				case "sett_minus":
				var shrinkInterval = setInterval(function(){
					console.log("loop");
					icons.size--;
					if(!settings.mousedown)
						clearInterval(shrinkInterval);
				},20);
				break;
			}
		});
		document.getElementById("settings").addEventListener('mouseup', function(e){
			settings.mousedown = false;
			switch(e.target.id){
				case "sett_plus":
					icons.save.size();
				break;
				case "sett_minus":
					icons.save.size();
				break;
			}
		});
	})(),
	documentClickListener: (function(){
		document.addEventListener("click", function(e){
			if((icons.edit.enabled) && ((e.target == document.body) || (e.target.id == "iconContainer")))
				icons.edit.disable();

			if((folders.edit.enabled) && ((e.target == document.body) || (e.target.id == "iconContainer")))
				folders.edit.disable();

			if((settings.visible) && ((e.target == document.body) || (e.target.id == "iconContainer")))
				settings.hide();
		});
	})(),
}
