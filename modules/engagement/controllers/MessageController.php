<?php
namespace App\Controllers;

use App\Models\Message;

class MessageController
{
    private $message;

    public function __construct()
    {
        $this->message = new Message();
    }

    public function sendMessage($sender_id, $receiver_id, $message)
    {
        return $this->message->create(['sender_id' => $sender_id, 'receiver_id' => $receiver_id, 'message' => $message]);
    }

    public function messageThreads($employee_id = null)
    {
        if ($employee_id === null || $employee_id === '') {
            return $this->message->allThreads();
        }

        return $this->message->threads($employee_id);
    }

    public function getMessageHistory($sender_id, $receiver_id)
    {
        return $this->message->getMessageHistory($sender_id, $receiver_id);
    }

    public function getUnreadMessages($employee_id)
    {
        return $this->message->getUnreadMessages($employee_id);
    }
}