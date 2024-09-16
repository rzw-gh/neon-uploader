<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/config/config.php");

$interval = [
    1 => "اول",
    2 => "دوم",
    3 => "سوم",
    4 => "چهارم",
    5 => "پنجم",
    6 => "ششم",
    7 => "هفتم",
    8 => "هشتم",
    9 => "نهم",
    10 => "دهم",
    11 => "یازدهم",
    12 => "دوازدهم",
    13 => "سیزدهم",
    14 => "چهاردهم",
    15 => "پانزدهم",
    16 => "شانزدهم",
    17 => "هفدهم",
    18 => "هجدهم",
    19 => "نوزدهم",
    20 => "بیستم",
    21 => "بیست و یکم",
    22 => "بیست و دوم",
    23 => "بیست و سوم",
    24 => "بیست و چهارم",
    25 => "بیست و پنجم",
    26 => "بیست و ششم",
    27 => "بیست و هفتم",
    28 => "بیست و هشتم",
    29 => "بیست و نهم",
    30 => "سی ام",
    31 => "سی و یکم",
    32 => "سی و دوم",
    33 => "سی و سوم",
    34 => "سی و چهارم",
    35 => "سی و پنجم",
    36 => "سی و ششم",
    37 => "سی و هفتم",
    38 => "سی و هشتم",
    39 => "سی و نهم",
    40 => "چهلم",
];

if (is_super_admin($from_id)) {
    $home_buttons = [
        [['text' => "🚀 تبلیغات", 'callback_data' => 'menu_ad'], ['text' => "🍿 محتوا", 'callback_data' => 'menu_content']],
        [['text' => "🙌 دستیار", 'callback_data' => 'menu_assistant'], ['text' => "🗞️ اخبار", 'callback_data' => 'menu_news']],
        [['text' => "⚙️ تنظیمات", 'callback_data' => 'menu_setting'], ['text' => "📊 آمار", 'callback_data' => 'menu_statistics']],
    ];
} elseif (is_admin($from_id)) {
    $home_buttons = [
        [['text' => "🙌 دستیار", 'callback_data' => 'menu_assistant'], ['text' => "🗞️ اخبار", 'callback_data' => 'menu_news']],
        [['text' => "♥️ علاقه مندی من", 'callback_data' => 'user_favorite_content'], ['text' => "🔎 جستجو", 'callback_data' => 'content_search']]
    ];
} else {
    $home_buttons = [
        [['text' => "♥️ علاقه مندی من", 'callback_data' => 'user_favorite_content'], ['text' => "🔎 جستجو", 'callback_data' => 'content_search']],
        [['text' => "🗞️ اخبار", 'callback_data' => 'menu_news']]
    ];
}

[$user, $show_sponser, $show_ad, $bot_msg_id, $step] = getUser();
validate();

if ($data == 'home') {
    [$title, $link, $username] = get_chat_info($chat_id, 'user');
    $greet_msg = "سلام 👀 $title خوش اومدی";
    if (is_super_admin($chat_id)) {
        $greet_msg = "سلام رِئیس 👑 خوش اومدی";
    }
    $bot_msg_id = editMsg(
        $greet_msg,
        ['inline_keyboard' => $home_buttons]
    );
} elseif ($data == 'home_new') {
    [$title, $link, $username] = get_chat_info($chat_id, 'user');
    $greet_msg = "سلام 👀 $title خوش اومدی";
    if (is_super_admin($chat_id)) {
        $greet_msg = "سلام رِئیس 👑 خوش اومدی";
    }
    deleteMessage();
    $bot_msg_id = sendMessage(
        $chat_id,
        $greet_msg,
        ['inline_keyboard' => $home_buttons]
    );
}

function bot($method, $params = [], $update_user = true)
{
    global $bot_token, $bot_msg_id;
    $url = "https://api.telegram.org/bot" . $bot_token . "/" . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    $response = curl_exec($ch);
    if ($response) {
        $responseData = json_decode($response, true);
        if ($responseData && $responseData['ok']) {
            if ($update_user) {
                $bot_msg_id = $responseData['result']['message_id'];
                update_user($bot_msg_id);
            }
            return $responseData['result']['message_id'];
        } else {
            if (preg_match('/message to copy not found/', $responseData['description'], $matches)) {
                return "message_to_copy_not_found";
            } else {
                set_log($responseData['description']);
                return null;
            }
        }
    } else {
        set_log(json_decode($response));
        return null;
    }
}

