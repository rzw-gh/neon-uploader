<?php
if (is_super_admin($from_id)) {
    if (!isset($content_channel_id)) {
        sendMessage($from_id, "⚠️ لطفا ابتدا از تنظیمات کانال محتوا را ست کنید"); exit;
    }
} else {
    if (!isset($content_channel_id)) {
        sendMessage($from_id, "به زودی با قابلیت های بیشتر برمیگردیم... ♥️"); exit;
    }
}

/////////////////////////////USER PANEL/////////////////////////////////
if ($input_type == 'message') {
    $haystack = $text;
} else {
    $haystack = $data;
}

if (preg_match('/\scontent_(\d+)$/', $haystack, $matches)) {
    show_content($matches);
} elseif (preg_match('/^content_(\d+)(?:_([\w,]+))?$/', $data, $matches)) {
    show_content($matches);
} elseif (preg_match('/^content_show_quality_(\d+)(?:_([\w,]+))?$/', $data, $matches)) {
    show_content_quality($matches);
} elseif (preg_match('/^content_show_parts_(\d+)_(\d+)_(\d+)(?:_([\w,]+))?$/', $data, $matches)) {
    show_parts($matches);
} elseif (preg_match('/^content_download_all_parts_(\d+)_(\d+)$/', $data, $matches)) {
    $content_id = $matches[1];
    $quality = $matches[2];
    $content = $db->table('content')->select()->where([['id', '=', $content_id]])->execute();
    $content_download = (int)$content[0]['download'] === 1 ? false : true;
    $omit = $content[0]['omit'];

    $parts = $db->raw("SELECT * FROM content WHERE parent_id = '$content_id' AND quality = '$quality' ORDER BY id ASC")->execute();

    $sent_ids = [];
    foreach ($parts as $part) {
        $content_id = $part['id'];
        $post_id = $part['post_id'];
        $message_id = copyContent($post_id, null, null, false, false, $content_download);
        if (isset($message_id)) {
            $sent_ids[$message_id] = $content_id;
        }
    }

    if ($omit && count($sent_ids) > 0) {
        $txt = count($parts) > 1 ? ' های' : '';
        $txt2 = count($parts) > 1 ? 'میشوند' : 'میشود';
        $txt3 = count($parts) > 1 ? ' هارو' : '';
        sendMessage($chat_id, "‼️ فایل$txt بالا به زودی پاک $txt2" . "\n" . "لطفا فایل$txt3 بفرستید به سیو مسیج خودتون", null, null, true, false, false);
        schedule_content_omit($sent_ids);
    }
} elseif (preg_match('/^content_download_part_(\d+)$/', $data, $matches)) {
    $content_id = $matches[1];
    $content = $db->table('content')->select()->where([['id', '=', $content_id]])->execute();
    $content_download = (int)$content[0]['download'] === 1 ? false : true;
    $post_id = $content[0]['post_id'];
    $omit = $content[0]['omit'];
    $message_id = copyContent($post_id, null, null, false, false, $content_download);
    if (isset($message_id) && $omit) {
        sendMessage($chat_id, "‼️ فایل بالا به زودی پاک میشود" . "\n" . "لطفا فایل رو بفرستید به سیو مسیج خودتون", null, null, true, false, false);
        schedule_content_omit([$message_id=>$content_id]);
    }
} elseif (preg_match('/^content_(first|second)_reaction_(\d+)$/', $data, $matches)) {
    $type = $matches[1];
    $post_id = $matches[2];
    $content = $db->table('content')->select()->where([['post_id', '=', $post_id]])->execute()[0];
    $content_id = $content['id'];
    $user = $db->table('user')->select()->where([['tid', '=', $from_id]])->execute()[0];
    $user_reaction = $db->table('user_content_reaction')->select()->where([['user_id', '=', $user['id']], ['content_id', '=', $content_id]])->execute();
    if (count($user_reaction) > 0) {
        show_alert("⚠️ شما قبلا یک بار ری اکشن زدین");
    } elseif (count($user_reaction) == 0) {
        $db->transaction();
        try {
            if ($type == 'first') {
                $coloum = $type."_reaction";
                $reaction_count = (int)$content['first_reaction_count'];
            } elseif ($type == 'second') {
                $coloum = $type."_reaction";
                $reaction_count = (int)$content['second_reaction_count'];
            }
            $db->table('user_content_reaction')->insert(['user_id' => $user['id'], 'content_id' => $content_id, $coloum => '1', 'date' => date("Y-m-d H:i:s")])->execute();
            $reaction_count += 1;
            $db->table('content')->update([$coloum."_count" => $reaction_count])->where([['post_id', '=', $post_id]])->execute();
            $content = $db->table('content')->select()->where([['post_id', '=', $post_id]])->execute()[0];
            $view_count = intval($content['view']);
            $post_id = $content['post_id'];
            $buttons = create_content_buttons($post_id);
            $bot_msg = editMsg(null, ['inline_keyboard' => $buttons], 'button');
            $db->commit();
        } catch (Exception $ex) {
            $db->rollback();
            exit;
        }
    }
} elseif (preg_match('/^(add|remove)_favorite_(\d+)$/', $data, $matches)) {
    $type = $matches[1];
    $content_id = $matches[2];

    $db->transaction();
    try{
        $user = $db->table('user')->select()->where([['tid', '=', $from_id]])->execute()[0];
        if ($type == 'add') {
            $db->table('favorite')->insert(["user_id" => $user['id'], "content_id" => $content_id])->execute();
        } elseif ($type == 'remove') {
            $db->table('favorite')->delete()->where([['user_id', '=', $user['id']], ['content_id', '=', $content_id]])->execute();
        }

        $content = $db->table('content')->select()->where([['id', '=', $content_id]])->execute()[0];
        $buttons = create_content_buttons($content['post_id']);
        editMsg(null, ['inline_keyboard' => $buttons], 'button');
        $db->commit();
    }catch (Exception $ex) {
        show_alert("⚠️ خطایی رخ داد");
        exit;
    }
} elseif ($data == 'user_favorite_content') {
    $user = $db->table('user')->select()->where([['tid', '=', $from_id]])->execute()[0];
    $user_id = $user['id'];
    $user_favorites = $db->raw("SELECT * FROM favorite WHERE user_id = '$user_id' ORDER BY id DESC")->execute();

    if (count($user_favorites) == 0) {
        show_alert("لیست علاقه مندی های شما خالی میباشد");
        exit;
    }

    $caption = "\n\n";

    foreach ($user_favorites as $index => $user_favorite) {
        $content = $db->table('content')->select()->where([['id', '=', $user_favorite['content_id']], ['active', '=', '1']])->execute();
        if (count($content) > 0) {
            $content = $content[0];
            $content_title = $content['title'];
            $post_id = $content['post_id'];
            $link = "t.me/$bot_username?start=content_$post_id";

            if ($index === 0) {
                $caption .= "● [$content_title]($link)";
            } else {
                $caption .= "● [$content_title]($link)";
                if (count($user_favorites) > $index + 1) {$caption .= "\n";}
            }
        }
    }

    $buttons[] = [['text' => "➥ برگشت", 'callback_data' => 'home']];

    editMsg("♥️ علاقه مندی ها".$caption, ['inline_keyboard' => $buttons]);
} elseif ($data == "content_search_trigger") {
    $msg_id = editMsg(
        "🔎 متن جستجو را وارد کنید",
        ['inline_keyboard' => [
            [['text' => "➥ برگشت", 'callback_data' => 'home']],
        ]]
    );
    update_user($msg_id, "content_search");
} elseif ($text == "/content_search_trigger") {
    $msg_id = sendMessage(
        $chat_id,
        "🔎 متن جستجو را وارد کنید",
        ['inline_keyboard' => [
            [['text' => "➥ برگشت", 'callback_data' => 'home']],
        ]]
    );
    update_user($msg_id, "content_search");
} elseif ($step === "content_search" && !isset($data) && $haystack != '/start' && $text != '/content_search_trigger') {
    deleteMessage();
    $search = $db->raw("SELECT * FROM content WHERE title LIKE '%" . $text . "%' ORDER BY id")->execute();
    live_statistics("جستجو: $text", $from_id);

    if (count($search) > 0) {
        $caption = "🔎 نتایج جستجو برای `$text`\n\n";
        foreach ($search as $index => $result) {
            $content_title = $result['title'];
            $link = "http://t.me/$bot_username?start=content_" . $result['post_id'];
            if ($index === 0) {
                $caption .= "● [$content_title]($link)\n";
            } else {
                $caption .= "● [$content_title]($link)";
                if (count($search) > $index + 1) {$caption .= "\n";}
            }
        }
    } else {
        $caption = "🔎 نتیجه ای برای جستجوی شما یافت نشد";
    }

    $msg_id = editMsg(
        $caption,
        ['inline_keyboard' => [
            [['text' => "➥ برگشت", 'callback_data' => 'home']],
        ]],
        'text'
    );

    update_user($msg_id, "content_search");
}

