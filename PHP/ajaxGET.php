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
			ID bigint PRIMARY KEY,
			URL varchar(500) NOT NULL,
			imageExt varchar(20) NOT NULL
		) DEFAULT CHARSET=utf8;
	");
	$db->query("
		CREATE TABLE IF NOT EXISTS iconsorder (
			orderID int NOT NULL,
			ID bigint NOT NULL,
			folderID bigint NOT NULL,
		) DEFAULT CHARSET=utf8;
	");
	$db->query("
		CREATE TABLE IF NOT EXISTS settings (
			userID bigint NOT NULL,
			iconSize int NOT NULL,
			bgTimestamp bigint NOT NULL
		) DEFAULT CHARSET=utf8;
	");
	$db->query("
		CREATE TABLE IF NOT EXISTS folders (
			userID bigint NOT NULL,
			orderID int NOT NULL,
			folderID bigint NOT NULL,
			name varchar(30) NOT NULL
		) DEFAULT CHARSET=utf8;
	");
	$db->query("
		CREATE TABLE IF NOT EXISTS users (
			userID bigint PRIMARY KEY,
			username varchar(30) NOT NULL,
			password varchar(60) NOT NULL
		) DEFAULT CHARSET=utf8;
	");
	$db->query("
		CREATE TABLE IF NOT EXISTS sessions (
			userID bigint NOT NULL,
			sessionID varchar(60) NOT NULL,
  		expireTime bigint NOT NULL
		) DEFAULT CHARSET=utf8;
	");









	// check correctness of user verification cookies from client side
	if(!isset($_COOKIE["userID"]) || !isset($_COOKIE["sessID"])){
		$response->responseData = "log-in";
		respond();
	}
	$userID = adaptToQuery($_COOKIE["userID"]);
	$sessID = adaptToQuery($_COOKIE["sessID"]);
	if(!mysqli_num_rows($db->query("SELECT userID FROM sessions WHERE userID = ".$userID." AND sessionID = '".$sessID."'"))){
		$response->responseData = "log-in";
		respond();
	}









	if(isset($_REQUEST['getUser'])){
		$expireTime = timestamp()+(86400*30);

		setcookie("userID", $userID, $expireTime, "/"); // extend lifespan of cookie by another 30 days (86400s = 1 day)
		$newSessID = str_shuffle(password_hash($sessID, PASSWORD_BCRYPT));
		// if by some miracle in dastabase exists session with sessionID == newSessionID, generate new newSessionID
		while(@mysqli_num_rows("SELECT sessionID FROM sessions WHERE sessionID = '".$newSessID."'"))
			$newSessID = str_shuffle($newSessID);
		setcookie("sessID", $newSessID, $expireTime, "/"); // set new sessionID cookie for another 30 days

		$resp = array();
			array_push($resp, $userID);
			array_push($resp, $db->query("SELECT username FROM users WHERE userID = ".$userID)->fetch_object()->username);
			array_push($resp, $sessID);
		$response->responseData = $resp;

		// delete unactive sessions older than 30 days
		$db->query("DELETE FROM sessions WHERE expireTime < ".timestamp());
		respond();
	}









	if(isset($_REQUEST['loadSize'])){
		$iconSize = $db->query("SELECT iconSize FROM settings WHERE userID = ".$userID)->fetch_object()->iconSize;
		$response->responseData = $iconSize;
		respond();
	}









	if(isset($_REQUEST['loadBG'])){
		$bgTimestamp = @$db->query("SELECT bgTimestamp FROM settings WHERE userID = ".$userID)->fetch_object()->bgTimestamp;
		if($bgTimestamp == 0)
			$response->responseData = "bg.jpg";
		else
			$response->responseData = "bg/".$userID.".jpg?".$bgTimestamp;
		respond();
	}









	if(isset($_REQUEST['loadFolderContent'])){
		$folder = ($_REQUEST['loadFolderContent'] == "BIN") ? $userID : adaptToQuery($_REQUEST['loadFolderContent']); // BIN ID is the same as userID
		if(!is_numeric($folder))
			$folder = -1;

		$folderName = @$db->query("SELECT name FROM folders WHERE userID = ".$userID." AND folderID = ".$folder)->fetch_object()->name;
		if(!$folderName){
			$response->error = "This folder doesn't exist";
			$response->responseData = "reload";
			respond();
		}

		do{
			$iconsInDB = selectColumnToArray("iconsorder", "ID", "folderID", $folder);		// writes to array elements from column "ID" of "iconsorder" table, for selected value in column "folder"
		}while(count(array_unique($iconsInDB))<count($iconsInDB));	// repeat while there are no duplicates (they apear sometimes for a short time while changing order)

		$results = $db->query("
			SELECT icons.ID, icons.URL, icons.imageExt
			FROM iconsorder
			INNER JOIN icons ON icons.ID = iconsorder.ID
			WHERE folderID = ".$folder."
			ORDER BY iconsorder.orderID ASC
		");

		while($row = $results->fetch_object()){
			array_push($icons->ID, $row->ID);
			array_push($icons->URL, $row->URL);
			array_push($icons->image, ($row->imageExt) ? $row->ID.$row->imageExt : '');
		}
		$icons->count = mysqli_num_rows($results);

		$response->responseData = $icons;
		$response->log = "Loaded content of folder: '".$folderName."'";
		if($folder == $userID) // BIN ID is the same as userID
			$response->log = "Loaded content of bin";
		respond();
	}









	if(isset($_REQUEST['loadFolders'])){
		$foldersList = array();
		$results = $db->query("SELECT `folderID`, `name` FROM folders WHERE userID = ".$userID." ORDER BY orderID ASC");

		$results->fetch_object(); // ignore first folder (BIN)
		while($row = $results->fetch_object()){
			array_push($foldersList, $row->folderID);
			array_push($foldersList, $row->name);
		}

		$response->log = "Loaded list of folders";
		$response->responseData = $foldersList;
		respond();
	}









	if(isset($_REQUEST['loadImage'])){
		$ID = adaptToQuery($_REQUEST['loadImage']);
		$imageExt = $db->query("SELECT imageExt FROM icons WHERE ID = ".$ID)->fetch_object()->imageExt;
		$response->responseData = $ID.$imageExt;
		respond();
	}









	function adaptToQuery($string){
		global $db;
		return mysqli_real_escape_string($db, $string);
	}




	function timestamp(){
		return (new DateTime())->format("U");
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



/*
	$fname = "ajax_info.txt";
	$file = fopen($fname, 'w+');
	fwrite($file, "testtest");
	fclose($file);
*/
?>
