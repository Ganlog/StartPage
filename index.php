<!DOCTYPE>
<html>
<head>
	<base target="_parent" /> <!-- gdy strona jest w iframe ta linijka pozwala przekierowywać linki poza iframe -->
	<meta charset="utf-8">
	<title>Strona główna</title>
	<link rel="shortcut icon" href="images/favicon.ico">
	<link rel="stylesheet" type="text/css" href="CSS/style.css">
	<link rel="stylesheet" type="text/css" href="CSS/popupWindow.css">
</head>

<body>
	<div id="settings">
		<div class="detailedSettings">
			<img id="sett_editBack" title="Edytuj tło" src="images/zmientlo.png" draggable="false">
		</div>
		<div class="detailedSettings">
			<img id="sett_editFolders" title="Edytowanie folderów" src="images/editFolders.png" draggable="false">
		</div>
		<div class="detailedSettings">
			<img id="sett_editIcons" title="Edytowanie ikon" src="images/editIcons.png" draggable="false">
		</div>
		<div class="detailedSettings">
			<img id="sett_plus" src="images/plusi.png" title="Powiększ ikony" draggable="false">
			<img id="sett_minus" src="images/minusi.png" title="Zmniejsz ikony" draggable="false">
			<img src="images/plusminus.png" title="Zmień rozmiar ikon" class="detailedSettingsSwitch" draggable="false">
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
				<p>Wgraj obrazek z komputera:</p>
				<input id="w_UploadFILE" type="file" accept="image/*">
				<p>lub podaj adres URL obrazka:</p>
				<input id="w_UploadURL" type="text">
				<button id="w_UploadURLOK">OK</button>
			</div>
		</div>

		<div id="w_Icon">
			<div id="w_AddIcon">
				<p>Podaj nowy adres:</p>
				<input id="w_AddIconAddress" type="text">
				<button id="w_AddIconAddressOK">OK</button>
			</div>
			<div id="w_ChangeIcon">
				<p>Zmień adres:</p>
				<input id="w_ChangeIconAddress" type="text">
				<button id="w_ChangeIconAddressOK">OK</button>
				<button id="w_DeleteIkonButton">Usuń ikonę</button>
			</div>
		</div>

		<div id="w_Folder">
			<div id="w_AddFolder">
				<p>Podaj nazwe nowego folderu:</p>
				<input id="w_AddFolderName" type="text">
				<button id="w_AddFolderNameOK">OK</button>
			</div>
			<div id="w_ChangeFolder">
				<p>Zmień nazwę folderu:</p>
				<input id="w_ChangeFolderName" type="text">
				<button id="w_ChangeFolderNameOK">OK</button>
				<button id="w_DeleteFolderButton">Usuń folder</button>
			</div>
			<div id="w_DeleteFolder">
				<p>Czy napewno chcesz usunąć folder a całą zawartość przenieść do kosza ?</p>
				<button id="w_DefinitelyDelFolder">Tak</button>
				<button id="w_DoNotDelFolder">Nie</button>
			</div>
		</div>

	</div>
	<div id="windowBgBlock"></div>
</body>
</html>

<script>
	window.onload = function(){
		icons.load.size();
		icons.load.folder(localStorage['lastActiveFolder'] || "Start");
		folders.load();
	}
</script>

<script src="JS/ajax.js"></script>
<script src="JS/tools.js"></script>
<script src="JS/displayMessages.js"></script>

<script src="JS/icons.js"></script>
<script src="JS/folders.js"></script>
<script src="JS/settings.js"></script>
<script src="JS/popupWindow.js"></script>
