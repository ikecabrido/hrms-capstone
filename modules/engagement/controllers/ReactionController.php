<?php
namespace App\Controllers;

use App\Models\Reaction;

class ReactionController
{
    private $reactionModel;

    public function __construct()
    {
        $this->reactionModel = new Reaction();
    }

    public function addReaction($postId, $employeeId, $userId, $type)
    {
        return $this->reactionModel->addReaction($postId, $employeeId, $userId, $type);
    }

    public function getReactionsByPost($postId)
    {
        return $this->reactionModel->getReactionsByPost($postId);
    }

    public function removeReaction($reactionId)
    {
        $this->reactionModel->removeReaction($reactionId);
    }

    public function getReactionAnalytics($postId)
    {
        return $this->reactionModel->getAnalytics($postId);
    }
}