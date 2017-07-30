icons = {
	list: {},
	order: [],
	count: 0,
	size: 0,
	selected: null,
	activeFolder: null,
	save:{
		order: function(){
			ajax.onload = function(){
				if(ajax.responseData == "reload"){
					display.info("Folder will be reloaded");
					icons.load.folder(icons.activeFolder);
				}
			}
			var data = new FormData();
				data.append("order", JSON.stringify(icons.order));
				data.append("folder", icons.activeFolder);
			ajax.POST("saveOrder", data);
		},
		icon: function(ID, URL){
			ajax.onload = function(){
				icons.add(ID, URL);
				icons.arrange();
				icons.selected = ID;
				popupWindow.turnON("uploadImage");
			}
			var data = new FormData();
				data.append("ID", ID);
				data.append("folder", icons.activeFolder);
				data.append("URL", URL);
			ajax.POST("addIcon", data);
		},
		address: function(ID, URL){
			ajax.onload = function(){
				icons.list[ID].img.alt = URL;
				if((URL.substring(0,4) != "http") && (URL.substring(0,11) != "javascript:"))
					icons.list[ID].a.href = "http://"+URL;
				else  icons.list[ID].a.href = URL;
			}
			var data = new FormData();
				data.append("URL", URL);
				data.append("ID", ID);
			ajax.POST("saveNewAddress", data);
		},
		size: function(){
			var data = new FormData();
				data.append("size", icons.size);
			ajax.POST("saveSize", data);
		},
		imageFILE: function(ID, file){
			ajax.onload = function(){
				icons.load.image(ID);
			}
			var data = new FormData();
				data.append("image", file, ID);
			ajax.POST("saveImageFILE", data);
		},
		imageURL: function(ID, URL){
			ajax.onload = function(){
				icons.load.image(ID);
			}
			var data = new FormData();
				data.append("URL", URL);
				data.append("ID", ID);
			ajax.POST("saveImageURL", data);
		},
		bgFILE: function(file){
			ajax.onload = function(){
				document.body.style.backgroundImage = "url('images/bg.jpg?"+tools.generateID()+"')";
			}
			var data = new FormData();
				data.append("image", file);
			ajax.POST("saveBgFILE", data);
		},
		bgURL: function(URL){
			ajax.onload = function(){
				document.body.style.backgroundImage = "url('images/bg.jpg?"+tools.generateID()+"')";
			}
			var data = new FormData();
				data.append("URL", URL);
			ajax.POST("saveBgURL", data);
		},
	},
	load:{
		size: function(){
			ajax.onload = function(){
				if(ajax.responseData)
					icons.size = parseInt(ajax.responseData);
			}
			ajax.GET("loadSize");
		},
		folder: function(folder){
			ajax.onload = function(){
				icons.activeFolder = folder;
				if(ajax.responseData){
					// reset essential values
						document.getElementById("iconContainer").innerHTML = '';
						icons.list = {}
						icons.order = [];
						icons.count = 0;

					// show folder content
						var ajaxResp = ajax.responseData;
						for(i = 0; i < ajaxResp.count; i++){
							icons.add(ajaxResp.ID[i], ajaxResp.URL[i], ajaxResp.image[i]);
						}

					// show plus-icon if current folder is not bin
					if(icons.activeFolder != "BIN")
							icons.iconPlus.show();
					icons.arrange();
					localStorage['lastActiveFolder'] = folder;
				}
			}
			ajax.GET("loadFolderContent", folder);
		},
		image: function(ID){
			ajax.onload = function(){
				if(ajax.responseData){
					icons.list[ID].img.src = "images/icons/"+ajax.responseData+"?"+tools.generateID();
					if(document.getElementsByClassName("uploadImage")[0])
						popupWindow.turnOFF();
				}
			}
			ajax.GET("loadImage", ID);
		},
	},
	edit:{
		enabled: false,
		enable: function(){
			this.enabled = true;
			tools.changeCSS(".editBlockade","height","100%");
			tools.changeCSS(".editBlockade","opacity","1");
		},
		disable: function(){
			this.enabled = false;
			tools.changeCSS(".editBlockade","height","0%");
			tools.changeCSS(".editBlockade","opacity","0");
		},
	},
	moveIconToFolder: function(ID, folder){
		delete icons.list[ID];						// delete icon from list of icons
		icons.order.splice(icons.order.indexOf(ID), 1);	// delete icon from order list
		icons.count--;
		icons.arrange();
		ajax.onload = function(){
			if(ajax.responseData == "reload"){
				display.info("Lista folderów zostanie ponownie załadowana");
				folders.load();
				icons.load.folder(icons.activeFolder);
			}
			else if(ajax.responseData == "alreadyInFolder")
				icons.load.folder(icons.activeFolder);
			else
				document.getElementById("iconContainer").removeChild(document.getElementById(ID));
		}
		var data = new FormData();
			data.append("ID", ID);
			data.append("folder", folder);
		ajax.POST("moveIconToFolder", data);
	},
	deleteIcon: function(ID){
		if(icons.activeFolder.toUpperCase() == "BIN"){

			delete icons.list[ID];						// delete icon from list of icons
			icons.order.splice(icons.order.indexOf(ID), 1);	// delete icon from order list
			icons.count--;

			icons.arrange();
			document.getElementById(ID).remove();

			var data = new FormData();
				data.append("ID", ID);
			ajax.POST("deleteIcon", data);
			popupWindow.turnOFF();
		}
		else{
			icons.moveIconToFolder(ID, "BIN");
		}
	},
	arrange: function(){
		var
			margin = 0.2 * this.size,
			containerWidth = document.getElementById("iconContainer").offsetWidth,
			size = this.size + margin,
			iconCount = (document.getElementById("iconPlus")) ? this.count+1 : this.count,
			countXtest = Math.floor(containerWidth / size),		// number of columns of icons (must be tested to avoid situation with 0 columns)
			countX = (countXtest > 0) ? countXtest : 1,			// number of columns must be at least 1
			countY = Math.ceil(iconCount / countX),				// number of rows
			positionX, positionY;

		nr = 0;
		for(var i=0; i < countY; i++){
			if((iconCount - i * countX) < countX)
				countX = iconCount - i*countX;

			positionX = Math.round((containerWidth - countX*size) / 2) + margin/2;
			positionY = margin;
			for(j=0; j < countX; j++){
				if(icons.list[icons.order[nr]]){
					icons.list[icons.order[nr]].style.left = positionX+j*size;
					icons.list[icons.order[nr]].style.top = positionY+i*size;
				}
				nr++;
			}
			if(document.getElementById("iconPlus")){
				document.getElementById("iconPlus").style.left = positionX+(j-1)*size;
				document.getElementById("iconPlus").style.top = positionY+i*size;
			}
		}
		document.getElementById("iconContainer").style.height = countY*size+margin;
	},
	add: function(ID, URL, image){
		this.list[ID] = new this.iconObject(ID, URL, image);
		this.order[this.count] = ID;
		this.count++;
	},
	iconObject: function(ID, URL, image){
		// creating visible icon
			var div = document.createElement("div");
				div.id = ID;
				div.setAttribute("class", "icon");
				document.getElementById("iconContainer").appendChild(div);

				// creating editBlockade and its eventListener
						var block = document.createElement("div");
							block.setAttribute("class", "editBlockade");
							block.addEventListener("click", function(e){
								icons.selected = e.target.parentNode.id;
								popupWindow.turnON("editIcon");
							});
							div.appendChild(block);

				// creating 'a' element
						var a = document.createElement("a");
							a.setAttribute("class", "URL");
							if((URL.substring(0,4) != "http") && (URL.substring(0,11) != "javascript:"))
								a.href = "http://"+URL;
							else  a.href = URL;
							div.appendChild(a);

				// creating img element if image is set
						var img = document.createElement("img");
							if(image){
								img.src = "images/icons/" + image;
							}
							img.alt = URL;
							img.setAttribute("class", "image");
							div.appendChild(img);

		// ease of acces to variables
				this.iconDIV = div;
				this.style = div.style;
				this.id = div.id;
				this.block = block;
				this.img = img;
				this.a = a;
	},
	sizeListener: (function(){
		// first size set and arrangement (50 miliseconds delay is necessary to give time to finish creation of "icons" class)
			var oldSize;
			setTimeout(function(){
				tools.changeCSS(".icon", "width", icons.size+"px");
				tools.changeCSS(".icon", "height", icons.size+"px");
				tools.changeCSS(".image", "font-size", icons.size/8+"px");
				icons.arrange();
				oldSize = icons.size;
			}, 50);

		// set icon size ang arrange them every time size is changed
			setInterval(function(){
				if(icons.size != oldSize){
					tools.changeCSS(".icon", "width", icons.size+"px");
					tools.changeCSS(".icon", "height", icons.size+"px");
					tools.changeCSS(".image", "font-size", icons.size/8+"px");
					icons.arrange();
					oldSize = icons.size;
				}
			}, 200);
	})(),
	windowWidthListener: (function(){
		var windowWidth = document.getElementById("iconContainer").clientWidth;
		setInterval(function(){
			if(windowWidth != document.getElementById("iconContainer").clientWidth){
				icons.arrange();
				windowWidth = document.getElementById("iconContainer").clientWidth;
			}
		}, 500);
	})(),
	iconPlus:{
		show: function(){
			var iconPlus = document.createElement("div");
				iconPlus.id = "iconPlus";
				iconPlus.classList.add("icon");
				iconPlus.addEventListener('click', function(){ popupWindow.turnON("addIcon"); });
			document.getElementById("iconContainer").appendChild(iconPlus);
			icons.arrange();
		},
		hide: function(){
			document.getElementById("iconPlus").remove();
			icons.arrange();
		}
	},
}































