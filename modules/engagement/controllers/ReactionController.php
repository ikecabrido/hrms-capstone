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

    public function addReaction($postId, $employeeId, $userId, $type, $targetType = 'post', $targetId = null)
    {
        return $this->reactionModel->addReaction($postId, $employeeId, $userId, $type, $targetType, $targetId);
    }

    public function getReactionsByPost($postId)
    {
        return $this->reactionModel->getReactionsByPost($postId);
    }

    public function getReactionCounts($targetType, $targetId)
    {
        return $this->reactionModel->getReactionCounts($targetType, $targetId);
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