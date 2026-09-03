<?php
require_once __DIR__ . '/../../../auth/session.php';
require_once __DIR__ . '/../autoload.php';

use App\Controllers\SurveyController;
use App\Controllers\FeedbackController;
use App\Controllers\SurveyAnswerController;

$surveyCtrl = new SurveyController();
$feedbackCtrl = new FeedbackController();
$surveyAnswerCtrl = new SurveyAnswerController();

$payload = $payload ?? [];
$payload['surveys'] = $surveyCtrl->index();
$payload['feedback'] = $feedbackCtrl->index();
$payload['survey_answers'] = $payload['survey_answers'] ?? [];

// Fetch survey answers for a specific survey or response
$surveyId = null;
$responseId = null;
if (isset($_GET['response_id']) && is_numeric($_GET['response_id'])) {
    $responseId = (int)$_GET['response_id'];
} elseif (isset($_GET['survey_id']) && is_numeric($_GET['survey_id'])) {
    $surveyId = (int)$_GET['survey_id'];
} elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $surveyId = (int)$_GET['id'];
}

if ($responseId !== null) {
    $payload['survey_answers'] = $surveyAnswerCtrl->getByResponse($responseId);
} elseif ($surveyId !== null) {
    $payload['survey_answers'] = $surveyAnswerCtrl->getBySurvey($surveyId);
} else {
    $payload['survey_answers'] = $surveyAnswerCtrl->getAll();
}

