<?php
namespace App\Models;

class EmployeeBadge extends BaseModel
{
    public function all()
    {
        $this->execute(
            "UPDATE eer_employee_badges
             SET reason = COALESCE(reason, 'Assigned by HR/Admin.'),
                 performance_linked = CASE
                     WHEN COALESCE(performance_score, 0) > 0 THEN 1
                     ELSE COALESCE(performance_linked, 0)
                 END
             WHERE reason IS NULL OR performance_linked IS NULL"
        );
        $employeeName = $this->getEmployeeNameSql('e', 'employee_name');
        $hasCycleAwards = $this->tableHasColumns(
            'eer_recognition_cycle_awards',
            ['employee_badge_id', 'month_year', 'award_level']
        );
        $cycleColumns = $hasCycleAwards
            ? ', ca.month_year AS recognition_month, ca.award_level'
            : ', NULL AS recognition_month, NULL AS award_level';
        $cycleJoin = $hasCycleAwards
            ? ' LEFT JOIN eer_recognition_cycle_awards ca ON ca.employee_badge_id = eb.eer_employee_badge_id'
            : '';
        $sql = 'SELECT eb.eer_employee_badge_id, eb.employee_id, eb.badge_id, eb.awarded_at, eb.awarded_by,
                       ' . $employeeName . ', b.name AS badge_name, b.icon AS badge_icon,
                       b.points_value AS badge_points' . $cycleColumns . '
                FROM eer_employee_badges eb 
                LEFT JOIN em_employees e ON eb.employee_id = e.employee_id 
                LEFT JOIN eer_badges b ON eb.badge_id = b.eer_badge_id' . $cycleJoin;
        return $this->execute($sql)->fetchAll();
    }

    public function find($id)
    {
        return $this->execute(
            'SELECT eer_employee_badge_id, employee_id, badge_id, awarded_at, awarded_by
             FROM eer_employee_badges WHERE eer_employee_badge_id = :id',
            ['id' => $id]
        )->fetch();
    }

    public function create($data)
    {
        $sql = 'INSERT INTO eer_employee_badges (employee_id, badge_id, awarded_at) 
                VALUES (:employee_id, :badge_id, NOW())';
        $this->execute($sql, $data);
        return $this->db->lastInsertId();
    }
}
