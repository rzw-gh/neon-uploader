<?php
error_reporting(0);
set_time_limit(30);

require_once($_SERVER['DOCUMENT_ROOT'] . "/config/orm.php");

$hostname = "localhost";
$database = "database_name";
$username = "username";
$password = "password";

$db = new ORM($hostname, $username, $password, $database);

$config = $db->table("config")->select()->execute()[0];
$bot_token = $config['bot_token'];
$developer_tid = $config['developer_tid'];
$admin_users_tids = is_null($config['admin_users_tid']) ? $config['admin_users_tid'] : explode(",", $config['admin_users_tid']);
$customer_users_tid = is_null($config['customer_users_tid']) ? $config['customer_users_tid'] : explode(",", $config['customer_users_tid']);
$maintance = $config['maintance'];
$bot_username = $config['bot_username'];
$content_channel_id = $config['content_channel_id'];
$channel_ids = is_null($config['channel_id']) || empty($config['channel_id']) ? $config['channel_id'] : explode(",", $config['channel_id']);
$super_user_tids = is_null($config['super_user_tid']) ? $config['super_user_tid'] : explode(",", $config['super_user_tid']);
$now = date("Y-m-d H:i:s");