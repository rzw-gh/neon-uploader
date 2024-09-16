<?php
if (is_super_admin($from_id)) {
    [$title, $link, $username] = get_chat_info($developer_tid, 'user');
    if ($data == 'menu_setting') {
        editMsg(
            "⚙️ تنظیمات",
            ['inline_keyboard' => [
                [['text' => !$maintance ? "⚡ ربات روشن است" : "🛠️ ربات خاموش است", 'callback_data' => !$maintance ? 'power_off' : 'power_on']],
                [['text' => "🍿 کانال محتوا", 'callback_data' => 'content_channel'], ['text' => "🖥 کانال اصلی", 'callback_data' => 'main_channel']],
                [['text' => "🧑‍💻 ادمین ها", 'callback_data' => 'admin'],['text' => "🌟 پشتیبانی", 'url' => "https://t.me/$username"]],
                [['text' => "🏠 خانه", 'callback_data' => 'home']]
            ]]
        );
    } elseif (preg_match('/^power_(\w+)$/', $data, $matches)) {
        $type = $matches[1];
        if ($type == 'off') {
            $db->table('config')->update(['maintance'=>'1'])->where([['id', '=', '1']])->execute();
            $bot_msg_id = editMsg(
                "⚙️ تنظیمات",
                ['inline_keyboard' => [
                    [['text' => "🛠️ ربات خاموش است", 'callback_data' => 'power_on']],
                    [['text' => "🍿 کانال محتوا", 'callback_data' => 'content_channel'], ['text' => "🖥 کانال های اصلی", 'callback_data' => 'main_channel']],
                    [['text' => "💳 شماره کارت", 'callback_data' => 'card_number'], ['text' => "💭 چت گروپ", 'callback_data' => 'chat_group']],
                    [['text' => "🧑‍💻 ادمین ها", 'callback_data' => 'admin'], ['text' => "🤵‍♂️ مشتری ها", 'callback_data' => 'customer']],
                    [['text' => "🌟 پشتیبانی", 'url' => "https://t.me/$username"], ['text' => "🧭 راهنما", 'callback_data' => 'guide']],
                    [['text' => "🏠 خانه", 'callback_data' => 'home']]
                ]]
            );
        } else {
            $db->table('config')->update(['maintance'=>'0'])->where([['id', '=', '1']])->execute();
            $bot_msg_id = editMsg(
                "⚙️ تنظیمات",
                ['inline_keyboard' => [
                    [['text' => "⚡ ربات روشن است", 'callback_data' => 'power_off']],
                    [['text' => "🍿 کانال محتوا", 'callback_data' => 'content_channel'], ['text' => "🖥 کانال های اصلی", 'callback_data' => 'main_channel']],
                    [['text' => "💳 شماره کارت", 'callback_data' => 'card_number'], ['text' => "💭 چت گروپ", 'callback_data' => 'chat_group']],
                    [['text' => "🧑‍💻 ادمین ها", 'callback_data' => 'admin'], ['text' => "🤵‍♂️ مشتری ها", 'callback_data' => 'customer']],
                    [['text' => "🌟 پشتیبانی", 'url' => "https://t.me/$username"], ['text' => "🧭 راهنما", 'callback_data' => 'guide']],
                    [['text' => "🏠 خانه", 'callback_data' => 'home']]
                ]]
            );
        }
    } elseif (preg_match('/main_channel(?:_(\w+)(?:_(-?\d+))?)?$/', $data, $matches)) {
        $type = $matches[1];
        $channel_id = $matches[2];

        if (isset($type)) {
            if ($type == 'delete') {
                [$title, $link, $username] = get_chat_info($channel_id, 'channel');
                $buttons = [];
                $buttons[] = [
                    ['text' => "❌ لغو", 'callback_data' => "main_channel_cancel"],
                    ['text' => "✅ تایید حذف", 'callback_data' => "main_channel_confirm_$channel_id"]
                ];

                editMsg("حذف کانال " . '"' . $title . '"', ['inline_keyboard' => $buttons]);
            } elseif ($type == 'cancel') {
                $buttons = [];
                foreach ($channel_ids as $channel_id) {
                    [$title, $link, $username] = get_chat_info($channel_id, 'channel');
                    $buttons[] = [
                        ['text' => "❌ حذف", 'callback_data' => "main_channel_delete_$channel_id"],
                        ['text' => $title, 'url' => $link],
                    ];
                }
                $buttons[] = [['text' => "➕ افزودن", 'callback_data' => 'main_channel_add'], ['text' => "➥ برگشت", 'callback_data' => 'menu_setting']];
                editMsg("🖥 کانال های اصلی", ['inline_keyboard' => $buttons]);
            } elseif ($type == 'confirm') {
                $channel_ids = array_diff($channel_ids, [$channel_id]);
                $ids = count($channel_ids) > 0 ? implode(',', $channel_ids) : null;
                $config_id = $db->table('config')->select('id')->execute()[0]['id'];
                $db->table('config')->update(['channel_id'=>$ids])->where([['id', '=', $config_id]])->execute();
                if (!$db->getError()) {
                    editMsg('✅ تغییرات با موفقیت انجام شد', ['inline_keyboard' => [[['text' => "🏠 خانه", 'callback_data' => 'home'],['text' => "➥ برگشت", 'callback_data' => 'main_channel']]]]);
                } else {
                    editMsg('⚠️ خطایی رخ داده است لطفا دوباره تلاش کنید ', [[['text' => "🏠 خانه", 'callback_data' => 'home'],['text' => "➥ برگشت", 'callback_data' => 'main_channel']]]);
                }
            } elseif ($type == 'add') {
                $buttons = [];
                $buttons[] = [
                    ['text' => "🏠 خانه", 'callback_data' => 'home'],
                    ['text' => "➥ برگشت", 'callback_data' => 'main_channel'],
                ];
                editMsg("لطفا با استفاده از دستور زیر اطلاعات را ارسال کنید \n`action=add_main_channel&chat_id=-1002042095640`", ['inline_keyboard' => $buttons]);
            }
        } else {
            $buttons = [];
            foreach ($channel_ids as $channel_id) {
                [$title, $link, $username] = get_chat_info($channel_id, 'channel', 'channel');
                if (is_null($link)) {
                    $callback_type = 'callback_data';
                    $callback_data = 'none';
                } else {
                    $callback_type = 'url';
                    $callback_data = $link;
                }

                if (!isset($title)) {
                    $title = "کانال";
                }
                $buttons[] = [
                    ['text' => "❌ حذف", 'callback_data' => "main_channel_delete_$channel_id"],
                    ['text' => "$title", $callback_type => $callback_data],
                ];
            }
            $buttons[] = [['text' => "➕ افزودن", 'callback_data' => 'main_channel_add'], ['text' => "➥ برگشت", 'callback_data' => 'menu_setting']];
            editMsg("🖥 کانال های اصلی", ['inline_keyboard' => $buttons]);
        }
    } elseif (preg_match('/content_channel(?:_(\w+)(?:_(-?\d+))?)?$/', $data, $matches)) {
        $type = $matches[1];
        $channel_id = $matches[2];

        if (isset($type)) {
            if ($type == 'delete') {
                [$title, $link, $username] = get_chat_info($channel_id, 'channel');
                $buttons = [];
                $buttons[] = [
                    ['text' => "❌ لغو", 'callback_data' => "content_channel_cancel"],
                    ['text' => "✅ تایید حذف", 'callback_data' => "content_channel_confirm_$channel_id"]
                ];
                editMsg("حذف کانال " . '"' . $title . '"', ['inline_keyboard' => $buttons]);
            } elseif ($type == 'cancel') {
                [$title, $link, $username] = get_chat_info($content_channel_id, 'channel');
                $buttons = [];
                if (is_null($content_channel_id)) {
                    $buttons[] = [['text' => "➕ افزودن", 'callback_data' => 'content_channel_add'], ['text' => "➥ برگشت", 'callback_data' => 'menu_setting']];
                } else {
                    $buttons[] = [
                        ['text' => "❌ حذف", 'callback_data' => "content_channel_delete_$content_channel_id"],
                        ['text' => $title, 'url' => $link],
                    ];
                    $buttons[] = [['text' => "➥ برگشت", 'callback_data' => 'content_channel']];
                }
                editMsg("🍿 کانال محتوا", ['inline_keyboard' => $buttons]);
            } elseif ($type == 'confirm') {
                $config_id = $db->table('config')->select('id')->execute()[0]['id'];
                $db->table('config')->update(['content_channel_id'=>null])->where([['id', '=', $config_id]])->execute();
                if (!$db->getError()) {
                    editMsg('✅ تغییرات با موفقیت انجام شد', ['inline_keyboard' => [[['text' => "🏠 خانه", 'callback_data' => 'home'],['text' => "➥ برگشت", 'callback_data' => 'content_channel']]]]);
                } else {
                    editMsg('⚠️ خطایی رخ داده است لطفا دوباره تلاش کنید ', [[['text' => "🏠 خانه", 'callback_data' => 'home'],['text' => "➥ برگشت", 'callback_data' => 'content_channel']]]);
                }
            } elseif ($type == 'add') {
                $buttons = [];
                $buttons[] = [
                    ['text' => "🏠 خانه", 'callback_data' => 'home'],
                    ['text' => "➥ برگشت", 'callback_data' => 'content_channel'],
                ];
                editMsg("لطفا با استفاده از دستور زیر اطلاعات را ارسال کنید \n`action=add_content_channel&chat_id=-1002042095640`", ['inline_keyboard' => $buttons]);
            }
        } else {
            [$title, $link, $username] = get_chat_info($content_channel_id, 'channel');
            $buttons = [];
            if (is_null($content_channel_id)) {
                $buttons[] = [['text' => "➕ افزودن", 'callback_data' => 'content_channel_add'], ['text' => "➥ برگشت", 'callback_data' => 'menu_setting']];
            } else {
                $buttons[] = [
                    ['text' => "❌ حذف", 'callback_data' => "content_channel_delete_$content_channel_id"],
                    ['text' => $title, 'url' => $link],
                ];
                $buttons[] = [['text' => "➥ برگشت", 'callback_data' => 'menu_setting']];
            }
            editMsg("🍿 کانال محتوا", ['inline_keyboard' => $buttons]);
        }
    } elseif (preg_match('/admin(?:_(\w+)(?:_(-?\d+))?)?$/', $data, $matches)) {
        $type = $matches[1];
        $admin_tid = substr($matches[2], 1);

        if (isset($type)) {
            if ($type == 'delete') {
                $buttons = [];
                $buttons[] = [
                    ['text' => "❌ لغو", 'callback_data' => "admin_cancel"],
                    ['text' => "✅ تایید حذف", 'callback_data' => "admin_confirm_-$admin_tid"]
                ];
                [$title, $link, $username] = get_chat_info($admin_tid, 'user');
                editMsg("حذف ادمین " . '"' . $title . '"', ['inline_keyboard' => $buttons]);
            } elseif ($type == 'cancel') {
                list_admins();
            } elseif ($type == 'confirm') {
                $admin_users_tids = array_diff($admin_users_tids, [$admin_tid]);
                $ids = count($admin_users_tids) > 0 ? implode(',', $admin_users_tids) : null;
                $config_id = $db->table('config')->select('id')->execute()[0]['id'];
                $db->table('config')->update(['admin_users_tid'=>$ids])->where([['id', '=', $config_id]])->execute();
                if (!$db->getError()) {
                    editMsg('✅ تغییرات با موفقیت انجام شد', ['inline_keyboard' => [[['text' => "🏠 خانه", 'callback_data' => 'home'],['text' => "➥ برگشت", 'callback_data' => 'admin']]]]);
                } else {
                    editMsg('⚠️ خطایی رخ داده است لطفا دوباره تلاش کنید ', [[['text' => "🏠 خانه", 'callback_data' => 'home'],['text' => "➥ برگشت", 'callback_data' => 'admin']]]);
                }
            } elseif ($type == 'add') {
                $buttons = [];
                $buttons[] = [
                    ['text' => "🏠 خانه", 'callback_data' => 'home'],
                    ['text' => "➥ برگشت", 'callback_data' => 'admin'],
                ];
                editMsg("لطفا با استفاده از دستور زیر اطلاعات را ارسال کنید \n`action=add_admin&chat_id=836683995`", ['inline_keyboard' => $buttons]);
            } elseif ($type == 'activate') {
                $db->table('user')->update(['active'=>'1'])->where([['tid', '=', $admin_tid]])->execute();
                if (!$db->getError()) {
                    $ch = curl_init("https://api.telegram.org/bot$bot_token/answerCallbackQuery");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, ['callback_query_id' => $query_id, 'text' => "✅ ادمین فعال شد"]);
                    curl_exec($ch);
                    curl_close($ch);
                }
                list_admins();
            } elseif ($type == 'deactivate') {
                $db->table('user')->update(['active'=>'0'])->where([['tid', '=', $admin_tid]])->execute();
                if (!$db->getError()) {
                    $ch = curl_init("https://api.telegram.org/bot$bot_token/answerCallbackQuery");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, ['callback_query_id' => $query_id, 'text' => "❌ ادمین غیر فعال شد"]);
                    curl_exec($ch);
                    curl_close($ch);
                }
                list_admins();
            } elseif ($type == 'type') {
                $user = $db->table('user')->select('content_edit_type')->where([['tid', '=', $admin_tid]])->execute()[0];
                if ($user['content_edit_type'] == 'no') {
                    $content_edit_type = 'yes';
                    $callback_msg = '✅ اجازه ویرایش دارد';
                } elseif ($user['content_edit_type'] == 'yes') {
                    $content_edit_type = 'own';
                    $callback_msg = '✅ اجازه ویرایش فقط محتوای خود';
                } elseif ($user['content_edit_type'] == 'own') {
                    $content_edit_type = 'no';
                    $callback_msg = '❌ اجازه ویرایش ندارد';
                }
                $db->table('user')->update(['content_edit_type'=>$content_edit_type])->where([['tid', '=', $admin_tid]])->execute();
                if (!$db->getError()) {
                    $ch = curl_init("https://api.telegram.org/bot$bot_token/answerCallbackQuery");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, ['callback_query_id' => $query_id, 'text' => $callback_msg]);
                    curl_exec($ch);
                    curl_close($ch);
                }
                list_admins();
            }
        } else {
            list_admins();
        }
    } elseif (preg_match('/action=(\w+)&chat_id=(-?\d+)$/', $text, $matches)) {
        $type = $matches[1];
        $id = $matches[2];

        if ($type == 'add_main_channel') {
            if (in_array($id, $channel_ids)) {
                sendMessage($from_id, '⚠️ شناسه تکراری میباشد', ['inline_keyboard' => [[['text' => "🏠 خانه", 'callback_data' => 'home'],['text' => "➥ برگشت", 'callback_data' => 'main_channel']]]]);
                exit;
            }
            $channel_ids[] = (string)$id;
            $ids = implode(',', $channel_ids);
            $config_id = $db->table('config')->select('id')->execute()[0]['id'];
            $db->table('config')->update(['channel_id'=>$ids])->where([['id', '=', $config_id]])->execute();
            if (!$db->getError()) {
                sendMessage($from_id, '✅ کانال اصلی با موفقیت اضافه شد', ['inline_keyboard' => [[['text' => "🏠 خانه", 'callback_data' => 'home'],['text' => "➥ برگشت", 'callback_data' => 'main_channel']]]]);
            } else {
                sendMessage($from_id, '⚠️ خطایی رخ داده است لطفا دوباره تلاش کنید ', [[['text' => "🏠 خانه", 'callback_data' => 'home'],['text' => "➥ برگشت", 'callback_data' => 'main_channel']]]);
            }
        } elseif ($type == 'add_content_channel') {
            $config = $db->table('config')->select('id')->execute()[0];
            if ($id == $config['content_channel_id']) {
                sendMessage($from_id, '⚠️ شناسه تکراری میباشد', ['inline_keyboard' => [[['text' => "🏠 خانه", 'callback_data' => 'home'],['text' => "➥ برگشت", 'callback_data' => 'content_channel']]]]);
                exit;
            }
            $db->table('config')->update(['content_channel_id'=>$id])->where([['id', '=', $config['id']]])->execute();
            if (!$db->getError()) {
                sendMessage($from_id, '✅ کانال محتوا با موفقیت اضافه شد', ['inline_keyboard' => [[['text' => "🏠 خانه", 'callback_data' => 'home'],['text' => "➥ برگشت", 'callback_data' => 'content_channel']]]]);
            } else {
                sendMessage($from_id, '⚠️ خطایی رخ داده است لطفا دوباره تلاش کنید ', [[['text' => "🏠 خانه", 'callback_data' => 'home'],['text' => "➥ برگشت", 'callback_data' => 'content_channel']]]);
            }
        } elseif ($type == 'add_admin') {
            if (in_array($id, $admin_users_tids)) {
                sendMessage($from_id, '⚠️ شناسه تکراری میباشد', ['inline_keyboard' => [[['text' => "🏠 خانه", 'callback_data' => 'home'],['text' => "➥ برگشت", 'callback_data' => 'admin']]]]);
                exit;
            }
            $admin_users_tids[] = (string)$id;
            $ids = implode(',', $admin_users_tids);
            $config_id = $db->table('config')->select('id')->execute()[0]['id'];
            $db->table('config')->update(['admin_users_tid'=>$ids])->where([['id', '=', $config_id]])->execute();
            if (!$db->getError()) {
                sendMessage($from_id, '✅ ادمین با موفقیت اضافه شد', ['inline_keyboard' => [[['text' => "🏠 خانه", 'callback_data' => 'home'],['text' => "➥ برگشت", 'callback_data' => 'admin']]]]);
            } else {
                sendMessage($from_id, '⚠️ خطایی رخ داده است لطفا دوباره تلاش کنید ', [[['text' => "🏠 خانه", 'callback_data' => 'home'],['text' => "➥ برگشت", 'callback_data' => 'admin']]]);
            }
        }
    }
}