function sendAction($chat_id, $action) {
    // Type of action to broadcast. Choose one, depending on what the user is about to receive: typing for text messages, upload_photo for photos, record_video or upload_video for videos, record_voice or upload_voice for voice notes, upload_document for general files, choose_sticker for stickers, find_location for location data, record_video_note or upload_video_note for video notes.

    $params = [
        'chat_id' => $chat_id,
        'action' => $action
    ];

    return bot('SendChatAction', $params);
}

function copyContent($post_id, $caption = null, $replyMarkup = null, $update_user = true, $delete_previous = false, $protect_content = false, $to_chat_id = null, $parsmode = null)
{
    global $from_id, $content_channel_id, $message_id;

    if (isset($to_chat_id)) {
        $chat_id = $to_chat_id;
    } else {
        $chat_id = $from_id;
    }

    $params = [
        'chat_id' => $chat_id,
        'from_chat_id' => $content_channel_id,
        'message_id' => $post_id,
        'protect_content' => $protect_content,
    ];

    if (isset($parsmode)) {
        $params["parse_mode"] = $parsmode;
    }

    if (isset($caption)) {
        $params['caption'] = $caption;
    }

    if (!is_null($replyMarkup)) {
        $params['reply_markup'] = json_encode($replyMarkup);
    }

    $msg_id = bot('copyMessage', $params, $update_user);

    if (isset($msg_id) && $delete_previous) {
        deleteMessage($message_id);
    }

    return $msg_id;
}

function sendMessage($chat_id, $text, $replyMarkup = null, $message_id = null, $web_page_preview = true, $update_user = true, $delete_previous = false, $parsmode = null)
{
    $params = [
        'chat_id' => $chat_id,
        'text' => (string)$text,
        'parse_mode' => isset($parsmode) ? $parsmode :"Markdown",
        'disable_web_page_preview' => $web_page_preview,
    ];

    if (!is_null($message_id) && !$delete_previous) {
        $params['reply_to_message_id'] = $message_id;
    }

    if (!is_null($replyMarkup)) {
        $replyMarkup['resize_keyboard'] = true;
        $replyMarkup['one_time_keyboard'] = true;
        $params['reply_markup'] = json_encode($replyMarkup);
    }

    $msg_id = bot('sendMessage', $params, $update_user);

    if (isset($msg_id) && $delete_previous) {
        deleteMessage($message_id);
    }

    return $msg_id;
}

function sendSticker($chat_id, $sticker_id)
{
    $params = [
        'chat_id' => $chat_id,
        'sticker' => $sticker_id,
    ];

    return bot('sendSticker', $params);
}

function editMsg($text = null, $replyMarkup = null, $type = 'text', $parsmode = null) {
    global $bot_msg_id, $chat_id;
    
    $params = [
        'chat_id' => $chat_id,
        'message_id' => $bot_msg_id,
        'parse_mode' => "Markdown",
        'disable_web_page_preview' => true,
    ];

    if (isset($parsmode)) {
        $params["parse_mode"] = $parsmode;
    }

    if (!is_null($text)) {
        $text = (string)$text;
        if ($type == 'text') {
            $params['text'] = $text;
        } elseif ($type == 'caption') {
            $params['caption'] = $text;
        }
    }

    if (!is_null($replyMarkup)) {
        $params['reply_markup'] = json_encode($replyMarkup);
    }

    if ($type == 'text') {
        $type = 'editMessageText';
    } elseif ($type == 'caption') {
        $type = 'editMessageCaption';
    } elseif ($type == 'button') {
        $type = 'editMessageReplyMarkup';
    }

    if (!is_null($text) || !is_null($replyMarkup)) {
        return bot($type, $params);
    }
}

function deleteMessage($messageID = null)
{
    global $chat_id, $message_id;
    if (isset($messageID)) {
        $message_id = $messageID;
    }

    $params = [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
    ];

    return bot('deleteMessage', $params, false);
}

function sendDocument($type, $chatID = null, $documentID, $caption = null, $replyMarkup = null)
{
    global $chat_id;
    if (isset($chatID)) {
        $chat_id = $chatID;
    }

    $params = [
        'chat_id' => $chat_id,
        $type => $documentID,
    ];

    if (isset($caption)) {
        $params['caption'] = $caption;
    }

    if (!is_null($replyMarkup)) {
        $params['reply_markup'] = json_encode($replyMarkup);
    }

    if ($type == "photo") {
        return bot('sendPhoto', $params);
    } elseif ($type == "video") {
        return bot('sendVideo', $params);
    } elseif ($type == "document") {
        return bot('sendDocument', $params);
    }
}

function escapeMarkdownV2($input) {
    foreach (['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'] as $char) {
        $input = str_replace($char, '\\' . $char, $input);
    }
    return $input;
}

