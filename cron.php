<?php
$root = __DIR__;
include "$root/config/vars.php";
include "$root/core/vk.api.class.php";
include "$root/core/bot.api.class.php";
include "$root/core/coin.api.class.php";

set_time_limit(300);

$vkCoin = new VKCoinClient($coin_id, $coin_key);
$vkApi = new vkApi($access_token, "5.95");
$botApi = new botApi($vkApi, "2000000001");
$counter = -1;
$is_bonus = 0;

while (true) {
    $game = json_decode(file_get_contents("$root/database/game.json"));
    $global = json_decode(file_get_contents("$root/database/global.json"));
    $counter++;
    if ($game->time - time() <= 0) {
        goto win;
    }
    if ($counter % 20 == 0 or $counter == 0) {
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

    sleep(1);
}

win:
if ($game->winner != 0) {
    $winner = $game->winner;
    foreach ($game->players as $player) {
        if ($player->id == $winner) $sum = $player->sum;
    }
    $percent = 100 / ($game->bank / $sum);
    $percent = number_format($percent, 2, ",", " ");
    $data = ["user_ids" => $winner];
    $user = $vkApi->method("users.get", $data);
    $name = "{$user->response[0]->first_name} {$user->response[0]->last_name}";
    $message = "С процентом $percent% выигрывает [id{$winner}|{$name}]";
} else {
    $players = [];
    foreach ($game->players as $player) {
        $percent = 100 / ($game->bank / $player->sum);
        $num = intval(number_format($percent, 2, "", ""));
        for ($i = 0; $i < $num; $i++) {
            $players[count($players)] = $player->id;
        }
    }
    $winner = array_rand($players);
    $winner = $players[$winner];
    foreach ($game->players as $player) {
        if ($player->id == $winner) $sum = $player->sum;
    }
    $percent = 100 / ($game->bank / $sum);
    $percent = number_format($percent, 2, ",", " ");
    $data = ["user_ids" => $winner];
    $user = $vkApi->method("users.get", $data);
    $name = "{$user->response[0]->first_name} {$user->response[0]->last_name}";
    $message = "С процентом $percent% выигрывает [id{$winner}|{$name}]";
}
$bank = ($game->bank - ($game->bank / 100 * $global->proc)) * 1000;
$proc = $game->bank / 100 * $global->proc;
$global->counter++;
$game->players = [];
$game->bank = 0;
$game->time = 0;
$game->winner = 0;
file_put_contents("$root/database/game.json", json_encode($game));
file_put_contents("$root/database/global.json", json_encode($global));
$vkApi->method("messages.editChat", ["chat_id" => "1", "title" => "$name_conf - Банк: 0"]);
$vkCoin->sendTransfer($winner, $bank);
$bank = $botApi->num_format($bank / 1000);
$message .= ", сумма выигрыша: $bank";
$botApi->sendMessage($message);