/////////////////////////////ADMIN PANEL/////////////////////////////////

if (is_super_admin($from_id)){
    if ($data == 'menu_content') {
        editMsg(
            "🍿 محتوا",
            ['inline_keyboard' => [
                [['text' => "➕ افزودن محتوا", 'callback_data' => 'add_content'], ['text' => "🎬 لیست محتوا", 'callback_data' => 'list_content']],
                [['text' => "♥️ علاقه مندی من", 'callback_data' => 'user_favorite_content'], ['text' => "🔎 جستجو", 'callback_data' => 'content_search_trigger']],
                [['text' => "🏠 خانه", 'callback_data' => 'home']],
            ]]
        );
    } elseif (strpos($data, 'list_content') !== false) {
        list_content();
    } elseif (preg_match('/^view_content_(\d+)$/', $data, $matches)) {
        view_content($matches[1]);
    } elseif (preg_match('/^view_content_old_(\d+)$/', $data, $matches)) {
        view_content($matches[1], false);
    } elseif (preg_match('/get_content_link_(\d+)/', $data, $matches)) {
        $post_id = $matches[1];
        $buttons[] = [
            ['text' => "⤴️ بازگشت به محتوا", 'callback_data' => 'cancel_content_operation_' . $post_id],
        ];
        editMsg("`http://t.me/$bot_username?start=content_$post_id`", ['inline_keyboard' => $buttons], 'caption');
    } elseif (preg_match('/^(deactivate|activate)_content_(\d+)$/', $data, $matches)) {
        $type = $matches[1];
        $post_id = $matches[2];
        $res = $db->table('content')->update(['active' => $type === 'activate' ? '1' : '0'])->where([['post_id', '=', $post_id]])->execute();
        $alert_msg = $type === 'activate' ? "✅ محتوا فعال شد" : "❌ محتوا غیر فعال شد";
        if ($res['status']){
            show_alert($alert_msg);
            view_content($post_id, false);
        } else {
            show_alert("⚠️ خطایی رخ داد لطفا دوباره تلاش کنید");
            exit;
        }
    } elseif (preg_match('/^(film|serie)_show_type_(\d+)$/', $data, $matches)) {
        $type = $matches[1];
        $post_id = $matches[2];
        $res = $db->table('content')->update(['show_type' => $type])->where([['post_id', '=', $post_id]])->execute();
        $alert_msg = $type === 'film' ? "🍿 نوع فیلم انتخاب شد" : "🍿 نوع سریال انتخاب شد";
        if ($res['status']) {
            show_alert($alert_msg);
            view_content($post_id, false);
        } else {
            show_alert("⚠️ خطایی رخ داد لطفا دوباره تلاش کنید", true);
        }
    } elseif (preg_match('/^(deactivate|activate)_content_download_(\d+)$/', $data, $matches)) {
        $type = $matches[1];
        $post_id = $matches[2];

        $res = $db->table('content')->update(['download' => $type === 'activate' ? '1' : '0'])->where([['post_id', '=', $post_id]])->execute();
        $alert_msg = $type === 'activate' ? "✅ اجازه دانلود فعال شد" : "❌ اجازه دانلود غیر فعال شد";
        if ($res['status']){
            show_alert($alert_msg);
            view_content($post_id, false);
        } else {
            show_alert("⚠️ خطایی رخ داد لطفا دوباره تلاش کنید");
            exit;
        }
    } elseif (preg_match('/^(deactivate|activate)_content_omit_(\d+)$/', $data, $matches)) {
        $type = $matches[1];
        $post_id = $matches[2];

        $res = $db->table('content')->update(['omit' => $type === 'activate' ? '1' : '0'])->where([['post_id', '=', $post_id]])->execute();
        $alert_msg = $type === 'activate' ? "✅ حذف خودکار محتوا فعال شد" : "❌ حذف خودکار غیر فعال شد";
        if ($res['status']){
            show_alert($alert_msg);
            view_content($post_id, false);
        } else {
            show_alert("⚠️ خطایی رخ داد لطفا دوباره تلاش کنید");
            exit;
        }
    } elseif (preg_match('/^confirm_delete_content_(\d+)$/', $data, $matches)) {
        $post_id = $matches[1];
        $db->transaction();
        try{
            $content = $db->table('content')->select()->where([['post_id', '=', $post_id]])->execute();
            $db->table('content')->delete()->where([['id', '=', $content[0]['id']]])->execute();
            $db->table('content')->delete()->where([['parent_id', '=', $content[0]['id']]])->execute();
            $seasons = $db->table('content')->select()->where([['main_season_id', '=', $content[0]['id']]])->execute();
            foreach ($seasons as $season) {
                $db->table('content')->delete()->where([['id', '=', $season['id']]])->execute();
                $db->table('content')->delete()->where([['parent_id', '=', $season['id']]])->execute();
            } 
            $db->commit();

            show_alert('محتوای مورد نظر با موفقیت حذف شد ✅');
            deleteMessage($bot_msg_id);

            if (isset($content[0]['parent_id'])) {
                $content = $db->table('content')->select()->where([['id', '=', $content[0]['parent_id']]])->execute();
                view_content($content[0]['post_id']);
            } else{
                list_content('list_content_return_' . $post_id);
            }
            
        }catch (Exception $ex) {
            $db->rollback();
            exit;
        }
    } elseif (preg_match('/^delete_content_(\d+)$/', $data, $matches)) {
        $post_id = $matches[1];
        $buttons = [];
        $buttons[] = [
            ['text' => "❌ لغو", 'callback_data' => 'cancel_content_operation_' . $post_id],
            ['text' => "✅ تایید حذف", 'callback_data' => 'confirm_delete_content_' . $post_id]
        ];
        editMsg(null, ['inline_keyboard' => $buttons], 'button');
    } elseif (preg_match('/^cancel_content_operation_(\d+)$/', $data, $matches)) {
        $post_id = $matches[1];
        update_user($msg_id, null);
        view_content($post_id, false);
    } elseif (preg_match('/^edit_content_(\d+)$/', $data, $matches)) {
        $post_id = $matches[1];
        $content = $db->table('content')->select()->where([['post_id', '=', $post_id]])->execute();
        $description = escapeMarkdownV2($content[0]['description']);
        $buttons = [];
        $buttons[] = [
            ['text' => "✍️ توضیحات", 'callback_data' => 'edit_content_description_' . $post_id],
            ['text' => "♻️ جایگزینی", 'callback_data' => 'replace_content_main_' . $post_id],
        ];
        $buttons[] = [
            ['text' => "❌ لغو", 'callback_data' => 'cancel_content_operation_' . $post_id],
        ];
        editMsg($description, ['inline_keyboard' => $buttons], 'caption', "MarkDownV2");
    } elseif (preg_match('/^edit_content_description_(\d+)$/', $data, $matches)) {
        $post_id = $matches[1];
        $content = $db->table('content')->select()->where([['post_id', '=', $post_id]])->execute();
        $buttons = [];
        $buttons[] = [
            ['text' => "❌ لغو", 'callback_data' => 'cancel_content_operation_' . $post_id]
        ];

        $caption = "✍️ توضیحات جدید را ارسال کنید";
        $msg_id = editMsg($caption, ['inline_keyboard' => $buttons], 'caption');
        update_user($msg_id, 'edit_content_'.$content[0]['id'].'_'.$post_id.'_description');
    } elseif (preg_match('/^edit_content_(\d+)_(\d+)_description$/', $step, $matches) && isset($text)) {
        $content_id = $matches[1];
        $post_id = $matches[2];
        $buttons = [
            [['text' => "⤴️ بازگشت به محتوا", 'callback_data' => 'cancel_content_operation_' . $post_id]]
        ];

        deleteMessage();
        $db->table('content')->update(['description' => $text])->where([['id', '=', $content_id]])->execute();
        if ($db->hasError() == false){
            update_user($msg_id, null);
            $msg_id = editMsg('توضیحات با موفقیت ویرایش شد ✅', ['inline_keyboard' => $buttons], 'caption');
            deleteMessage();
        } else {
            $msg_id = editMsg('⚠️ خطایی رخ داد لطفا دوباره تلاش کنید', ['inline_keyboard' => $buttons], 'caption');
        }
    } elseif (preg_match('/^view_episode_(\d+)_(\d+)(?:_([\w,]+))?$/', $data, $matches)) {
        $parent_post_id = $matches[1];
        $episode = $matches[2];

        if (isset($matches[3])) {
            $quality = $matches[3];
            $parent_content = $db->table('content')->select()->where([['post_id', '=', $parent_post_id]])->execute();
            $parent_content_id = $parent_content[0]['id'];
            $content = $db->raw("SELECT * FROM content where parent_id = '$parent_content_id' and episode = '$episode' and quality = '$quality'")->execute();
            if (count($content) > 0) {
                $content_post_id = $content[0]['post_id'];
                view_content($content_post_id);
            } else {
                $buttons = [
                    [['text' => "🌟 افزودن کیفیت $quality", 'callback_data' => 'add_episode_' . $parent_post_id . "_" . $episode . "_" . $quality], ['text' => "➥ برگشت", 'callback_data' => 'view_episode_' . $parent_post_id . "_" . $episode]]
                ];
                if ($episode < 10) {$episode = "0" . $episode;}
                editMsg("⚠️ کیفیت $quality برای قسمت $episode یافت نشد", ['inline_keyboard' => $buttons], 'caption');
            }
        } else {
            $buttons = [
                [['text' => "🌟 کیفیت 1080", 'callback_data' => 'view_episode_' . $parent_post_id . "_" . $episode . "_1080"]],
                [['text' => "🌟 کیفیت 480", 'callback_data' => 'view_episode_' . $parent_post_id . "_" . $episode . "_480"], ['text' => "🌟 کیفیت 720", 'callback_data' => 'view_episode_' . $parent_post_id . "_" . $episode . "_720"]],
                [['text' => "➥ برگشت", 'callback_data' => 'cancel_content_operation_' . $parent_post_id]],
            ];
            if ($episode < 10) {$episode = "0" . $episode;}
            editMsg("🎬 انتخاب کیفیت قسمت $episode", ['inline_keyboard' => $buttons], 'caption');
        }
    } elseif (strpos($data, 'add_content') !== false) {
        $buttons = [];
        $buttons[] = [
            ['text' => "🏠 خانه", 'callback_data' => 'home'],
            ['text' => "➥ برگشت", 'callback_data' => 'menu_content'],
        ];
        $msg_id = editMsg("لطفا عنوان محتوا را وارد کنید", ['inline_keyboard' => $buttons]);
        if (isset($msg_id)) {
            update_user($msg_id, 'new_content_title');
        }
    } elseif ($step === "new_content_title" && isset($text)) {
        $buttons = [];
        $buttons[] = [
            ['text' => "🏠 خانه", 'callback_data' => 'home'],
            ['text' => "➥ برگشت", 'callback_data' => 'menu_content'],
        ];
        $msg_id = sendMessage($chat_id, "لطفا توضیحات محتوا را وارد کنید\nبرای خالی گذاشتن کلمه خالی را وارد کنید", ['inline_keyboard' => $buttons]);
        if (isset($msg_id)) {
            update_user($msg_id, 'new_content_description');
            $db->table('user')->update(['bot_text' => $text])->where([['tid', '=', $from_id]])->execute();
        }
    } elseif ($step === "new_content_description" && isset($text)) {
        $title = $db->table('user')->select('bot_text')->where([['tid', '=', $from_id]])->execute()[0]['bot_text'];
        $buttons = [];
        $buttons[] = [
            ['text' => "🏠 خانه", 'callback_data' => 'home'],
            ['text' => "➥ برگشت", 'callback_data' => 'menu_content'],
        ];
        $msg_id = sendMessage($chat_id, "لطفا فایل محتوا را آپلود یا فروارد کنید", ['inline_keyboard' => $buttons]);
        if (isset($msg_id)) {
            update_user($msg_id, 'new_content_file');
            $db->table('user')->update(['bot_text' => $text])->where([['tid', '=', $from_id]])->execute();
            $db->table('user')->update(['bot_text' => "$title^$text"])->where([['tid', '=', $from_id]])->execute();
        }
    } elseif ($step === "new_content_file" && !isset($text) && !isset($data)) {
        $user_detail = explode("^", $db->table('user')->select('bot_text')->where([['tid', '=', $from_id]])->execute()[0]['bot_text']);
        $title = $user_detail[0];
        $description = $user_detail[1];

        $buttons = [];
        $buttons[] = [
            ['text' => "🏠 خانه", 'callback_data' => 'home'],
            ['text' => "➥ برگشت", 'callback_data' => 'menu_content'],
        ];

        $msg_id = bot(
            'copyMessage',
            [
                'chat_id' => $content_channel_id,
                'from_chat_id' => $from_id,
                'message_id' => $message_id,
            ], false
        );
        if (isset($msg_id)) {
            if ($description === 'خالی') {
                $description = null;
            }

            $res = $db->table('content')->insert([
                'author' => $chat_id,
                'title' => $title,
                'description' => $description,
                'post_id'=>$msg_id,
                'created_at'=>$now,
                'first_reaction'=>'👍',
                'second_reaction'=>'👎',
                'season' => '1',
            ])->execute();

            if ($res['status']) {
                sendMessage($chat_id, 'محتوا با موفقیت اضافه شد ✅', ['inline_keyboard' => [[['text' => "👀 مشاهده محتوا", 'callback_data' => 'view_content_' . $msg_id]]]]);
            } else {
                editMsg("⚠️ خطایی رخ داده است لطفا دوباره تلاش کنید", ['inline_keyboard' => $buttons]);
                deleteMessage();
            }
        } else {
            editMsg("⚠️ خطایی رخ داده است لطفا دوباره تلاش کنید", ['inline_keyboard' => $buttons]);
            deleteMessage();
        }
    } elseif (preg_match('/^add_season_(\d+)_(\d+)$/', $data, $matches)) {
        $post_id = $matches[1];
        $season = $matches[2];

        $buttons[] = [
            ['text' => "➥ برگشت", 'callback_data' => 'cancel_content_operation_' . $post_id],
        ];
        $msg_id = editMsg("لطفا عنوان بخش " . ($season < 10 ? "0" . $season : $season) . " را وارد کنید", ['inline_keyboard' => $buttons], 'caption');
        if (isset($msg_id)) {
            update_user($msg_id, 'new_content_season_title');
            $db->table('user')->update(['bot_text' => "$post_id^$season"])->where([['tid', '=', $from_id]])->execute();
        }
    } elseif ($step === "new_content_season_title" && isset($text)) {
        $user_detail = explode("^", $db->table('user')->select('bot_text')->where([['tid', '=', $from_id]])->execute()[0]['bot_text']);
        $post_id = $user_detail[0];
        $season = $user_detail[1];

        $buttons[] = [
            ['text' => "➥ برگشت", 'callback_data' => 'add_season_' . $post_id . "_" . $season],
        ];
        $msg_id = sendMessage($chat_id, "لطفا توضیحات محتوا را وارد کنید\nبرای خالی گذاشتن کلمه خالی را وارد کنید", ['inline_keyboard' => $buttons]);
        if (isset($msg_id)) {
            update_user($msg_id, 'new_content_season_description');
            $db->table('user')->update(['bot_text' => "$post_id^$season^$text"])->where([['tid', '=', $from_id]])->execute();
        }
    } elseif ($step === "new_content_season_description" && isset($text)) {
        $user_detail = explode("^", $db->table('user')->select('bot_text')->where([['tid', '=', $from_id]])->execute()[0]['bot_text']);
        $post_id = $user_detail[0];
        $season = $user_detail[1];
        $title = $user_detail[2];

        $buttons[] = [
            ['text' => "➥ برگشت", 'callback_data' => 'add_season_' . $post_id . "_" . $season],
        ];
        $msg_id = sendMessage($chat_id, "لطفا فایل محتوا را آپلود یا فروارد کنید", ['inline_keyboard' => $buttons]);
        if (isset($msg_id)) {
            update_user($msg_id, 'new_content_season_file');
            $db->table('user')->update(['bot_text' => "$post_id^$season^$title^$text"])->where([['tid', '=', $from_id]])->execute();
        }
    } elseif ($step === "new_content_season_file" && !isset($text) && !isset($data)) {
        $user_detail = explode("^", $db->table('user')->select('bot_text')->where([['tid', '=', $from_id]])->execute()[0]['bot_text']);
        $post_id = $user_detail[0];
        $season = $user_detail[1];
        $title = $user_detail[2];
        $description = $user_detail[3];
        $content = $db->table('content')->select()->where([['post_id', '=', $post_id]])->execute();

        if (isset($content[0]['main_season_id'])) {
            $main_season_id = $content[0]['main_season_id'];
        } else {
            $main_season_id = $content[0]['id'];
        }

        $buttons[] = [
            ['text' => "➥ برگشت", 'callback_data' => 'cancel_content_operation_' . $post_id],
        ];

        $msg_id = bot(
            'copyMessage',
            [
                'chat_id' => $content_channel_id,
                'from_chat_id' => $from_id,
                'message_id' => $message_id,
            ], false
        );
        if (isset($msg_id)) {
            if ($description === 'خالی') {
                $description = null;
            }

            $res = $db->table('content')->insert([
                'author' => $chat_id,
                'title' => $title,
                'description' => $description,
                'post_id'=>$msg_id,
                'created_at'=>$now,
                'first_reaction'=>'👍',
                'second_reaction'=>'👎',
                'season' => $season,
                'main_season_id' => $main_season_id
            ])->execute();

            if ($res['status']) {
                sendMessage($chat_id, "بخش " . ($season < 10 ? "0" . $season : $season) . " با موفقیت اضافه شد ✅", ['inline_keyboard' => [[['text' => "👀 مشاهده بخش", 'callback_data' => 'view_content_' . $msg_id]]]]);
            } else {
                editMsg("⚠️ خطایی رخ داده است لطفا دوباره تلاش کنید", ['inline_keyboard' => $buttons]);
                deleteMessage();
            }
        } else {
            editMsg("⚠️ خطایی رخ داده است لطفا دوباره تلاش کنید", ['inline_keyboard' => $buttons]);
            deleteMessage();
        }
    } elseif (preg_match('/^add_episode_(\d+)_(\d+)(?:_([\w,]+))?/', $data, $matches)) {
        $parent_post_id = $matches[1];
        $episode = $matches[2];

        if (isset($matches[3])) {
            $quality = $matches[3];
            $buttons[] = [
                ['text' => "➥ برگشت", 'callback_data' => 'add_episode_' . $parent_post_id],
            ];
            $msg_id = editMsg("📤 لطفا فایل کیفیت $quality قسمت " . ($episode < 10 ? "0" . $episode : $episode) . " را آپلود یا فروارد کنید", ['inline_keyboard' => $buttons], 'caption');
            if (isset($msg_id)) {
                update_user($msg_id, 'new_content_episode_file');
                $db->table('user')->update(['bot_text' => "$parent_post_id,$episode,$quality"])->where([['tid', '=', $from_id]])->execute();
            }
        } else {
            $buttons = [
                [['text' => "🌟 کیفیت 1080", 'callback_data' => 'add_episode_' . $parent_post_id . "_" . $episode  . "_1080"]],
                [['text' => "🌟 کیفیت 480", 'callback_data' => 'add_episode_' . $parent_post_id . "_" . $episode  . "_480"], ['text' => "🌟 کیفیت 720", 'callback_data' => 'add_episode_' . $parent_post_id . "_" . $episode  . "_720"]],
                [['text' => "➥ برگشت", 'callback_data' => 'cancel_content_operation_' . $parent_post_id]],
            ];
            editMsg("🎬 افزودن قسمت " . ($episode < 10 ? "0" . $episode : $episode), ['inline_keyboard' => $buttons], 'caption');
        }
    } elseif ($step === "new_content_episode_file" && !isset($text) && !isset($data)) {
        $user_detail = explode(",", $db->table('user')->select('bot_text')->where([['tid', '=', $from_id]])->execute()[0]['bot_text']);
        $parent_post_id = $user_detail[0];
        $episode = $user_detail[1];
        $quality = $user_detail[2];
        $content = $db->table('content')->select()->where([['post_id', '=', $parent_post_id]])->execute();
        $buttons[] = [
            ['text' => "➥ برگشت", 'callback_data' => 'cancel_content_operation_' . $parent_post_id],
        ];
        $msg_id = bot(
            'copyMessage',
            [
                'chat_id' => $content_channel_id,
                'from_chat_id' => $from_id,
                'message_id' => $message_id,
            ], false
        );
        if (isset($msg_id)) {
            deleteMessage();
            $res = $db->table('content')->insert([
                'author' => $chat_id,
                'parent_id' => $content[0]['id'],
                'post_id' => $msg_id,
                'first_reaction'=>'👍',
                'second_reaction'=>'👎',
                'episode' => $episode,
                'quality' => $quality,
                'created_at' => $now
            ])->execute();
            if ($res['status']) {
                editMsg('قسمت با موفقیت اضافه شد ✅', ['inline_keyboard' => $buttons], 'caption');
            } else {
                $error = true;
            }
        } else {
            $error = true;
        }
        if ($error) {
            editMsg("⚠️ خطایی رخ داد لطفا دوباره تلاش کنید", ['inline_keyboard' => $buttons], 'caption');
            update_user($msg_id, 'new_content_episode_file');
            $db->table('user')->update(['bot_text' => "$parent_post_id,$episode,$quality"])->where([['tid', '=', $from_id]])->execute();
        }
    } elseif (preg_match('/^replace_content_(main|part)_(\d+)$/', $data, $matches)) {
        $type = $matches[1];
        $post_id = $matches[2];
        $buttons[] = [
            ['text' => "➥ برگشت", 'callback_data' => 'cancel_content_operation_' . $post_id],
        ];
        $msg_id = editMsg("📤 لطفا فایل جایگزین را آپلود یا فروارد کنید", ['inline_keyboard' => $buttons], 'caption');
        if (isset($msg_id)) {
            update_user($msg_id, 'replace_content');
            $db->table('user')->update(['bot_text' => "$post_id,$msg_id,$type"])->where([['tid', '=', $from_id]])->execute();
        }
    } elseif ($step === "replace_content" && !isset($text) && !isset($data)) {
        $user_detail = explode(",", $db->table('user')->select('bot_text')->where([['tid', '=', $from_id]])->execute()[0]['bot_text']);
        $post_id = $user_detail[0];
        $delete_msge_id = $user_detail[1];
        $type = $user_detail[2];
        $content = $db->table('content')->select()->where([['post_id', '=', $post_id]])->execute();

        $msg_id = bot(
            'copyMessage',
            [
                'chat_id' => $content_channel_id,
                'from_chat_id' => $from_id,
                'message_id' => $message_id,
            ], false
        );

        if ($type == 'part') {
            $content = $db->table('content')->select()->where([['id', '=', $content[0]['parent_id']]])->execute();
            $return_post_id = $content[0]['post_id'];
        } else {
            $return_post_id = $msg_id;
        }

        $buttons[] = [
            ['text' => "➥ بازگشت به محتوا", 'callback_data' => 'view_content_' . $return_post_id],
        ];

        if (isset($msg_id)) {
            $res = $db->table('content')->update(['author' => $chat_id, 'post_id'=>$msg_id])->where([['post_id', '=', $post_id]])->execute();
            if ($res['status']) {
                $type_text = $type === 'part' ? "قسمت" : "محتوا";
                sendMessage($chat_id, "$type_text با موفقیت جایگزین شد ✅", ['inline_keyboard' => $buttons], null, true, true, true);
                deleteMessage($delete_msge_id);
            } else {
                $error = true;
            }
        } else {
            $error = true;
        }

        if ($error) {
            sendMessage($chat_id, "⚠️ خطایی رخ داد لطفا دوباره تلاش کنید", ['inline_keyboard' => $buttons], null, true, true, true);
            deleteMessage($delete_msge_id);
            update_user($msg_id, 'replace_content');
            $db->table('user')->update(['bot_text' => "$post_id,$delete_msge_id,$type"])->where([['tid', '=', $from_id]])->execute();
        }
    }

    if (preg_match('/\/start\?action=(\w+)&post_id=(\d+)&post_new_id=(\d+)/', $text, $matches)) {
        // replace_content
        $contentAction = $matches[1];
        $contentPostId = $matches[2];
        $contentNewPostId = $matches[3];

        $content = $db->table('content')->select()->where([['post_id', '=', $contentPostId]])->execute();
        $content = $db->table('content')->select()->where([['id', '=', $content[0]['parent_id']]])->execute();

        $buttons[] = [
            ['text' => "➥ بازگشت به محتوا", 'callback_data' => 'view_content_' . $content[0]['post_id']],
        ];

        if (!is_null($contentAction) && $contentAction == 'replace_content') {
            if (is_null($contentPostId) || is_null($contentNewPostId)) {
                sendMessage($chat_id, '⚠️ پارامتر های وارد شده کامل نیست', ['inline_keyboard' => $buttons], $message_id, true, true, true);
            } else {
                $res = $db->table('content')->update(['author' => $chat_id, 'post_id'=>$contentNewPostId])->where([['post_id', '=', $contentPostId]])->execute();
                if ($db->hasError() == false){
                    sendMessage($chat_id, 'جایگزینی محتوای مورد نظر با موفقیت انجام شد ✅', ['inline_keyboard' => $buttons], $message_id, true, true, true);
                } else {
                    sendMessage($chat_id, 'خطایی به هنگام جایگزینی محتوا رخ داده است لطفا دوباره تلاش کنید ⚠️', ['inline_keyboard' => $buttons], $message_id, true, true, true);
                }
            }
        }
    }

    if (preg_match('/^automate_send_choose_channel_(\d+)$/', $data, $matches) && isset($data)) {
        $post_id = $matches[1];
        $buttons = [];

        $content = $db->table('content')->select()->where([['post_id', '=', $post_id]])->execute();
        $caption = preg_replace('/\s✍️.*/', '', $content[0]['description']);
        $caption = preg_replace('/\sS\d+/', '', $caption);
        $caption = escapeMarkdownV2($caption);
        $caption .= "\n🍿 لینک های دانلود:\n➖ [دریافت از ربات تلگرامی](http://t.me/$bot_username?start=content_$post_id)";

        foreach ($channel_ids as $channel_id) {
            [$title, $link, $username] = get_chat_info($channel_id, 'channel');
            $title = '"' . $title . '"';
            $buttons[] = [
                ['text' => "ارسال به $title", 'callback_data' => "automate_send_$post_id" . "_channel_$channel_id"]
            ];
        }

        $buttons[] = [
            ['text' => "🏠 خانه", 'callback_data' => 'home_new'],
            ['text' => "➥ برگشت", 'callback_data' => 'cancel_content_operation_' . $post_id],
        ];
        editMsg($caption, ['inline_keyboard' => $buttons], 'caption', "MarkdownV2");
    } elseif (preg_match('/^automate_send_(\d+)_channel_(-?\d+)$/', $data, $matches)) {
        $post_id = $matches[1];
        $channel_id = $matches[2];

        deleteMessage($bot_msg_id);
        $buttons = [[['text' => "🏠 خانه", 'callback_data' => 'home'], ['text' => "➥ برگشت", 'callback_data' => "view_content_$post_id"]]];

        $content = $db->table('content')->select()->where([['post_id', '=', $post_id]])->execute();
        $caption = preg_replace('/\s✍️.*/', '', $content[0]['description']);
        $caption = preg_replace('/\sS\d+/', '', $caption);
        $caption = escapeMarkdownV2($caption);
        $caption .= "\n🍿 لینک های دانلود:\n➖ [دریافت از ربات تلگرامی](http://t.me/$bot_username?start=content_$post_id)";
        $bot_msg_id = copyContent($post_id, $caption, null, true, false, false, $channel_id, "MarkdownV2");

        if (isset($bot_msg_id)) {
            sendMessage($from_id, '✅ محتوای مورد نظر با موفقیت به کانال ارسال شد', ['inline_keyboard' => $buttons]);
        } else {
            sendMessage($from_id, '⚠️ خطایی رخ داده است لطفا دوباره تلاش کنید ', ['inline_keyboard' => $buttons]);
        }
    }
}

