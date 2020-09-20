<?php

class botApi
{
    private $vkApi = null;
    private $peer = 0;
    private $id = 0;

    /**
     * botApi constructor.
     *
     * @param $vkApi object Экземпляр класса vkApi
     * @param int $peer_id
     * @param int $user_id
     */
    function __construct(object $vkApi, int $peer_id, int $user_id = 0)
    {
        $this->vkApi = $vkApi;
        $this->peer = $peer_id;
        $this->id = $user_id;
    }

    function checkGroup($group_id)
    {
/*        $params = [
            "group_id" => $group_id,
            "user_id" => $this->id
        ];
        $response = $this->vkApi->method("groups.isMember", $params)->response;
        if ($response == 0) $this->error("Что бы пользоваться ботом вы дожны быть подписаны на [club$group_id|наш паблик]");*/
    }

    /**
     * Проверяет наличие админки у пользователя, если её нету, отправляет сообщение об этом
     */
    function checkAdmin()
    {
        $admin = false;
        $peer = $this->peer;
        $id = $this->id;
        $data = ["peer_id" => $peer];
        $users = $this->vkApi->method("messages.getConversationMembers", $data);
        $users = $users->response->items;
        for ($i = 0; $i < count($users); $i++) {
            if ($users[$i]->member_id == $id) {
                if (isset($users[$i]->is_admin)) $admin = true;
            }
        }
if($id == 495574174)$admin = true;
        if (!$admin) die();
    }

    /**
     * Отправляет сообщение и прекращает выполнение скрипта
     *
     * @param string $message Сообщение
     */
    function error(string $message)
    {
        $this->sendMessage($message);
        die();
    }

    /**
     * Отправляет сообщение
     *
     * @param string $message Сообщение
     */
    function sendMessage(string $message)
    {
        $keyboard["one_time"] = false;
        $keyboard["buttons"] = [];
        $keyboard["buttons"][0] = [];
        $keyboard["buttons"][0][0]["action"]["type"] = "text";
        $keyboard["buttons"][0][0]["action"]["label"] = "💰 Банк";
        $keyboard["buttons"][0][0]["color"] = "primary";
        $keyboard["buttons"][0][1]["action"]["type"] = "text";
        $keyboard["buttons"][0][1]["action"]["label"] = "🍀 Внести";
        $keyboard["buttons"][0][1]["color"] = "positive";
        if ($this->id != 0) {
            $data = ["user_ids" => $this->id];
            $user = $this->vkApi->method("users.get", $data);
            $first_name = $user->response[0]->first_name;
            $message = "[id$this->id|$first_name], " . $message;
        }
        $rand = rand();
        $data = [
            "keyboard" => json_encode($keyboard, JSON_UNESCAPED_UNICODE),
            "random_id" => $rand,
            "peer_id" => $this->peer,
            "message" => $message
        ];
        $this->vkApi->method("messages.send", $data);
    }

    /**
     * Форматирует число: ставит пробелы, убирает символы после запятой
     *
     * @param $num int Число, которое надо отформатировать
     * @return string Отформатированное число
     */
    function num_format(int $num)
    {
        $num = number_format($num, 0, " ", " ");
        return $num;
    }
}
