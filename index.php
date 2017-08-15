<!DOCTYPE>
<html>
<head>
	<base target="_parent" /> <!-- if this page is inside iframe this line allows to redirect page by <a href=''> -->
	<meta charset="utf-8">
	<title>Strona główna</title>
	<link rel="shortcut icon" href="images/favicon.ico">
	<link rel="stylesheet" type="text/css" href="CSS/style.css">
	<link rel="stylesheet" type="text/css" href="CSS/popupWindow.css">
	<script>
		window.onload = function(){
			users.getCurrentUser();
		}
	</script>

	<script src="JS/ajax.js" defer></script>
	<script src="JS/tools.js" defer></script>
	<script src="JS/displayMessages.js" defer></script>

	<script src="JS/icons.js" defer></script>
	<script src="JS/folders.js" defer></script>
	<script src="JS/settings.js" defer></script>
	<script src="JS/users.js" defer></script>
	<script src="JS/popupWindow.js" defer></script>
</head>

<body>
	<div id="settings">
		<div class="detailedSettings">
			<img id="sett_account" title="Manage your account" src="images/user.png" draggable="false">
		</div>
		<div class="detailedSettings">
			<img id="sett_editBack" title="Edit background" src="images/changeBG.png" draggable="false">
		</div>
		<div class="detailedSettings">
			<img id="sett_editFolders" title="Enable folders edit" src="images/editFolders.png" draggable="false">
		</div>
		<div class="detailedSettings">
			<img id="sett_editIcons" title="Enable icons edit" src="images/editIcons.png" draggable="false">
		</div>
		<div class="detailedSettings">
			<img id="sett_plus" src="images/plusi.png" title="Increase icons size" draggable="false">
			<img id="sett_minus" src="images/minusi.png" title="Reduce icons size" draggable="false">
			<img src="images/plusminus.png" title="Change icons size" class="detailedSettingsSwitch" draggable="false">
		</div>
		<img src="images/menu.png" id="settingsSwitch" draggable="false">
	</div>

	<div id="folders">
		<div id="mainFolders"></div>
		<img id="folder_BIN" src="images/bin.png">
	</div>

	<div id="iconContainer"></div>

	<div id="window">
		<div id="w_Header"></div>
		<div id="w_TurnOFF"></div>

		<div id="w_FilesUpload">
			<div id="w_DropUpload"></div>
			<div id="w_Upload">
				<p>Upload image from computer:</p>
				<input id="w_UploadFILE" type="file" accept="image/*">
				<p>od add URL address:</p>
				<input id="w_UploadURL" type="text">
				<button id="w_UploadURLOK">OK</button>
			</div>
		</div>

		<div id="w_Icon">
			<div id="w_AddIcon">
				<p>Add new address:</p>
				<input id="w_AddIconAddress" type="text">
				<button id="w_AddIconAddressOK">OK</button>
			</div>
			<div id="w_ChangeIcon">
				<p>Change address:</p>
				<input id="w_ChangeIconAddress" type="text">
				<button id="w_ChangeIconAddressOK">OK</button>
				<button id="w_DeleteIkonButton">Delete icon</button>
			</div>
		</div>

		<div id="w_Folder">
			<div id="w_AddFolder">
				<p>Add a new folder name:</p>
				<input id="w_AddFolderName" type="text">
				<button id="w_AddFolderNameOK">OK</button>
			</div>
			<div id="w_ChangeFolder">
				<p>Change folder name:</p>
				<input id="w_ChangeFolderName" type="text">
				<button id="w_ChangeFolderNameOK">OK</button>
				<button id="w_DeleteFolderButton">Delete folder</button>
			</div>
			<div id="w_DeleteFolder">
				<p>Are you sure you want to delete this folder, and move its content to a bin?</p>
				<button id="w_DefinitelyDelFolder">Yes</button>
				<button id="w_DoNotDelFolder">No</button>
			</div>
		</div>

		<div id="w_Account">
			<p id="w_Username"></p>
			<button id="w_LogOutButton">Log out</button>
		</div>

		<div id="w_LogOrSign">
			<div id="w_LogIn">
				<p>Username:</p>
				<input id="w_LogInUser" type="text">
				<p>Password:</p>
				<input id="w_LogInPass" type="password"></br>
				<button id="w_LogInButton">Log in</button></br></br>
				<p>Don't have an account?</p>
				<button id="w_TurnSingUp">Sign up</button>
			</div>
			<div id="w_SignUp">
				<p>Username:</p>
				<input id="w_SignUpUser" type="text">
				<p>Password:</p>
				<input id="w_SignUpPass" type="password">
				<p>Confirm Password:</p>
				<input id="w_SignUpConfPass" type="password"></br>
				<button id="w_SignUpButton">Sign up</button></br></br>
				<p>Already have an account?</p>
				<button id="w_TurnLogIn">Log in</button>
			</div>
		</div>

	</div>
	<div id="windowBgBlock"></div>
</body>
</html>