// user view
function show_content($matches, $new = true) {
    global $db, $chat_id, $from_id, $message_id, $text;
    if (isset($matches[2]) && $matches[2] == 'old') {$new = false;}

    $post_id = $matches[1];
    $content = $db->table('content')->select()->where([['post_id', '=', $post_id]])->execute();
    if (count($content) > 0) {
        if (!$content[0]['active']) {
            if (isset($text)) {
                sendMessage($chat_id, 'محتوای مورد نظر یافت نشد 🫠', null, $message_id, true, true, true); exit;
            } else {
                show_alert("🫠 محتوای مورد نظر در حال به روز رسانی میباشد", true);
            }
        } else {
            $content_id = $content[0]['id'];

            live_statistics($content_id, $from_id);

            $view_count = intval($content[0]['view']) + 1;
            if ($view_count >= 1000) {
                $view_count = formatViews($view_count);
            }

            $favorite_count = $db->raw("SELECT COUNT(f.id) AS favorite_count FROM content c LEFT JOIN favorite f ON c.id = f.content_id WHERE c.id = $content_id;")->execute()[0]['favorite_count'];
            if ($favorite_count >= 1000) {
                $favorite_count = formatViews($favorite_count);
            }

            $buttons = [];

            $post_id = $content[0]['post_id'];
            $buttons = create_content_buttons($post_id, $buttons);
            $caption = $content[0]['description'] . "\n\n" . "👀 تعداد بازدید: $view_count". "\n" . "♥️ در لیست علاقه مندی های $favorite_count نفر";

            $content_download = (int)$content[0]['download'] === 1 ? false : true;

            if ($new) {
                $message_id = copyContent($post_id, $caption, ['inline_keyboard' => $buttons], true, true, $content_download);
            } else {
                $message_id = editMsg(null, ['inline_keyboard' => $buttons], 'button');
            }

            if (isset($message_id)) {
                $db->table('content')->update(["view" => $view_count])->where([['id', '=', $content_id]])->execute();
                $db->table('user')->update(['user_viewed_last_content_id' => $content[0]['id']])->where([['tid', '=', $from_id]])->execute();

                if (isset($content[0]['parent_id'])) {
                    $parent_id = $content[0]['parent_id'];
                    $parent_content = $db->table('content')->select()->where([['id', '=', $parent_id]])->execute()[0];
                    $db->table('content')->update(["view" => intval($parent_content['view']) + 1])->where([['id', '=', $parent_id]])->execute();
                }
            }
        }
    } else {
        sendMessage($chat_id, 'متاسفانه فایل مورد نظر یافت نشد 🫠', null, $message_id, true, true, true);
    }
}

