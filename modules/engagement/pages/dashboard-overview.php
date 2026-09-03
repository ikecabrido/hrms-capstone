<?php
require_once __DIR__ . '/../../../auth/session.php';
require_once __DIR__ . '/../autoload.php';

use App\Controllers\EmployeeController;


use App\Controllers\SurveyController;
use App\Controllers\RecognitionController;
use App\Controllers\GrievanceController;
use App\Controllers\CommunicationController;
use App\Controllers\SocialController;
use App\Controllers\FeedbackController;
use App\Controllers\GroupController;




$surveyCtrl = new SurveyController();
$recognitionCtrl = new RecognitionController();
$grievanceCtrl = new GrievanceController();
$communicationCtrl = new CommunicationController();
$socialCtrl = new SocialController();
$feedbackCtrl = new FeedbackController();
$employeeCtrl = new EmployeeController();
$groupCtrl = new GroupController();

$payload = $payload ?? [];
$payload['surveys'] = $surveyCtrl->index();
$payload['recognitions'] = $recognitionCtrl->getRecognitions();
$payload['grievances'] = $grievanceCtrl->getGrievances();
$payload['feedback'] = $feedbackCtrl->index();
$payload['announcements'] = $communicationCtrl->getAnnouncements();
$payload['feed'] = $socialCtrl->getPosts();
$payload['employees'] = $employeeCtrl->index();
$payload['groups'] = $groupCtrl->getGroups();
$payload['notifications'] = [];
$payload['notifications'] = $communicationCtrl->getNotifications();


// Data will be loaded via API using JavaScript
// var_dump($payload);
?>  

<div class="module-header">
      <h1>Dashboard Overview</h1>
