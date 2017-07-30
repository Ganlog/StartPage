<?php
	class Response {
		public $log;
		public $info;
		public $error;
		public $responseData;
	}
	$response = new Response();

	class Icons {
		public $count;
		public $ID = array();
		public $URL = array();
		public $image = array();
	}


	include "logDB.php";
	$db = @new mysqli($host, $user, $password, $dbname);
	if ($db->connect_error) {
		$response->error = "Database connection error. <br>";
		$response->error .= "Error number ".$db->connect_errno.": ".mb_convert_encoding($db->connect_error, 'utf-8');
	}
	else{
		$db->query("
			CREATE TABLE IF NOT EXISTS icons (
			  ID bigint(15) NOT NULL,
			  URL varchar(500) NOT NULL,
			  image varchar(20) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");
		$db->query("
			CREATE TABLE IF NOT EXISTS iconsorder (
			  orderID int(10) NOT NULL,
			  ID bigint(15) NOT NULL,
			  folder varchar(100) NOT NULL
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
		");
		$db->query("
			CREATE TABLE IF NOT EXISTS settings (
			  size int(10) NOT NULL,
			  other varchar(100) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");
		$db->query("
			CREATE TABLE IF NOT EXISTS folders (
			  orderID int(10) NOT NULL,
			  name varchar(100) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");





		if(isset($_REQUEST['loadSize'])){
			if($db->query("SELECT size FROM settings")->fetch_object())		// if size is saved in database, get it
				$size = $db->query("SELECT size FROM settings")->fetch_object()->size;
			else{		// otherwise set value and save it to database
				$size = 100;
				$db->query("INSERT INTO settings SET size = 100");
			}
			$response->responseData = $size;
		}

		if(isset($_REQUEST['loadFolderContent'])){
			$folder = adaptToQuery($db, $_REQUEST['loadFolderContent']);
			do{
				$iconsInDB = selectColumnToArray($db, "iconsorder", "ID", "folder", $folder);		// writes to array elements from column "ID" of "iconsorder" table, for selected value in column "folder"
			}while(count(array_unique($iconsInDB))<count($iconsInDB));	// repeat while there are no duplicates (they apear sometimes for a short time while changing order)

			$results = $db->query("
				SELECT icons.ID, icons.URL, icons.image
				FROM iconsorder
				INNER JOIN icons ON icons.ID = iconsorder.ID
				WHERE folder = '".$folder."'
				ORDER BY iconsorder.orderID ASC
			");

			$icons = new Icons();
			while($row = $results->fetch_object()){
				array_push($icons->ID, $row->ID);
				array_push($icons->URL, $row->URL);
				array_push($icons->image, $row->image);
			}
			$icons->count = mysqli_num_rows($results);

			$response->responseData = $icons;
			$response->log = "Loaded content of folder: '".$folder."'";
			if($folder == "BIN")
				$response->log = "Loaded content of bin";
		}


		if(isset($_REQUEST['loadFolders'])){
			$foldersList = array();
			$results = $db->query("SELECT name FROM folders ORDER BY orderID ASC");
			$foldersCount = mysqli_num_rows($results);

			if($foldersCount){	// if some folders exist, write their names to array 'foldersList'
				while($row = $results->fetch_object())
					array_push($foldersList, $row->name);
			}
			else{	// if no folder exists, add folder "Start"
				$db->query("INSERT INTO folders SET orderID = 0, name = 'Start'");
				array_push($foldersList, 'start');
			}

			$response->log = "Loaded list of folders";
			$response->responseData = $foldersList;
		}


		if(isset($_REQUEST['loadImage'])){
			$ID = adaptToQuery($db, $_REQUEST['loadImage']);
			$image = $db->query("SELECT image FROM icons WHERE ID = '".$ID."'")->fetch_object()->image;
			$response->responseData = $image;
		}
	}

	print_r(json_encode($response));

	function adaptToQuery($db, $string)
	{
	    return mysqli_real_escape_string($db, $string);
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

	/*
		$fname = "ajax_info.txt";
		$file = fopen($fname, 'w+');
		fwrite($file, "testtest");
		fclose($file);
	*/
?>
