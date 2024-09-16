<?php
$year = (int)date('Y');
$month = (int)date('n');
$day = (int)date('d');
switch ($month) {
    case 1:
    case 2:
    case 12:
        $season = 'winter';
        $prvseason = 'پاییز';
        $prvseason_en = "fall";
        $prvseason_emoji = '🍁';
        $year -= 1;
        break;
    case 3:
    case 4:
    case 5:
        $season = 'spring';
        $prvseason = 'زمستان';
        $prvseason_en = "winter";
        $prvseason_emoji = '❄️';
        break;
    case 6:
    case 7:
    case 8:
        $season = 'summer';
        $prvseason = 'بهار';
        $prvseason_en = "spring";
        $prvseason_emoji = '🌱';
        break;
    case 9:
    case 10:
    case 11:
        $season = 'fall';
        $prvseason = 'تابستان';
        $prvseason_en = "summer";
        $prvseason_emoji = '🏖';
        break;
}

$season_title = "$prvseason_emoji انیمه برتر $prvseason";
$season_url = "https://animecorner.me/". $prvseason_en ."-". $year ."-anime-of-the-season-rankings";
$week_title = "🗓 انیمه برتر هفته";
$week_url = "https://animecorner.me/category/rankings/";

if ($data == 'menu_news') {
    $buttons = [
        [['text' => "My Anime List 🔵", 'callback_data' => 'news_mal'], ['text' => "Crunchyroll 🟠", 'callback_data' => 'news_crunchyroll']],
        [['text' => "Animation 🟣", 'callback_data' => 'news_awn'], ['text' => "Movie 🔴", 'callback_data' => 'news_slashfilm']],
        [['text' => $season_title, 'url' => $season_url], ['text' => $week_title, 'url' => $week_url]],
        [['text' => "🎂 تولد امروز", 'callback_data' => 'today_birthday']]
    ];
    $buttons = add_return_home($buttons);

    editMsg("🗞️ اخبار", ['inline_keyboard' => $buttons]);
} elseif ($data == 'menu_news_new') {
    $buttons = [
        [['text' => "My Anime List 🔵", 'callback_data' => 'news_mal'], ['text' => "Crunchyroll 🟠", 'callback_data' => 'news_crunchyroll']],
        [['text' => "Animation 🟣", 'callback_data' => 'news_awn'], ['text' => "Movie 🔴", 'callback_data' => 'news_slashfilm']],
        [['text' => $season_title, 'url' => $season_url], ['text' => $week_title, 'url' => $week_url]],
        [['text' => "🎂 تولد امروز", 'callback_data' => 'today_birthday']]
    ];
    $buttons = add_return_home($buttons);

    sendMessage($chat_id, "🗞️ اخبار", ['inline_keyboard' => $buttons]);
} elseif ($data == 'news_mal') {
    $rss_feed_url = 'https://myanimelist.net/rss/news.xml';
    $xml = @simplexml_load_file($rss_feed_url);

    if ($xml === false) {
        show_alert("⚠️ خطایی رخ داد لطفا دوباره تلاش کنید", true);
    } else {
        sendAction($chat_id, "typing");
        live_statistics("اخبار MAL", $from_id);
    }

    $namespaces = $xml->getNamespaces(true);
    $media_ns = $namespaces['media'];
    $count = count($xml->channel->item);

    $index = 0;
    foreach ($xml->channel->item as $item) {
        $title = html_entity_decode((string)$item->title);
        $description = html_entity_decode((string)$item->description);
        $pubDate = (string)$item->pubDate;
        $link = (string)$item->link;

        if(mb_strlen($description) > 750) {
            $truncatedCaption = mb_substr($description, 0, 730);
            $lastSpacePos = mb_strrpos($truncatedCaption, ' ');
            $truncatedCaption = mb_substr($truncatedCaption, 0, $lastSpacePos);
            $caption = $truncatedCaption . '...';
        } else {
            $caption = $description;
        }
        $caption = "📌 $title\n\n💬 $caption\n\n🔗 $link\n\n⏰ $pubDate\n\n#news";

        if ($index + 1 == $count) {
            $buttons = add_return_home([], "menu_news_new", true);
            $buttons = ['inline_keyboard' => $buttons];
        } elseif (!check_if_today($pubDate)) {
            $buttons = add_return_home([], "menu_news_new", true);
            $buttons = ['inline_keyboard' => $buttons];
        } else {
            $buttons = null;
        }


        sendAction($chat_id, "typing");
        if (isset($item->children($media_ns)->thumbnail)) {
            $thumbnail = (string)$item->children($media_ns)->thumbnail;
            sendDocument("photo", $chat_id, $thumbnail, $caption, $buttons);
        } else {
            sendMessage($chat_id, $caption, $buttons);
        }
        $index++;

        if (!check_if_today($pubDate)) {
            die();
        }
    }
} elseif ($data == 'news_crunchyroll') {
    $rss_feed_url = 'https://cr-news-api-service.prd.crunchyrollsvc.com/v1/en-US/rss';
    $xml = @simplexml_load_file($rss_feed_url);

    if ($xml === false) {
        show_alert("⚠️ خطایی رخ داد لطفا دوباره تلاش کنید", true);
    } else {
        sendAction($chat_id, "typing");
        live_statistics("اخبار کرانچی رول", $from_id);
    }

    $namespaces = $xml->getNamespaces(true);
    $media_ns = $namespaces['media'];
    $count = count($xml->channel->item);

    $index = 0;
    foreach ($xml->channel->item as $item) {
        $title = html_entity_decode((string)$item->title);
        $description = html_entity_decode((string)$item->children('http://purl.org/rss/1.0/modules/content/')->encoded);
        $pubDate = (string)$item->pubDate;
        $link = (string)$item->link;

        if(mb_strlen($description) > 750) {
            $truncatedCaption = mb_substr($description, 0, 450);
            $lastSpacePos = mb_strrpos($truncatedCaption, ' ');
            $truncatedCaption = mb_substr($truncatedCaption, 0, $lastSpacePos);
            $caption = $truncatedCaption . '...';
        } else {
            $caption = $description;
        }
        $caption = "📌 $title\n\n💬 $caption\n\n🔗 $link\n\n⏰ $pubDate\n\n#news";

        if ($index + 1 == $count) {
            $buttons = add_return_home([], "menu_news_new", true);
            $buttons = ['inline_keyboard' => $buttons];
        } elseif (!check_if_today($pubDate)) {
            $buttons = add_return_home([], "menu_news_new", true);
            $buttons = ['inline_keyboard' => $buttons];
        } else {
            $buttons = null;
        }

        sendAction($chat_id, "typing");
        if (isset($item->children($media_ns)->thumbnail)) {
            $thumbnail = (string)$item->children('http://search.yahoo.com/mrss/')->thumbnail->attributes()['url'];
            sendDocument("photo", $chat_id, $thumbnail, $caption, $buttons);
        } else {
            sendMessage($chat_id, $caption, $buttons);
        }
        $index++;

        if (!check_if_today($pubDate)) {
            die();
        }
    }
} elseif ($data == 'news_awn') {
    $rss_feed_url = 'https://www.awn.com/news/rss.xml';
    $xml = @simplexml_load_file($rss_feed_url);

    if ($xml === false) {
        show_alert("⚠️ خطایی رخ داد لطفا دوباره تلاش کنید", true);
    } else {
        live_statistics("اخبار انیمیشن", $from_id);
    }
    $count = count($xml->channel->item);

    $index = 0;
    foreach ($xml->channel->item as $item) {
        $title = trim(html_entity_decode((string)$item->title));
        $description = trim(html_entity_decode((string)$item->description));
        $pubDate = (string)$item->pubDate;
        $link = (string)$item->link;

        if(mb_strlen($description) > 750) {
            $truncatedCaption = mb_substr($description, 0, 450);
            $lastSpacePos = mb_strrpos($truncatedCaption, ' ');
            $truncatedCaption = mb_substr($truncatedCaption, 0, $lastSpacePos);
            $caption = $truncatedCaption . '...';
        } else {
            $caption = $description;
        }
        $caption = "📌 $title\n\n💬 $caption\n\n🔗 $link\n\n⏰ $pubDate\n\n#news";

        if ($index + 1 == $count) {
            $buttons = add_return_home([], "menu_news_new", true);
            $buttons = ['inline_keyboard' => $buttons];
        } elseif (!check_if_today($pubDate)) {
            $buttons = add_return_home([], "menu_news_new", true);
            $buttons = ['inline_keyboard' => $buttons];
        } else {
            $buttons = null;
        }

        sendAction($chat_id, "typing");
        if (isset($item->enclosure)) {
            $thumbnail = (string)(string) $item->enclosure['url'];
            sendDocument("photo", $chat_id, $thumbnail, $caption, $buttons);
        } else {
            sendMessage($chat_id, $caption, $buttons);
        }

        $index++;

        if (!check_if_today($pubDate)) {
            die();
        }
    }
} elseif ($data == 'news_slashfilm') {
    $rss_feed_url = 'https://www.slashfilm.com/feed/';
    $xml = @simplexml_load_file($rss_feed_url);

    if ($xml === false) {
        show_alert("⚠️ خطایی رخ داد لطفا دوباره تلاش کنید", true);
    } else {
        live_statistics("اخبار فیلم", $from_id);
    }

    $namespaces = $xml->getNamespaces(true);
    $media_ns = $namespaces['media'];
    $count = count($xml->channel->item);

    $index = 0;
    foreach ($xml->channel->item as $item) {
        $title = trim(html_entity_decode((string)$item->title));
        $description = (string)$item->description;
        $description = substr($description, 9);
        $description = substr($description, 0, -3);
        $description = trim(html_entity_decode($description));
        $pubDate = (string)$item->pubDate;
        $link = (string)$item->link;

        if(mb_strlen($description) > 750) {
            $truncatedCaption = mb_substr($description, 0, 450);
            $lastSpacePos = mb_strrpos($truncatedCaption, ' ');
            $truncatedCaption = mb_substr($truncatedCaption, 0, $lastSpacePos);
            $caption = $truncatedCaption . '...';
        } else {
            $caption = $description;
        }
        $caption = "📌 $title\n\n💬 $caption\n\n🔗 $link\n\n⏰ $pubDate\n\n#news";

        if ($index + 1 == $count) {
            $buttons = add_return_home([], "menu_news_new", true);
            $buttons = ['inline_keyboard' => $buttons];
        } elseif (!check_if_today($pubDate)) {
            $buttons = add_return_home([], "menu_news_new", true);
            $buttons = ['inline_keyboard' => $buttons];
        } else {
            $buttons = null;
        }

        sendAction($chat_id, "typing");
        if (isset($item->children($media_ns)->thumbnail)) {
            $thumbnail = (string)$item->children('http://search.yahoo.com/mrss/')->thumbnail->attributes()['url'];
            sendDocument("photo", $chat_id, $thumbnail, $caption, $buttons);
        } else {
            sendMessage($chat_id, $caption, $buttons);
        }
        $index++;

        if (!check_if_today($pubDate)) {
            die();
        }
    }
} elseif ($data == 'today_birthday') {
    $userAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36 Edg/119.0.0.0";
    $apiUrl = "https://www.animecharactersdatabase.com/api_series_characters.php?month=$month&day=$day";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    $response = curl_exec($ch);
    if ($response === false) {
        show_alert("⚠️ خطایی رخ داد لطفا دوباره تلاش کنید", true);
    } else {
        $index = 0;
        $characters = json_decode($response, true)['characters'];
        foreach ($characters as $index => $character) {
            $name = $character['name'];
            $description = trim(html_entity_decode($character['desc']));
            $gender = $character['gender'] === 'Male' ? 'مرد' : 'زن';
            $gender_sign = $gender === 'مرد' ? '👨' : '👩';
            $img = $character['character_image'];
            $caption = "🎉 نام: $name\n$gender_sign جنسیت: $gender\n🔍 توضیحات: $description";

            if ($index + 1 == count($characters)) {
                $buttons = add_return_home([], "menu_news_new", true);
                $buttons = ['inline_keyboard' => $buttons];
            } else {
                $buttons = null;
            }
            sendAction($chat_id, "typing");
            sendDocument("photo", $chat_id, $img, $caption, $buttons);
        }
        live_statistics("اخبار تولد", $from_id);
    }
    curl_close($ch); exit;
}

function check_if_today($date) {
    $timestamp = strtotime($date);
    $date = date("Y-m-d", $timestamp);
    $today = date("Y-m-d");
    if ($date === $today) {
        return true;
    } else {
        return false;
    }
}