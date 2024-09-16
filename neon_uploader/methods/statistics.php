<?php
if (is_super_admin($from_id)) {
    if ($data == 'menu_statistics') {
        list_statistics_menu();
    } elseif ($data == 'live_statistics') {
        $user = $db->table('user')->update(["live_statistics" => '1'])->where([['tid', '=', $from_id]])->execute()[0];
        $ch = curl_init("https://api.telegram.org/bot$bot_token/answerCallbackQuery");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['callback_query_id' => $query_id, 'text' => "✅ بازدید زنده با موفقیت فعال شد"]);
        curl_exec($ch);
        curl_close($ch);
        list_statistics_menu();
    } elseif ($data == 'cancel_live_statistics') {
        $user = $db->table('user')->update(["live_statistics" => '0'])->where([['tid', '=', $from_id]])->execute()[0];
        $ch = curl_init("https://api.telegram.org/bot$bot_token/answerCallbackQuery");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['callback_query_id' => $query_id, 'text' => "✅ بازدید زنده با موفقیت غیر فعال شد"]);
        curl_exec($ch);
        curl_close($ch);
        list_statistics_menu();
    } elseif ($data == 'users_statistics') {
        $user_count = $db->raw("SELECT COUNT(id) AS user_count FROM user")->execute()[0]['user_count'];
        
        $currentDate = new DateTime();

        $firstDay = clone $currentDate;
        $firstDay->modify('this week')->setTime(0, 0, 0);
        $lastDay = clone $firstDay;
        $lastDay->modify('this week +6 days')->setTime(23, 59, 59);
        $weekStartDate = $firstDay->format('Y-m-d H:i:s');
        $weekEndDate = $lastDay->format('Y-m-d H:i:s');

        $firstDay = clone $currentDate;
        $firstDay->modify('first day of this month')->setTime(0, 0, 0);
        $lastDay = clone $currentDate;
        $lastDay->modify('last day of this month')->setTime(23, 59, 59);
        $monthStartDate = $firstDay->format('Y-m-d H:i:s');
        $monthEndDate = $lastDay->format('Y-m-d H:i:s');

        $todayStart = clone $currentDate;
        $todayStart = $todayStart->setTime(0, 0, 0);
        $todayEnd = clone $currentDate;
        $todayEnd = $todayEnd->setTime(23, 59, 59);
        $todayStartDate = $todayStart->format('Y-m-d H:i:s');
        $todayEndDate = $todayEnd->format('Y-m-d H:i:s');

        $month_new_users = (string)$db->raw("SELECT COUNT(id) AS user_count FROM user WHERE joined_at >= '$monthStartDate'")->execute()[0]['user_count'];
        $month_active_users = (string)$db->raw("SELECT COUNT(id) AS user_count FROM user WHERE last_interaction >= '$monthStartDate'")->execute()[0]['user_count'];
        $week_new_users = (string)$db->raw("SELECT COUNT(id) AS user_count FROM user WHERE joined_at >= '$weekStartDate' AND joined_at <= '$weekEndDate'")->execute()[0]['user_count'];
        $week_active_users = (string)$db->raw("SELECT COUNT(id) AS user_count FROM user WHERE last_interaction >= '$weekStartDate' AND last_interaction <= '$weekEndDate'")->execute()[0]['user_count'];
        $today_new_users = (string)$db->raw("SELECT COUNT(id) AS user_count FROM user WHERE joined_at >= '$todayStartDate' AND joined_at <= '$todayEndDate'")->execute()[0]['user_count'];
        $today_active_users = (string)$db->raw("SELECT COUNT(id) AS user_count FROM user WHERE last_interaction >= '$todayStartDate' AND last_interaction <= '$todayEndDate'")->execute()[0]['user_count'];

        $buttons = [
            [['text' => "👥 کاربران: $user_count", 'callback_data' => 'none']],
            [['text' => "🌱 تازه ماه: $month_new_users", 'callback_data' => 'none'], ['text' => "🚀 فعال ماه: $month_active_users", 'callback_data' => 'none']],
            [['text' => "🌱 تازه هفته: $week_new_users", 'callback_data' => 'none'], ['text' => "🚀 فعال هفته: $week_active_users", 'callback_data' => 'none']],
            [['text' => "🌱 تازه امروز: $today_new_users", 'callback_data' => 'none'], ['text' => "🚀 فعال امروز: $today_active_users", 'callback_data' => 'none']],
        ];

        $buttons[] = [
            ['text' => "🏠 خانه", 'callback_data' => 'home'],
            ['text' => "➥ برگشت", 'callback_data' => 'menu_statistics'],
        ];

        editMsg(
            "👾 آمار ربات",
            ['inline_keyboard' => $buttons]
        );
    } elseif ($data == 'content_statistics') {
        $content_view = $db->raw("SELECT sum(view) AS content_count FROM `content` WHERE parent_id IS NULL")->execute()[0]['content_count'];
        $buttons = [
            [['text' => "🔭 بازدید: $content_view", 'callback_data' => 'none']],
        ];
        
        $top_content = $db->raw("SELECT * FROM content WHERE parent_id IS NULL ORDER BY view DESC LIMIT 5")->execute();
        foreach ($top_content as $index => $item) {
            switch ($index) {
                case 0:
                    $medal = ' 🏆'; break;
                case 1:
                    $medal = ' 🏆'; break;
                case 2:
                    $medal = ' 🏆'; break;
                case 3:
                    $medal = ' 🏅'; break;
                case 4:
                    $medal = ' 🏅'; break;
            }
            $post_id = $item['post_id'];
            $buttons[] = [
                ['text' => $item['view'] . $medal, 'callback_data' => 'view_content_' . $post_id],
                ['text' => $item['title'], 'callback_data' => 'view_content_' . $post_id]
            ];
        }

        $buttons[] = [
            ['text' => "🏠 خانه", 'callback_data' => 'home'],
            ['text' => "➥ برگشت", 'callback_data' => 'menu_statistics'],
        ];

        editMsg(
            "🍿 آمار محتوا",
            ['inline_keyboard' => $buttons]
        );
    } elseif ($data == 'reaction_statistics') {
        $reaction_count = $db->raw("SELECT COUNT(id) AS reaction_count FROM user_content_reaction")->execute()[0]['reaction_count'];
        if ($reaction_count < 5) {
            show_alert('⚠️ تعداد ری اکشن کمتر از 5 عدد میباشد', true);
        }

        $buttons[] = [
            ['text' => "👍 ری اکشن: $reaction_count", 'callback_data' => 'none'],
        ];

        $top_reactions = $db->raw("SELECT
            c.id,
            c.parent_id,
            c.post_id,
            c.title,
            cr.content_id,
            COUNT(cr.id) AS reaction_count
        FROM
            content c
        LEFT JOIN
            user_content_reaction cr ON c.id = cr.content_id
        GROUP BY
            c.id
        ORDER BY
            reaction_count DESC LIMIT 5;")->execute();

        foreach ($top_reactions as $index => $reaction) {
            if (isset($reaction['parent_id'])) {
                $parent = $db->table('content')->select()->where([['id', '=', $reaction['parent_id']]])->execute()[0];
                $title = $parent['title'];
                $post_id = $parent['post_id'];
            } else {
                $title = $reaction['title'];
                $post_id = $reaction['post_id'];
            }
            switch ($index) {
                case 0:
                    $medal = ' 🏆'; break;
                case 1:
                    $medal = ' 🏆'; break;
                case 2:
                    $medal = ' 🏆'; break;
                case 3:
                    $medal = ' 🏅'; break;
                case 4:
                    $medal = ' 🏅'; break;
            }
            $buttons[] = [
                ['text' => $reaction['reaction_count'] . $medal, 'callback_data' => 'view_content_' . $post_id],
                ['text' => $title, 'callback_data' => 'view_content_' . $post_id]
            ];
        }

        $buttons[] = [
            ['text' => "🏠 خانه", 'callback_data' => 'home'],
            ['text' => "➥ برگشت", 'callback_data' => 'menu_statistics'],
        ];

        editMsg(
            "👍 آمار ری اکشن",
            ['inline_keyboard' => $buttons]
        );
    } elseif ($data == 'favorite_statistics') {
        $favorite_count = $db->raw("SELECT COUNT(id) AS favorite_count FROM favorite")->execute()[0]['favorite_count'];
        $buttons[] = [
            ['text' => "♥️ علاقه مندی: $favorite_count", 'callback_data' => 'none'],
        ];

        $top_favorite = $db->raw("SELECT
            c.id,
            c.post_id,
            c.title,
            f.content_id,
            COUNT(f.id) AS favorite_count
        FROM
            content c
        LEFT JOIN
            favorite f ON c.id = f.content_id
        WHERE
            parent_id IS NULL
        GROUP BY
            c.id
        ORDER BY
            favorite_count DESC LIMIT 5;")->execute();

        foreach ($top_favorite as $index => $favorite) {
            switch ($index) {
                case 0:
                    $medal = ' 🏆'; break;
                case 1:
                    $medal = ' 🏆'; break;
                case 2:
                    $medal = ' 🏆'; break;
                case 3:
                    $medal = ' 🏅'; break;
                case 4:
                    $medal = ' 🏅'; break;
            }
            $post_id = $favorite['post_id'];
            $buttons[] = [
                ['text' => $favorite['favorite_count'] . $medal, 'callback_data' => 'view_content_' . $post_id],
                ['text' => $favorite['title'], 'callback_data' => 'view_content_' . $post_id]
            ];
        }

        $buttons[] = [
            ['text' => "🏠 خانه", 'callback_data' => 'home'],
            ['text' => "➥ برگشت", 'callback_data' => 'menu_statistics'],
        ];

        editMsg(
            "♥️ آمار علاقه مندی",
            ['inline_keyboard' => $buttons]
        );
    } elseif (preg_match('/admin_statistics(?:_(\d+))?$/', $data, $matches)) {
        $admin_tid = $matches[1];

        if (isset($admin_tid)) {
            [$title, $link, $username] = get_chat_info($admin_tid, 'user');
            $content = $db->raw("SELECT post_id, view, title FROM content WHERE parent_id IS NULL AND author = $admin_tid ORDER BY view DESC")->execute();
            $content_view = 0;
            foreach ($content as $item) {
                $content_view += $item['view'];
            }
            $content_count = count($content);

            $buttons = [
                [['text' => $title, 'url' => $link]],
                [['text' => "🔭 بازدید: $content_view", 'callback_data' => 'none'], ['text' => "🍿 تعداد محتوا: $content_count", 'callback_data' => 'none']],
            ];

            foreach ($content as $index => $item) {
                switch ($index) {
                    case 0:
                        $medal = ' 🥇'; break;
                    case 1:
                        $medal = ' 🥈'; break;
                    case 2:
                        $medal = ' 🥉'; break;
                }
                $post_id = $item['post_id'];
                $buttons[] = [
                    ['text' => $item['view'] . $medal, 'callback_data' => 'view_content_' . $post_id],
                    ['text' => $item['title'], 'callback_data' => 'view_content_' . $post_id]
                ];
                
                if ($index == 2) {break;}
            }

            $buttons[] = [
                ['text' => "🏠 خانه", 'callback_data' => 'home'],
                ['text' => "➥ برگشت", 'callback_data' => 'admin_statistics'],
            ];
        } else {
            if (is_null($admin_users_tids)) {
                $admin_users_tids = [];
            }
            if (is_null($super_user_tids)) {
                $super_user_tids = [];
            }
            $admins = array_merge($admin_users_tids, $super_user_tids);
            
            $buttons[] = [
                ['text' => "نام", 'callback_data' => "none"],
                ['text' => "عنوان", 'callback_data' => "none"]
            ];

            foreach ($admins as $admin_id) {
                [$title, $link, $username] = get_chat_info($admin_id, 'user');

                $role = '🧑‍💻 ادمین';
                if (is_super_admin($admin_id)) {
                    $role = '🌟 سوپر ادمین';
                }
                $buttons[] = [
                    ['text' => $title, 'callback_data' => "admin_statistics_".$admin_id],
                    ['text' => $role, 'callback_data' => "admin_statistics_".$admin_id]
                ];
            }

            $buttons[] = [
                ['text' => "🏠 خانه", 'callback_data' => 'home'],
                ['text' => "➥ برگشت", 'callback_data' => 'menu_statistics'],
            ];
        }

        editMsg(
            "🧑‍💻 آمار ادمین ها",
            ['inline_keyboard' => $buttons]
        );
    }
}

function list_statistics_menu() {
    global $from_id, $db;
    $user = $db->table('user')->select()->where([['tid', '=', $from_id]])->execute()[0];
    if ($user['live_statistics']) {
        $live_statistics_text = "🚫 لغو بازدید زنده";
        $live_statistics_data = 'cancel_live_statistics';
    } else {
        $live_statistics_text = "🛰 بازدید زنده";
        $live_statistics_data = 'live_statistics';
    }

    editMsg(
        "📊 آمار",
        ['inline_keyboard' => [
            [['text' => $live_statistics_text, 'callback_data' => $live_statistics_data]],
            [['text' => "🧑‍💻 ادمین ها", 'callback_data' => 'admin_statistics']],
            [['text' => "👥 کاربران", 'callback_data' => 'users_statistics'], ['text' => "🍿 محتوا", 'callback_data' => 'content_statistics']],
            [['text' => "👍 ری اکشن", 'callback_data' => 'reaction_statistics'], ['text' => "♥️ علاقه مندی", 'callback_data' => 'favorite_statistics']],
            [['text' => "🏠 خانه", 'callback_data' => 'home']],
        ]]
    );
}