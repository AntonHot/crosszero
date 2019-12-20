<?php

namespace Service;

class Connection {
    
    public $id;
    public $name;
    public $resource;
    public $messages = [];
    public $invites = [];
    
    public function __construct($resource) {
        $this->resource = $resource;
        return $this;
    }
    
    public function setId($id) {
        $this->id = $id;
    }
    
    public function setName($name) {
        $this->name = $name;
    }
}