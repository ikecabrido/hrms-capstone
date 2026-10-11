<?php

include_once __DIR__ . '/../../../database/db.php';

class Employee
{
    private $conn;
    private $employeeid;
    private $firstname;
    private $lastname;
    private $middlename;
    private $department;
    private $position;
    private $status;

    public function __construct($pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }
    }

    /**
     * Get all employees
     */
    public function getEmployees()
    {
        $sql = "SELECT 
                    e.employee_id,
                    e.first_name,
                    e.middle_name,
                    e.last_name,
                    d.department_name,
                    p.position_name,
                    e.employment_status AS status
                FROM em_employees AS e
                LEFT JOIN em_departments AS d
                    ON e.department_id = d.department_id
                LEFT JOIN em_positions AS p
                    ON e.position_id = p.position_id
                WHERE e.is_archived = 0
                ORDER BY e.employee_id";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDashboardAnalytics()
    {
        $metrics = $this->conn->query("
            SELECT
                COUNT(*) AS total_employees,
                SUM(employment_status = 'Active') AS active_employees,
                SUM(employment_status IN ('Resigned', 'Terminated')) AS separated_employees,
                SUM(hire_date >= DATE_FORMAT(CURDATE(), '%Y-01-01')) AS new_hires,
                ROUND(AVG(TIMESTAMPDIFF(YEAR, birth_date, CURDATE())), 1) AS average_age,
                ROUND(AVG(TIMESTAMPDIFF(MONTH, hire_date, CURDATE()) / 12), 1) AS average_tenure
            FROM em_employees
            WHERE is_archived = 0
        ")->fetch(PDO::FETCH_ASSOC) ?: [];

        $attendance = $this->conn->query("
            SELECT
                SUM(status = 'PRESENT') AS present_days,
                SUM(status = 'LATE') AS late_arrivals,
                SUM(status = 'ABSENT') AS absent_days,
                COUNT(*) AS recorded_days,
                SUM(status = 'ON_LEAVE') AS leave_days
            FROM ta_attendance
            WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ")->fetch(PDO::FETCH_ASSOC) ?: [];

        $todayAttendance = $this->conn->query("
            SELECT
                SUM(status IN ('PRESENT', 'LATE')) AS attended_today,
                SUM(status = 'PRESENT') AS present_today,
                SUM(status = 'LATE') AS late_today,
                SUM(status = 'ABSENT') AS absent_today,
                SUM(status = 'ON_LEAVE') AS leave_today
            FROM ta_attendance
            WHERE attendance_date = CURDATE()
        ")->fetch(PDO::FETCH_ASSOC) ?: [];

        $attendanceDays = (int) ($this->conn->query("
            SELECT COUNT(DISTINCT attendance_date)
            FROM ta_attendance
            WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ")->fetchColumn() ?: 0);

        $leave = $this->conn->query("
            SELECT COUNT(*) AS employees_on_leave
            FROM ta_leave_requests
            WHERE status = 'Approved'
              AND CURDATE() BETWEEN start_date AND end_date
        ")->fetch(PDO::FETCH_ASSOC) ?: [];

        $gender = $this->conn->query("
            SELECT gender, COUNT(*) AS total
            FROM em_employees
            WHERE is_archived = 0
            GROUP BY gender
            ORDER BY total DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $months = $this->conn->query("
            SELECT DATE_FORMAT(hire_date, '%b') AS month_name, COUNT(*) AS total
            FROM em_employees
            WHERE is_archived = 0
              AND hire_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
            GROUP BY YEAR(hire_date), MONTH(hire_date), DATE_FORMAT(hire_date, '%b')
            ORDER BY YEAR(hire_date), MONTH(hire_date)
        ")->fetchAll(PDO::FETCH_ASSOC);

        $metrics['present_days'] = (int) ($attendance['present_days'] ?? 0);
        $metrics['late_arrivals'] = (int) ($attendance['late_arrivals'] ?? 0);
        $metrics['absent_days'] = (int) ($attendance['absent_days'] ?? 0);
        $metrics['attendance_recorded_days'] = (int) ($attendance['recorded_days'] ?? 0);
        $metrics['attendance_rate'] = !empty($attendance['recorded_days'])
            ? round((((int) $attendance['present_days'] + (int) $attendance['late_arrivals']) / (int) $attendance['recorded_days']) * 100, 1)
            : 0;
        $metrics['leave_days'] = (int) ($attendance['leave_days'] ?? 0);
        $metrics['employees_on_leave'] = (int) ($leave['employees_on_leave'] ?? 0);
        $metrics['attended_today'] = (int) ($todayAttendance['attended_today'] ?? 0);
        $metrics['present_today'] = (int) ($todayAttendance['present_today'] ?? 0);
        $metrics['late_today'] = (int) ($todayAttendance['late_today'] ?? 0);
        $metrics['absent_today'] = (int) ($todayAttendance['absent_today'] ?? 0);
        $metrics['leave_today'] = (int) ($todayAttendance['leave_today'] ?? 0);
        $metrics['attendance_days'] = $attendanceDays;
        $metrics['today_attendance_rate'] = !empty($metrics['active_employees'])
            ? round(((int) $metrics['attended_today'] / (int) $metrics['active_employees']) * 100, 1)
            : 0;
        $metrics['estimated_30_day_rate'] = !empty($metrics['active_employees']) && $attendanceDays > 0
            ? round((((int) $metrics['present_days'] + (int) $metrics['late_arrivals']) / ((int) $metrics['active_employees'] * $attendanceDays)) * 100, 1)
            : 0;
        $metrics['turnover_rate'] = !empty($metrics['total_employees'])
            ? round(((int) $metrics['separated_employees'] / (int) $metrics['total_employees']) * 100, 1)
            : 0;

        return [
            'metrics' => $metrics,
            'gender' => $gender,
            'hire_months' => $months,
        ];
    }

    public function getLearningAnalytics()
    {
        $enrollmentStats = $this->conn->query("
            SELECT
                COUNT(*) AS total_enrollments,
                SUM(status = 'completed') AS completed_enrollments,
                SUM(status = 'in_progress') AS in_progress_enrollments,
                SUM(status NOT IN ('completed', 'in_progress')) AS pending_enrollments
            FROM ld_enrollment
        ")->fetch(PDO::FETCH_ASSOC) ?: [];

        $certificateCount = $this->conn->query("
            SELECT COUNT(*) AS total_certificates
            FROM ld_certificate
        ")->fetch(PDO::FETCH_ASSOC) ?: [];

        $topCourses = $this->conn->query("
            SELECT c.title, COUNT(e.id) AS enrollment_count,
                   SUM(e.status = 'completed') AS completion_count
            FROM ld_course c
            INNER JOIN ld_enrollment e ON e.course_id = c.id
            GROUP BY c.id, c.title
            ORDER BY enrollment_count DESC, c.title ASC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total_enrollments' => (int) ($enrollmentStats['total_enrollments'] ?? 0),
            'completed_enrollments' => (int) ($enrollmentStats['completed_enrollments'] ?? 0),
            'in_progress_enrollments' => (int) ($enrollmentStats['in_progress_enrollments'] ?? 0),
            'pending_enrollments' => (int) ($enrollmentStats['pending_enrollments'] ?? 0),
            'total_certificates' => (int) ($certificateCount['total_certificates'] ?? 0),
            'top_courses' => $topCourses,
        ];
    }

    public function getTrainingReport()
    {
        $summary = $this->conn->query("
            SELECT
                COUNT(*) AS total_enrollments,
                COUNT(DISTINCT learner_id) AS participating_employees,
                SUM(status = 'completed') AS completed_enrollments,
                SUM(status IN ('enrolled', 'in_progress')) AS active_enrollments,
                ROUND(SUM(status = 'completed') * 100 / NULLIF(COUNT(*), 0), 1) AS completion_rate
            FROM ld_enrollment
        ")->fetch(PDO::FETCH_ASSOC) ?: [];

        $courseBreakdown = $this->conn->query("
            SELECT c.title,
                   COUNT(e.id) AS participants,
                   SUM(e.status = 'completed') AS completed,
                   ROUND(SUM(e.status = 'completed') * 100 / NULLIF(COUNT(e.id), 0), 1) AS completion_rate
            FROM ld_course c
            INNER JOIN ld_enrollment e ON e.course_id = c.id
            GROUP BY c.id, c.title
            ORDER BY participants DESC, c.title ASC
            LIMIT 8
        ")->fetchAll(PDO::FETCH_ASSOC);

        $departmentBreakdown = $this->conn->query("
            SELECT COALESCE(d.department_name, 'Unassigned') AS department_name,
                   COUNT(e.id) AS participants,
                   SUM(e.status = 'completed') AS completed
            FROM ld_enrollment e
            INNER JOIN em_employees emp ON emp.employee_id = e.learner_id
            LEFT JOIN em_departments d ON d.department_id = emp.department_id
            GROUP BY d.department_id, d.department_name
            ORDER BY participants DESC, department_name ASC
            LIMIT 8
        ")->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total_enrollments' => (int) ($summary['total_enrollments'] ?? 0),
            'participating_employees' => (int) ($summary['participating_employees'] ?? 0),
            'completed_enrollments' => (int) ($summary['completed_enrollments'] ?? 0),
            'active_enrollments' => (int) ($summary['active_enrollments'] ?? 0),
            'completion_rate' => (float) ($summary['completion_rate'] ?? 0),
            'course_breakdown' => $courseBreakdown,
            'department_breakdown' => $departmentBreakdown,
        ];
    }

    public function saveLearningReportingRequirement(array $input): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO ld_reporting_requirement
                (target_name, reporting_period_start, reporting_period_end, reporting_frequency, completion_target, expected_output, notes, created_by)
            VALUES
                (:target_name, :period_start, :period_end, :reporting_frequency, :completion_target, :expected_output, :notes, :created_by)
        ");
        $stmt->execute([
            ':target_name' => $input['target_name'],
            ':period_start' => $input['period_start'],
            ':period_end' => $input['period_end'],
            ':reporting_frequency' => $input['reporting_frequency'],
            ':completion_target' => $input['completion_target'],
            ':expected_output' => $input['expected_output'],
            ':notes' => $input['notes'] !== '' ? $input['notes'] : null,
            ':created_by' => $input['created_by'] > 0 ? $input['created_by'] : null,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    public function getLearningReportingRequirements(int $limit = 8): array
    {
        $limit = max(1, min($limit, 50));
        return $this->conn->query("
            SELECT id, target_name, reporting_period_start, reporting_period_end,
                   reporting_frequency, completion_target, expected_output, notes, status, created_at
            FROM ld_reporting_requirement
            ORDER BY created_at DESC, id DESC
            LIMIT {$limit}
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateLearningReportingRequirement(int $id, array $input): void
    {
        $stmt = $this->conn->prepare("
            UPDATE ld_reporting_requirement
            SET target_name = :target_name,
                reporting_period_start = :period_start,
                reporting_period_end = :period_end,
                reporting_frequency = :reporting_frequency,
                completion_target = :completion_target,
                expected_output = :expected_output,
                notes = :notes
            WHERE id = :id
        ");
        $stmt->execute([
            ':id' => $id,
            ':target_name' => $input['target_name'],
            ':period_start' => $input['period_start'],
            ':period_end' => $input['period_end'],
            ':reporting_frequency' => $input['reporting_frequency'],
            ':completion_target' => $input['completion_target'],
            ':expected_output' => $input['expected_output'],
            ':notes' => $input['notes'] !== '' ? $input['notes'] : null,
        ]);
    }

    public function deleteLearningReportingRequirement(int $id): void
    {
        $stmt = $this->conn->prepare('DELETE FROM ld_reporting_requirement WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function saveCompetencyRequirement(array $input): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO ld_competency_requirement
                (skill_name, target_department, priority_level, employees_needing_training,
                 target_proficiency, expected_learning_output, notes, created_by)
            VALUES
                (:skill_name, :target_department, :priority_level, :employees_needing_training,
                 :target_proficiency, :expected_learning_output, :notes, :created_by)
        ");
        $stmt->execute([
            ':skill_name' => $input['skill_name'],
            ':target_department' => $input['target_department'],
            ':priority_level' => $input['priority_level'],
            ':employees_needing_training' => $input['employees_needing_training'],
            ':target_proficiency' => $input['target_proficiency'],
            ':expected_learning_output' => $input['expected_learning_output'],
            ':notes' => $input['notes'] !== '' ? $input['notes'] : null,
            ':created_by' => $input['created_by'] > 0 ? $input['created_by'] : null,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    public function getCompetencyRequirements(int $limit = 8): array
    {
        $limit = max(1, min($limit, 50));
        return $this->conn->query("
            SELECT id, skill_name, target_department, priority_level,
                   employees_needing_training, target_proficiency,
                   expected_learning_output, notes, status, created_at
            FROM ld_competency_requirement
            ORDER BY created_at DESC, id DESC
            LIMIT {$limit}
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateCompetencyRequirement(int $id, array $input): void
    {
        $stmt = $this->conn->prepare("
            UPDATE ld_competency_requirement
            SET skill_name = :skill_name,
                target_department = :target_department,
                priority_level = :priority_level,
                employees_needing_training = :employees_needing_training,
                target_proficiency = :target_proficiency,
                expected_learning_output = :expected_learning_output,
                notes = :notes
            WHERE id = :id
        ");
        $stmt->execute([
            ':id' => $id,
            ':skill_name' => $input['skill_name'],
            ':target_department' => $input['target_department'],
            ':priority_level' => $input['priority_level'],
            ':employees_needing_training' => $input['employees_needing_training'],
            ':target_proficiency' => $input['target_proficiency'],
            ':expected_learning_output' => $input['expected_learning_output'],
            ':notes' => $input['notes'] !== '' ? $input['notes'] : null,
        ]);
    }

    public function deleteCompetencyRequirement(int $id): void
    {
        $stmt = $this->conn->prepare('DELETE FROM ld_competency_requirement WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function getLearningEngagementTrends(int $limit = 8): array
    {
        $limit = max(1, min($limit, 50));
        return $this->conn->query("
            SELECT
                es.snapshot_date,
                es.active_days_30,
                es.courses_in_progress,
                es.courses_completed_ytd,
                es.days_since_last_activity,
                es.risk_score,
                CONCAT(emp.first_name, ' ', emp.last_name) AS learner_name,
                COALESCE(d.department_name, 'Unassigned') AS department_name
            FROM ld_engagement_snapshot es
            INNER JOIN (
                SELECT learner_id, MAX(snapshot_date) AS snapshot_date
                FROM ld_engagement_snapshot
                GROUP BY learner_id
            ) latest ON latest.learner_id = es.learner_id
                AND latest.snapshot_date = es.snapshot_date
            INNER JOIN em_employees emp ON emp.employee_id = es.learner_id
            LEFT JOIN em_departments d ON d.department_id = emp.department_id
            ORDER BY COALESCE(es.risk_score, 0) DESC, es.active_days_30 ASC, learner_name ASC
            LIMIT {$limit}
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveTrainingEngagement(array $input): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO wf_training_engagement
                (learner_team, department, training_activity, engagement_status,
                 engagement_data, learning_action, created_by)
            VALUES
                (:learner_team, :department, :training_activity, :engagement_status,
                 :engagement_data, :learning_action, :created_by)
        ");
        $stmt->execute([
            ':learner_team' => $input['learner_team'],
            ':department' => $input['department'],
            ':training_activity' => $input['training_activity'],
            ':engagement_status' => $input['engagement_status'],
            ':engagement_data' => $input['engagement_data'],
            ':learning_action' => $input['learning_action'],
            ':created_by' => $input['created_by'] > 0 ? $input['created_by'] : null,
        ]);

        $workforceId = (int) $this->conn->lastInsertId();

        $learningStmt = $this->conn->prepare("
            INSERT INTO ld_training_engagement
                (source_module, external_reference_id, learner_team_type, learner_id, learner_name,
                 department, training_activity, engagement_status, engagement_data,
                 recommended_action, notes, status, created_by)
            VALUES
                (:source_module, :external_reference_id, :learner_team_type, :learner_id, :learner_name,
                 :department, :training_activity, :engagement_status, :engagement_data,
                 :recommended_action, :notes, :status, :created_by)
        ");

        $learningStmt->execute([
            ':source_module' => 'workforce',
            ':external_reference_id' => (string) $workforceId,
            ':learner_team_type' => $input['learner_team'],
            ':learner_id' => null,
            ':learner_name' => null,
            ':department' => $input['department'],
            ':training_activity' => $input['training_activity'],
            ':engagement_status' => $input['engagement_status'],
            ':engagement_data' => $input['engagement_data'],
            ':recommended_action' => $input['learning_action'],
            ':notes' => null,
            ':status' => 'received',
            ':created_by' => $input['created_by'] > 0 ? $input['created_by'] : null,
        ]);

        return $workforceId;
    }

    public function getTrainingEngagementInputs(int $limit = 20): array
    {
        $limit = max(1, min($limit, 100));
        return $this->conn->query("
            SELECT id, learner_team, department, training_activity,
                   engagement_status, engagement_data, learning_action,
                   created_by, created_at, updated_at
            FROM wf_training_engagement
            ORDER BY created_at DESC, id DESC
            LIMIT {$limit}
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateTrainingEngagement(int $id, array $input): void
    {
        $stmt = $this->conn->prepare("
            UPDATE wf_training_engagement
            SET learner_team = :learner_team,
                department = :department,
                training_activity = :training_activity,
                engagement_status = :engagement_status,
                engagement_data = :engagement_data,
                learning_action = :learning_action
            WHERE id = :id
        ");
        $stmt->execute([
            ':id' => $id,
            ':learner_team' => $input['learner_team'],
            ':department' => $input['department'],
            ':training_activity' => $input['training_activity'],
            ':engagement_status' => $input['engagement_status'],
            ':engagement_data' => $input['engagement_data'],
            ':learning_action' => $input['learning_action'],
        ]);

        $learningStmt = $this->conn->prepare("
            UPDATE ld_training_engagement
            SET learner_team_type = :learner_team_type,
                department = :department,
                training_activity = :training_activity,
                engagement_status = :engagement_status,
                engagement_data = :engagement_data,
                recommended_action = :recommended_action,
                status = :status
            WHERE source_module = 'workforce'
              AND external_reference_id = :external_reference_id
        ");

        $learningStmt->execute([
            ':learner_team_type' => $input['learner_team'],
            ':department' => $input['department'],
            ':training_activity' => $input['training_activity'],
            ':engagement_status' => $input['engagement_status'],
            ':engagement_data' => $input['engagement_data'],
            ':recommended_action' => $input['learning_action'],
            ':status' => 'monitoring',
            ':external_reference_id' => (string) $id,
        ]);
    }

    public function deleteTrainingEngagement(int $id): void
    {
        $stmt = $this->conn->prepare('DELETE FROM wf_training_engagement WHERE id = :id');
        $stmt->execute([':id' => $id]);

        $learningStmt = $this->conn->prepare("DELETE FROM ld_training_engagement WHERE source_module = 'workforce' AND external_reference_id = :external_reference_id");
        $learningStmt->execute([':external_reference_id' => (string) $id]);
    }

    public function getTrainingParticipationList(int $limit = 20): array
    {
        $limit = max(1, min($limit, 100));
        return $this->conn->query("
            SELECT
                e.id,
                CONCAT(emp.first_name, ' ', emp.last_name) AS learner_name,
                COALESCE(d.department_name, 'Unassigned') AS department_name,
                c.title AS course_title,
                e.status,
                e.enrolled_at,
                e.completed_at,
                CASE
                    WHEN e.status = 'completed' THEN 100
                    WHEN e.status = 'in_progress' THEN 50
                    WHEN e.status IN ('enrolled', 'invited') THEN 0
                    ELSE 0
                END AS completion_percentage
            FROM ld_enrollment e
            INNER JOIN em_employees emp ON emp.employee_id = e.learner_id
            INNER JOIN ld_course c ON c.id = e.course_id
            LEFT JOIN em_departments d ON d.department_id = emp.department_id
            ORDER BY COALESCE(e.enrolled_at, e.created_at) DESC, e.id DESC
            LIMIT {$limit}
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCertificationStatus()
    {
        $status = $this->conn->query("
            SELECT
                COUNT(*) AS total_certificates,
                SUM(status = 'active' AND (valid_until IS NULL OR valid_until >= CURDATE())) AS active_certificates,
                SUM(status = 'active' AND valid_until IS NOT NULL AND valid_until < CURDATE()) AS expired_certificates,
                SUM(status = 'active' AND valid_until IS NOT NULL
                    AND valid_until BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)) AS expiring_certificates,
                SUM(status = 'archived') AS archived_certificates
            FROM ld_certificate
        ")->fetch(PDO::FETCH_ASSOC) ?: [];

        $certificates = $this->conn->query("
            SELECT
                cert.id,
                cert.verification_code,
                cert.issued_at,
                cert.valid_until,
                cert.status,
                course.title AS course_title,
                CONCAT(emp.first_name, ' ', emp.last_name) AS learner_name
            FROM ld_certificate cert
            INNER JOIN ld_course course ON course.id = cert.course_id
            INNER JOIN em_employees emp ON emp.employee_id = cert.learner_id
            ORDER BY cert.issued_at DESC, cert.id DESC
            LIMIT 8
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($certificates as &$certificate) {
            if ($certificate['status'] === 'archived') {
                $certificate['display_status'] = 'Archived';
            } elseif ($certificate['valid_until'] !== null && $certificate['valid_until'] < date('Y-m-d')) {
                $certificate['display_status'] = 'Expired';
            } elseif ($certificate['valid_until'] !== null && $certificate['valid_until'] <= date('Y-m-d', strtotime('+30 days'))) {
                $certificate['display_status'] = 'Expiring soon';
            } else {
                $certificate['display_status'] = 'Active';
            }
        }
        unset($certificate);

        return [
            'total_certificates' => (int) ($status['total_certificates'] ?? 0),
            'active_certificates' => (int) ($status['active_certificates'] ?? 0),
            'expired_certificates' => (int) ($status['expired_certificates'] ?? 0),
            'expiring_certificates' => (int) ($status['expiring_certificates'] ?? 0),
            'archived_certificates' => (int) ($status['archived_certificates'] ?? 0),
            'certificates' => $certificates,
        ];
    }

    public function getJobVacancies()
    {
        $stmt = $this->conn->prepare("
            SELECT
                j.id,
                j.title,
                j.category,
                j.location,
                j.max_applicants,
                COUNT(a.id) AS total_applicants
            FROM rao_jobs AS j
            LEFT JOIN rao_applications AS a
                ON a.job_id = j.id
               AND a.is_archived = 0
            GROUP BY j.id, j.title, j.category, j.location, j.max_applicants
            ORDER BY j.id DESC
            LIMIT 6
        ");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getHiringSlotNotifications()
    {
        $stmt = $this->conn->prepare("
            SELECT
                j.id,
                j.title,
                j.department,
                j.location,
                j.max_applicants,
                j.is_posted,
                COUNT(a.id) AS total_applicants
            FROM rao_jobs AS j
            LEFT JOIN rao_applications AS a
                ON a.job_id = j.id
               AND a.is_archived = 0
            GROUP BY j.id, j.title, j.department, j.location, j.max_applicants, j.is_posted
            HAVING j.max_applicants = 0 OR COUNT(a.id) < j.max_applicants
            ORDER BY j.id DESC
            LIMIT 8
        ");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createHiringSlot($title, $department, $location, $maxApplicants, $description, $qualifications)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO rao_jobs
                (title, department, description, qualifications, category, location, max_applicants, is_posted)
            VALUES
                (:title, :department, :description, :qualifications, 'Workforce hiring request', :location, :max_applicants, 0)
        ");
        $stmt->execute([
            ':title' => $title,
            ':department' => $department,
            ':description' => $description,
            ':qualifications' => $qualifications,
            ':location' => $location,
            ':max_applicants' => $maxApplicants,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    public function getHiringPositions()
    {
        $stmt = $this->conn->query("
            SELECT position_id, position_name
            FROM em_positions
            WHERE position_name IS NOT NULL
              AND TRIM(position_name) <> ''
            ORDER BY position_name ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getHiringDepartments()
    {
        $stmt = $this->conn->query("
            SELECT department_id, department_name
            FROM em_departments
            WHERE department_name IS NOT NULL
              AND TRIM(department_name) <> ''
            ORDER BY department_name ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getHiringLocations()
    {
        $stmt = $this->conn->query("
            SELECT DISTINCT TRIM(location) AS location
            FROM rao_jobs
            WHERE location IS NOT NULL
              AND TRIM(location) <> ''
            ORDER BY location ASC
        ");

        $locations = array_map(
            static function ($row) {
                return $row['location'];
            },
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );

        foreach (['Main campus', 'Remote', 'Hybrid'] as $defaultLocation) {
            if (!in_array($defaultLocation, $locations, true)) {
                $locations[] = $defaultLocation;
            }
        }
        sort($locations, SORT_NATURAL | SORT_FLAG_CASE);

        return $locations;
    }

    public function getAttritionAnalytics()
    {
        $db = $this->conn;
        // Exit records are created by the exit module, not by the unused
        // workforce snapshot tables.
        $employees = $db->query("
            SELECT CONCAT(e.first_name, ' ', e.last_name) COLLATE utf8mb4_unicode_ci AS employee_name,
                   COALESCE(d.department_name, 'Unassigned') COLLATE utf8mb4_unicode_ci AS department,
                   COALESCE(p.position_name, 'Unassigned') COLLATE utf8mb4_unicode_ci AS position,
                   e.hire_date, r.intended_last_working_day AS separation_date,
                   'resigned' COLLATE utf8mb4_unicode_ci AS separation_type,
                   r.resignation_reason COLLATE utf8mb4_unicode_ci AS reason_for_leaving
            FROM ep_resignation_requests r
            INNER JOIN em_employees e ON e.employee_id = r.employee_id
            LEFT JOIN em_departments d ON d.department_id = e.department_id
            LEFT JOIN em_positions p ON p.position_id = e.position_id
            WHERE r.status = 'Approved'
            UNION ALL
            SELECT CONCAT(e.first_name, ' ', e.last_name) COLLATE utf8mb4_unicode_ci AS employee_name,
                   COALESCE(d.department_name, 'Unassigned') COLLATE utf8mb4_unicode_ci AS department,
                   COALESCE(p.position_name, 'Unassigned') COLLATE utf8mb4_unicode_ci AS position,
                   e.hire_date, t.effective_date AS separation_date,
                   'terminated' COLLATE utf8mb4_unicode_ci AS separation_type,
                   t.termination_reason COLLATE utf8mb4_unicode_ci AS reason_for_leaving
            FROM exit_terminations t
            INNER JOIN em_employees e ON e.employee_id = t.employee_id
            LEFT JOIN em_departments d ON d.department_id = e.department_id
            LEFT JOIN em_positions p ON p.position_id = e.position_id
            WHERE t.status = 'approved'
            UNION ALL
            SELECT CONCAT(e.first_name, ' ', e.last_name) COLLATE utf8mb4_unicode_ci AS employee_name,
                   COALESCE(d.department_name, 'Unassigned') COLLATE utf8mb4_unicode_ci AS department,
                   COALESCE(p.position_name, 'Unassigned') COLLATE utf8mb4_unicode_ci AS position,
                   e.hire_date, COALESCE(e.archived_date, e.updated_at, CURDATE()) AS separation_date,
                   IF(e.employment_status = 'Resigned', 'resigned', 'terminated') COLLATE utf8mb4_unicode_ci AS separation_type,
                   'Not specified' COLLATE utf8mb4_unicode_ci AS reason_for_leaving
            FROM em_employees e
            LEFT JOIN em_departments d ON d.department_id = e.department_id
            LEFT JOIN em_positions p ON p.position_id = e.position_id
            WHERE e.employment_status IN ('Resigned', 'Terminated')
              AND NOT EXISTS (
                  SELECT 1 FROM ep_resignation_requests r
                  WHERE r.employee_id = e.employee_id AND r.status = 'Approved'
              )
              AND NOT EXISTS (
                  SELECT 1 FROM exit_terminations t
                  WHERE t.employee_id = e.employee_id AND t.status = 'approved'
              )
            ORDER BY separation_date DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $summary = [
            'resignations' => 0,
            'terminations' => 0,
            'voluntary' => 0,
            'involuntary' => 0,
            'average_tenure' => 0,
        ];
        $months = [];
        $departments = [];
        $reasons = [];
        $tenures = [];
        foreach ($employees as &$employee) {
            $type = strtolower($employee['separation_type']);
            $summary[$type === 'resigned' ? 'resignations' : 'terminations']++;
            $summary[$type === 'resigned' ? 'voluntary' : 'involuntary']++;

            $monthKey = date('Y-m', strtotime($employee['separation_date']));
            if (!isset($months[$monthKey])) {
                $months[$monthKey] = ['period' => date('M Y', strtotime($employee['separation_date'])), 'total_separations' => 0, 'voluntary_separations' => 0, 'involuntary_separations' => 0, 'attrition_rate_percent' => 0, 'average_tenure_departing' => 0];
            }
            $months[$monthKey]['total_separations']++;
            $months[$monthKey][$type === 'resigned' ? 'voluntary_separations' : 'involuntary_separations']++;

            $department = $employee['department'];
            $departments[$department] = ($departments[$department] ?? 0) + 1;
            $reason = trim((string) $employee['reason_for_leaving']) ?: 'Not specified';
            $reasons[$reason] = ($reasons[$reason] ?? 0) + 1;

            $tenure = (!empty($employee['hire_date']) && $employee['hire_date'] !== '0000-00-00')
                ? max(0, (strtotime($employee['separation_date']) - strtotime($employee['hire_date'])) / 31557600)
                : 0;
            $tenures[] = $tenure;
            $employee['separation_date'] = date('Y-m-d', strtotime($employee['separation_date']));
        }
        unset($employee);
        $summary['average_tenure'] = $tenures ? round(array_sum($tenures) / count($tenures), 1) : 0;
        krsort($months);
        $monthly = array_slice(array_values($months), 0, 12);
        $workforceSize = max(1, (int) $db->query("SELECT COUNT(*) FROM em_employees WHERE is_archived = 0")->fetchColumn());
        foreach ($monthly as &$month) {
            $month['attrition_rate_percent'] = round(($month['total_separations'] / $workforceSize) * 100, 2);
        }
        unset($month);
        arsort($departments);
        arsort($reasons);
        $departments = array_map(function ($department, $total) {
            return ['department' => $department, 'total' => $total];
        }, array_keys(array_slice($departments, 0, 8, true)), array_values(array_slice($departments, 0, 8, true)));
        $reasons = array_map(function ($reason, $total) {
            return ['reason' => $reason, 'total' => $total];
        }, array_keys(array_slice($reasons, 0, 6, true)), array_values(array_slice($reasons, 0, 6, true)));
        return ['summary' => $summary, 'monthly' => $monthly, 'departments' => $departments, 'reasons' => $reasons, 'employees' => $employees];
    }

    public function getDiversityAnalytics()
    {
        $db = $this->conn;
        $metrics = $db->query("
            SELECT
                COUNT(*) AS total_employees,
                SUM(LOWER(employment_status) = 'active') AS active_employees,
                SUM(LOWER(gender) = 'female' AND LOWER(employment_status) = 'active') AS women_active,
                SUM(
                    LOWER(employment_status) = 'active'
                    AND LOWER(COALESCE(p.position_name, '')) REGEXP 'manager|director|supervisor|lead|chief|head'
                ) AS leadership_active,
                SUM(gender IS NOT NULL AND TRIM(gender) <> ''
                    AND birth_date IS NOT NULL
                    AND e.department_id IS NOT NULL
                    AND e.position_id IS NOT NULL) AS complete_profiles
            FROM em_employees e
            LEFT JOIN em_positions p ON p.position_id = e.position_id
            WHERE e.is_archived = 0
        ")->fetch(PDO::FETCH_ASSOC) ?: [];

        $gender = $db->query("
            SELECT COALESCE(NULLIF(TRIM(gender), ''), 'Not specified') AS label, COUNT(*) AS total
            FROM em_employees
            WHERE is_archived = 0 AND LOWER(employment_status) = 'active'
            GROUP BY label ORDER BY total DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $age = $db->query("
            SELECT
                CASE
                    WHEN birth_date IS NULL THEN 'Not specified'
                    WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) < 25 THEN 'Under 25'
                    WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) < 35 THEN '25-34'
                    WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) < 45 THEN '35-44'
                    WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) < 55 THEN '45-54'
                    ELSE '55+'
                END AS label,
                COUNT(*) AS total
            FROM em_employees
            WHERE is_archived = 0 AND LOWER(employment_status) = 'active'
            GROUP BY label
            ORDER BY FIELD(label, 'Under 25', '25-34', '35-44', '45-54', '55+', 'Not specified')
        ")->fetchAll(PDO::FETCH_ASSOC);

        $departments = $db->query("
            SELECT COALESCE(d.department_name, 'Unassigned') AS department,
                   COUNT(*) AS total,
                   SUM(LOWER(e.gender) = 'female') AS women
            FROM em_employees e
            LEFT JOIN em_departments d ON d.department_id = e.department_id
            WHERE e.is_archived = 0 AND LOWER(e.employment_status) = 'active'
            GROUP BY e.department_id, d.department_name
            ORDER BY total DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $employmentTypes = $db->query("
            SELECT COALESCE(NULLIF(TRIM(employment_type), ''), 'Not specified') AS label, COUNT(*) AS total
            FROM em_employees
            WHERE is_archived = 0 AND LOWER(employment_status) = 'active'
            GROUP BY label ORDER BY total DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $leaders = $db->query("
            SELECT CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
                   COALESCE(p.position_name, 'Unassigned') AS position,
                   COALESCE(d.department_name, 'Unassigned') AS department,
                   COALESCE(NULLIF(TRIM(e.gender), ''), 'Not specified') AS gender
            FROM em_employees e
            LEFT JOIN em_positions p ON p.position_id = e.position_id
            LEFT JOIN em_departments d ON d.department_id = e.department_id
            WHERE e.is_archived = 0
              AND LOWER(e.employment_status) = 'active'
              AND LOWER(COALESCE(p.position_name, '')) REGEXP 'manager|director|supervisor|lead|chief|head'
            ORDER BY e.last_name, e.first_name
            LIMIT 12
        ")->fetchAll(PDO::FETCH_ASSOC);

        $metrics['women_percent'] = !empty($metrics['active_employees'])
            ? round(((int) $metrics['women_active'] / (int) $metrics['active_employees']) * 100, 1) : 0;
        $metrics['leadership_percent'] = !empty($metrics['active_employees'])
            ? round(((int) $metrics['leadership_active'] / (int) $metrics['active_employees']) * 100, 1) : 0;
        $metrics['profile_percent'] = !empty($metrics['total_employees'])
            ? round(((int) $metrics['complete_profiles'] / (int) $metrics['total_employees']) * 100, 1) : 0;

        return [
            'metrics' => $metrics,
            'gender' => $gender,
            'age' => $age,
            'departments' => $departments,
            'employment_types' => $employmentTypes,
            'leaders' => $leaders,
        ];
    }

    /**
     * Get logged-in employee ID
     */
    public function getEmployeeId()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION['employee_id'] ?? null;
    }

    /**
     * Get logged-in employee name
     */
    public function getEmployeeName()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $employeeId = $_SESSION['employee_id'] ?? null;

        if ($employeeId) {
            $sql = "SELECT first_name, last_name
                    FROM em_employees
                    WHERE employee_id = :employee_id
                    LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':employee_id', $employeeId);
            $stmt->execute();

            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($employee) {
                return htmlspecialchars(
                    $employee['first_name'] . ' ' . $employee['last_name']
                );
            }
        }

        return 'Unknown User';
    }

    /**
     * Get logged-in employee position
     */
    public function getEmployeePosition()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $employeeId = $_SESSION['employee_id'] ?? null;

        if ($employeeId) {
            $sql = "SELECT p.position_name
                    FROM em_employees AS e
                    LEFT JOIN em_positions AS p
                        ON e.position_id = p.position_id
                    WHERE e.employee_id = :employee_id
                    LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':employee_id', $employeeId);
            $stmt->execute();

            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($employee) {
                return htmlspecialchars($employee['position_name']);
            }
        }

        return 'Unknown Position';
    }
}
