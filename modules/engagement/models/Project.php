<?php
namespace App\Models;

class Project extends BaseModel
{
    private function ensureTableExists()
    {
        $sql = "CREATE TABLE IF NOT EXISTS eer_projects (
            eer_project_id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) DEFAULT NULL,
            description text DEFAULT NULL,
            deadline date DEFAULT NULL,
            status varchar(50) DEFAULT 'planning',
            created_by_employee_id int(11) DEFAULT NULL,
            created_at datetime DEFAULT current_timestamp(),
            updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (eer_project_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        $this->execute($sql);
    }

    public function createProject($name, $description, $deadline, $status, $createdBy)
    {
        $this->ensureTableExists();

        $sql = 'INSERT INTO eer_projects (name, description, deadline, status, created_by_employee_id, created_at)
            VALUES (:name, :description, :deadline, :status, :created_by_employee_id, NOW())';

        $this->execute($sql, [
            'name' => $name,
            'description' => $description,
            'deadline' => $deadline,
            'status' => $status,
            'created_by_employee_id' => $createdBy
        ]);

        return $this->db->lastInsertId();
    }

    public function getAllProjects()
    {
        $this->ensureTableExists();
        $nameSql = $this->getEmployeeNameSql('e', 'creator_name');
        return $this->execute("SELECT p.*, $nameSql FROM eer_projects p
                      LEFT JOIN em_employees e ON p.created_by_employee_id = e.employee_id
                      ORDER BY p.created_at DESC")->fetchAll();
    }

    public function getProjectById($id)
    {
        return $this->execute('SELECT * FROM eer_projects WHERE eer_project_id = :id', ['id' => $id])->fetch();
    }

    public function updateProjectStatus($id, $status)
    {
        $sql = 'UPDATE eer_projects SET status = :status, updated_at = NOW() WHERE eer_project_id = :id';
        return $this->execute($sql, ['id' => $id, 'status' => $status]);
    }

    public function deleteProject($id)
    {
        $sql = 'DELETE FROM eer_projects WHERE eer_project_id = :id';
        return $this->execute($sql, ['id' => $id]);
    }
}
