<?php
/**
 * Employee Model Class
 * Handles all employee-related database operations
 */

require_once __DIR__ . '/../config/Database.php';

class Employee {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get all employees with age_group calculated from birthdate
     */
    public function getAllEmployees() {
        $query = "SELECT 
                    *,
                    CASE 
                        WHEN birthdate IS NOT NULL THEN
                            CASE 
                                WHEN YEAR(CURDATE()) - YEAR(birthdate) - (DATE_FORMAT(birthdate, '%m%d') > DATE_FORMAT(CURDATE(), '%m%d')) < 25 THEN '18-24'
                                WHEN YEAR(CURDATE()) - YEAR(birthdate) - (DATE_FORMAT(birthdate, '%m%d') > DATE_FORMAT(CURDATE(), '%m%d')) < 35 THEN '25-34'
                                WHEN YEAR(CURDATE()) - YEAR(birthdate) - (DATE_FORMAT(birthdate, '%m%d') > DATE_FORMAT(CURDATE(), '%m%d')) < 45 THEN '35-44'
                                WHEN YEAR(CURDATE()) - YEAR(birthdate) - (DATE_FORMAT(birthdate, '%m%d') > DATE_FORMAT(CURDATE(), '%m%d')) < 55 THEN '45-54'
                                ELSE '55+'
                            END
                        ELSE 'Unknown'
                    END as age_group
                FROM employees 
                ORDER BY full_name ASC";
        return $this->db->fetchAll($query);
    }

    /**
     * Get employee by ID
     */
    public function getEmployeeById($id) {
        $query = "SELECT * FROM employees WHERE employee_id = ?";
        return $this->db->fetchOne($query, [$id], 'i');
    }

    /**
     * Get employees by department
     */
    public function getEmployeesByDepartment($department) {
        $query = "SELECT * FROM employees WHERE department = ? ORDER BY full_name ASC";
        return $this->db->fetchAll($query, [$department], 's');
    }

    /**
     * Get employees by employment status
     */
    public function getEmployeesByStatus($status) {
        $query = "SELECT * FROM employees WHERE employment_status = ? ORDER BY full_name ASC";
        return $this->db->fetchAll($query, [$status], 's');
    }

    /**
     * Get total employee count
     */
    public function getTotalEmployees() {
        $query = "SELECT COUNT(*) as count FROM employees WHERE employment_status != 'Resigned' AND employment_status != 'Terminated'";
        $result = $this->db->fetchOne($query);
        return $result['count'];
    }

    /**
     * Get total teachers count
     */
    public function getTotalTeachers() {
        $query = "SELECT COUNT(*) as count FROM employees WHERE position LIKE '%Teacher%' AND employment_status != 'Resigned' AND employment_status != 'Terminated'";
        $result = $this->db->fetchOne($query);
        return $result['count'];
    }

    /**
     * Get total staff count
     */
    public function getTotalStaff() {
        $query = "SELECT COUNT(*) as count FROM employees WHERE position NOT LIKE '%Teacher%' AND employment_status != 'Resigned' AND employment_status != 'Terminated'";
        $result = $this->db->fetchOne($query);
        return $result['count'];
    }

    /**
     * Get new hires this year
     */
    public function getNewHiresThisYear() {
        $currentYear = date('Y');
        $query = "SELECT COUNT(*) as count FROM employees WHERE YEAR(date_hired) = ?";
        $result = $this->db->fetchOne($query, [$currentYear], 'i');
        return $result['count'];
    }

    /**
     * Get employees by gender distribution
     */
    public function getGenderDistribution() {
        $query = "SELECT gender, COUNT(*) as count FROM employees WHERE employment_status != 'Resigned' AND employment_status != 'Terminated' GROUP BY gender";
        return $this->db->fetchAll($query);
    }

    /**
     * Get employees by age group
     */
    public function getAgeGroupDistribution() {
        $query = "SELECT 
                    CASE 
                        WHEN age < 25 THEN '18-24'
                        WHEN age BETWEEN 25 AND 34 THEN '25-34'
                        WHEN age BETWEEN 35 AND 44 THEN '35-44'
                        WHEN age BETWEEN 45 AND 54 THEN '45-54'
                        ELSE '55+'
                    END as age_group,
                    COUNT(*) as count
                FROM employees
                WHERE employment_status != 'Resigned' AND employment_status != 'Terminated'
                GROUP BY age_group
                ORDER BY age_group ASC";
        return $this->db->fetchAll($query);
    }

    /**
     * Get employees by department distribution
     */
    public function getDepartmentDistribution() {
        $query = "SELECT department, COUNT(*) as count FROM employees WHERE employment_status != 'Resigned' AND employment_status != 'Terminated' GROUP BY department ORDER BY count DESC";
        return $this->db->fetchAll($query);
    }

