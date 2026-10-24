<?php

require_once 'ExitManagementModel.php';

class SurveyModel extends ExitManagementModel
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureSurveyScheduleColumns();
    }

    protected function ensureSurveyScheduleColumns(): void
    {
        if (!$this->tableExists('exit_surveys')) {
            $this->db->exec("CREATE TABLE IF NOT EXISTS exit_surveys (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NULL,
                description TEXT NULL,
                target_audience VARCHAR(50) NULL,
                employee_id VARCHAR(50) NULL,
                exit_case_type ENUM('resignation','termination') NULL,
                exit_case_id INT NULL,
                start_date DATE NULL,
                end_date DATE NULL,
                scheduled_date DATE NULL,
                scheduled_time TIME NULL,
                status VARCHAR(50) NULL DEFAULT 'active',
                created_by INT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                approval_status ENUM('pending','draft','scheduled','approved','completed','archived') NOT NULL DEFAULT 'pending',
                employee_status_updated TINYINT(1) NOT NULL DEFAULT 0
            )");
        }

        $schemaChecks = [
            ['employee_id', 'varchar(50) NULL'],
            ['exit_case_type', "enum('resignation','termination') NULL"],
            ['exit_case_id', 'int(11) NULL'],
            ['scheduled_date', 'date NULL'],
            ['scheduled_time', 'time NULL'],
            ['approval_status', "enum('pending','draft','scheduled','approved','completed','archived') NOT NULL DEFAULT 'pending'"],
            ['employee_status_updated', 'tinyint(1) NOT NULL DEFAULT 0']
        ];

        foreach ($schemaChecks as [$columnName, $definition]) {
            if (!$this->columnExists('exit_surveys', $columnName)) {
                $this->db->exec("ALTER TABLE exit_surveys ADD COLUMN {$columnName} {$definition}");
            }
        }

        if ($this->columnExists('exit_surveys', 'approval_status')) {
            $this->db->exec("ALTER TABLE exit_surveys MODIFY COLUMN approval_status ENUM('pending','draft','scheduled','approved','completed','archived') NOT NULL DEFAULT 'pending'");
        }

        if ($this->columnExists('exit_surveys', 'start_date')) {
            $this->db->exec('ALTER TABLE exit_surveys MODIFY COLUMN start_date DATE NULL');
        }

        if ($this->columnExists('exit_surveys', 'end_date')) {
            $this->db->exec('ALTER TABLE exit_surveys MODIFY COLUMN end_date DATE NULL');
        }

        if (!$this->tableExists('exit_survey_questions')) {
            $this->db->exec("CREATE TABLE IF NOT EXISTS exit_survey_questions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                survey_id INT NOT NULL,
                question_text TEXT NOT NULL,
                question_type VARCHAR(50) NOT NULL,
                options JSON NULL,
                required TINYINT(1) NOT NULL DEFAULT 0,
                order_num INT NOT NULL DEFAULT 0,
                rating TINYINT NULL,
                rated_at TIMESTAMP NULL,
                rated_by INT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_exit_survey_questions_survey_id (survey_id),
                KEY idx_exit_survey_questions_order (survey_id, order_num)
            )");
        } else {
            foreach (['rating', 'rated_at', 'rated_by'] as $columnName) {
                if (!$this->columnExists('exit_survey_questions', $columnName)) {
                    $definition = $columnName === 'rating' ? 'TINYINT NULL' : ($columnName === 'rated_at' ? 'TIMESTAMP NULL' : 'INT NULL');
                    $this->db->exec("ALTER TABLE exit_survey_questions ADD COLUMN {$columnName} {$definition}");
                }
            }
        }

        if (!$this->tableExists('exit_post_survey_question_bank')) {
            $this->db->exec("CREATE TABLE IF NOT EXISTS exit_post_survey_question_bank (
                id INT AUTO_INCREMENT PRIMARY KEY,
                question_text TEXT NOT NULL,
                category VARCHAR(100) NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )");
        }

        $this->ensurePostSurveyQuestionBankSeed();
    }

    protected function ensurePostSurveyQuestionBankSeed(): void
    {
        if (!$this->tableExists('exit_post_survey_question_bank')) {
            return;
        }

        $countStmt = $this->db->query('SELECT COUNT(*) FROM exit_post_survey_question_bank');
        if ((int)$countStmt->fetchColumn() > 0) {
            return;
        }

        $rows = [
            ['How would you rate your overall satisfaction with the company?', 'overall', 1],
            ['How would you rate how well the role matched your expectations?', 'role', 2],
            ['How would you rate the support you received from your supervisor?', 'supervisor', 3],
            ['How would you rate the communication from management during your exit?', 'management', 4],
            ['How would you rate the fairness of how you were treated?', 'fairness', 5],
            ['How would you rate the teamwork and collaboration in your role?', 'teamwork', 6],
            ['How would you rate your satisfaction with your pay?', 'pay', 7],
            ['How would you rate your satisfaction with your benefits?', 'benefits', 8],
            ['How would you rate your opportunities for career growth?', 'career_growth', 9],
            ['How would you rate the training and development provided to you?', 'training', 10],
            ['How would you rate the recognition of your performance?', 'recognition', 11],
            ['How would you rate your work-life balance?', 'work_life_balance', 12],
            ['How would you rate your working conditions and tools?', 'working_conditions', 13],
            ['How would you rate the company culture?', 'culture', 14],
            ['How would you rate the clarity of company policies?', 'policies', 15],
            ['How would you rate how the exit process was handled?', 'exit_process', 16],
            ['How would you rate the final settlement process?', 'settlement', 17],
            ['How would you rate the likelihood of recommending the company?', 'recommendation', 18],
        ];

        $stmt = $this->db->prepare('INSERT INTO exit_post_survey_question_bank (question_text, category, is_active, sort_order) VALUES (?, ?, 1, ?)');
        foreach ($rows as $row) {
            $stmt->execute([$row[0], $row[1], $row[2]]);
        }
    }

    public function syncPendingSurveys(): int
    {
        $this->ensureSurveyScheduleColumns();

        $eligibleCases = [];

        if ($this->tableExists('exit_resignations')) {
            $resignationStmt = $this->db->query("SELECT id AS exit_case_id, employee_id, 'resignation' AS exit_case_type FROM exit_resignations WHERE status = 'approved'");
            while ($row = $resignationStmt->fetch(PDO::FETCH_ASSOC)) {
                $eligibleCases[] = $row;
            }
        }

        if ($this->tableExists('exit_terminations')) {
            $terminationStmt = $this->db->query("SELECT id AS exit_case_id, employee_id, 'termination' AS exit_case_type FROM exit_terminations WHERE status = 'approved'");
            while ($row = $terminationStmt->fetch(PDO::FETCH_ASSOC)) {
                $eligibleCases[] = $row;
            }
        }

        if (empty($eligibleCases)) {
            return 0;
        }

        $created = 0;
        foreach ($eligibleCases as $case) {
            $caseType = (string)($case['exit_case_type'] ?? '');
            $caseId = (int)($case['exit_case_id'] ?? 0);
            $employeeId = (int)($case['employee_id'] ?? 0);

            if ($caseType === '' || $caseId <= 0) {
                continue;
            }

            if (!$this->isExitCaseEligibleForPostExitFeedback($caseType, $caseId, $employeeId)) {
                continue;
            }

            $existsStmt = $this->db->prepare("SELECT COUNT(*) FROM exit_surveys WHERE exit_case_type = ? AND exit_case_id = ? AND approval_status <> 'archived'");
            $existsStmt->execute([$caseType, $caseId]);
            if ((int)$existsStmt->fetchColumn() > 0) {
                continue;
            }

            $insertStmt = $this->db->prepare("INSERT INTO exit_surveys (title, description, target_audience, employee_id, exit_case_type, exit_case_id, start_date, end_date, scheduled_date, scheduled_time, status, created_by, created_at, approval_status, employee_status_updated) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL, NULL, NULL, 'active', ?, NOW(), 'pending', 0)");
            $insertStmt->execute([
                'Post-Exit Survey',
                'Post-exit feedback survey for the completed exit case.',
                'all',
                $employeeId ?: null,
                $caseType,
                $caseId,
                $_SESSION['employee_id'] ?? null,
            ]);

            $created++;
        }

        return $created;
    }

    public function getDefaultPostExitQuestions(): array
    {
        return [
            'How satisfied were you with the overall exit process?',
            'How clearly were your exit responsibilities and final tasks explained?',
            'How well did the HR team communicate with you during your exit?',
            'How would you rate the professionalism of the exit process?',
            'How would you rate the clarity of your final pay and settlement process?',
            'How would you rate the knowledge transfer process and handover support?',
            'How respectful and considerate was the offboarding experience?',
            'How well did the company address your questions and concerns?',
            'How likely are you to recommend this company to others?',
            'How satisfied were you with the interview and feedback process?',
            'How clear were the timelines for your exit and last working day?',
            'How would you rate the support available during your transition?',
            'How well did management communicate the reason and next steps for your exit?',
            'What could the company improve in its exit process?',
            'Any final comments or suggestions for improving future exits?'
        ];
    }

    /**
     * Create a survey
     */
    public function createSurvey(array $data): int
    {
        $this->ensureSurveyScheduleColumns();

        $title = trim((string)($data['title'] ?? ''));
        if ($title === '') {
            $title = 'Post-Exit Survey';
        }

        $employeeId = isset($data['employee_id']) && $data['employee_id'] !== '' ? (string)$data['employee_id'] : null;
        $exitCaseType = !empty($data['exit_case_type']) ? $data['exit_case_type'] : null;
        $exitCaseId = !empty($data['exit_case_id']) ? (int)$data['exit_case_id'] : null;
        $scheduledDate = !empty($data['scheduled_date']) ? $data['scheduled_date'] : ($data['start_date'] ?? null);
        $scheduledTime = !empty($data['scheduled_time']) ? $data['scheduled_time'] : '09:00:00';

        $stmt = $this->db->prepare("
            INSERT INTO exit_surveys (title, description, target_audience, employee_id, exit_case_type, exit_case_id,
                                    start_date, end_date, scheduled_date, scheduled_time, status, created_by, created_at, approval_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, NOW(), 'scheduled')
        ");

        $stmt->execute([
            $title,
            $data['description'] ?? null,
            $data['target_audience'] ?? 'all',
            $employeeId,
            $exitCaseType,
            $exitCaseId,
            $data['start_date'] ?? $scheduledDate,
            $data['end_date'] ?? $scheduledDate,
            $scheduledDate,
            $scheduledTime,
            $data['created_by'] ?? null
        ]);

        $surveyId = (int)$this->db->lastInsertId();

        $questions = $data['questions'] ?? [];
        if (empty($questions)) {
            $questions = array_map(function ($questionText, $index) {
                return [
                    'text' => $questionText,
                    'type' => $index >= 13 ? 'textarea' : 'rating',
                    'options' => $index >= 13 ? null : ['1', '2', '3', '4', '5'],
                    'required' => true
                ];
            }, $this->getDefaultPostExitQuestions(), array_keys($this->getDefaultPostExitQuestions()));
        }

        if (!empty($questions)) {
            $this->addSurveyQuestions($surveyId, $questions);
        }

        return $surveyId;
    }

    /**
     * Add questions to a survey
     */
    public function addSurveyQuestions(int $surveyId, array $questions): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO exit_survey_questions (survey_id, question_text, question_type,
                                        options, required, order_num, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        foreach ($questions as $index => $question) {
            $options = null;
            if (in_array($question['type'], ['radio', 'checkbox', 'select']) && isset($question['options'])) {
                $options = json_encode($question['options']);
            }

            $stmt->execute([
                $surveyId,
                $question['text'],
                $question['type'],
                $options,
                $question['required'] ?? false,
                $index + 1
            ]);
        }

        return true;
    }

    /**
     * Get survey by ID
     */
    public function getSurveyById(int $surveyId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT s.*, CONCAT(e.first_name, ' ', e.last_name) AS employee_name, e.employment_status
            FROM exit_surveys s
            LEFT JOIN em_employees e ON e.employee_id = s.employee_id
            WHERE s.id = ?
        ");
        $stmt->execute([$surveyId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Alias for backward compatibility
     */
    public function getSurvey(int $surveyId): ?array
    {
        return $this->getSurveyById($surveyId);
    }

    /**
     * Duplicate a survey and its questions
     */
    public function duplicateSurvey(int $surveyId): ?int
    {
        $survey = $this->getSurveyById($surveyId);
        if (!$survey) {
            return null;
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO exit_surveys (title, description, target_audience, start_date, end_date, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $survey['title'],
                $survey['description'],
                $survey['target_audience'],
                $survey['start_date'],
                $survey['end_date'],
                'active',
                $_SESSION['employee_id'] ?? null
            ]);

            $newSurveyId = (int)$this->db->lastInsertId();
            $questions = $this->getSurveyQuestions($surveyId);
            if (!empty($questions)) {
                $this->addSurveyQuestions($newSurveyId, array_map(function ($question) {
                    return [
                        'text' => $question['question_text'],
                        'type' => $question['question_type'],
                        'options' => $question['options'] ? json_decode($question['options'], true) : null,
                        'required' => (bool)$question['required']
                    ];
                }, $questions));
            }

            $this->db->commit();
            return $newSurveyId;
        } catch (Exception $e) {
            $this->db->rollBack();
            return null;
        }
    }

    /**
     * Get survey questions
     */
    public function getSurveyQuestions(int $surveyId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM exit_survey_questions
            WHERE survey_id = ?
            ORDER BY order_num ASC
        ");
        $stmt->execute([$surveyId]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decode options JSON
        foreach ($questions as &$question) {
            if ($question['options']) {
                $question['options'] = json_decode($question['options'], true);
            }
        }

        return $questions;
    }

    private ?bool $hasResponseCaseColumns = null;
    private ?bool $hasResponseJsonColumn = null;

    private ?bool $hasResponseScheduleColumns = null;

    protected function hasResponseCaseColumns(): bool
    {
        if ($this->hasResponseCaseColumns === null) {
            $this->hasResponseCaseColumns = $this->columnExists('exit_survey_responses', 'exit_case_type')
                && $this->columnExists('exit_survey_responses', 'exit_case_id');
        }

        return $this->hasResponseCaseColumns;
    }

    protected function hasResponseScheduleColumns(): bool
    {
        if ($this->hasResponseScheduleColumns === null) {
            $this->hasResponseScheduleColumns = $this->columnExists('exit_survey_responses', 'survey_type')
                && $this->columnExists('exit_survey_responses', 'scheduled_date')
                && $this->columnExists('exit_survey_responses', 'scheduled_time');
        }

        return $this->hasResponseScheduleColumns;
    }

    protected function hasResponseJsonColumn(): bool
    {
        if ($this->hasResponseJsonColumn === null) {
            $this->hasResponseJsonColumn = $this->columnExists('exit_survey_responses', 'responses');
        }

        return $this->hasResponseJsonColumn;
    }

    protected function hasExistingSurveyResponse(int $surveyId, int $employeeId, ?string $exitCaseType = null, ?int $exitCaseId = null): bool
    {
        if ($this->hasResponseCaseColumns() && $exitCaseType && $exitCaseId) {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM exit_survey_responses WHERE survey_id = ? AND exit_case_type = ? AND exit_case_id = ?"
            );
            $stmt->execute([$surveyId, $exitCaseType, $exitCaseId]);
        } else {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM exit_survey_responses WHERE survey_id = ? AND employee_id = ?"
            );
            $stmt->execute([$surveyId, $employeeId]);
        }

        return (int)$stmt->fetchColumn() > 0;
    }

    protected function validateApprovedExitCase(?string $exitCaseType, ?int $exitCaseId, int $employeeId): bool
    {
        if (!$exitCaseType || !$exitCaseId) {
            throw new Exception('Approved exit case selection is required for this feedback response.');
        }

        if (!in_array($exitCaseType, ['resignation', 'termination'], true)) {
            throw new Exception('Invalid exit case type selected.');
        }

        if ($exitCaseType === 'resignation') {
            $stmt = $this->db->prepare("SELECT id, employee_id FROM exit_resignations WHERE id = ? AND status = 'approved'");
        } else {
            $stmt = $this->db->prepare("SELECT id, employee_id FROM exit_terminations WHERE id = ? AND status = 'approved'");
        }

        $stmt->execute([$exitCaseId]);
        $case = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$case) {
            throw new Exception('Selected exit case is not approved or does not exist.');
        }

        if ((string)$case['employee_id'] !== (string)$employeeId) {
            throw new Exception('Selected exit case does not belong to the specified employee.');
        }

        return true;
    }

    protected function validatePostExitFeedbackEligibility(?string $exitCaseType, ?int $exitCaseId, int $employeeId): bool
    {
        if (!$exitCaseType || !$exitCaseId) {
            throw new Exception('Eligible exit case selection is required for post-exit feedback.');
        }

        if (!$this->isExitCaseEligibleForPostExitFeedback($exitCaseType, $exitCaseId, $employeeId)) {
            throw new Exception('Selected exit case is not eligible for post-exit feedback because the required exit-management steps are not complete.');
        }

        return true;
    }

    /**
     * Submit survey response
     */
    public function submitSurveyResponse(int $surveyId, int $employeeId, array $responses, ?string $exitCaseType = null, ?int $exitCaseId = null, ?string $surveyType = null, ?string $scheduledDate = null, ?string $scheduledTime = null): bool
    {
        if ($this->hasResponseCaseColumns()) {
            $this->validateApprovedExitCase($exitCaseType, $exitCaseId, $employeeId);

            if (($surveyType ?? '') === 'post_exit_feedback') {
                $this->validatePostExitFeedbackEligibility($exitCaseType, $exitCaseId, $employeeId);
            }
        }

        if ($this->hasResponseScheduleColumns()) {
            if (!$surveyType || !$scheduledDate || !$scheduledTime) {
                throw new Exception('Survey type, scheduled date and scheduled time are required.');
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $scheduledDate)) {
                throw new Exception('Scheduled date must use YYYY-MM-DD format.');
            }

            if (!preg_match('/^\d{2}:\d{2}$/', $scheduledTime)) {
                throw new Exception('Scheduled time must use HH:MM format.');
            }
        }

        if ($this->hasExistingSurveyResponse($surveyId, $employeeId, $exitCaseType, $exitCaseId)) {
            throw new Exception('This survey has already been submitted for the selected exit case.');
        }

        // Start transaction
        $this->db->beginTransaction();

        try {
            $insertColumns = ['survey_id', 'employee_id'];
            $placeholders = ['?', '?'];
            $params = [$surveyId, $employeeId];

            if ($this->hasResponseCaseColumns()) {
                $insertColumns[] = 'exit_case_type';
                $insertColumns[] = 'exit_case_id';
                $placeholders[] = '?';
                $placeholders[] = '?';
                $params[] = $exitCaseType;
                $params[] = $exitCaseId;
            }

            if ($this->hasResponseScheduleColumns()) {
                $insertColumns[] = 'survey_type';
                $insertColumns[] = 'scheduled_date';
                $insertColumns[] = 'scheduled_time';
                $placeholders[] = '?';
                $placeholders[] = '?';
                $placeholders[] = '?';
                $params[] = $surveyType;
                $params[] = $scheduledDate;
                $params[] = $scheduledTime;
            }

            $insertColumns[] = 'submitted_at';
            $placeholders[] = 'NOW()';

            $stmt = $this->db->prepare(
                "INSERT INTO exit_survey_responses (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $placeholders) . ")"
            );

            if (!$stmt->execute($params)) {
                throw new Exception('Failed to insert survey response');
            }
            $responseId = (int)$this->db->lastInsertId();

            // Insert individual answers
            $stmt = $this->db->prepare("
                INSERT INTO exit_survey_answers (response_id, question_id, answer_text, answer_value)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($responses as $questionId => $answer) {
                $answerText = is_array($answer) ? json_encode($answer) : $answer;
                $answerValue = is_array($answer) ? implode(', ', $answer) : $answer;

                if (!$stmt->execute([$responseId, $questionId, $answerText, $answerValue])) {
                    throw new Exception('Failed to insert survey answer');
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Get survey responses
     */
    public function getSurveyResponses(int $surveyId): array
    {
        if ($this->columnExists('exit_survey_responses', 'responses')) {
            $responseStmt = $this->db->prepare("SELECT sr.id, sr.survey_id, sr.employee_id, sr.responses, sr.submitted_at, sr.exit_case_type, sr.exit_case_id, sr.survey_type, sr.scheduled_date, sr.scheduled_time, COALESCE(CONCAT(emp.first_name, ' ', emp.last_name), sr.employee_id) AS respondent_name FROM exit_survey_responses sr LEFT JOIN em_employees emp ON sr.employee_id = emp.employee_id WHERE sr.survey_id = ? ORDER BY sr.submitted_at DESC");
            $responseStmt->execute([$surveyId]);
            $responses = $responseStmt->fetchAll(PDO::FETCH_ASSOC);
            $rows = [];

            foreach ($responses as $response) {
                $payload = json_decode((string)($response['responses'] ?? '[]'), true);
                if (!is_array($payload)) {
                    $payload = [];
                }

                foreach ($payload as $questionId => $rating) {
                    $questionStmt = $this->db->prepare('SELECT question_text, question_type, order_num FROM exit_survey_questions WHERE id = ? AND survey_id = ? LIMIT 1');
                    $questionStmt->execute([$questionId, $surveyId]);
                    $question = $questionStmt->fetch(PDO::FETCH_ASSOC);
                    $rows[] = [
                        'id' => $response['id'],
                        'response_id' => $response['id'],
                        'question_id' => (int)$questionId,
                        'question_text' => $question['question_text'] ?? null,
                        'question_type' => $question['question_type'] ?? 'rating',
                        'answer_value' => $rating,
                        'answer_text' => (string)$rating,
                        'submitted_at' => $response['submitted_at'],
                        'employee_id' => $response['employee_id'],
                        'respondent_name' => $response['respondent_name'],
                        'exit_case_type' => $response['exit_case_type'] ?? null,
                        'exit_case_id' => $response['exit_case_id'] ?? null,
                        'survey_type' => $response['survey_type'] ?? null,
                        'scheduled_date' => $response['scheduled_date'] ?? null,
                        'scheduled_time' => $response['scheduled_time'] ?? null,
                        'order_num' => $question['order_num'] ?? 0,
                    ];
                }
            }

            if (!empty($rows)) {
                usort($rows, static fn ($a, $b) => [$a['submitted_at'] ?? '', $a['order_num'] ?? 0] <=> [$b['submitted_at'] ?? '', $b['order_num'] ?? 0]);
                return $rows;
            }
        }

        $selectFields = 'sa.*, sq.question_text, sr.submitted_at, sr.employee_id';
        if ($this->hasResponseCaseColumns()) {
            $selectFields .= ', sr.exit_case_type, sr.exit_case_id, CONCAT(UPPER(LEFT(sr.exit_case_type, 1)), SUBSTRING(sr.exit_case_type, 2), " #", sr.exit_case_id) AS exit_case_label';
        }

        if ($this->hasResponseScheduleColumns()) {
            $selectFields .= ', sr.survey_type, sr.scheduled_date, sr.scheduled_time';
        }

        $stmt = $this->db->prepare("\
            SELECT {$selectFields}, COALESCE(CONCAT(emp.first_name, ' ', emp.last_name), sr.employee_id) AS respondent_name\
            FROM exit_survey_answers sa\
            JOIN exit_survey_questions sq ON sa.question_id = sq.id\
            JOIN exit_survey_responses sr ON sa.response_id = sr.id\
            LEFT JOIN em_employees emp ON sr.employee_id = emp.employee_id\
            WHERE sr.survey_id = ?\
            ORDER BY sr.submitted_at DESC, sq.order_num ASC\
        ");
        $stmt->execute([$surveyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get survey response details
     */
    public function getSurveyResponseDetails(int $responseId): array
    {
        $responseStmt = $this->db->prepare("SELECT sr.*, s.title as survey_title, COALESCE(CONCAT(emp.first_name, ' ', emp.last_name), sr.employee_id) AS full_name FROM exit_survey_responses sr JOIN exit_surveys s ON sr.survey_id = s.id LEFT JOIN em_employees emp ON sr.employee_id = emp.employee_id WHERE sr.id = ?");
        $responseStmt->execute([$responseId]);
        $response = $responseStmt->fetch(PDO::FETCH_ASSOC);

        if (!$response) return [];

        $payload = json_decode((string)($response['responses'] ?? '[]'), true);
        if (is_array($payload) && !empty($payload)) {
            $answers = [];
            foreach ($payload as $questionId => $rating) {
                $questionStmt = $this->db->prepare('SELECT question_text, question_type, order_num FROM exit_survey_questions WHERE id = ? AND survey_id = ? LIMIT 1');
                $questionStmt->execute([$questionId, $response['survey_id']]);
                $question = $questionStmt->fetch(PDO::FETCH_ASSOC);
                $answers[] = [
                    'response_id' => $responseId,
                    'question_id' => (int)$questionId,
                    'question_text' => $question['question_text'] ?? null,
                    'question_type' => $question['question_type'] ?? 'rating',
                    'answer_value' => $rating,
                    'answer_text' => (string)$rating,
                    'order_num' => $question['order_num'] ?? 0,
                ];
            }
            return [
                'response' => $response,
                'answers' => $answers,
            ];
        }

        $selectFields = "sr.*, s.title as survey_title, COALESCE(CONCAT(emp.first_name, ' ', emp.last_name), sr.employee_id) AS full_name";
        if ($this->hasResponseCaseColumns()) {
            $selectFields .= ', sr.exit_case_type, sr.exit_case_id';
        }

        if ($this->hasResponseScheduleColumns()) {
            $selectFields .= ', sr.survey_type, sr.scheduled_date, sr.scheduled_time';
        }

        $stmt = $this->db->prepare("\
            SELECT {$selectFields}\
            FROM exit_survey_responses sr\
            JOIN exit_surveys s ON sr.survey_id = s.id\
            LEFT JOIN em_employees emp ON sr.employee_id = emp.employee_id\
            WHERE sr.id = ?\
        ");
        $stmt->execute([$responseId]);
        $response = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$response) return [];

        $stmt = $this->db->prepare("
            SELECT sa.*, sq.question_text, sq.question_type
            FROM exit_survey_answers sa
            JOIN exit_survey_questions sq ON sa.question_id = sq.id
            WHERE sa.response_id = ?
            ORDER BY sq.order_num ASC
        ");
        $stmt->execute([$responseId]);
        $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($answers as &$answer) {
            if ($answer['question_type'] === 'checkbox' && $answer['answer_text']) {
                $answer['answer_array'] = json_decode($answer['answer_text'], true);
            }
        }

        return [
            'response' => $response,
            'answers' => $answers
        ];
    }

    /**
     * Get active surveys for an employee
     */
    public function getActiveSurveysForEmployee(int $employeeId): array
    {
        $stmt = $this->db->prepare("
            SELECT s.* FROM exit_surveys s
            WHERE s.status = 'active'
            AND s.start_date <= CURDATE()
            AND s.end_date >= CURDATE()
            AND (s.target_audience = 'all' OR s.id NOT IN (
                SELECT survey_id FROM exit_survey_responses WHERE employee_id = ?
            ))
        ");
        $stmt->execute([$employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all surveys with optional status filter and pagination
     */
    public function getAllSurveys(?string $status = null, int $page = 1, int $limit = 10, string $search = ''): array
    {
        $this->syncPendingSurveys();
        $this->finalizeEmployeeSeparation();

        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT
                s.id,
                s.title,
                s.description,
                s.status,
                s.employee_id,
                s.exit_case_type,
                s.exit_case_id,
                s.scheduled_date,
                s.scheduled_time,
                s.approval_status,
                s.created_at,
                s.updated_at,
                CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
                CONCAT(COALESCE(UPPER(LEFT(s.exit_case_type, 1)), ''), SUBSTRING(COALESCE(s.exit_case_type, ''), 2)) AS exit_case_label,
                (SELECT COUNT(*) FROM exit_survey_questions q WHERE q.survey_id = s.id) AS question_count,
                (SELECT COUNT(*) FROM exit_survey_questions q WHERE q.survey_id = s.id AND q.rating IS NOT NULL AND q.rating BETWEEN 1 AND 5) AS rated_count
            FROM exit_surveys s
            LEFT JOIN em_employees e ON e.employee_id = s.employee_id
        ";

        $countSql = "
            SELECT COUNT(*) as total
            FROM exit_surveys s
        ";

        $params = [];
        $whereClause = "";

        if ($status && $status !== 'all') {
            $whereClause = " WHERE s.status = :status";
            $params['status'] = $status;
        }

        if (!empty($search)) {
            $searchCondition = $whereClause ? " AND" : " WHERE";
            $searchCondition .= " (s.title LIKE :search0 OR s.description LIKE :search1 OR CONCAT(e.first_name, ' ', e.last_name) LIKE :search2)";
            $whereClause .= $searchCondition;
            $searchParam = "%$search%";
            $params['search0'] = $searchParam;
            $params['search1'] = $searchParam;
            $params['search2'] = $searchParam;
        }

        // Get total count
        $countStmt = $this->db->prepare($countSql . $whereClause);
        $countStmt->execute($params);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $this->db->prepare($sql . $whereClause . " ORDER BY s.created_at DESC LIMIT :limit OFFSET :offset");
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ];
    }

    public function scheduleSurvey(int $surveyId, string $scheduledDate, string $scheduledTime): bool
    {
        $this->ensureSurveyScheduleColumns();

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $scheduledDate) || !preg_match('/^\d{2}:\d{2}(?::\d{2})?$/', $scheduledTime)) {
            return false;
        }

        $survey = $this->getSurveyById($surveyId);
        if (!$survey) {
            return false;
        }

        $status = (string)($survey['approval_status'] ?? '');
        if (!in_array($status, ['pending', 'scheduled'], true)) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $questionCount = (int)$this->db->query("SELECT COUNT(*) FROM exit_survey_questions WHERE survey_id = {$surveyId}")->fetchColumn();
            if ((int)$questionCount === 0) {
                $bankRows = $this->db->prepare('SELECT question_text FROM exit_post_survey_question_bank WHERE is_active = 1 ORDER BY sort_order ASC, id ASC');
                $bankRows->execute();
                $insertQuestion = $this->db->prepare('INSERT INTO exit_survey_questions (survey_id, question_text, question_type, required, order_num, created_at) VALUES (?, ?, "rating", 1, ?, NOW())');
                $orderIndex = 1;
                while ($bankRow = $bankRows->fetch(PDO::FETCH_ASSOC)) {
                    $insertQuestion->execute([$surveyId, $bankRow['question_text'], $orderIndex]);
                    $orderIndex++;
                }
            }

            $stmt = $this->db->prepare("UPDATE exit_surveys SET approval_status = 'scheduled', scheduled_date = ?, scheduled_time = ?, status = 'active' WHERE id = ?");
            $stmt->execute([$scheduledDate, $scheduledTime, $surveyId]);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Survey schedule error: ' . $e->getMessage());
            return false;
        }
    }

    public function saveSurveyRatings(int $surveyId, array $ratings): array
    {
        $survey = $this->getSurveyById($surveyId);
        if (!$survey) {
            return ['success' => false, 'message' => 'Survey not found'];
        }

        if (($survey['approval_status'] ?? '') !== 'scheduled') {
            return ['success' => false, 'message' => 'Ratings can only be saved while the survey is scheduled.'];
        }

        $questionMap = [];
        foreach ($this->getSurveyQuestions($surveyId) as $question) {
            $questionMap[(int)$question['id']] = $question;
        }

        $rated = 0;
        $this->db->beginTransaction();
        try {
            foreach ($ratings as $entry) {
                $questionId = (int)($entry['question_id'] ?? 0);
                if ($questionId <= 0 || !isset($questionMap[$questionId])) {
                    continue;
                }

                $rawRating = $entry['rating'] ?? '';
                if ($rawRating === '' || $rawRating === null) {
                    $rating = null;
                } else {
                    if (!preg_match('/^-?\d+$/', (string)$rawRating)) {
                        throw new Exception('Survey rating must be an integer between 1 and 5.');
                    }
                    $rating = (int)$rawRating;
                    if ($rating < 1 || $rating > 5) {
                        throw new Exception('Survey rating must be between 1 and 5.');
                    }
                }

                $stmt = $this->db->prepare('UPDATE exit_survey_questions SET rating = ?, rated_at = ?, rated_by = ? WHERE id = ? AND survey_id = ?');
                $stmt->execute([$rating, $rating === null ? null : date('Y-m-d H:i:s'), $_SESSION['employee_id'] ?? $survey['employee_id'] ?? null, $questionId, $surveyId]);
                if ($rating !== null) {
                    $rated++;
                }
            }

            $this->db->commit();
            return ['success' => true, 'rated' => $rated, 'total' => count($questionMap), 'message' => 'Ratings saved successfully'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function completeSurvey(int $surveyId): array
    {
        $survey = $this->getSurveyById($surveyId);
        if (!$survey) {
            return ['success' => false, 'message' => 'Survey not found'];
        }

        if (($survey['approval_status'] ?? '') !== 'scheduled') {
            return ['success' => false, 'message' => 'Only scheduled surveys can be completed.'];
        }

        $questions = $this->getSurveyQuestions($surveyId);
        $missing = [];
        foreach ($questions as $question) {
            $rating = isset($question['rating']) && $question['rating'] !== null && $question['rating'] !== '' ? (int)$question['rating'] : null;
            if ($rating === null || $rating < 1 || $rating > 5) {
                $missing[] = (int)$question['id'];
            }
        }

        if (!empty($missing)) {
            return ['success' => false, 'message' => 'All questions must be rated before completion.', 'missing' => $missing];
        }

        $ratings = [];
        foreach ($questions as $question) {
            $ratings[(string)$question['id']] = (int)$question['rating'];
        }

        $this->db->beginTransaction();
        try {
            $responseStmt = $this->db->prepare('INSERT INTO exit_survey_responses (survey_id, employee_id, responses, submitted_at) VALUES (?, ?, ?, NOW())');
            $responseStmt->execute([$surveyId, $survey['employee_id'] ?? null, json_encode($ratings, JSON_UNESCAPED_SLASHES)]);

            $updateStmt = $this->db->prepare("UPDATE exit_surveys SET approval_status = 'completed', status = 'completed' WHERE id = ?");
            $updateStmt->execute([$surveyId]);
            $this->db->commit();
            return ['success' => true, 'message' => 'Survey marked as completed'];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Survey complete error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Generate survey report
     */
    public function generateSurveyReport(int $surveyId): array
    {
        $survey = $this->getSurveyById($surveyId);
        if (!$survey) return [];

        $questions = $this->getSurveyQuestions($surveyId);
        $responses = $this->getSurveyResponses($surveyId);

        $report = [
            'survey' => $survey,
            'total_responses' => count($responses),
            'questions' => []
        ];

        foreach ($questions as $question) {
            $questionReport = [
                'question' => $question,
                'responses' => []
            ];

            $grouped = [];
            foreach ($responses as $response) {
                if ((int)($response['question_id'] ?? 0) === (int)$question['id']) {
                    $answerValue = (string)($response['answer_value'] ?? '');
                    $grouped[$answerValue] = ($grouped[$answerValue] ?? 0) + 1;
                }
            }

            foreach ($grouped as $answerValue => $count) {
                $questionReport['responses'][] = ['answer_value' => $answerValue, 'count' => $count];
            }

            $report['questions'][] = $questionReport;
        }

        return $report;
    }

    public function finalizeEmployeeSeparation(): int
    {
        if (!$this->tableExists('exit_surveys')) {
            return 0;
        }

        $stmt = $this->db->query("SELECT id, employee_id, exit_case_type, exit_case_id FROM exit_surveys WHERE employee_status_updated = 0 AND employee_id IS NOT NULL");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $updated = 0;

        foreach ($rows as $row) {
            $employeeId = (int)($row['employee_id'] ?? 0);
            $caseType = (string)($row['exit_case_type'] ?? '');
            $caseId = (int)($row['exit_case_id'] ?? 0);

            if ($employeeId <= 0 || !in_array($caseType, ['resignation', 'termination'], true) || $caseId <= 0) {
                continue;
            }

            $settlementStmt = $this->db->prepare("SELECT settlement_id, status FROM exit_employee_settlements WHERE exit_case_type = ? AND exit_case_id = ? AND status = 'paid' ORDER BY settlement_id DESC LIMIT 1");
            $settlementStmt->execute([$caseType, $caseId]);
            $settlement = $settlementStmt->fetch(PDO::FETCH_ASSOC);
            if (!$settlement) {
                continue;
            }

            $dateQuery = $caseType === 'resignation'
                ? "SELECT last_working_date FROM exit_resignations WHERE id = ? AND last_working_date IS NOT NULL LIMIT 1"
                : "SELECT effective_date AS last_working_date FROM exit_terminations WHERE id = ? AND effective_date IS NOT NULL LIMIT 1";
            $dateStmt = $this->db->prepare($dateQuery);
            $dateStmt->execute([$caseId]);
            $dateValue = $dateStmt->fetchColumn();
            if (empty($dateValue)) {
                continue;
            }

            if (strtotime((string)$dateValue) > time()) {
                continue;
            }

            $employeeStmt = $this->db->prepare('SELECT employment_status FROM em_employees WHERE employee_id = ? LIMIT 1');
            $employeeStmt->execute([$employeeId]);
            $employmentStatus = (string)($employeeStmt->fetchColumn() ?? '');
            if (!in_array($employmentStatus, ['Active', 'Probationary'], true)) {
                continue;
            }

            $targetStatus = $caseType === 'resignation' ? 'Resigned' : 'Terminated';
            $this->updateEmployeeStatus($employeeId, $targetStatus);

            $markStmt = $this->db->prepare('UPDATE exit_surveys SET employee_status_updated = 1 WHERE id = ?');
            $markStmt->execute([$row['id']]);
            $updated++;
        }

        return $updated;
    }

    /**
     * Archive survey
     */
    public function archiveSurvey(int $surveyId, string $archiveReason = 'Manual archive'): bool
    {
        // Get the full survey data
        $stmt = $this->db->prepare("SELECT * FROM exit_surveys WHERE id = ?");
        $stmt->execute([$surveyId]);
        $survey = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$survey) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // Insert into exit_archive
            $archiveStmt = $this->db->prepare("
                INSERT INTO exit_archive (
                    archive_type, original_id, employee_id, title, description, content,
                    status, original_created_by, archived_by, archive_reason, archive_data
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $title = "Survey - " . ($survey['title'] ?? 'Unknown Survey');
            $description = "Archived survey record";
            $content = json_encode($survey);
            $archivedBy = $_SESSION['employee_id'] ?? 1;

            $archiveStmt->execute([
                'survey',
                $surveyId,
                null, // surveys don't have specific employee_id
                $title,
                $description,
                $content,
                $survey['status'],
                $survey['created_by'],
                $archivedBy,
                $archiveReason,
                $content
            ]);

            // Delete from exit_surveys
            $deleteStmt = $this->db->prepare("DELETE FROM exit_surveys WHERE id = ?");
            $deleteStmt->execute([$surveyId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Survey archive error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unarchive survey
     */
    public function unarchiveSurvey(int $surveyId): bool
    {
        // Get archived data
        $stmt = $this->db->prepare("SELECT * FROM exit_archive WHERE archive_type = 'survey' AND original_id = ?");
        $stmt->execute([$surveyId]);
        $archive = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$archive) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // Decode the archived data
            $surveyData = json_decode($archive['archive_data'], true);
            if (!$surveyData) {
                return false;
            }

            // Insert back into exit_surveys
            $insertStmt = $this->db->prepare("
                INSERT INTO exit_surveys (
                    id, title, description, status, created_by, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $insertStmt->execute([
                $surveyData['id'],
                $surveyData['title'],
                $surveyData['description'],
                $surveyData['status'] ?? 'draft',
                $surveyData['created_by'],
                $surveyData['created_at'],
                date('Y-m-d H:i:s')
            ]);

            // Update archive record to mark as restored
            $updateStmt = $this->db->prepare("
                UPDATE exit_archive
                SET restored = 1, restored_by = ?, restored_at = NOW()
                WHERE id = ?
            ");
            $restoredBy = $_SESSION['employee_id'] ?? 1;
            $updateStmt->execute([$restoredBy, $archive['id']]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Survey unarchive error: " . $e->getMessage());
            return false;
        }
    }
}