function show_parts($matches) {
    global $db, $from_id;

    $content_id = $matches[1];
    $quality = $matches[2];
    $page = $matches[3];
    $return = isset($matches[4]) ? true : false;

    $content = $db->table('content')->select()->where([['id', '=', $content_id]])->execute();
    $post_id = $content[0]['post_id'];
    $query = "SELECT * FROM content WHERE parent_id = '$content_id' AND quality = '$quality' ORDER BY episode DESC";
    [$episodes, $pagination] = paginate($query, 12, $page);

    if (is_super_admin($from_id)) {
        $post_id = $content[0]['post_id'];
        $buttons[] = [
            ['text' => "🎬 محتوا", 'callback_data' => "view_content_$post_id"],
            ['text' => "🆔 " . $content[0]['id'], 'callback_data' => 'none'],
        ];
    }

    $buttons[] = [['text' => "● کیفیت $quality ●", 'callback_data' => "none"]];

    if (count($episodes) > 1 && count($episodes) <= 24) {
        $buttons[] = [['text' => "📦 دریافت یکجا", 'callback_data' => "content_download_all_parts_$content_id"."_$quality"]];
    }

    for ($i = 0; $i < count($episodes); $i += 2) {
        if (isset($episodes[$i + 1])) {
            $index1 = $episodes[$i]['episode'];
            if ($index1 < 10) {$index1 = "0" . $index1;}
            $index2 = $episodes[$i + 1]['episode'];
            if ($index2 < 10) {$index2 = "0" . $index2;}
            $buttons[] = [
                ['text' => "🍿 قسمت $index2", 'callback_data' => "content_download_part_" . $episodes[$i + 1]['id']],
                ['text' => "🍿 قسمت $index1", 'callback_data' => "content_download_part_" . $episodes[$i]['id']],
            ];
        } else {
            $index = $episodes[$i]['episode'];
            if ($index < 10) {$index = "0" . $index;}
            $buttons[] = [
                ['text' => "🍿 قسمت $index", 'callback_data' => "content_download_part_" . $episodes[$i]['id']],
            ];
        }
    }

    if (count($pagination) > 0) {
        if (isset($pagination['prev']) && isset($pagination['next'])) {
            if ($return) {
                $buttons[] = [
                    ['text' => "صفحه بعد ⬅️", 'callback_data' => 'content_show_parts_' . $content_id . "_" . $quality . "_" . $pagination['next'] . "_1"],
                    ['text' => "➡️ صفحه قبل", 'callback_data' => 'content_show_parts_' . $content_id . "_" . $quality . "_" . $pagination['prev'] . "_1"],
                ];
                $buttons[] = [['text' => "⤴️ بازگشت", 'callback_data' => "content_$post_id"."_old"]];
            } else {
                $buttons[] = [
                    ['text' => "صفحه بعد ⬅️", 'callback_data' => 'content_show_parts_' . $content_id . "_" . $quality . "_" . $pagination['next']],
                    ['text' => "➡️ صفحه قبل", 'callback_data' => 'content_show_parts_' . $content_id . "_" . $quality . "_" . $pagination['prev']],
                ];
                $buttons[] = [['text' => "⤴️ بازگشت به کیفیت ها", 'callback_data' => "content_show_quality_$content_id"."_single"]];
            }
        } else {
            if (isset($pagination['prev'])) {
                if ($return) {
                    $buttons[] = [
                        ['text' => "⤴️ بازگشت", 'callback_data' => "content_$post_id"],
                        ['text' => "➡️ صفحه قبل", 'callback_data' => 'content_show_parts_' . $content_id . "_" . $quality . "_" . $pagination['prev'] . "_1"],
                    ];
                } else {
                    $buttons[] = [
                        ['text' => "⤴️ بازگشت به کیفیت ها", 'callback_data' => "content_show_quality_$content_id"."_single"],
                        ['text' => "➡️ صفحه قبل", 'callback_data' => 'content_show_parts_' . $content_id . "_" . $quality . "_" . $pagination['prev']],
                    ];
                }
            } elseif (isset($pagination['next'])) {
                if ($return) {
                    $buttons[] = [
                        ['text' => "صفحه بعد ⬅️", 'callback_data' => 'content_show_parts_' . $content_id . "_" . $quality . "_" . $pagination['next'] . "_1"],
                        ['text' => "⤴️ بازگشت", 'callback_data' => "content_$post_id"."_old"],
                    ];
                } else {
                    $buttons[] = [
                        ['text' => "صفحه بعد ⬅️", 'callback_data' => 'content_show_parts_' . $content_id . "_" . $quality . "_" . $pagination['next']],
                        ['text' => "⤴️ بازگشت به کیفیت ها", 'callback_data' => "content_show_quality_$content_id"."_single"],
                    ];
                }
                
            }
        }
    } elseif ($return) {
        $buttons[] = [['text' => "⤴️ بازگشت", 'callback_data' => "content_$post_id"."_old"]];
    } else {
        $buttons[] = [['text' => "⤴️ بازگشت به کیفیت ها", 'callback_data' => "content_show_quality_$content_id"."_single"]];
    }

    editMsg(null, ['inline_keyboard' => $buttons], 'button');
}