function show_alert($text, $exit = false) {
    global $query_id, $bot_token;
    $ch = curl_init("https://api.telegram.org/bot$bot_token/answerCallbackQuery");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['callback_query_id' => $query_id, 'text' => $text, "show_alert" => true]);
    curl_exec($ch);
    curl_close($ch);
    if ($exit) {
        exit;
    }
}

function get_chat_info($from_id, $type) {
    global $bot_token;
    $username = null;
    $title = null;
    $link = null;

    $chatInfo = json_decode(file_get_contents("https://api.telegram.org/bot$bot_token/getChat?chat_id=$from_id"), true);
    if ($chatInfo['ok']) {
        if ($type == 'channel') {
            $title = $chatInfo['result']['title'];
            $username = isset($chatInfo['result']['username']) ? $chatInfo['result']['username'] : null;
            $link = isset($chatInfo['result']['invite_link']) ? $chatInfo['result']['invite_link'] : null;
        } elseif ($type == 'user') {
            $username = isset($chatInfo['result']['username']) ? $chatInfo['result']['username'] : null;
            if ($chatInfo['result']['has_private_forwards']) {
                $link = null;
            } else {
                if (is_null($username)) {
                    $link = "tg://user?id=$from_id";
                } else {
                    $link = "https://t.me/$username";
                }
            }
            if (strlen($chatInfo['result']['first_name']) == 0) {
                $title = "کاربر";
            } else {
                $title = $chatInfo['result']['first_name'];
            }
        } elseif ($type == 'private') {
            $username = isset($chatInfo['result']['username']) ? $chatInfo['result']['username'] : null;
            if ($chatInfo['result']['has_private_forwards']) {
                $link = null;
            } else {
                if (is_null($username)) {
                    $link = "tg://user?id=$from_id";
                } else {
                    $link = "https://t.me/$username";
                }
            }
            $title = $chatInfo['result']['first_name'];
        }
    }

    return [$title, $link, $username];
}

function is_chat_member($user_id, $chat_id, $sponser_id = null) {
    global $bot_token, $db;

    if (is_array($chat_id)) {
        foreach ($chat_id as $sponser) {
            if (!is_chat_member($user_id, $sponser['chat_id'])) {
                return false;
            }
        }
    } else {
        $res = json_decode(file_get_contents("https://api.telegram.org/bot" . $bot_token . "/getChatMember?chat_id=$chat_id&user_id=" . $user_id));

        if (isset($sponser_id) && !$res->ok && $res->description == "Bad Request: chat not found") {
            $db->table('sponser')->update(['active' => '0'])->where([['id', '=', $sponser_id]])->execute();
            return true;
        } else {
            $res = $res->result->status;
            if ($res != 'member' && $res != 'creator' && $res != 'administrator' && !is_admin($user_id)) {
                return false;
            }
        }
    }
    return true;
}

function requestContact($text)
{
    global $chat_id;
    $replyMarkup = [
        'keyboard' => [
            [
                ['text' => 'Share your contact', 'request_contact' => true]
            ]
        ]
    ];

    sendMessage($chat_id, $text, $replyMarkup);
}

function maintance()
{
    global $maintance, $chat_id, $from_id, $admin_users_tids;
    if ($maintance && !is_super_admin($from_id)) {
        bot('sendmessage', [
            'chat_id' => $chat_id,
            'text' => "به زودی با قابلیت های بیشتر برمیگردیم... ♥️"
        ]);
        exit();
    }
}

function getUser()
{
    global $db, $chat_id, $input_type, $from_id, $chat_type, $text, $data, $from_username, $full_name;
    $now = date("Y-m-d H:i:s");

    if ($chat_type == 'private' || is_null($chat_type)) {
        if (count($db->table("user")->select()->where([["tid", "=", $from_id]])->execute()) == 0) {
            if ($input_type == 'message') {
                $haystack = $text;
            } else {
                $haystack = $data;
            }
            [$current, $previous] = extract_actions($haystack);
            if (isset($current)) {
                $haystack = $previous;
            }

            if (preg_match('/referrer_(\d+)?/', $haystack, $matches)) {
                $active_sponsers = $db->raw("SELECT * FROM sponser WHERE active = '1'")->execute();
                if (is_chat_member($from_id, $active_sponsers)) {
                    $referrer = $matches[1];
                    $user = $db->table("user")->insert([
                        "tid" => $from_id,
                        "username" => $from_username,
                        "name" => $full_name,
                        "referrer_tid" => $referrer,
                        "joined_at" => $now
                    ])->execute();
                    referral_notif($referrer);
                }
            } else {
                $user = $db->table("user")->insert([
                    "tid" => $from_id,
                    "name" => $full_name,
                    "username" => $from_username,
                    "joined_at" => $now
                ])->execute();
            }
        }
    }

    $user = $db->table("user")->select()->where([["tid", "=", $from_id]])->execute();
    $show_sponser = true;
    $show_ad = true;
    $bot_msg_id = null;
    $step = null;
    if (count($user) > 0) {
        $show_sponser = $user[0]['show_sponser'];
        $show_ad = $user[0]['show_ad'];
        $bot_msg_id = $user[0]['bot_msg_id'];
        $step = $user[0]['step'];
    }

    return [$user, $show_sponser, $show_ad, $bot_msg_id, $step];
}

