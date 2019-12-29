<?php

namespace Service;

class GameQueue {
    
    public $challenges;

    public function __construct() {
        $this->challenges = [];
    }

    public function addGame($game) {
        $this->challenges[] = $game;
    }

    public function searchGameById($gameId) {
        foreach ($this->challenges as $game) {
            if ($game->id === $gameId) {
                return $game;
            }
        }
        return false;
    }
    
}