function show_content_quality($matches) {
    global $db, $chat_id, $from_id;
    $content_id = $matches[1];
    $type = $matches[2];
    $content = $db->table('content')->select()->where([['id', '=', $content_id]])->execute();
    $post_id = $content[0]['post_id'];
    $buttons = [];

    if (is_super_admin($from_id)) {
        $post_id = $content[0]['post_id'];
        $buttons[] = [
            ['text' => "🎬 محتوا", 'callback_data' => "view_content_$post_id"],
            ['text' => "🆔 " . $content[0]['id'], 'callback_data' => 'none'],
        ];
    }

    $quality_count = $db->raw("SELECT COUNT(DISTINCT quality) AS num_qualities FROM `content` WHERE parent_id = '$content_id'")->execute()[0]['num_qualities'];
    if ($type == 'latest') {
        if ($quality_count > 1) {
            $qualities_arr = ["1080", "720", "480"];

            $last_episode = $db->raw("SELECT MAX(episode) AS last_episode FROM content WHERE parent_id = '$content_id';")->execute();
            if (count($last_episode) > 0 && !is_null(@$last_episode[0]['last_episode'])) {
                $last_episode = $last_episode[0]['last_episode'];
            } else {
                $last_episode = '1';
            }

            $qualities = [];
            foreach($qualities_arr as $quality) {
                $episode_quality = $db->raw("SELECT * FROM content WHERE parent_id = '$content_id' AND episode = '$last_episode' AND quality = '$quality'")->execute();
                if (count($episode_quality) > 0) {
                    $qualities[] = $quality;
                }
            }

            foreach($qualities as $quality) {
                $latest_id = $db->raw("SELECT MAX(id) AS latest_id FROM content WHERE parent_id = '$content_id' AND quality = '$quality'")->execute()[0]['latest_id'];
                if (isset($latest_id)) {
                    $buttons[] = [['text' => "🌟 کیفیت $quality", 'callback_data' => "content_download_part_$latest_id"]];
                }
            }
        } else {
            $latest_id = $db->raw("SELECT MAX(id) AS latest_id FROM content WHERE parent_id = '$content_id'")->execute()[0]['latest_id'];
            $content = $db->table('content')->select()->where([['id', '=', $latest_id]])->execute();
            $content_download = (int)$content[0]['download'] === 1 ? false : true;
            $post_id = $content[0]['post_id'];
            $omit = $content[0]['omit'];
            $message_id = copyContent($post_id, null, null, false, false, $content_download);
            if (isset($message_id) && $omit) {
                sendMessage($chat_id, "‼️ فایل بالا به زودی پاک میشود" . "\n" . "لطفا فایل رو بفرستید به سیو مسیج خودتون", null, null, true, false, false);
                schedule_content_omit([$message_id=>$latest_id]);
            }
            exit;
        }
    } elseif ($type == 'single') {
        if ($quality_count > 1) {
            foreach(["1080", "720", "480"] as $quality) {
                $count = $db->raw("SELECT COUNT(id) AS count FROM content WHERE parent_id = '$content_id' AND quality = '$quality';")->execute()[0]['count'];
                if ($count > 0) {
                    $buttons[] = [['text' => "🌟 کیفیت $quality", 'callback_data' => "content_show_parts_$content_id"."_$quality"."_1"]];
                }
            }
        } else {
            foreach(["1080", "720", "480"] as $quality) {
                $count = $db->raw("SELECT COUNT(id) AS count FROM content WHERE parent_id = '$content_id' AND quality = '$quality';")->execute()[0]['count'];
                if ($count > 0) {
                    show_parts([null, $content_id, $quality, 1, 1]);
                    break;
                }
            }
            exit;
        }
    }

    $buttons[] = [['text' => "⤴️ بازگشت", 'callback_data' => "content_$post_id"."_old"]];
    editMsg(null, ['inline_keyboard' => $buttons], 'button');
}

