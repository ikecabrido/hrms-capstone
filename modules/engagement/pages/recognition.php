<?php
require_once __DIR__ . '/../../../auth/session.php';
require_once __DIR__ . '/../autoload.php';

use App\Controllers\RecognitionController;

$recognitionCtrl = new RecognitionController();
$payload = $payload ?? [];
$payload['recognitions'] = $recognitionCtrl->getRecognitions();

$validRecognitionTabs = ['recognition', 'employee-month', 'badges', 'rewards', 'leaderboard'];
$resetRecognitionTab = !empty($_SESSION['reset_recognition_tab_on_first_visit']);
if ($resetRecognitionTab) {
  unset($_SESSION['reset_recognition_tab_on_first_visit'], $_SESSION['engagement_recognition_tab']);
  setcookie('engagement_recognition_tab', '', time() - 3600, '/hrms-capstone/modules/engagement/');
  setcookie('engagement:recognition:active-tab', '', time() - 3600, '/hrms-capstone/modules/engagement/');
}

$savedRecognitionTab = $resetRecognitionTab
  ? 'recognition'
  : strtolower(trim((string)($_COOKIE['engagement_recognition_tab'] ?? $_COOKIE['engagement:recognition:active-tab'] ?? '')));
if ($savedRecognitionTab === '') {
  $savedRecognitionTab = strtolower(trim((string)($_SESSION['engagement_recognition_tab'] ?? '')));
}
$activeRecognitionTab = in_array($savedRecognitionTab, $validRecognitionTabs, true) ? $savedRecognitionTab : 'recognition';

