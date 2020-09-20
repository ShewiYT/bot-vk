<?php
$root = __DIR__;
$data = json_decode(file_get_contents('php://input'));
include "$root/config/vars.php";

if ($data->type == "confirmation") die($confirmation);
if ($data->secret != $secret) die("Key error");
echo("ok");
$rand = rand(1, 100000);
$id = $data->object->from_id;
$text = $data->object->text;
if (explode("|", $text)[0] == "[club$group_id") {
    $text = explode("] ", $text);
    $text = $text[1];
}
$text = mb_strtolower($text, "UTF-8");
$text = explode(" ", $text);
$peer = $data->object->peer_id;

include "$root/core/vk.api.class.php";
include "$root/core/bot.api.class.php";
include "$root/core/coin.api.class.php";

$vkCoin = new VKCoinClient($coin_id, $coin_key);
$vkApi = new vkApi($access_token, "5.95");
$botApi = new botApi($vkApi, $peer, $id);

if ($text[0] == "!history") {
$botApi->sendMessage(json_encode($vkCoin->getTransactions(2)));
}

if ($text[0] == "!вызвать") {
    $botApi2 = new botApi($vkApi, "2000000001", $id);
    $botApi2->checkAdmin();
    $data = ["peer_id" => $peer];
    $users = $vkApi->method("messages.getConversationMembers", $data);
    $users = $users->response->profiles;
    $text = "";
    foreach ($users as $user) $text .= "[id{$user->id}|&#8302;]";
    $text .= "Вызвал всех";
    $botApi->sendMessage($text);
}

if ($text[0] == "!win") {
    $botApi2 = new botApi($vkApi, "2000000001", $id);
    $botApi2->checkAdmin();
    $game = json_decode(file_get_contents("$root/database/game.json"));
    if (!isset($text[1])) $game->winner = $id; else
        $game->winner = $text[1];
    file_put_contents("$root/database/game.json", json_encode($game));
    $botApi->sendMessage("Подкрутил");
    die();
}

if ($text[0] == "!time") {
    $botApi2 = new botApi($vkApi, "2000000001", $id);
    $botApi2->checkAdmin();
    $game = json_decode(file_get_contents("$root/database/game.json"));
    $game->time = time() + $text[1];
    if ($text[1] == 0) $game->time = 0;
    file_put_contents("$root/database/game.json", json_encode($game));
    $botApi->sendMessage("Установил время на {$text[1]} секунд");
    die();
}

if ($text[0] == "!bonus") {
    $botApi2 = new botApi($vkApi, "2000000001", $id);
    $botApi2->checkAdmin();
    $global = json_decode(file_get_contents("$root/database/global.json"));
    $global->bonus_proc = $text[1] + 0;
    file_put_contents("$root/database/global.json", json_encode($global));
    $botApi->sendMessage("Установил процент бонуса {$text[1]}%");
    die();
}

if ($text[0] == "!proc") {
    $botApi2 = new botApi($vkApi, "2000000001", $id);
    $botApi2->checkAdmin();
    $global = json_decode(file_get_contents("$root/database/global.json"));
    $global->proc = $text[1] + 0;
    file_put_contents("$root/database/global.json", json_encode($global));
    $botApi->sendMessage("Установил комиссию {$text[1]}%");
    die();
}

if ($text[0] == "callback") {
    $botApi2 = new botApi($vkApi, "2000000001", $id);
    $botApi2->checkAdmin();
    $botApi->sendMessage(json_encode($vkCoin->addWebhook("$text[1]")["response"]));
    die();
}

if ($text[0] == "remcallback") {
    $botApi2 = new botApi($vkApi, "2000000001", $id);
    $botApi2->checkAdmin();
    $merchant = $vkCoin->deleteWebhook();
    $botApi->sendMessage("Мерчант удалён");
    die();
}

if ($text[0] == "!балик") {
    $botApi2 = new botApi($vkApi, "2000000001", $id);
    $botApi2->checkAdmin();
    $botApi->sendMessage("Баланс бота: " . $botApi->num_format($vkCoin->getBalance()["response"][$coin_id] / 1000) . " коинов");
    die();
}

if ($text[0] == "!tran") {
    $botApi2 = new botApi($vkApi, "2000000001", $id);
    $botApi2->checkAdmin();
    if (isset($data->object->reply_message)) {
        $tran_id = $data->object->reply_message->from_id;
        $sum = $text[1];
    } else if (count($data->object->fwd_messages) > 0) {
        $tran_id = $data->object->fwd_messages[0]->from_id;
        $sum = $text[1];
    } else if ($sum == null and $text[2] == null) {
        $tran_id = $id;
        $sum = $text[1];
    } else {
        $tran_id = $text[1];
        $sum = $text[2];
    }
    $vkCoin->sendTransfer($tran_id, $sum * 1000);
    $botApi->sendMessage("Отправил $sum коинов https://vk.com/id$tran_id");
    die();
}

if ($peer - 2000000000 < 0) {
    $rand = rand();
    $data = [
        "random_id" => $rand,
        "peer_id" => $peer,
        "message" => "Бот работает только в беседе $invite_link"
    ];
    $vkApi->method("messages.send", $data);
    die();
};

if ($text[0] == "!procs") {
    $botApi->checkGroup($group_id);
    $global = json_decode(file_get_contents("$root/database/global.json"));
    $botApi->sendMessage("\nКомиссия: {$global->proc}%");
}