function create_content_buttons($post_id, $buttons = []) {
    global $db, $from_id;

    $content = $db->table('content')->select()->where([['post_id', '=', $post_id]])->execute();
    $content_id = $content[0]['id'];
    $season = $content[0]['season'];
    $user = $db->table('user')->select()->where([['tid', '=', $from_id]])->execute()[0];

    if (is_super_admin($from_id)) {
        $post_id = $content[0]['post_id'];
        $buttons[] = [
            ['text' => "🎬 محتوا", 'callback_data' => "view_content_$post_id"],
            ['text' => "🆔 " . $content[0]['id'], 'callback_data' => 'none'],
        ];
    }

    //EPISODES
    $episodes = $db->table('content')->select()->where([['parent_id', '=', $content_id]])->execute();
    if (count($episodes) > 0) {
        $buttons[] = [
            ['text' => "🔥 جدید ترین قسمت", 'callback_data' => "content_show_quality_$content_id"."_latest"],
            ['text' => "🎬 قسمت ها", 'callback_data' => "content_show_quality_$content_id"."_single"]
        ];
    } else {
        $buttons[] = [['text' => "🫠 لینک دانلود به زودی", 'callback_data' => "none"]];
    }

    //REACTION
    $first_reaction = $content[0]['first_reaction'];
    $first_reaction_count = $content[0]['first_reaction_count'];
    $second_reaction = $content[0]['second_reaction'];
    $second_reaction_count = $content[0]['second_reaction_count'];
    $buttons[] = [
        ['text' => $second_reaction_count . " $second_reaction", 'callback_data' => "content_second_reaction_$post_id"],
        ['text' => $first_reaction_count . " $first_reaction", 'callback_data' => "content_first_reaction_$post_id"]
    ];

    //FAVORITE
    $favorite = $db->table('favorite')->select()->where([['user_id', '=', $user['id']], ['content_id', '=', $content_id]])->execute();
    if (count($favorite) == 0) {
        $buttons[] = [['text' => "♥️ افزودن به علاقه مندی", 'callback_data' => "add_favorite_$content_id"]];
    } else {
        $buttons[] = [['text' => "💔 حذف از علاقه مندی", 'callback_data' => "remove_favorite_$content_id"]];
    }

    //SEASON
    if (isset($content[0]['main_season_id'])) {
        $main_season_id = $content[0]['main_season_id'];
        $next_season = $db->raw("SELECT post_id FROM content WHERE season > $season AND main_season_id = $main_season_id LIMIT 1")->execute();
        if ((int)$season === 2) {
            $prev_season_post_id = $db->raw("SELECT post_id FROM content WHERE id = $main_season_id")->execute()[0]['post_id'];
        } else {
            $prev_season_post_id = $db->raw("SELECT post_id FROM content WHERE season < $season AND main_season_id = $main_season_id LIMIT 1")->execute()[0]['post_id'];
        }
        if (count($next_season) > 0) {
            $next_season_post_id = $next_season[0]['post_id'];
            $buttons[] = [
                ['text' => "فصل بعدی ⬅️", 'callback_data' => 'content_' . $next_season_post_id],
                ['text' => "➡️ فصل قبلی", 'callback_data' => 'content_' . $prev_season_post_id],
            ];
        } else {
            $buttons[] = [
                ['text' => "➡️ فصل قبلی", 'callback_data' => 'content_' . $prev_season_post_id],
            ];
        }
    } else {
        $main_season_id = $content[0]['id'];
        $next_season = $db->raw("SELECT post_id FROM content WHERE season > $season AND main_season_id = $main_season_id LIMIT 1")->execute();
        if (count($next_season) > 0) {
            $next_season_post_id = $next_season[0]['post_id'];
            $buttons[] = [
                ['text' => "فصل بعدی ⬅️", 'callback_data' => 'content_' . $next_season_post_id],
            ];
        }
    }

    return $buttons;
}

