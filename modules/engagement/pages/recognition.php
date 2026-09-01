<?php
require_once __DIR__ . '/../../../auth/session.php';
require_once __DIR__ . '/../autoload.php';

use App\Controllers\RecognitionController;
use App\Controllers\GrievanceController;
use App\Controllers\SocialController;
use App\Controllers\SurveyController;
use App\Controllers\FeedbackController;
use App\Controllers\CommunicationController;
use App\Controllers\RewardController;
use App\Controllers\RewardRedemptionController;
use App\Controllers\BadgeController;
use App\Controllers\EmployeeBadgeController;
use App\Controllers\AwardHistoryController;
use App\Controllers\EmployeeController;

$theme = $_SESSION['user']['theme'] ?? 'light';

$ctrl = new RecognitionController();
$grievanceCtrl = new GrievanceController();
$socialCtrl = new SocialController();
$surveyCtrl = new SurveyController();
$feedbackCtrl = new FeedbackController();
$communicationCtrl = new CommunicationController();


$payload = $payload ?? [];
$payload['recognitions'] = $ctrl->getRecognitions();
$payload['leaderboard'] = $ctrl->getLeaderboard();

// Only include Employee Recognition & Rewards tables
$rewardCtrl = new RewardController();
$rewardRedemptionCtrl = new RewardRedemptionController();
$badgeCtrl = new BadgeController();
$employeeBadgeCtrl = new EmployeeBadgeController();
$awardHistoryCtrl = new AwardHistoryController();
$employeeCtrl = new EmployeeController();

$payload['rewards'] = $rewardCtrl->index();
$payload['reward_redemptions'] = $rewardRedemptionCtrl->index();
$payload['badges'] = $badgeCtrl->index();
$payload['employee_badges'] = $employeeBadgeCtrl->index();
$payload['award_history'] = $awardHistoryCtrl->index();
$payload['employees'] = $employeeCtrl->index();
$payload['announcements'] = $communicationCtrl->getRecognitionAnnouncements();
$payload['recently_recognized'] = $ctrl->getRecentlyRecognizedEmployees(30);
$payload['comprehensive_leaderboard'] = $ctrl->getComprehensiveLeaderboard(10);
$payload['department_leaderboard'] = $ctrl->getDepartmentLeaderboard(null, 10);
$payload['recognition_recommendations'] = $ctrl->getRecognitionRecommendations(10);
$payload['performance_leaderboard'] = $ctrl->getPerformanceLeaderboard(10);
$payload['employees_without_reports'] = $ctrl->getEmployeesWithoutPerformanceReports();
$payload['performance_candidates'] = $rewardCtrl->getPerformanceBasedCandidates();
$payload['top_performers'] = $rewardCtrl->getTopPerformers();
$payload['improvement_candidates'] = $rewardCtrl->getImprovementCandidates();

$currentEmployeeId = $_SESSION['user']['employee_id'] ?? null;
$currentUserId = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
if ($currentEmployeeId) {
  $payload['my_points'] = $ctrl->getEmployeeTotalPoints($currentEmployeeId);
  $payload['my_badge_recommendations'] = $ctrl->getBadgeRecommendations($currentEmployeeId);
}

$selectedMonth = (int)($_GET['month'] ?? date('m'));
$selectedYear = (int)($_GET['year'] ?? date('Y'));
$payload['employee_of_month_candidates'] = $ctrl->getEmployeeOfTheMonthCandidates($selectedMonth, $selectedYear, $currentUserId);

