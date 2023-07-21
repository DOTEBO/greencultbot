<?php 

function createLog($timestamp, $entity, $source_id, $context, $message) {
	$dbCon = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$createLog = mysqli_query($dbCon, "INSERT INTO log (created_at, entity, source, context, message) VALUES ('$timestamp', '$entity','$source_id','$context','$message')");
	if (!$createLog) {
		error_log("error with create log in DB");
	}
	mysqli_close($dbCon);
}

function checkUser($id) {
	$dbCon = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$checkUser = mysqli_query($dbCon, "SELECT * FROM users WHERE id = $id");
	$userNumRows = mysqli_num_rows($checkUser);
	if ($userNumRows == 1) {
		return $checkUser;
	}
	elseif ($userNumRows == 0) {
		return false;
	}
	else {
		error_log("ERROR! TWIN USER IN DB!");
	}
	mysqli_close($dbCon);
}

function debug($string, $clear = false) {
	$logFileName = __DIR__."/temp/debug.txt";
	if ($clear == false) {
		file_put_contents($logFileName, TIME_NOW." | ".print_r($string, true)."\r\n", FILE_APPEND);
	}
	else {
		file_put_contents($logFileName, " ");
		file_put_contents($logFileName, TIME_NOW." | ".print_r($string, true)."\r\n", FILE_APPEND);
	}
}

function findClub() {
    $dbCon = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $clubs = mysqli_query($dbCon, "SELECT * FROM clubs");

    $buttons = [];
    while ($club = mysqli_fetch_assoc($clubs)) {
        $button = [ 
			['text' => $club['name'], 'callback_data' => "show_club ".$club['id']]
		];
        $buttons[] = $button;
    }

    mysqli_close($dbCon);
    return $buttons;
}

function showClubStatus($club_id, $user_language){
	$dbCon = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$clubInfo = mysqli_fetch_array(mysqli_query($dbCon, "SELECT * FROM clubs WHERE id = '$club_id'"));
	$club_name = $clubInfo['name'];
	$club_desc = $clubInfo['description'];
	$club_status = $clubInfo['status'];
	switch ($club_status) {
		case 'active':
			$club_status = " 🟢";
			break;
		
		default:
			$club_status = " 🔴";
			break;
	}
	$message = "=======================\n       ♦️ ".$club_name." ♦️\n======================="."\n\n❔ ".msg($user_language, "club_status").$club_status."\n\nℹ️ ".msg($user_language, "club_desc").$club_desc."\n\n=======================";
	return $message;
	mysqli_close($dbCon);
}

function showMenu($user_language){
	$keyboard = [
		[msg($user_language, 'menu_find')],
		[msg($user_language, 'menu_favourite')],
	];
	return $keyboard;
}

function showCommentMenu($user_language, $club_id){
	if (checkClubStatus($club_id)) {
		$clubStatusComment = ['text' => msg($user_language, 'menu_comment_closed'), 'callback_data' => "submitCommentClosed ".$club_id];	
	} else {
		$clubStatusComment = ['text' => msg($user_language, 'menu_comment_open'), 'callback_data' => "submitCommentOpen ".$club_id];
	}
	$buttons = 
	[
		$clubStatusComment,
		['text' => msg($user_language, 'menu_comment_discount'), 'callback_data' => "submitCommentDiscount ".$club_id]
	];
	return $buttons;
}

function checkFollow($user_id, $club_id) {
	$dbCon = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$clubInfo = mysqli_fetch_array(mysqli_query($dbCon, "SELECT * FROM clubs c INNER JOIN follows f ON f.followed_club_id = c.id WHERE c.id = '$club_id' AND f.following_user_id = '$user_id'"));
	if ($clubInfo) {
		return true;
	} else return false;
	mysqli_close($dbCon);
}

function findFollowers($club_id) {
	$dbCon = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$followers = mysqli_query($dbCon, "SELECT following_user_id FROM follows WHERE followed_club_id = '$club_id'");
	if ($followers) {
		while ($row = mysqli_fetch_array($followers)) {
			$result[] = $row[0];
		}
		return $result;
	} else return [];
	mysqli_close($dbCon);
}

function getClubName($club_id) {
	$dbCon = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$club_title = mysqli_fetch_array(mysqli_query($dbCon, "SELECT name FROM clubs WHERE id = '$club_id'"));
	if ($club_title) {
		return $club_title[0];
	} else return false;
	mysqli_close($dbCon);
}

function unFollow($user_id, $club_id) {
	$dbCon = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$result = mysqli_query($dbCon, "DELETE FROM follows WHERE followed_club_id = '$club_id' AND following_user_id = '$user_id'");
	if ($result) {
		return true;
	} else return false;
	mysqli_close($dbCon);
}

function checkClubStatus($club_id) {
	$dbCon = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$result = mysqli_fetch_array(mysqli_query($dbCon, "SELECT status FROM clubs WHERE id = '$club_id'"));
	if ($result[0] == 'active') {
		return true;
	} else return false;
	mysqli_close($dbCon);
}

function getLanguage($user_id) {
	$dbCon = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$user_language = mysqli_fetch_array(mysqli_query($dbCon, "SELECT language FROM users WHERE id = '$user_id'"));
	return $user_language[0];
}

?>