<?php

class ExitManagementModel
{
    private PDO $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getExitRequests($status = null)
    {
        try {
            $sql = "SELECT er.id, 
                           CONCAT('RES-', LPAD(er.id, 6, '0')) AS request_number,
                           er.employee_id, 
                           er.created_at AS date_filed, 
                           er.last_working_date AS last_working_day,
                           er.reason, 
                           CASE er.status 
                             WHEN 'pending_review' THEN 'Pending Review'
                             WHEN 'pending_legal_review' THEN 'Pending Legal Review'
                             WHEN 'approved' THEN 'Approved'
                             WHEN 'rejected' THEN 'Rejected'
                             WHEN 'rejected_by_legal' THEN 'Rejected by Legal'
                             WHEN 'withdrawn' THEN 'Withdrawn'
                           END AS type_of_separation,
                           '' AS immediate_supervisor,
                           COALESCE(er.comments, er.review_remarks, er.hr_approval_comments, er.legal_approval_comments) AS separation_notes,
                           CASE 
                             WHEN er.archived_from_status IS NOT NULL THEN 'Archived'
                             WHEN er.hr_approved_at IS NOT NULL AND er.legal_approved_at IS NOT NULL THEN 'Completed'
                             ELSE 'Pending'
                           END AS overall_status,
                           CASE 
                             WHEN er.legal_approved_at IS NOT NULL THEN 'Confirmed'
                             WHEN er.status = 'rejected_by_legal' THEN 'Returned'
                             ELSE 'Pending'
                           END AS legal_status,
                           er.legal_approved_at AS confirmed_at,
                           er.legal_approved_by AS confirmed_by,
                           COALESCE(er.review_remarks, er.legal_approval_comments) AS legal_remarks,
                           er.approved_at, 
                           NULL AS recruitment_status, 
                           NULL AS recruitment_notified_at,
                           CASE WHEN er.archived_from_status IS NOT NULL THEN 1 ELSE 0 END AS archived,
                           NULL AS archived_at,
                           er.created_at, er.updated_at,
                           er.submitted_by AS created_by,
                           CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name,
                           e.employee_code AS employee_no,
                           d.department_name,
                           p.position_name
                    FROM exit_resignations er
                    LEFT JOIN em_employees e ON e.employee_id = er.employee_id
                    LEFT JOIN em_departments d ON d.department_id = e.department_id
                    LEFT JOIN em_positions p ON p.position_id = e.position_id";
            if ($status) {
                $sql .= " WHERE overall_status = :status";
            }
            $sql .= " ORDER BY er.created_at DESC";
            $stmt = $this->db->prepare($sql);
            if ($status) {
                $stmt->execute([':status' => $status]);
            } else {
                $stmt->execute();
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getExitRequestById($id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT er.id, 
                       CONCAT('RES-', LPAD(er.id, 6, '0')) AS request_number,
                       er.employee_id, 
                       er.created_at AS date_filed, 
                       er.last_working_date AS last_working_day,
                       er.reason, 
                       CASE er.status 
                         WHEN 'pending_review' THEN 'Pending Review'
                         WHEN 'pending_legal_review' THEN 'Pending Legal Review'
                         WHEN 'approved' THEN 'Approved'
                         WHEN 'rejected' THEN 'Rejected'
                         WHEN 'rejected_by_legal' THEN 'Rejected by Legal'
                         WHEN 'withdrawn' THEN 'Withdrawn'
                       END AS type_of_separation,
                       '' AS immediate_supervisor,
                       COALESCE(er.comments, er.review_remarks, er.hr_approval_comments, er.legal_approval_comments) AS separation_notes,
                       CASE 
                         WHEN er.archived_from_status IS NOT NULL THEN 'Archived'
                         WHEN er.hr_approved_at IS NOT NULL AND er.legal_approved_at IS NOT NULL THEN 'Completed'
                         ELSE 'Pending'
                       END AS overall_status,
                       CASE 
                         WHEN er.legal_approved_at IS NOT NULL THEN 'Confirmed'
                         WHEN er.status = 'rejected_by_legal' THEN 'Returned'
                         ELSE 'Pending'
                       END AS legal_status,
                       er.legal_approved_at AS confirmed_at,
                       er.legal_approved_by AS confirmed_by,
                       COALESCE(er.review_remarks, er.legal_approval_comments) AS legal_remarks,
                       er.approved_at, 
                       NULL AS recruitment_status, 
                       NULL AS recruitment_notified_at,
                       CASE WHEN er.archived_from_status IS NOT NULL THEN 1 ELSE 0 END AS archived,
                       NULL AS archived_at,
                       er.created_at, er.updated_at,
                       er.submitted_by AS created_by,
                       CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', e.last_name, '') AS employee_name,
                       e.employee_code AS employee_no,
                       d.department_name,
                       p.position_name
                FROM exit_resignations er
                LEFT JOIN em_employees e ON e.employee_id = er.employee_id
                LEFT JOIN em_departments d ON d.department_id = e.department_id
                LEFT JOIN em_positions p ON p.position_id = e.position_id
                WHERE er.id = ? LIMIT 1
            ");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                return $result;
            }

            $stmt = $this->db->prepare("
                SELECT 
                    t.id,
                    CONCAT('TER-', LPAD(t.id, 6, '0')) AS request_number,
                    t.employee_id,
                    t.created_at AS date_filed,
                    t.effective_date AS last_working_day,
                    t.termination_reason AS reason,
                    CASE t.status
                        WHEN 'approved' THEN 'Approved'
                        WHEN 'pending' THEN 'Pending'
                        WHEN 'rejected' THEN 'Rejected'
                        WHEN 'rejected_by_legal' THEN 'Rejected by Legal'
                        WHEN 'withdrawn' THEN 'Withdrawn'
                        ELSE COALESCE(t.status, 'Pending')
                    END AS type_of_separation,
                    '' AS immediate_supervisor,
                    t.comments AS separation_notes,
                    CASE 
                        WHEN t.approved_at IS NOT NULL THEN 'Completed'
                        ELSE 'Pending'
                    END AS overall_status,
                    CASE 
                        WHEN t.legal_approved_at IS NOT NULL THEN 'Confirmed'
                        ELSE 'Pending'
                    END AS legal_status,
                    t.legal_approved_at AS confirmed_at,
                    t.legal_approved_by AS confirmed_by,
                    COALESCE(t.review_remarks, t.legal_approval_comments) AS legal_remarks,
                    t.approved_at,
                    NULL AS recruitment_status,
                    NULL AS recruitment_notified_at,
                    0 AS archived,
                    NULL AS archived_at,
                    t.created_at, t.updated_at,
                    t.submitted_by AS created_by,
                    CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', e.last_name, '') AS employee_name,
                    e.employee_code AS employee_no,
                    d.department_name,
                    p.position_name
                FROM exit_terminations t
                LEFT JOIN em_employees e ON e.employee_id = t.employee_id
                LEFT JOIN em_departments d ON d.department_id = e.department_id
                LEFT JOIN em_positions p ON p.position_id = e.position_id
                WHERE t.id = ? LIMIT 1
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }

    public function getExitApprovals($exitRequestId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM lc_exit_approvals 
                WHERE exit_request_id = ? 
                ORDER BY 
                    CASE approver_role
                        WHEN 'Immediate Supervisor' THEN 1
                        WHEN 'Department Head' THEN 2
                        WHEN 'HR Officer' THEN 3
                        WHEN 'Legal Officer' THEN 4
                        WHEN 'Finance' THEN 5
                        WHEN 'IT' THEN 6
                        WHEN 'Property Custodian' THEN 7
                        WHEN 'HR Director' THEN 8
                    END
            ");
            $stmt->execute([$exitRequestId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getClearanceItems($exitRequestId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM lc_exit_clearance 
                WHERE exit_request_id = ? 
                ORDER BY category, item_name
            ");
            $stmt->execute([$exitRequestId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getExitInterview($exitRequestId)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM re_exit_interviews WHERE exit_request_id = ? LIMIT 1");
            $stmt->execute([$exitRequestId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }

    public function getVacantPositions()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT vp.*, 
                       CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name) AS full_name,
                       d.department_name,
                       p.position_name
                FROM lc_vacant_positions vp
                LEFT JOIN exit_resignations er ON er.id = vp.exit_request_id
                LEFT JOIN em_employees e ON e.employee_id = er.employee_id
                LEFT JOIN em_departments d ON d.department_id = e.department_id
                LEFT JOIN em_positions p ON p.position_id = e.position_id
                ORDER BY vp.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function updateExitLegalStatus($id, $status, $reviewedBy = null, $remarks = null)
    {
        try {
            $now = date('Y-m-d H:i:s');
            if ($status === 'Confirmed') {
                $stmt = $this->db->prepare("
                    UPDATE exit_resignations 
                    SET legal_approved_at = :now, 
                        legal_approved_by = :reviewed_by, 
                        legal_approval_comments = :remarks, 
                        updated_at = NOW() 
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':now' => $now,
                    ':reviewed_by' => $reviewedBy,
                    ':remarks' => $remarks,
                    ':id' => $id
                ]);
                if ($stmt->rowCount() > 0) {
                    return true;
                }
                $stmt = $this->db->prepare("
                    UPDATE exit_terminations 
                    SET legal_approved_at = :now, 
                        legal_approved_by = :reviewed_by, 
                        legal_approval_comments = :remarks, 
                        updated_at = NOW() 
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':now' => $now,
                    ':reviewed_by' => $reviewedBy,
                    ':remarks' => $remarks,
                    ':id' => $id
                ]);
                return $stmt->rowCount() > 0;
            } elseif ($status === 'Returned') {
                $stmt = $this->db->prepare("
                    UPDATE exit_resignations
                    SET reviewed_at = :now,
                        reviewed_by = :reviewed_by,
                        review_remarks = :remarks,
                        legal_approved_by = 0,
                        legal_approved_at = NULL,
                        legal_approval_comments = NULL,
                        updated_at = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':now' => $now,
                    ':reviewed_by' => $reviewedBy,
                    ':remarks' => $remarks,
                    ':id' => $id
                ]);
                if ($stmt->rowCount() > 0) {
                    return true;
                }
                $stmt = $this->db->prepare("
                    UPDATE exit_terminations
                    SET reviewed_at = :now,
                        reviewed_by = :reviewed_by,
                        review_remarks = :remarks,
                        legal_approved_by = 0,
                        legal_approved_at = NULL,
                        legal_approval_comments = NULL,
                        updated_at = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':now' => $now,
                    ':reviewed_by' => $reviewedBy,
                    ':remarks' => $remarks,
                    ':id' => $id
                ]);
                return $stmt->rowCount() > 0;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function updateExitRecruitmentStatus($id, $status)
    {
        return true;
    }

    public function rejectExit($id, $reviewedBy = null, $remarks = null)
    {
        try {
            $now = date('Y-m-d H:i:s');
            $stmt = $this->db->prepare("
                UPDATE exit_resignations
                SET status = 'rejected_by_legal',
                    reviewed_at = :now,
                    reviewed_by = :reviewed_by,
                    review_remarks = :remarks,
                    legal_approved_by = 0,
                    legal_approved_at = NULL,
                    legal_approval_comments = NULL,
                    updated_at = NOW()
                WHERE id = :id
            ");
            return $stmt->execute([
                ':now' => $now,
                ':reviewed_by' => $reviewedBy,
                ':remarks' => $remarks,
                ':id' => $id
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function getExitActivityLog($exitRequestId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM re_activity_log 
                WHERE table_name = 'exit_resignations' AND record_id = :id 
                ORDER BY created_at DESC
            ");
            $stmt->execute([':id' => $exitRequestId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}
