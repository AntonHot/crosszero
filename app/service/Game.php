<?php

namespace Service;

class Game {

    public $id;

    public $nameGame = 'Крестики-нолики';

    public $countPlayers = 2;

    /** @var Connection */
    public $initiator;

    public $players = [];

    public $figures = [
        '0' => 'X',
        '1' => '0'
    ];

    /** @var State */
    public $state;

    public $status;

    public $whoseMoveKey;

    const PENDING_PLAYERS = 1;
    const PROCESS = 2;
    const ENDED = 3;
    
    public function __construct($connection) {
        $this->id = uniqid();
        $this->initiator = $connection;
        $this->players[0] = $this->initiator;
        $this->status = self::PENDING_PLAYERS;
    }

    public function startCompletedChallenge() {
        if (count($this->players) === $this->countPlayers) {
            $this->status = self::PROCESS;
            $this->state = [
                '0' => ['', '', ''],
                '1' => ['', '', ''],
                '2' => ['', '', '']
            ];
            $this->whoseMoveKey = 0;
        }
    }

    public function getPlayers() {
        $players = [];
        foreach ($this->players as $player) {
            $players[] = [
                'id' => $player->id,
                'name' => $player->name,
            ];
        }
        return $players;
    }

    public function getActivePlayer() {
        $key = $this->whoseMoveKey;
        return [
            'id' => $this->players[$key]->id,
            'name' => $this->players[$key]->name,
            'figure' => $this->figures[$key]
        ];
    }

    public function changeStateGame($newState) {
        $this->state = $newState;
        $this->whoseMoveKey++;
        $this->whoseMoveKey %= count($this->players);
    }
}