$selectedMonth = (int)($_GET['month'] ?? date('m'));
$selectedYear = (int)($_GET['year'] ?? date('Y'));

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
              <ul class="nav nav-tabs recognition-nav-tabs" id="recognition-tabs" role="tablist" data-reset-tab="<?= $resetRecognitionTab ? 'true' : 'false' ?>">
                <li class="nav-item">
                  <a class="nav-link<?= $activeRecognitionTab === 'recognition' ? ' active' : '' ?>" id="recognition-tab" href="#recognition" role="tab" aria-selected="<?= $activeRecognitionTab === 'recognition' ? 'true' : 'false' ?>">
                    <i class="fas fa-heart mr-2"></i>Recognition Feed
                  </a>
                </li>
                                <li class="nav-item">
                                  <a class="nav-link<?= $activeRecognitionTab === 'employee-month' ? ' active' : '' ?>" id="employee-month-tab" href="#employee-month" role="tab" aria-selected="<?= $activeRecognitionTab === 'employee-month' ? 'true' : 'false' ?>">
                                    <i class="fas fa-star mr-2"></i>Employee of the Month
                                  </a>
                                </li>

                <li class="nav-item">
                  <a class="nav-link<?= $activeRecognitionTab === 'badges' ? ' active' : '' ?>" id="badges-tab" href="#badges" role="tab" aria-selected="<?= $activeRecognitionTab === 'badges' ? 'true' : 'false' ?>">
                    <i class="fas fa-medal mr-2"></i>Achievement Badges
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link<?= $activeRecognitionTab === 'rewards' ? ' active' : '' ?>" id="rewards-tab" href="#rewards" role="tab" aria-selected="<?= $activeRecognitionTab === 'rewards' ? 'true' : 'false' ?>">
                    <i class="fas fa-gift mr-2"></i>Rewards & Incentives
                  </a>
                </li>

                <li class="nav-item">
                  <a class="nav-link<?= $activeRecognitionTab === 'leaderboard' ? ' active' : '' ?>" id="leaderboard-tab" href="#leaderboard" role="tab" aria-selected="<?= $activeRecognitionTab === 'leaderboard' ? 'true' : 'false' ?>">
                    <i class="fas fa-trophy mr-2"></i>Points & Leaderboard
                  </a>
                </li>
              </ul>
            </div>

            <div class="card-body recognition-tabs-body">
              <div class="tab-content" id="recognition-tabs-content">

                <!-- Recognition Feed Tab -->
                <div class="tab-pane fade<?= $activeRecognitionTab === 'recognition' ? ' show active' : '' ?>" id="recognition" role="tabpanel" aria-labelledby="recognition-tab">
                  <div class="recognition-main-grid">
                    <div class="recognition-left-column">
                      <div class="card card-success card-outline shadow-sm border-0 recognition-feed recognition-feature-card">
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
                                <div class="text-center m-2">
                                  <p class="text-muted">No performance recommendations available yet.</p>
                                </div>
                              <?php endif; ?>
                            </div>
                            <nav id="performance-recommendations-pagination" class="mt-2"></nav>
                          </div>
                        </div>
                        <div id="employees-without-reports-panel" style="display: none;">
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
                      <div class="card card-info card-outline shadow-sm border-0 mb-3 recognition-quick-stats">
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
                      <div class="card card-warning card-outline shadow-sm border-0 rounded-xl mb-3 recognition-recent-activity">
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
                              <?php $score = $performer['overall_rating'] ?? 'N/A'; $displayScore = is_numeric($score) ? number_format((float)$score, 2, '.', '') : $score; ?>
                                <li class="list-group-item recognition-summary-item">
                                  <div class="recognition-summary-content">
                                    <strong><?= htmlspecialchars($performer['employee_name'] ?? $performer['employee_id'] ?? 'Unknown') ?></strong>
                                    <div class="recognition-summary-meta">
                                    Overall Rating: <?= htmlspecialchars($displayScore) ?>/5.00
                                    &bull; Review period: <?= htmlspecialchars($performer['review_period'] ?? 'N/A') ?>
                                    </div>
                                  </div>
                                <span class="badge badge-success recognition-score-badge"><?= htmlspecialchars($displayScore) ?>/5.00</span>
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
                <div class="tab-pane fade<?= $activeRecognitionTab === 'employee-month' ? ' show active' : '' ?>" id="employee-month" role="tabpanel" aria-labelledby="employee-month-tab">
                  <div class="row">
                    <div class="col-md-8">
                      <div class="card card-warning card-outline">
                        <div class="card-header header-action-card-header">
                          <h3 class="card-title"><i class="fas fa-star mr-2"></i>Top 5 Employees for Recognition</h3>
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

                          <div class="list-group" id="employee-month-candidates-list">
                            <p class="text-muted text-center">Loading finalized performance ratings...</p>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="col-md-4">
                      <!-- Current Employee of the Month -->
                      <div class="card card-warning card-outline">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-crown mr-2"></i>Current Winner</h3>
                        </div>
                        <div class="card-body text-center" id="current-winner-content">
                          <div class="current-winner-empty">
                            <i class="fas fa-trophy text-primary" aria-hidden="true"></i>
                            <p>No approved Employee of the Month yet for <?= date('F Y') ?></p>
                            <small>HR will finalize the selection after reviewing the latest finalized performance reports.</small>
                          </div>
                        </div>
                      </div>

                      <!-- Monthly Recognition Rules -->
                      <div class="card card-info card-outline recognition-rules-card">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Selection Rules</h3>
                        </div>
                        <div class="card-body">
                          <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success mr-2"></i> Recognition Feed recipients lang ang niraranggo</li>
                            <li><i class="fas fa-check text-success mr-2"></i> Finalized Overall Rating lang ang ginagamit; hindi isinasama ang Draft reports</li>
                            <li><i class="fas fa-check text-success mr-2"></i> Kung walang finalized rating, ililista ang employee pagkatapos ng may rating; hindi Recognition Feed points ang batayan ng ranggo</li>
                            <li><i class="fas fa-check text-success mr-2"></i> Employee of the Month ang may pinakamataas na rating</li>
                            <li><i class="fas fa-check text-success mr-2"></i> Kapag approved: Top 1 hanggang Top 5 ay makakakuha ng 50 / 40 / 30 / 20 / 10 points ayon sa ranking; walang dagdag na points ang badges para maiwasan ang dobleng points</li>
                            <li><i class="fas fa-check text-success mr-2"></i> HR ang mag-aapprove at magpapasya sa tie sa ika-5 puwesto</li>
                            <li><i class="fas fa-check text-success mr-2"></i> Sa HR approval, matatala sa Recognition Feed at award history ang napiling employees</li>
                          </ul>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Achievement Badges Tab -->
                <div class="tab-pane fade<?= $activeRecognitionTab === 'badges' ? ' show active' : '' ?>" id="badges" role="tabpanel" aria-labelledby="badges-tab">
                  <div class="row">
                    <div class="col-md-8">
                      <div class="card card-info card-outline recognition-badges-card">
                        <div class="card-header header-action-card-header">
                          <h3 class="card-title"><i class="fas fa-medal mr-2"></i>Achievement Badges</h3>
                        </div>
                        <div class="card-body">
                          <div id="badges-feed"></div>
                        </div>
                      </div>
                    </div>

                    <div class="col-md-4">
                      <div class="card card-info card-outline mb-3">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-cog mr-2"></i>Badge Administration</h3>
                        </div>
                        <div class="card-body">
                          <p class="mb-2">Manage the badge catalog and recognize employees for their achievements.</p>
                          <p class="text-muted small mb-0">Create badges, review their tier and points, then assign them to employees.</p>
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
                <div class="tab-pane fade<?= $activeRecognitionTab === 'rewards' ? ' show active' : '' ?>" id="rewards" role="tabpanel" aria-labelledby="rewards-tab">
                  <div class="row">
                    <div class="col-md-8">
                      <div class="card card-primary card-outline rewards-catalog-card">
                        <div class="card-header header-action-card-header">
                          <h3 class="card-title"><i class="fas fa-gift mr-2"></i>Rewards Catalog</h3>
                          <div class="card-tools">
                            <button type="button" class="btn btn-primary btn-sm header-action-button" data-recognition-open="addRewardModal">
                              <i class="fas fa-plus mr-1"></i>Add Reward
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm header-action-button" data-recognition-open="editRewardModal">
                              <i class="fas fa-edit mr-1"></i>Edit Reward
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
                      <div class="card card-success card-outline reward-redemptions-card">
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
                <div class="tab-pane fade<?= $activeRecognitionTab === 'leaderboard' ? ' show active' : '' ?>" id="leaderboard" role="tabpanel" aria-labelledby="leaderboard-tab">
                  <div class="row">
                    <div class="col-md-8">
                      <div class="card card-warning card-outline recognition-leaderboard-card">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-trophy mr-2"></i>Monthly Points Leaderboard</h3>
                          <div class="text-muted small">Points come from approved recognitions, earned badges, and configured award points. Performance ratings do not add points.</div>
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
                                    <th>Badges</th>
                                    <th>Awards</th>
                                    <th>Total</th>
                                  </tr>
                                </thead>
                                <tbody id="comprehensive-leaderboard-list">
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
                                      <td><?= htmlspecialchars($entry['badge_points'] ?? 0) ?></td>
                                      <td><?= htmlspecialchars($entry['award_points'] ?? 0) ?></td>
                                      <td><span class="badge badge-success badge-pill"><?= htmlspecialchars($entry['total_points'] ?? 0) ?> pts</span></td>
                                    </tr>
                                  <?php endforeach; ?>
                                </tbody>
                              </table>
                            </div>
                          <?php else: ?>
                            <div class="table-responsive leaderboard-table-scroll">
                              <table class="table table-hover mb-0">
                                <thead>
                                  <tr>
                                    <th>Rank</th>
                                    <th>Employee</th>
                                    <th>Recognition</th>
                                    <th>Badges</th>
                                    <th>Awards</th>
                                    <th>Total</th>
                                  </tr>
                                </thead>
                                <tbody id="comprehensive-leaderboard-list">
                                  <tr><td colspan="6" class="text-muted text-center">Loading leaderboard...</td></tr>
                                </tbody>
                              </table>
                            </div>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="card card-secondary card-outline recognition-department-leaderboard-card">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-users mr-2"></i>Department Leaderboard</h3>
                        </div>
                          <div class="card-body p-0" id="department-leaderboard-list">
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
        <div class="form-row">
          <div class="form-group col-md-6"><label for="create-badge-tier">Tier</label><select id="create-badge-tier" name="tier" class="form-control" required>
            <option value="bronze">Bronze</option>
            <option value="silver">Silver</option>
            <option value="gold">Gold</option>
            <option value="platinum">Platinum</option>
          </select></div>
          <div class="form-group col-md-6"><label for="create-badge-points">Points Value</label><input type="number" id="create-badge-points" name="points_value" class="form-control" min="1" value="10" readonly required></div>
        </div>
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
      <form id="add-reward-form" method="POST" action="index.php?page=recognition" data-skip="true">
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

