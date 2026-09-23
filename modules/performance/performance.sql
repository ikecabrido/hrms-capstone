-- Goal Setting module database
-- Import this file into the HRMS database before opening the Goal Setting page.

CREATE TABLE IF NOT EXISTS pm_goals (
    goal_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    employee_name VARCHAR(255) NOT NULL,
    department INT DEFAULT NULL,
    position VARCHAR(100) DEFAULT NULL,
    supervisor_id INT DEFAULT NULL,
    supervisor_name VARCHAR(255) DEFAULT NULL,
    goal_title VARCHAR(255) NOT NULL,
    goal_description TEXT NOT NULL,
    goal_category VARCHAR(100) DEFAULT 'Performance',
    goal_type VARCHAR(100) DEFAULT 'Individual Goal',
    priority_level VARCHAR(20) DEFAULT 'Medium',
    start_date DATE DEFAULT NULL,
    due_date DATE DEFAULT NULL,
    target_completion_percentage TINYINT UNSIGNED DEFAULT 0,
    kpi_name VARCHAR(255) DEFAULT NULL,
    kpi_target VARCHAR(255) DEFAULT NULL,
    expected_outcome TEXT DEFAULT NULL,
    smart_notes TEXT DEFAULT NULL,
    progress_percentage TINYINT UNSIGNED DEFAULT 0,
    progress_notes TEXT DEFAULT NULL,
    latest_update_date DATE DEFAULT NULL,
    status VARCHAR(30) DEFAULT 'Draft',
    approval_comment TEXT DEFAULT NULL,
    rejection_reason TEXT DEFAULT NULL,
    completion_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_goals_employee (employee_id),
    INDEX idx_goals_status (status),
    INDEX idx_goals_priority (priority_level),
    INDEX idx_goals_category (goal_category),
    INDEX idx_goals_due_date (due_date),
    INDEX idx_goals_department (department)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_goal_progress (
    progress_id INT AUTO_INCREMENT PRIMARY KEY,
    goal_id INT NOT NULL,
    progress_percentage TINYINT UNSIGNED NOT NULL,
    progress_notes TEXT DEFAULT NULL,
    update_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_goal_progress_goal (goal_id),
    CONSTRAINT fk_goal_progress_goal FOREIGN KEY (goal_id) REFERENCES pm_goals(goal_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_goal_approvals (
    approval_id INT AUTO_INCREMENT PRIMARY KEY,
    goal_id INT NOT NULL,
    supervisor_id INT DEFAULT NULL,
    supervisor_name VARCHAR(255) DEFAULT NULL,
    decision VARCHAR(20) NOT NULL,
    comments TEXT DEFAULT NULL,
    approved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_goal_approvals_goal (goal_id),
    CONSTRAINT fk_goal_approvals_goal FOREIGN KEY (goal_id) REFERENCES pm_goals(goal_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_goal_attachments (
    attachment_id INT AUTO_INCREMENT PRIMARY KEY,
    goal_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(100) DEFAULT NULL,
    uploaded_by VARCHAR(50) DEFAULT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_goal_attachments_goal (goal_id),
    CONSTRAINT fk_goal_attachments_goal FOREIGN KEY (goal_id) REFERENCES pm_goals(goal_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_goal_comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    goal_id INT NOT NULL,
    user_id VARCHAR(50) DEFAULT NULL,
    user_name VARCHAR(255) DEFAULT NULL,
    comment_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_goal_comments_goal (goal_id),
    CONSTRAINT fk_goal_comments_goal FOREIGN KEY (goal_id) REFERENCES pm_goals(goal_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_goal_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    goal_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT DEFAULT NULL,
    created_by VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_goal_history_goal (goal_id),
    CONSTRAINT fk_goal_history_goal FOREIGN KEY (goal_id) REFERENCES pm_goals(goal_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_goal_events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    event_title VARCHAR(255) NOT NULL,
    event_type VARCHAR(100) NOT NULL DEFAULT 'Other HR Event',
    start_date DATE NOT NULL,
    end_date DATE DEFAULT NULL,
    start_time TIME DEFAULT NULL,
    end_time TIME DEFAULT NULL,
    employee_name VARCHAR(255) DEFAULT NULL,
    department INT DEFAULT NULL,
    location VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    related_goal_id INT DEFAULT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Scheduled',
    reminder VARCHAR(50) NOT NULL DEFAULT 'None',
    created_by VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_goal_events_dates (start_date, end_date),
    INDEX idx_goal_events_type (event_type),
    INDEX idx_goal_events_goal (related_goal_id),
    CONSTRAINT fk_goal_events_goal FOREIGN KEY (related_goal_id) REFERENCES pm_goals(goal_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- 360-DEGREE FEEDBACK MODULE
-- ============================================================================

CREATE TABLE IF NOT EXISTS pm_feedback_cycles (
    cycle_id INT AUTO_INCREMENT PRIMARY KEY,
    cycle_name VARCHAR(255) NOT NULL,
    cycle_period VARCHAR(100) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    submission_start_date DATE DEFAULT NULL,
    submission_end_date DATE DEFAULT NULL,
    status ENUM('Draft', 'Active', 'Closed', 'Archived') DEFAULT 'Draft',
    is_active TINYINT(1) DEFAULT 0,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_feedback_cycles_status (status),
    INDEX idx_feedback_cycles_active (is_active),
    INDEX idx_feedback_cycles_dates (start_date, end_date),
    CONSTRAINT fk_feedback_cycles_creator FOREIGN KEY (created_by)
        REFERENCES em_employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_competencies (
    competency_id INT AUTO_INCREMENT PRIMARY KEY,
    competency_name VARCHAR(255) NOT NULL UNIQUE,
    competency_code VARCHAR(50) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    category VARCHAR(100) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_competencies_active (is_active),
    INDEX idx_competencies_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_feedback_questions (
    question_id INT AUTO_INCREMENT PRIMARY KEY,
    competency_id INT DEFAULT NULL,
    question_text VARCHAR(500) NOT NULL,
    question_type ENUM('Rating', 'Text', 'Scale') DEFAULT 'Rating',
    scale_min INT DEFAULT 1,
    scale_max INT DEFAULT 5,
    is_active TINYINT(1) DEFAULT 1,
    order_sequence INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_feedback_questions_competency (competency_id),
    CONSTRAINT fk_feedback_questions_competency FOREIGN KEY (competency_id)
        REFERENCES pm_competencies(competency_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_rater_types (
    rater_type_id INT AUTO_INCREMENT PRIMARY KEY,
    rater_type_name VARCHAR(100) NOT NULL UNIQUE,
    rater_type_code VARCHAR(50) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rater_types_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_feedback_assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    cycle_id INT NOT NULL,
    employee_id INT NOT NULL,
    employee_name VARCHAR(255) DEFAULT NULL,
    department INT DEFAULT NULL,
    rater_type_id INT DEFAULT NULL,
    rater_type_name VARCHAR(100) DEFAULT NULL,
    rater_id INT NOT NULL,
    rater_name VARCHAR(255) DEFAULT NULL,
    is_anonymous TINYINT(1) DEFAULT 0,
    status ENUM('Pending', 'Assigned', 'InProgress', 'Submitted', 'Completed') DEFAULT 'Pending',
    assigned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    submission_date TIMESTAMP NULL DEFAULT NULL,
    assigned_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_feedback_assignments_cycle (cycle_id),
    INDEX idx_feedback_assignments_employee (employee_id),
    INDEX idx_feedback_assignments_rater (rater_id),
    INDEX idx_feedback_assignments_rater_type (rater_type_id),
    CONSTRAINT fk_feedback_assignments_cycle FOREIGN KEY (cycle_id)
        REFERENCES pm_feedback_cycles(cycle_id) ON DELETE CASCADE,
    CONSTRAINT fk_feedback_assignments_employee FOREIGN KEY (employee_id)
        REFERENCES em_employees(employee_id) ON DELETE CASCADE,
    CONSTRAINT fk_feedback_assignments_rater_type FOREIGN KEY (rater_type_id)
        REFERENCES pm_rater_types(rater_type_id) ON DELETE SET NULL,
    CONSTRAINT fk_feedback_assignments_rater FOREIGN KEY (rater_id)
        REFERENCES em_employees(employee_id) ON DELETE CASCADE,
    CONSTRAINT fk_feedback_assignments_assigner FOREIGN KEY (assigned_by)
        REFERENCES em_employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_feedback_responses (
    response_id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    question_id INT NOT NULL,
    rating INT DEFAULT NULL,
    text_response TEXT DEFAULT NULL,
    comment TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_feedback_responses_assignment (assignment_id),
    INDEX idx_feedback_responses_question (question_id),
    CONSTRAINT fk_feedback_responses_assignment FOREIGN KEY (assignment_id)
        REFERENCES pm_feedback_assignments(assignment_id) ON DELETE CASCADE,
    CONSTRAINT fk_feedback_responses_question FOREIGN KEY (question_id)
        REFERENCES pm_feedback_questions(question_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_competency_ratings (
    rating_id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    competency_id INT NOT NULL,
    rating_score DECIMAL(4,2) DEFAULT NULL,
    feedback_text TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_competency_ratings_assignment (assignment_id),
    INDEX idx_competency_ratings_competency (competency_id),
    CONSTRAINT fk_competency_ratings_assignment FOREIGN KEY (assignment_id)
        REFERENCES pm_feedback_assignments(assignment_id) ON DELETE CASCADE,
    CONSTRAINT fk_competency_ratings_competency FOREIGN KEY (competency_id)
        REFERENCES pm_competencies(competency_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_feedback_summary (
    summary_id INT AUTO_INCREMENT PRIMARY KEY,
    cycle_id INT NOT NULL,
    employee_id INT NOT NULL,
    employee_name VARCHAR(255) DEFAULT NULL,
    department INT DEFAULT NULL,
    total_raters_assigned INT DEFAULT 0,
    total_raters_completed INT DEFAULT 0,
    overall_score DECIMAL(4,2) DEFAULT NULL,
    self_rating DECIMAL(4,2) DEFAULT NULL,
    manager_rating DECIMAL(4,2) DEFAULT NULL,
    peer_rating DECIMAL(4,2) DEFAULT NULL,
    subordinate_rating DECIMAL(4,2) DEFAULT NULL,
    strengths TEXT DEFAULT NULL,
    areas_for_improvement TEXT DEFAULT NULL,
    key_insights TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_feedback_summary_cycle (cycle_id),
    INDEX idx_feedback_summary_employee (employee_id),
    CONSTRAINT fk_feedback_summary_cycle FOREIGN KEY (cycle_id)
        REFERENCES pm_feedback_cycles(cycle_id) ON DELETE CASCADE,
    CONSTRAINT fk_feedback_summary_employee FOREIGN KEY (employee_id)
        REFERENCES em_employees(employee_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_rater_type_ratings (
    rating_id INT AUTO_INCREMENT PRIMARY KEY,
    summary_id INT NOT NULL,
    rater_type_id INT NOT NULL,
    rater_type_name VARCHAR(100) DEFAULT NULL,
    average_rating DECIMAL(4,2) DEFAULT NULL,
    number_of_raters INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rater_type_ratings_summary (summary_id),
    INDEX idx_rater_type_ratings_type (rater_type_id),
    CONSTRAINT fk_rater_type_ratings_summary FOREIGN KEY (summary_id)
        REFERENCES pm_feedback_summary(summary_id) ON DELETE CASCADE,
    CONSTRAINT fk_rater_type_ratings_type FOREIGN KEY (rater_type_id)
        REFERENCES pm_rater_types(rater_type_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_competency_summary (
    competency_summary_id INT AUTO_INCREMENT PRIMARY KEY,
    summary_id INT NOT NULL,
    competency_id INT NOT NULL,
    competency_name VARCHAR(255) DEFAULT NULL,
    average_rating DECIMAL(4,2) DEFAULT NULL,
    strength_indicator TINYINT(1) DEFAULT 0,
    improvement_indicator TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_competency_summary_summary (summary_id),
    INDEX idx_competency_summary_competency (competency_id),
    CONSTRAINT fk_competency_summary_summary FOREIGN KEY (summary_id)
        REFERENCES pm_feedback_summary(summary_id) ON DELETE CASCADE,
    CONSTRAINT fk_competency_summary_competency FOREIGN KEY (competency_id)
        REFERENCES pm_competencies(competency_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_feedback_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT DEFAULT NULL,
    summary_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    action_details TEXT DEFAULT NULL,
    performed_by INT DEFAULT NULL,
    performed_by_name VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_feedback_history_assignment (assignment_id),
    INDEX idx_feedback_history_summary (summary_id),
    INDEX idx_feedback_history_action (action),
    CONSTRAINT fk_feedback_history_assignment FOREIGN KEY (assignment_id)
        REFERENCES pm_feedback_assignments(assignment_id) ON DELETE SET NULL,
    CONSTRAINT fk_feedback_history_summary FOREIGN KEY (summary_id)
        REFERENCES pm_feedback_summary(summary_id) ON DELETE SET NULL,
    CONSTRAINT fk_feedback_history_employee FOREIGN KEY (performed_by)
        REFERENCES em_employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_feedback_360_entries (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    reviewer_id INT DEFAULT NULL,
    reviewer_type VARCHAR(30) NOT NULL,
    reviewer_name VARCHAR(120) NOT NULL,
    category VARCHAR(80) NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comments TEXT DEFAULT NULL,
    review_period VARCHAR(50) DEFAULT NULL,
    department INT DEFAULT NULL,
    feedback_status VARCHAR(30) DEFAULT 'Pending',
    feedback_category VARCHAR(80) DEFAULT NULL,
    strengths TEXT DEFAULT NULL,
    areas_for_improvement TEXT DEFAULT NULL,
    recommendation VARCHAR(80) DEFAULT NULL,
    is_anonymous TINYINT(1) DEFAULT 0,
    supporting_documents VARCHAR(255) DEFAULT NULL,
    hr_remarks TEXT DEFAULT NULL,
    overall_rating DECIMAL(3,2) DEFAULT NULL,
    competency_scores TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_feedback_360_employee (employee_id),
    INDEX idx_feedback_360_review_period (review_period),
    CONSTRAINT fk_feedback_360_employee FOREIGN KEY (employee_id)
        REFERENCES em_employees(employee_id) ON DELETE CASCADE,
    CONSTRAINT fk_feedback_360_reviewer FOREIGN KEY (reviewer_id)
        REFERENCES em_employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- PERFORMANCE REPORT MODULE
-- ============================================================================

CREATE TABLE IF NOT EXISTS pm_report_disciplinary_actions (
    disciplinary_action_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action_code VARCHAR(20) NOT NULL UNIQUE,
    employee_id INT NOT NULL,
    employee_name VARCHAR(150) NOT NULL,
    department INT DEFAULT NULL,
    position VARCHAR(100) NOT NULL,
    violation VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    incident_date DATE NOT NULL,
    reported_by INT NOT NULL,
    disciplinary_action VARCHAR(100) NOT NULL,
    effective_date DATE NOT NULL,
    status ENUM('Active', 'Completed', 'Under Review', 'Cancelled') NOT NULL DEFAULT 'Active',
    corrective_action TEXT DEFAULT NULL,
    follow_up_review_date DATE DEFAULT NULL,
    remarks TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_disciplinary_employee (employee_id),
    INDEX idx_disciplinary_department (department),
    INDEX idx_disciplinary_status (status),
    CONSTRAINT fk_disciplinary_employee FOREIGN KEY (employee_id)
        REFERENCES em_employees(employee_id) ON DELETE CASCADE,
    CONSTRAINT fk_disciplinary_reporter FOREIGN KEY (reported_by)
        REFERENCES em_employees(employee_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_performance_reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    report_code VARCHAR(40) NOT NULL UNIQUE,
    employee_id INT DEFAULT NULL,
    employee_name VARCHAR(255) DEFAULT NULL,
    department INT DEFAULT NULL,
    position VARCHAR(100) DEFAULT NULL,
    supervisor_id INT DEFAULT NULL,
    supervisor_name VARCHAR(255) DEFAULT NULL,
    review_period VARCHAR(100) DEFAULT NULL,
    period_start DATE DEFAULT NULL,
    period_end DATE DEFAULT NULL,
    overall_rating DECIMAL(4,2) DEFAULT NULL,
    kpi_health_score DECIMAL(5,2) DEFAULT NULL,
    feedback_average DECIMAL(4,2) DEFAULT NULL,
    goal_completion_rate DECIMAL(5,2) DEFAULT NULL,
    performance_status VARCHAR(50) DEFAULT NULL,
    risk_level VARCHAR(50) DEFAULT NULL,
    training_recommendation VARCHAR(255) DEFAULT NULL,
    summary TEXT DEFAULT NULL,
    status ENUM('Draft', 'Finalized', 'Archived') DEFAULT 'Draft',
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_performance_reports_employee (employee_id),
    INDEX idx_performance_reports_department (department),
    INDEX idx_performance_reports_period (review_period),
    INDEX idx_performance_reports_status (status),
    CONSTRAINT fk_performance_reports_employee FOREIGN KEY (employee_id)
        REFERENCES em_employees(employee_id) ON DELETE SET NULL,
    CONSTRAINT fk_performance_reports_supervisor FOREIGN KEY (supervisor_id)
        REFERENCES em_employees(employee_id) ON DELETE SET NULL,
    CONSTRAINT fk_performance_reports_creator FOREIGN KEY (created_by)
        REFERENCES em_employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_performance_report_kpis (
    kpi_item_id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    kpi_name VARCHAR(255) DEFAULT NULL,
    goal_title VARCHAR(255) DEFAULT NULL,
    kpi_target VARCHAR(255) DEFAULT NULL,
    actual_value VARCHAR(255) DEFAULT NULL,
    achievement_percentage DECIMAL(6,2) DEFAULT NULL,
    performance_status VARCHAR(100) DEFAULT NULL,
    manager_comments TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_report_kpis_report (report_id),
    CONSTRAINT fk_report_kpis_report FOREIGN KEY (report_id)
        REFERENCES pm_performance_reports(report_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_performance_report_evaluations (
    evaluation_id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    competency_name VARCHAR(255) DEFAULT NULL,
    rating DECIMAL(4,2) DEFAULT NULL,
    comments TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_report_evaluations_report (report_id),
    CONSTRAINT fk_report_evaluations_report FOREIGN KEY (report_id)
        REFERENCES pm_performance_reports(report_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_performance_report_actions (
    action_id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    action_type VARCHAR(100) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    owner INT DEFAULT NULL,
    due_date DATE DEFAULT NULL,
    status ENUM('Open', 'Planned', 'Completed', 'Deferred') DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_report_actions_report (report_id),
    INDEX idx_report_actions_owner (owner),
    CONSTRAINT fk_report_actions_report FOREIGN KEY (report_id)
        REFERENCES pm_performance_reports(report_id) ON DELETE CASCADE,
    CONSTRAINT fk_report_actions_owner FOREIGN KEY (owner)
        REFERENCES em_employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- APPRAISAL & REVIEW MODULE
-- ============================================================================

CREATE TABLE IF NOT EXISTS pm_review_cycles (
    cycle_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    cycle_period VARCHAR(100) DEFAULT NULL,
    appraisal_type VARCHAR(100) DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    description TEXT DEFAULT NULL,
    status VARCHAR(30) DEFAULT 'Inactive',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_review_cycles_status (status),
    INDEX idx_review_cycles_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_appraisals (
    appraisal_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    employee_name VARCHAR(255) NOT NULL,
    department INT DEFAULT NULL,
    reviewer_id INT DEFAULT NULL,
    reviewer_name VARCHAR(255) DEFAULT NULL,
    status VARCHAR(30) DEFAULT 'Not Started',
    overall_rating DECIMAL(3,2) DEFAULT NULL,
    due_date DATE DEFAULT NULL,
    review_cycle_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_appraisals_employee (employee_id),
    INDEX idx_appraisals_department (department),
    INDEX idx_appraisals_cycle (review_cycle_id),
    INDEX idx_appraisals_status (status),
    CONSTRAINT fk_appraisals_employee FOREIGN KEY (employee_id)
        REFERENCES em_employees(employee_id) ON DELETE CASCADE,
    CONSTRAINT fk_appraisals_reviewer FOREIGN KEY (reviewer_id)
        REFERENCES em_employees(employee_id) ON DELETE SET NULL,
    CONSTRAINT fk_appraisals_cycle FOREIGN KEY (review_cycle_id)
        REFERENCES pm_review_cycles(cycle_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_appraisal_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    appraisal_id INT NOT NULL,
    criterion VARCHAR(255) NOT NULL,
    rating DECIMAL(3,2) DEFAULT NULL,
    comments TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_appraisal_items_appraisal (appraisal_id),
    CONSTRAINT fk_appraisal_items_appraisal FOREIGN KEY (appraisal_id)
        REFERENCES pm_appraisals(appraisal_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_appraisal_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    appraisal_id INT NOT NULL,
    action VARCHAR(120) NOT NULL,
    details TEXT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_appraisal_history_appraisal (appraisal_id),
    INDEX idx_appraisal_history_employee (created_by),
    CONSTRAINT fk_appraisal_history_appraisal FOREIGN KEY (appraisal_id)
        REFERENCES pm_appraisals(appraisal_id) ON DELETE CASCADE,
    CONSTRAINT fk_appraisal_history_employee FOREIGN KEY (created_by)
        REFERENCES em_employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- EVALUATION MODULE
-- ============================================================================

CREATE TABLE IF NOT EXISTS pm_evaluations (
    evaluation_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    employee_name VARCHAR(255) NOT NULL,
    department VARCHAR(150) DEFAULT NULL,
    position VARCHAR(150) DEFAULT NULL,
    evaluation_cycle VARCHAR(100) NOT NULL,
    evaluation_period_start DATE DEFAULT NULL,
    evaluation_period_end DATE DEFAULT NULL,
    evaluator_id INT DEFAULT NULL,
    evaluator_name VARCHAR(255) DEFAULT NULL,
    evaluation_date DATE DEFAULT NULL,
    overall_score DECIMAL(5,2) DEFAULT NULL,
    overall_rating VARCHAR(40) DEFAULT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'In Progress',
    strengths TEXT DEFAULT NULL,
    areas_for_improvement TEXT DEFAULT NULL,
    recommended_training TEXT DEFAULT NULL,
    final_remarks TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_evaluations_employee (employee_id),
    INDEX idx_evaluations_cycle (evaluation_cycle),
    INDEX idx_evaluations_status (status),
    INDEX idx_evaluations_date (evaluation_date),
    CONSTRAINT fk_evaluations_employee FOREIGN KEY (employee_id)
        REFERENCES em_employees(employee_id) ON DELETE CASCADE,
    CONSTRAINT fk_evaluations_evaluator FOREIGN KEY (evaluator_id)
        REFERENCES em_employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_evaluation_criteria (
    criterion_id INT AUTO_INCREMENT PRIMARY KEY,
    evaluation_id INT NOT NULL,
    criterion_name VARCHAR(150) NOT NULL,
    rating TINYINT UNSIGNED NOT NULL DEFAULT 0,
    score DECIMAL(5,2) DEFAULT NULL,
    comments TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_evaluation_criteria_evaluation (evaluation_id),
    CONSTRAINT fk_evaluation_criteria_evaluation FOREIGN KEY (evaluation_id)
        REFERENCES pm_evaluations(evaluation_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- KPI TRACKING MODULE
-- ============================================================================

CREATE TABLE IF NOT EXISTS pm_kpi_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(150) NOT NULL UNIQUE,
    category_description TEXT DEFAULT NULL,
    category_color VARCHAR(20) DEFAULT '#3498db',
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_kpi_categories_creator FOREIGN KEY (created_by)
        REFERENCES em_employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_kpi_definitions (
    kpi_id INT AUTO_INCREMENT PRIMARY KEY,
    kpi_code VARCHAR(50) NOT NULL UNIQUE,
    kpi_name VARCHAR(255) NOT NULL,
    kpi_description TEXT DEFAULT NULL,
    category_id INT DEFAULT NULL,
    kpi_type ENUM('Financial', 'Operational', 'HR', 'Customer', 'Quality', 'Safety', 'Other') NOT NULL DEFAULT 'Operational',
    measurement_unit VARCHAR(50) DEFAULT NULL,
    calculation_formula TEXT DEFAULT NULL,
    data_source VARCHAR(150) DEFAULT NULL,
    benchmark_value DECIMAL(14,4) DEFAULT NULL,
    target_direction ENUM('Higher is Better', 'Lower is Better', 'Target Range') NOT NULL DEFAULT 'Higher is Better',
    min_target_value DECIMAL(14,4) DEFAULT NULL,
    max_target_value DECIMAL(14,4) DEFAULT NULL,
    weight DECIMAL(5,2) DEFAULT 100.00,
    is_active TINYINT(1) DEFAULT 1,
    default_frequency ENUM('Daily', 'Weekly', 'Bi-weekly', 'Monthly', 'Quarterly', 'Semi-annually', 'Annually') DEFAULT 'Monthly',
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kpi_definitions_category (category_id),
    INDEX idx_kpi_definitions_type (kpi_type),
    INDEX idx_kpi_definitions_active (is_active),
    CONSTRAINT fk_kpi_definitions_category FOREIGN KEY (category_id)
        REFERENCES pm_kpi_categories(category_id) ON DELETE SET NULL,
    CONSTRAINT fk_kpi_definitions_creator FOREIGN KEY (created_by)
        REFERENCES em_employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_kpi_assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    kpi_id INT NOT NULL,
    assignee_id INT NOT NULL,
    assignee_name VARCHAR(255) NOT NULL,
    department INT DEFAULT NULL,
    position VARCHAR(100) DEFAULT NULL,
    supervisor_id INT DEFAULT NULL,
    supervisor_name VARCHAR(255) DEFAULT NULL,
    target_value DECIMAL(14,4) NOT NULL,
    stretch_target_value DECIMAL(14,4) DEFAULT NULL,
    min_acceptable_value DECIMAL(14,4) DEFAULT NULL,
    period_type ENUM('Daily', 'Weekly', 'Bi-weekly', 'Monthly', 'Quarterly', 'Semi-annually', 'Annually') NOT NULL DEFAULT 'Monthly',
    period_start_date DATE NOT NULL,
    period_end_date DATE NOT NULL,
    weight DECIMAL(5,2) DEFAULT 100.00,
    assignment_status ENUM('Draft', 'Active', 'Paused', 'Completed', 'Cancelled') DEFAULT 'Active',
    approval_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    approved_by INT DEFAULT NULL,
    approved_at TIMESTAMP NULL DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kpi_assignments_kpi (kpi_id),
    INDEX idx_kpi_assignments_assignee (assignee_id),
    INDEX idx_kpi_assignments_department (department),
    INDEX idx_kpi_assignments_period (period_start_date, period_end_date),
    INDEX idx_kpi_assignments_status (assignment_status),
    CONSTRAINT fk_kpi_assignments_definition FOREIGN KEY (kpi_id)
        REFERENCES pm_kpi_definitions(kpi_id) ON DELETE CASCADE,
    CONSTRAINT fk_kpi_assignments_assignee FOREIGN KEY (assignee_id)
        REFERENCES em_employees(employee_id) ON DELETE CASCADE,
    CONSTRAINT fk_kpi_assignments_supervisor FOREIGN KEY (supervisor_id)
        REFERENCES em_employees(employee_id) ON DELETE SET NULL,
    CONSTRAINT fk_kpi_assignments_approver FOREIGN KEY (approved_by)
        REFERENCES em_employees(employee_id) ON DELETE SET NULL,
    CONSTRAINT fk_kpi_assignments_creator FOREIGN KEY (created_by)
        REFERENCES em_employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_kpi_entries (
    entry_id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    kpi_id INT NOT NULL,
    entry_date DATE NOT NULL,
    reporting_period VARCHAR(50) DEFAULT NULL,
    actual_value DECIMAL(14,4) NOT NULL,
    target_value DECIMAL(14,4) DEFAULT NULL,
    variance_value DECIMAL(14,4) DEFAULT NULL,
    variance_percentage DECIMAL(8,4) DEFAULT NULL,
    performance_score DECIMAL(5,2) DEFAULT NULL,
    performance_status ENUM('Not Started', 'At Risk', 'Behind', 'On Track', 'Exceeds Target', 'Completed') DEFAULT 'Not Started',
    entry_notes TEXT DEFAULT NULL,
    evidence_file_path VARCHAR(500) DEFAULT NULL,
    captured_by INT DEFAULT NULL,
    captured_by_name VARCHAR(255) DEFAULT NULL,
    reviewed_by INT DEFAULT NULL,
    reviewed_by_name VARCHAR(255) DEFAULT NULL,
    review_status ENUM('Pending Review', 'Reviewed', 'Verified', 'Disputed') DEFAULT 'Pending Review',
    review_comments TEXT DEFAULT NULL,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kpi_entries_assignment (assignment_id),
    INDEX idx_kpi_entries_kpi (kpi_id),
    INDEX idx_kpi_entries_date (entry_date),
    INDEX idx_kpi_entries_status (performance_status),
    CONSTRAINT fk_kpi_entries_assignment FOREIGN KEY (assignment_id)
        REFERENCES pm_kpi_assignments(assignment_id) ON DELETE CASCADE,
    CONSTRAINT fk_kpi_entries_definition FOREIGN KEY (kpi_id)
        REFERENCES pm_kpi_definitions(kpi_id) ON DELETE CASCADE,
    CONSTRAINT fk_kpi_entries_capturer FOREIGN KEY (captured_by)
        REFERENCES em_employees(employee_id) ON DELETE SET NULL,
    CONSTRAINT fk_kpi_entries_reviewer FOREIGN KEY (reviewed_by)
        REFERENCES em_employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_kpi_targets (
    target_id INT AUTO_INCREMENT PRIMARY KEY,
    kpi_id INT NOT NULL,
    assignment_id INT DEFAULT NULL,
    target_period VARCHAR(50) NOT NULL,
    target_period_start DATE NOT NULL,
    target_period_end DATE NOT NULL,
    baseline_value DECIMAL(14,4) DEFAULT NULL,
    target_value DECIMAL(14,4) NOT NULL,
    stretch_target_value DECIMAL(14,4) DEFAULT NULL,
    actual_value DECIMAL(14,4) DEFAULT NULL,
    achievement_percentage DECIMAL(8,4) DEFAULT NULL,
    target_status ENUM('Pending', 'In Progress', 'Met', 'Exceeded', 'Missed') DEFAULT 'Pending',
    set_by INT DEFAULT NULL,
    set_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kpi_targets_kpi (kpi_id),
    INDEX idx_kpi_targets_assignment (assignment_id),
    INDEX idx_kpi_targets_period (target_period_start, target_period_end),
    INDEX idx_kpi_targets_status (target_status),
    CONSTRAINT fk_kpi_targets_definition FOREIGN KEY (kpi_id)
        REFERENCES pm_kpi_definitions(kpi_id) ON DELETE CASCADE,
    CONSTRAINT fk_kpi_targets_assignment FOREIGN KEY (assignment_id)
        REFERENCES pm_kpi_assignments(assignment_id) ON DELETE SET NULL,
    CONSTRAINT fk_kpi_targets_employee FOREIGN KEY (set_by)
        REFERENCES em_employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_kpi_attachments (
    attachment_id INT AUTO_INCREMENT PRIMARY KEY,
    entry_id INT DEFAULT NULL,
    assignment_id INT DEFAULT NULL,
    kpi_id INT DEFAULT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(100) DEFAULT NULL,
    file_size BIGINT DEFAULT NULL,
    description VARCHAR(255) DEFAULT NULL,
    uploaded_by INT DEFAULT NULL,
    uploaded_by_name VARCHAR(255) DEFAULT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_kpi_attachments_entry (entry_id),
    INDEX idx_kpi_attachments_assignment (assignment_id),
    INDEX idx_kpi_attachments_kpi (kpi_id),
    CONSTRAINT fk_kpi_attachments_entry FOREIGN KEY (entry_id)
        REFERENCES pm_kpi_entries(entry_id) ON DELETE CASCADE,
    CONSTRAINT fk_kpi_attachments_assignment FOREIGN KEY (assignment_id)
        REFERENCES pm_kpi_assignments(assignment_id) ON DELETE CASCADE,
    CONSTRAINT fk_kpi_attachments_definition FOREIGN KEY (kpi_id)
        REFERENCES pm_kpi_definitions(kpi_id) ON DELETE CASCADE,
    CONSTRAINT fk_kpi_attachments_uploader FOREIGN KEY (uploaded_by)
        REFERENCES em_employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_kpi_comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT DEFAULT NULL,
    entry_id INT DEFAULT NULL,
    kpi_id INT DEFAULT NULL,
    user_id INT DEFAULT NULL,
    user_name VARCHAR(255) DEFAULT NULL,
    user_role VARCHAR(100) DEFAULT NULL,
    comment_text TEXT NOT NULL,
    comment_type ENUM('General', 'Question', 'Action Item', 'Feedback', 'Resolution') DEFAULT 'General',
    is_resolved TINYINT(1) DEFAULT 0,
    resolved_by INT DEFAULT NULL,
    resolved_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kpi_comments_assignment (assignment_id),
    INDEX idx_kpi_comments_entry (entry_id),
    INDEX idx_kpi_comments_kpi (kpi_id),
    INDEX idx_kpi_comments_user (user_id),
    CONSTRAINT fk_kpi_comments_assignment FOREIGN KEY (assignment_id)
        REFERENCES pm_kpi_assignments(assignment_id) ON DELETE CASCADE,
    CONSTRAINT fk_kpi_comments_entry FOREIGN KEY (entry_id)
        REFERENCES pm_kpi_entries(entry_id) ON DELETE CASCADE,
    CONSTRAINT fk_kpi_comments_definition FOREIGN KEY (kpi_id)
        REFERENCES pm_kpi_definitions(kpi_id) ON DELETE CASCADE,
    CONSTRAINT fk_kpi_comments_user FOREIGN KEY (user_id)
        REFERENCES em_employees(employee_id) ON DELETE SET NULL,
    CONSTRAINT fk_kpi_comments_resolver FOREIGN KEY (resolved_by)
        REFERENCES em_employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_kpi_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    kpi_id INT DEFAULT NULL,
    assignment_id INT DEFAULT NULL,
    entry_id INT DEFAULT NULL,
    action_type ENUM('Create', 'Update', 'Delete', 'Status Change', 'Review', 'Approval', 'Comment', 'Attachment') NOT NULL,
    field_changed VARCHAR(150) DEFAULT NULL,
    old_value TEXT DEFAULT NULL,
    new_value TEXT DEFAULT NULL,
    action_details TEXT DEFAULT NULL,
    performed_by INT DEFAULT NULL,
    performed_by_name VARCHAR(255) DEFAULT NULL,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_kpi_history_kpi (kpi_id),
    INDEX idx_kpi_history_assignment (assignment_id),
    INDEX idx_kpi_history_entry (entry_id),
    INDEX idx_kpi_history_action (action_type),
    CONSTRAINT fk_kpi_history_definition FOREIGN KEY (kpi_id)
        REFERENCES pm_kpi_definitions(kpi_id) ON DELETE SET NULL,
    CONSTRAINT fk_kpi_history_assignment FOREIGN KEY (assignment_id)
        REFERENCES pm_kpi_assignments(assignment_id) ON DELETE SET NULL,
    CONSTRAINT fk_kpi_history_entry FOREIGN KEY (entry_id)
        REFERENCES pm_kpi_entries(entry_id) ON DELETE SET NULL,
    CONSTRAINT fk_kpi_history_employee FOREIGN KEY (performed_by)
        REFERENCES em_employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- TRAINING DEVELOPMENT MODULE
-- ============================================================================

CREATE TABLE IF NOT EXISTS pm_training_programs (
    training_id INT AUTO_INCREMENT PRIMARY KEY,
    training_code VARCHAR(100) NOT NULL UNIQUE,
    training_title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    training_category VARCHAR(100) DEFAULT NULL,
    skill_focus VARCHAR(255) DEFAULT NULL,
    training_provider VARCHAR(255) DEFAULT NULL,
    training_type ENUM('Internal', 'External', 'Online') DEFAULT 'Internal',
    duration_hours DECIMAL(6,2) DEFAULT NULL,
    delivery_mode ENUM('Face-to-Face', 'Online', 'Hybrid') DEFAULT 'Online',
    cost DECIMAL(12,2) DEFAULT 0.00,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_training_programs_category (training_category),
    INDEX idx_training_programs_provider (training_provider),
    INDEX idx_training_programs_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_training_recommendations (
    recommendation_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    recommendation_date DATE DEFAULT NULL,
    source_type VARCHAR(100) DEFAULT NULL,
    source_id INT DEFAULT NULL,
    development_area VARCHAR(255) DEFAULT NULL,
    performance_gap TEXT DEFAULT NULL,
    recommendation_reason TEXT DEFAULT NULL,
    priority_level ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    recommended_by VARCHAR(255) DEFAULT NULL,
    status ENUM('Pending', 'Approved', 'Rejected', 'In Progress', 'Completed') DEFAULT 'Pending',
    target_completion_date DATE DEFAULT NULL,
    completion_date DATE DEFAULT NULL,
    remarks TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_training_recommendations_employee (employee_id),
    INDEX idx_training_recommendations_source (source_id),
    INDEX idx_training_recommendations_status (status),
    INDEX idx_training_recommendations_priority (priority_level),
    CONSTRAINT fk_training_recommendations_employee FOREIGN KEY (employee_id)
        REFERENCES em_employees(employee_id) ON DELETE CASCADE,
    CONSTRAINT fk_training_recommendations_program FOREIGN KEY (source_id)
        REFERENCES pm_training_programs(training_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_employee_training (
    employee_training_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    training_id INT NOT NULL,
    recommendation_id INT DEFAULT NULL,
    assigned_date DATE DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    attendance_status ENUM('Not Started', 'Attended', 'Absent') DEFAULT 'Not Started',
    completion_status ENUM('Pending', 'In Progress', 'Completed') DEFAULT 'Pending',
    completion_percentage DECIMAL(5,2) DEFAULT 0.00,
    final_score DECIMAL(6,2) DEFAULT NULL,
    certificate_status ENUM('Not Issued', 'Issued') DEFAULT 'Not Issued',
    certificate_reference VARCHAR(255) DEFAULT NULL,
    remarks TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_employee_training_employee (employee_id),
    INDEX idx_employee_training_program (training_id),
    INDEX idx_employee_training_recommendation (recommendation_id),
    INDEX idx_employee_training_status (completion_status),
    CONSTRAINT fk_employee_training_employee FOREIGN KEY (employee_id)
        REFERENCES em_employees(employee_id) ON DELETE CASCADE,
    CONSTRAINT fk_employee_training_program FOREIGN KEY (training_id)
        REFERENCES pm_training_programs(training_id) ON DELETE CASCADE,
    CONSTRAINT fk_employee_training_recommendation FOREIGN KEY (recommendation_id)
        REFERENCES pm_training_recommendations(recommendation_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS pm_training_evaluations (
    evaluation_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_training_id INT NOT NULL,
    evaluation_date DATE DEFAULT NULL,
    knowledge_rating DECIMAL(4,2) DEFAULT NULL,
    skill_rating DECIMAL(4,2) DEFAULT NULL,
    application_rating DECIMAL(4,2) DEFAULT NULL,
    overall_rating DECIMAL(4,2) DEFAULT NULL,
    feedback TEXT DEFAULT NULL,
    evaluated_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_training_evaluations_assignment (employee_training_id),
    INDEX idx_training_evaluations_evaluator (evaluated_by),
    CONSTRAINT fk_training_evaluations_assignment FOREIGN KEY (employee_training_id)
        REFERENCES pm_employee_training(employee_training_id) ON DELETE CASCADE,
    CONSTRAINT fk_training_evaluations_evaluator FOREIGN KEY (evaluated_by)
        REFERENCES em_employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

