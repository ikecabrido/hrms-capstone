-- Training Requests: learners request training for skill gaps with no available courses
CREATE TABLE IF NOT EXISTS ld_training_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    learner_id INT NOT NULL,
    skill_id INT NOT NULL,
    skill_name VARCHAR(255) NOT NULL,
    message TEXT,
    status ENUM('pending','accepted','rejected','completed') DEFAULT 'pending',
    instructor_id INT DEFAULT NULL,
    course_id INT DEFAULT NULL,
    instructor_note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (learner_id) REFERENCES em_employees(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES ld_skill(id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES em_employees(employee_id) ON DELETE SET NULL,
    FOREIGN KEY (course_id) REFERENCES ld_course(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_learner (learner_id),
    INDEX idx_skill (skill_id)
);