<div class="modal fade" id="editRewardModal" tabindex="-1" role="dialog" aria-labelledby="editRewardModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="editRewardModalLabel"><i class="fas fa-edit mr-2"></i>Edit Reward</h5><button type="button" class="close" data-dismiss="modal" data-recognition-close="editRewardModal" aria-label="Close"><span>&times;</span></button></div>
    <form id="edit-reward-form" data-skip="true">
      <div class="modal-body">
        <div class="form-group"><label for="edit-reward-select">Select Reward</label><select id="edit-reward-select" class="form-control" required><option value="">Select reward</option></select></div>
        <div class="form-group"><label for="edit-reward-name">Reward Name</label><input type="text" id="edit-reward-name" class="form-control" required maxlength="150"></div>
        <div class="form-group"><label for="edit-reward-description">Description</label><textarea id="edit-reward-description" class="form-control" rows="3" maxlength="500"></textarea></div>
        <div class="form-group mb-0"><label for="edit-reward-points">Points Required</label><input type="number" id="edit-reward-points" class="form-control" min="1" required></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal" data-recognition-close="editRewardModal">Cancel</button><button type="submit" class="btn btn-primary">Save Changes</button></div>
    </form>
  </div></div>
</div>

<!-- Quick Recognition Modal -->
<div class="modal fade" id="sendRecognitionModal" tabindex="-1" role="dialog" aria-labelledby="sendRecognitionModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="sendRecognitionModalLabel"><i class="fas fa-award mr-2"></i>Recognize Employee</h5><button type="button" class="close" data-dismiss="modal" data-recognition-close="sendRecognitionModal"><span>&times;</span></button></div>
    <form id="send-recognition-form" data-skip="true">
      <div class="modal-body">
        <div class="form-group"><label for="rec-receiver-name">Employee</label><input type="text" id="rec-receiver-name" class="form-control" readonly aria-readonly="true" placeholder="Select employee from a recommendation"><input type="hidden" id="rec-receiver" required></div>
        <div class="form-group"><label for="rec-message">Message</label><textarea id="rec-message" class="form-control" rows="4" required placeholder="Why are you recognizing this employee?"></textarea></div>
        <div class="form-group"><label for="rec-points">Points</label><input id="rec-points" class="form-control" type="number" min="1" value="10" required></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal" data-recognition-close="sendRecognitionModal">Cancel</button><button type="submit" id="send-recognition-btn" class="btn btn-success"><i class="fas fa-paper-plane mr-1"></i>Send Recognition</button></div>
    </form>
  </div></div>
</div>

    </div>


</body>
