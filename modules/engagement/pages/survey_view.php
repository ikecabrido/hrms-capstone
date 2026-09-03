<?php
require_once __DIR__ . '/../../../auth/session.php';
require_once __DIR__ . '/../autoload.php';

use App\Controllers\SurveyController;

$theme = $_SESSION['user']['theme'] ?? 'light';
$surveyCtrl = new SurveyController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $surveyId = (int)($_POST['survey_id'] ?? 0);
    $employeeId = (int)($_SESSION['user']['employee_id'] ?? $_SESSION['user']['id'] ?? 0);
    $answers = $_POST['answers'] ?? [];

    if ($surveyId > 0 && $employeeId > 0 && !empty($answers)) {
        try {
            $surveyCtrl->submit($surveyId, $employeeId, $answers);
            $_SESSION['flash_success'] = 'Thank you! Your survey response has been submitted.';
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Unable to submit your survey response. Please try again.';
        }
    } else {
        $_SESSION['flash_error'] = 'Please answer all required questions before submitting.';
    }

    $redirectTarget = '/hrms-capstone/modules/engagement/index.php?page=survey#satisfaction';
    header('Location: ' . $redirectTarget);
    exit;
}

$surveyId = (int)($_GET['id'] ?? 0);
$viewMode = (strtolower((string)($_GET['action'] ?? '')) === 'view');
$survey = null;
$questions = [];

