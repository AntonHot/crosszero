<?php

namespace Service\Message;

class TextMessage extends Message {
    
    protected $text;
    
    public function __construct($from, $to, $text) {
        $this->from = $from;
        $this->to = $to;
        $this->text = $text;
    }
    
}