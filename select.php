<?php

$user = DB::$the->query("SELECT ban,id_key,cat FROM `sel_users` WHERE `chat` = {$chat} ");
$user = $user->fetch(PDO::FETCH_ASSOC);

// Берем информацию о разделе
$row = DB::$the->query("SELECT * FROM `sel_subcategory` WHERE `id` = '".$message."' ");
$subcat = $row->fetch(PDO::FETCH_ASSOC);

// Берем информацию о категории
$row = DB::$the->query("SELECT name FROM `sel_category` WHERE `id` = '".$subcat['id_cat']."' ");
$cat = $row->fetch(PDO::FETCH_ASSOC);

// Проверяем наличие ключей
$total = DB::$the->query("SELECT id FROM `sel_keys` where `id_subcat` = '".$subcat['id']."' and `sale` = '0' and `block` = '0' ");
$total = $total->fetchAll();

if(count($total) == 0) // Если пусто, вызываем ошибку
{ 

// Отправляем текст
$bot->sendMessage($chat, '⛔ Данный товар закончился!');
}
else // Иначе выводим результат
{

$clear = DB::$the->query("SELECT block_user FROM `sel_keys` where `block_user` = '".$chat."' ");
$clear = $clear->fetchAll();

if(count($clear) != 0){
DB::$the->prepare("UPDATE sel_keys SET block=? WHERE block_user=? ")->execute(array("0", $chat)); 
DB::$the->prepare("UPDATE sel_keys SET block_time=? WHERE block_user=? ")->execute(array('0', $chat));
DB::$the->prepare("UPDATE sel_keys SET block_user=? WHERE block_user=? ")->execute(array('0', $chat));  
}

// Получаем информацию о ключе 
$key = DB::$the->query("SELECT id,code,id_subcat FROM `sel_keys` where `id_subcat` = '".$subcat['id']."' and `sale` = '0' and `block` = '0' order by rand() limit 1");
$key = $key->fetch(PDO::FETCH_ASSOC);


DB::$the->prepare("UPDATE sel_keys SET block=? WHERE id=? ")->execute(array("1", $key['id'])); 
DB::$the->prepare("UPDATE sel_keys SET block_user=? WHERE id=? ")->execute(array($chat, $key['id'])); 
DB::$the->prepare("UPDATE sel_keys SET block_time=? WHERE id=? ")->execute(array(time(), $key['id'])); 

DB::$the->prepare("UPDATE sel_users SET id_key=? WHERE chat=? ")->execute(array($key['id'], $chat));
	DB::$the->prepare("UPDATE sel_users SET verification=? WHERE chat=? ")->execute(array(time(), $chat));
$set_qiwi = DB::$the->query("SELECT number FROM `sel_set_qiwi` WHERE `active` = '1' ");
$set_qiwi = $set_qiwi->fetch(PDO::FETCH_ASSOC);	
	
DB::$the->prepare("UPDATE sel_users SET pay_number=? WHERE chat=? ")->execute(array($set_qiwi['number'], $chat)); 
	$cat_name = urldecode($cat['name']);
	$subcat_name = urldecode($subcat['name']);
$text = "Вам зарезервировано: {$subcat_name}
Район: {$cat_name}
Переведите на кошелек Qiwi
№+{$set_qiwi['number']}
Сумму: {$subcat['amount']} руб

С комментарием: ".$key['id']."


Резерв длится {$set_bot['block']}мин. В течении этого времени оплатите заказ.
Внимание!!!
Оплата производится ОДНИМ платежом.
Оплата частями не принимается!!! Сумма должна быть не меньше прайсовой!!!
После оплаты - нажмите кнопку \"Я Оплатил(а)\" или введите любое сообщение.
Для отмены заказа: 0 или Otmena
";


// Отправляем текст
$keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup([['Я Оплатил(а)'], ['Otmena']], null, true);
$bot->sendMessage($chat, $text, false, null, null, $keyboard);


}	

exit;
?>