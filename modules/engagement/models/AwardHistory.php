<?php
namespace App\Models;

class AwardHistory extends BaseModel
{
    public function all()
    {
        $employeeName = $this->getEmployeeNameSql('e', 'employee_name');
        $sql = 'SELECT ah.*, ' . $employeeName . ' FROM eer_award_history ah 
                LEFT JOIN em_employees e ON ah.employee_id = e.employee_id 
                ORDER BY ah.created_at DESC';
        return $this->execute($sql)->fetchAll();
    }

    public function find($id)
    {
        return $this->execute('SELECT * FROM eer_award_history WHERE eer_award_history_id = :id', ['id' => $id])->fetch();
    }

    public function findByEmployeeAndMonthYear($employeeId, $monthYear)
    {
        $sql = 'SELECT * FROM eer_award_history WHERE employee_id = :employee_id AND month_year = :month_year AND award_type = :award_type';
        return $this->execute($sql, [
            'employee_id' => $employeeId,
            'month_year' => $monthYear,
            'award_type' => 'employee_of_month'
        ])->fetch();
    }

    public function create($data)
    {
        $existing = $this->execute(
            'SELECT eer_award_history_id FROM eer_award_history
             WHERE employee_id = :employee_id
               AND award_type = :award_type
               AND month_year = :month_year
             ORDER BY eer_award_history_id DESC
             LIMIT 1',
            [
                'employee_id' => $data['employee_id'],
                'award_type' => $data['award_type'] ?? 'employee_of_month',
                'month_year' => $data['month_year'] ?? date('Y-m')
            ]
        )->fetchColumn();

        if ($existing) {
            $this->execute(
                'UPDATE eer_award_history ah
                 SET ah.performance_score = COALESCE(
                         ah.performance_score,
                         (SELECT pr.overall_rating * 20 FROM pm_performance_reports pr
                          WHERE pr.employee_id = ah.employee_id
                          ORDER BY pr.period_end DESC, pr.report_id DESC LIMIT 1),
                         (SELECT pa.overall_rating * 20 FROM pm_appraisals pa
                          WHERE pa.employee_id = ah.employee_id
                          ORDER BY COALESCE(pa.due_date, pa.created_at) DESC, pa.appraisal_id DESC LIMIT 1),
                         0
                     ),
                     ah.points = GREATEST(COALESCE(ah.points, 0), COALESCE(ah.vote_count, 0) * 5),
                     ah.award_icon = COALESCE(ah.award_icon, :existing_award_icon)
                 WHERE ah.eer_award_history_id = :id',
                ['id' => $existing, 'existing_award_icon' => 'fas fa-trophy']
            );
            return $existing;
        }

        $sql = 'INSERT INTO eer_award_history (employee_id, award_name, reason, nominated_by, award_type, points, performance_score, month_year, status, award_icon, created_at)
                VALUES (:employee_id, :award_name, :reason, :nominated_by, :award_type, 0,
                    COALESCE(
                        (SELECT pr.overall_rating * 20 FROM pm_performance_reports pr
                         WHERE pr.employee_id = :performance_employee_id
                         ORDER BY pr.period_end DESC, pr.report_id DESC LIMIT 1),
                        (SELECT pa.overall_rating * 20 FROM pm_appraisals pa
                         WHERE pa.employee_id = :appraisal_employee_id
                         ORDER BY COALESCE(pa.due_date, pa.created_at) DESC, pa.appraisal_id DESC LIMIT 1),
                        0
                    ),
                    :month_year, :status, :award_icon, NOW())';
        $params = [
            'employee_id' => $data['employee_id'],
            'award_name' => $data['award_name'] ?? 'Employee of the Month Nomination',
            'reason' => $data['reason'] ?? null,
            'nominated_by' => $data['nominated_by'] ?? null,
            'award_type' => $data['award_type'] ?? 'employee_of_month',
            'performance_employee_id' => $data['employee_id'],
            'appraisal_employee_id' => $data['employee_id'],
            'month_year' => $data['month_year'] ?? date('Y-m'),
            'status' => $data['status'] ?? 'nominated',
            'award_icon' => $data['award_icon'] ?? 'fas fa-trophy'
        ];
        $this->execute($sql, $params);
        return $this->db->lastInsertId();
    }

    public function incrementVoteCount($awardHistoryId)
    {
        $sql = 'UPDATE eer_award_history
                SET vote_count = vote_count + 1,
                    points = GREATEST(COALESCE(points, 0), (vote_count + 1) * 5)
                WHERE eer_award_history_id = :id';
        return $this->execute($sql, ['id' => $awardHistoryId])->rowCount();
    }

}

