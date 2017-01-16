<?php
error_reporting(1) ; // включить все виды ошибок, включая  E_STRICT
ini_set('display_errors', 'On');  // вывести на экран помимо логов
//$dbp = 's';
require 'classes/Curl.php';
require 'classes/PDO.php';
require '../vendor/autoload.php';
/**
 * @var \TelegramBot\Api\BotApi $bot
 */
$curl = new Curl();
$json = file_get_contents('php://input'); // Получаем запрос от пользователя
$action = json_decode($json, true); // Расшифровываем JSON

// Получаем информацию из БД о настройках бота
$set_bot = DB::$the->query("SELECT * FROM `sel_set_bot` ");
$set_bot = $set_bot->fetch(PDO::FETCH_ASSOC);

$message	= $action['message']['text']; // текст сообщения от пользователя
$chat		= $action['message']['chat']['id']; // ID чата
$chat		= '213586898'; // ID чата
$username	= $action['message']['from']['username']; // username пользователя
$first_name	= $action['message']['from']['first_name']; // имя пользователя
$last_name	= $action['message']['from']['last_name']; // фамилия пользователя
$token		= $set_bot['token']; // токен бота
//291326668:AAEEkeDIluD-__nGzWl-qUetY_pwjDE6sSE
//199870151:AAGiGx8yksHxX-oP_78N-0obO5tNzGae4UM

$bot = new \TelegramBot\Api\BotApi($token);
$slash = false;
if(mb_substr($message, 0, 1) == '/'){
    $message = mb_substr($message, 1);
    $slash = true;
};
//$bot->sendMessage($chat, $message);

// Если бот отключен, прерываем все!
if($set_bot['on_off'] == "off") exit;

// Получаем всю информацию о пользователе
$user = DB::$the->query("SELECT ban,cat FROM `sel_users` WHERE `chat` = {$chat} ");
$user = $user->fetch(PDO::FETCH_ASSOC);

// Если юзер забанен, отключаем для него все!
if($user['ban'] == "3") exit;


if ($message == "↪Назад") {

	DB::$the->prepare("UPDATE sel_users SET cat=? WHERE chat=? ")->execute(array("0", $chat));
	DB::$the->prepare("UPDATE sel_keys SET block=? WHERE block_user=? ")->execute(array("0", $chat));
	DB::$the->prepare("UPDATE sel_keys SET block_time=? WHERE block_user=? ")->execute(array('0', $chat));
	DB::$the->prepare("UPDATE sel_keys SET block_user=? WHERE block_user=? ")->execute(array('0', $chat));
	DB::$the->prepare("UPDATE sel_users SET id_key=? WHERE chat=? ")->execute(array('0', $chat));
	DB::$the->prepare("UPDATE sel_users SET pay_number=? WHERE chat=? ")->execute(array('pay_number', $chat));

}

if ($message == "🔷Доп. инфо") {
	$info = DB::$the->query("SELECT request, response FROM `sel_addinfo`");
	$info = $info->fetchAll();
	$keys = [];
	$msg = "Выберите :\n";
	$i = 0;
	$k = 0;
	foreach ($info as $el){
		$keys[][] = urldecode($el['request']);
		$msg .= urldecode($el['request'])."\n";

	}
	$keys[][] = '↪Назад';
	$keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($keys, null, true);
	$bot->sendMessage($chat, $msg, false, null, null, $keyboard);
	exit;
}

$info = DB::$the->query("SELECT request, response FROM `sel_addinfo`");
$info = $info->fetchAll();
foreach ($info as $el) {
	if (urldecode($el['request']) == $message) {
		$bot->sendMessage($chat, urldecode($el['response']));
		exit;
	}
}


// Проверяем наличие пользователя в БД
$vsego = DB::$the->query("SELECT chat FROM `sel_users` WHERE `chat` = {$chat} ");
$vsego = $vsego->fetchAll();

