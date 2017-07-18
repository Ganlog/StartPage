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
		$response->error = "Wystapił błąd podczas łączenia z bazą. <br>Błąd numer ". $db->connect_errno . ": " . $db->connect_error;
	}
	else{


		if(isset($_REQUEST['addIcon'])){
			$ID = adaptToQuery($db, $_POST["ID"]);
			$folder = adaptToQuery($db, $_POST["folder"]);
			$URL = adaptToQuery($db, $_POST["URL"]);
			
			$folderCount = $db->query("SELECT COUNT(*) AS count FROM iconsorder WHERE folder = '".$folder."'")->fetch_object()->count;

			$db->query("INSERT INTO iconsorder SET orderID = '".$folderCount."', ID = '".$ID."', folder = '".$folder."'");
			$db->query("INSERT INTO icons SET ID = '".$ID."', URL = '".$URL."'");
			
			$response->log = "Dodano ikonę do folderu '".$folder."'";
		}		


		if(isset($_REQUEST['deleteIcon'])){
			$ID = adaptToQuery($db, $_POST["ID"]);
			$folder = $db->query("SELECT folder FROM iconsorder WHERE ID = '".$ID."'")->fetch_object()->folder;
			
			deleteWithOrderIdDecrease($db, "iconsorder", "ID", $ID, "folder", $folder);	//usuń z tabeli "iconsorder" ikonę o podanym "ID" i "folder" oraz przesuń wszystkie kolejne orderID o -1
			
			$image = $db->query("SELECT image FROM icons WHERE ID = '".$ID."'")->fetch_object()->image;
			$db->query("DELETE FROM icons WHERE ID = '".$ID."'");
			$response->log = "Ikona została bezpowrotnie usunięta";
			
			if($image){
				@unlink('../images/icons/'.$image);
				$response->log .= "<br>Usunięto plik ".$image;
			}
		}


		if(isset($_REQUEST['saveOrder'])){		
			$folder = adaptToQuery($db, $_POST["folder"]);
			$newOrder = json_decode($_POST["order"]);
			$iconsInDB = selectColumnToArray($db, "iconsorder", "ID", "folder", $folder);		// przepisuje do tablicy elementy kolumny "ID" z tabeli "iconsorder", dla podanej watrości w kolumnie "folder"
			
			if((count($newOrder) == count($iconsInDB)) && !array_diff($newOrder,$iconsInDB)){ 		//jeśli tablice maja takie same wartości i długość
				for($i=0; $i<count($newOrder); $i++){
					$db->query("
						UPDATE iconsorder SET ID = '".adaptToQuery($db, $newOrder[$i])."'  
						WHERE orderID = ".$i." AND folder = '".$folder."'
					");
				}
				$response->log = "Kolejność ikon w folderze '".$folder."' została zapisana";
			}
			else{
				$response->error = "Przesłane ikony nie zgadzają się z ikonami w bazie";
				$response->responseData = "reload";			
			}
		}		


		if(isset($_REQUEST['saveNewAddress'])){
			$URL = adaptToQuery($db, $_POST["URL"]);
			$ID = adaptToQuery($db, $_POST["ID"]);
			$db->query("UPDATE icons SET URL = '".$URL."' WHERE ID = '".$ID."'");
			$response->log = "Zmieniono adres linku na '".$URL."'";
		}


		if(isset($_REQUEST['saveSize'])){
			$db->query("UPDATE settings SET size = ".adaptToQuery($db, $_POST["size"]));
			$response->log = "Wielkość ikon została zapisana";
		}


		if(isset($_REQUEST['saveBgFILE'])){
			if (empty($_FILES["image"]) || ($_FILES["image"]["error"])){
				$response->error = "Wystąpił błąd podczcas przesyłania obrazka";		
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
						$response->error = "Niepoprawne rozszerzenie pliku";
				}
				if($extension){
					if (move_uploaded_file($image["tmp_name"], $path.$filename.$extension))
						$response->log = "Zapisano tło";
					else	
						$response->error = "Wystąpił błąd podczas zapisywania tła";
				}
			}
		}


		if(isset($_REQUEST['saveImageFILE'])){
			if (empty($_FILES["image"]) || ($_FILES["image"]["error"])){
				$response->error = "Wystąpił błąd podczcas przesyłania obrazka";		
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
						$response->error = "Niepoprawne rozszerzenie pliku";
				}
				if($extension){
					if(file_exists($path.$filename.".jpg"))			unlink($path.$filename.".jpg");
					if(file_exists($path.$filename.".png"))		unlink($path.$filename.".png");
					if(file_exists($path.$filename.".gif"))			unlink($path.$filename.".gif");
					
					if (move_uploaded_file($image["tmp_name"], $path.$filename.$extension)){
						$db->query("UPDATE icons SET image='".$filename.$extension."' WHERE ID='".$filename."'");
						$response->log = "Zapisano plik ".$filename.$extension;
					}
					else
						$response->error = "Wystąpił błąd podczas zapisywania obrazka";
				}
			}
		}		


		if(isset($_REQUEST['saveBgURL'])){
			$URL = $_POST["URL"];			
			$path = "../images/";
			$filename = "bg";
			
			$tmpFile = fopen($path.$filename.".tmp", 'w+');              				// otwiera file handle tymczasowego plik
			$cURL = curl_init($URL);
			curl_setopt($cURL, CURLOPT_FILE, $tmpFile);							// wrzuć do pliku
			curl_setopt($cURL, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($cURL, CURLOPT_TIMEOUT, 1000);							// umozliwa kontakt przez dlugi czas
			curl_setopt($cURL, CURLOPT_USERAGENT, 'Mozilla/5.0');
			curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, FALSE);     	//pozwala na polaczenie z https
			curl_setopt($cURL, CURLOPT_SSL_VERIFYHOST, 2); 				//pozwala na polaczenie z https
			curl_exec($cURL);
			$extension = curl_getinfo($cURL, CURLINFO_CONTENT_TYPE);// tworzony jest plik tmp po to zeby po 'curl_exec($curl);' poznac rozzerzenie
			curl_close($cURL);
			fclose($tmpFile);
			
			switch($extension){
				case 'image/jpeg':
				case 'image/png':
				case 'image/gif':
					$extension = ".jpg";
				break;
				default: 
					$response->info = "Niepoprawne rozszerzenie pliku, lub błąd hosta obrazka. Spróbuj zapisać obrazek i dodać go z pliku";
					$extension = '';
			}
			
			if($extension){
				//if(imagejpeg( imagecreatefromstring(file_get_contents($path.$filename.".tmp"))  ,  $path.$filename.$extension))    // <- zamienia dowolny format na jpg
				if(@copy($path.$filename.".tmp",  $path.$filename.$extension))			// <- pod rozszerzeniem .jpg itak siedza gify i png
					$response->log = "Zapisano tlo";	
				else
					$response->error = "Wystąpił błąd podczas zapisywania tla";  
			}
			@unlink($path.$filename.".tmp");
		}	


		if(isset($_REQUEST['saveImageURL'])){
			$URL = $_POST["URL"];			
			$path = "../images/icons/";
			$filename = adaptToQuery($db, $_POST["ID"]);

			$tmpFile = fopen($path.$filename.".tmp", 'w+');              				// otwiera file handle tymczasowego plik
			$cURL = curl_init($URL);
			curl_setopt($cURL, CURLOPT_FILE, $tmpFile);							// wrzuć do pliku
			curl_setopt($cURL, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($cURL, CURLOPT_TIMEOUT, 1000);							// umozliwa kontakt przez dlugi czas
			curl_setopt($cURL, CURLOPT_USERAGENT, 'Mozilla/5.0');
			curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, FALSE);     	//pozwala na polaczenie z https
			curl_setopt($cURL, CURLOPT_SSL_VERIFYHOST, 2); 				//pozwala na polaczenie z https
			curl_exec($cURL);
			$extension = curl_getinfo($cURL, CURLINFO_CONTENT_TYPE);// tworzony jest plik tmp po to zeby po 'curl_exec($curl);' poznac rozzerzenie
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
					$response->info = "Niepoprawne rozszerzenie pliku, lub błąd hosta obrazka. Spróbuj zapisać obrazek i dodać go z pliku";
					@unlink($path.$filename.".tmp");
					$extension = '';
			}	
			
			if($extension){			
				if(file_exists($path.$filename.".jpg"))			unlink($path.$filename.".jpg");
				if(file_exists($path.$filename.".png"))		unlink($path.$filename.".png");
				if(file_exists($path.$filename.".gif"))			unlink($path.$filename.".gif");			
						
				if(@copy($path.$filename.".tmp",  $path.$filename.$extension)){
					$db->query("UPDATE icons SET image='".$filename.$extension."' WHERE ID='".$filename."'");	
					$response->log = "Zapisano plik ".$filename.$extension;	
				}
				else
					$response->error = "Wystąpił błąd podczas zapisywania tla";  
			}
			@unlink($path.$filename.".tmp"); 
		}



		if(isset($_REQUEST['addFolder'])){	
			$name = adaptToQuery($db, $_POST["name"]);
				
			$foldersInDB = selectColumnToArray($db, "folders", "name");	// przepisuje do tablicy elementy kolumny "name" z tabeli "folders"
			if(in_array($name, $foldersInDB)){
				$response->info = "Podany folder jest juz w bazie";
				$response->responseData = "alreadyExist";
			}
			else{
				$foldersCount = $db->query("SELECT COUNT(*) AS count FROM folders")->fetch_object()->count;
				$db->query("INSERT INTO folders SET orderID = ".$foldersCount.", name = '".$name."'");
				
				$response->log = "Dodano folder '".$name."'";
			}
		}


		if(isset($_REQUEST['deleteFolder'])){
			$name = adaptToQuery($db, $_POST["name"]);
			
			if((strtoupper($name) == "START") || (strtoupper($name) == "BIN"))
				$response->info = "Nie mozna usunąć tego folderu";
			
			else{
				moveFolderContent($db, $name, "BIN");
				deleteWithOrderIdDecrease($db, "folders", "name", $name);	//usuń z tabeli "folders" folder o podanym "name" oraz przesuń wszystkie kolejne orderID o -1
				$response->log = "Usunięto folder '".$name."', a całą jego zawartość przeniesiono do kosza";
			}
		}

		
		if(isset($_REQUEST['renameFolder'])){
			$oldName = adaptToQuery($db, $_POST["oldName"]);
			$newName = adaptToQuery($db, $_POST["newName"]);
			
			if($db->query("SELECT name FROM folders WHERE name = '".$oldName."'")->fetch_object()){ //jeśli istnieje w bazie folder ktoremu chcemu zmienic nawę
				if((strtoupper($oldName) == "START") || (strtoupper($oldName) == "BIN"))
					$response->info = "Nie mozna zmienić nazwy tego folderu";
				
				else if($oldName == $newName)
					$response->log = "xD ?";
				
				else{
					moveFolderContent($db, $oldName, $newName);
					
					$foldersInDB = selectColumnToArray($db, "folders", "name");	// przepisuje do tablicy elementy kolumny "name" z tabeli "folders"		
					if(in_array($newName, $foldersInDB)){
						$response->log = "Zawartość folderu została przeniesiona do istniejącego już folderu '".$newName."'";
						$response->responseData = "alreadyExist";
						deleteWithOrderIdDecrease($db, "folders", "name", $oldName);	//usuń z tabeli "folders" folder o podanym "name" oraz przesuń wszystkie kolejne ID o -1;
					}
					else{
						$db->query("UPDATE folders SET name = '".$newName."' WHERE name = '".$oldName."'");
						$response->log = "Zmieniono nazwe folderu z '".$oldName."' na '".$newName."'";
					}
				}
			}
			else{
				$response->error = "Folder któremu chcesz zmienić nazwe nie istnieje";
				$response->responseData = "reload";
			}
		}

		
		if(isset($_REQUEST['moveIconToFolder'])){
			$ID = adaptToQuery($db, $_POST["ID"]);
			$newFolder = adaptToQuery($db, $_POST["folder"]);
			if(($db->query("SELECT name FROM folders WHERE name = '".$newFolder."'")->fetch_object()) || ($newFolder == "BIN")){ //jeśli istnieje folder docelowy
				$oldFolder = $db->query("SELECT folder FROM iconsorder WHERE ID = '".$ID."'")->fetch_object()->folder;
				
				if(strtoupper($newFolder) == strtoupper($oldFolder)){
					$response->info = "Wybrana ikona jest już w tym folderze";
					$response->responseData = "alreadyInFolder";
				}
				else{
					/* wszystkim następnym ikonom ze starego folderu zmiejsz orderID o 1 */
						$oldfolderCount = $db->query("SELECT COUNT(*) AS count FROM iconsorder WHERE folder = '".$oldFolder."'")->fetch_object()->count;
						$orderID = $db->query("SELECT orderID FROM iconsorder WHERE ID = '".$ID."'")->fetch_object()->orderID;
						for($i = $orderID+1; $i < $oldfolderCount; $i++){
							$db->query("UPDATE iconsorder SET orderID = orderID-1 WHERE orderID = ".$i." AND folder = '".$oldFolder."'");
						}						
					
					/* a potem przenieś wybrana ikone na koniec w nowym folderze */
						$newfolderCount = $db->query("SELECT COUNT(*) AS count FROM iconsorder WHERE folder = '".$newFolder."'")->fetch_object()->count;
						$db->query("UPDATE iconsorder SET orderID = ".$newfolderCount.", folder = '".$newFolder."' WHERE ID = '".$ID."'");		
						
					$response->log = "Ikona została przeniesiona do folderu '".$newFolder."'";
					if($newFolder == "BIN") $response->log = "Ikona została przeniesiona do kosza";
				}
			}
			else{
				$response->error = "Folder do którego chcesz przenieść ikonę nie istnieje";
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
				$response->log = "Kolejność folderów została zapisana";
			}
			else{
				$response->error = "Przesłane foldery nie zgadzają się z folderami w bazie";
				$response->responseData = "reload";
			}
		}		
	
	}
	print_r(json_encode($response));


	
	
	
	function adaptToQuery($db, $string){
		return mysqli_real_escape_string($db, $string);
	}

	function deleteWithOrderIdDecrease($db, $tableName, $columnName, $value, $detailColumnName=0, $detailValue=0){
		/* znalezienie liczby wszystkich elementów pasujących do zapytania */
			$selectedCount = $db->query(
				"SELECT COUNT(*) AS count FROM ".$tableName.
				(($detailColumnName) ? " WHERE ".$detailColumnName." = '".$detailValue."'" : "")
			)->fetch_object()->count;

		/* znalezienie numeru porządkowego elementu który chcemy usunąc */
			$orderID = $db->query("SELECT orderID FROM ".$tableName." WHERE ".$columnName." = '".$value."'")->fetch_object()->orderID;

		/* usunięcie tego elementu */
			$db->query("DELETE FROM ".$tableName." WHERE ".$columnName." = '".$value."'");
		
		/* zmiejszenie orderID wszystkich kolejnych elementów o 1 */
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