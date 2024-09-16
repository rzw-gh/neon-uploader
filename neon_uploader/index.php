<?php

$input = json_decode(file_get_contents("php://input"));
$bot_msg_id = null;

if (isset($input->message)) {
    $input_type = 'message';
    $message = $input->message;
    $from_id    = $input->message->from->id;
    $from_username    = isset($input->message->from->username) ? $input->message->from->username : null;
    $chat_id    = $input->message->chat->id;
    $chat_type  = $input->message->chat->type;
    $text       = isset($input->message->text) ? ltrim($input->message->text) : null;
    $first_name = isset($input->message->from->first_name) ? $input->message->from->first_name : null;
    $last_name = isset($input->message->from->last_name) ? $input->message->from->last_name : null;
    $full_name = "$first_name $last_name";
    $message_id = $input->message->message_id;
    $forwardedChatId = isset($message->forward_from_chat->id) ? $message->forward_from_chat->id : null;
    $caption = isset($input->message->caption) ? $input->message->caption : null;

    if (isset($input->message->photo)) {
        $photo_id = end($input->message->photo)->file_id;
    }

    if (isset($input->message->video)) {
        $video_id = $input->message->video->file_id;
    }

    if (isset($input->message->document)) {
        $document_id = $input->message->document->file_id;
    }

    if (isset($input->message->contact)) {
        $contact = $input->message->contact;
        $phone_number = $input->message->contact->phone_number;
    }
} elseif (isset($input->callback_query)) {
    $input_type = 'callbackquery';
    $from_id    = $input->callback_query->from->id;
    $from_username    = isset($input->callback_query->from->username) ? $input->callback_query->from->username : null;
    $first_name    = isset($input->callback_query->from->first_name) ? $input->callback_query->from->first_name : null;
    $last_name    = isset($input->callback_query->from->last_name) ? $input->callback_query->from->last_name : null;
    $full_name = "$first_name $last_name";
    $chat_id    = $input->callback_query->message->chat->id;
    $data       = ltrim($input->callback_query->data);
    $query_id   = $input->callback_query->id;
    $message_id = $input->callback_query->message->message_id;
    $in_text    = isset($input->callback_query->message->text) ? $input->callback_query->message->text : null;
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/methods/global.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/methods/content.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/methods/statistics.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/methods/global_send.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/methods/assistant.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/methods/setting.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/methods/ad.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/methods/news.php");