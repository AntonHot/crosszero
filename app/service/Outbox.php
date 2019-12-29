<?php

namespace Service;

class Outbox {

    /** @var Message */
    public $messages;

    public function __construct() {
        $this->textMessages = [];
    }
}
