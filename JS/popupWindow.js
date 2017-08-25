popupWindow = {
	turnON: function(what){
		var iconID = icons.selected;
		var slectedFolder = folders.selected;
		icons.selected = null;
		folders.selected = null;
		popupWindow.turnOFF();

		document.getElementById("window").setAttribute("class", what);
		document.getElementById("windowBgBlock").setAttribute("class", what);
		document.getElementById("windowBgBlock").addEventListener('click', popupWindow.turnOFF);
		document.getElementById("w_TurnOFF").addEventListener('click', popupWindow.turnOFF);
		document.getElementById("w_DropUpload").addEventListener('dragover', function(e){ e.preventDefault(); });
		document.getElementById("w_DropUpload").addEventListener('dragleave', function(e){ e.preventDefault();  this.style.display = "none"; });

		switch(what){

			case "addIcon": {
				document.getElementById("w_Header").innerHTML = "Add icon:";
				document.getElementById("w_AddIconAddress").focus();
				document.getElementById("w_AddIconAddress").addEventListener('keyup', function(e){ if(e.keyCode == 13) icons.save.icon(tools.generateID(), this.value); });
				document.getElementById("w_AddIconAddressOK").addEventListener('click', function(e){ icons.save.icon(tools.generateID(), document.getElementById("w_AddIconAddress").value); });
			}
			break;

			case "editIcon": {
				document.getElementById("w_Header").innerHTML = "Icon edit:";
				document.getElementById("w_ChangeIconAddress").value = icons.list[iconID].img.alt;
				document.getElementById("w_UploadFILE").addEventListener('change', function(e){ icons.save.imageFILE(iconID, e.target.files[0]); });
				document.getElementById("w_UploadURL").addEventListener('keyup', function(e){ if(e.keyCode == 13) icons.save.imageURL(iconID, e.target.value); });
				document.getElementById("w_UploadURLOK").addEventListener('click', function(e){ icons.save.imageURL(iconID, document.getElementById("w_UploadURL").value); });
				document.getElementById("window").addEventListener('dragenter', function(e){ e.preventDefault(); document.getElementById("w_DropUpload").style.display = "block"; });
				document.getElementById("w_DropUpload").addEventListener('drop', function(e){
					e.preventDefault();
					if(e.dataTransfer.files.length != 0)			icons.save.imageFILE(iconID, e.dataTransfer.files[0]);
					if(e.dataTransfer.getData("URL"))			icons.save.imageURL(iconID, e.dataTransfer.getData("URL"));
					document.getElementById("w_DropUpload").style.display = "none";
				});
				document.getElementById("w_ChangeIconAddress").addEventListener('keyup', function(e){ if(e.keyCode == 13) icons.save.address(iconID, this.value); });
				document.getElementById("w_ChangeIconAddressOK").addEventListener('click', function(e){ icons.save.address(iconID, document.getElementById("w_ChangeIconAddress").value); });
				document.getElementById("w_DeleteIkonButton").addEventListener('click', function(){ icons.deleteIcon(iconID); popupWindow.turnOFF(); });
				tools.addKeyPressedListener(46, function(){ icons.deleteIcon(iconID); popupWindow.turnOFF(); }) // 46 - Del
			}
			break;

			case "uploadImage": {
				document.getElementById("w_Header").innerHTML = "Upload image:";
				document.getElementById("w_UploadFILE").addEventListener('change', function(e){ icons.save.imageFILE(iconID, e.target.files[0]); });
				document.getElementById("w_UploadURL").addEventListener('keyup', function(e){ if(e.keyCode == 13) icons.save.imageURL(iconID, e.target.value); });
				document.getElementById("w_UploadURLOK").addEventListener('click', function(e){ icons.save.imageURL(iconID, document.getElementById("w_UploadURL").value); });
				document.getElementById("window").addEventListener('dragenter', function(e){ e.preventDefault(); document.getElementById("w_DropUpload").style.display = "block"; });
				document.getElementById("w_DropUpload").addEventListener('drop', function(e){
					e.preventDefault();
					if(e.dataTransfer.files.length != 0)			icons.save.imageFILE(iconID, e.dataTransfer.files[0]);
					if(e.dataTransfer.getData("URL"))			icons.save.imageURL(iconID, e.dataTransfer.getData("URL"));
					document.getElementById("w_DropUpload").style.display = "none";
				});
			}
			break;

			case "editBg": {
				document.getElementById("w_Header").innerHTML = "Background edit:";
				document.getElementById("w_UploadFILE").addEventListener('change', function(e){ users.save.bgFILE(e.target.files[0]); });
				document.getElementById("w_UploadURL").addEventListener('keyup', function(e){ if(e.keyCode == 13) users.save.bgURL(e.target.value); });
				document.getElementById("w_UploadURLOK").addEventListener('click', function(e){ users.save.bgURL(document.getElementById("w_UploadURL").value); });
				document.getElementById("window").addEventListener('dragenter', function(e){ e.preventDefault(); document.getElementById("w_DropUpload").style.display = "block"; });
				document.getElementById("w_DropUpload").addEventListener('drop', function(e){
					e.preventDefault();
					if(e.dataTransfer.files.length != 0)			users.save.bgFILE(e.dataTransfer.files[0]);
					if(e.dataTransfer.getData("URL"))			users.save.bgURL(e.dataTransfer.getData("URL"));
					document.getElementById("w_DropUpload").style.display = "none";
				});
				document.getElementById("w_restoreDefBG").addEventListener('click', function(e){ users.load.defaultBackground(); });
			}
			break;

			case "addFolder": {
				document.getElementById("w_Header").innerHTML = "Add folder:";
				document.getElementById("w_AddFolderName").focus();
				document.getElementById("w_AddFolderName").addEventListener('keyup', function(e){ if(e.keyCode == 13) folders.save.newFolder(this.value); });
				document.getElementById("w_AddFolderNameOK").addEventListener('click', function(e){ folders.save.newFolder(document.getElementById("w_AddFolderName").value); });
			}
			break;

			case "editFolder": {
				document.getElementById("w_Header").innerHTML = "Folder edit:";
				document.getElementById("w_ChangeFolderName").value = slectedFolder.id.replace("folder_", '');
				document.getElementById("w_ChangeFolderName").focus();
				document.getElementById("w_ChangeFolderName").addEventListener('keyup', function(e){ if(e.keyCode == 13) folders.renameFolder(slectedFolder, this.value); });
				document.getElementById("w_ChangeFolderNameOK").addEventListener('click', function(e){ folders.renameFolder(slectedFolder, document.getElementById("w_ChangeFolderName").value); });
				document.getElementById("w_DeleteFolderButton").addEventListener('click', function(){ folders.selected = slectedFolder; popupWindow.turnON("definitelyDeleteFolder"); });
			}
			break;

			case "definitelyDeleteFolder": {
				document.getElementById("w_Header").innerHTML = "Delete folder:";
				document.getElementById("w_DefinitelyDelFolder").focus();
				document.getElementById("windowBgBlock").addEventListener('click', function(){ slectedFolder.removeAttribute("style"); });
				document.getElementById("w_TurnOFF").addEventListener('click', function(){ slectedFolder.removeAttribute("style"); });
				document.getElementById("w_DoNotDelFolder").addEventListener('click', function(){ slectedFolder.removeAttribute("style"); popupWindow.turnOFF(); });
				document.getElementById("w_DefinitelyDelFolder").addEventListener('click', function(){ folders.deleteFolder(slectedFolder); popupWindow.turnOFF(); });
			}
			break;

			case "manageAccount": {
				document.getElementById("w_Header").innerHTML = "Manage account:";
				document.getElementById("w_Username").innerHTML = localStorage["currentUser"].split(",")[1]; // get username
				document.getElementById("w_ChangeBG").addEventListener('click', function(){ popupWindow.turnON("editBg"); });
				document.getElementById("w_LogOutButton").addEventListener('click', function(){ users.logOut(); });
			}
			break;

			case "log-in": {
				users.removeUserContent();
				document.getElementById("w_Header").innerHTML = "Log in:";
				document.getElementById("w_LogInUser").addEventListener('keyup', function(e){ if(e.keyCode == 13) document.getElementById("w_LogInPass").focus(); });
				document.getElementById("w_LogInPass").addEventListener('keyup', function(e){ if(e.keyCode == 13) document.getElementById("w_LogInButton").click(); });
				document.getElementById("w_LogInButton").addEventListener('click', function(){ users.logInCheck(); });
				document.getElementById("w_TurnSingUp").addEventListener('click', function(){ popupWindow.turnON("sign-up"); });
				document.getElementById("w_LogInUser").focus();
			}
			break;

			case "sign-up": {
				users.removeUserContent();
				document.getElementById("w_Header").innerHTML = "Sign up:";
				document.getElementById("w_SignUpUser").addEventListener('keyup', function(e){ if(e.keyCode == 13) document.getElementById("w_SignUpPass").focus(); });
				document.getElementById("w_SignUpPass").addEventListener('keyup', function(e){ if(e.keyCode == 13) document.getElementById("w_SignUpConfPass").focus(); });
				document.getElementById("w_SignUpConfPass").addEventListener('keyup', function(e){ if(e.keyCode == 13) document.getElementById("w_SignUpButton").click(); });
				document.getElementById("w_SignUpButton").addEventListener('click', function(){ users.signUpCheck(); });
				document.getElementById("w_TurnLogIn").addEventListener('click', function(){ popupWindow.turnON("log-in"); });
				document.getElementById("w_SignUpUser").focus();
			}
			break;
		}
	},
	turnOFF: function(){
		// replace of element with it's clone removes it's and it child's event listeners
		var window = document.getElementById('window');
			windowClone = window.cloneNode(true);
		window.parentNode.replaceChild(windowClone, window);
		window.remove();
		window = windowClone;
		window.removeAttribute("class");

		tools.removeKeyPressedListener(46);
		document.getElementById("windowBgBlock").removeAttribute("class");
		document.getElementById("w_UploadURL").value = '';
		document.getElementById("w_UploadFILE").value = '';
		document.getElementById("w_AddIconAddress").value = '';
		document.getElementById("w_ChangeIconAddress").value = '';
		document.getElementById("w_AddFolderName").value = '';
		document.getElementById("w_ChangeFolderName").value = '';
		document.getElementById("w_LogInUser").value = '';
		document.getElementById("w_LogInPass").value = '';
		document.getElementById("w_SignUpUser").value = '';
		document.getElementById("w_SignUpPass").value = '';
		document.getElementById("w_SignUpConfPass").value = '';
	}
}
