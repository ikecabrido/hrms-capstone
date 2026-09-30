<?php

require_once __DIR__ . '/../../../database/db.php';

class Interview
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Check if an interview stage already exists
     */
    public function stageExists($application_id, $type): bool
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM rao_interviews
            WHERE application_id = :application_id
            AND interview_type = :interview_type
            LIMIT 1
        ");

        $stmt->execute([
            ':application_id' => $application_id,
            ':interview_type' => $type
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    /**
     * Create interview
     */
    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO rao_interviews
            (
                application_id,
                interview_date,
                interview_time,
                interview_type,
                stage_order,
                interview_mode,
                meeting_link,
                interviewer
            )
            VALUES
            (
                :application_id,
                :interview_date,
                :interview_time,
                :interview_type,
                :stage_order,
                :interview_mode,
                :meeting_link,
                :interviewer
            )
        ");

        return $stmt->execute([
            ':application_id' => $data['application_id'],
            ':interview_date' => $data['interview_date'],
            ':interview_time' => $data['interview_time'],
            ':interview_type' => $data['interview_type'],
            ':stage_order' => $data['stage_order'],
            ':interview_mode' => $data['interview_mode'],
            ':meeting_link' => $data['meeting_link'],
            ':interviewer' => $data['interviewer']
        ]);
    }

    /**
     * Get one interview by application ID
     */
    public function findByApplicationId(int $application_id)
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM rao_interviews
            WHERE application_id = :application_id
            ORDER BY stage_order ASC
            LIMIT 1
        ");

        $stmt->execute([
            ':application_id' => $application_id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all interviews for an application
     */
    public function getByApplicationId($application_id)
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM rao_interviews
            WHERE application_id = :application_id
            ORDER BY stage_order ASC
        ");

        $stmt->execute([
            ':application_id' => $application_id
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get next interview stage
     */
    public function getNextStageOrder($application_id)
    {
        $stmt = $this->db->prepare("
            SELECT MAX(stage_order) AS max_stage
            FROM rao_interviews
            WHERE application_id = :application_id
        ");

        $stmt->execute([
            ':application_id' => $application_id
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return ((int)($row['max_stage'] ?? 0)) + 1;
    }

    /**
     * Check whether applicant can proceed
     */
    /**
     * Check whether applicant can proceed to the next interview stage
     */
    /**
     * Check whether applicant can proceed to next interview stage
     */
    public function canProceed($application_id, $stage_order = null): bool
    {
        $stmt = $this->db->prepare("
        SELECT stage_order, interview_type, result
        FROM rao_interviews
        WHERE application_id = :application_id
        ORDER BY stage_order DESC
        LIMIT 1
    ");

        $stmt->execute([
            ':application_id' => (int) $application_id
        ]);

        $latest = $stmt->fetch(PDO::FETCH_ASSOC);

        /*
     * No previous interview:
     * Initial Interview is allowed.
     */
        if (!$latest) {
            return true;
        }

        $latestStage = (int) $latest['stage_order'];

        $latestResult = strtolower(
            trim($latest['result'] ?? 'pending')
        );

        /*
     * Current/latest interview must be passed
     * before scheduling the next stage.
     */
        if ($latestResult !== 'passed') {
            return false;
        }

        /*
     * If a requested stage was supplied,
     * make sure it is actually the next stage.
     */
        if ($stage_order !== null) {
            return (int) $stage_order === ($latestStage + 1);
        }

        return true;
    }
    /**
     * Get interview tracking data
     */
    /**
 * Get interview tracking data
 */
public function getTrackingData()
{
    $sql = "
        SELECT
            a.id AS application_id,
            a.job_id,
            j.title AS job_title,
            a.first_name,
            a.last_name,
            a.hired,
            a.ready_for_offer,

            MAX(
                CASE
                    WHEN i.stage_order = 1
                    THEN LOWER(TRIM(i.result))
                END
            ) AS initial_result,

            MAX(
                CASE
                    WHEN i.stage_order = 2
                    THEN LOWER(TRIM(i.result))
                END
            ) AS technical_result,

            MAX(
                CASE
                    WHEN i.stage_order = 3
                    THEN LOWER(TRIM(i.result))
                END
            ) AS final_result,

            MAX(o.id) AS offer_id

        FROM rao_applications a

        LEFT JOIN rao_jobs j
            ON a.job_id = j.id

        LEFT JOIN rao_interviews i
            ON a.id = i.application_id

        LEFT JOIN rao_offer o
            ON a.id = o.application_id

        GROUP BY
            a.id,
            a.job_id,
            j.title,
            a.first_name,
            a.last_name,
            a.hired,
            a.ready_for_offer

        ORDER BY a.id DESC
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    /**
     * Update interview result
     */
    public function updateResult($id, $result)
    {
        $stmt = $this->db->prepare("
            UPDATE rao_interviews
            SET result = :result
            WHERE id = :id
        ");

        return $stmt->execute([
            ':result' => $result,
            ':id' => $id
        ]);
    }

    /**
     * Get interview by ID
     */
    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM rao_interviews
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all schedules
     */
    /**
     * Get all latest interview schedules
     */
    public function getAllSchedules()
    {
        $query = "
        SELECT 
            i.id,
            i.application_id,

            CONCAT(a.first_name, ' ', a.last_name) AS full_name,
            position_record.position_name AS position,
            j.department,

            i.interview_date,
            i.interview_time,
            i.interviewer,
            i.result,
            i.stage_order,
            i.rating,
            i.feedback,

            /* Application status */
            a.ready_for_offer,
            a.hired,

            CASE
                /* Final interview passed and rated */
                WHEN i.result = 'passed'
                     AND i.stage_order = 3
                     AND i.rating IS NOT NULL
                     AND i.rating > 0
                    THEN 'Ready for Offer'

                /* Future interview */
                WHEN NOW() < CONCAT(
                    i.interview_date,
                    ' ',
                    i.interview_time
                )
                    THEN 'Scheduled'

                /* Interview currently happening */
                WHEN NOW() BETWEEN 
                    CONCAT(
                        i.interview_date,
                        ' ',
                        i.interview_time
                    )
                    AND DATE_ADD(
                        CONCAT(
                            i.interview_date,
                            ' ',
                            i.interview_time
                        ),
                        INTERVAL 1 HOUR
                    )
                    THEN 'Ongoing'

                /* Interview finished */
                ELSE 'Completed'
            END AS computed_status

        FROM rao_interviews i

        INNER JOIN rao_applications a
            ON i.application_id = a.id

        LEFT JOIN rao_jobs j
            ON a.job_id = j.id

        LEFT JOIN em_positions position_record
            ON j.position_id = position_record.position_id

        INNER JOIN (
            SELECT 
                application_id,
                MAX(stage_order) AS max_stage
            FROM rao_interviews
            GROUP BY application_id
        ) latest
            ON i.application_id = latest.application_id
            AND i.stage_order = latest.max_stage

        ORDER BY
            i.interview_date ASC,
            i.interview_time ASC
    ";

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
