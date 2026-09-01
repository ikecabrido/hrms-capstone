<?php
namespace App\Controllers;

use App\Models\Reply;

class ReplyController
{
    private $replyModel;

    public function __construct()
    {
        $this->replyModel = new Reply();
    }

    public function getRepliesByComment($commentId)
    {
        return $this->replyModel->getRepliesByComment($commentId);
    }

    public function getRepliesByPost($postId)
    {
        return $this->replyModel->getRepliesByPost($postId);
    }

    public function getAllReplies()
    {
        return $this->replyModel->getAllReplies();
    }

    public function addReply($commentId, $postId, $authorId, $content, $authorType = 'employee', $parentReplyId = null, $mentionedUserId = null)
    {
        return $this->replyModel->addReply($commentId, $postId, $authorId, $content, $authorType, $parentReplyId, $mentionedUserId);
    }

    public function deleteReply($replyId)
    {
        return $this->replyModel->deleteReply($replyId);
    }
}