function referral_notif($referrer_tid)
{
    global $db, $from_id;

    $db->transaction();
    try{
        $referrer = $db->table('user')->select()->where([['tid', '=', $referrer_tid]])->execute()[0];
        $referrer_score = $db->table('user_score')->select()->where([['user_id', '=', $referrer['id']], ['referral_tid', '=', $from_id]])->execute();

        if (count($referrer_score) == 0) {
            [$title, $link, $username] = get_chat_info($from_id, 'user');
            sendMessage($referrer_tid, "🎉 کاربر [$title]($link) با استفاده از لینک دعوت شما وارد ربات شد و 300 امتیاز به شما تعلق گرفت");

            $db->table('user_score')->insert([
                "user_id" => $referrer['id'],
                "score" => 300,
                "referral_tid" => $from_id,
                "archived" => "1",
                'date' => date("Y-m-d H:i:s")
            ])->execute();

            $db->table('user')->update([
                "score" => $referrer['score'] + 300,
                "league_score" => $referrer['league_score'] + 300
            ])->where([['id', '=', $referrer['id']]])->execute();
        }

        $db->commit();
    }catch (Exception $ex) {
        $db->rollback();
        exit;
    }
}

// avoid sending message in this function. it will cause infinit loop
function update_user($bot_msg_id = null, $new_step = null) {
    global $from_id, $db, $now;

    $update_arr = ['blocked_by_user' => '0', "last_interaction"=>$now];

    if (isset($new_step)) {
        $update_arr['step'] = $new_step;
    } else {
        $update_arr['step'] = null;
    }

    if (isset($bot_msg_id)) {
        $update_arr['bot_msg_id'] = $bot_msg_id;
    }

    $db->table("user")->update($update_arr)->where([['tid', '=', $from_id]])->execute();
}

