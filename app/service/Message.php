<?php

namespace Service;

class Message {

    public $type;
    public $sender;
    public $receivers;
    public $game;
    public $text;
    public $members;

    public function __construct($params) {
        foreach ($params as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function getContent() {
        $content = [];
        $content['type'] = $this->type;
        $content['sender'] = [
                'id' => isset($this->sender) ? $this->sender->id : '',
                'name' => isset($this->sender) ? $this->sender->name : ''
        ];
        $content['text'] = $this->text;
        $content['members'] = $this->members;
        if (isset($this->game)) {
            $content['game']['id'] = $this->game->id;
            if ($this->game->status === Game::PROCESS) {
                $content['game'] = [
                    'id' => $this->game->id,
                    'state' => $this->game->state,
                    'players' => $this->game->getPlayers(),
                    'whoseMove' => $this->game->getActivePlayer()
                ];
            }
        }
        return $content;
    }
}
