﻿<?php
/**
 * @var \TelegramBot\Api\BotApi $bot
 */

// Получаем информацию из БД о настройках бота
//$set_bot = DB::$the->query("SELECT token,block FROM `sel_set_bot` ");
//$set_bot = $set_bot->fetch(PDO::FETCH_ASSOC);
//$token = $set_bot['token']; // токен бота

// $chat = trim($argv[1]);
// $message = trim($argv[2]);
// $chat = '213586898';
//
//var_dump($bot->sendMessage($chat, $message));
// $bot->sendMessage($chat, $message);

$name_cat = DB::$the->query("SELECT name, id FROM `sel_category` WHERE `name` = '".$message."' ");
$name_cat = $name_cat->fetch(PDO::FETCH_ASSOC);

if (count($name_cat) == 0){
	$name_cat = DB::$the->query("SELECT name, id FROM `sel_category` WHERE `id` = '".$message."' ");
	$name_cat = $name_cat->fetch(PDO::FETCH_ASSOC);
}
DB::$the->prepare("UPDATE sel_users SET cat=? WHERE chat=? ")->execute(array($name_cat['id'], $chat));

$text = "Вы выбрали: ".urldecode($name_cat['name'])."\n\n";

$query = DB::$the->query("SELECT id,name,mesto FROM `sel_subcategory` WHERE `id_cat` = '".$name_cat['id']."' order by `mesto` ");

$keys = [];
$i = 0;
$k = 0;
while($cat = $query->fetch()) {
	$text .= urldecode($cat['name'])." или /".$cat['id']." \n\n"; // ЭТО НАЗВАНИЕ КАТЕГОРИЙ

	// if($k >= 3){ $i++; $k = 0;}
	$keys[][] = urldecode($cat['name']);
	// $k++;
}

//$keys[][] = '🆘Помощь';
$keys[][] = '🔷Доп. инфо';
$keys[][] = '↪Назад';
$text .= "\n".$set_bot['footer'];
// Отправляем все это пользователю
$keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($keys, true, true);
$bot->sendMessage($chat, $text, false, null, null, $keyboard);