function set_log(...$logs)
{
    global $from_id;
    foreach($logs as $log) {
        $logContent = "\n" . "-------------------" . "\n" . '-' . date("Y-m-d H:i:s") . '-' . $from_id . "-" . $log;
        $path = $_SERVER['DOCUMENT_ROOT'] . '/';
        if (!file_exists($path)) {
            mkdir($path, 0700);
        }
        file_put_contents($path . "bot_log.txt", $logContent . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

function extract_actions($data)
{
    preg_match('/^(.*?)~(.*?)$/', $data, $matches);

    $current = isset($matches[1]) ? $matches[1] : null;
    $previous = isset($matches[2]) ? $matches[2] : null;

    return [$current, $previous];
}

function append_previous_action($new_command, $previous_command = null)
{
    global $input_type, $text, $data, $chat_id;

    if ($input_type == 'message') {
        $current_command = $text;
    } elseif (preg_match('/^(.*?)~(.*?)$/', $data, $matches)) {
        [$current, $previous] = extract_actions($data);
        $current_command = $current;
    } else {
        $current_command = $data;
    }

    if ($current_command == $new_command) {
        $previous_command = $previous_command;
    } else {
        $previous_command = $current_command;
    }

    return $new_command . "~" . $previous_command;
}

function validate_user_sponsers()
{
    global $db, $chat_type, $query_id, $input_type, $text, $from_id, $chat_id, $bot_token, $show_sponser, $data, $from_id, $interval;

    if (isset($data)) {
        [$current, $previous] = extract_actions($data);
    } elseif (isset($text)) {
        [$current, $previous] = extract_actions($text);
    }

    if ($chat_type == 'private' || is_null($chat_type)) {
        if (!is_null($from_id)) {
            if ($show_sponser || !is_admin($from_id)) {
                $active_sponsers = $db->raw("SELECT * FROM sponser WHERE active = '1'")->execute();
                $bot_sponser_buttons = [];
                $join_require = [];
                foreach ($active_sponsers as $index => $sponser) {
                    $sponser_chat_id = $sponser['chat_id'];
                    $sponser_invite_link = $sponser['invite_link'];
                    if (!is_chat_member($from_id, $sponser_chat_id)) {
                        $join_require[] = $sponser;
                    }
                }

                foreach ($join_require as $index => $sponser) {
                    $sponser_chat_id = $sponser['chat_id'];
                    $sponser_invite_link = $sponser['invite_link'];
                    if (!is_chat_member($from_id, $sponser_chat_id)) {
                        $channel_index = $interval[$index + 1];
                        $bot_sponser_buttons[] = [
                            ['text' => "📢 عضویت در کانال $channel_index", 'url' => "https://t.me/$sponser_invite_link"],
                        ];
                    }
                }

                $sponser_count = count($bot_sponser_buttons);
                if ($sponser_count > 0) {
                    $bot_sponser_buttons[] = [
                        ['text' => "تایید عضویت ✅", 'callback_data' => append_previous_action('validate_user_sponsers', $previous)],
                    ];

                    if ($current == 'validate_user_sponsers') {
                        $ch = curl_init("https://api.telegram.org/bot$bot_token/answerCallbackQuery");
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, ['callback_query_id' => $query_id, 'text' => "هنوز که عضو نشدی رفیق ❤️"]);
                        curl_exec($ch);
                        curl_close($ch);
                    } else {
                        if ($sponser_count > 1) {
                            $channel = "کانال های";
                        } else {
                            $channel = "کانال";
                        }
                        sendMessage(
                            $chat_id,
                            "لطفا برای استفاده از ربات ابتدا در $channel زیر عضو شوید.",
                            ['inline_keyboard' => $bot_sponser_buttons],
                            null, true, false
                        );
                    }
                    exit;
                } else {
                    if ($current == 'validate_user_sponsers') {
                        if ($input_type == 'message' && isset($current)) {
                            $text = $previous;
                        } elseif (isset($current)) {
                            $data = $previous;
                        }
                        deleteMessage();
                    }
                }
            }
        }
    }
}

function greet()
{
    global $text, $data, $input_type, $from_id;

    if ($input_type == 'message') {
        $haystack = $text;
    } else {
        $haystack = $data;
    }

    if ($haystack == '/start' || strpos($haystack, 'referrer_') !== false) {
        greetFunc();
    }
}

function greetFunc()
{
    global $chat_id, $from_id, $home_buttons, $text;

    [$title, $link, $username] = get_chat_info($chat_id, 'user');
    $greet_msg = "👀 سلام $title خوش اومدی";

    if (is_super_admin($chat_id)) {
        $greet_msg = "سلام رِئیس 👑 خوش اومدی";
    }

    sendMessage(
        $chat_id, $greet_msg,
        ['inline_keyboard' => $home_buttons],
        null, true, true, true
    );
}

function validate()
{
    maintance();
    check_user();
    validate_user_sponsers();
    greet();
}

function is_admin($from_id)
{
    global $admin_users_tids, $super_user_tids;
    if (in_array($from_id, $admin_users_tids) || in_array($from_id, $super_user_tids)) {
        return true;
    }
    return false;
}

function is_super_admin($from_id)
{
    global $super_user_tids;
    if (in_array($from_id, $super_user_tids)) {
        return true;
    }
    return false;
}

function check_user()
{
    global $user, $chat_id;
    
    if (count($user) > 0) {
        if (!$user[0]['active']) {
            sendMessage($chat_id, "اکانت شما بنابر دلایلی مسدود شده است. برای اطلاعات بیشتر با پشتیبان در ارتباط باشید");
            exit();
        }
    }
}

function addEmptyRow($buttons) {
    $buttons[] = [
        ['text' => " ", 'callback_data' => 'none']
    ];
    return $buttons;
}

function add_return_home($buttons = [], $return_data = null, $new = false) {
    if (isset($return_data)) {
        $buttons[] = [
            ['text' => "🏠 خانه", 'callback_data' => $new === true ? 'home_new' : 'home'], ['text' => "➥ برگشت", 'callback_data' => $return_data]
        ];
    } else {
        $buttons[] = [
            ['text' => "🏠 خانه", 'callback_data' => $new === true ? 'home_new' : 'home']
        ];
    }
    return $buttons;
}

function recursive_log($array) {
    // Check if the array is multidimensional
    if (is_array($array) && count($array) > 0 && is_array(current($array))) {
        // If it's multidimensional, dig into each element recursively
        foreach ($array as $item) {
            recursive_log($item);
        }
    } else {
        // If it's not multidimensional, dump the item
        set_log($array);
    }
}