// Get nominated employees (for badge assignment filtering)
$nominatedEmployeeIds = [];
foreach ($payload['award_history'] ?? [] as $award) {
  if (strpos($award['award_name'] ?? '', 'Nomination') !== false) {
    if (!in_array($award['employee_id'], $nominatedEmployeeIds)) {
      $nominatedEmployeeIds[] = $award['employee_id'];
    }
  }
}
$payload['nominated_employee_ids'] = $nominatedEmployeeIds;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $currentEmployeeId = $_SESSION['user']['employee_id'] ?? $_SESSION['employee_id'] ?? null;
  $currentUserId = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
  $senderId = $currentUserId ?? $currentEmployeeId;

  if ($senderId) {
    // Handle recognition submission
    if (!empty($_POST['receiver_id']) && !empty($_POST['message'])) {
      $ctrl->sendRecognition($senderId, $_POST['receiver_id'], $_POST['message'], (int)($_POST['points'] ?? 10));
      $_SESSION['flash_success'] = 'Recognition sent successfully.';
    }

    // Handle badge assignment
    if (!empty($_POST['badge_employee_id']) && !empty($_POST['badge_id'])) {
      $performanceScore = null;
      if (!empty($_POST['badge_employee_id'])) {
        $employeeId = (int)$_POST['badge_employee_id'];
        $performanceScore = $ctrl->getEmployeePerformanceScore($employeeId);
      }
      $ctrl->assignAchievementBadge($_POST['badge_employee_id'], $_POST['badge_id'], $currentUserId, $performanceScore);
      $_SESSION['flash_success'] = 'Badge assigned successfully.';
    }

    // Handle adding new reward
    if (!empty($_POST['action']) && $_POST['action'] === 'add_reward' && !empty($_POST['reward_name']) && !empty($_POST['reward_points'])) {
      try {
        $rewardCtrl->store([
          'name' => $_POST['reward_name'],
          'description' => $_POST['reward_description'] ?? '',
          'points_required' => (int)$_POST['reward_points']
        ]);
        $_SESSION['flash_success'] = 'Reward added successfully!';
      } catch (Exception $e) {
        $_SESSION['flash_error'] = 'Error adding reward: ' . $e->getMessage();
      }
    }

    // Handle recognition announcements
    if (!empty($_POST['form_type']) && $_POST['form_type'] === 'recognition_announcement') {
      $title = trim($_POST['title'] ?? '');
      $content = trim($_POST['content'] ?? '');
      if ($title && $content) {
        $communicationCtrl->postRecognitionAnnouncement($title, $content, $senderId);
        $_SESSION['flash_success'] = 'Recognition announcement posted successfully.';
      } else {
        $_SESSION['flash_error'] = 'Title and content are required for recognition announcements.';
      }
    }

    // Handle Employee of the Month nomination
    if (!empty($_POST['nominate_employee_id']) && !empty($_POST['nomination_reason'])) {
      $employee = $employeeCtrl->find($_POST['nominate_employee_id']);
      if ($employee) {
        $awardHistoryCtrl->store([
          'employee_id' => $_POST['nominate_employee_id'],
          'award_name' => 'Employee of the Month Nomination',
          'reason' => $_POST['nomination_reason'],
          'nominated_by' => $senderId,
          'award_type' => 'employee_of_month',
          'month_year' => date('Y-m'),
          'status' => 'nominated'
        ]);
        $_SESSION['flash_success'] = 'Employee nominated for Employee of the Month.';
      }
    }

        // Handle Employee of the Month vote
        if (!empty($_POST['action']) && $_POST['action'] === 'vote_employee_month' && !empty($_POST['award_history_id'])) {
          $awardHistoryId = (int)$_POST['award_history_id'];

          if (!isset($_SESSION['employee_month_votes'])) {
            $_SESSION['employee_month_votes'] = [];
          }

          if (!$ctrl->hasVotedForEmployeeMonth((int)($currentUserId ?? $senderId), $awardHistoryId)) {
            $_SESSION['employee_month_votes'][] = $awardHistoryId;

            $nomineeEmployeeId = $ctrl->getEmployeeFromAwardHistory($awardHistoryId);

            if ($nomineeEmployeeId) {
              $ctrl->addVotePoints($currentEmployeeId ?? $senderId, $nomineeEmployeeId);
              $awardHistoryCtrl->incrementVoteCount($awardHistoryId);
            }

            $_SESSION['flash_success'] = 'Your vote has been recorded! (+5 points awarded to nominee)';
          } else {
            $_SESSION['flash_error'] = 'You have already voted this month.';
          }
        }
      }

      header('Location: ' . $_SERVER['REQUEST_URI']);
      exit;
    }

    $flashSuccess = $_SESSION['flash_success'] ?? null;
    $flashError = $_SESSION['flash_error'] ?? null;
    unset($_SESSION['flash_success'], $_SESSION['flash_error']);

    function getTierLabel($points) {
      if ($points >= 1000) {
        return 'Platinum';
      }
      if ($points >= 750) {
        return 'Gold';
      }
      if ($points >= 500) {
        return 'Silver';
      }
      return 'Bronze';
    }
    ?> 
    <link rel="stylesheet" href="pages/css/style/recognition.css">
    
</body>
 <div class="module-header">
        <h1>Recognitions</h1>
    </div>
