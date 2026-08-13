<?php

/**
 * Attendance Metrics Calculator
 * Handles calculations for punctuality score, overtime frequency, and attendance rates
 */

class MetricsCalculator
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    /**
     * Calculate punctuality score for employee in a month
     * Score out of 100: 100 = perfect, decreases based on late incidents and minutes
     * 
     * @param string $employeeId
     * @param string $monthYear Format: 'YYYY-MM'
     * @return array Punctuality data
     */
    public function calculatePunctualityScore($employeeId, $monthYear)
    {
        try {
            // Parse month-year
            $month = substr($monthYear, 5, 2);
            $year = substr($monthYear, 0, 4);

            // Get all late incidents for the month
            $query = "SELECT 
                        COUNT(*) as total_late_incidents,
                        COALESCE(SUM(late_minutes), 0) as total_late_minutes,
                        COALESCE(AVG(late_minutes), 0) as average_late_minutes,
                        COALESCE(MAX(late_minutes), 0) as max_late_minutes,
                        COUNT(CASE WHEN late_minutes > 30 THEN 1 END) as severe_late_count
                     FROM ta_attendance
                     WHERE employee_id = ?
                     AND MONTH(attendance_date) = ?
                     AND YEAR(attendance_date) = ?
                     AND status = 'LATE'";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$employeeId, $month, $year]);
            $lateData = $stmt->fetch(PDO::FETCH_ASSOC);

            // Calculate score components
            $lateIncidents = (int)$lateData['total_late_incidents'];
            $totalLateMinutes = (int)$lateData['total_late_minutes'];
            $avgLateMinutes = (float)$lateData['average_late_minutes'];
            $severeCount = (int)$lateData['severe_late_count'];

            // Scoring algorithm:
            // Base: 100 points
            // Per late incident: -5 points
            // Per severe late (>30 min): -10 points
            // Per 5 minutes average: -1 point
            $score = 100;
            $score -= ($lateIncidents * 5); // -5 per incident
            $score -= ($severeCount * 10); // Additional penalty for severe lateness
            $score -= floor($avgLateMinutes / 5); // -1 point per 5 min average

            // Ensure score doesn't go below 0
            $score = max(0, $score);

            // Determine grade
            $grade = $this->getGradeFromScore($score);

            // Prepare breakdown
            $breakdown = [
                'late_incidents' => $lateIncidents,
                'total_late_minutes' => $totalLateMinutes,
                'average_late_minutes' => round($avgLateMinutes, 2),
                'max_late_minutes' => (int)$lateData['max_late_minutes'],
                'severe_late_count' => $severeCount,
                'score_deductions' => [
                    'late_incidents' => $lateIncidents * 5,
                    'severe_late_penalty' => $severeCount * 10,
                    'average_penalty' => floor($avgLateMinutes / 5)
                ]
            ];

            // Update or insert score
            $this->savePunctualityScore($employeeId, $monthYear, $score, $grade, $breakdown);

            return [
                'success' => true,
                'employee_id' => $employeeId,
                'month_year' => $monthYear,
                'punctuality_score' => $score,
                'punctuality_grade' => $grade,
                'breakdown' => $breakdown,
                'interpretation' => $this->interpretScore($score, $grade)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calculate overtime frequency for employee in a month
     * 
     * @param string $employeeId
     * @param string $monthYear Format: 'YYYY-MM'
     * @return array Overtime frequency data
     */
    public function calculateOvertimeFrequency($employeeId, $monthYear)
    {
        try {
            // Parse month-year
            $month = substr($monthYear, 5, 2);
            $year = substr($monthYear, 0, 4);

            // Get overtime data
            $query = "SELECT 
                        COUNT(DISTINCT DATE(overtime_date)) as overtime_instances,
                        COALESCE(SUM(overtime_hours), 0) as total_overtime_hours,
                        COALESCE(AVG(overtime_hours), 0) as average_overtime_per_instance,
                        COALESCE(MAX(overtime_hours), 0) as max_overtime_in_single_day,
                        COALESCE(SUM(CASE WHEN approved = 1 THEN overtime_hours ELSE 0 END), 0) as approved_hours,
                        COALESCE(SUM(CASE WHEN approved = 0 THEN overtime_hours ELSE 0 END), 0) as unapproved_hours
                     FROM ta_overtime_tracking
                     WHERE employee_id = ?
                     AND MONTH(overtime_date) = ?
                     AND YEAR(overtime_date) = ?";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$employeeId, $month, $year]);
            $overtimeData = $stmt->fetch(PDO::FETCH_ASSOC);

            $instances = (int)$overtimeData['overtime_instances'];
            $totalHours = (float)$overtimeData['total_overtime_hours'];

            // Determine frequency rating based on instances
            // LOW: 0-2 instances
            // MODERATE: 3-5 instances
            // HIGH: 6-9 instances
            // CRITICAL: 10+ instances
            if ($instances <= 2) {
                $rating = 'LOW';
            } elseif ($instances <= 5) {
                $rating = 'MODERATE';
            } elseif ($instances <= 9) {
                $rating = 'HIGH';
            } else {
                $rating = 'CRITICAL';
            }

            // Save frequency record
            $this->saveOvertimeFrequency($employeeId, $monthYear, $instances, $totalHours, $rating, $overtimeData);

            return [
                'success' => true,
                'employee_id' => $employeeId,
                'month_year' => $monthYear,
                'overtime_instances' => $instances,
                'total_overtime_hours' => round($totalHours, 2),
                'average_overtime_per_instance' => round($overtimeData['average_overtime_per_instance'], 2),
                'max_overtime_in_single_day' => round($overtimeData['max_overtime_in_single_day'], 2),
                'overtime_frequency_rating' => $rating,
                'approved_overtime_hours' => round($overtimeData['approved_hours'], 2),
                'unapproved_overtime_hours' => round($overtimeData['unapproved_hours'], 2),
                'rating_interpretation' => $this->interpretFrequencyRating($rating, $instances)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calculate comprehensive attendance metrics
     * 
     * @param string $employeeId
     * @param string $monthYear Format: 'YYYY-MM'
     * @return array All metrics
     */
    public function calculateAttendanceMetrics($employeeId, $monthYear)
    {
        try {
            // Parse month-year
            $month = substr($monthYear, 5, 2);
            $year = substr($monthYear, 0, 4);

            // Get attendance summary
            $query = "SELECT 
                        COUNT(CASE WHEN status IN ('PRESENT', 'ON_TIME', 'EARLY') THEN 1 END) as present_days,
                        COUNT(CASE WHEN status = 'ABSENT' THEN 1 END) as absent_days,
                        COUNT(CASE WHEN status = 'LATE' THEN 1 END) as late_incidents,
                        COALESCE(SUM(overtime_hours), 0) as total_overtime_hours,
                        COUNT(*) as total_days
                     FROM ta_attendance
                     WHERE employee_id = ?
                     AND MONTH(attendance_date) = ?
                     AND YEAR(attendance_date) = ?
                     AND status != 'HOLIDAY'";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$employeeId, $month, $year]);
            $attendanceData = $stmt->fetch(PDO::FETCH_ASSOC);

            $presentDays = (int)$attendanceData['present_days'];
            $absentDays = (int)$attendanceData['absent_days'];
            $lateIncidents = (int)$attendanceData['late_incidents'];
            $totalDays = $presentDays + $absentDays + $lateIncidents;

            // Calculate rates
            $attendanceRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;
            $absenceRate = $totalDays > 0 ? round(($absentDays / $totalDays) * 100, 2) : 0;

            // Get punctuality score
            $punctuality = $this->calculatePunctualityScore($employeeId, $monthYear);
            $punctualityScore = $punctuality['success'] ? $punctuality['punctuality_score'] : 0;

            // Get overtime frequency
            $overtime = $this->calculateOvertimeFrequency($employeeId, $monthYear);
            $frequencyRating = $overtime['success'] ? $overtime['overtime_frequency_rating'] : 'LOW';

            // Calculate overall performance score (weighted average)
            $overallScore = ($attendanceRate * 0.40) + ($punctualityScore * 0.35) + ((100 - $absenceRate) * 0.25);
            $overallScore = round($overallScore, 2);

            // Prepare summary
            $statusSummary = [
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'late_incidents' => $lateIncidents,
                'total_overtime_hours' => round($attendanceData['total_overtime_hours'], 2)
            ];

            // Save metrics
            $this->saveAttendanceMetrics($employeeId, $monthYear, $attendanceRate, $absenceRate, $punctualityScore, $frequencyRating, $overallScore, $statusSummary);

            return [
                'success' => true,
                'employee_id' => $employeeId,
                'month_year' => $monthYear,
                'attendance_rate' => $attendanceRate,
                'absence_rate' => $absenceRate,
                'punctuality_score' => $punctualityScore,
                'overtime_frequency_rating' => $frequencyRating,
                'overall_performance_score' => $overallScore,
                'status_summary' => $statusSummary,
                'performance_interpretation' => $this->interpretOverallPerformance($overallScore)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Record late minutes for attendance record
     */
    public function recordLateMinutes($attendanceId, $lateMinutes)
    {
        try {
            $query = "UPDATE ta_attendance 
                     SET late_minutes = ?
                     WHERE attendance_id = ?";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$lateMinutes, $attendanceId]);

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Record overtime event
     */
    public function recordOvertimeEvent($employeeId, $attendanceId, $overtimeHours, $categoryKey = 'OTHER', $notes = '')
    {
        try {
            $overtimeMinutes = (int)(($overtimeHours - floor($overtimeHours)) * 60);
            
            $query = "INSERT INTO ta_overtime_tracking 
                     (employee_id, attendance_id, overtime_date, overtime_hours, overtime_minutes, reason_category, reason_notes)
                     VALUES (?, ?, CURDATE(), ?, ?, ?, ?)";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$employeeId, $attendanceId, $overtimeHours, $overtimeMinutes, $categoryKey, $notes]);

            return ['success' => true, 'tracking_id' => $this->db->lastInsertId()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get punctuality score for display
     */
    public function getPunctualityScore($employeeId, $monthYear)
    {
        try {
            $query = "SELECT * FROM ta_punctuality_scores 
                     WHERE employee_id = ? AND month_year = ?";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$employeeId, $monthYear]);
            $score = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($score) {
                $score['breakdown'] = json_decode($score['score_breakdown'], true);
            }

            return $score;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get overtime frequency for display
     */
    public function getOvertimeFrequency($employeeId, $monthYear)
    {
        try {
            $query = "SELECT * FROM ta_overtime_frequency 
                     WHERE employee_id = ? AND month_year = ?";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$employeeId, $monthYear]);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get attendance metrics for display
     */
    public function getAttendanceMetrics($employeeId, $monthYear)
    {
        try {
            $query = "SELECT * FROM ta_attendance_metrics 
                     WHERE employee_id = ? AND month_year = ?";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$employeeId, $monthYear]);
            $metrics = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($metrics) {
                $metrics['status_summary'] = json_decode($metrics['status_summary'], true);
            }

            return $metrics;
        } catch (\Exception $e) {
            return null;
        }
    }

    // ===== PRIVATE HELPER METHODS =====

    private function savePunctualityScore($employeeId, $monthYear, $score, $grade, $breakdown)
    {
        $query = "INSERT INTO ta_punctuality_scores 
                 (employee_id, month_year, total_late_incidents, total_late_minutes, average_late_minutes, 
                  punctuality_score, punctuality_grade, score_breakdown)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                 total_late_incidents = VALUES(total_late_incidents),
                 total_late_minutes = VALUES(total_late_minutes),
                 average_late_minutes = VALUES(average_late_minutes),
                 punctuality_score = VALUES(punctuality_score),
                 punctuality_grade = VALUES(punctuality_grade),
                 score_breakdown = VALUES(score_breakdown),
                 updated_at = NOW()";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $employeeId,
            $monthYear,
            $breakdown['late_incidents'],
            $breakdown['total_late_minutes'],
            $breakdown['average_late_minutes'],
            $score,
            $grade,
            json_encode($breakdown)
        ]);
    }

    private function saveOvertimeFrequency($employeeId, $monthYear, $instances, $totalHours, $rating, $data)
    {
        $query = "INSERT INTO ta_overtime_frequency 
                 (employee_id, month_year, overtime_instances, total_overtime_hours, average_overtime_per_instance,
                  max_overtime_in_single_day, overtime_frequency_rating, approved_overtime_hours, unapproved_overtime_hours)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                 overtime_instances = VALUES(overtime_instances),
                 total_overtime_hours = VALUES(total_overtime_hours),
                 average_overtime_per_instance = VALUES(average_overtime_per_instance),
                 max_overtime_in_single_day = VALUES(max_overtime_in_single_day),
                 overtime_frequency_rating = VALUES(overtime_frequency_rating),
                 approved_overtime_hours = VALUES(approved_overtime_hours),
                 unapproved_overtime_hours = VALUES(unapproved_overtime_hours),
                 updated_at = NOW()";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $employeeId,
            $monthYear,
            $instances,
            $totalHours,
            $data['average_overtime_per_instance'],
            $data['max_overtime_in_single_day'],
            $rating,
            $data['approved_hours'],
            $data['unapproved_hours']
        ]);
    }

    private function saveAttendanceMetrics($employeeId, $monthYear, $attendanceRate, $absenceRate, $punctuality, $frequencyRating, $overallScore, $statusSummary)
    {
        $query = "INSERT INTO ta_attendance_metrics 
                 (employee_id, month_year, attendance_rate, absence_rate, punctuality_score, overtime_frequency_rating,
                  overall_performance_score, total_present_days, total_absent_days, total_late_incidents, total_overtime_hours, status_summary)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                 attendance_rate = VALUES(attendance_rate),
                 absence_rate = VALUES(absence_rate),
                 punctuality_score = VALUES(punctuality_score),
                 overtime_frequency_rating = VALUES(overtime_frequency_rating),
                 overall_performance_score = VALUES(overall_performance_score),
                 total_present_days = VALUES(total_present_days),
                 total_absent_days = VALUES(total_absent_days),
                 total_late_incidents = VALUES(total_late_incidents),
                 total_overtime_hours = VALUES(total_overtime_hours),
                 status_summary = VALUES(status_summary),
                 updated_at = NOW()";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $employeeId,
            $monthYear,
            $attendanceRate,
            $absenceRate,
            $punctuality,
            $frequencyRating,
            $overallScore,
            $statusSummary['present_days'],
            $statusSummary['absent_days'],
            $statusSummary['late_incidents'],
            $statusSummary['total_overtime_hours'],
            json_encode($statusSummary)
        ]);
    }

    private function getGradeFromScore($score)
    {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }

    private function interpretScore($score, $grade)
    {
        $interpretations = [
            'A' => 'Excellent punctuality',
            'B' => 'Good punctuality',
            'C' => 'Acceptable punctuality',
            'D' => 'Poor punctuality',
            'F' => 'Serious punctuality issues'
        ];

        return [
            'grade' => $grade,
            'description' => $interpretations[$grade],
            'score' => $score
        ];
    }

    private function interpretFrequencyRating($rating, $instances)
    {
        $interpretations = [
            'LOW' => 'Minimal overtime work',
            'MODERATE' => 'Occasional overtime',
            'HIGH' => 'Frequent overtime',
            'CRITICAL' => 'Excessive overtime - review needed'
        ];

        return [
            'rating' => $rating,
            'description' => $interpretations[$rating],
            'instances' => $instances
        ];
    }

    private function interpretOverallPerformance($score)
    {
        if ($score >= 90) return 'Excellent performance';
        if ($score >= 80) return 'Good performance';
        if ($score >= 70) return 'Satisfactory performance';
        if ($score >= 60) return 'Needs improvement';
        return 'Critical performance issues';
    }
}