// Fetch survey results for a specific survey
if (isset($_GET['action']) && $_GET['action'] === 'view_results' && isset($_GET['survey_id'])) {
    $surveyId = (int)$_GET['survey_id'];
    $results = $surveyCtrl->getSurveyResults($surveyId);
    $payload['survey_results'] = $results;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flashSuccess = '';
    $flashError = '';

    if (!empty($_POST['action']) && $_POST['action'] === 'hr_feedback') {
        // Handle HR feedback submission
        $feedbackData = [
            'employee_id' => $_POST['employee_id'] ?? null,
            'comment' => $_POST['comments'] ?? '',
            'category' => $_POST['category'] ?? 'general',
            'rating' => !empty($_POST['rating']) ? (int)$_POST['rating'] : null,
            'evaluator_type' => 'HR',
            'is_anonymous' => 0,
            'allow_followup' => 1
        ];

        try {
            $feedbackCtrl->store(
                $feedbackData['employee_id'],
                $feedbackData['comment'],
                $feedbackData['rating'],
                $feedbackData['evaluator_type'],
                $feedbackData['category'],
                $feedbackData['is_anonymous']
            );
            $_SESSION['flash_success'] = 'Feedback submitted successfully to the employee.';
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Error submitting feedback. Please try again.';
        }
    } elseif (!empty($_POST['action']) && $_POST['action'] === 'submit_suggestion') {
        $suggestionComment = trim($_POST['comment'] ?? '');
        $suggestionCategory = $_POST['category'] ?? 'other';
        $suggestionRating = !empty($_POST['rating']) ? (int)$_POST['rating'] : null;
        $isAnonymous = !empty($_POST['is_anonymous']) ? 1 : 0;
        $employeeId = $_SESSION['user']['employee_id'] ?? $_SESSION['user']['id'] ?? null;

        if ($suggestionComment === '') {
            $_SESSION['flash_error'] = 'Please add a suggestion before submitting.';
        } elseif (empty($employeeId)) {
            $_SESSION['flash_error'] = 'Unable to identify the submitting employee.';
        } else {
            try {
                $feedbackCtrl->store(
                    $employeeId,
                    $suggestionComment,
                    $suggestionRating,
                    'Suggestion',
                    $suggestionCategory,
                    $isAnonymous
                );
                $_SESSION['flash_success'] = 'Suggestion submitted successfully.';
            } catch (Exception $e) {
                $_SESSION['flash_error'] = 'Error submitting suggestion. Please try again.';
            }
        }
    } elseif (!empty($_POST['action']) && $_POST['action'] === 'delete') {
        $surveyId = (int) ($_POST['survey_id'] ?? 0);

        if ($surveyId > 0) {
            $deleted = $surveyCtrl->delete($surveyId);
            if ($deleted) {
                $_SESSION['flash_success'] = 'Survey deleted successfully.';
            } else {
                $_SESSION['flash_error'] = 'Failed to delete survey. It may not exist.';
            }
        } else {
            $_SESSION['flash_error'] = 'Invalid survey ID for deletion.';
        }
    } elseif (!empty($_POST['action']) && $_POST['action'] === 'create_survey') {
        $submittedToken = $_POST['survey_form_token'] ?? '';
        $expectedToken = $_SESSION['survey_form_token'] ?? '';

        if ($submittedToken === '' || !hash_equals($expectedToken, $submittedToken)) {
            $_SESSION['flash_error'] = 'This survey submission has already been processed.';
        } else {
            unset($_SESSION['survey_form_token']);

            // Handle survey creation
            $title = trim($_POST['title'] ?? '');
            $questions = array_map('trim', explode("\n", $_POST['questions_raw'] ?? ''));
            $questions = array_filter($questions, static function ($question) {
              return $question !== '';
            });

            if ($title === '' || empty($questions)) {
              $_SESSION['flash_error'] = 'Title and at least one question are required.';
            } else {
              $formatted = array_map(function ($q) {
                return ['question_text' => $q];
              }, $questions);

              $surveyData = [
                'title' => $title,
                'description' => $_POST['description'] ?? '',
                'survey_type' => $_POST['survey_type'] ?? 'satisfaction',
                'is_anonymous' => isset($_POST['is_anonymous']) ? 1 : 0
              ];

              $employeeId = (int)($_SESSION['user']['employee_id'] ?? $_SESSION['employee_id'] ?? $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);
              if ($employeeId > 0) {
                try {
                  $surveyCtrl->store($surveyData, $formatted, $employeeId);
                  $payload['surveys'] = $surveyCtrl->index();
                  $surveyTypeName = $surveyData['survey_type'] === 'pulse' ? 'Pulse Survey' : 'Satisfaction Survey';
                  $_SESSION['flash_success'] = $surveyTypeName . ' created successfully.';
                } catch (Exception $e) {
                  $_SESSION['flash_error'] = 'Error creating survey: ' . $e->getMessage();
                }
              } else {
                $_SESSION['flash_error'] = 'User authentication required. Employee ID was not found in the current session.';
              }
            }
        }
    } elseif (!empty($_POST['title']) && !empty($_POST['questions_raw'])) {
        $_SESSION['flash_error'] = 'Title and at least one question are required.';
    }

    // Redirects are handled client-side after successful submit so the page can
    // reload cleanly within the engagement layout without triggering header warnings.
}

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$surveyFormToken = $_SESSION['survey_form_token'] ?? '';
if ($surveyFormToken === '') {
  $surveyFormToken = bin2hex(random_bytes(16));
  $_SESSION['survey_form_token'] = $surveyFormToken;
}

