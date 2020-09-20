<?php
$root = __DIR__;
include "$root/config/vars.php";
include "$root/core/vk.api.class.php";
include "$root/core/bot.api.class.php";
include "$root/core/coin.api.class.php";

$data = json_decode(file_get_contents('php://input'), true);
$game = json_decode(file_get_contents("$root/database/game.json"));
$global = json_decode(file_get_contents("$root/database/global.json"));
if ($data == null) die("WTF");
$vkCoin = new VKCoinClient($coin_id, $coin_key);
$vkApi = new vkApi($access_token, "5.95");
$botApi = new botApi($vkApi, "2000000001");
if (!$vkCoin->isKeyValid($data)) die("Мамкин хакер");
echo("ok");
$data['amount'] = $data['amount'] / 1000;
if ($data['amount'] < 1) die();
$data_req = ["user_ids" => $data['from_id']];
$user = $vkApi->method("users.get", $data_req);
$name = $user->response[0]->first_name . " " . $user->response[0]->last_name;
if ($game->time - time() <= 5 and $game->time != 0) {
    $vkCoin->sendTransfer($data["from_id"], $data["amount"] * 1000);
    die();
}
save:
$game = json_decode(file_get_contents("$root/database/game.json"));
$global = json_decode(file_get_contents("$root/database/global.json"));
$id = 0;
foreach ($game->players as $key => $player) {
    if ($player->id == $data['from_id']) {
        $id = 1;
        $game->players[$key]->sum = $game->players[$key]->sum + $data['amount'];
    }
}
if ($id == 0) {
    $id = count($game->players);
    $game->players[$id]->id = $data['from_id'];
    $game->players[$id]->name = $name;
    $game->players[$id]->sum = $data['amount'];
}
if (count($game->players) >= 2 and $game->time == 0) {
    $exec = 1;
    $game->time = time() + 120;
} else $exec = 0;
$sum = $botApi->num_format($data['amount']);
$game->bank = $game->bank + $data['amount'];
$bank = $botApi->num_format($game->bank);
$vkApi->method("messages.editChat", ["chat_id" => "1", "title" => "$name_conf - Банк: $bank"]);
if (file_put_contents("$root/database/game.json", json_encode($game)) === false or file_put_contents("$root/database/global.json", json_encode($global)) === false) {
    sleep(1);
    goto save;
}
$botApi->sendMessage("Игрок [id{$data['from_id']}|$name] сделал ставку $sum коинов");
if ($exec == 1) passthru("(php -f cron.php $secret &) >> null");
