<?php

namespace App\Model;

use ThreadMeUp\Slack\Client as SlackClient;

class Slack
{
    protected $client;

    public function __construct($token, $team, $username, $icon) {
        $this->client = new SlackClient([
            'token'    => $token,
            'team'     => $team,
            'username' => $username,
            'icon'     => $icon,
            'parse'    => ''
        ]);
    }

    public function send($message, $channel)
    {
        $this->client->setDebug(false);
        return $this->client->chat($channel)->send($message);
    }
}
