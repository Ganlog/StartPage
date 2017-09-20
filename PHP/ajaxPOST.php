<?php
	class Response {
		public $log;
		public $info;
		public $error;
		public $responseData;
	}
	$response = new Response();

	function respond(){
		global $response;
		print_r(json_encode($response));
		exit;
	}









	include 'logDB.php';
	$db = @new mysqli($db_host, $db_user, $db_pass, $db_name);
	if ($db->connect_error) {
		$response->error = "Database connection error. <br>";
		$response->error .= "Error number ".$db->connect_errno.": ".mb_convert_encoding($db->connect_error, 'utf-8');
		respond();
	}









	// if $_REQUEST is different than "sign-up" and "log-in"
	if(!isset($_REQUEST['sign-up']) && !isset($_REQUEST['log-in'])){
		// check correctness of user verification cookies from client side
		if(!isset($_COOKIE["userID"]) || !isset($_COOKIE["sessID"])){
			$response->responseData = "log-in";
			respond();
		}
		$userID = adaptToQuery($_COOKIE["userID"]);
		$sessID = adaptToQuery($_COOKIE["sessID"]);
		if(!isset($_REQUEST['confirmNewSessID']))
			if(!mysqli_num_rows($db->query("SELECT userID FROM sessions WHERE userID = ".$userID." AND sessionID = '".$sessID."'"))){
				$response->responseData = "log-in";
				respond();
			}
	}









	if(isset($_REQUEST['confirmNewSessID'])){
		$oldSessID = adaptToQuery($_POST["oldSessID"]);
		$expireTime = time()+(86400*30);
		if(mysqli_num_rows($db->query("SELECT sessionID FROM sessions WHERE sessionID = '".$oldSessID."'"))){
			$db->query("UPDATE sessions SET sessionID = '".$sessID."', expireTime = ".$expireTime." WHERE sessionID = '".$oldSessID."'");
			$response->responseData = $sessID;
		}else
			$response->responseData = "nima";
		respond();
	}









	if(isset($_REQUEST['sign-up'])){
		$username = adaptToQuery($_POST["username"]);
		$userID = time();
		$password = adaptToQuery($_POST["pass"]);
		$passEncrypted = password_hash($password, PASSWORD_BCRYPT);
		$expireTime = time()+(86400*30);

		if(strlen($username) > 30){
			$response->error = "Maximum length of username is 30. Your username has ".strlen($username)." characters";
			respond();
		}

		// while user with this userID exists add 1 and check again
		while(mysqli_num_rows($db->query("SELECT userID FROM users WHERE userID = ".$userID)))
			$userID++;

		// if username is already taken
		if(mysqli_num_rows($db->query("SELECT username FROM users WHERE username = '".$username."'")))
			$response->error = "User with this username already exists";
		else{
			$db->query("INSERT INTO users SET userID = ".$userID.", username = '".$username."', password = '".$passEncrypted."'");
			setcookie("userID", $userID, $expireTime, "/");

			$sessID = str_shuffle(password_hash($userID.time(), PASSWORD_BCRYPT));
			$db->query("INSERT INTO sessions SET userID = ".$userID.", sessionID = '".$sessID."', expireTime = ".$expireTime);
			setcookie("sessID", $sessID, $expireTime, "/");

			// add folder 'BIN' and 'Start' and set default icons size for a new user
				$db->query("INSERT INTO folders SET userID = ".$userID.", orderID = 0, folderID = ".$userID.", name = 'BIN'"); //BIN folder has the same ID as userID
				// while foler with this userID exists add 1 and check again
					$folder = time(); while(mysqli_num_rows($db->query("SELECT folderID FROM folders WHERE folderID = ".$folder))) $folder++;
					$db->query("INSERT INTO folders SET userID = ".$userID.", orderID = 1, folderID = ".$folder.", name = 'Start'");
				$db->query("INSERT INTO settings SET userID = ".$userID.", iconSize = 100");

			$response->log = "Registered as '".$username."'";
			$resp = array();
				array_push($resp, $userID);
				array_push($resp, $username);
			$response->responseData = $resp;
		}
		respond();
	}









	if(isset($_REQUEST['log-in'])){
		$username = adaptToQuery($_POST["username"]);
		$password = adaptToQuery($_POST["pass"]);
		$expireTime = time()+(86400*30);

		$hashPass = @$db->query("SELECT password FROM users WHERE username = '".$username."'")->fetch_object()->password;
		if(password_verify($password, $hashPass)){
			$user = $db->query("SELECT userID, username FROM users WHERE username = '".$username."'")->fetch_object(); // it is used to get original upper/lowerCases
			$userID = $user->userID;
			$username = $user->username;
			setcookie("userID", $userID, $expireTime, "/");

			$sessID = str_shuffle(password_hash($userID.time(), PASSWORD_BCRYPT));
			$db->query("INSERT INTO sessions SET userID = ".$userID.", sessionID = '".$sessID."', expireTime = ".$expireTime);
			setcookie("sessID", $sessID, $expireTime, "/");

			$response->log = "Logged in as '".$username."'";
			$resp = array();
				array_push($resp, $userID);
				array_push($resp, $username);
			$response->responseData = $resp;
		}
		else
			$response->error = "Wrong login or password. Try again";
		respond();
	}









	if(isset($_REQUEST['log-out'])){
		setcookie("userID", "", 0, "/");	// delete cookie
		$db->query("DELETE FROM sessions WHERE sessionID = '".$_COOKIE["sessID"]."'");
		setcookie("sessID", "", 0, "/");
		$response->log = "Successfully logged out";
		respond();
	}









	if(isset($_REQUEST['addIcon'])){
		$ID = adaptToQuery($_POST["ID"]);
		$folder = adaptToQuery($_POST["folder"]);
		$URL = adaptToQuery($_POST["URL"]);
		$folderName = @$db->query("SELECT name FROM folders WHERE userID = ".$userID." AND folderID = ".$folder)->fetch_object()->name;

		if(strlen($URL) > 500){
			$response->error = 'URL is too long. Please shorten it by using for example "Google URL Shortener"';
			respond();
		}

		// prevent from adding icon with ID of already existing icon
		while(mysqli_num_rows($db->query("SELECT ID FROM icons WHERE ID = ".$ID)))
			$ID++;

		$folderIconsNum = $db->query("SELECT COUNT(*) AS count FROM iconsorder WHERE folderID = ".$folder)->fetch_object()->count;
		$db->query("INSERT INTO iconsorder SET folderID = ".$folder.", orderID = ".$folderIconsNum.", ID = ".$ID);
		$db->query("INSERT INTO icons SET ID = ".$ID.", URL = '".$URL."'");

		$response->log = "Icon added to folder '".$folderName."'";
		$response->responseData = $ID;
		respond();
	}









	if(isset($_REQUEST['deleteIcon'])){
		$ID = adaptToQuery($_POST["ID"]);
		$folder = $db->query("SELECT folderID FROM iconsorder WHERE ID = ".$ID)->fetch_object()->folderID;

		deleteWithOrderIdDecrease("iconsorder", "ID", $ID, "folderID", $folder);	// delete icon with selected "ID" and "folderID" from table "iconsorder", and decrease every next "orderID" by 1

		$image = $db->query("SELECT image FROM icons WHERE ID = ".$ID)->fetch_object()->image;
		$db->query("DELETE FROM icons WHERE ID = ".$ID);
		$response->log = "Icon removed from server";

		if($image){
			@unlink('../images/icons/'.$image);
			$response->log .= "<br>Removed file ".$image;
		}
		respond();
	}









	if(isset($_REQUEST['saveOrder'])){
		$folder = adaptToQuery($_POST["folder"]);
		$folderName = @$db->query("SELECT name FROM folders WHERE userID = ".$userID." AND folderID = ".$folder)->fetch_object()->name;


		$newOrder = json_decode($_POST["order"]);
		$iconsInDB = selectColumnToArray("iconsorder", "ID", "folderID", $folder);		// writes to array elements from column "ID" of table "iconsorder", for selected value in column "folder"

		if((count($newOrder) == count($iconsInDB)) && !array_diff($newOrder,$iconsInDB)){
			for($i=0; $i<count($newOrder); $i++){
				$db->query("
					UPDATE iconsorder SET ID = ".adaptToQuery($newOrder[$i])."
					WHERE orderID = ".$i." AND folderID = ".$folder."
				");
			}
			$response->log = "Icons order in folder '".$folderName."' saved";
		}
		else{
			$response->error = "Icons sent to server were different than in database";
			$response->responseData = "reload";
		}
		respond();
	}









	if(isset($_REQUEST['saveNewAddress'])){
		$URL = adaptToQuery($_POST["URL"]);
		$ID = adaptToQuery($_POST["ID"]);
		$db->query("UPDATE icons SET URL = '".$URL."' WHERE ID = ".$ID);
		$response->log = "Icon URL changed to '".$URL."'";
		respond();
	}









	if(isset($_REQUEST['saveSize'])){
		$db->query("UPDATE settings SET iconSize = ".adaptToQuery($_POST["size"])." WHERE userID = ".$userID);
		$response->log = "Icons size saved";
		respond();
	}









	if(isset($_REQUEST['restoreDefaultBG'])){
		$BG = $db->query("SELECT background FROM settings WHERE userID = ".$userID)->fetch_object()->background; //check if user has his own custom background image
		if($BG){
			@unlink("../images/bg/".$BG.".jpg");
			$db->query("UPDATE settings SET background = 0 WHERE userID = ".$userID);
			$response->log = "Custom background removed";
		}
		else
			$response->log = "Default background is already set";

		respond();
	}









	if(isset($_REQUEST['saveBgFILE'])){
		if (empty($_FILES["image"]) || ($_FILES["image"]["error"])){
			$response->error = "Error occurred while sending background image";
		}
		else{
			$image = $_FILES["image"];
			$path = "../images/bg/";
			$BG = $db->query("SELECT background FROM settings WHERE userID = ".$userID)->fetch_object()->background; //check if user has his own custom background image
			$filename = ($BG) ? $BG : time(); // if he has -> get its name, and if not -> generate new name

			switch($image["type"]){
				case 'image/jpeg':
				case 'image/png':
				case 'image/gif':
					$extension = ".jpg";
				break;
				default:
					$response->error = "Incorrect file extension";
			}
			if($extension){
				if (move_uploaded_file($image["tmp_name"], $path.$filename.$extension)){
					$db->query("UPDATE settings SET background = ".$filename." WHERE userID = ".$userID);
					$response->responseData = $path.$filename.$extension;
					$response->log = "Background image saved";
				}
				else
					$response->error = "Error occurred while saving background image, probably access privileges on server are incorrect";
			}
		}
		respond();
	}









	if(isset($_REQUEST['saveImageFILE'])){
		if (empty($_FILES["image"]) || ($_FILES["image"]["error"])){
			$response->error = "Error occurred while sending image";
		}
		else{
			$image = $_FILES["image"];
			$path = "../images/icons/";
			$ID = adaptToQuery($image["name"]);

			switch($image["type"]){
				case 'image/jpeg':
					$extension = ".jpg";
				break;
				case 'image/png':
					$extension = ".png";
				break;
				case 'image/gif':
					$extension = ".gif";
				break;
				default:
					$response->error = "Incorrect file extension";
			}
			if(@$extension){
				if(file_exists($path.$ID.".jpg"))		unlink($path.$ID.".jpg");
				if(file_exists($path.$ID.".png"))		unlink($path.$ID.".png");
				if(file_exists($path.$ID.".gif"))		unlink($path.$ID.".gif");

				if (move_uploaded_file($image["tmp_name"], $path.$ID.$extension)){
					$db->query("UPDATE icons SET imageExt = '".$extension."?".time()."' WHERE ID = ".$ID);
					$response->log = "Image ".$ID.$extension." saved";
					$response->responseData = $ID.$extension."?".time();
				}
				else
					$response->error = "Error occurred while saving image, probably access privileges on server are incorrect";
			}
		}
		respond();
	}









	if(isset($_REQUEST['saveBgURL'])){
		$URL = $_POST["URL"];
		$path = "../images/bg/";
		$BG = $db->query("SELECT background FROM settings WHERE userID = ".$userID)->fetch_object()->background; //check if user has his own custom background image
		$filename = ($BG) ? $BG : time(); // if he has -> get its name, and if not -> generate new name

		$tmpFile = fopen($path.$filename.".tmp", 'w+');        	 	// temporarly save file to server with .tmp extension
		$cURL = curl_init($URL);
		curl_setopt($cURL, CURLOPT_FILE, $tmpFile);								// put to temp file
		curl_setopt($cURL, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($cURL, CURLOPT_TIMEOUT, 1000);
		curl_setopt($cURL, CURLOPT_USERAGENT, 'Mozilla/5.0');
		curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, FALSE);     		// for https connection
		curl_setopt($cURL, CURLOPT_SSL_VERIFYHOST, 2); 						// for https connection
		curl_exec($cURL);
		$extension = curl_getinfo($cURL, CURLINFO_CONTENT_TYPE);
		curl_close($cURL);
		fclose($tmpFile);

		switch($extension){
			case 'image/jpeg':
			case 'image/png':
			case 'image/gif':
				$extension = ".jpg";
			break;
			default:
				$response->info = "Wrong file extension or host problem. Try to save image on your computer, and add it from file";
				$extension = '';
		}

		if(@$extension){
			//if(imagejpeg( imagecreatefromstring(file_get_contents($path.$filename.".tmp"))  ,  $path.$filename.$extension))    // <- change .jpg, .png and .gif format to .jpg
			if(@copy($path.$filename.".tmp",  $path.$filename.$extension)){			// <- save .jpg, .png and .gif with extension .jpg without changing format and if succeded
				if($BG == 0)
					$db->query("UPDATE settings SET background = ".$filename." WHERE userID = ".$userID);

				$response->responseData = $path.$filename.$extension;
				$response->log = "Background saved";
			}
			else
				$response->error = "Error occurred while saving background image, probably access privileges on server are incorrect";
		}
		@unlink($path.$filename.".tmp");
		respond();
	}









	if(isset($_REQUEST['saveImageURL'])){
		$URL = $_POST["URL"];
		$path = "../images/icons/";
		$ID = adaptToQuery($_POST["ID"]);

		$tmpFile = fopen($path.$ID.".tmp", 'w+');        	 	// temporarly save file to server with .tmp extension
		$cURL = curl_init($URL);
		curl_setopt($cURL, CURLOPT_FILE, $tmpFile);								// put to temp file
		curl_setopt($cURL, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($cURL, CURLOPT_TIMEOUT, 1000);
		curl_setopt($cURL, CURLOPT_USERAGENT, 'Mozilla/5.0');
		curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, FALSE);     		// for https connection
		curl_setopt($cURL, CURLOPT_SSL_VERIFYHOST, 2); 						// for https connection
		curl_exec($cURL);
		$extension = curl_getinfo($cURL, CURLINFO_CONTENT_TYPE);
		curl_close($cURL);
		fclose($tmpFile);

		switch($extension){
			case 'image/jpeg':
				$extension = ".jpg";
			break;
			case 'image/png':
				$extension = ".png";
			break;
			case 'image/gif':
				$extension = ".gif";
			break;
			default:
				$response->info = "Wrong file extension or host problem. Try to save image on your computer, and add it from file";
				@unlink($path.$ID.".tmp");
				$extension = '';
		}

		if($extension){
			if(file_exists($path.$ID.".jpg"))		unlink($path.$ID.".jpg");
			if(file_exists($path.$ID.".png"))		unlink($path.$ID.".png");
			if(file_exists($path.$ID.".gif"))		unlink($path.$ID.".gif");

			if(@copy($path.$ID.".tmp",  $path.$ID.$extension)){
				$db->query("UPDATE icons SET imageExt = '".$extension."?".time()."' WHERE ID = ".$ID);
				$response->log = "Image ".$ID.$extension." saved";
			}
			else
				$response->error = "Error occurred while saving background image, probably access privileges on server are incorrect";
		}
		@unlink($path.$ID.".tmp");
		respond();
	}









	if(isset($_REQUEST['addFolder'])){
		$name_unadapted = $_POST["name"];
		$name = adaptToQuery($_POST["name"]);

		if(strlen($name_unadapted) > 30){
			$response->error = "Maximum length of folder name is 30. Your name has ".strlen($name_unadapted)." characters";
			$response->responseData = "nameTooLong";
			respond();
		}

		$folderInDB = @$db->query("SELECT name FROM folders WHERE userID = ".$userID." AND name = '".$name."'")->fetch_object()->name;
		if($folderInDB){
			$response->info = "Folder with name '".$name_unadapted."' already exists in database";
			$response->responseData = "alreadyExist";
			respond();
		}

		$foldersNum = $db->query("SELECT COUNT(*) AS count FROM folders WHERE userID = ".$userID)->fetch_object()->count;
		$folder = time();
		// while folder with this folderID exists add 1 and check again
		while(mysqli_num_rows($db->query("SELECT folderID FROM folders WHERE folderID = ".$folder)))
			$folder++;
		$db->query("INSERT INTO folders SET userID = ".$userID.", orderID = ".$foldersNum.", folderID = ".$folder.", name = '".$name."'");
		$response->log = "Folder '".$name_unadapted."' added";

		$resp = array();
			array_push($resp, $folder);
			array_push($resp, $name_unadapted);
		$response->responseData = $resp;
		respond();
	}









	if(isset($_REQUEST['deleteFolder'])){
		$folder = adaptToQuery($_POST["folder"]);
		$folderName = @$db->query("SELECT name FROM folders WHERE userID = ".$userID." AND folderID = ".$folder)->fetch_object()->name;

		// Last folder and folder "BIN" can't be removed
		$foldersNum = $db->query("SELECT COUNT(*) AS count FROM folders WHERE userID = ".$userID)->fetch_object()->count;
		if(($foldersNum <= 2) || ($folder == "BIN")){
			$response->info = "This folder can't be removed";
			$response->responseData = "cantDelete";
		}
		else{
			moveFolderContent($folder, $userID); // BIN ID is the same as userID
			deleteWithOrderIdDecrease("folders", "folderID", $folder, "userID", $userID);	// delete folder with selected "folderID" and "userID" from table "folders", and decrease every next "orderID" by 1
			$response->log = "Folder '".$folderName."' deleted. It's content is now inside bin";
		}
		respond();
	}









	if(isset($_REQUEST['renameFolder'])){
		$folder = adaptToQuery($_POST["folder"]);
		$newName = adaptToQuery($_POST["newName"]);
		$oldName = @$db->query("SELECT name FROM folders WHERE userID = ".$userID." AND folderID = ".$folder)->fetch_object()->name;

		// if old folder doesn't exist
		if(!$oldName){
			$response->error = "This folder doesn't exist";
			$response->responseData = "reload";
			respond();
		}

		$folderWithNewName = @$db->query("SELECT folderID, name FROM folders WHERE userID = ".$userID." AND name = '".$newName."'")->fetch_object();
		// if folder with new name already exist
		if($folderWithNewName){
			deleteWithOrderIdDecrease("folders", "folderID", $folder, "userID", $userID); // delete folder with selected "name" and "userID" from table "folders", and decrease every next "orderID" by 1
			moveFolderContent($folder, $folderWithNewName->folderID); // move folders content to folder with new name
			$response->log = "Content of folder moved to existing folder '".$folderWithNewName->name."'";
			$response->responseData = "alreadyExist";
		}
		else{
			$db->query("UPDATE folders SET name = '".$newName."' WHERE userID = ".$userID." AND folderID = ".$folder);
			$response->log = "Folder renamed to '".$newName."'";
		}
		respond();
	}









	if(isset($_REQUEST['moveIconToFolder'])){
		$ID = adaptToQuery($_POST["ID"]);
		$oldFolder = @$db->query("SELECT folderID FROM iconsorder WHERE ID = ".$ID)->fetch_object()->folderID;
		$newFolder = ($_POST["folder"] == "BIN") ? $userID : adaptToQuery($_POST["folder"]); // BIN ID is the same as userID
		$folderInDB = $db->query("SELECT name FROM folders WHERE userID = ".$userID." AND folderID = ".$newFolder)->fetch_object();

		// if folder with this ID doesn't exist
		if(!$folderInDB){
			$response->error = "This folder doesn't exist";
			$response->responseData = "reload";
			respond();
		}

		// if icon with this ID doesn't exist
		if(!$oldFolder){
			$response->error = "This icon doesn't exist";
			$response->responseData = "reloadIcons";
			respond();
		}

		// if icon is already in this folder
		if($newFolder == $oldFolder){
			$response->info = "Selected icon is already in this folder";
			$response->responseData = "alreadyInFolder";
			respond();
		}

		// decrease every next "orderID" by 1
		$oldFolderIconsNum = $db->query("SELECT COUNT(*) AS count FROM iconsorder WHERE folderID = ".$oldFolder)->fetch_object()->count;
		$orderID = $db->query("SELECT orderID FROM iconsorder WHERE ID = ".$ID)->fetch_object()->orderID;
		for($i = $orderID+1; $i < $oldFolderIconsNum; $i++){
			$db->query("UPDATE iconsorder SET orderID = orderID-1 WHERE orderID = ".$i." AND folderID = ".$oldFolder);
		}

		// and then move selected icon to the end of a new folder
		$newFolderIconsNum = $db->query("SELECT COUNT(*) AS count FROM iconsorder WHERE folderID = ".$newFolder)->fetch_object()->count;
		$db->query("UPDATE iconsorder SET orderID = ".$newFolderIconsNum.", folderID = ".$newFolder." WHERE ID = ".$ID);

		$response->log = "Icon moved to folder '".$folderInDB->name."'";
		if($folderInDB->name == "BIN") $response->log = "Icon moved to bin";
		respond();
	}









	if(isset($_REQUEST['saveFoldersOrder'])){
		$newOrder = json_decode($_POST["order"]);

		$foldersInDB = selectColumnToArray("folders", "folderID", "userID", $userID);	// writes to array elements from column "folderID" of table "folders", for selected user
		// if arrays have same length and values
		if((count($newOrder) == count($foldersInDB)) && !array_diff($newOrder,$foldersInDB)){
			for($i=0; $i<count($newOrder); $i++){
				$db->query("
					UPDATE folders SET orderID = ".($i+1)."
					WHERE userID = ".$userID." AND folderID = ".adaptToQuery($newOrder[$i])
				);
			}
			$response->log = "Folders order saved";
		}
		else{
			$response->error = "Folders sent to server are different than in database";
			$response->responseData = "reload";
		}
		respond();
	}









	function adaptToQuery($string){
		global $db;
		return mysqli_real_escape_string($db, $string);
	}




	function deleteWithOrderIdDecrease($tableName, $columnName, $value, $detailColumnName=0, $detailValue=0){
		global $db;
		// finding number of elements matching query
			$selectedCount = $db->query(
				"SELECT COUNT(*) AS count FROM ".$tableName." ".
				"WHERE ".(($detailColumnName) ? $detailColumnName." = '".$detailValue."'" : '')
			)->fetch_object()->count;

		// get orderID of deleted element
			$orderID = $db->query("SELECT orderID FROM ".$tableName." WHERE ".$columnName." = '".$value."'")->fetch_object()->orderID;

		// delete element
			$db->query("DELETE FROM ".$tableName." WHERE ".$columnName." = '".$value."'");

		// decreasing every next "orderID" by 1
			for($i = $orderID+1; $i < $selectedCount; $i++){
				$db->query(
					"UPDATE ".$tableName." SET orderID = orderID-1 WHERE orderID = ".$i." ".
					(($detailColumnName) ? "AND ".$detailColumnName." = '".$detailValue."'" : '')
				);
			}
	}




	function selectColumnToArray($tableName, $columnName, $detailColumnName=0, $detailValue=0){
		global $db;
		$array = array();
		$results = $db->query(
			"SELECT ".$columnName." FROM ".$tableName." ".
			"WHERE ".(($detailColumnName) ? $detailColumnName." = '".$detailValue."'" : '')
		);

		while($row = $results->fetch_object())
			array_push($array, $row->$columnName);
		return $array;
	}




	function moveFolderContent($oldFolder, $newFolder){
		global $db;
		$oldFolder = adaptToQuery($oldFolder);
		$newFolder = adaptToQuery($newFolder);
		$newFolderIconsNum = adaptToQuery($db->query("SELECT COUNT(*) AS count FROM iconsorder WHERE folderID = ".$newFolder)->fetch_object()->count);

		$folderIcons = $db->query("SELECT ID FROM iconsorder WHERE folderID = ".$oldFolder);
		while($row = $folderIcons->fetch_object()){
			$ID = $row->ID;
			$db->query("UPDATE iconsorder SET orderID = ".$newFolderIconsNum++.", folderID = ".$newFolder." WHERE ID = ".$ID);
		}
	}




/*
	$fname = "ajax_info.txt";
	$file = fopen($fname, 'w+');
	fwrite($file, "testtest");
	fclose($file);
*/
?>