/* drag and drop */
document.getElementById("iconContainer").addEventListener('dragstart', function(e){
	if(e.target.className.indexOf("URL") != -1){ 	// if dragged element is of class "URL"

		var selected = icons.list[e.target.parentNode.id];
		icons.selected = selected.id;
		// setting drag image and hiding actual dragged div
			e.dataTransfer.setDragImage(selected.img, icons.size/2, icons.size/2);
			setTimeout(function(){
				selected.style.display = "none";
			},10);
		if(!settings.visible)
			document.getElementById("folder_BIN").style.display = "block";
	}
	else
		e.preventDefault();
});


document.getElementById("iconContainer").addEventListener('dragenter', function(e){
	e.preventDefault();
	if(icons.selected){
		// if 'selected' hovered object of class 'icon' - ok
		// if something else or itself end function
				var hovered = null;
				if(e.target.className.indexOf("URL") != -1)
					hovered = icons.list[e.target.parentNode.id].id;
				if(hovered == null) return;
				if(icons.order.indexOf(hovered) == icons.order.indexOf(icons.selected)) return;

		// moving to right from empty spot
				if(icons.order.indexOf(hovered) > icons.order.indexOf(icons.selected)){
					var i = icons.order.indexOf(icons.selected);
					while(i < icons.order.indexOf(hovered)){
						icons.order[i] = icons.order[i+1];
						i++;
					}
				}

		// moving to left from empty spot
				else{
					var i = icons.order.indexOf(icons.selected);
					while(i > icons.order.indexOf(hovered)){
						icons.order[i] = icons.order[i-1];
						i--;
					}
				}

		icons.order[i] = icons.selected;
		icons.arrange();
	}
});


document.getElementById("iconContainer").addEventListener('dragover', function(e){
	e.preventDefault();
});


document.getElementById("iconContainer").addEventListener('drop', function(e){
	e.preventDefault();
	if(icons.selected){
		icons.save.order();
	}
	if((!icons.selected) && (!folders.selected)){ // if something is dropped and its not icon or folder -> window to add icon appears
		popupWindow.turnON("addIcon");
		document.getElementById("w_AddIconAddress").value = e.dataTransfer.getData("TEXT");
	}
});


document.getElementById("iconContainer").addEventListener('dragend', function(e){
	e.preventDefault();
	if(icons.selected){
		if(icons.list[icons.selected])
			icons.list[icons.selected].iconDIV.removeAttribute("style");
		icons.arrange();
	}
	icons.selected = null;
	if(!settings.visible)
		document.getElementById("folder_BIN").style.display = "none";
});
