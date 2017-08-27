folders = {
	selected: null,
	swap: function(N1, N2){
		if (N1 && N2){
			var P1 = N1.parentNode;
			var T1 = document.createElement("span");
			P1.insertBefore(T1, N1);

			var P2 = N2.parentNode;
			var T2 = document.createElement("span");
			P2.insertBefore(T2, N2);

			P1.insertBefore(N2, T1);
			P2.insertBefore(N1, T2);

			P1.removeChild(T1);
			P2.removeChild(T2);
		}
	},
	load: function(){
		ajax.onload = function(){
			if(ajax.responseData){
				// remove information about previous folders
					document.getElementById("mainFolders").innerHTML = '';
					folders.folderPlus.show();

				// show folders
					var ajaxResp = ajax.responseData;
					for(i = 0; i < ajaxResp.length; i++)
						folders.add(ajaxResp[i]);
			}
		}
		ajax.GET("loadFolders");
	},
	add: function(name){
		var div = document.createElement("div");
			div.setAttribute("class", "folder");
			div.setAttribute("draggable", "true");
			div.id = "folder_"+name;
			div.innerHTML = name;
		document.getElementById("mainFolders").insertBefore(div, document.getElementById("folderPlus"));
	},
	save: {
		newFolder: function(name){
			ajax.onload = function(){
				if(ajax.responseData == "alreadyExist"){
					display.info("List of folders should reload automatically in a second or less");
					folders.load();
				}
				else if(ajax.responseData != "nameTooLong"){
					folders.add(name);
					popupWindow.turnOFF();
				}
			}
			var data = new FormData();
				data.append("name", name);
			ajax.POST("addFolder", data);
		},
		order: function(){
			var foldersList = document.getElementById("mainFolders").children;

			var newOrder = [];
			for(i=0; i < foldersList.length-1; i++) 	// -1 because 'folderPlus' is ignored
				newOrder.push(foldersList[i].id.replace("folder_", ''));

			ajax.onload = function(){
				if(ajax.responseData == "reload"){
					display.info("List of folders will be reloaded");
					folders.load();
				}
			}
			var data = new FormData();
				data.append("order", JSON.stringify(newOrder));
			ajax.POST("saveFoldersOrder", data);
		},
	},
	renameFolder: function(folderDIV, newName){
		var oldName = folderDIV.id.replace("folder_", '');
		ajax.onload = function(){
			if(ajax.responseData){
				if(ajax.responseData == "reload"){
					display.info("List of folders should reload automatically in a second or less");
					folders.load();
					return;
				}

				// if folder with new name already exist refresh list of folders
				if(ajax.responseData == "alreadyExist")
					folders.load();
				// otherwise add new folder to list
				else{
					folderDIV.id = "folder_"+newName;
					folderDIV.innerHTML = newName;
				}

				// if folder which name was changed was active folder, load folder with new name
				if(oldName == icons.activeFolder)
					icons.load.folder(newName);

				popupWindow.turnOFF();
			}
		}
		var data = new FormData();
			data.append("oldName", oldName);
			data.append("newName", newName);
		ajax.POST("renameFolder", data);
	},
	deleteFolder: function(folderDIV){
		if(folderDIV.id.replace("folder_", '').toUpperCase() == "START"){
			folderDIV.removeAttribute("style");
			display.info("You can't delete folder 'Start'");
		}
		else{
			ajax.onload = function(){
				if(folderDIV.id.replace("folder_", '') == icons.activeFolder){
					icons.load.folder("start");
				}
				folderDIV.remove();
			};
			var data = new FormData();
				data.append("name", folderDIV.id.replace("folder_", ''));
			ajax.POST("deleteFolder", data);
		}
	},
	edit:{
		enabled: false,
		enable: function(){
			if(!this.enabled){
				this.enabled = true;
				tools.changeCSS(".folder","background-position","-60px");
			}
		},
		disable: function(){
			if(this.enabled){
				this.enabled = false;
				tools.changeCSS(".folder","background-position","0px");
			}
		},
	},
	folderPlus:{
		show: function(){
			var folderPlus = document.createElement("img");
				folderPlus.id = "folderPlus";
				folderPlus.src = "images/plus.png";
				folderPlus.addEventListener('click', function(){ popupWindow.turnON("addFolder"); });
			document.getElementById("mainFolders").appendChild(folderPlus);
		},
		hide: function(){
			document.getElementById("folderPlus").remove();
		}
	},
}





















document.getElementById("folders").addEventListener('click', function(e){
	if(e.target.id.indexOf("folder_") != -1){
		folder = e.target.id.replace("folder_", '');
		if((folders.edit.enabled) && (folder != "BIN")){
			folders.selected = e.target;
			popupWindow.turnON("editFolder");
		}
		else{
			icons.load.folder(folder);
		}
	}
});


document.getElementById("mainFolders").addEventListener('dragstart', function(e){
	if(e.target.id.indexOf("folder_") != -1){
		var selected = e.target;
		folders.selected = selected;
		setTimeout(function(){
			selected.style.opacity = 0;
		},10);
		if(!settings.visible)
			document.getElementById("folder_BIN").style.display = "block";
	}
	else
		e.preventDefault();
});


document.getElementById("mainFolders").addEventListener('dragenter', function(e){
	e.preventDefault();
	if(e.target.id.indexOf("folder_") != -1)
		folders.swap(folders.selected, e.target);
});


document.getElementById("folders").addEventListener('dragover', function(e){
	if(e.target.id.indexOf("folder_") != -1)
		e.preventDefault();
});


document.getElementById("folders").addEventListener('dragend', function(e){
	e.preventDefault();
	if(folders.selected){
		folders.selected.removeAttribute("style");
		folders.selected = null;
	}
	if(!settings.visible)
		document.getElementById("folder_BIN").style.display = "none";
});







document.getElementById("folder_BIN").addEventListener('drop', function(e){
	e.preventDefault();
	if(icons.selected){
		icons.deleteIcon(icons.selected);
		icons.selected = null;
	}
	if(folders.selected){
		popupWindow.turnON("definitelyDeleteFolder");
	}
});


document.getElementById("mainFolders").addEventListener('drop', function(e){
	e.preventDefault();
	if((icons.selected) && (e.target.id.indexOf("folder_") != -1)){	// is icon is dropped on folder
		var folder = e.target.id.replace("folder_", '');
		icons.moveIconToFolder(icons.selected, folder);
	}
	if(folders.selected){
		folders.save.order();
	}
});
