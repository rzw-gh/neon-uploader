<?php
$hostname = "localhost";
$database = "database_name";
$username = "username";
$password = "password";
$bot_token = "bot_token";

$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$now = date("Y-m-d H:i:s");
$sql = "SELECT * FROM delayed_message WHERE delete_time <= '$now'";
$result = mysqli_query($conn, $sql);

if ($result) {
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    $success = true;
    foreach ($rows as $message) {
        $url = "https://api.telegram.org/bot" . $bot_token . "/deleteMessage";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'chat_id' => $message['chat_id'],
            'message_id' => $message['message_id'],
        ]);
        $response = curl_exec($ch);

        if ($response) {
            $responseData = json_decode($response, true);
            if ($responseData && $responseData['ok']) {
            } else {
                $success = false;
                if ($responseData['description'] == 'Bad Request: message to delete not found') {
                    $message_id = $message['id'];
                    $user_tid = $message['chat_id'];
                    $sql = "DELETE FROM delayed_message WHERE id = '$message_id'";
                    mysqli_query($conn, $sql);

                    $sql = "UPDATE user SET blocked_by_user = '1' WHERE tid = '$user_tid'";
                    mysqli_query($conn, $sql);
                }
            }
        } else {
            $success = false;
        }
    }

    mysqli_free_result($result);

    if ($success) {
        $sql = "DELETE FROM delayed_message WHERE delete_time <= '$now'";
        mysqli_query($conn, $sql);
    }
    mysqli_close($conn);
}