?>
<div class="survey-area container-fluid">
  <div class="module-header">
        <h1>Survey</h1>
    </div>

    <div class="module-content">
      <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success" role="alert"><?= htmlspecialchars($flashSuccess) ?></div>
      <?php endif; ?>
      <?php if (!empty($flashError)): ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($flashError) ?></div>
      <?php endif; ?>

      <!-- Main survey tab navigation and content -->
      <div class="row">
        <div class="col-12">
          <div class="card shadow-sm border-0" id="survey-tabs-card" style="visibility:hidden">
            <div class="card-header p-0">
              <ul class="nav nav-tabs survey-nav-tabs" id="survey-tabs" role="tablist">
                <li class="nav-item">
                  <a class="nav-link" id="satisfaction-tab" data-toggle="tab" href="#satisfaction" role="tab" aria-controls="satisfaction" aria-selected="false">
                    <i class="fas fa-chart-line mr-2"></i>Employee Satisfaction Surveys
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="pulse-tab" data-toggle="tab" href="#pulse" role="tab" aria-controls="pulse" aria-selected="false">
                    <i class="fas fa-bolt mr-2"></i>Pulse Surveys
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="hr-feedback-tab" data-toggle="tab" href="#hr-feedback" role="tab" aria-controls="hr-feedback" aria-selected="false">
                    <i class="fas fa-user-tie mr-2"></i>HR Feedback
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="suggestions-tab" data-toggle="tab" href="#suggestions" role="tab" aria-controls="suggestions" aria-selected="false">
                    <i class="fas fa-lightbulb mr-2"></i>Suggestions & Ideas
                  </a>
                </li>
              </ul>
            </div>
            <div class="card-body survey-tabs-body">
              <div class="tab-content" id="survey-tab-content">

                  

                <!-- Employee Satisfaction Surveys Tab -->
                <div class="tab-pane fade" id="satisfaction" role="tabpanel" aria-labelledby="satisfaction-tab">
                  <div class="row">
                    <div class="col-12">
                      <div class="card card-secondary card-outline">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Create Employee Satisfaction Survey</h3>
                          <div class="card-tools">

                          </div>
                        </div>
                        <div class="card-body">
                          <form method="post" action="?page=survey" class="survey-form" data-skip="true">
                            <input type="hidden" name="action" value="create_survey">
                            <input type="hidden" name="survey_type" value="satisfaction">
                            <input type="hidden" name="survey_form_token" value="<?= htmlspecialchars($surveyFormToken) ?>">
                            <div class="form-group">
                              <label for="survey-title">Survey Title</label>
                              <input id="survey-title" type="text" name="title" class="form-control" placeholder="Enter survey title" required>
                            </div>
                            <div class="form-group">
                              <label for="survey-description">Description (Optional)</label>
                              <textarea id="survey-description" name="description" class="form-control" rows="2" placeholder="Brief description of the survey purpose"></textarea>
                            </div>
                            <div class="form-group">
                              <label for="survey-questions">Questions (one per line)</label>
                              <textarea id="survey-questions" name="questions_raw" class="form-control" rows="5" placeholder="How satisfied are you with your work environment?