</div>

  <div class="dashboard-area container-fluid">
    <div class="row">
      <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
        <div class="small-box bg-primary">
          <div class="inner">
            <h3 id="count-announcements"><?= count($payload['announcements'] ?? []) ?></h3>
            <p>Communication</p>
          </div>
          <div class="icon"><i class="fas fa-bullhorn"></i></div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
        <div class="small-box bg-info">
          <div class="inner">
            <h3 id="count-surveys"><?= count($payload['surveys'] ?? []) ?></h3>
            <p>Surveys</p>
          </div>
          <div class="icon"><i class="fas fa-poll"></i></div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
        <div class="small-box bg-danger">
          <div class="inner">
            <h3 id="count-feedback"><?= count($payload['feedback'] ?? []) ?></h3>
            <p>Feedback</p>
          </div>
          <div class="icon"><i class="fas fa-comments"></i></div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
        <div class="small-box bg-success">
          <div class="inner">
            <h3 id="count-recognitions"><?= count($payload['recognitions'] ?? []) ?></h3>
            <p>Recognitions</p>
          </div>
          <div class="icon"><i class="fas fa-award"></i></div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
        <div class="small-box bg-warning">
          <div class="inner">
            <h3 id="count-grievances"><?= count($payload['grievances'] ?? []) ?></h3>
            <p>Grievances</p>
          </div>
          <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
        <div class="small-box bg-secondary">
          <div class="inner">
            <h3 id="count-feed"><?= count($payload['feed'] ?? []) ?></h3>
            <p>Social posts</p>
          </div>
          <div class="icon"><i class="fas fa-users"></i></div>
        </div>
      </div>
      <div class="col-lg-2 col-md-6 col-sm-12 mb-3">
        <div class="small-box bg-indigo">
          <div class="inner">
            <h3 id="count-employees"><?= count($payload['employees'] ?? []) ?></h3>
            <p>Employees</p>
          </div>
          <div class="icon"><i class="fas fa-user-friends"></i></div>
        </div>
      </div>
      <div class="col-lg-2 col-md-6 col-sm-12 mb-3">
        <div class="small-box bg-teal">
          <div class="inner">
            <h3 id="count-groups"><?= count($payload['groups'] ?? []) ?></h3>
            <p>Groups</p>
          </div>
          <div class="icon"><i class="fas fa-object-group"></i></div>
        </div>
      </div>
      <div class="col-lg-2 col-md-6 col-sm-12 mb-3">
        <div class="small-box bg-purple">
          <div class="inner">
            <h3 id="count-notifications"><?= count($payload['notifications'] ?? []) ?></h3>
            <p>Notifications</p>
          </div>
          <div class="icon"><i class="fas fa-bell"></i></div>
        </div>
      </div>
    </div>

    <!-- Quick Overviews -->
    <?php
      $latestGrievances = array_slice($payload['grievances'] ?? [], 0, 3);
      $latestFeed = array_slice($payload['feed'] ?? [], 0, 3);
      $latestSurveys = array_slice($payload['surveys'] ?? [], 0, 3);

      // Prepare Survey analytics: count responses per survey (show last 6 surveys)
      $surveyLabels = [];
      $surveyValues = [];
      $surveysList = array_values($payload['surveys'] ?? []);
      if (!empty($surveysList)) {
        // take last 6 surveys (they are ordered by newest first in controller)
        $slice = array_slice($surveysList, 0, 6);
        foreach ($slice as $s) {
          $sid = $s['eer_survey_id'] ?? $s['id'] ?? null;
          if (empty($sid)) continue;
          $responses = $surveyCtrl->getSurveyResults((int)$sid);
          $count = is_array($responses) ? count($responses) : 0;
          $surveyLabels[] = htmlspecialchars($s['title'] ?? 'Survey ' . $sid);
          $surveyValues[] = $count;
        }
      }

      // Total responses across the surveys shown
      $surveyTotalResponses = array_sum($surveyValues);

      // Prepare Feedback analytics by category
      $feedbackCounts = [];
      foreach ($payload['feedback'] ?? [] as $f) {
        $cat = $f['category'] ?? 'Uncategorized';
        $feedbackCounts[$cat] = ($feedbackCounts[$cat] ?? 0) + 1;
      }
      $feedbackLabels = array_keys($feedbackCounts);
      $feedbackValues = array_values($feedbackCounts);

      // Prepare Grievance analytics by status
      $grievanceCounts = [];
      foreach ($payload['grievances'] ?? [] as $g) {
        $status = $g['status'] ?? 'Unknown';
        $grievanceCounts[$status] = ($grievanceCounts[$status] ?? 0) + 1;
      }
      $grievanceLabels = array_keys($grievanceCounts);
      $grievanceValues = array_values($grievanceCounts);
    ?>
    <div class="row mt-3">
      <div class="col-md-4">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Survey Analytics</h5>
            <span class="badge badge-primary"><?= (int)($surveyTotalResponses ?? 0) ?> total</span>
          </div>
          <div class="card-body" style="min-height:260px;">
            <canvas id="dashboardSurveyChart" data-survey-labels="<?= htmlspecialchars(json_encode($surveyLabels)) ?>" data-survey-values="<?= htmlspecialchars(json_encode($surveyValues)) ?>" style="max-height:240px;"></canvas>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Feedback Analytics</h5>
            <span class="badge badge-primary"><?= count($payload['feedback'] ?? []) ?> total</span>
          </div>
          <div class="card-body" style="min-height:260px;">
            <canvas id="dashboardFeedbackChart" data-feedback-labels="<?= htmlspecialchars(json_encode($feedbackLabels)) ?>" data-feedback-values="<?= htmlspecialchars(json_encode($feedbackValues)) ?>" style="max-height:240px;"></canvas>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Grievances Analytics</h5>
            <span class="badge badge-primary"><?= count($payload['grievances'] ?? []) ?> total</span>
          </div>
          <div class="card-body" style="min-height:260px;">
            <canvas id="dashboardGrievanceChart" data-grievance-labels="<?= htmlspecialchars(json_encode($grievanceLabels)) ?>" data-grievance-values="<?= htmlspecialchars(json_encode($grievanceValues)) ?>" style="max-height:240px;"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="row mt-4">
      <div class="col-md-4">
        <div class="card communication-summary-card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Latest Communications</h5>
            <span class="badge badge-primary"><?= count($payload['announcements'] ?? []) ?> total</span>
          </div>
          <div class="card-body">
            <?php if (!empty($payload['announcements'])): ?>
              <ul class="list-group list-group-flush">
                <?php foreach (array_slice($payload['announcements'], 0, 5) as $announcement): ?>
                  <li class="list-group-item">
                    <strong><?= htmlspecialchars($announcement['title'] ?? 'No title available') ?></strong><br>
                    <small>By: <?= htmlspecialchars($announcement['created_by_name'] ?? $announcement['created_by'] ?? 'Unknown') ?> | <?= htmlspecialchars($announcement['created_at'] ?? 'Unknown') ?></small>
                  </li>
                <?php endforeach; ?>
              </ul>
              <?php if (count($payload['announcements'] ?? []) > 5): ?>
                <div class="mt-3 text-right"><small>Showing latest 5 of <?= count($payload['announcements']) ?></small></div>
              <?php endif; ?>
            <?php else: ?>
              <p class="text-muted mb-0">No communications yet.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card feedback-summary-card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Latest Feedback</h5>
            <span class="badge badge-primary"><?= count($payload['feedback'] ?? []) ?> total</span>
          </div>
          <div class="card-body">
            <?php if (!empty($payload['feedback'])): ?>
              <ul class="list-group list-group-flush">
                <?php foreach (array_slice($payload['feedback'], 0, 5) as $feedback): ?>
                  <li class="list-group-item">
                    <?= htmlspecialchars($feedback['category'] ?? 'Feedback') ?> by <?= htmlspecialchars($feedback['evaluator_type'] ?? 'Self') ?><br>
                    <small>To: <?= htmlspecialchars($feedback['employee_name'] ?? 'Unknown') ?> | <?= htmlspecialchars($feedback['comments'] ?? 'No comments available') ?></small>
                  </li>
                <?php endforeach; ?>
              </ul>
              <?php if (count($payload['feedback'] ?? []) > 5): ?>
                <div class="mt-3 text-right"><small>Showing latest 5 of <?= count($payload['feedback']) ?></small></div>
              <?php endif; ?>
            <?php else: ?>
              <p class="text-muted mb-0">No feedback yet.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card survey-summary-card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Latest Surveys</h5>
            <span class="badge badge-primary"><?= count($payload['surveys'] ?? []) ?> total</span>
          </div>
          <div class="card-body">
            <?php if (!empty($latestSurveys)): ?>
              <ul class="list-group list-group-flush">
                <?php foreach ($latestSurveys as $survey): ?>
                  <li class="list-group-item">
                    <?= htmlspecialchars($survey['title'] ?? 'No title') ?><br>
                    <small>Created by: <?= htmlspecialchars($survey['created_by_name'] ?? $survey['created_by'] ?? 'Unknown') ?></small>
                  </li>
                <?php endforeach; ?>
              </ul>
              <?php if (count($payload['surveys'] ?? []) > 3): ?>
                <div class="mt-3 text-right"><small>Showing latest 3 of <?= count($payload['surveys']) ?></small></div>
              <?php endif; ?>
            <?php else: ?>
              <p class="text-muted">No surveys yet.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="row mt-4">
      <div class="col-md-4">
        <div class="card survey-summary-card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Latest Grievances</h5>
            <span class="badge badge-primary"><?= count($payload['grievances'] ?? []) ?> total</span>
          </div>
          <div class="card-body">
            <?php if (!empty($latestGrievances)): ?>
              <ul class="list-group list-group-flush">
                <?php foreach ($latestGrievances as $grievance): ?>
                  <li class="list-group-item">
                    <strong><?= htmlspecialchars($grievance['subject'] ?? 'No title') ?></strong><br>
                    <small>Status: <?= htmlspecialchars($grievance['status'] ?? 'Unknown') ?> | Filed by: <?= htmlspecialchars($grievance['filed_by'] ?? $grievance['employee_name'] ?? 'Unknown') ?></small>
                  </li>
                <?php endforeach; ?>
              </ul>
              <?php if (count($payload['grievances'] ?? []) > 3): ?>
                <div class="mt-3 text-right"><small>Showing latest 3 of <?= count($payload['grievances']) ?></small></div>
              <?php endif; ?>
            <?php else: ?>
              <p class="text-muted">No grievances yet.</p>
            <?php endif; ?>
          </div>
        </div>
     </div>

      <div class="col-md-4">
        <div class="card survey-summary-card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Latest Social Posts</h5>
            <span class="badge badge-primary"><?= count($payload['feed'] ?? []) ?> total</span>
          </div>
          <div class="card-body">
            <?php if (!empty($latestFeed)): ?>
              <ul class="list-group list-group-flush">
                <?php foreach ($latestFeed as $post): ?>
                  <li class="list-group-item">
                    <?= htmlspecialchars(mb_strimwidth($post['content'] ?? 'No content', 0, 80, '...')) ?><br>
                    <small>By: <?= htmlspecialchars($post['author_name'] ?? $post['employee_name'] ?? 'Unknown') ?></small>
                  </li>
                <?php endforeach; ?>
              </ul>
              <?php if (count($payload['feed'] ?? []) > 3): ?>
                <div class="mt-3 text-right"><small>Showing latest 3 of <?= count($payload['feed']) ?></small></div>
              <?php endif; ?>
            <?php else: ?>
              <p class="text-muted">No social posts yet.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card survey-summary-card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Latest Recognitions</h5>
            <span class="badge badge-primary"><?= count($payload['recognitions'] ?? []) ?> total</span>
          </div>
          <div class="card-body">
            <?php if (!empty($payload['recognitions'])): ?>
              <ul class="list-group list-group-flush">
                <?php foreach ($payload['recognitions'] as $recognition): ?>
                  <li class="list-group-item">
                    <?= htmlspecialchars($recognition['message'] ?? 'No message available') ?><br>
                    <small>From: <?= htmlspecialchars($recognition['sender_name'] ?? 'Unknown') ?> | To: <?= htmlspecialchars($recognition['receiver_name'] ?? 'Unknown') ?> | Points: <?= htmlspecialchars($recognition['points'] ?? '0') ?></small>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <p class="text-muted">No recognitions yet.</p>
            <?php endif; ?>
          </div>
                     
        </div>  
      </div>
    </div>  
  <script src="pages/js/dashboard.js"></script>