function list_admins() {
    global $db, $admin_users_tids;
    $buttons = [];
    
    if (count($admin_users_tids) > 0) {
        $buttons[] = [
            ['text' => "عملیات", 'callback_data' => "none"],
            ['text' => "وضعیت", 'callback_data' => "none"],
            ['text' => "دسترسی ویرایش", 'callback_data' => "none"],
            ['text' => "نام", 'callback_data' => "none"],
        ];
    }

    foreach ($admin_users_tids as $admin_tid) {
        [$title, $link, $username] = get_chat_info($admin_tid, 'user');
        if (is_null($link)) {
            $callback_type = 'callback_data';
            $callback_data = 'none';
        } else {
            $callback_type = 'url';
            $callback_data = $link;
        }
        $user = $db->table('user')->select('active', 'content_edit_type')->where([['tid', '=', $admin_tid]])->execute()[0];
        $active = $user['active'];

        if ($user['content_edit_type'] == 'no') {
            $edit_type = '❌ ندارد';
        } elseif ($user['content_edit_type'] == 'yes') {
            $edit_type = '✅ دارد';
        } elseif ($user['content_edit_type'] == 'own') {
            $edit_type = '✅ محتوای خود';
        }

        $buttons[] = [
            ['text' => "❌ حذف", 'callback_data' => "admin_delete_-$admin_tid"],
            ['text' => $active ? '✅ فعال' : '❌ غیر فعال', 'callback_data' => $active ? "admin_deactivate_-$admin_tid" : "admin_activate_-$admin_tid"],
            ['text' => $edit_type, 'callback_data' => "admin_type_-$admin_tid"],
            ['text' => $title, $callback_type => $callback_data],
        ];
    }
    $buttons[] = [['text' => "➕ افزودن", 'callback_data' => 'admin_add'], ['text' => "➥ برگشت", 'callback_data' => 'menu_setting']];
    editMsg("🧑‍💻 ادمین ها", ['inline_keyboard' => $buttons]);
}