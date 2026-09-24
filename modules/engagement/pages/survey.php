<?php
require_once __DIR__ . '/../../../auth/session.php';
require_once __DIR__ . '/../autoload.php';

use App\Controllers\SurveyController;
use App\Controllers\EmployeeController;

$payload = $payload ?? [];
$surveyController = new SurveyController();
$payload['surveys'] = $surveyController->index();
$employeeController = new EmployeeController();
$payload['employees'] = $employeeController->index();

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
          <div class="card shadow-sm border-0" id="survey-tabs-card">
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
                    <div class="col-lg-7">
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
                          <div id="satisfaction-surveys-list">
                            <?php
                              $satisfactionSurveys = array_filter($payload['surveys'], static function ($survey) {
                                return strtolower((string)($survey['survey_type'] ?? 'satisfaction')) === 'satisfaction';
                              });
                            ?>
                            <?php if (empty($satisfactionSurveys)): ?>
                              <p class="text-muted">No satisfaction surveys yet.</p>
                            <?php else: ?>
                              <div class="list-group">
                                <?php foreach ($satisfactionSurveys as $survey): ?>
                                  <div class="list-group-item survey-result-row">
                                    <div class="survey-result-details">
                                      <strong><?= htmlspecialchars($survey['title'] ?? '') ?></strong><br>
                                      <small class="text-muted">Created: <?= htmlspecialchars($survey['created_at'] ?? 'N/A') ?> | Anonymous: <?= !empty($survey['is_anonymous']) ? 'Yes' : 'No' ?></small>
                                    </div>
                                    <a class="btn btn-sm btn-info survey-result-action" href="/hrms-capstone/modules/engagement/pages/survey_view.php?module=survey&amp;action=view&amp;id=<?= (int)($survey['eer_survey_id'] ?? 0) ?>">View</a>
                                  </div>
                                <?php endforeach; ?>
                              </div>
                            <?php endif; ?>
                          </div>
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
                          <div id="pulse-surveys-list">
                            <?php
                              $pulseSurveys = array_filter($payload['surveys'], static function ($survey) {
                                return strtolower((string)($survey['survey_type'] ?? '')) === 'pulse';
                              });
                            ?>
                            <?php if (empty($pulseSurveys)): ?>
                              <div class="text-center text-muted py-4">
                                <i class="fas fa-bolt fa-3x mb-3 text-warning"></i>
                                <p>No active pulse surveys</p>
                                <small>Create your first pulse survey to get quick feedback from employees</small>
                              </div>
                            <?php else: ?>
                              <div class="row">
                                <?php foreach ($pulseSurveys as $survey): ?>
                                  <div class="col-md-6 mb-3">
                                    <div class="card border-warning"><div class="card-body pulse-result-row">
                                      <div class="pulse-result-details">
                                        <h6 class="card-title"><?= htmlspecialchars($survey['title'] ?? '') ?></h6>
                                        <p class="card-text small text-muted">Created: <?= htmlspecialchars($survey['created_at'] ?? 'N/A') ?><br>Anonymous: <?= !empty($survey['is_anonymous']) ? 'Yes' : 'No' ?></p>
                                      </div>
                                      <a class="btn btn-sm btn-outline-info pulse-result-action" href="/hrms-capstone/modules/engagement/pages/survey_view.php?module=survey&amp;action=view&amp;id=<?= (int)($survey['eer_survey_id'] ?? 0) ?>">Take Survey</a>
                                    </div></div>
                                  </div>
                                <?php endforeach; ?>
                              </div>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- HR Feedback Tab -->
               <div class="tab-pane fade" id="hr-feedback" role="tabpanel" aria-labelledby="hr-feedback-tab">
                  <div class="row feedback-layout-row">
                      <div class="col-lg-7">
                      <div class="card card-primary card-outline">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-user-tie mr-2"></i>Provide Feedback to Employee</h3>
                        </div>
                        <div class="card-body">
                          <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>HR Feedback:</strong> This section is for the HR team to give feedback directly to selected employees.
                          </div>

                          <form method="post" class="hr-feedback-form" data-skip="true">
                            <input type="hidden" name="action" value="hr_feedback">
                            <div class="form-group">
                              <label for="feedback-employee">Select Employee</label>
                              <select id="feedback-employee" name="employee_id" class="form-control" required>
                                <option value="">Select an employee</option>
                                <?php foreach ($payload['employees'] as $employee): ?>
                                  <?php
                                    $employeeName = $employee['full_name'] ?? trim(implode(' ', array_filter([
                                      $employee['first_name'] ?? '',
                                      $employee['middle_name'] ?? '',
                                      $employee['last_name'] ?? ''
                                    ])));
                                    $employeeName = $employeeName !== '' ? $employeeName : 'Employee #' . ($employee['employee_id'] ?? '');
                                  ?>
                                  <option value="<?= (int)($employee['employee_id'] ?? 0) ?>"><?= htmlspecialchars($employeeName) ?></option>
                                <?php endforeach; ?>
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
                            <div class="feedback-form-actions">
                              <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane mr-2"></i>Submit Feedback
                              </button>
                              <button type="button" class="btn btn-outline-primary feedback-history-toggle" aria-expanded="false" aria-controls="feedbackHistoryDetails">
                                <i class="fas fa-clock mr-1"></i>Feedback History
                              </button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>

                    <div id="feedbackHistoryDetails" class="feedback-history-modal" hidden role="dialog" aria-modal="true" aria-labelledby="feedbackHistoryModalTitle">
                      <div class="feedback-history-modal-dialog">
                        <div class="feedback-history-modal-header">
                          <h3 id="feedbackHistoryModalTitle"><i class="fas fa-history mr-2"></i>Feedback History</h3>
                          <button type="button" class="feedback-history-close" aria-label="Close feedback history">&times;</button>
                        </div>
                        <div class="feedback-history-modal-body">
                          <div id="feedback-history-list" class="table-responsive">
                            <p class="text-muted text-center py-4 mb-0">Loading feedback history...</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <style>
                  #feedbackHistoryDetails {
                    position: fixed;
                    inset: 0;
                    z-index: 1060;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 1.25rem;
                    background: rgba(12, 32, 50, 0.5);
                  }

                  #feedbackHistoryDetails[hidden] {
                    display: none !important;
                  }

                  #feedbackHistoryDetails .feedback-history-modal-dialog {
                    width: min(1180px, 100%) !important;
                    max-height: min(760px, calc(100vh - 2.5rem)) !important;
                    overflow: hidden !important;
                    background: #ffffff !important;
                    border: 1px solid rgba(23, 95, 154, 0.18) !important;
                    border-radius: 12px !important;
                    box-shadow: 0 15px 40px rgba(14, 39, 64, 0.22) !important;
                  }

                  #feedbackHistoryDetails .feedback-history-modal-header {
                    display: flex !important;
                    align-items: center !important;
                    justify-content: space-between !important;
                    padding: 1rem 1.35rem !important;
                    background: linear-gradient(180deg, #1a9ad8 0%, #0f7ec0 100%) !important;
                    color: #ffffff !important;
                  }

                  #feedbackHistoryDetails .feedback-history-modal-header h3 {
                    margin: 0 !important;
                    font-size: 1.05rem !important;
                    font-weight: 700 !important;
                    color: #ffffff !important;
                  }

                  #feedbackHistoryDetails .feedback-history-close {
                    width: 38px !important;
                    height: 38px !important;
                    padding: 0 !important;
                    border: 0 !important;
                    border-radius: 50% !important;
                    background: rgba(255, 255, 255, 0.18) !important;
                    color: #ffffff !important;
                    font-size: 1.8rem !important;
                  }

                  #feedbackHistoryDetails .feedback-history-modal-body {
                    max-height: calc(min(760px, 100vh - 2.5rem) - 70px);
                    overflow: auto;
                    background: #ffffff;
                    padding: 0;
                  }

                  #feedback-history-list table {
                    width: 100%;
                    border-collapse: collapse;
                    background: #fff;
                  }

                  #feedback-history-list th {
                    background: #f5f9ff;
                    color: #3a5879;
                    font-size: 0.8rem;
                    font-weight: 800;
                    text-transform: uppercase;
                    letter-spacing: 0.04em;
                    text-align: left;
                    padding: 0.9rem 1rem;
                    border-bottom: 1px solid #dfeaf4;
                  }

                  #feedback-history-list td {
                    padding: 0.9rem 1rem;
                    border-bottom: 1px solid #e7eef7;
                    color: #1b3c64;
                    vertical-align: top;
                    line-height: 1.5;
                  }

                  #feedback-history-list td[colspan="4"] {
                    background: #fbfdff;
                    padding-top: 0.5rem;
                    padding-bottom: 1.15rem;
                  }

                  #feedback-history-list td[colspan="4"] strong {
                    color: #0f3e71;
                    font-weight: 800;
                  }

                  body.feedback-history-modal-open {
                    overflow: hidden;
                  }
                </style>

                <script>
                  document.addEventListener('DOMContentLoaded', function () {
                    const toggle = document.querySelector('.feedback-history-toggle');
                    const modal = document.getElementById('feedbackHistoryDetails');
                    if (!toggle || !modal) return;

                    const closeButton = modal.querySelector('.feedback-history-close');
                    const dialog = modal.querySelector('.feedback-history-modal-dialog');

                    const setHistoryState = function (isOpen) {
                      modal.hidden = !isOpen;
                      document.body.classList.toggle('feedback-history-modal-open', isOpen);
                      toggle.setAttribute('aria-expanded', String(isOpen));
                      toggle.innerHTML = isOpen
                        ? '<i class="fas fa-times mr-1"></i>Hide History'
                        : '<i class="fas fa-clock mr-1"></i>Feedback History';
                    };

                    toggle.addEventListener('click', function () {
                      setHistoryState(modal.hidden);
                    });

                    if (closeButton) {
                      closeButton.addEventListener('click', function () {
                        setHistoryState(false);
                      });
                    }

                    modal.addEventListener('click', function (event) {
                      if (event.target === modal) {
                        setHistoryState(false);
                      }
                    });

                    document.addEventListener('keydown', function (event) {
                      if (event.key === 'Escape' && !modal.hidden) {
                        setHistoryState(false);
                      }
                    });

                    if (dialog) {
                      dialog.addEventListener('click', function (event) {
                        event.stopPropagation();
                      });
                    }
                  });
                </script>

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
                                  <form method="post" class="suggestion-form" data-skip="true">
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

                              <div class="suggestions-container" id="suggestions-list"><p class="text-muted">Loading suggestions...</p></div>
                            </div>

                            <div class="col-md-4">
                              <h5>Suggestion Analytics</h5>
                              <div class="card mb-3">
                                <div class="card-header">
                                  <h6 class="card-title mb-0">By Category</h6>
                                </div>
                                <div class="card-body">
                                  <div id="suggestion-category-analytics"><p class="text-muted small">Loading categories...</p></div>
                                </div>
                              </div>

                              <div class="card">
                                <div class="card-header">
                                  <h6 class="card-title mb-0">Quality Insights</h6>
                                </div>
                                <div class="card-body small">
                                  <div id="suggestion-quality-analytics"><p class="text-muted small">Loading insights...</p></div>
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
   