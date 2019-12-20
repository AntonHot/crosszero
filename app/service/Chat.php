<?php

namespace Service;

use Exception;
use Service\Message\{TextMessage};

class Chat {
    
    const TEXT_MESSAGE = 100;
    const SYSTEM_MESSAGE = 101;
    const GAME_MESSAGE = 102;
    
    /** @var array(Member) */
    protected $members = [];
    
    /** @var array(Message) */
    protected $messageHistory = [];
    
    /**
     * @param $message
        // $message = [
        //     'type' => 100, # text
        //     'from' => 'sdjfnwlkenfwe', # phpsessid
        //     'to' => '', # '' = all, 'phpsessid' = user
        //     'text' => ''
        // ];
     */
    public function handle($message) {
        $response = [];
        $type = $message->type;
        switch ($type) {
            case self::TEXT_MESSAGE:
                $from = $this->members[$message->from];
                if (empty(trim($message->to))) {
                    $to = $this->members;
                } else {
                    $to = [$this->members[$message->to]];
                }
                return new TextMessage($from, $to, $message->text);
                break;
            case self::SYSTEM_MESSAGE:
                
                break;
            case self::GAME_MESSAGE:
                
                break;
            default:
                throw new Exception('Unknown type message');
                break;
        }
    }
    
    /**
     * @return array $message
     */
    public function create($type) {
        
    }
    
    // public function createChatMessage($username, $textMessage, $members) {
    //     $tempMembers = [];
    //     foreach ($members as $id => $info) {
    //         $tempMembers[] = [
    //             'id' => $info['id'],
    //             'username' => $info['username']
    //         ];
    //     }
    //     $message = [
    //         'user' => $username,
    //         'message' => $textMessage,
    //         'members' => $tempMembers
    //     ];
    //     return seal(json_encode($message));
    // }
}