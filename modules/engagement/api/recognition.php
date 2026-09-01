<?php
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/utils.php';

use App\Controllers\RecognitionController;
use App\Controllers\AwardHistoryController;

if (session_status() === PHP_SESSION_NONE) { session_start(); }
$action = $_GET['action'] ?? 'list';
if (!isset($_SESSION['user']) && empty($_SESSION['employee_id']) && $action !== 'list') {
   jsonResponse(['error' => 'Unauthorized'], 401);
}

$ctrl = new RecognitionController();
$action = $_GET['action'] ?? 'list';
$data = inputData();

try {
    switch ($action) {
        case 'list':
            $data = $ctrl->getRecognitions();
            jsonResponse(['success' => true, 'data' => $data]);
            break;

        // Return performance-based recognition recommendations
        case 'recommendations':
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $data = $ctrl->getRecognitionRecommendations($limit);
            jsonResponse(['success' => true, 'data' => $data]);
            break;

        // Return performance-only leaderboard
        case 'performance_leaderboard':
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $data = $ctrl->getPerformanceLeaderboard($limit);
            jsonResponse(['success' => true, 'data' => $data]);
            break;

        // Return suggested awards for a specific performer
        case 'suggested_awards':
            $employeeId = $_GET['employee_id'] ?? $data['employee_id'] ?? null;
            if (!$employeeId) jsonResponse(['error' => 'employee_id is required'], 400);
            $data = $ctrl->getSuggestedAwardsForPerformer($employeeId);
            jsonResponse(['success' => true, 'data' => $data]);
            break;

        // Generic leaderboard (top recognized employees)
        case 'leaderboard':
            $data = $ctrl->getLeaderboard();
            jsonResponse(['success' => true, 'data' => $data]);
            break;
        case 'send':
            foreach (['receiver_id', 'message', 'points'] as $f) {
                if (!isset($data[$f])) jsonResponse(['error' => "$f is required"], 400);
            }
            $senderEmployeeId = $_SESSION['employee_id'] ?? $_SESSION['user']['employee_id'] ?? null;
            if (!$senderEmployeeId) {
                jsonResponse(['error' => 'Current user is not authenticated'], 400);
            }
            $id = $ctrl->sendRecognition((int)$senderEmployeeId, $data['receiver_id'], $data['message'], (int)$data['points']);
            jsonResponse(['id' => $id], 201);
            break;
        case 'history':
            $employeeId = $_SESSION['user']['employee_id'] ?? null;
            if (!$employeeId) jsonResponse(['error' => 'Unauthorized'], 401);
            jsonResponse(['success' => true, 'data' => $ctrl->getRecognitionHistory($employeeId)]);
            break;

        case 'vote_employee_month':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'POST required'], 405);
            $awardHistoryId = $data['award_history_id'] ?? null;
            if (!$awardHistoryId) jsonResponse(['error' => 'award_history_id is required'], 400);
            $senderUserId = $_SESSION['user']['id']
                ?? $_SESSION['user_id']
                ?? null;
            $senderEmployeeId = $_SESSION['user']['employee_id']
                ?? $_SESSION['employee_id']
                ?? null;
            if (!$senderUserId) jsonResponse(['error' => 'Unauthorized'], 401);
            if (!$senderEmployeeId) jsonResponse(['error' => 'Employee profile not found'], 400);
            if ($ctrl->hasVotedForEmployeeMonth((int)$senderUserId, (int)$awardHistoryId)) {
                jsonResponse(['error' => 'You have already voted this month.'], 403);
            }
            $awardHistoryCtrl = new AwardHistoryController();
            $nomineeEmployeeId = $ctrl->getEmployeeFromAwardHistory((int)$awardHistoryId);
            if (!$nomineeEmployeeId) jsonResponse(['error' => 'Nominee not found'], 404);
            $ctrl->addVotePoints($senderEmployeeId, $nomineeEmployeeId);
            $ctrl->recordEmployeeMonthVote((int)$awardHistoryId, (int)$senderUserId, $nomineeEmployeeId);
            jsonResponse(['success' => true, 'message' => 'Vote recorded']);
            break;

        case 'delete_employee_month':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'POST required'], 405);
            $awardHistoryId = (int)($data['award_history_id'] ?? 0);
            if (!$awardHistoryId) jsonResponse(['error' => 'award_history_id is required'], 400);
            if (!$ctrl->deleteEmployeeMonthNomination($awardHistoryId)) {
                jsonResponse(['error' => 'Nomination not found or already deleted'], 404);
            }
            jsonResponse(['success' => true, 'message' => 'Nomination deleted successfully.']);
            break;

        case 'rewards':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $action = $data['action'] ?? null;
                $result = $ctrl->manageRewardsCatalog($action, $data);
                jsonResponse(['success' => true, 'data' => $result]);
            }
            break;

        case 'assign_badge':
            if (!isset($data['employee_id'], $data['badge_id'])) jsonResponse(['error' => 'employee_id and badge_id are required'], 400);
            $currentUserId = $_SESSION['user']['id']
                ?? $_SESSION['user_id']
                ?? null;
            $performanceScore = $ctrl->getEmployeePerformanceScore((int)$data['employee_id']);
            $result = $ctrl->assignAchievementBadge((int)$data['employee_id'], (int)$data['badge_id'], $currentUserId, $performanceScore);
            jsonResponse(['success' => true, 'data' => $result]);
            break;

        // Comprehensive leaderboard with all sources
        case 'comprehensive_leaderboard':
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $data = $ctrl->getComprehensiveLeaderboard($limit);
            jsonResponse(['success' => true, 'data' => $data]);
            break;

        // Employee of the month candidates
        case 'employee_of_month':
            $month = $_GET['month'] ?? null;
            $year = $_GET['year'] ?? null;
            $currentUserId = $_SESSION['user']['id'] ?? null;
            $data = $ctrl->getEmployeeOfTheMonthCandidates($month, $year, $currentUserId);
            jsonResponse(['success' => true, 'data' => $data]);
            break;

        // Get employee total points
        case 'employee_points':
            $employeeId = $_GET['employee_id'] ?? $data['employee_id'] ?? null;
            if (!$employeeId) jsonResponse(['error' => 'employee_id is required'], 400);
            $data = $ctrl->getEmployeeTotalPoints($employeeId);
            jsonResponse(['success' => true, 'data' => $data]);
            break;

        // Get badge recommendations
        case 'badge_recommendations':
            $employeeId = $_GET['employee_id'] ?? $data['employee_id'] ?? null;
            if (!$employeeId) jsonResponse(['error' => 'employee_id is required'], 400);
            $data = $ctrl->getBadgeRecommendations($employeeId);
            jsonResponse(['success' => true, 'data' => $data]);
            break;

        // Get department leaderboard
        case 'department_leaderboard':
            $department = $_GET['department'] ?? null;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $data = $ctrl->getDepartmentLeaderboard($department, $limit);
            jsonResponse(['success' => true, 'data' => $data]);
            break;


        default:
            jsonResponse(['error' => 'unknown action'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

