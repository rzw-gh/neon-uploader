# Neon Uploader

This is a telegram media & file manager bot for channels built with pure PHP.

## Prerequisites

- a php configured server with cpanel and phpmyadmin
- a domain

# Installation

1. link your domain to the server
2. head over to telegram `t.me/BotFather` and create your own bot. save bot token which botfather will give you
3. extract `neon_uploader` folder to your server's public_html folder
4. head over to this link once `https://api.telegram.org/bot{bot_token}/setWebhook?url={yourdomain.com/index.php}` which `{bot_token}` is the token you got from botfather. this will set telegram bot api weebhooks to index.php file inside public_html folder

# Database Configuration

1. open phpmyadmin from cpanel
2. import neonuploader.sql to create all database tables
3. find config table and insert a row with these informations:
```bash
your telegram account id for `developer_tid` and `super_user_tid` columns # you can find your telegram id with the help of t.me/userinfobot
bot token from botfather for `bot_token` column
your telegram bot username for `bot_username` column
your main channel id for `channel_id` column # you can find your channel id with the help of t.me/username_to_id_bot # make sure your bot is full admin of this channel
your private file&media archive channel id for `content_channel_id` column # you can find your channel id with the help of t.me/username_to_id_bot # make sure your bot is full admin of this channel
```
4. from `cpanel / Manage My Databases` create a root user with all privileges and link it to your database `neonuploader`

# Code Configuration
1. head over to `core/config.php` and change these variables on your neeeds:
```bash
$hostname = "localhost";
$database = "database_name";
$username = "username";
$password = "password";
```
2. head over to `core/cron.php` and change these variables on your neeeds:
```bash
$hostname = "localhost";
$database = "database_name";
$username = "username";
$password = "password";
$bot_token = "bot_token";
```
3. head over to `cpanel / cron jobs` and add new cron job with these settings:
```bash
1. put this code inside command input and replace yourdomain.com with yours: php -q /home/itsrezai/public_html/yourdomain.com/config/cron.php
2. set * for Minute, Hour, Day, Month and Weekday inputs
```

if you've done all steps without errors your bot should be ready to use. head over to your bot on telgram and send `/start/` command. NOTICE your bot should be member and full admin of your channels to work.