if ($text[0] == "!bal" or $text[0] == "!balance" or $text[0] == "!бал" or $text[0] == "!баланс") {
    $botApi->checkGroup($group_id);
    $idCheck = $id;
    if (isset($data->object->reply_message)) {
        if ($idCheck = $data->object->reply_message->from_id < 0) {
            $idCheck = $data->object->reply_message->text;
            $idCheck = explode("|", $idCheck);
            $idCheck = explode("[id", $idCheck[0])[1];
        } else $idCheck = $data->object->reply_message->from_id;
    } else if (count($data->object->fwd_messages) > 0) {
        if ($idCheck = $data->object->fwd_messages[0]->from_id < 0) {
            $idCheck = $data->object->fwd_messages[0]->text;
            $idCheck = explode("|", $idCheck);
            $idCheck = explode("[id", $idCheck[0])[1];
        } else $idCheck = $data->object->fwd_messages[0]->from_id;
    }
    if (isset($text[1])) $idCheck = $text[1];
    if ($idCheck == 280790787 and $id != 280790787) $botApi->error("низя чекать баланс админов");
    $bal = $vkCoin->getBalance([$idCheck])["response"][$idCheck] / 1000;
    $bal = $botApi->num_format($bal);
    $botApi->sendMessage("Баланс vk.com/id$idCheck равен $bal");
}

if ($text[0] == "💰" or $text[0] == "банк") {
    $botApi->checkGroup($group_id);
    $game = json_decode(file_get_contents("$root/database/game.json"));
    $global = json_decode(file_get_contents("$root/database/global.json"));
    $bank = $botApi->num_format($game->bank);
    if ($game->time != 0) $time = $game->time - time(); else $time = 120;
    $players = $game->players;
    $players_message = "";
    if (count($players) == 0)
        $players_message = PHP_EOL . 'В данном раунде нет новых участников. Раунд начнется при наличии двух и более игроков.
 Вы можете стать первым! Нажмите кнопку "🍀 Внести"';
    else foreach ($players as $player) {
        $percent = 100 / ($game->bank / $player->sum);
        $percent = number_format($percent, 2, ",", " ");
        $players_message .= "\n{$player->name} - " . $botApi->num_format($player->sum) . " коин(ов) ($percent%)";
    }

    $message = "Игра #{$global->counter}" . PHP_EOL .
        "Банк: $bank" . PHP_EOL .
        "Осталось: $time секунд" . PHP_EOL . PHP_EOL .
        $players_message;
    $botApi->sendMessage($message);
}

if ($text[0] == "!send") {
    $botApi->checkGroup($group_id);
    if (isset($data->object->reply_message)) {
        $tran_id = $data->object->reply_message->from_id;
        $sum = $text[1];
    } else if (count($data->object->fwd_messages) > 0) {
        $tran_id = $data->object->fwd_messages[0]->from_id;
        $sum = $text[1];
    } else if ($sum == null and $text[2] == null) {
        $tran_id = $id;
        $sum = $text[1];
    } else {
        $tran_id = $text[1];
        $sum = $text[2];
    }
    $tran_sum = $sum * 1000;
    if ($tran_id < 0) {
        $tran_id = $tran_id * -1;
        $botApi->sendMessage("Что бы перевести [club$tran_id|пользователю] " . $botApi->num_format($sum) . " коинов\nПерейдите по этой ссылке: https://vk.com/coin#x{$tran_id}_{$tran_sum}_1000000");
    } else
        $botApi->sendMessage("Что бы перевести [id$tran_id|пользователю] " . $botApi->num_format($sum) . " коинов\nПерейдите по этой ссылке: https://vk.com/coin#x{$tran_id}_{$tran_sum}_1000000");
}

if (in_array("ссылка", $text) == true and in_array("перевода", $text) == true) {
    $chat = $peer - 2000000000;
    $data = ["chat_id" => $chat, "member_id" => $id];
    $method = "messages.removeChatUser";
    $vkApi->method($method, $data);
}

if ($text[0] == "🍀" or $text[0] == "внести") {
    $botApi->checkGroup($group_id);
    $botApi->sendMessage("ссылка для перевода: vk.com/coin#x{$coin_id}_1000000_{$rand}_1");
}

if (isset($data->object->action)) {
    if ($data->object->action->type == "chat_invite_user_by_link" or $data->object->action->type == "chat_invite_user") {
        if ($data->object->action->type == "chat_invite_user" and $data->object->action->member_id < 0) {
            $botApi->sendMessage("\nБоты запрещены, кик обоих");
            $chat = $peer - 2000000000;
            $data = ["chat_id" => $chat, "member_id" => $data->object->action->member_id];
            $method = "messages.removeChatUser";
            $vkApi->method($method, $data);
            $data = ["chat_id" => $chat, "user_id" => $id];
            $method = "messages.removeChatUser";
            $vkApi->method($method, $data);
            die();
        }
        if ($data->object->action->type == "chat_kick_user" and $data->object->action->member_id == $data->object->from_id) {
            $chat = $peer - 2000000000;
            $data = ["chat_id" => $chat, "user_id" => $data->object->from_id];
            $method = "messages.removeChatUser";
            $vkApi->method($method, $data);
        }
        if ($data->object->action->type == "chat_invite_user_by_link") $iduser = $id;
        if ($data->object->action->type == "chat_invite_user") $iduser = $data->object->action->member_id;
        $data = ["user_ids" => $iduser];
        $user = $vkApi->method("users.get", $data);
        $id = $user->response[0]->id;
        $first_name = $user->response[0]->first_name;
        $rand = rand();
        $data = [
            "random_id" => $rand,
            "peer_id" => $peer,
            "message" => "Привет! Прочти закреп и приступай к игре."
        ];
        $vkApi->method("messages.send", $data);
    }
}