function list_content($data_param = null) {
    global $db, $bot_token, $query_id, $data, $bot_msg_id, $chat_id, $content_channel_id;

    if (is_null($content_channel_id)) {
        editMsg(
            "⚠️ لطفا ابتدا کانال محتوا را از بخش تنظیمات اضافه کنید",
            ['inline_keyboard' => [
                [['text' => "🏠 خانه", 'callback_data' => 'home'], ['text' => "➥ برگشت", 'callback_data' => 'menu_setting']]
            ]]
        );
        exit;
    }

    if (!is_null($data_param)) {
        $data = $data_param;
    }

    $new = false;
    $content_count = $db->raw("SELECT COUNT(id) AS `content_count` FROM content WHERE parent_id IS NULL")->execute()[0]['content_count'];

    if ($content_count == 0) {
        show_alert("⚠️ محتوایی وجود ندارد"); exit;
    } else {
        if (preg_match('/^list_content_(next|previous|return)_(\d+)$/', $data, $matches)) {
            $type = $matches[1];
            $first_id = $matches[2];
            if ($type == 'next') {
                $contents = $db->raw("SELECT * FROM content WHERE parent_id IS NULL AND main_season_id IS NULL AND post_id < $first_id ORDER BY id DESC LIMIT 25")->execute();
            } elseif ($type == 'previous') {
                $contents = $db->raw("SELECT * FROM content WHERE parent_id IS NULL AND main_season_id IS NULL AND post_id > $first_id ORDER BY id DESC LIMIT 25")->execute();
            } elseif ($type == 'return') {
                $new = true;
                deleteMessage($bot_msg_id);
                if ($content_count <= 25) {
                    $contents = $db->raw("SELECT * FROM content WHERE parent_id IS NULL AND main_season_id IS NULL ORDER BY id DESC")->execute();
                } else {
                    $contents = $db->raw("SELECT * FROM content WHERE parent_id IS NULL AND main_season_id IS NULL AND post_id <= $first_id ORDER BY id DESC LIMIT 25")->execute();
                }
            }

            if (count($contents) == 0) {
                show_alert("⚠️ محتوایی وجود ندارد");
                exit;
            }
        } else {
            $contents = $db->raw("SELECT * FROM content WHERE parent_id IS NULL AND main_season_id IS NULL ORDER BY id DESC LIMIT 25")->execute();
        }

        $buttons = [];

        $buttons[] = [
            ['text' => "بازدید", 'callback_data' => 'none'],
            ['text' => "عنوان", 'callback_data' => 'none'],
        ];

        $first_id = null;
        $last_id = null;

        foreach ($contents as $key => $content) {
            $title = $content['title'];
            $post_id = $content['post_id'];

            if ($key == 0) {
                $first_id = $post_id;
            }

            if ($key + 1 == count($contents)) {
                $last_id = $post_id;
            }

            $buttons[] = [
                ['text' => $content['view'], 'callback_data' => 'view_content_' . $post_id],
                ['text' => $title, 'callback_data' => 'view_content_' . $post_id],
            ];
        }

        if ($content_count > 25) {
            $buttons[] = [
                ['text' => "قبلی ⬅️", 'callback_data' => "list_content_previous_".$first_id],
                ['text' => "➡️ بعدی", 'callback_data' => "list_content_next_".$last_id],
            ];
        }

        $buttons[] = [
            ['text' => "🏠 خانه", 'callback_data' => 'home'],
            ['text' => "➥ برگشت", 'callback_data' => 'menu_content'],
        ];

        if ($new) {
            sendMessage($chat_id, "🎬 لیست محتوا", ['inline_keyboard' => $buttons]);
        } else {
            editMsg("🎬 لیست محتوا", ['inline_keyboard' => $buttons]);
        }
    }
}

