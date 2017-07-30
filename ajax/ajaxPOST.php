<?php
	class Response {
		public $log;
		public $info;
		public $error;
		public $responseData;
	}
	$response = new Response();

	include 'logDB.php';
	$db = @new mysqli($host, $user, $password, $dbname);
	if ($db->connect_error) {
		$response->error = "Database connection error. <br>";
		$response->error .= "Error number ".$db->connect_errno.": ".mb_convert_encoding($db->connect_error, 'utf-8');
	}
	else{


		if(isset($_REQUEST['addIcon'])){
			$ID = adaptToQuery($db, $_POST["ID"]);
			$folder = adaptToQuery($db, $_POST["folder"]);
			$URL = adaptToQuery($db, $_POST["URL"]);

			$folderCount = $db->query("SELECT COUNT(*) AS count FROM iconsorder WHERE folder = '".$folder."'")->fetch_object()->count;

			$db->query("INSERT INTO iconsorder SET orderID = '".$folderCount."', ID = '".$ID."', folder = '".$folder."'");
			$db->query("INSERT INTO icons SET ID = '".$ID."', URL = '".$URL."'");

			$response->log = "Icon added to folder '".$folder."'";
		}


		if(isset($_REQUEST['deleteIcon'])){
			$ID = adaptToQuery($db, $_POST["ID"]);
			$folder = $db->query("SELECT folder FROM iconsorder WHERE ID = '".$ID."'")->fetch_object()->folder;

			deleteWithOrderIdDecrease($db, "iconsorder", "ID", $ID, "folder", $folder);	// delete icon with selected "ID" and "folder" from table "iconsorder", and decrease every next "orderID" by 1

			$image = $db->query("SELECT image FROM icons WHERE ID = '".$ID."'")->fetch_object()->image;
			$db->query("DELETE FROM icons WHERE ID = '".$ID."'");
			$response->log = "Icon removed from server";

			if($image){
				@unlink('../images/icons/'.$image);
				$response->log .= "<br>Removed file ".$image;
			}
		}


		if(isset($_REQUEST['saveOrder'])){
			$folder = adaptToQuery($db, $_POST["folder"]);
			$newOrder = json_decode($_POST["order"]);
			$iconsInDB = selectColumnToArray($db, "iconsorder", "ID", "folder", $folder);		// writes elements from column "ID" of table "iconsorder", for selected value in column "folder", to array

			if((count($newOrder) == count($iconsInDB)) && !array_diff($newOrder,$iconsInDB)){
				for($i=0; $i<count($newOrder); $i++){
					$db->query("
						UPDATE iconsorder SET ID = '".adaptToQuery($db, $newOrder[$i])."'
						WHERE orderID = ".$i." AND folder = '".$folder."'
					");
				}
				$response->log = "Icons order in folder '".$folder."' saved";
			}
			else{
				$response->error = "Icons sent to server are different than in database";
				$response->responseData = "reload";
			}
		}


		if(isset($_REQUEST['saveNewAddress'])){
			$URL = adaptToQuery($db, $_POST["URL"]);
			$ID = adaptToQuery($db, $_POST["ID"]);
			$db->query("UPDATE icons SET URL = '".$URL."' WHERE ID = '".$ID."'");
			$response->log = "Icon URL changed to '".$URL."'";
		}


		if(isset($_REQUEST['saveSize'])){
			$db->query("UPDATE settings SET size = ".adaptToQuery($db, $_POST["size"]));
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
				$filename = adaptToQuery($db, $image["name"]);

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
				if($extension){
					if(file_exists($path.$filename.".jpg"))		unlink($path.$filename.".jpg");
					if(file_exists($path.$filename.".png"))		unlink($path.$filename.".png");
					if(file_exists($path.$filename.".gif"))		unlink($path.$filename.".gif");

					if (move_uploaded_file($image["tmp_name"], $path.$filename.$extension)){
						$db->query("UPDATE icons SET image='".$filename.$extension."' WHERE ID='".$filename."'");
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

			if($extension){
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
			$filename = adaptToQuery($db, $_POST["ID"]);

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
			$name = adaptToQuery($db, $_POST["name"]);

			$foldersInDB = selectColumnToArray($db, "folders", "name");	// writes elements from column "name" of table "folders", to array
			if(in_array($name, $foldersInDB)){
				$response->info = "This folder is already in database";
				$response->responseData = "alreadyExist";
			}
			else{
				$foldersCount = $db->query("SELECT COUNT(*) AS count FROM folders")->fetch_object()->count;
				$db->query("INSERT INTO folders SET orderID = ".$foldersCount.", name = '".$name."'");

				$response->log = "Folder '".$name."' added";
			}
		}


		if(isset($_REQUEST['deleteFolder'])){
			$name = adaptToQuery($db, $_POST["name"]);

			if((strtoupper($name) == "START") || (strtoupper($name) == "BIN"))
				$response->info = "You can't delete this folder";

			else{
				moveFolderContent($db, $name, "BIN");
				deleteWithOrderIdDecrease($db, "folders", "name", $name);	// delete folder with selected "name" from table "folders", and decrease every next "orderID" by 1
				$response->log = "Folder '".$name."' deleted, it's content is now inside bin";
			}
		}


		if(isset($_REQUEST['renameFolder'])){
			$oldName = adaptToQuery($db, $_POST["oldName"]);
			$newName = adaptToQuery($db, $_POST["newName"]);

			if($db->query("SELECT name FROM folders WHERE name = '".$oldName."'")->fetch_object()){ // if this folder exist
				if((strtoupper($oldName) == "START") || (strtoupper($oldName) == "BIN"))
					$response->info = "You can't change this folder's name";

				else if($oldName == $newName)
					$response->log = "xD ?";

				else{
					moveFolderContent($db, $oldName, $newName);

					$foldersInDB = selectColumnToArray($db, "folders", "name");	// writes elements from column "name" of table "folders", to array
					if(in_array($newName, $foldersInDB)){
						$response->log = "Content of folder moved to existing folder '".$newName."'";
						$response->responseData = "alreadyExist";
						deleteWithOrderIdDecrease($db, "folders", "name", $oldName);	// delete folder with selected "name" from table "folders", and decrease every next "orderID" by 1
					}
					else{
						$db->query("UPDATE folders SET name = '".$newName."' WHERE name = '".$oldName."'");
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
			$ID = adaptToQuery($db, $_POST["ID"]);
			$newFolder = adaptToQuery($db, $_POST["folder"]);
			if(($db->query("SELECT name FROM folders WHERE name = '".$newFolder."'")->fetch_object()) || ($newFolder == "BIN")){ // if folder with this name exist
				$oldFolder = $db->query("SELECT folder FROM iconsorder WHERE ID = '".$ID."'")->fetch_object()->folder;

				if(strtoupper($newFolder) == strtoupper($oldFolder)){
					$response->info = "Selected icon is already in this folder";
					$response->responseData = "alreadyInFolder";
				}
				else{
					// decrease every next "orderID" by 1
						$oldfolderCount = $db->query("SELECT COUNT(*) AS count FROM iconsorder WHERE folder = '".$oldFolder."'")->fetch_object()->count;
						$orderID = $db->query("SELECT orderID FROM iconsorder WHERE ID = '".$ID."'")->fetch_object()->orderID;
						for($i = $orderID+1; $i < $oldfolderCount; $i++){
							$db->query("UPDATE iconsorder SET orderID = orderID-1 WHERE orderID = ".$i." AND folder = '".$oldFolder."'");
						}

					// and then move selected icon to the end of a new folder
						$newfolderCount = $db->query("SELECT COUNT(*) AS count FROM iconsorder WHERE folder = '".$newFolder."'")->fetch_object()->count;
						$db->query("UPDATE iconsorder SET orderID = ".$newfolderCount.", folder = '".$newFolder."' WHERE ID = '".$ID."'");

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

			$foldersInDB = selectColumnToArray($db, "folders", "name");	// przepisuje do tablicy elementy kolumny "name" z tabeli "folders"
			if((count($newOrder) == count($foldersInDB)) && !array_diff($newOrder,$foldersInDB)){ 		//jeśli tablice maja takie same wartości i długość
				for($i=0; $i<count($newOrder); $i++){
					$db->query("
						UPDATE folders SET name = '".adaptToQuery($db, $newOrder[$i])."'
						WHERE orderID = ".$i
					);
				}
				$response->log = "Folders order saved";
			}
			else{
				$response->error = "Folders sent to server are different than in database";
				$response->responseData = "reload";
			}
		}

	}
	print_r(json_encode($response));





	function adaptToQuery($db, $string){
		return mysqli_real_escape_string($db, $string);
	}

	function deleteWithOrderIdDecrease($db, $tableName, $columnName, $value, $detailColumnName=0, $detailValue=0){
		// finding number of elements matching query
			$selectedCount = $db->query(
				"SELECT COUNT(*) AS count FROM ".$tableName.
				(($detailColumnName) ? " WHERE ".$detailColumnName." = '".$detailValue."'" : "")
			)->fetch_object()->count;

		// finding orderID of element which will be deleted
			$orderID = $db->query("SELECT orderID FROM ".$tableName." WHERE ".$columnName." = '".$value."'")->fetch_object()->orderID;

		// deletion of this element
			$db->query("DELETE FROM ".$tableName." WHERE ".$columnName." = '".$value."'");

		// decreasing every next "orderID" by 1
			for($i = $orderID+1; $i < $selectedCount; $i++){
				$db->query(
					"UPDATE ".$tableName." SET orderID = orderID-1 WHERE orderID = ".$i.
					(($detailColumnName) ? " AND ".$detailColumnName." = '".$detailValue."'" : "")
				);
			}
	}

	function selectColumnToArray($db, $tableName, $columnName, $detailColumnName=0, $detailValue=0){
		$array = array();
		$results = $db->query(
			"SELECT ".$columnName." FROM ".$tableName.
			(($detailColumnName) ? " WHERE ".$detailColumnName." = '".$detailValue."'" : "")
		);
		while($row = $results->fetch_object())
			array_push($array, $row->$columnName);
		return $array;
	}

	function moveFolderContent($db, $oldFolder, $newFolder){
		$oldFolder = adaptToQuery($db, $oldFolder);
		$newFolder = adaptToQuery($db, $newFolder);
		$newFolderCount = adaptToQuery($db, $db->query("SELECT COUNT(*) AS count FROM iconsorder WHERE folder = '".$newFolder."'")->fetch_object()->count);

		$iconsInOldFolder = array();
		$results = $db->query("SELECT ID FROM iconsorder WHERE folder = '".$oldFolder."'");
		while($row = $results->fetch_object()){
			$ID = $row->ID;
			$db->query("UPDATE iconsorder SET orderID = ".$newFolderCount++.", folder = '".$newFolder."' WHERE ID = '".$ID."'");
		}
	}



	/*
		$fname = "ajax_info.txt";
		$file = fopen($fname, 'w+');
		fwrite($file, "testtest");
		fclose($file);
	*/
?>
