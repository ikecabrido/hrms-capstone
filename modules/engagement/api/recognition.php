<?php
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/utils.php';

use App\Controllers\RecognitionController;
use App\Controllers\AwardHistoryController;
use App\Controllers\RewardController;
use App\Controllers\RewardRedemptionController;
use App\Controllers\BadgeController;
use App\Controllers\EmployeeBadgeController;
use App\Controllers\EmployeeController;
use App\Controllers\CommunicationController;

if (session_status() === PHP_SESSION_NONE) { session_start(); }
engagementApiRequireLogin();
$action = $_GET['action'] ?? 'list';
$isHrAdmin = engagementApiIsHrAdmin();
if (in_array($action, ['adjust_points', 'delete_employee_month', 'rewards', 'assign_badge', 'approve_monthly_top_five'], true) && !$isHrAdmin) {
    jsonResponse(['error' => 'Unauthorized'], 403);
}

$sessionUser = is_array($_SESSION['user'] ?? null) ? $_SESSION['user'] : [];
$currentEmployeeId = $sessionUser['employee_id'] ?? $_SESSION['employee_id'] ?? null;
$currentUserId = $sessionUser['id']
    ?? $sessionUser['user_id']
    ?? $_SESSION['user_id']
    ?? null;
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$ctrl = new RecognitionController();
$action = $_GET['action'] ?? 'list';
$data = inputData();

