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
	$icons = new Icons();

	function respond(){
		global $response;
		print_r(json_encode($response));
		exit;
	}









	// conecting to database
	include 'logDB.php';
	$db = @new mysqli($db_host, $db_user, $db_pass, $db_name);
	if ($db->connect_error) {
		$response->error = "Database connection error. <br>";
		$response->error .= "Error number ".$db->connect_errno.": ".mb_convert_encoding($db->connect_error, 'utf-8');
		respond();
	}









	//creating nessesary tables if they doesn't exist
	$db->query("
		CREATE TABLE IF NOT EXISTS icons (
			ID bigint(15) PRIMARY KEY,
			URL varchar(500) NOT NULL,
			image varchar(20) NOT NULL
		) DEFAULT CHARSET=utf8;
	");
	$db->query("
		CREATE TABLE IF NOT EXISTS iconsorder (
			username varchar(30) NOT NULL,
			orderID int(10) NOT NULL,
			ID bigint(15) NOT NULL,
			folder varchar(30) NOT NULL
		) DEFAULT CHARSET=utf8;
	");
	$db->query("
		CREATE TABLE IF NOT EXISTS settings (
			username varchar(30) NOT NULL,
			iconSize int(10) NOT NULL,
			background bigint(20) NOT NULL
		) DEFAULT CHARSET=utf8;
	");
	$db->query("
		CREATE TABLE IF NOT EXISTS folders (
			username varchar(30) NOT NULL,
			orderID int(10) NOT NULL,
			name varchar(30) NOT NULL
		) DEFAULT CHARSET=utf8;
	");
	$db->query("
		CREATE TABLE IF NOT EXISTS users (
			username varchar(30) PRIMARY KEY,
			password varchar(60) NOT NULL
		) DEFAULT CHARSET=utf8;
	");
	$db->query("
		CREATE TABLE IF NOT EXISTS sessions (
			username varchar(30) NOT NULL,
			sessionID varchar(60) NOT NULL,
  		expireTime bigint(20) NOT NULL
		) DEFAULT CHARSET=utf8;
	");









	// check correctness of user verification cookies from client side
	if(!isset($_COOKIE["user"]) || !isset($_COOKIE["sessID"])){
		$response->responseData = "log-in";
		respond();
	}
	$user = adaptToQuery($_COOKIE["user"]);
	$sessID = adaptToQuery($_COOKIE["sessID"]);
	$sessVerify = mysqli_num_rows($db->query("SELECT username FROM sessions WHERE username = '".$user."' AND sessionID = '".$sessID."'"));

	if(!$sessVerify){
		$response->responseData = "log-in";
		respond();
	}









	if(isset($_REQUEST['getUser'])){
		$expireTime = time()+(86400*30);
		setcookie("user", $user, $expireTime, "/"); // extend lifespan of cookie by another 30 days (86400s = 1 day)
		$newSessID = str_shuffle(password_hash($sessID, PASSWORD_BCRYPT));
		$db->query("UPDATE sessions SET sessionID = '".$newSessID."', expireTime = '".$expireTime."' WHERE sessionID = '".$sessID."'");
		setcookie("sessID", $newSessID, $expireTime, "/"); // set new sessionID cookie for another 30 days

		// delete unactive sessions older than 30 days
		$db->query("DELETE FROM sessions WHERE expireTime < '".time()."'");

		$response->responseData = $user;
		respond();
	}









	if(isset($_REQUEST['loadSize'])){
		$iconSize = @$db->query("SELECT iconSize FROM settings WHERE username = '".$user."'")->fetch_object()->iconSize; // if iconSize is saved in database, get it
		if($iconSize == 0){		// otherwise set value and save it to database
			$iconSize = 100;
			$db->query("INSERT INTO settings SET username = '".$user."', iconSize = ".$iconSize);
		}
		$response->responseData = $iconSize;
		respond();
	}









	if(isset($_REQUEST['loadBG'])){
		$BG = @$db->query("SELECT background FROM settings WHERE username = '".$user."'")->fetch_object()->background;
		if($BG == 0)
			$response->responseData = "bg.jpg";
		else
			$response->responseData = "bg/".$BG.".jpg";
		respond();
	}









	if(isset($_REQUEST['loadFolderContent'])){
		$folder = adaptToQuery($_REQUEST['loadFolderContent']);
		do{
			$iconsInDB = selectColumnToArray("iconsorder", "ID", "folder", $folder);		// writes to array elements from column "ID" of "iconsorder" table, for selected value in column "folder"
		}while(count(array_unique($iconsInDB))<count($iconsInDB));	// repeat while there are no duplicates (they apear sometimes for a short time while changing order)

		$results = $db->query("
			SELECT icons.ID, icons.URL, icons.image
			FROM iconsorder
			INNER JOIN icons ON icons.ID = iconsorder.ID
			WHERE username = '".$user."' AND folder = '".$folder."'
			ORDER BY iconsorder.orderID ASC
		");

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
		respond();
	}









	if(isset($_REQUEST['loadFolders'])){
		$foldersList = array();
		$results = $db->query("SELECT name FROM folders WHERE username = '".$user."' ORDER BY orderID ASC");
		$foldersCount = mysqli_num_rows($results);

		if($foldersCount){	// if some folders exist, write their names to array 'foldersList'
			while($row = $results->fetch_object())
				array_push($foldersList, $row->name);
		}
		else{	// if no folder exists, add folder "Start"
			$db->query("INSERT INTO folders SET username = '".$user."', orderID = 0, name = 'Start'");
			array_push($foldersList, 'start');
		}

		$response->log = "Loaded list of folders";
		$response->responseData = $foldersList;
		respond();
	}









	if(isset($_REQUEST['loadImage'])){
		$ID = adaptToQuery($_REQUEST['loadImage']);
		$image = $db->query("SELECT image FROM icons WHERE ID = '".$ID."'")->fetch_object()->image;
		$response->responseData = $image;
		respond();
	}









	function adaptToQuery($string){
		global $db;
		return mysqli_real_escape_string($db, $string);
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



/*
	$fname = "ajax_info.txt";
	$file = fopen($fname, 'w+');
	fwrite($file, "testtest");
	fclose($file);
*/
?>
