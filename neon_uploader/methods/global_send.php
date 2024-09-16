// <?php
// if (is_super_admin($from_id)) {
//     $buttons = [[['text' => "🏠 خانه", 'callback_data' => 'home']]];

//     if ($data == 'global_send') {
//         editMsg("لطفا با استفاده از دستور زیر اطلاعات را ارسال کنید \n`/start?action=global_send&post_id=1`", ['inline_keyboard' => $buttons]);
//     }

//     if (preg_match('/\/start\?action=global_send&post_id=(\d+)/', $text, $matches)) {
//         $post_id = $matches[1];
//         $users = $db->raw("SELECT id, tid, username FROM user WHERE id <= 500 ORDER BY id ASC")->execute();
//         $blocked_user = 0;
//         foreach ($users as $user) {
//             $result = bot('copyMessage', ['chat_id' => $user['tid'], 'from_chat_id' => $content_channel_id, 'message_id' => $post_id], false);
//             if (!isset($result)) {
//                 $db->table('user')->update(["blocked_by_user" => '1'])->where([['id', '=', $user['id']]])->execute();
//                 $blocked_user++;
//             }
//         }
//         if ($blocked_user === 0) {
//             sendMessage($chat_id, '✅ محتوای مورد نظر با موفقیت به همه ارسال شد', ['inline_keyboard' => $buttons], null, true, false);
//         } else {
//             sendMessage($chat_id, "✅ محتوای مورد نظر با موفقیت به همه ارسال شد و $blocked_user کاربر غیر فعال شناسایی شد", ['inline_keyboard' => $buttons], null, true, false);
//         }
//     }
// }