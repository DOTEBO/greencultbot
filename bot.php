<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once 'config.php';
require_once 'functions.php';
require_once 'localization.php';

use \TelegramBot\Api\Client;

try {
// Initialize the Telegram Bot API client
$bot_token = TG_TOKEN;
$bot = new Client($bot_token);

    //Handle /ping command
    $bot->command('ping', function ($message) use ($bot) {
        $bot->sendMessage($message->getChat()->getId(), 'pong!');
        $timestamp = $message->getDate();
        $timestamp = date("Y-m-d H:i:s",$timestamp);
        createLog($timestamp, 'user', $message->getChat()->getId(), 'command', '/ping');
    });

    //Handle /help command
    $bot->command('help', function ($message) use ($bot) {
        $timestamp = $message->getDate();
        $timestamp = date("Y-m-d H:i:s",$timestamp);
        createLog($timestamp, 'user', $message->getChat()->getId(), 'command', '/help');
        $user_id = $message->getChat()->getId();
        $user_language = getLanguage($user_id);
        $bot->deleteMessage($user_id, $message->getMessageId()-1);
        $bot->deleteMessage($user_id, $message->getMessageId());
        $checked_user = checkUser($user_id);
        if ($checked_user!=false) {
            $checked_user = mysqli_fetch_assoc($checked_user);
            if ($checked_user['status']=="active") {
                $replyMarkup = new TelegramBot\Api\Types\ReplyKeyboardMarkup(showMenu($user_language), true, true);
                $bot->sendMessage($user_id, "https://goo.su/PAhh6nl", null, false, null, null, $replyMarkup);
            }
            else if ($checked_user['status']=="closed"){
                $bot->sendMessage($user_id, msg($user_language, "closed_user"));
            }
            else if ($checked_user['status']=="banned"){
                $bot->sendMessage($user_id, msg($user_language, "bannde_user"));
            }
            else {
                $bot->sendMessage($user_id, "unknown status");
            }
        }
    });

    //Handle /menu command
    $bot->command('menu', function ($message) use ($bot) {
        $timestamp = $message->getDate();
        $timestamp = date("Y-m-d H:i:s",$timestamp);
        createLog($timestamp, 'user', $message->getChat()->getId(), 'command', '/menu');
        $user_id = $message->getChat()->getId();
        $user_language = getLanguage($user_id);
        $bot->deleteMessage($user_id, $message->getMessageId()-1);
        $bot->deleteMessage($user_id, $message->getMessageId());
        $checked_user = checkUser($user_id);
        if ($checked_user!=false) {
            $checked_user = mysqli_fetch_assoc($checked_user);
            if ($checked_user['status']=="active") {
                $replyMarkup = new TelegramBot\Api\Types\ReplyKeyboardMarkup(showMenu($user_language), true, true);
                $bot->sendMessage($user_id, msg($user_language, "menu_hello_msg"), null, false, null, null, $replyMarkup);
            }
            else if ($checked_user['status']=="closed"){
                $bot->sendMessage($user_id, msg($user_language, "closed_user"));
            }
            else if ($checked_user['status']=="banned"){
                $bot->sendMessage($user_id, msg($user_language, "bannde_user"));
            }
            else {
                $bot->sendMessage($user_id, "unknown status");
            }
        }

    });

    //Handle /start command
    $bot->command('start', function ($message) use ($bot) {
        $dbCon = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $user_language = $message->getFrom()->getLanguageCode();
        $chat_id = $message->getChat()->getId();
        $username = $message->getFrom()->getUsername();
        $timestamp = $message->getDate();
        $timestamp = date("Y-m-d H:i:s",$timestamp);
        $checked_user = checkUser($chat_id);
        createLog($timestamp, 'user', $chat_id, 'command', '/start');
        if ($checked_user!=false) {
            $checked_user = mysqli_fetch_assoc($checked_user);
            $bot->deleteMessage($chat_id, $message->getMessageId());
            $clubs = findClub();
            $clubs[] = [
                ['text' => msg($user_language, "cancel"), 'callback_data' => "cancel"]
            ];
            if ($checked_user   ['status']=="active") {
                $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(
                    $clubs
                );
                $bot->sendMessage($chat_id, msg($user_language, "find_club"), null, false, null, null, $keyboard);
                }
            else if ($checked_user['status']=="closed"){
                $bot->sendMessage($chat_id, msg($user_language, "welcome"));
                $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([
                    [
                        ['text' => msg($user_language, "yes_21"), 'callback_data' => 'yes_21'],
                        ['text' => msg($user_language, "no_21"), 'callback_data' => 'no_21']
                    ]
                ]);
                $bot->sendMessage($chat_id, msg($user_language, "confirm_21"), null, false, null, null, $keyboard);
            }
            else if ($checked_user['status']=="banned"){
                $bot->sendMessage($chat_id, msg($user_language, "bannde_user"));
            }
            else {
                $bot->sendMessage($chat_id, "unknown status");
            }
        }
        else {
            $bot->deleteMessage($message->getChat()->getId(), $message->getMessageId());
            $createUser = mysqli_query($dbCon, "INSERT INTO users VALUES ('$chat_id', '$username', 'active', '$user_language', '$timestamp', 'user','$timestamp')");
            if (!$createUser) {
                error_log('Error with user creating in DB' . $chat_id);
            }
            $bot->sendMessage($message->getChat()->getId(), msg($user_language, "welcome"));
            $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([
                [
                    ['text' => msg($user_language, "yes_21"), 'callback_data' => 'yes_21'],
                    ['text' => msg($user_language, "no_21"), 'callback_data' => 'no_21']
                ]
            ]);
            $bot->sendMessage($message->getChat()->getId(), msg($user_language, "confirm_21"), null, false, null, null, $keyboard);         
        }
        mysqli_close($dbCon);
    });
    
    //Handle replies to inline buttons
    $bot->callbackQuery(function ($message) use ($bot) {
        $dbCon = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($message->getData() !== null) {
            $data = $message->getData();
            $chat_id = $message->getMessage()->getChat()->getId();
            $user_language = getLanguage($chat_id);
            $timestamp = $message->getMessage()->getDate();
            $timestamp = date("Y-m-d H:i:s",$timestamp);
            $checked_user = checkUser($chat_id);
            $checked_user = mysqli_fetch_assoc($checked_user);
            createLog($timestamp, 'user', $chat_id, 'replyMarkupInlineButton', $data);
            if ($data !== null) {
                if ($data == "no_21") {
                    $bot->deleteMessage($chat_id, $message->getMessage()->getMessageId()-1);
                    $bot->deleteMessage($chat_id, $message->getMessage()->getMessageId());
                    $updateUser = mysqli_query($dbCon, "UPDATE users SET status = 'closed' WHERE id = '$chat_id'");
                    if (!$updateUser) {
                        error_log('Error with user updating in DB' . $chat_id);
                    }
                    $bot->sendMessage($chat_id, msg($user_language, "no_msg_21"));
                }
                else if ($data == "yes_21") {
                    $bot->deleteMessage($chat_id, $message->getMessage()->getMessageId()-1);
                    $bot->deleteMessage($chat_id, $message->getMessage()->getMessageId());
                    if ($checked_user['status']=="closed"){
                        $updateQuery = mysqli_query($dbCon, "UPDATE users SET status = 'active' WHERE id = '$chat_id'");
                        $replyMarkup = new TelegramBot\Api\Types\ReplyKeyboardMarkup(showMenu($user_language), true, true);
                        $bot->sendMessage($chat_id, msg($user_language, "reopen_account_msg"), null, false, null, null, $replyMarkup);
                    }
                    else if ($checked_user['status']=="banned"){
                        $bot->sendMessage($chat_id, msg($user_language, "bannde_user"));
                    }
                    else {
                        $bot->sendMessage($chat_id,  msg($user_language, "yes_msg_21"));
                        $clubs = findClub();
                        $clubs[] = [
                            ['text' => msg($user_language, "cancel"), 'callback_data' => "cancel"]
                        ];
                        $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($clubs);
                        $bot->sendMessage($chat_id, msg($user_language, "find_club"), null, false, null, null, $keyboard);
                    }
                }
                else if (str_contains($data, "show_club")){
                    if ($checked_user!=false) {
                        $bot->deleteMessage($chat_id, $message->getMessage()->getMessageId());
                        if ($checked_user['status']=="active") {
                            $club_id_str = explode(" ", $data);
                            $club_id = $club_id_str[1];
                            if(checkFollow($chat_id,$club_id)){
                                $keyboardPrepare = [
                                    ['text' => msg($user_language, "add_comment"), 'callback_data' => "addComment ".$club_id],
                                    ['text' => msg($user_language, "remove_favourite"), 'callback_data' => "unFollow ".$club_id]
                                ];
                            } 
                            else if (!checkFollow($chat_id,$club_id)) {
                                $keyboardPrepare[] = ['text' => msg($user_language, "add_to_favourite"), 'callback_data' => "follow ".$club_id];    
                            }
                            $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([
                                $keyboardPrepare,
                                [
                                    ['text' => msg($user_language, "cancel"), 'callback_data' => "cancel"]
                                ]
                            ]);
                            $bot->sendMessage($chat_id, showClubStatus($club_id, $user_language), null, false, null, null, $keyboard);
                        }
                        else if ($checked_user['status']=="closed"){
                            $bot->sendMessage($chat_id, msg($user_language, "closed_user"));
                        }
                        else if ($checked_user['status']=="banned"){
                            $bot->sendMessage($chat_id, msg($user_language, "bannde_user"));
                        }
                        else {
                            $bot->sendMessage($chat_id, "unknown status");
                        }
                    }
                }
                else if ($data == "cancel") {
                    $bot->deleteMessage($chat_id, $message->getMessage()->getMessageId());
                    if ($checked_user['status']=="active") {
                        $replyMarkup = new TelegramBot\Api\Types\ReplyKeyboardMarkup(showMenu($user_language), true, true);
                        $bot->sendMessage($chat_id, msg($user_language, "menu_hello_msg"), null, false, null, null, $replyMarkup);
                    }
                    else if ($checked_user['status']=="closed"){
                        $bot->sendMessage($chat_id, msg($user_language, "closed_user"));
                    }
                    else if ($checked_user['status']=="banned"){
                        $bot->sendMessage($chat_id, msg($user_language, "bannde_user"));
                    }
                    else {
                        $bot->sendMessage($chat_id, "unknown status");
                    }
                }
                else if (str_contains($data, "follow")){
                    if ($checked_user['status']=="active") {
                        $bot->deleteMessage($chat_id, $message->getMessage()->getMessageId());
                        $club_id_str = explode(" ", $data);
                        $club_id = $club_id_str[1];
                        $checkFollow = mysqli_query($dbCon, "SELECT * FROM follows WHERE following_user_id = '$chat_id' AND followed_club_id = '$club_id'");
                        $checkNumRow = mysqli_num_rows($checkFollow);
                        $followMsg = "follow_msg";
                        if ($checkNumRow > 0) {
                            $followMsg = "already_following_msg";
                        }
                        else {
                            $follow = mysqli_query($dbCon, "INSERT INTO follows VALUES ('$chat_id', '$club_id', '$timestamp')");
                        }
                        $replyMarkup = new TelegramBot\Api\Types\ReplyKeyboardMarkup(showMenu($user_language), true, true);
                        $bot->sendMessage($chat_id, msg($user_language, $followMsg), null, false, null, null, $replyMarkup);
                    }
                    else if ($checked_user['status']=="closed"){
                        $bot->sendMessage($chat_id, msg($user_language, "closed_user"));
                    }
                    else if ($checked_user['status']=="banned"){
                        $bot->sendMessage($chat_id, msg($user_language, "bannde_user"));
                    }
                    else {
                        $bot->sendMessage($chat_id, "unknown status");
                    }
                }
                else if (str_contains($data, "unFollow")) {
                    $club_id_str = explode(" ", $data);
                    $club_id = $club_id_str[1];
                    $bot->deleteMessage($chat_id, $message->getMessage()->getMessageId());
                    $replyMarkup = new TelegramBot\Api\Types\ReplyKeyboardMarkup(showMenu($user_language), true, true);
                    if(unFollow($chat_id, $club_id)) {
                        $bot->sendMessage($chat_id, msg($user_language, "unfollow_msg"), null, false, null, null, $replyMarkup);
                    } else $bot->sendMessage($chat_id, msg($user_language, "wip"), null, false, null, null, $replyMarkup);
                }
                else if (str_contains($data, "addComment")) {
                    $club_id_str = explode(" ", $data);
                    $club_id = $club_id_str[1];
                    $bot->deleteMessage($chat_id, $message->getMessage()->getMessageId());
                    $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([
                        showCommentMenu($user_language, $club_id),
                        [
                            ['text' => msg($user_language, "cancel"), 'callback_data' => "cancel"]
                        ]
                    ]);
                    $bot->sendMessage($chat_id, msg($user_language, "menu_comment_option"), null, false, null, null, $keyboard);
                }
                else if (str_contains($data, "submitComment")) {
                    $club_id_str = explode(" ", $data);
                    $club_id = $club_id_str[1];
                    $title = $club_id_str[0];
                    $comment = mysqli_query($dbCon, "INSERT INTO comments (title, user_id, club_id, created_at) VALUES ('$title', '$chat_id', '$club_id', '$timestamp')");
                    if (!$comment) {
                        error_log('Error with comment creating in DB' . $chat_id);
                    }
                    $bot->deleteMessage($chat_id, $message->getMessage()->getMessageId());
                    $club_title = getClubName($club_id);
                    $followers = findFollowers($club_id);
                    $replyMarkup = new TelegramBot\Api\Types\ReplyKeyboardMarkup(showMenu($user_language), true, true);
                    switch(true) {
                        case str_contains($data,'submitCommentClosed'): 
                            foreach ($followers as $follower) {
                                if ($follower != $chat_id) {
                                    $bot->sendMessage($follower, 'iii ' . msg($user_language, "menu_comment_closed") . ' - ' . $club_title . ' !!!');
                                }
                            }
                            $bot->sendMessage($chat_id, msg($user_language, "comment_closed"), null, false, null, null, $replyMarkup);
                            break;
                        case str_contains($data,'submitCommentDiscount'):
                            foreach ($followers as $follower) {
                                if ($follower != $chat_id) {
                                    $bot->sendMessage($follower, 'iii ' . msg($user_language, "menu_comment_discount") . ' - ' . $club_title . ' !!!');
                                }
                            }
                            $bot->sendMessage($chat_id, msg($user_language, "comment_discount"), null, false, null, null, $replyMarkup);
                            break;
                        case str_contains($data,'submitCommentOpen'): 
                            foreach ($followers as $follower) {
                                if ($follower != $chat_id) {
                                    $bot->sendMessage($follower, 'iii ' . msg($user_language, "menu_comment_open") . ' - ' . $club_title . ' !!!');
                                }
                            }
                            $bot->sendMessage($chat_id, msg($user_language, "comment_open"), null, false, null, null, $replyMarkup);
                            break;
                    }
                }
                else {
                    $bot->sendMessage($chat_id, "Вам штрафная, сударь!");
                }
            }
        }
        mysqli_close($dbCon);
    });

    //Handle text messages
    $bot->on(function (\TelegramBot\Api\Types\Update $update) use ($bot) {
        $message = $update->getMessage();
        $id = $message->getChat()->getId();
        $message_id = $message->getMessageId();
        $text = $message->getText();
        $timestamp = $message->getDate();
        $timestamp = date("Y-m-d H:i:s",$timestamp);
        $dbCon = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $checked_user = checkUser($id);
        $checked_user = mysqli_fetch_assoc($checked_user);
        createLog($timestamp, 'user', $id, 'message', $text);
        $user_language = getLanguage($id);
        if (str_contains($text,msg($user_language, "menu_find"))) {
            $bot->deleteMessage($id, $message_id-1);
            $bot->deleteMessage($id, $message_id);
            $clubs = findClub();
            $clubs[] = [
                ['text' => msg($user_language, "cancel"), 'callback_data' => "cancel"]
            ];
            if ($checked_user['status']=="active") {
                $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($clubs);
                $bot->sendMessage($id, msg($user_language, "find_club"), null, false, null, null, $keyboard);
            }
            else if ($checked_user['status']=="closed"){
                $bot->sendMessage($id, msg($user_language, "closed_user"));
            }
            else if ($checked_user['status']=="banned"){
                $bot->sendMessage($id, msg($user_language, "bannde_user"));
            }
            else {
                $bot->sendMessage($id, "unknown status");
            }
        }
        if (str_contains($text,"addclub")) {
            if ($checked_user['role']!="user"){
                $bot->deleteMessage($id, $message_id);
                $clubData = explode(',',$text);
                $clubName = $clubData[1];
                $clubCountry = $clubData[2];
                $clubCity = $clubData[3];
                $clubDesc = $clubData[4];
                $createClub = mysqli_query($dbCon, "INSERT INTO clubs (name, status, country, city, description, created_at) VALUES ('$clubName', 'active', '$clubCountry', '$clubCity', '$clubDesc','$timestamp')");
                if (!$createClub) {
                    error_log('Error with club creating in DB' . $id);
                }
            }
            else {
                $bot->sendMessage($id, msg($user_language, "restricted_acces"));
            }
        }
        if (str_contains($text,"ban")) {
            $checked_user = checkUser($id);
            $checked_user = mysqli_fetch_assoc($checked_user);
            $bot->deleteMessage($id, $message_id);
            if ($checked_user['role']=="admin"){
                $banData = explode(' ',$text);
                $banUsername = $banData[1];
                $checkUser = mysqli_query($dbCon, "SELECT * FROM users WHERE username = '$banUsername'");
                $userNumRows = mysqli_num_rows($checkUser);
                if ($userNumRows == 1) {
                    $banUser = mysqli_query($dbCon, "UPDATE users SET status = 'banned' WHERE username='$banUsername'");
                    if (!$banUser) {
                        error_log('Error with banning ' . $banUsername . ' | From - ' . $id);
                    }
                    $bot->sendMessage($id, "User ".$banUsername.' banned');
                }
                else {
                    $bot->sendMessage($id, "Error");
                }
            }
            else {
                $bot->sendMessage($id, msg($user_language, "restricted_acces"));
            }
        }
        if (str_contains($text,"pardon")) {
            $checked_user = checkUser($id);
            $checked_user = mysqli_fetch_assoc($checked_user);
            $bot->deleteMessage($id, $message_id);
            if ($checked_user['role']=="admin"){
                $unbanData = explode(' ',$text);
                $unbanUsername = $unbanData[1];
                $checkUser = mysqli_query($dbCon, "SELECT * FROM users WHERE username = '$unbanUsername'");
                $userNumRows = mysqli_num_rows($checkUser);
                if ($userNumRows == 1) {
                    $unbanUser = mysqli_query($dbCon, "UPDATE users SET status = 'active' WHERE username='$unbanUsername'");
                    if (!$banUser) {
                        error_log('Error with unbanning ' . $unbanUsername . ' | From - ' . $id);
                    }
                    $bot->sendMessage($id, "User ".$unbanUsername.' unbanned');
                }
                else {
                    $bot->sendMessage($id, "Error");
                }
            }
            else {
                $bot->sendMessage($id, msg($user_language, "restricted_acces"));
            }
        }
        if (str_contains($text,"role")) {
            $checked_user = checkUser($id);
            $checked_user = mysqli_fetch_assoc($checked_user);
            $bot->deleteMessage($id, $message_id);
            if ($checked_user['role']=="admin"){
                $roleData = explode(' ',$text);
                $setRoleUsername = $roleData[1];
                $newRole = $roleData[2];
                $checkUser = mysqli_query($dbCon, "SELECT * FROM users WHERE username = '$setRoleUsername'");
                $userNumRows = mysqli_num_rows($checkUser);
                if ($userNumRows == 1) {
                    $check_user = mysqli_fetch_assoc($checkUser);
                    $setRoleUser = mysqli_query($dbCon, "UPDATE users SET role = '$newRole' WHERE username='$setRoleUsername'");
                    if (!$banUser) {
                        error_log('Error with setting new role for ' . $unbanUsername . ' | From - ' . $id);
                    }
                    $bot->sendMessage($id, "Role for ".$setRoleUsername.' changed');
                    $bot->sendMessage($check_user['id'], "System msg | Your new role - ".$newRole.'. Congratulations!');
                }
                else {
                    $bot->sendMessage($id, "Error");
                }
            }
            else {
                $bot->sendMessage($id, msg($user_language, "restricted_acces"));
            }
        }
        if (str_contains($text,"msg")) {
            $checked_user = checkUser($id);
            $checked_user = mysqli_fetch_assoc($checked_user);
            $bot->deleteMessage($id, $message_id);
            if ($checked_user['role']=="admin"){
                $msgData = explode(' ',$text);
                $msgUsername = $msgData[1];
                $checkUser = mysqli_query($dbCon, "SELECT * FROM users WHERE username = '$msgUsername'");
                $userNumRows = mysqli_num_rows($checkUser);
                if ($userNumRows == 1) { 
                    $check_user = mysqli_fetch_assoc($checkUser);
                    $userToId = $check_user['id'];
                    $msgData2 = explode(': ',$text);
                    $msgText = $msgData2[1];
                    $msgUser = $bot->sendMessage($userToId, $msgText);
                    if (!$msgUser) {
                        error_log('Error with sending message to ' . $msgUsername . ' | From - ' . $id);
                    }
                    $bot->sendMessage($id, "Message to ".$msgUsername.' send');
                }
                else {
                    $bot->sendMessage($id, "Error");
                }
            }
            else {
                $bot->sendMessage($id, msg($user_language, "restricted_acces"));
            }
        }
        if (str_contains($text, msg($user_language, "menu_favourite"))) {
            if ($checked_user['status']=="active") {
                $bot->deleteMessage($id, $message_id-1);
                $bot->deleteMessage($id, $message_id);
                $checkFollow = mysqli_query($dbCon, "SELECT followed_club_id FROM follows WHERE following_user_id = '$id'");
                $checkNumRow = mysqli_num_rows($checkFollow);
                $showFollowsMsg = "show_follows_msg";
                $buttons = [];
                if ($checkNumRow > 0) {
                    while ($club = mysqli_fetch_assoc($checkFollow)) {
                        $club_info = mysqli_query($dbCon, "SELECT * FROM clubs WHERE id = ".$club['followed_club_id']);
                        $club_info = mysqli_fetch_array($club_info);
                        $buttons[] = ['text' => $club_info['name'], 'callback_data' => "show_club ".$club_info['id']];
                    }
                }
                else{
                    $showFollowsMsg = "no_follows_msg";
                }
                $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([
                    $buttons,
                    [
                        ['text' => msg($user_language, "cancel"), 'callback_data' => "cancel"]
                    ]
                ]);
                $bot->sendMessage($id, msg($user_language, $showFollowsMsg), null, false, null, null, $keyboard);
            }
            else if ($checked_user['status']=="closed"){
                $bot->sendMessage($id, msg($user_language, "closed_user"));
            }
            else if ($checked_user['status']=="banned"){
                $bot->sendMessage($id, msg($user_language, "bannde_user"));
            }
            else {
                $bot->sendMessage($id, "unknown status");
            }
        }
        mysqli_close($dbCon);
    }, function () {
        return true;
    });
    
    $bot->run();

} catch (\TelegramBot\Api\Exception $e) {
    $e->getMessage();
}