try {
    if (!$currentUserId) {
        if ($currentEmployeeId) {
            $db = \Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                'SELECT user_id FROM user_account WHERE employee_id = :employee_id LIMIT 1'
            );
            $stmt->execute(['employee_id' => $currentEmployeeId]);
            $currentUserId = $stmt->fetchColumn() ?: null;
        }
    }

    switch ($action) {
        case 'page_data':
            $rewardCtrl = new RewardController();
            $employeeCtrl = new EmployeeController();
            $optionalRewardData = function (callable $loader) {
                try {
                    return $loader();
                } catch (\Throwable $e) {
                    error_log('Optional recognition reward data unavailable: ' . $e->getMessage());
                    return [];
                }
            };
            $recognitionData = [
                'recognitions' => $ctrl->getRecognitions(),
                'leaderboard' => $ctrl->getLeaderboard(),
                'rewards' => $rewardCtrl->index(),
                'reward_redemptions' => array_values(array_filter(
                    (new RewardRedemptionController())->index(),
                    function ($redemption) use ($isHrAdmin, $currentEmployeeId) {
                        return $isHrAdmin || (int)($redemption['employee_id'] ?? 0) === (int)$currentEmployeeId;
                    }
                )),
                'badges' => (new BadgeController())->index(),
                'employee_badges' => (new EmployeeBadgeController())->index(),
                'award_history' => (new AwardHistoryController())->index(),
                'employees' => $employeeCtrl->index(),
                'announcements' => (new CommunicationController())->getRecognitionAnnouncements(),
                'recently_recognized' => $ctrl->getRecentlyRecognizedEmployees(30),
                'comprehensive_leaderboard' => $ctrl->getComprehensiveLeaderboard(10),
                'department_leaderboard' => $ctrl->getDepartmentLeaderboard(null, 10),
                'recognition_recommendations' => $isHrAdmin ? $ctrl->getRecognitionRecommendations(10) : [],
                'performance_leaderboard' => $isHrAdmin ? $ctrl->getPerformanceLeaderboard(10) : [],
                'employees_without_reports' => $isHrAdmin ? $ctrl->getEmployeesWithoutPerformanceReports() : [],
                'performance_candidates' => $isHrAdmin ? $optionalRewardData([$rewardCtrl, 'getPerformanceBasedCandidates']) : [],
                'top_performers' => $isHrAdmin ? $optionalRewardData([$rewardCtrl, 'getTopPerformers']) : [],
                'improvement_candidates' => $isHrAdmin ? $optionalRewardData([$rewardCtrl, 'getImprovementCandidates']) : [],
            ];

            if ($currentEmployeeId) {
                $recognitionData['my_points'] = $ctrl->getEmployeeTotalPoints($currentEmployeeId);
                $recognitionData['my_badge_recommendations'] = $ctrl->getBadgeRecommendations($currentEmployeeId);
            }

            $selectedMonth = (int)($_GET['month'] ?? date('m'));
            $selectedYear = (int)($_GET['year'] ?? date('Y'));
            if ($selectedMonth < 1 || $selectedMonth > 12 || $selectedYear < 2000 || $selectedYear > 2200) {
                jsonResponse(['error' => 'A valid month and year are required'], 400);
            }
            $recognitionData['employee_of_month_candidates'] = $ctrl->getMonthlyRecognitionCycle(
                sprintf('%04d-%02d', $selectedYear, $selectedMonth),
                $isHrAdmin
            );
            $recognitionData['employee_of_month_candidates']['can_approve'] = $isHrAdmin;
            $recognitionData['current_employee_of_month'] = $ctrl->getMonthlyRecognitionCycle(
                date('Y-m'),
                $isHrAdmin
            );

            jsonResponse(['success' => true, 'data' => $recognitionData]);
            break;
        case 'list':
            $data = $ctrl->getRecognitions();
            jsonResponse(['success' => true, 'data' => $data]);
            break;

        // Return performance-based recognition recommendations
        case 'recommendations':
            if (!$isHrAdmin) jsonResponse(['error' => 'Unauthorized'], 403);
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $data = $ctrl->getRecognitionRecommendations($limit);
            jsonResponse(['success' => true, 'data' => $data]);
            break;

        // Return performance-only leaderboard
        case 'performance_leaderboard':
            if (!$isHrAdmin) jsonResponse(['error' => 'Unauthorized'], 403);
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $data = $ctrl->getPerformanceLeaderboard($limit);
            jsonResponse(['success' => true, 'data' => $data]);
            break;

        // Return suggested awards for a specific performer
        case 'suggested_awards':
            if (!$isHrAdmin) jsonResponse(['error' => 'Unauthorized'], 403);
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
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                jsonResponse(['error' => 'POST required'], 405);
            }
            $senderEmployeeId = $currentEmployeeId;
            if (!$senderEmployeeId) {
                jsonResponse(['error' => 'Current user is not authenticated'], 401);
            }
            $receiverId = filter_var($data['receiver_id'] ?? null, FILTER_VALIDATE_INT);
            $points = filter_var($data['points'] ?? null, FILTER_VALIDATE_INT);
            $messageInput = $data['message'] ?? null;
            $message = is_string($messageInput) ? trim($messageInput) : '';
            if ($receiverId === false || $receiverId <= 0 || $points === false || $points <= 0 || $message === '') {
                jsonResponse(['error' => 'A valid recipient, message, and positive points value are required'], 400);
            }
            $id = $ctrl->sendRecognition((int)$senderEmployeeId, $receiverId, $message, $points);
            jsonResponse(['success' => true, 'id' => $id], 201);
            break;
        case 'adjust_points':
            if (!$isHrAdmin) jsonResponse(['error' => 'Unauthorized'], 403);
            if (!isset($data['employee_id'], $data['points'], $data['reason'])) {
                jsonResponse(['error' => 'employee_id, points, and reason are required'], 400);
            }
            $adminEmployeeId = $currentEmployeeId;
            if (!$adminEmployeeId) jsonResponse(['error' => 'Admin employee profile not found'], 400);
            $id = $ctrl->adjustEmployeePoints((int)$data['employee_id'], (int)$data['points'], (string)$data['reason'], (int)$adminEmployeeId);
            jsonResponse(['success' => true, 'id' => $id], 201);
            break;
        case 'history':
            $employeeId = $currentEmployeeId;
            if (!$employeeId) jsonResponse(['error' => 'Unauthorized'], 401);
            jsonResponse(['success' => true, 'data' => $ctrl->getRecognitionHistory($employeeId)]);
            break;

        case 'vote_employee_month':
            jsonResponse(['error' => 'Monthly recognition is based on finalized Overall Ratings and HR approval; voting is no longer used.'], 410);
            break;

        case 'delete_employee_month':
            if (!$isHrAdmin) jsonResponse(['error' => 'Unauthorized'], 403);
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'POST required'], 405);
            $awardHistoryId = (int)($data['award_history_id'] ?? 0);
            if (!$awardHistoryId) jsonResponse(['error' => 'award_history_id is required'], 400);
            if (!$ctrl->deleteEmployeeMonthNomination($awardHistoryId)) {
                jsonResponse(['error' => 'Nomination not found or already deleted'], 404);
            }
            jsonResponse(['success' => true, 'message' => 'Nomination deleted successfully.']);
            break;

        case 'rewards':
            if (!$isHrAdmin) jsonResponse(['error' => 'Unauthorized'], 403);
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $action = $data['action'] ?? null;
                $result = $ctrl->manageRewardsCatalog($action, $data);
                jsonResponse(['success' => true, 'data' => $result]);
            }
            break;

        case 'assign_badge':
            if (!$isHrAdmin) jsonResponse(['error' => 'Unauthorized'], 403);
            if (!isset($data['employee_id'], $data['badge_id'])) jsonResponse(['error' => 'employee_id and badge_id are required'], 400);
            $awardedByEmployeeId = $currentEmployeeId;
            if (!$awardedByEmployeeId && $currentUserId) {
                $db = \Database::getInstance()->getConnection();
                $stmt = $db->prepare(
                    'SELECT employee_id FROM user_account WHERE user_id = :user_id LIMIT 1'
                );
                $stmt->execute(['user_id' => $currentUserId]);
                $awardedByEmployeeId = $stmt->fetchColumn() ?: null;
            }
            if (!$awardedByEmployeeId) {
                jsonResponse(['error' => 'Admin employee profile not found'], 400);
            }
            $performanceScore = $ctrl->getEmployeePerformanceScore((int)$data['employee_id']);
            $result = $ctrl->assignAchievementBadge((int)$data['employee_id'], (int)$data['badge_id'], (int)$awardedByEmployeeId, $performanceScore);
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
            $month = filter_var($_GET['month'] ?? date('m'), FILTER_VALIDATE_INT);
            $year = filter_var($_GET['year'] ?? date('Y'), FILTER_VALIDATE_INT);
            if ($month === false || $month < 1 || $month > 12 || $year === false || $year < 2000 || $year > 2200) {
                jsonResponse(['error' => 'A valid month and year are required'], 400);
            }
            $data = $ctrl->getMonthlyRecognitionCycle(sprintf('%04d-%02d', $year, $month), $isHrAdmin);
            $data['can_approve'] = $isHrAdmin;
            jsonResponse(['success' => true, 'data' => $data]);
            break;

        case 'monthly_top_five':
            $monthYear = $_GET['month_year'] ?? '';
            if (!is_string($monthYear) || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthYear)) {
                jsonResponse(['error' => 'A valid month_year in YYYY-MM format is required'], 400);
            }
            $data = $ctrl->getMonthlyRecognitionCycle($monthYear, $isHrAdmin);
            $data['can_approve'] = $isHrAdmin;
            jsonResponse(['success' => true, 'data' => $data]);
            break;

        case 'approve_monthly_top_five':
            if (!$isHrAdmin) jsonResponse(['error' => 'Unauthorized'], 403);
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') jsonResponse(['error' => 'POST required'], 405);
            $monthYear = $data['month_year'] ?? '';
            $selectedEmployeeIds = $data['selected_employee_ids'] ?? null;
            $employeeOfMonthId = filter_var($data['employee_of_month_id'] ?? null, FILTER_VALIDATE_INT);
            if (!is_string($monthYear) || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthYear)
                || !is_array($selectedEmployeeIds) || $employeeOfMonthId === false || $employeeOfMonthId <= 0) {
                jsonResponse(['error' => 'A valid cycle, employee selections, and Employee of the Month are required'], 400);
            }
            $approverEmployeeId = $currentEmployeeId;
            if (!$approverEmployeeId && $currentUserId) {
                $db = \Database::getInstance()->getConnection();
                $stmt = $db->prepare(
                    'SELECT employee_id FROM user_account WHERE user_id = :user_id LIMIT 1'
                );
                $stmt->execute(['user_id' => $currentUserId]);
                $approverEmployeeId = $stmt->fetchColumn() ?: null;
            }
            if (!$approverEmployeeId) {
                jsonResponse(['error' => 'Admin employee profile not found'], 400);
            }
            try {
                $data = $ctrl->approveMonthlyRecognitionCycle(
                    $monthYear,
                    $selectedEmployeeIds,
                    $employeeOfMonthId,
                    (int)$approverEmployeeId
                );
            } catch (\DomainException $error) {
                jsonResponse(['error' => $error->getMessage()], $error->getCode() ?: 422);
            }
            $data['can_approve'] = true;
            jsonResponse(['success' => true, 'data' => $data]);
            break;

        // Get employee total points
        case 'employee_points':
            $employeeId = $_GET['employee_id'] ?? $data['employee_id'] ?? null;
            if (!$employeeId) jsonResponse(['error' => 'employee_id is required'], 400);
            if (!$isHrAdmin && (int)$employeeId !== (int)$currentEmployeeId) {
                jsonResponse(['error' => 'Unauthorized'], 403);
            }
            $data = $ctrl->getEmployeeTotalPoints($employeeId);
            jsonResponse(['success' => true, 'data' => $data]);
            break;

        // Get badge recommendations
        case 'badge_recommendations':
            $employeeId = $_GET['employee_id'] ?? $data['employee_id'] ?? null;
            if (!$employeeId) jsonResponse(['error' => 'employee_id is required'], 400);
            if (!$isHrAdmin && (int)$employeeId !== (int)$currentEmployeeId) {
                jsonResponse(['error' => 'Unauthorized'], 403);
            }
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
} catch (Throwable $e) {
    $status = $e instanceof \InvalidArgumentException ? 400 : ($e instanceof \RuntimeException ? 409 : 500);
    jsonResponse(['error' => $e->getMessage()], $status);
}
