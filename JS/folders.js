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
					for(i = 0; i < ajaxResp.length; i+=2)
						folders.add(ajaxResp[i], ajaxResp[i+1]);
			}
		}
		ajax.GET("loadFolders");
	},
	add: function(ID, name){
		var div = document.createElement("div");
			div.setAttribute("class", "folder");
			div.setAttribute("draggable", "true");
			div.id = "folder_"+ID;
			div.innerHTML = name;
		document.getElementById("mainFolders").insertBefore(div, document.getElementById("folderPlus"));
	},
	save: {
		newFolder: function(name){
			ajax.onload = function(){
				if(ajax.responseData){
					var ajaxResp = ajax.responseData;
					if(ajaxResp == "alreadyExist"){
						display.info("List of folders should reload automatically in a second or less");
						folders.load();
					}
					else if(ajaxResp != "nameTooLong"){
						folders.add(ajaxResp[0], ajaxResp[1]);
						popupWindow.turnOFF();
					}
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
		var folder = folderDIV.id.replace("folder_", '');
		ajax.onload = function(){
			if((ajax.responseData) && (ajax.responseData == "reload")){
				display.info("List of folders should reload automatically in a second or less");
				folders.load();
				return;
			}

			folderDIV.innerHTML = newName;
		}
		var data = new FormData();
			data.append("folder", folder);
			data.append("newName", newName);
		ajax.POST("renameFolder", data);
	},
	deleteFolder: function(folderDIV){
		var folderName = folderDIV.id.replace("folder_", '');
		ajax.onload = function(){
			if(ajax.responseData == "cantDelete"){
				folderDIV.removeAttribute("style"); // if folder can't be deleted show it again on the list
				return;
			}

			if(icons.activeFolder == "BIN"){
				icons.load.folder("BIN");
			}

			if(icons.activeFolder == folderName){ // if active folder was deleted
				icons.load.folder(document.getElementsByClassName("folder")[0].id.replace("folder_", '')); // load first folder from folders list
			}
			folderDIV.remove();
		};
		var data = new FormData();
			data.append("folder", folderDIV.id.replace("folder_", ''));
		ajax.POST("deleteFolder", data);
	},
	edit:{
		enabled: false,
		enable: function(){
			if(!this.enabled){
				this.enabled = true;
				tools.changeCSS(".folder","background-position","-60px");
				tools.changeCSS(".folder","filter","brightness(150%)");
			}
		},
		disable: function(){
			if(this.enabled){
				this.enabled = false;
				tools.changeCSS(".folder","background-position","0px");
				tools.changeCSS(".folder","filter","brightness(100%)");
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
		var folder = e.target.id.replace("folder_", '');
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
	if((icons.selected) && (e.target.id.indexOf("folder_") != -1)){	// if icon is dropped on folder
		var folder = e.target.id.replace("folder_", '');
		icons.moveIconToFolder(icons.selected, folder);
	}
	if(folders.selected){
		folders.save.order();
	}
});
