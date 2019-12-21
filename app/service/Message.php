<?php

namespace Service;

class Message {

    public $type;
    public $sender;
    public $receivers;
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
        return [
            'type' => $this->type,
            'sender' => [
                'id' => isset($this->sender) ? $this->sender->id : '',
                'name' => isset($this->sender) ? $this->sender->name : ''
            ],
            'text' => $this->text,
            'members' => $this->members
        ];
    }
}