<div class="recognition-area">
        <div class="row"> 
          <div class="col-12">
            <?php if ($flashSuccess): ?>
              <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                  <?= htmlspecialchars($flashSuccess) ?>
              </div>
           <?php endif; ?>
           <?php if ($flashError): ?>
             <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                 <?= htmlspecialchars($flashError) ?>
               </div>
            <?php endif; ?>
          </div>
        </div>
      <!-- Main content -->

          <div class="card shadow-sm border-0 recognition-card">
            <div class="card-header p-0 border-0">
              <ul class="nav nav-tabs recognition-nav-tabs" id="recognition-tabs" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="recognition-tab" data-toggle="pill" href="#recognition" role="tab">
                    <i class="fas fa-heart mr-2"></i>Recognition Feed
                  </a>
                </li>
                                <li class="nav-item">
                                  <a class="nav-link" id="employee-month-tab" data-toggle="pill" href="#employee-month" role="tab">
                                    <i class="fas fa-star mr-2"></i>Employee of the Month
                                  </a>
                                </li>

                <li class="nav-item">
                  <a class="nav-link" id="badges-tab" data-toggle="pill" href="#badges" role="tab">
                    <i class="fas fa-medal mr-2"></i>Achievement Badges
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="rewards-tab" data-toggle="pill" href="#rewards" role="tab">
                    <i class="fas fa-gift mr-2"></i>Rewards & Incentives
                  </a>
                </li>

                <li class="nav-item">
                  <a class="nav-link" id="leaderboard-tab" data-toggle="pill" href="#leaderboard" role="tab">
                    <i class="fas fa-trophy mr-2"></i>Points & Leaderboard
                  </a>
                </li>
              </ul>
            </div>

            <div class="card-body recognition-tabs-body">
              <div class="tab-content" id="recognition-tabs-content">

                <!-- Recognition Feed Tab -->
                <div class="tab-pane fade show active" id="recognition" role="tabpanel" aria-labelledby="recognition-tab">
                  <div class="recognition-main-grid">
                    <div class="recognition-left-column">
                      <div class="card card-success card-outline shadow-sm border-0 recognition-feed">
                        <div class="card-header d-flex align-items-center justify-content-between">
                          <h3 class="card-title mb-0"><i class="fas fa-heart mr-2"></i>Recognition Feed</h3>
                        </div>
                        <div class="card-body" id="recognition-feed">
                          <?php if (!empty($payload['recognitions'])): ?>
                            <div class="list-group list-group-flush">
                              <?php foreach ($payload['recognitions'] as $recognition): ?>
                                <div class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-2 shadow-sm">
                                  <div class="d-flex flex-column">
                                    <div class="mb-1">
                                      <strong><?= htmlspecialchars($recognition['sender_name']) ?></strong>
                                      <span class="text-muted">recognized</span>
                                      <strong><?= htmlspecialchars($recognition['receiver_name']) ?></strong>
                                    </div>
                                    <div class="text-muted small mb-1"><i class="fas fa-clock mr-1"></i><?= htmlspecialchars(date('M j, Y H:i', strtotime($recognition['created_at']))) ?></div>
                                    <div class="lh-relaxed">
                                      <?= htmlspecialchars($recognition['message']) ?>
                                    </div>
                                  </div>
                                  <span class="badge badge-success align-self-md-start align-self-end mt-2 mt-md-0">+<?= htmlspecialchars($recognition['points']) ?> pts</span>
                                </div>
                              <?php endforeach; ?>
                            </div>
                          <?php else: ?>
                            <p class="text-muted text-center">No recognitions yet. Be the first to recognize a colleague!</p>
                          <?php endif; ?>
                        </div>
                      </div>

                      <div class="recognition-subgrid mt-3">
                        <div>
                          <!-- Performance Recommendations -->
                          <div class="card card-light card-outline shadow-sm border-0">
                            <div class="card-header">
                              <h3 class="card-title mb-0"><i class="fas fa-thumbs-up mr-2"></i>Performance Recommendations</h3>
                            </div>
                            <div class="card-body p-2" id="performance-recommendations-list">
                              <?php if (!empty($payload['recognition_recommendations'])): ?>
                                <ul class="list-group list-group-flush">
                                  <?php foreach ($payload['recognition_recommendations'] as $recommendation): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                      <div>
                                        <strong><?= htmlspecialchars($recommendation['employee_name'] ?? $recommendation['employee_id'] ?? 'Unknown') ?></strong>
                                        <div class="text-muted small">
                                          <?= htmlspecialchars($recommendation['evaluation_period'] ?? 'Performance Report') ?>
                                          &bull; Grade: <?= htmlspecialchars($recommendation['final_grade'] ?? 'N/A') ?>
                                          &bull; Score: <?= htmlspecialchars($recommendation['final_rating_percent'] ?? 'N/A') ?>%
                                        </div>
                                        <?php if (!empty($recommendation['period_end'])): ?>
                                          <div class="text-muted extra-small">Period end: <?= htmlspecialchars($recommendation['period_end']) ?></div>
                                        <?php endif; ?>
                                      </div>
                                      <button type="button" class="btn btn-sm btn-outline-success recommend-recognize"
                                        data-employee-id="<?= htmlspecialchars($recommendation['employee_id'] ?? '') ?>"
                                        data-employee-name="<?= htmlspecialchars($recommendation['employee_name'] ?? '') ?>">Recognize</button>
                                    </li>
                                  <?php endforeach; ?>
                                </ul>
                              <?php else: ?>
                                <p class="text-muted text-center m-2">No recommendations available yet.</p>
                              <?php endif; ?>
                            </div>
                            <nav id="performance-recommendations-pagination" class="mt-2"></nav>
                          </div>
                        </div>
                        <div>
                          <!-- Employees without Performance Reports -->
                          <div class="card card-secondary card-outline shadow-sm border-0">
                            <div class="card-header">
                              <h3 class="card-title mb-0"><i class="fas fa-user-clock mr-2"></i>Employees without Performance Reports</h3>
                            </div>
                            <div class="card-body p-2">
                              <div id="employees-without-reports-list">
                                <?php if (!empty($payload['employees_without_reports'])): ?>
                                  <ul class="list-group list-group-flush">
                                    <?php foreach ($payload['employees_without_reports'] as $emp): ?>
                                      <li class="list-group-item recognition-summary-item rounded-list-item">
                                        <div class="recognition-summary-content">
                                          <strong><?= htmlspecialchars($emp['employee_name']) ?></strong>
                                          <div class="recognition-summary-meta"><?= htmlspecialchars($emp['department'] ?? 'No department') ?></div>
                                        </div>
                                        <span class="badge badge-danger recognition-status-badge">No Report</span>
                                      </li>
                                    <?php endforeach; ?>
                                  </ul>
                                <?php else: ?>
                                  <p class="text-muted text-center m-2">All employees have performance report data.</p>
                                <?php endif; ?>
                              </div>
                              <nav id="employees-without-reports-pagination" class="mt-2"></nav>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="recognition-right-column">
                      <!-- Quick Stats -->
                      <div class="card card-info card-outline shadow-sm border-0 mb-3">
                        <div class="card-header">
                          <h3 class="card-title mb-0"><i class="fas fa-chart-bar mr-2"></i>Quick Stats</h3>
                        </div>
                        <div class="card-body quick-stats-card">
                          <div class="quick-stats-row">
                            <div class="quick-stats-item">
                              <div class="quick-stats-label">Total Recognitions</div>
                              <div class="quick-stats-value text-info"><?= count($payload['recognitions'] ?? []) ?></div>
                            </div>
                            <div class="quick-stats-item">
                              <div class="quick-stats-label">Points Awarded</div>
                              <div class="quick-stats-value text-success"><?= array_sum(array_column($payload['recognitions'] ?? [], 'points')) ?></div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Recent Recognitions -->
                      <div class="card card-warning card-outline shadow-sm border-0 rounded-xl mb-3">
                        <div class="card-header recent-activity-header">
                          <h3 class="card-title mb-0 recent-activity-title"><i class="fas fa-clock mr-2"></i>Recent Activity</h3>
                        </div>
                        <div class="card-body p-0 recent-activity-scroll">
                          <?php
                          $recentRecognitions = $payload['recognitions'] ?? [];
                          if (!empty($recentRecognitions)):
                          ?>
                            <ul class="list-group list-group-flush">
                              <?php foreach ($recentRecognitions as $recognition): ?>
                                <li class="list-group-item recent-activity-item">
                                  <div class="recent-activity-content">
                                    <strong class="recent-activity-name"><?= htmlspecialchars($recognition['sender_name'] ?? $recognition['sender_id'] ?? 'Unknown employee') ?></strong>
                                    <div class="recent-activity-recipient"><span>recognized</span> <?= htmlspecialchars($recognition['receiver_name'] ?? $recognition['receiver_id'] ?? 'Unknown employee') ?></div>
                                    <small class="recent-activity-time"><i class="fas fa-clock mr-1"></i><?= htmlspecialchars($recognition['created_at'] ?? '') ?></small>
                                  </div>
                                  <span class="badge badge-success recent-activity-badge">+<?= htmlspecialchars($recognition['points'] ?? 0) ?> pts</span>
                                </li>
                              <?php endforeach; ?>
                            </ul>
                          <?php else: ?>
                            <p class="text-muted text-center p-3">No recent activity</p>
                          <?php endif; ?>
                        </div>
                      </div>

                      <!-- Top Performers -->
                      <div class="card card-info card-outline shadow-sm border-0">
                        <div class="card-header">
                          <h3 class="card-title mb-0"><i class="fas fa-chart-line mr-2"></i>Top Performers</h3>
                        </div>
                        <div class="card-body p-2" id="top-performers-list">
                          <?php if (!empty($payload['performance_leaderboard'])): ?>
                            <ul class="list-group list-group-flush">
                              <?php foreach ($payload['performance_leaderboard'] as $performer): ?>
                                <?php $score = $performer['final_rating_percent'] ?? $performer['performance_score'] ?? 'N/A'; $displayScore = is_numeric($score) ? rtrim(rtrim(number_format((float)$score, 1, '.', ''), '0'), '.') : $score; ?>
                                <li class="list-group-item recognition-summary-item">
                                  <div class="recognition-summary-content">
                                    <strong><?= htmlspecialchars($performer['employee_name'] ?? $performer['employee_id'] ?? 'Unknown') ?></strong>
                                    <div class="recognition-summary-meta">
                                      Score: <?= htmlspecialchars($displayScore) ?>%
                                      &bull; Grade: <?= htmlspecialchars($performer['final_grade'] ?? 'N/A') ?>
                                    </div>
                                  </div>
                                  <span class="badge badge-success recognition-score-badge">+<?= htmlspecialchars($displayScore) ?>%</span>
                                </li>
                              <?php endforeach; ?>
                            </ul>
                          <?php else: ?>
                            <p class="text-muted text-center m-2">No top performers found.</p>
                          <?php endif; ?>
                        </div>
                        <nav id="top-performers-pagination" class="mt-2"></nav>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Employee of the Month Tab -->
                <div class="tab-pane fade" id="employee-month" role="tabpanel" aria-labelledby="employee-month-tab">
                  <div class="row">
                    <div class="col-md-8">
                      <div class="card card-warning card-outline">
                        <div class="card-header header-action-card-header">
                          <h3 class="card-title"><i class="fas fa-star mr-2"></i>Employee of the Month Nominations</h3>
                          <div class="card-tools">
                            <button type="button" class="btn btn-warning btn-sm header-action-button" data-recognition-open="nominateEmployeeModal">
                              <i class="fas fa-plus mr-1"></i> Add Nomination
                            </button>
                          </div>
                        </div>
                        <div class="card-body">
                          <form id="employee-month-filter-form" method="GET" class="form-inline mb-3">
                            <label class="mr-2">Month</label>
                            <select id="employee-of-month-month" name="month" class="form-control mr-2 employee-month-select">
                              <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $selectedMonth == $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                              <?php endfor; ?>
                            </select>
                            <label class="mr-2">Year</label>
                            <select id="employee-of-month-year" name="year" class="form-control mr-2 employee-year-select">
                              <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                                <option value="<?= $y ?>" <?= $selectedYear == $y ? 'selected' : '' ?>><?= $y ?></option>
                              <?php endfor; ?>
                            </select>
                            <button type="submit" class="btn btn-sm btn-warning">Apply</button>
                          </form>

                          <?php if (!empty($payload['employee_of_month_candidates'])): ?>
                            <div class="list-group" id="employee-month-candidates-list">
                              <?php foreach ($payload['employee_of_month_candidates'] as $candidate): ?>
                                <?php
                                $hasVoted = isset($candidate['has_voted']) ? (bool)$candidate['has_voted'] : (isset($_SESSION['employee_month_votes']) && in_array($candidate['eer_award_history_id'] ?? null, $_SESSION['employee_month_votes']));
                                $statusBadge = $candidate['status'] === 'winner' ? 'badge-success' : ($candidate['status'] === 'shortlisted' ? 'badge-info' : 'badge-warning');
                                ?>
                                <div class="list-group-item d-flex justify-content-between align-items-start candidate-item" data-award-history-id="<?= htmlspecialchars($candidate['eer_award_history_id']) ?>" data-employee-id="<?= htmlspecialchars($candidate['employee_id']) ?>">
                                  <div>
                                    <strong><?= htmlspecialchars($candidate['employee_name'] ?? 'Unknown') ?></strong><br>
                                    <small class="text-muted">
                                      Department: <?= htmlspecialchars($candidate['department'] ?? 'N/A') ?> • Votes: <?= htmlspecialchars($candidate['votes'] ?? 0) ?> • Performance: <?= htmlspecialchars($candidate['performance_score'] ?? 0) ?>%
                                    </small>
                                    <div class="mt-1">
                                      <span class="badge <?= $statusBadge ?>"><?= htmlspecialchars(ucfirst($candidate['status'] ?? 'nominated')) ?></span>
                                      <?php if (!empty($candidate['nomination_reason'])): ?>
                                        <span class="text-muted small">Reason: <?= htmlspecialchars($candidate['nomination_reason']) ?></span>
                                      <?php endif; ?>
                                    </div>
                                  </div>
                                  <div class="text-right candidate-actions">
                                    <span class="badge badge-info">Recognition: <?= htmlspecialchars($candidate['recognition_total'] ?? 0) ?> pts</span>
                                    <?php if (!empty($candidate['eer_award_history_id'])): ?>
                                      <button type="button" class="btn btn-sm btn-outline-danger delete-nomination mt-2" data-award-history-id="<?= htmlspecialchars($candidate['eer_award_history_id']) ?>"><i class="fas fa-trash"></i> Delete</button>
                                    <?php endif; ?>
                                    <?php if (!empty($candidate['eer_award_history_id']) && !$hasVoted): ?>
                                      <form method="POST" action="" class="mt-2 vote-form">
                                        <input type="hidden" name="action" value="vote_employee_month">
                                        <input type="hidden" name="award_history_id" value="<?= htmlspecialchars($candidate['eer_award_history_id']) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-warning">
                                          <i class="fas fa-vote-yea"></i> Vote +5
                                        </button>
                                      </form>
                                    <?php elseif (empty($candidate['eer_award_history_id'])): ?>
                                      <button type="button" class="btn btn-sm btn-outline-secondary mt-2" disabled>Not Nominated</button>
                                    <?php endif; ?>
                                  </div>
                                </div>
                              <?php endforeach; ?>
                            </div>
                          <?php else: ?>
                            <p class="text-muted text-center">No nominations yet.</p>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>

                    <div class="col-md-4">
                      <!-- Current Employee of the Month -->
                      <div class="card card-warning card-outline">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-crown mr-2"></i>Current Winner</h3>
                        </div>
                        <div class="card-body text-center">
                          <?php
                          // Get the current month's employee of the month
                          $currentMonth = date('Y-m');
                          $currentWinner = null;
                          $winnerEmployee = null;
                          
                          // Search for Employee of the Month nominations for current month
                          foreach ($payload['award_history'] ?? [] as $award) {
                            if (strpos($award['award_name'], 'Employee of the Month') !== false) {
                              $awardMonth = date('Y-m', strtotime($award['created_at'] ?? $award['awarded_at'] ?? date('Y-m-d')));
                              if ($awardMonth === $currentMonth) {
                                $currentWinner = $award;
                                break;
                              }
                            }
                          }
                          
                          // Get the employee details
                          $winnerName = 'Unknown Employee';
                          $winnerInitials = '??';
                          if ($currentWinner) {
                            $winnerEmployee = array_filter($payload['employees'] ?? [], function($emp) use ($currentWinner) {
                              return ($emp['employee_id'] ?? null) == ($currentWinner['employee_id'] ?? null);
                            });
                            $winnerEmployee = reset($winnerEmployee);
                            if ($winnerEmployee) {
                              $winnerName = $winnerEmployee['full_name']
                                  ?? $winnerEmployee['employee_name']
                                  ?? trim(implode(' ', array_filter([
                                      $winnerEmployee['first_name'] ?? null,
                                      $winnerEmployee['middle_name'] ?? null,
                                      $winnerEmployee['last_name'] ?? null,
                                  ])))
                                  ?: 'Unknown Employee';
                                    $winnerNameParts = preg_split('/\s+/', trim($winnerName));
                                    $winnerInitials = strtoupper(substr($winnerNameParts[0] ?? '?', 0, 1) . substr($winnerNameParts[count($winnerNameParts) - 1] ?? '?', 0, 1));
                            }
                          }
                          ?>
                          
                          <?php if ($winnerEmployee): ?>
                            <div class="current-winner-profile">
                              <div class="current-winner-avatar" aria-hidden="true"><?= htmlspecialchars($winnerInitials) ?></div>
                              <h5 class="current-winner-name"><?= htmlspecialchars($winnerName) ?></h5>
                              <p class="current-winner-period"><?= date('F Y') ?></p>
                              <span class="badge badge-warning current-winner-badge"><i class="fas fa-crown mr-1"></i>Employee of the Month</span>
                            </div>
                            <?php if ($currentWinner && !empty($currentWinner['reason'])): ?>
                              <div class="current-winner-reason">
                                <span class="current-winner-reason-label">Reason</span>
                                <span><?= htmlspecialchars($currentWinner['reason']) ?></span>
                              </div>
                            <?php endif; ?>
                          <?php else: ?>
                            <div class="text-center text-muted py-4">
                              <i class="fas fa-trophy fa-3x mb-3 text-warning"></i>
                              <p>No Employee of the Month selected yet for <?= date('F Y') ?></p>
                              <small>Nominations will be announced soon!</small>
                            </div>
                          <?php endif; ?>
                        </div>
                      </div>

                      <!-- Nomination Rules -->
                      <div class="card card-info card-outline">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Nomination Rules</h3>
                        </div>
                        <div class="card-body">
                          <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success mr-2"></i> Any employee can nominate</li>
                            <li><i class="fas fa-check text-success mr-2"></i> Must provide specific reason</li>
                            <li><i class="fas fa-check text-success mr-2"></i> Voting period: 2 weeks</li>
                            <li><i class="fas fa-check text-success mr-2"></i> Winner announced monthly</li>
                          </ul>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Achievement Badges Tab -->
                <div class="tab-pane fade" id="badges" role="tabpanel" aria-labelledby="badges-tab">
                  <div class="row">
                    <div class="col-md-8">
                      <div class="card card-info card-outline">
                        <div class="card-header header-action-card-header">
                          <h3 class="card-title"><i class="fas fa-medal mr-2"></i>Achievement Badges</h3>
                          <div class="card-tools">
                            <button type="button" class="btn btn-info btn-sm header-action-button mr-2" data-recognition-open="createBadgeModal">
                              <i class="fas fa-plus mr-1"></i>Create Badge
                            </button>
                            <button type="button" class="btn btn-info btn-sm header-action-button" data-recognition-open="assignBadgeModal">
                              <i class="fas fa-plus mr-1"></i>Assign Badge
                            </button>
                          </div>
                        </div>
                        <div class="card-body">
                          <div id="badges-feed"></div>
                        </div>
                      </div>
                    </div>

                    <div class="col-md-4">
                      <div class="card card-info card-outline mb-3">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-sparkles mr-2"></i>Recommended for You</h3>
                        </div>
                        <div class="card-body">
                          <?php if (!empty($payload['my_badge_recommendations'])): ?>
                            <ul class="list-group list-group-flush">
                              <?php foreach ($payload['my_badge_recommendations'] as $recommendation): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                  <div>
                                    <strong><?= htmlspecialchars($recommendation['name']) ?></strong>
                                    <div class="text-muted small"><?= htmlspecialchars($recommendation['description'] ?? '') ?></div>
                                  </div>
                                  <span class="badge <?= $recommendation['status'] === 'owned' ? 'badge-success' : 'badge-info' ?>">
                                    <?= htmlspecialchars(ucfirst($recommendation['status'] ?? 'pending')) ?>
                                  </span>
                                </li>
                              <?php endforeach; ?>
                            </ul>
                          <?php else: ?>
                            <p class="text-muted text-center">No badge recommendations available yet.</p>
                          <?php endif; ?>
                        </div>
                      </div>

                      <!-- Employee Badges -->
                      <div class="card card-success card-outline">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-award mr-2"></i>Employee Badges</h3>
                        </div>
                        <div class="card-body">
                          <div id="employee-badges-feed" class="list-group"></div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Rewards & Incentives Tab -->
                <div class="tab-pane fade" id="rewards" role="tabpanel" aria-labelledby="rewards-tab">
                  <div class="row">
                    <div class="col-md-8">
                      <div class="card card-primary card-outline">
                        <div class="card-header header-action-card-header">
                          <h3 class="card-title"><i class="fas fa-gift mr-2"></i>Rewards Catalog</h3>
                          <div class="card-tools">
                            <button type="button" class="btn btn-primary btn-sm header-action-button" data-recognition-open="addRewardModal">
                              <i class="fas fa-plus mr-1"></i>Add Reward
                            </button>
                          </div>
                        </div>
                        <div class="card-body">
                          <div id="rewards-feed"></div>
                        </div>
                      </div>
                    </div>

                    <div class="col-md-4">
                      <!-- Reward Redemptions -->
                      <div class="card card-success card-outline">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-history mr-2"></i>Recent Redemptions</h3>
                        </div>
                        <div class="card-body">
                          <div id="reward-redemptions-feed"></div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Points & Leaderboard Tab -->
                <div class="tab-pane fade" id="leaderboard" role="tabpanel" aria-labelledby="leaderboard-tab">
                  <div class="row">
                    <div class="col-md-8">
                      <div class="card card-warning card-outline">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-trophy mr-2"></i>Comprehensive Leaderboard</h3>
                        </div>
                        <div class="card-body">
                          <?php if (!empty($payload['comprehensive_leaderboard'])): ?>
                            <div class="table-responsive leaderboard-table-scroll">
                              <table class="table table-hover mb-0">
                                <thead>
                                  <tr>
                                    <th>Rank</th>
                                    <th>Employee</th>
                                    <th>Recognition</th>
                                    <th>Performance</th>
                                    <th>Badges</th>
                                    <th>Awards</th>
                                    <th>Total</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <?php foreach (array_slice($payload['comprehensive_leaderboard'], 0, 9) as $entry): ?>
                                    <tr>
                                      <td>
                                        <span class="badge <?= ($entry['rank_position'] ?? 1) <= 3 ? 'badge-warning' : 'badge-secondary' ?>">
                                          <?= htmlspecialchars($entry['rank_position'] ?? 1) ?>
                                        </span>
                                      </td>
                                      <td>
                                        <strong><?= htmlspecialchars($entry['employee_name'] ?? 'Unknown') ?></strong>
                                        <div class="text-muted small"><?= htmlspecialchars($entry['department'] ?? 'N/A') ?></div>
                                      </td>
                                      <td><?= htmlspecialchars($entry['recognition_points'] ?? 0) ?></td>
                                      <td><?= htmlspecialchars($entry['performance_points'] ?? 0) ?></td>
                                      <td><?= htmlspecialchars($entry['badge_points'] ?? 0) ?></td>
                                      <td><?= htmlspecialchars($entry['award_points'] ?? 0) ?></td>
                                      <td><span class="badge badge-success badge-pill"><?= htmlspecialchars($entry['total_points'] ?? 0) ?> pts</span></td>
                                    </tr>
                                  <?php endforeach; ?>
                                </tbody>
                              </table>
                            </div>
                          <?php else: ?>
                            <p class="text-muted text-center">No leaderboard data available yet.</p>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="card card-secondary card-outline">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-users mr-2"></i>Department Leaderboard</h3>
                        </div>
                        <div class="card-body p-0">
                          <?php if (!empty($payload['department_leaderboard'])): ?>
                            <div class="department-leaderboard-scroll">
                              <ul class="list-group list-group-flush">
                                <?php foreach (array_slice($payload['department_leaderboard'], 0, 9) as $deptEntry): ?>
                                  <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                      <strong><?= htmlspecialchars($deptEntry['employee_name'] ?? 'Unknown') ?></strong>
                                      <div class="text-muted small"><?= htmlspecialchars($deptEntry['department'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="text-right">
                                      <span class="badge badge-info">#<?= htmlspecialchars($deptEntry['dept_rank'] ?? 1) ?></span>
                                      <div class="text-muted small"><?= htmlspecialchars($deptEntry['total_points'] ?? 0) ?> pts</div>
                                    </div>
                                  </li>
                                <?php endforeach; ?>
                              </ul>
                            </div>
                            <?php if (count($payload['department_leaderboard']) > 9): ?>
                            <?php endif; ?>
                          <?php else: ?>
                            <p class="text-muted text-center p-3">No department ranking data yet.</p>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>
      </div>
      </div>

<!-- Quick Recognition Modal -->
<div class="modal fade" id="nominateEmployeeModal" tabindex="-1" role="dialog" aria-labelledby="nominateEmployeeModalLabel" aria-hidden="true" data-current-employee-id="<?= htmlspecialchars($_SESSION['employee_id'] ?? $_SESSION['user']['employee_id'] ?? '') ?>">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="nominateEmployeeModalLabel"><i class="fas fa-star mr-2"></i>Add Nomination</h5><button type="button" class="close" data-dismiss="modal" data-recognition-close="nominateEmployeeModal" aria-label="Close"><span>&times;</span></button></div>
    <form id="nomination-form" method="POST" action="" data-skip>
      <div class="modal-body">
        <div class="form-group"><label for="nominate-employee">Employee</label><select id="nominate-employee" name="nominate_employee_id" class="form-control" required><option value="">Select employee</option></select></div>
        <div class="form-group"><label for="nomination-reason">Reason for nomination</label><textarea id="nomination-reason" name="nomination_reason" class="form-control" rows="4" required placeholder="Why should this employee be nominated?"></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal" data-recognition-close="nominateEmployeeModal">Cancel</button><button type="submit" class="btn btn-warning"><i class="fas fa-star mr-1"></i>Submit Nomination</button></div>
    </form>
  </div></div>
</div>

<!-- Create Badge Modal -->
<div class="modal fade" id="createBadgeModal" tabindex="-1" role="dialog" aria-labelledby="createBadgeModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="createBadgeModalLabel"><i class="fas fa-medal mr-2"></i>Create Badge</h5><button type="button" class="close" data-dismiss="modal" data-recognition-close="createBadgeModal" aria-label="Close"><span>&times;</span></button></div>
    <form id="create-badge-form">
      <div class="modal-body">
        <div class="form-group"><label for="create-badge-name">Badge Name</label><input type="text" id="create-badge-name" name="name" class="form-control" required maxlength="100" placeholder="Enter badge name"></div>
        <div class="form-group"><label for="create-badge-description">Description</label><textarea id="create-badge-description" name="description" class="form-control" rows="3" maxlength="500" placeholder="Describe the badge"></textarea></div>
        <div class="form-group"><label for="create-badge-category">Category</label><select id="create-badge-category" name="category" class="form-control" required>
          <option value="achievement">Achievement</option>
          <option value="performance">Performance</option>
          <option value="teamwork">Teamwork</option>
          <option value="service">Service</option>
        </select></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal" data-recognition-close="createBadgeModal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-medal mr-1"></i>Create Badge</button></div>
    </form>
  </div></div>
</div>

<!-- Quick Recognition Modal -->
<div class="modal fade" id="assignBadgeModal" tabindex="-1" role="dialog" aria-labelledby="assignBadgeModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="assignBadgeModalLabel"><i class="fas fa-medal mr-2"></i>Assign Badge</h5><button type="button" class="close" data-dismiss="modal" data-recognition-close="assignBadgeModal" aria-label="Close"><span>&times;</span></button></div>
    <form id="assign-badge-form">
      <div class="modal-body">
        <div class="form-group"><label for="badge_employee_id">Employee</label><select id="badge_employee_id" class="form-control" required><option value="">Select employee</option></select></div>
        <div class="form-group"><label for="badge_id">Badge</label><select id="badge_id" class="form-control" required><option value="">Select badge</option></select></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal" data-recognition-close="assignBadgeModal">Cancel</button><button type="submit" class="btn btn-info"><i class="fas fa-medal mr-1"></i>Assign Badge</button></div>
    </form>
  </div></div>
</div>

<!-- Add Reward Modal -->
<div class="modal fade" id="addRewardModal" tabindex="-1" role="dialog" aria-labelledby="addRewardModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addRewardModalLabel"><i class="fas fa-gift mr-2"></i>Add Reward</h5>
        <button type="button" class="close" data-dismiss="modal" data-recognition-close="addRewardModal" aria-label="Close"><span>&times;</span></button>
      </div>
      <form id="add-reward-form" method="POST" action="">
        <input type="hidden" name="action" value="add_reward">
        <div class="modal-body">
          <div class="form-group">
            <label for="reward-name">Reward Name</label>
            <input type="text" id="reward-name" name="reward_name" class="form-control" required maxlength="150" placeholder="Enter reward name">
          </div>
          <div class="form-group">
            <label for="reward-description">Description</label>
            <textarea id="reward-description" name="reward_description" class="form-control" rows="3" maxlength="500" placeholder="Describe this reward"></textarea>
          </div>
          <div class="form-group mb-0">
            <label for="reward-points">Points Required</label>
            <input type="number" id="reward-points" name="reward_points" class="form-control" min="1" required placeholder="Enter points required">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal" data-recognition-close="addRewardModal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Add Reward</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Quick Recognition Modal -->
<div class="modal fade" id="sendRecognitionModal" tabindex="-1" role="dialog" aria-labelledby="sendRecognitionModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="sendRecognitionModalLabel"><i class="fas fa-award mr-2"></i>Recognize Employee</h5><button type="button" class="close" data-dismiss="modal" data-recognition-close="sendRecognitionModal"><span>&times;</span></button></div>
    <form>
      <div class="modal-body">
        <div class="form-group"><label for="rec-receiver">Employee</label><select id="rec-receiver" class="form-control" required><option value="">Select employee</option></select></div>
        <div class="form-group"><label for="rec-message">Message</label><textarea id="rec-message" class="form-control" rows="4" required placeholder="Why are you recognizing this employee?"></textarea></div>
        <div class="form-group"><label for="rec-points">Points</label><input id="rec-points" class="form-control" type="number" min="1" value="10" required></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal" data-recognition-close="sendRecognitionModal">Cancel</button><button type="submit" id="send-recognition-btn" class="btn btn-success"><i class="fas fa-paper-plane mr-1"></i>Send Recognition</button></div>
    </form>
  </div></div>
</div>

    </div>


</body>