if ($surveyId > 0) {
    $survey = $surveyCtrl->getWithQuestions($surveyId);
    if ($survey) {
        $questions = $survey['questions'] ?? [];
    }
}

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Take Survey</title>
    <style>
        body {
            background: linear-gradient(135deg, #edf5ff 0%, #f9fbff 100%);
            font-family: 'Segoe UI', sans-serif;
            color: #1e293b;
        }
        .survey-shell {
            max-width: 980px;
            margin: 32px auto;
            padding: 0 18px 40px;
        }
        .survey-card {
            background: #fff;
            border-radius: 20px;
            border: 1px solid rgba(148, 163, 184, 0.28);
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }
        .survey-header {
            background: linear-gradient(135deg, #0f4c81 0%, #1e6bb8 100%);
            color: #fff;
            padding: 28px 30px;
        }
        .survey-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }
        .survey-header p {
            margin: 10px 0 0;
            color: rgba(255,255,255,0.84);
        }
        .survey-body {
            padding: 28px 30px 18px;
        }
        .survey-question {
            background: #f8fbff;
            border: 1px solid #e2ecf7;
            border-radius: 14px;
            padding: 18px 18px 14px;
            margin-bottom: 18px;
        }
        .survey-question label {
            display: block;
            font-weight: 700;
            color: #1f2d3d;
            margin-bottom: 12px;
        }
        .survey-question textarea,
        .survey-question input {
            width: 100%;
            border: 1px solid #cfe0f5;
            border-radius: 12px;
            padding: 12px 14px;
            background: #fff;
            transition: all 0.2s ease;
        }
        .survey-question textarea:focus,
        .survey-question input:focus {
            outline: none;
            border-color: #3d8bfd;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
        }
        .survey-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
            font-size: 0.92rem;
            color: #475569;
        }
        .survey-badge {
            display: inline-block;
            background: #eaf3ff;
            color: #0f4c81;
            border: 1px solid #cfe0f5;
            border-radius: 999px;
            padding: 6px 12px;
            font-weight: 600;
        }
        .survey-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid #edf2f7;
        }
        .btn-primary-custom {
            background: linear-gradient(135deg, #0f4c81 0%, #1b6eb5 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 12px 22px;
            font-weight: 700;
            box-shadow: 0 10px 18px rgba(31, 92, 165, 0.2);
        }
        .btn-secondary-custom {
            background: #edf2f8;
            color: #1e293b;
            border: 1px solid #dfe8f5;
            border-radius: 12px;
            padding: 11px 18px;
            font-weight: 600;
        }
        .alert {
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 18px;
        }
        @media (max-width: 640px) {
            .survey-header,
            .survey-body {
                padding-left: 18px;
                padding-right: 18px;
            }
            .survey-actions {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <div class="survey-shell">
        <div class="survey-card">
            <div class="survey-header">
                <h1><?= htmlspecialchars($survey['title'] ?? 'Take Survey') ?></h1>
                <p><?= htmlspecialchars($survey['description'] ?? 'Please answer the following questions honestly and thoughtfully.') ?></p>
            </div>
            <div class="survey-body">
                <?php if (!empty($flashSuccess)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
                <?php endif; ?>
                <?php if (!empty($flashError)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($flashError) ?></div>
                <?php endif; ?>

                <?php if (empty($survey)): ?>
                    <div class="alert alert-warning">Survey not found. Please return to the survey list and try again.</div>
                    <div class="survey-actions">
                        <a href="/hrms-capstone/modules/engagement/index.php?page=survey#satisfaction" class="btn-secondary-custom">Back to Surveys</a>
                    </div>
                <?php else: ?>
                    <div class="survey-meta">
                        <span class="survey-badge">Survey ID: #<?= (int)($survey['eer_survey_id'] ?? 0) ?></span>
                        <span class="survey-badge">Anonymous: <?= (!empty($survey['is_anonymous'])) ? 'Yes' : 'No' ?></span>
                        <?php if (!empty($survey['created_at'])): ?>
                            <span class="survey-badge">Created: <?= htmlspecialchars(date('M d, Y', strtotime($survey['created_at']))) ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($viewMode): ?>
                        <div class="survey-readonly">
                            <?php if (empty($questions)): ?>
                                <div class="alert alert-warning">No questions have been added for this survey yet.</div>
                            <?php else: ?>
                                <?php foreach ($questions as $index => $question): ?>
                                    <?php $fieldId = 'q_' . ($question['eer_survey_question_id'] ?? $index + 1); ?>
                                    <div class="survey-question">
                                        <label for="<?= htmlspecialchars($fieldId) ?>">
                                            <?= htmlspecialchars($index + 1) ?>. <?= htmlspecialchars($question['question_text'] ?? 'Untitled question') ?>
                                        </label>
                                        <div class="survey-answer-readonly" style="white-space: pre-wrap; min-height: 64px; padding: 12px 14px; border: 1px solid #cfe0f5; border-radius: 12px; background: #f8fbff; color: #1e293b;">
                                            <?= htmlspecialchars((string)($question['answer'] ?? $question['response_answer'] ?? 'No answer provided yet.')) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="survey-actions">
                            <a href="/hrms-capstone/modules/engagement/index.php?page=survey#satisfaction" class="btn-secondary-custom">Back</a>
                        </div>
                    <?php else: ?>
                        <form method="post" action="/hrms-capstone/modules/engagement/pages/survey_view.php?id=<?= (int)($survey['eer_survey_id'] ?? 0) ?>">
                            <input type="hidden" name="survey_id" value="<?= (int)($survey['eer_survey_id'] ?? 0) ?>">

                            <?php if (empty($questions)): ?>
                                <div class="alert alert-warning">No questions have been added for this survey yet.</div>
                            <?php else: ?>
                                <?php foreach ($questions as $index => $question): ?>
                                    <?php $fieldId = 'q_' . ($question['eer_survey_question_id'] ?? $index + 1); ?>
                                    <div class="survey-question">
                                        <label for="<?= htmlspecialchars($fieldId) ?>">
                                            <?= htmlspecialchars($index + 1) ?>. <?= htmlspecialchars($question['question_text'] ?? 'Untitled question') ?>
                                        </label>
                                        <?php $type = strtolower((string)($question['type'] ?? 'text')); ?>
                                        <?php if ($type === 'rating' || $type === 'number' || preg_match('/\b(1|5|scale|rating)\b/i', (string)$question['question_text'] ?? '')): ?>
                                            <input id="<?= htmlspecialchars($fieldId) ?>" type="number" min="1" max="5" name="answers[<?= (int)($question['eer_survey_question_id'] ?? $index + 1) ?>]" placeholder="Rate from 1 to 5" required>
                                        <?php else: ?>
                                            <textarea id="<?= htmlspecialchars($fieldId) ?>" rows="4" name="answers[<?= (int)($question['eer_survey_question_id'] ?? $index + 1) ?>]" placeholder="Type your answer here..." required></textarea>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <div class="survey-actions">
                                <a href="/hrms-capstone/modules/engagement/index.php?page=survey#satisfaction" class="btn-secondary-custom">Back</a>
                                <button type="submit" class="btn-primary-custom">Submit Survey</button>
                            </div>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="js/survey_view.js"></script>
</body>
</html>