    /**
     * Get resigned employees
     */
    public function getResignedEmployees() {
        $query = "SELECT * FROM employees WHERE employment_status = 'Resigned' ORDER BY id DESC";
        return $this->db->fetchAll($query);
    }

    /**
     * Get terminated employees
     */
    public function getTerminatedEmployees() {
        $query = "SELECT * FROM employees WHERE employment_status = 'Terminated' ORDER BY id DESC";
        return $this->db->fetchAll($query);
    }

    /**
     * Get retired employees
     */
    public function getRetiredEmployees() {
        $query = "SELECT * FROM employees WHERE employment_status = 'Retired' ORDER BY id DESC";
        return $this->db->fetchAll($query);
    }

    /**
     * Get attrition by month and year
     */
    public function getAttritionByMonth($year) {
        $query = "SELECT 
                    DATE_FORMAT(separation_date, '%Y-%m') as month,
                    COUNT(*) as count
                FROM employees
                WHERE employment_status IN ('Resigned', 'Terminated', 'Retired')
                AND YEAR(separation_date) = ?
                GROUP BY month
                ORDER BY month ASC";
        return $this->db->fetchAll($query, [$year], 'i');
    }

    /**
     * Get attrition rate for year
     */
    public function getAttritionRate($year) {
        // Get total employees at start of year
        $query = "SELECT COUNT(*) as count FROM employees WHERE hire_date < DATE_FORMAT(?, '%Y-01-01')";
        $startCount = $this->db->fetchOne($query, [$year . '-01-01'], 's')['count'];

        // Get separated employees during year
        $query = "SELECT COUNT(*) as count FROM employees WHERE employment_status IN ('Resigned', 'Terminated', 'Retired') AND YEAR(separation_date) = ?";
        $separatedCount = $this->db->fetchOne($query, [$year], 'i')['count'];

        if ($startCount == 0) return 0;
        return round(($separatedCount / $startCount) * 100, 2);
    }

    /**
     * Get employees at risk of turnover
     * Criteria: low performance, high absence, long tenure without promotion
     */
    public function getAtRiskEmployees() {
        $query = "SELECT 
                    id,
                    name,
                    department,
                    position,
                    hire_date,
                    performance_score,
                    absence_days,
                    YEAR(CURDATE()) - YEAR(hire_date) as tenure_years,
                    CASE 
                        WHEN performance_score < 3 AND absence_days > 15 THEN 'High Risk'
                        WHEN performance_score < 3 OR (absence_days > 15 AND YEAR(CURDATE()) - YEAR(hire_date) > 3) THEN 'Medium Risk'
                        WHEN (YEAR(CURDATE()) - YEAR(hire_date)) > 5 AND performance_score < 3.5 THEN 'Medium Risk'
                        ELSE 'Low Risk'
                    END as risk_level
                FROM employees
                WHERE employment_status != 'Resigned' 
                AND employment_status != 'Terminated'
                AND (
                    performance_score < 3 
                    OR absence_days > 15 
                    OR (YEAR(CURDATE()) - YEAR(hire_date) > 5 AND performance_score < 3.5)
                )
                ORDER BY risk_level DESC, performance_score ASC";
        return $this->db->fetchAll($query);
    }

    /**
     * Get diversity metrics
     */
    public function getDiversityMetrics() {
        return [
            'gender' => $this->getGenderDistribution(),
            'age_group' => $this->getAgeGroupDistribution(),
            'department' => $this->getDepartmentDistribution()
        ];
    }

    /**
     * Create new employee
     */
    public function createEmployee($data) {
        $query = "INSERT INTO employees (name, gender, department, position, hire_date, employment_status, salary, performance_score, absence_days, age) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['name'],
            $data['gender'],
            $data['department'],
            $data['position'],
            $data['hire_date'],
            $data['employment_status'],
            $data['salary'],
            $data['performance_score'],
            $data['absence_days'],
            $data['age']
        ];
        
        return $this->db->insert($query, $params, 'ssssssddii');
    }

    /**
     * Update employee
     */
    public function updateEmployee($id, $data) {
        $query = "UPDATE employees SET 
                    name = ?, gender = ?, department = ?, position = ?, 
                    hire_date = ?, employment_status = ?, salary = ?, 
                    performance_score = ?, absence_days = ?, age = ?
                  WHERE id = ?";
        
        $params = [
            $data['name'],
            $data['gender'],
            $data['department'],
            $data['position'],
            $data['hire_date'],
            $data['employment_status'],
            $data['salary'],
            $data['performance_score'],
            $data['absence_days'],
            $data['age'],
            $id
        ];
        
        return $this->db->update($query, $params, 'ssssssddiii');
    }

    /**
     * Delete employee
     */
    public function deleteEmployee($id) {
        $query = "DELETE FROM employees WHERE id = ?";
        return $this->db->delete($query, [$id], 'i');
    }
}

?>
