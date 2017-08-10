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
	$db = @new mysqli($host, $user, $password, $dbname);
	if ($db->connect_error) {
		$response->error = "Database connection error. <br>";
		$response->error .= "Error number ".$db->connect_errno.": ".mb_convert_encoding($db->connect_error, 'utf-8');

	}
	session_start();









	if(isset($_REQUEST['sign-up'])){
		$user = adaptToQuery($_POST["user"]);
		$password = adaptToQuery($_POST["pass"]);
		$passEncrypted = hash('sha512',$password);

		if(strlen($user) > 30){
			$response->error = "Maximum length of username is 30. Your username has ".strlen($user)." characters";
			respond();
		}

		// check if this username already exists in database
		$checkUser = mysqli_num_rows($db->query("SELECT username FROM users WHERE username = '".$user."'"));
		if(!$checkUser){
			$db->query("INSERT INTO users SET username = '".$user."', password = '".$passEncrypted."'");
			$_SESSION["user"] = $user;
			$response->log = "Registered as '".$user."'";
			$response->responseData = $user;
		}
		else
			$response->error = "User with this username already exists";
			respond();
	}









	if(isset($_REQUEST['log-in'])){
		$user = adaptToQuery($_POST["user"]);
		$password = adaptToQuery($_POST["pass"]);
		$passEncrypted = hash('sha512',$password);

		// check if user with this username and password exists in database
		$checkUser = mysqli_num_rows($db->query("SELECT username FROM users WHERE username = '".$user."' AND password = '".$passEncrypted."'"));
		if($checkUser){
			$user = $db->query("SELECT username FROM users WHERE username = '".$user."'")->fetch_object()->username; // it is used to get original upper/lowerCases
			$_SESSION["user"] = $user;
			$response->log = "Logged in as '".$user."'";
			$response->responseData = $user;
		}
		else
			$response->error = "Wrong login or password. Try again";
	}









	// if user isn't logged in - return response and stop executing further code
	if(!@$_SESSION["user"]){
		$response->responseData = "log-in";
		respond();
	}
	$user = adaptToQuery($_SESSION["user"]);









		if(isset($_REQUEST['log-out'])){
			session_destroy();
			$response->log = "Successfully logged out";
		}









	if(isset($_REQUEST['addIcon'])){
		$ID = adaptToQuery($_POST["ID"]);
		$folder = adaptToQuery($_POST["folder"]);
		$URL = adaptToQuery($_POST["URL"]);

		$folderCount = $db->query("SELECT COUNT(*) AS count FROM iconsorder WHERE username = '".$user."' AND folder = '".$folder."'")->fetch_object()->count;

		//todo -> check if icon with selected ID exist
		//todo -> if it exist add +1 to its id in while Loop
		//todo -> after insterting it to database return correct ID by "$response->responseData"
		$db->query("INSERT INTO iconsorder SET username = '".$user."', orderID = '".$folderCount."', ID = '".$ID."', folder = '".$folder."'");
		$db->query("INSERT INTO icons SET ID = '".$ID."', URL = '".$URL."'");

		$response->log = "Icon added to folder '".$folder."'";
	}









	if(isset($_REQUEST['deleteIcon'])){
		$ID = adaptToQuery($_POST["ID"]);
		$folder = $db->query("SELECT folder FROM iconsorder WHERE username = '".$user."' AND ID = '".$ID."'")->fetch_object()->folder;

		deleteWithOrderIdDecrease("iconsorder", "ID", $ID, "folder", $folder);	// delete icon with selected "ID" and "folder" from table "iconsorder", and decrease every next "orderID" by 1

		$image = $db->query("SELECT image FROM icons WHERE ID = '".$ID."'")->fetch_object()->image;
		$db->query("DELETE FROM icons WHERE ID = '".$ID."'");
		$response->log = "Icon removed from server";

		if($image){
			@unlink('../images/icons/'.$image);
			$response->log .= "<br>Removed file ".$image;
		}
	}









	if(isset($_REQUEST['saveOrder'])){
		$folder = adaptToQuery($_POST["folder"]);
		$newOrder = json_decode($_POST["order"]);
		$iconsInDB = selectColumnToArray("iconsorder", "ID", "folder", $folder);		// writes to array elements from column "ID" of table "iconsorder", for selected value in column "folder"

		//print_r($newOrder);
		//rint_r("<br><br><br><br><br>");
		//print_r($iconsInDB);


		if((count($newOrder) == count($iconsInDB)) && !array_diff($newOrder,$iconsInDB)){
			for($i=0; $i<count($newOrder); $i++){
				$db->query("
					UPDATE iconsorder SET ID = '".adaptToQuery($newOrder[$i])."'
					WHERE username = '".$user."' AND orderID = ".$i." AND folder = '".$folder."'
				");
			}
			$response->log = "Icons order in folder '".$folder."' saved";
		}
		else{
			$response->error = "Icons sent to server were different than in database. Page should reload in a second or less";
			$response->responseData = "reload";
		}
	}









	if(isset($_REQUEST['saveNewAddress'])){
		$URL = adaptToQuery($_POST["URL"]);
		$ID = adaptToQuery($_POST["ID"]);
		$db->query("UPDATE icons SET URL = '".$URL."' WHERE ID = '".$ID."'");
		$response->log = "Icon URL changed to '".$URL."'";
	}









	if(isset($_REQUEST['saveSize'])){
		$db->query("UPDATE settings SET size = ".adaptToQuery($_POST["size"])." WHERE username = '".$user."'");
		$response->log = "Icons size saved";
	}









	if(isset($_REQUEST['saveBgFILE'])){
		if (empty($_FILES["image"]) || ($_FILES["image"]["error"])){
			$response->error = "Error occurred while sending background image";
		}
		else{
			$image = $_FILES["image"];
			$path = "../images/";
			$filename = "bg";

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
				if (move_uploaded_file($image["tmp_name"], $path.$filename.$extension))
					$response->log = "Background image saved";
				else
					$response->error = "Error occurred while saving background image, probably access privileges on server are incorrect";
			}
		}
	}









	if(isset($_REQUEST['saveImageFILE'])){
		if (empty($_FILES["image"]) || ($_FILES["image"]["error"])){
			$response->error = "Error occurred while sending image";
		}
		else{
			$image = $_FILES["image"];
			$path = "../images/icons/";
			$filename = adaptToQuery($image["name"]);

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
				if(file_exists($path.$filename.".jpg"))		unlink($path.$filename.".jpg");
				if(file_exists($path.$filename.".png"))		unlink($path.$filename.".png");
				if(file_exists($path.$filename.".gif"))		unlink($path.$filename.".gif");

				if (move_uploaded_file($image["tmp_name"], $path.$filename.$extension)){
					$db->query("UPDATE icons SET image = '".$filename.$extension."' WHERE ID = '".$filename."'");
					$response->log = "Image ".$filename.$extension." saved";
				}
				else
					$response->error = "Error occurred while saving image, probably access privileges on server are incorrect";
			}
		}
	}









	if(isset($_REQUEST['saveBgURL'])){
		$URL = $_POST["URL"];
		$path = "../images/";
		$filename = "bg";

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
			if(@copy($path.$filename.".tmp",  $path.$filename.$extension))			// <- save .jpg, .png and .gif with extension .jpg without changing format
				$response->log = "Background saved";
			else
				$response->error = "Error occurred while saving background image, probably access privileges on server are incorrect";
		}
		@unlink($path.$filename.".tmp");
	}









	if(isset($_REQUEST['saveImageURL'])){
		$URL = $_POST["URL"];
		$path = "../images/icons/";
		$filename = adaptToQuery($_POST["ID"]);

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
				@unlink($path.$filename.".tmp");
				$extension = '';
		}

		if($extension){
			if(file_exists($path.$filename.".jpg"))		unlink($path.$filename.".jpg");
			if(file_exists($path.$filename.".png"))		unlink($path.$filename.".png");
			if(file_exists($path.$filename.".gif"))		unlink($path.$filename.".gif");

			if(@copy($path.$filename.".tmp",  $path.$filename.$extension)){
				$db->query("UPDATE icons SET image='".$filename.$extension."' WHERE ID='".$filename."'");
				$response->log = "Image ".$filename.$extension." saved";
			}
			else
				$response->error = "Error occurred while saving background image, probably access privileges on server are incorrect";
		}
		@unlink($path.$filename.".tmp");
	}









	if(isset($_REQUEST['addFolder'])){
		$name = adaptToQuery($_POST["name"]);

		$foldersInDB = selectColumnToArray("folders", "name");	// writes to array elements from column "name" of table "folders"
		if(in_array($name, $foldersInDB)){
			$response->info = "This folder is already in database";
			$response->responseData = "alreadyExist";
		}
		else{
			$foldersCount = $db->query("SELECT COUNT(*) AS count FROM folders WHERE username = '".$user."'")->fetch_object()->count;
			$db->query("INSERT INTO folders SET username = '".$user."', orderID = ".$foldersCount.", name = '".$name."'");

			$response->log = "Folder '".$name."' added";
		}
	}









	if(isset($_REQUEST['deleteFolder'])){
		$name = adaptToQuery($_POST["name"]);

		if((strtoupper($name) == "START") || (strtoupper($name) == "BIN"))
			$response->info = "You can't delete this folder";

		else{
			moveFolderContent($name, "BIN");
			deleteWithOrderIdDecrease("folders", "name", $name);	// delete folder with selected "name" from table "folders", and decrease every next "orderID" by 1
			$response->log = "Folder '".$name."' deleted, it's content is now inside bin";
		}
	}









	if(isset($_REQUEST['renameFolder'])){
		$oldName = adaptToQuery($_POST["oldName"]);
		$newName = adaptToQuery($_POST["newName"]);

		if($db->query("SELECT name FROM folders WHERE name = '".$oldName."'")->fetch_object()){ // if this folder exist
			if((strtoupper($oldName) == "START") || (strtoupper($oldName) == "BIN"))
				$response->info = "You can't change this folder's name";

			else if($oldName == $newName)
				$response->log = "xD ?";

			else{
				moveFolderContent($oldName, $newName);

				$foldersInDB = selectColumnToArray("folders", "name");	// writes to array elements from column "name" of table "folders"
				if(in_array($newName, $foldersInDB)){
					$response->log = "Content of folder moved to existing folder '".$newName."'";
					$response->responseData = "alreadyExist";
					deleteWithOrderIdDecrease("folders", "name", $oldName);	// delete folder with selected "name" from table "folders", and decrease every next "orderID" by 1
				}
				else{
					$db->query("UPDATE folders SET name = '".$newName."' WHERE username = '".$user."' AND name = '".$oldName."'");
					$response->log = "Folder renamed from '".$oldName."' to '".$newName."'";
				}
			}
		}
		else{
			$response->error = "This folder doesn't exist anymore";
			$response->responseData = "reload";
		}
	}









	if(isset($_REQUEST['moveIconToFolder'])){
		$ID = adaptToQuery($_POST["ID"]);
		$newFolder = adaptToQuery($_POST["folder"]);
		// if folder with this name exist
		if(($db->query("SELECT name FROM folders WHERE username = '".$user."' AND name = '".$newFolder."'")->fetch_object()) || ($newFolder == "BIN")){
			$oldFolder = $db->query("SELECT folder FROM iconsorder WHERE username = '".$user."' AND ID = '".$ID."'")->fetch_object()->folder;

			if(strtoupper($newFolder) == strtoupper($oldFolder)){
				$response->info = "Selected icon is already in this folder";
				$response->responseData = "alreadyInFolder";
			}
			else{
				// decrease every next "orderID" by 1
					$oldfolderCount = $db->query("SELECT COUNT(*) AS count FROM iconsorder WHERE username = '".$user."' AND folder = '".$oldFolder."'")->fetch_object()->count;
					$orderID = $db->query("SELECT orderID FROM iconsorder WHERE username = '".$user."' AND ID = '".$ID."'")->fetch_object()->orderID;
					for($i = $orderID+1; $i < $oldfolderCount; $i++){
						$db->query("UPDATE iconsorder SET orderID = orderID-1 WHERE username = '".$user."' AND orderID = ".$i." AND folder = '".$oldFolder."'");
					}

				// and then move selected icon to the end of a new folder
					$newfolderCount = $db->query("SELECT COUNT(*) AS count FROM iconsorder WHERE username = '".$user."' AND folder = '".$newFolder."'")->fetch_object()->count;
					$db->query("UPDATE iconsorder SET orderID = ".$newfolderCount.", folder = '".$newFolder."' WHERE username = '".$user."' AND ID = '".$ID."'");

				$response->log = "Icon moved to folder '".$newFolder."'";
				if($newFolder == "BIN") $response->log = "Icon moved to bin";
			}
		}
		else{
			$response->error = "This folder doesn't exist anymore";
			$response->responseData = "reload";
		}
	}









	if(isset($_REQUEST['saveFoldersOrder'])){
		$newOrder = json_decode($_POST["order"]);

		$foldersInDB = selectColumnToArray("folders", "name");	// writes to array elements from column "name" of table "folders"
		// if arrays have same length and values
		if((count($newOrder) == count($foldersInDB)) && !array_diff($newOrder,$foldersInDB)){
			for($i=0; $i<count($newOrder); $i++){
				$db->query("
					UPDATE folders SET name = '".adaptToQuery($newOrder[$i])."'
					WHERE username = '".$user."' AND orderID = ".$i
				);
			}
			$response->log = "Folders order saved";
		}
		else{
			$response->error = "Folders sent to server are different than in database";
			$response->responseData = "reload";
		}
	}









	respond();









	function adaptToQuery($string){
		global $db;
		return mysqli_real_escape_string($db, $string);
	}




	function deleteWithOrderIdDecrease($tableName, $columnName, $value, $detailColumnName=0, $detailValue=0){
		global $db, $user;
		// finding number of elements matching query
			$selectedCount = $db->query(
				"SELECT COUNT(*) AS count FROM ".$tableName." ".
				"WHERE username = '".$user."' ".
				(($detailColumnName) ? "AND ".$detailColumnName." = '".$detailValue."'" : '')
			)->fetch_object()->count;

		// creating array of elements orderIDs which will be deleted
			$orderID = $db->query("SELECT orderID FROM ".$tableName." WHERE username = '".$user."' AND ".$columnName." = '".$value."'")->fetch_object()->orderID;

		// deleting elements
			$db->query("DELETE FROM ".$tableName." WHERE username = '".$user."' AND ".$columnName." = '".$value."'");

		// decreasing every next "orderID" by 1
			for($i = $orderID+1; $i < $selectedCount; $i++){
				$db->query(
					"UPDATE ".$tableName." SET orderID = orderID-1 WHERE username = '".$user."' AND orderID = ".$i." ".
					(($detailColumnName) ? "AND ".$detailColumnName." = '".$detailValue."'" : '')
				);
			}
	}




	function selectColumnToArray($tableName, $columnName, $detailColumnName=0, $detailValue=0){
		global $db, $user;
		$array = array();
		$results = $db->query(
			"SELECT ".$columnName." FROM ".$tableName." ".
			"WHERE username = '".$user."' ".
			(($detailColumnName) ? "AND ".$detailColumnName." = '".$detailValue."'" : '')
		);
		while($row = $results->fetch_object())
			array_push($array, $row->$columnName);
		return $array;
	}




	function moveFolderContent($oldFolder, $newFolder){
		global $db, $user;
		$oldFolder = adaptToQuery($oldFolder);
		$newFolder = adaptToQuery($newFolder);
		$newFolderCount = adaptToQuery($db->query("SELECT COUNT(*) AS count FROM iconsorder WHERE username = '".$user."' AND folder = '".$newFolder."'")->fetch_object()->count);

		$iconsInOldFolder = array();
		$results = $db->query("SELECT ID FROM iconsorder WHERE username = '".$user."' AND folder = '".$oldFolder."'");
		while($row = $results->fetch_object()){
			$ID = $row->ID;
			$db->query("UPDATE iconsorder SET orderID = ".$newFolderCount++.", folder = '".$newFolder."' WHERE username = '".$user."' AND ID = '".$ID."'");
		}
	}



/*
	$fname = "ajax_info.txt";
	$file = fopen($fname, 'w+');
	fwrite($file, "testtest");
	fclose($file);
*/
?>