// admin view
function view_content($post_id, $new = true) {
    global $db, $bot_msg_id;
    $buttons = [];

    if ($new) { deleteMessage($bot_msg_id); }
    $content = $db->table('content')->select()->where([['post_id', '=', $post_id]])->execute();
    $content_id = $content[0]['id'];
    $season = $content[0]['season'];
    $view_count = $content[0]['view'];
    [$title, $link, $username] = get_chat_info($content[0]['author'], 'user');
    if (is_null($link)) {
        $callback_type = 'callback_data';
        $callback_data = 'none'; 
    } else {
        $callback_type = 'url';
        $callback_data = $link; 
    }

    if (is_null($content[0]['parent_id'])) { // content
        $episodes = $db->raw("SELECT * FROM content where parent_id = $content_id and episode is NOT NULL GROUP BY episode")->execute();

        $buttons[] = [
            ['text' => "🆔 " . $content[0]['id'], 'callback_data' => 'none'],
        ];
        $buttons[] = [
            ['text' => $content[0]['title'] . " 🎬", 'callback_data' => 'none'],
        ];
        $buttons[] = [
            ['text' => '🧑‍💻 ناشر: "'.$title.'"', $callback_type => $callback_data]
        ];

        $first_reaction = $content[0]['first_reaction'];
        $first_reaction_count = $content[0]['first_reaction_count'];
        $second_reaction = $content[0]['second_reaction'];
        $second_reaction_count = $content[0]['second_reaction_count'];

        $buttons[] = [
            ['text' => $content[0]['show_type'] == "film" ? '🍿 نوع فیلم' : '🍿 نوع سریال', 'callback_data' => $content[0]['show_type'] == "film" ? 'serie_show_type_' . $post_id : 'film_show_type_' . $post_id],
            ['text' => $content[0]['active'] ? '✅ فعال' : '❌ غیر فعال', 'callback_data' => $content[0]['active'] ? 'deactivate_content_' . $post_id : 'activate_content_' . $post_id]
        ];
        $buttons[] = [
            ['text' => $content[0]['omit'] ? "✅ حذف خودکار" : "❌ حذف خودکار", 'callback_data' => $content[0]['omit'] ? 'deactivate_content_omit_' . $post_id : 'activate_content_omit_' . $post_id],
            ['text' => $content[0]['download'] ? '✅ اجازه دانلود' : '❌ اجازه دانلود', 'callback_data' => $content[0]['download'] ? 'deactivate_content_download_' . $post_id : 'activate_content_download_' . $post_id]
        ];
        $buttons[] = [
            ['text' => $first_reaction_count . " $first_reaction " . $second_reaction_count . " $second_reaction", 'callback_data' => "none"],
            ['text' => "👀 $view_count بازدید", 'callback_data' => 'none']
        ];
        $buttons[] = [
            ['text' => "❌ حذف", 'callback_data' => 'delete_content_' . $post_id],
            ['text' => "✍️ ویرایش", 'callback_data' => 'edit_content_' . $post_id]
        ];
        $buttons[] = [
            ['text' => "🔗 دریافت لینک", 'callback_data' => 'get_content_link_' . $post_id],
            ['text' => "📢 ارسال به کانال", 'callback_data' => 'automate_send_choose_channel_' . $post_id]
        ];

        $last_episode = $db->raw("SELECT MAX(episode) AS last_episode FROM content WHERE parent_id = $content_id;")->execute();
        if (count($last_episode) > 0 && !is_null(@$last_episode[0]['last_episode'])) {
            $last_episode = $last_episode[0]['last_episode'] + 1;
        } else {
            $last_episode = '1';
        }

        if (isset($content[0]['main_season_id'])) {
            $main_season_id = $content[0]['main_season_id'];
            $last_season = $db->raw("SELECT MAX(season) AS last_season FROM content WHERE main_season_id = $main_season_id;")->execute()[0]['last_season'] + 1;
        } else {
            $main_season_id = $content[0]['id'];
            $last_season = $db->raw("SELECT MAX(season) AS last_season FROM content WHERE main_season_id = $main_season_id;")->execute()[0]['last_season'];
            if (count($last_season) > 0 && !is_null(@$last_season[0]['last_season'])) {
                $last_season = $last_season[0]['last_season'] + 1;
            } else {
                $last_season = "2";
            }
        }
        $buttons[] = [
            ['text' => "🎥 افزودن بخش", 'callback_data' => 'add_season_' . $post_id . "_" . $last_season],
            ['text' => "🎬 افزودن قسمت", 'callback_data' => 'add_episode_' . $post_id . "_" . $last_episode]
        ];

        if (isset($content[0]['main_season_id'])) {
            $next_season = $db->raw("SELECT post_id FROM content WHERE season > $season AND main_season_id = $main_season_id LIMIT 1")->execute();
            if ((int)$season === 2) {
                $prev_season_post_id = $db->raw("SELECT post_id FROM content WHERE id = $main_season_id")->execute()[0]['post_id'];
            } else {
                $prev_season_post_id = $db->raw("SELECT post_id FROM content WHERE season < $season AND main_season_id = $main_season_id ORDER BY id DESC LIMIT 1")->execute()[0]['post_id'];
            }
            if (count($next_season) > 0) {
                $next_season_post_id = $next_season[0]['post_id'];
                $buttons[] = [
                    ['text' => "فصل بعدی ⬅️", 'callback_data' => 'view_content_' . $next_season_post_id],
                    ['text' => "➡️ فصل قبلی", 'callback_data' => 'view_content_' . $prev_season_post_id],
                ];
            } else {
                $buttons[] = [
                    ['text' => "➡️ فصل قبلی", 'callback_data' => 'view_content_' . $prev_season_post_id],
                ];
            }
        } else {
            $next_season = $db->raw("SELECT post_id FROM content WHERE season > $season AND main_season_id = $main_season_id LIMIT 1")->execute();
            if (count($next_season) > 0) {
                $next_season_post_id = $next_season[0]['post_id'];
                $buttons[] = [
                    ['text' => "فصل بعدی ⬅️", 'callback_data' => 'view_content_' . $next_season_post_id],
                ];
            }
        }

        if (count($episodes) > 0) {
            for ($i = 0; $i < count($episodes); $i += 2) {
                if (isset($episodes[$i + 1])) {
                    $index1 = $i + 1;
                    if ($index1 < 10) {$index1 = "0" . $index1;}
                    $index2 = $i + 2;
                    if ($index2 < 10) {$index2 = "0" . $index2;}
                    $buttons[] = [
                        ['text' => "🎬 قسمت $index2", 'callback_data' => 'view_episode_' . $post_id . '_' . $episodes[$i + 1]['episode']],
                        ['text' => "🎬 قسمت $index1", 'callback_data' => 'view_episode_' . $post_id . '_' . $episodes[$i]['episode']],
                    ];
                } else {
                    $index = $i + 1;
                    if ($index < 10) {$index = "0" . $index;}
                    $buttons[] = [
                        ['text' => "🎬 قسمت $index", 'callback_data' => 'view_episode_' . $post_id . '_' . $episodes[$i]['episode']]
                    ];
                }
            }
        }
        $buttons[] = [
            ['text' => "⤴️ بازگشت به لیست محتوا", 'callback_data' => 'list_content_return_' . $post_id]
        ];
    } else { // content child
        $parent_id = $content[0]['parent_id'];
        $parent_content = $db->table('content')->select()->where([['id', '=', $parent_id]])->execute();
        $parent_post_id = $parent_content[0]['post_id'];
        $episode = $content[0]['episode'];
        $quality = $content[0]['quality'];

        $season = $parent_content[0]['season'];
        if ($season < 10) {$season = "0" . $season;}
        $buttons[] = [
            ['text' => "🎥 بخش $season", 'callback_data' => 'none'], ['text' => '🧑‍💻 ناشر: "'.$title.'"', $callback_type => $callback_data]
        ];
        $buttons[] = [
            ['text' => "🌟 کیفیت $quality", "callback_data" => "none"], ['text' => "🎬 قسمت $episode", 'callback_data' => 'none']
        ];
        $buttons[] = [
            ['text' => "❌ حذف", 'callback_data' => 'delete_content_' . $post_id],
            ['text' => "♻️ جایگزینی", 'callback_data' => 'replace_content_part_' . $post_id]
        ];

        $next_post = $db->raw("SELECT episode FROM content WHERE episode > $episode AND parent_id = $parent_id AND quality = '$quality' LIMIT 1")->execute();
        $prev_post = $db->raw("SELECT episode FROM content WHERE episode < $episode AND parent_id = $parent_id AND quality = '$quality' ORDER BY id DESC LIMIT 1")->execute();
        if (count($prev_post) > 0 && count($next_post) > 0) {
            $buttons[] = [
                ['text' => "بعدی ⬅️", 'callback_data' => 'view_episode_' . $parent_post_id . '_' . $next_post[0]['episode'] . '_' . $quality],
                ['text' => "➡️ قبلی", 'callback_data' => 'view_episode_' . $parent_post_id . '_' . $prev_post[0]['episode'] . '_' . $quality],
            ];
            $buttons[] = [
                ['text' => "⤴️ بازگشت به محتوا", 'callback_data' => 'view_content_' . $parent_post_id]
            ];
        } else {
            if (count($prev_post) > 0) {
                $buttons[] = [
                    ['text' => "⤴️ بازگشت به محتوا", 'callback_data' => 'view_content_' . $parent_post_id],
                    ['text' => "➡️ قبلی", 'callback_data' => 'view_episode_' . $parent_post_id . '_' . $prev_post[0]['episode'] . '_' . $quality],
                ];
            } elseif (count($next_post) > 0) {
                $buttons[] = [
                    ['text' => "بعدی ⬅️", 'callback_data' => 'view_episode_' . $parent_post_id . '_' . $next_post[0]['episode'] . '_' . $quality],
                    ['text' => "⤴️ بازگشت به محتوا", 'callback_data' => 'view_content_' . $parent_post_id],
                ];
            } else {
                $buttons[] = [
                    ['text' => "⤴️ بازگشت به محتوا", 'callback_data' => 'view_content_' . $parent_post_id],
                ];
            }
        }
    }

    if ($new) {
        copyContent($post_id, null, ['inline_keyboard' => $buttons]);
    } else {
        editMsg(null, ['inline_keyboard' => $buttons], 'caption');
    }
}

function schedule_content_omit($sent_ids) {
    global $db, $chat_id;
    $currentDate = new DateTime();
    $currentDate->add(new DateInterval('PT30S'));
    $deleteDate = $currentDate->format('Y-m-d H:i:s');

    foreach ($sent_ids as $message_id => $content_id) {
        $db->table('delayed_message')->insert(["chat_id" => $chat_id, "message_id" => $message_id, 'content_id' => $content_id, "delete_time" => $deleteDate])->execute();
    }
}

function live_statistics($content_id, $user_tid) {
    global $db, $bot_username, $from_id;
    $users = $db->raw("SELECT tid FROM user WHERE live_statistics = '1'")->execute();

    foreach ($users as $user) {
        if ($from_id != $user['tid'] || !is_super_admin($from_id)) {
            if (is_numeric($content_id)) {
                $content = $db->table('content')->select()->where([['id', '=', $content_id]])->execute()[0];
                $post_id = $content['post_id'];
                $subject_title = $content['title'];
    
                if (isset($content['parent_id'])) {
                    $parent_id = $content['parent_id'];
                    $parent_content = $db->table('content')->select()->where([['id', '=', $parent_id]])->execute()[0];
                    $subject_title = $parent_content['title'];
                }
                $subject_link = "t.me/$bot_username?start=content_$post_id";
            } else {
                $subject_title = $content_id;
                $subject_link = "none";
            }

            [$title, $link, $username] = get_chat_info($user_tid, 'user');
            if (is_null($link)) {
                $callback_type = 'callback_data';
                $callback_data = 'none'; 
            } else {
                $callback_type = 'url';
                $callback_data = $link; 
            }

            $buttons = [
                [['text' => $title, $callback_type => $callback_data]],
                [['text' => $subject_title, 'url' => $subject_link]],
                [['text' => "🚫 لغو بازدید زنده", 'callback_data' => 'cancel_live_statistics']],
            ];
            sendMessage($user['tid'], "🛰 بازدید زنده", ['inline_keyboard' => $buttons], null, true, false);
        }
    }
}

function formatViews($views) {
    $abbreviations = array(12 => 'K', 9 => 'M', 6 => 'K');

    foreach ($abbreviations as $exponent => $abbreviation) {
        if ($views >= pow(10, $exponent)) {
            return number_format($views / pow(10, $exponent), 1) . $abbreviation;
        }
    }

    return $views;
}

function paginate($query, $limit = 12, $page = 1) {
    global $db;

    $offset = ($page - 1) * $limit;

    $total_records = $db->raw("SELECT COUNT(*) as total FROM ($query) as total")->execute()[0]['total'];

    $total_pages = ceil($total_records / $limit);
    $prev_page = max($page - 1, 1);
    $next_page = min($page + 1, $total_pages);

    $data = $db->raw($query . " LIMIT $limit OFFSET $offset")->execute();

    $pagination = [];
    if ($prev_page != $page) {
        $pagination['prev'] = $prev_page;
    }
    if ($next_page != $page) {
        $pagination['next'] = $next_page;
    }

    return [$data, $pagination];
}