How would you rate your work-life balance?
What improvements would you suggest?" required></textarea>
                              <small class="form-text text-muted">Enter each question on a new line</small>
                            </div>
                            <div class="form-check">
                              <input class="form-check-input" type="checkbox" id="is-anonymous" name="is_anonymous" value="1">
                              <label class="form-check-label" for="is-anonymous">
                                Allow anonymous responses
                              </label>
                            </div>
                            <button class="btn btn-success" type="submit">Create Satisfaction Survey</button>
                          </form>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-12">
                      <div class="card card-info card-outline">
                        <div class="card-header"><h3 class="card-title">Available Satisfaction Surveys</h3></div>
                        <div class="card-body">
                          <?php
                          $satisfactionSurveys = array_filter($payload['surveys'] ?? [], function($survey) {
                            return ($survey['survey_type'] ?? 'satisfaction') === 'satisfaction';
                          });
                          ?>
                          <?php if (!empty($satisfactionSurveys)): ?>
                            <div class="list-group">
                              <?php foreach ($satisfactionSurveys as $survey): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                  <div>
                                    <strong><?=htmlspecialchars($survey['title'])?></strong><br>
                                    <small class="text-muted">
                                      Created: <?=htmlspecialchars($survey['created_at'] ?? 'N/A')?> |
                                      Anonymous: <?=($survey['is_anonymous'] ?? 0) ? 'Yes' : 'No'?>
                                    </small>
                                  </div>
                                  <div class="btn-group" role="group">
                                    <a class="btn btn-sm btn-info" href="/hrms-capstone/modules/engagement/pages/survey_view.php?module=survey&action=view&id=<?= (int)($survey['eer_survey_id'] ?? 0) ?>">View</a>
                                  </div>
                                </div>
                              <?php endforeach; ?>
                            </div>
                          <?php else: ?>
                            <p class="text-muted">No satisfaction surveys yet.</p>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Pulse Surveys Tab -->
                <div class="tab-pane fade" id="pulse" role="tabpanel" aria-labelledby="pulse-tab">
                  <div class="row">
                    <div class="col-12">
                      <div class="card card-warning card-outline">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-bolt mr-2"></i>Quick Pulse Surveys</h3>
                          <div class="card-tools">

                          </div>
                        </div>
                        <div class="card-body">
                          <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Pulse Surveys</strong> are short, frequent surveys designed to quickly gauge employee sentiment on specific topics.
                          </div>

                          <form method="post" action="?page=survey" class="pulse-survey-form" data-skip="true">
                            <input type="hidden" name="action" value="create_survey">
                            <input type="hidden" name="survey_type" value="pulse">
                            <input type="hidden" name="survey_form_token" value="<?= htmlspecialchars($surveyFormToken) ?>">
                            <div class="form-group">
                              <label for="pulse-title">Pulse Survey Title</label>
                              <input id="pulse-title" type="text" name="title" class="form-control" placeholder="e.g., How are you feeling about the new policy?" required>
                            </div>
                            <div class="form-group">
                              <label for="pulse-question">Single Question</label>
                              <input id="pulse-question" type="text" name="questions_raw" class="form-control" placeholder="On a scale of 1-5, how satisfied are you with...?" required>
                              <small class="form-text text-muted">Pulse surveys should have only one focused question</small>
                            </div>
                            <div class="form-check">
                              <input class="form-check-input" type="checkbox" id="pulse-anonymous" name="is_anonymous" value="1" checked>
                              <label class="form-check-label" for="pulse-anonymous">
                                Allow anonymous responses (recommended for pulse surveys)
                              </label>
                            </div>
                            <button class="btn btn-warning" type="submit">Create Pulse Survey</button>
                          </form>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-12">
                      <div class="card card-light card-outline">
                        <div class="card-header"><h3 class="card-title">Active Pulse Surveys</h3></div>
                        <div class="card-body">
                          <?php
                          $pulseSurveys = array_filter($payload['surveys'] ?? [], function($survey) {
                            return ($survey['survey_type'] ?? '') === 'pulse';
                          });
                          ?>
                          <?php if (!empty($pulseSurveys)): ?>
                            <div class="row">
                              <?php foreach ($pulseSurveys as $survey): ?>
                                <div class="col-md-6 mb-3">
                                  <div class="card border-warning">
                                    <div class="card-body">
                                      <h6 class="card-title"><?=htmlspecialchars($survey['title'])?></h6>
                                      <p class="card-text small text-muted">
                                        Created: <?=htmlspecialchars($survey['created_at'] ?? 'N/A')?><br>
                                        Anonymous: <?=($survey['is_anonymous'] ?? 0) ? 'Yes' : 'No'?>
                                      </p>
                                      <div class="btn-group btn-group-sm">
                                        <a class="btn btn-outline-info" href="/hrms-capstone/modules/engagement/pages/survey_view.php?module=survey&action=view&id=<?= (int)($survey['eer_survey_id'] ?? 0) ?>">Take Survey</a>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              <?php endforeach; ?>
                            </div>
                          <?php else: ?>
                            <div class="text-center text-muted py-4">
                              <i class="fas fa-bolt fa-3x mb-3 text-warning"></i>
                              <p>No active pulse surveys</p>
                              <small>Create your first pulse survey to get quick feedback from employees</small>
                            </div>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- HR Feedback Tab -->
               <div class="tab-pane fade" id="hr-feedback" role="tabpanel" aria-labelledby="hr-feedback-tab">
                  <div class="row">
                    <div class="col-12">
                      <div class="card card-primary card-outline">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-user-tie mr-2"></i>Provide Feedback to Employee</h3>
                        </div>
                        <div class="card-body">
                          <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>HR Feedback:</strong> This section is for the HR team to give feedback directly to selected employees.
                          </div>

                          <form method="post" class="hr-feedback-form">
                            <input type="hidden" name="action" value="hr_feedback">
                            <div class="form-group">
                              <label for="feedback-employee">Select Employee</label>
                              <select id="feedback-employee" name="employee_id" class="form-control" required>
                                <option value="">Select an employee</option>
                                <?php
                                use App\Controllers\EmployeeController;
                                $employeeCtrl = new EmployeeController();
                                $employees = $employeeCtrl->index();
                                foreach ($employees as $employee): {
                                  $firstName = trim((string)($employee['first_name'] ?? ''));
                                  $middleName = trim((string)($employee['middle_name'] ?? ''));
                                  $lastName = trim((string)($employee['last_name'] ?? ''));
                                  $employeeCode = trim((string)($employee['employee_code'] ?? ''));
                                  $displayName = trim((string)($employee['full_name'] ?? $employee['name'] ?? ''));

                                  if ($displayName === '') {
                                      $nameParts = array_filter([$firstName, $middleName, $lastName], function ($part) {
                                          return $part !== '';
                                      });
                                      $displayName = implode(' ', $nameParts);
                                  }

                                  if ($displayName === '') {
                                      $displayName = $employeeCode !== '' ? 'Employee ' . $employeeCode : 'Employee #' . ($employee['employee_id'] ?? 'Unknown');
                                  }

                                  $department = trim((string)($employee['department'] ?? $employee['department_name'] ?? ''));
                                  $departmentLabel = $department !== '' ? ' (' . htmlspecialchars($department) . ')' : '';
                                  ?>
                                  <option value="<?= (int)($employee['employee_id'] ?? 0) ?>"><?= htmlspecialchars($displayName) ?><?= $departmentLabel ?></option>
                                <?php }
                                endforeach; ?>
                              </select>
                            </div>
                            <div class="form-group">
                              <label for="feedback-category">Category</label>
                              <select id="feedback-category" name="category" class="form-control" required>
                                <option value="">Select a category</option>
                                <option value="performance">Performance</option>
                                <option value="behavior">Behavior</option>
                                <option value="skills">Skills Development</option>
                                <option value="teamwork">Teamwork</option>
                                <option value="leadership">Leadership</option>
                                <option value="other">Other</option>
                              </select>
                            </div>
                            <div class="form-group">
                              <label for="feedback-rating">Overall Rating</label>
                              <select id="feedback-rating" name="rating" class="form-control" required>
                                <option value="">Select rating</option>
                                <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                                <option value="4">⭐⭐⭐⭐ Very Good</option>
                                <option value="3">⭐⭐⭐ Good</option>
                                <option value="2">⭐⭐ Fair</option>
                                <option value="1">⭐ Poor</option>
                              </select>
                            </div>
                            <div class="form-group">
                              <label for="hr-feedback">Feedback Comments</label>
                              <textarea id="hr-feedback" name="comments" class="form-control" rows="5" placeholder="Provide detailed feedback to help the employee improve." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                              <i class="fas fa-paper-plane mr-2"></i>Submit Feedback
                            </button>
                          </form>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>


                <!-- Suggestions & Ideas Tab -->
                <div class="tab-pane fade" id="suggestions" role="tabpanel" aria-labelledby="suggestions-tab">
                  <div class="row">
                    <div class="col-12">
                      <div class="card card-primary card-outline">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-lightbulb mr-2"></i>Suggestions & Improvement Ideas</h3>
                        </div>
                        <div class="card-body">
                          <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Improvement Ideas:</strong> This section collects suggestions for workplace improvements from anonymous feedback and survey responses.
                          </div>

                          <div class="row">
                            <div class="col-md-8">
                              <div class="card mb-4">
                                <div class="card-header">
                                  <h6 class="card-title mb-0">Submit a Suggestion</h6>
                                </div>
                                <div class="card-body">
                                  <form method="post" class="suggestion-form">
                                    <input type="hidden" name="action" value="submit_suggestion">
                                    <div class="form-group">
                                      <label for="suggestion-comment">Suggestion or Idea</label>
                                      <textarea id="suggestion-comment" name="comment" class="form-control" rows="4" placeholder="Share an improvement idea, recommendation, or feedback." required></textarea>
                                    </div>
                                    <div class="form-row">
                                      <div class="form-group col-md-6">
                                        <label for="suggestion-category">Category</label>
                                        <select id="suggestion-category" name="category" class="form-control">
                                          <option value="work_environment">Work Environment</option>
                                          <option value="management">Management</option>
                                          <option value="policies">Company Policies</option>
                                          <option value="colleagues">Colleague Relations</option>
                                          <option value="compensation">Compensation & Benefits</option>
                                          <option value="work_life_balance">Work-Life Balance</option>
                                          <option value="other">Other</option>
                                        </select>
                                      </div>
                                      <div class="form-group col-md-3">
                                        <label for="suggestion-rating">Rating</label>
                                        <select id="suggestion-rating" name="rating" class="form-control">
                                          <option value="">Optional rating</option>
                                          <option value="5">5 ⭐ Excellent</option>
                                          <option value="4">4 ⭐ Very good</option>
                                          <option value="3">3 ⭐ Good</option>
                                          <option value="2">2 ⭐ Fair</option>
                                          <option value="1">1 ⭐ Poor</option>
                                        </select>
                                      </div>
                                      <div class="form-group col-md-3 d-flex align-items-end">
                                        <div class="form-check mb-0">
                                          <input class="form-check-input" type="checkbox" id="suggestion-anonymous" name="is_anonymous" value="1">
                                          <label class="form-check-label" for="suggestion-anonymous">Submit anonymously</label>
                                        </div>
                                      </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Save Suggestion</button>
                                  </form>
                                </div>
                              </div>

                              <h5>Recent Suggestions</h5>
                              <div class="mb-3">
                                <div class="d-flex flex-wrap align-items-center">
                                  <div class="btn-group mr-3 mb-2" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-primary sort-btn" data-sort="newest">Newest First</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary sort-btn" data-sort="highest">Highest Rated</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary sort-btn" data-sort="lowest">Lowest Rated</button>
                                  </div>
                                  <div class="form-group mb-0 flex-fill" style="min-width:240px; max-width:320px;">
                                    <label for="category-filter" class="sr-only">Filter by category</label>
                                    <select id="category-filter" class="form-control form-control-sm">
                                      <option value="">All Categories</option>
                                      <option value="work_environment">Work Environment</option>
                                      <option value="management">Management</option>
                                      <option value="policies">Company Policies</option>
                                      <option value="colleagues">Colleague Relations</option>
                                      <option value="compensation">Compensation & Benefits</option>
                                      <option value="work_life_balance">Work-Life Balance</option>
                                      <option value="other">Other</option>
                                    </select>
                                  </div>
                                </div>
                              </div>

                              <?php
                              $suggestions = array_filter($payload['feedback'] ?? [], function($feedback) {
                                $comment = strtolower($feedback['comment'] ?? '');
                                $category = strtolower($feedback['category'] ?? '');
                                $evaluatorType = strtolower($feedback['evaluator_type'] ?? '');

                                $suggestionCategories = [
                                  'work_environment',
                                  'management',
                                  'policies',
                                  'colleagues',
                                  'compensation',
                                  'work_life_balance',
                                  'other'
                                ];

                                return in_array($category, $suggestionCategories, true)
                                    || $evaluatorType === 'suggestion'
                                    || strpos($comment, 'suggest') !== false
                                    || strpos($comment, 'improve') !== false
                                    || strpos($comment, 'better') !== false
                                    || strpos($comment, 'recommend') !== false;
                              });
                              ?>

                              <?php if (!empty($suggestions)): ?>
                                <div class="suggestions-container">
                                  <?php foreach (array_slice($suggestions, 0, 15) as $suggestion): ?>
                                    <div class="card mb-3 suggestion-item" data-category="<?php echo htmlspecialchars($suggestion['category'] ?? 'other'); ?>" data-rating="<?php echo ($suggestion['rating'] ?? 0); ?>">
                                      <div class="card-body pb-2">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                          <span class="badge badge-pill badge-success"><?php echo ucfirst(str_replace('_', ' ', $suggestion['category'] ?? 'general')); ?></span>
                                          <div class="text-right">
                                            <small class="text-muted">Rating: <?php echo ($suggestion['rating'] ?? 'N/A'); ?> ⭐</small>
                                          </div>
                                        </div>
                                        <p class="mb-2"><?php echo nl2br(htmlspecialchars($suggestion['comment'] ?? '')); ?></p>
                                        <small class="text-muted">
                                          <i class="fas fa-user-secret mr-1"></i><?php echo ($suggestion['is_anonymous'] ?? 0) ? 'Anonymous Submission' : 'From: ' . htmlspecialchars($suggestion['employee_name'] ?? 'Unknown'); ?>
                                          | <i class="fas fa-calendar mr-1"></i><?php echo htmlspecialchars($suggestion['evaluation_date'] ?? 'Recent'); ?>
                                        </small>
                                      </div>
                                    </div>
                                  <?php endforeach; ?>
                                </div>
                                <?php if (count($suggestions) > 15): ?>
                                  <button class="btn btn-sm btn-outline-primary" id="load-more-suggestions">Load More Suggestions</button>
                                <?php endif; ?>
                              <?php else: ?>
                                <div class="text-center text-muted py-4">
                                  <i class="fas fa-lightbulb fa-3x mb-3 text-warning"></i>
                                  <p>No suggestions collected yet</p>
                                  <small>Suggestions will appear here as employees submit feedback with improvement ideas</small>
                                </div>
                              <?php endif; ?>
                            </div>

                            <div class="col-md-4">
                              <h5>Suggestion Analytics</h5>
                              <div class="card mb-3">
                                <div class="card-header">
                                  <h6 class="card-title mb-0">By Category</h6>
                                </div>
                                <div class="card-body">
                                  <?php
                                  $categoryCount = [];
                                  foreach ($suggestions as $suggestion) {
                                    $category = $suggestion['category'] ?? 'other';
                                    $categoryCount[$category] = ($categoryCount[$category] ?? 0) + 1;
                                  }
                                  arsort($categoryCount);
                                  ?>

                                  <?php if (!empty($categoryCount)): ?>
                                    <?php foreach (array_slice($categoryCount, 0, 7) as $category => $count): ?>
                                      <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span><?php echo ucfirst(str_replace('_', ' ', $category)); ?></span>
                                        <span class="badge badge-primary"><?php echo $count; ?></span>
                                      </div>
                                    <?php endforeach; ?>
                                  <?php else: ?>
                                    <p class="text-muted small">No categories yet</p>
                                  <?php endif; ?>
                                </div>
                              </div>

                              <div class="card">
                                <div class="card-header">
                                  <h6 class="card-title mb-0">Quality Insights</h6>
                                </div>
                                <div class="card-body small">
                                  <?php
                                  $totalSuggestions = count($suggestions);
                                  $highQualitySuggestions = count(array_filter($suggestions, function($s) {
                                    return ($s['rating'] ?? 0) >= 4;
                                  }));
                                  $suggestionQuality = $totalSuggestions > 0 ? ($highQualitySuggestions / $totalSuggestions) * 100 : 0;
                                  $avgSuggestionRating = !empty($suggestions) ? array_sum(array_column($suggestions, 'rating')) / count($suggestions) : 0;
                                  ?>
                                  <div class="mb-2">
                                    <strong>Total Suggestions:</strong><br>
                                    <span class="text-primary"><?php echo $totalSuggestions; ?></span>
                                  </div>
                                  <div class="mb-2">
                                    <strong>Avg Quality Rating:</strong><br>
                                    <span class="text-info"><?php echo number_format($avgSuggestionRating, 1); ?> ⭐</span>
                                  </div>
                                  <div>
                                    <strong>Quality Suggestions:</strong><br>
                                    <span class="text-success"><?php echo $highQualitySuggestions; ?> (<?php echo number_format($suggestionQuality, 0); ?>%)</span>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>



              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
    </div>
  <!-- Create Satisfaction Survey Modal -->