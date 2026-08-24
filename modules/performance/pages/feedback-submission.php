<?php
require_once __DIR__ . '/../classes/FeedbackController.php';
require_once __DIR__ . '/../../../database/db.php';

$feedbackController = new FeedbackController();
$messages = $feedbackController->getMessages();

// Get assignment ID from URL
$assignmentId = (int) ($_GET['assignment_id'] ?? 0);

if ($assignmentId <= 0) {
    $assignment = null;
    $responses = [];
    $questions = [];
} else {
    $assignment = $feedbackController->getAssignment($assignmentId);
    $responses = $feedbackController->getFeedbackResponses($assignmentId);
    $questions = $feedbackController->getFeedbackQuestions();
    
    if (!$assignment) {
        $assignment = null;
    }
}

// Get competencies for grouping
$competencies = $feedbackController->getCompetencies();
$competenciesMap = [];
foreach ($competencies as $comp) {
    $competenciesMap[$comp['competency_id']] = $comp;
}
?>

<link rel="stylesheet" href="css/pages/360-degree-feedback.css">

<div class="feedback-form-container">
    <!-- Header -->
    <div class="form-header">
        <div class="breadcrumb">
            <a href="?page=360-degree-feedback">360-Degree Feedback</a> /
            <span>Provide Feedback</span>
        </div>
        
        <h1>Provide Your Feedback</h1>
        <p>Help us understand performance and development areas through structured feedback</p>
    </div>

    <?php if (!empty($messages['success'])): ?>
        <div class="alert alert-success alert-dismissible" role="alert">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($messages['success']) ?>
            <button type="button" class="btn-close" onclick="this.parentElement.style.display='none';"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($messages['error'])): ?>
        <div class="alert alert-error alert-dismissible" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($messages['error']) ?>
            <button type="button" class="btn-close" onclick="this.parentElement.style.display='none';"></button>
        </div>
    <?php endif; ?>

    <?php if ($assignment): ?>
        <!-- Assignment Information Card -->
        <div class="assignment-card">
            <div class="card-header">
                <h2>Feedback Assignment Details</h2>
            </div>
            <div class="card-content">
                <div class="assignment-info-grid">
                    <div class="info-item">
                        <span class="info-label">Employee:</span>
                        <span class="info-value"><?= htmlspecialchars($assignment['employee_name'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Department:</span>
                        <span class="info-value"><?= htmlspecialchars($assignment['department'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Your Role:</span>
                        <span class="info-value">
                            <span class="badge"><?= htmlspecialchars($assignment['rater_type_name'] ?? 'N/A') ?></span>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status:</span>
                        <span class="info-value">
                            <span class="badge badge-<?= strtolower(str_replace(' ', '-', $assignment['status'] ?? 'pending')) ?>">
                                <?= htmlspecialchars($assignment['status'] ?? 'Pending') ?>
                            </span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feedback Form -->
        <form method="POST" class="feedback-submission-form" id="feedbackForm">
            <input type="hidden" name="action" value="submit_feedback">
            <input type="hidden" name="assignment_id" value="<?= (int) $assignmentId ?>">

            <?php if (!empty($questions)): ?>
                <?php 
                // Group questions by competency
                $groupedQuestions = [];
                foreach ($questions as $q) {
                    $compId = $q['competency_id'] ?? 0;
                    if (!isset($groupedQuestions[$compId])) {
                        $groupedQuestions[$compId] = [];
                    }
                    $groupedQuestions[$compId][] = $q;
                }
                ?>

                <?php foreach ($groupedQuestions as $compId => $qList): ?>
                    <?php $compName = $compId && isset($competenciesMap[$compId]) ? $competenciesMap[$compId]['competency_name'] : 'General Feedback'; ?>
                    
                    <div class="form-section">
                        <div class="section-header">
                            <h3><?= htmlspecialchars($compName) ?></h3>
                            <span class="section-meta"><?= count($qList) ?> questions</span>
                        </div>

                        <div class="questions-list">
                            <?php foreach ($qList as $index => $question): ?>
                                <?php 
                                // Find existing response if any
                                $existingResponse = null;
                                foreach ($responses as $resp) {
                                    if ($resp['question_id'] == $question['question_id']) {
                                        $existingResponse = $resp;
                                        break;
                                    }
                                }
                                ?>

                                <div class="question-item">
                                    <div class="question-header">
                                        <label class="question-text">
                                            <span class="question-number"><?= $index + 1 ?></span>
                                            <?= htmlspecialchars($question['question_text']) ?>
                                        </label>
                                    </div>

                                    <?php if ($question['question_type'] === 'Rating'): ?>
                                        <!-- Rating Scale -->
                                        <div class="rating-scale">
                                            <?php for ($i = $question['scale_min']; $i <= $question['scale_max']; $i++): ?>
                                                <label class="rating-option">
                                                    <input type="radio" 
                                                           name="responses[<?= (int) $question['question_id'] ?>][rating]" 
                                                           value="<?= $i ?>"
                                                           <?= ($existingResponse && $existingResponse['rating'] == $i) ? 'checked' : '' ?>>
                                                    <span class="rating-label"><?= $i ?></span>
                                                </label>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="rating-scale-info">
                                            <span class="scale-min">Not Aligned</span>
                                            <span class="scale-max">Highly Aligned</span>
                                        </div>
                                    <?php elseif ($question['question_type'] === 'Text'): ?>
                                        <!-- Text Response -->
                                        <textarea name="responses[<?= (int) $question['question_id'] ?>][text]" 
                                                  class="question-textarea"
                                                  rows="4"
                                                  placeholder="Please provide your feedback..."><?= htmlspecialchars($existingResponse['text_response'] ?? '') ?></textarea>
                                    <?php endif; ?>

                                    <!-- Comments/Notes -->
                                    <div class="question-comments">
                                        <label for="comment-<?= (int) $question['question_id'] ?>">
                                            <i class="fas fa-sticky-note"></i>
                                            Additional Comments (Optional)
                                        </label>
                                        <input type="text" 
                                               id="comment-<?= (int) $question['question_id'] ?>"
                                               name="responses[<?= (int) $question['question_id'] ?>][comment]"
                                               class="question-comment-input"
                                               placeholder="Any specific examples or observations..."
                                               value="<?= htmlspecialchars($existingResponse['comment'] ?? '') ?>">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Overall Feedback Section -->
                <div class="form-section overall-section">
                    <div class="section-header">
                        <h3>Overall Feedback</h3>
                    </div>

                    <div class="form-group">
                        <label for="overallStrengths">Key Strengths *</label>
                        <textarea id="overallStrengths" 
                                  name="overall_strengths"
                                  rows="4"
                                  placeholder="What are this person's key strengths?"
                                  required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="overallImprovements">Areas for Improvement *</label>
                        <textarea id="overallImprovements" 
                                  name="overall_improvements"
                                  rows="4"
                                  placeholder="What areas could this person develop further?"
                                  required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="recommendations">Recommendations (Optional)</label>
                        <textarea id="recommendations" 
                                  name="recommendations"
                                  rows="3"
                                  placeholder="Any specific recommendations for development or actions..."></textarea>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                        <i class="fas fa-arrow-left"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Feedback
                    </button>
                </div>

            <?php else: ?>
                <!-- No Questions -->
                <div class="empty-state">
                    <p><i class="fas fa-question-circle"></i></p>
                    <p>No feedback questions are available for this cycle yet.</p>
                    <p>Please contact your HR administrator.</p>
                </div>
            <?php endif; ?>
        </form>

        <!-- Progress Indicator -->
        <div class="form-progress">
            <div class="progress-info">
                <span class="progress-label">Form Completion:</span>
                <span class="progress-percentage" id="completionPercentage">0%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" id="completionBar"></div>
            </div>
        </div>

    <?php else: ?>
        <!-- Assignment Not Found -->
        <div class="error-state">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2>Assignment Not Found</h2>
            <p>The feedback assignment you're looking for doesn't exist or has been removed.</p>
            <a href="?page=360-degree-feedback" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    <?php endif; ?>
</div>

<script src="js/pages/feedback-submission.js"></script>