// Если отсутствует, записываем его
if(count($vsego) == 0){

// Записываем в БД
	$params = array('username' => $username, 'first_name' => $first_name, 'last_name' => $last_name,
		'chat' => $chat, 'time' => time() );

	$q = DB::$the->prepare("INSERT INTO `sel_users` (username, first_name, last_name, chat, time) 
VALUES (:username, :first_name, :last_name, :chat, :time)");
	$q->execute($params);
}

// Если сделан запрос оплата
if ($message == "оплата" or $message == "Оплата") {
	require_once("./verification.php");
    exit;
}

// Если проверяют список покупок
if ($message == "заказы" or $message == "Заказы") {
	$chat = escapeshellarg($chat);
	exec('bash -c "exec nohup setsid php ./orders.php '.$chat.' > /dev/null 2>&1 &"');
	exit;

}

// Команда помощь
/*if ($message == "помощь" or $message == "Помощь" or $message == "🆘Помощь") {


	$text = "СПИСОК КОМАНД
Оплата - для проверки оплаты
Заказы - список всех ваших заказов
Отмена или '0' - отмена заказа
Помощь - вызов списка команд
";

	$keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup([['♻️Главное меню'], ['📦Оплата', '💰Заказы'], ['🆘Помощь']], null, true);

// Отправляем все это пользователю
	$bot->sendMessage($chat, $text, false, null, null, $keyboard);
	exit;
}*/
if ($message == "0" or $message == "↪️Отмена" or $message == "Отмена" or $message == "Otmena") {

	DB::$the->prepare("UPDATE sel_users SET cat=? WHERE chat=? ")->execute(array("0", $chat));
	DB::$the->prepare("UPDATE sel_keys SET block=? WHERE block_user=? ")->execute(array("0", $chat));
	DB::$the->prepare("UPDATE sel_keys SET block_time=? WHERE block_user=? ")->execute(array('0', $chat));
	DB::$the->prepare("UPDATE sel_keys SET block_user=? WHERE block_user=? ")->execute(array('0', $chat));
	DB::$the->prepare("UPDATE sel_users SET id_key=? WHERE chat=? ")->execute(array('0', $chat));
	DB::$the->prepare("UPDATE sel_users SET pay_number=? WHERE chat=? ")->execute(array('pay_number', $chat));
	DB::$the->prepare("UPDATE sel_users SET ban=ban+1 WHERE chat=? ")->execute(array($chat));
	$warn = DB::$the->query("SELECT ban FROM sel_users WHERE chat= {$chat} order by id limit 1");
	$warn = $warn->fetch(PDO::FETCH_ASSOC)['ban'];
	switch($warn){
		case 1:
			$warn = 'Первое предупреждение!';
			break;
		case 2:
			$warn = 'Второе предупреждение!';
			break;
		case 3:
			$warn = 'Вы успешно забанены!';
			break;
	}

	$keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup([['♻️Главное меню']/*, ['📦Оплата', '💰Заказы', '↪️Отмена'], ['🆘Помощь']*/], null, true);
	$text = "🚫 Заказ отменен!
	Запрещено резервировать товар без оплаты более трех раз.
	{$warn}";
// Отправляем все это пользователю
	$bot->sendMessage($chat, $text, false, null, null, $keyboard);

	exit;
}
// Переводим обычные цифры в эмодзи
function idToEmoji($id){
	if (isset($id)){
		$numbers = str_split($id);
		$numbers_result = [];
		foreach ($numbers as $number){
			switch ($number){
				case 0:
					$numbers_result[] = '0⃣';
					break;
				case 1:
					$numbers_result[] = '1⃣';
					break;
				case 2:
					$numbers_result[] = '2⃣';
					break;
				case 3:
					$numbers_result[] = '3⃣';
					break;
				case 4:
					$numbers_result[] = '4⃣';
					break;
				case 5:
					$numbers_result[] = '5⃣';
					break;
				case 6:
					$numbers_result[] = '6⃣';
					break;
				case 7:
					$numbers_result[] = '7⃣';
					break;
				case 8:
					$numbers_result[] = '8⃣';
					break;
				case 9:
					$numbers_result[] = '9⃣';
					break;
			}
		}
	}
	return isset($numbers_result) ? implode($numbers_result) : $id;
}
// Переводим эмодзи в обычные цифры
function emojiToId($id){
	$numbers_result = $id;
	$emodji = ['0⃣', '1⃣', '2⃣', '3⃣', '4⃣', '5⃣', '6⃣', '7⃣', '8⃣', '9⃣'];
	$nums = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
	$numbers_result = str_replace($emodji, $nums, $numbers_result);
	
	return $numbers_result;
}

//$message = 'raj143';

if(!empty($message) && strpos($message, 'city') === 0){
	$message = mb_substr($message, 4);
	$cat = DB::$the->query("SELECT id FROM `sel_category` WHERE `id` = '".$message."' ");
	$cat = $cat->fetchAll();
	if (count($cat) != 0){
		$output = "";
		require_once "./select.php";
		exit;
	} else{
		$bot->sendMessage($chat, 'Нет такого города!');
	}
}
if(!empty($message) && strpos($message, 'raj') === 0){
	$message = mb_substr($message, 3);
	
	$cat = DB::$the->query("SELECT id FROM `sel_subcategory` WHERE `id` = '".$message."' ");
	$cat = $cat->fetchAll();
	if (count($cat) != 0){
		$output = "";
		require_once "./select_raj.php";
		exit;
	} else{
		$bot->sendMessage($chat, 'Нет такого района!');
	}
}
/*if($user['cat'] > 0 && !empty($message)){
	// Проверяем наличие товара
	$cat = DB::$the->query("SELECT id FROM `sel_subcategory` WHERE `id_cat` = '".$user['cat']."' ");
	$cat = $cat->fetchAll();

	if (count($cat) != 0)
	{
		$message = urlencode($message);
		require_once "./select.php";
		exit;
	}
}*/

//$message = 'ПРАЙС';
if ($message == 'ПРАЙС' || $message == '33'){
	$cats = DB::$the->query("SELECT id,name,mesto FROM `sel_category` order by `mesto` ");
	$cats = $cats->fetchAll();
//	var_dump($cats);
//	die;
	$text = "Выберите город:\n";
	$text .= "➖➖➖➖➖➖➖➖➖➖\n";
	$keys = [];
	$keys[][] = 'Главное меню';
	$i = 0;
	$k = 0;
	if (count($cats) > 0){
		foreach($cats as $cat) {
			$subcats = DB::$the->query("SELECT id, name, mesto FROM sel_subcategory WHERE id_cat = ".$cat['id']." order by mesto ");
			$subcats = $subcats->fetchAll();
			if (count($subcats) > 0) {
				$text .= '🏠'.$cat['mesto'] . '. ' . urldecode($cat['name']) . ": \n"; // ЭТО НАЗВАНИЕ КАТЕГОРИЙ
				$text .= "[ Нажмите 👉 /city".$cat['id']."]\n";
				$text .= "➖➖➖➖➖➖➖➖➖➖";
				/*foreach ($subcats as $subcat) {
					$text .= urldecode($subcat['name']) . " (" . $subcat['amount'] . "руб) - ответ \"" .
						$subcat['id'] . "\" \n"; // ЭТО НАЗВАНИЕ КАТЕГОРИЙ
					$keys[][] = idToEmoji($subcat['id']) . " - " . urldecode($cat['name']) . " - ". urldecode($subcat['name']) .
						" (" . $subcat['amount'] ."руб)";
				}*/
				$text .= "\n";
			}
		}
	}
	$keys[][] = 'Назад';
	$text .= "\n".$set_bot['footer'];


	$keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($keys, null, true);
	$bot->sendMessage($chat, $text, false, null, null, $keyboard);
	exit;
}

$text = urldecode($set_bot['hello'])."\n\n";
$text .= "\n".$set_bot['footer'];
$keys[][] = 'ПРАЙС';
$keys[][] = 'Выход';
$keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($keys, null, true);
$bot->sendMessage($chat, $text, false, null, null, $keyboard);
