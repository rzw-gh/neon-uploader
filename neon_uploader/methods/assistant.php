<?php
if (is_admin($from_id)) {
    $buttons = [[['text' => "🏠 خانه", 'callback_data' => 'home'], ['text' => "➥ برگشت", 'callback_data' => 'menu_assistant']]];
    if (preg_match('/^menu_assistant(?:_([\w,]+))?$/', $data, $matches)) {
        if (isset($matches[1])) {
            sendMessage(
                $from_id,
                "🙌 دستیار",
                ['inline_keyboard' => [
                    [['text' => "📎 ضمیمه کردن تصویر به متن", 'callback_data' => 'attach_picture']],
                    [['text' => "💎 دکمه شیشه ای", 'callback_data' => 'glassy_button_text']],
                    [['text' => "✍️ ویرایش فروارد", 'callback_data' => 'edit_forward_message']],
                    [['text' => "🏠 خانه", 'callback_data' => 'home']],
                ]]
            );
        } else {
            editMsg(
                "🙌 دستیار",
                ['inline_keyboard' => [
                    [['text' => "📎 ضمیمه کردن تصویر به متن", 'callback_data' => 'attach_picture']],
                    [['text' => "💎 دکمه شیشه ای", 'callback_data' => 'glassy_button_text']],
                    [['text' => "✍️ ویرایش فروارد", 'callback_data' => 'edit_forward_message']],
                    [['text' => "🏠 خانه", 'callback_data' => 'home']],
                ]]
            );
        }
    } elseif ($data == 'attach_picture') {
        $msg_id = editMsg("`content\_title \n[ ](link-to-image.com/image.png) \n[DOWNLOAD](link-to-file.com)`", ['inline_keyboard' => $buttons]);
        if (isset($msg_id)) {
            update_user($msg_id, 'attach_picture');
        }
    } elseif (preg_match('/^glassy_button_(\w+)$/', $data, $matches)) {
        $type = $matches[1];
        $msg_id = editMsg("`caption here\n***\n0_[google](google.com) , 1_0_[telegram](telegram.com) , 1_1_[insta](instagram.com)`", ['inline_keyboard' => $buttons], $type);
        if (isset($msg_id)) {
            update_user($msg_id, 'glassy_button');
        }
    } elseif ($step == 'attach_picture') {
        $buttons = [];
        foreach ($channel_ids as $channel_id) {
            [$title, $link, $username] = get_chat_info($channel_id, 'channel');
            $title = '"' . $title . '"';
            $buttons[] = [
                ['text' => "ارسال به $title", 'callback_data' => "share_attached_picture_$channel_id"]
            ];
        }
        $buttons[] = [['text' => "➥ برگشت", 'callback_data' => 'attach_picture']];

        $db->table('user')->update(['bot_text' => $text])->where([['tid', '=', $from_id]])->execute();

        deleteMessage($bot_msg_id);
        sendMessage($chat_id, $text, ['inline_keyboard' => $buttons], null, false);
    } elseif ($step == 'glassy_button') {
        $text = isset($caption) ? $caption : $text;
        $db->table('user')->update(['bot_text' => $text, 'photo_id' => $photo_id])->where([['tid', '=', $from_id]])->execute();

        [$caption, $buttons] = extract_glassy_buttons($text);

        $buttons = addEmptyRow($buttons);
        foreach ($channel_ids as $channel_id) {
            [$title, $link, $username] = get_chat_info($channel_id, 'channel');
            $title = '"' . $title . '"';
            $buttons[] = [
                ['text' => "ارسال به $title", 'callback_data' => "share_glassy_button_$channel_id"]
            ];
        }

        if (isset($photo_id)) {
            $type = 'caption';
        } else {
            $type = 'text';
        }
        $buttons[] = [['text' => "➥ برگشت", 'callback_data' => "glassy_button_$type"]];

        deleteMessage($bot_msg_id);
        if (!is_null($photo_id)) {
            sendDocument("photo", $chat_id, $photo_id, $caption, ['inline_keyboard' => $buttons]);
        } else {
            sendMessage($chat_id, $caption, ['inline_keyboard' => $buttons]);
        }
    } elseif (preg_match('/^share_attached_picture_(-?\d+)$/', $data, $matches)) {
        $channel_id = $matches[1];
        deleteMessage($bot_msg_id);
        $msg = $db->table('user')->select('bot_text')->where([['tid', '=', $from_id]])->execute()[0]['bot_text'];
        $buttons = [[['text' => "🏠 خانه", 'callback_data' => 'home'], ['text' => "➥ برگشت", 'callback_data' => 'attach_picture']]];
        if (isset($msg) && $msg !== '') {
            $bot_msg_id = sendMessage($channel_id, $msg, null, null, false);
            if (isset($bot_msg_id)) {
                sendMessage($chat_id, '✅ محتوای مورد نظر با موفقیت به کانال ارسال شد', ['inline_keyboard' => $buttons]);
            } else {
                sendMessage($chat_id, '⚠️ خطایی رخ داده است لطفا دوباره تلاش کنید ', ['inline_keyboard' => $buttons]);
            }
        } else {
            sendMessage($chat_id, '⚠️ خطایی رخ داده است لطفا دوباره تلاش کنید ', ['inline_keyboard' => $buttons]);
        }
    } elseif (preg_match('/^share_glassy_button_(-?\d+)$/', $data, $matches)) {
        $user = $db->table('user')->select()->where([['tid', '=', $from_id]])->execute()[0];
        [$caption, $glassy_buttons] = extract_glassy_buttons($user['bot_text']);
        $channel_id = $matches[1];
        deleteMessage($bot_msg_id);
        if (!is_null($user['photo_id'])) {
            $bot_msg_id = sendDocument("photo", $channel_id, $user['photo_id'], $caption, ['inline_keyboard' => $glassy_buttons]);
        } else {
            $bot_msg_id = sendMessage($channel_id, $caption, ['inline_keyboard' => $glassy_buttons]);
        }
        $buttons = [[['text' => "🏠 خانه", 'callback_data' => 'home'], ['text' => "➥ برگشت", 'callback_data' => "glassy_button_text"]]];
        if (isset($bot_msg_id)) {
            sendMessage($from_id, '✅ محتوای مورد نظر با موفقیت به کانال ارسال شد', ['inline_keyboard' => $buttons]);
        } else {
            sendMessage($from_id, '⚠️ خطایی رخ داده است لطفا دوباره تلاش کنید ', ['inline_keyboard' => $buttons]);
        }
    } elseif ($data == 'edit_forward_message') {
        $msg_id = editMsg("📬 حالا پیامی که میخوای ویرایش کنی رو برام فروارد کن", ['inline_keyboard' => $buttons]);
        if (isset($msg_id)) {
            update_user($msg_id, 'edit_forward_message');
        }
    } elseif ($step === 'edit_forward_message') {
        if (isset($photo_id) || isset($video_id) || isset($document_id)) {
            $msg_id = sendMessage($from_id, "📬 حالا متنی که میخوای زیرش قرار بدی رو برام بفرس (اگه میخوای خالی باشه کلمه خالی رو بفرس)", ['inline_keyboard' => $buttons]);
            if (isset($msg_id)) {
                update_user($msg_id, 'edit_forward_message_caption');
                if (isset($photo_id)) {
                    $media_type = 'photo';
                    $media_id = $photo_id;
                } elseif (isset($video_id)) {
                    $media_type = 'video';
                    $media_id = $video_id;
                } elseif (isset($document_id)) {
                    $media_type = 'document';
                    $media_id = $document_id;
                }
                $db->table('user')->update(['bot_text' => "$media_type^$media_id"])->where([['tid', '=', $from_id]])->execute();
            }
        } else {
            $msg_id = sendMessage($from_id, "🫠 اینو که خودتم میتونی ویرایش کنی رفیق (:", ['inline_keyboard' => $buttons]);
        }
    } elseif ($step === 'edit_forward_message_caption' && isset($text)) {
        $buttons = [];
        foreach ($channel_ids as $channel_id) {
            [$title, $link, $username] = get_chat_info($channel_id, 'channel');
            $title = '"' . $title . '"';
            $buttons[] = [
                ['text' => "ارسال به $title", 'callback_data' => "share_forwarded_msg_$channel_id"]
            ];
        }

        $buttons[] = [
            ['text' => "🏠 خانه", 'callback_data' => 'home_new'],
            ['text' => "➥ برگشت", 'callback_data' => 'menu_assistant_new'],
        ];

        $user = explode("^", $db->table('user')->select('bot_text')->where([['tid', '=', $from_id]])->execute()[0]['bot_text']);
        $media_type = $user[0];
        $media_id = $user[1];
        if ($text === 'خالی') {
            $caption = null;
            $db->table('user')->update(['bot_text' => "$media_type^$media_id^$caption"])->where([['tid', '=', $from_id]])->execute();
        } else {
            $caption = $text;
            $db->table('user')->update(['bot_text' => "$media_type^$media_id^$caption"])->where([['tid', '=', $from_id]])->execute();
        }

        if ($media_type == "photo") {
            $bot_msg_id = sendDocument("photo", $from_id, $media_id, $caption, ['inline_keyboard' => $buttons]);
        } elseif ($media_type == "video") {
            $bot_msg_id = sendDocument("video", $from_id, $media_id, $caption, ['inline_keyboard' => $buttons]);
        } elseif ($media_type == "document") {
            $bot_msg_id = sendDocument("document", $from_id, $media_id, $caption, ['inline_keyboard' => $buttons]);
        }
    } elseif (preg_match('/^share_forwarded_msg_(-?\d+)$/', $data, $matches)) {
        $channel_id = $matches[1];
        deleteMessage($bot_msg_id);
        $buttons = [[['text' => "🏠 خانه", 'callback_data' => 'home'], ['text' => "➥ برگشت", 'callback_data' => 'menu_assistant']]];

        $user = explode("^", $db->table('user')->select('bot_text')->where([['tid', '=', $from_id]])->execute()[0]['bot_text']);
        $media_type = $user[0];
        $media_id = $user[1];
        $caption = $user[2] === "null" ? null : $user[2];
        if ($media_type == "photo") {
            $bot_msg_id = sendDocument("photo", $channel_id, $media_id, $caption);
        } elseif ($media_type == "video") {
            $bot_msg_id = sendDocument("video", $channel_id, $media_id, $caption);
        } elseif ($media_type == "document") {
            $bot_msg_id = sendDocument("document", $channel_id, $media_id, $caption);
        }

        if (isset($bot_msg_id)) {
            sendMessage($from_id, '✅ محتوای مورد نظر با موفقیت به کانال ارسال شد', ['inline_keyboard' => $buttons]);
        } else {
            sendMessage($from_id, '⚠️ خطایی رخ داده است لطفا دوباره تلاش کنید ', ['inline_keyboard' => $buttons]);
        }
    }
}

function extract_glassy_buttons($text) {
    // Separate the caption and the remaining text
    [$caption, $textWithoutCaption] = explode("***\n", $text);

    // Use preg_split to split the string based on commas outside parentheses
    $elements = preg_split('/\s*,\s*(?![^()]*\))/', $textWithoutCaption);

    // Initialize a new array to store the rearranged result
    $buttons = [];

    // Iterate through the elements
    foreach ($elements as $element) {
        // Extract index, prefix, text, and link from the element
        preg_match('/^(\d+)_?(.*)_\[([^)]+)\]\(([^)]+)\)$/', $element, $matches);
        $index = intval($matches[1]);
        $prefix = $matches[2];
        $text = $matches[3];
        $link = $matches[4];

        // If there is a prefix, use it to nest the item within the resultArr
        if ($prefix !== "") {
            // Add the item to the nested array without an extra level of nesting
            $buttons[$index][] = ["text" => $text, "url" => $link];
        } else {
            // If no prefix, add the item directly to the resultArr
            $buttons[$index][] = ["text" => $text, "url" => $link];
        }
    }

    return [$caption, $buttons];
}