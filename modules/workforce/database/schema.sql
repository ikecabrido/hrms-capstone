-- ============================================
-- Workforce Analytics Database Schema
-- School Management System
-- ============================================

-- Create database
CREATE DATABASE IF NOT EXISTS work_analytics;
USE work_analytics;

-- ============================================
-- EMPLOYEES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS employees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    age INT,
    department VARCHAR(100) NOT NULL,
    position VARCHAR(100) NOT NULL,
    hire_date DATE NOT NULL,
    employment_status ENUM('Full-time', 'Part-time', 'Contract', 'Temporary', 'Resigned', 'Terminated', 'Retired') DEFAULT 'Full-time',
    salary DECIMAL(10, 2) NOT NULL,
    performance_score DECIMAL(3, 2) CHECK (performance_score >= 1 AND performance_score <= 5),
    absence_days INT DEFAULT 0,
    separation_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_department (department),
    INDEX idx_employment_status (employment_status),
    INDEX idx_hire_date (hire_date),
    INDEX idx_performance (performance_score)
);

-- ============================================
-- SAMPLE DATA INSERT
-- ============================================

-- Insert sample employees
INSERT INTO employees (name, gender, age, department, position, hire_date, employment_status, salary, performance_score, absence_days) VALUES
-- Administration Department
('John Smith', 'Male', 45, 'Administration', 'Principal', '2015-08-15', 'Full-time', 85000.00, 4.8, 2),
('Sarah Johnson', 'Female', 42, 'Administration', 'Vice Principal', '2016-03-22', 'Full-time', 75000.00, 4.6, 3),
('Michael Chen', 'Male', 38, 'Administration', 'Administrative Officer', '2017-01-10', 'Full-time', 55000.00, 4.2, 5),

-- Academics Department - Teachers
('Emma Wilson', 'Female', 35, 'Academics', 'Senior Teacher', '2014-09-01', 'Full-time', 65000.00, 4.7, 1),
('David Brown', 'Male', 40, 'Academics', 'Senior Teacher', '2013-07-15', 'Full-time', 65000.00, 4.5, 2),
('Lisa Anderson', 'Female', 32, 'Academics', 'Teacher', '2018-08-20', 'Full-time', 52000.00, 4.3, 4),
('James Taylor', 'Male', 29, 'Academics', 'Teacher', '2019-09-01', 'Full-time', 48000.00, 4.1, 6),
('Rachel Martinez', 'Female', 31, 'Academics', 'Teacher', '2018-01-15', 'Full-time', 50000.00, 4.4, 3),
('Thomas Garcia', 'Male', 36, 'Academics', 'Department Head', '2016-02-01', 'Full-time', 70000.00, 4.6, 2),
('Patricia Lee', 'Female', 28, 'Academics', 'Teacher', '2020-08-15', 'Full-time', 46000.00, 3.9, 8),
('Christopher Jones', 'Male', 44, 'Academics', 'Senior Teacher', '2012-06-01', 'Full-time', 67000.00, 4.5, 1),

-- Finance Department
('Jennifer White', 'Female', 48, 'Finance', 'Finance Manager', '2010-05-01', 'Full-time', 72000.00, 4.4, 2),
('Robert Davis', 'Male', 41, 'Finance', 'Accountant', '2015-03-15', 'Full-time', 58000.00, 4.2, 4),
('Karen Miller', 'Female', 34, 'Finance', 'Accountant', '2017-09-01', 'Full-time', 56000.00, 4.1, 3),

-- HR Department
('Amanda Thompson', 'Female', 37, 'HR', 'HR Manager', '2013-04-10', 'Full-time', 65000.00, 4.3, 3),
('Steven Moore', 'Male', 39, 'HR', 'HR Officer', '2016-07-01', 'Full-time', 52000.00, 4.0, 6),
('Deborah Jackson', 'Female', 43, 'HR', 'Training Coordinator', '2014-01-20', 'Full-time', 54000.00, 4.2, 2),

-- IT Department
('Kevin Harris', 'Male', 31, 'IT', 'IT Manager', '2015-02-01', 'Full-time', 68000.00, 4.4, 4),
('Michelle Clark', 'Female', 26, 'IT', 'IT Support', '2019-08-15', 'Full-time', 45000.00, 3.8, 10),
('Daniel Rodriguez', 'Male', 33, 'IT', 'System Administrator', '2017-05-01', 'Full-time', 60000.00, 4.2, 5),
('Jessica Lewis', 'Female', 27, 'IT', 'IT Support', '2020-06-01', 'Full-time', 43000.00, 3.7, 12),

-- Support Services
('Mark Williams', 'Male', 50, 'Support Services', 'Facilities Manager', '2008-03-15', 'Full-time', 55000.00, 3.9, 8),
('Maria Hernandez', 'Female', 38, 'Support Services', 'Cleaner/Janitor', '2018-01-01', 'Part-time', 28000.00, 3.5, 10),
('Jorge Lopez', 'Male', 35, 'Support Services', 'Security Officer', '2017-04-01', 'Full-time', 42000.00, 4.0, 6),
('Angela Scott', 'Female', 44, 'Support Services', 'Cook', '2013-07-01', 'Full-time', 38000.00, 4.2, 3),

-- Resigned employees
('Robert Martinez', 'Male', 42, 'Academics', 'Teacher', '2016-08-01', 'Resigned', 54000.00, 2.8, 18),
('Susan Adams', 'Female', 38, 'Finance', 'Analyst', '2015-01-15', 'Resigned', 53000.00, 3.0, 20),

-- Terminated employees
('George Wilson', 'Male', 40, 'IT', 'IT Technician', '2017-03-01', 'Terminated', 48000.00, 2.5, 25),

-- Retired employee
('Helen Young', 'Female', 65, 'Administration', 'Administrative Officer', '1995-06-01', 'Retired', 48000.00, 4.1, 2),

-- Employees at risk (low performance, high absence)
('Brandon Hall', 'Male', 29, 'Academics', 'Teacher', '2018-08-01', 'Full-time', 49000.00, 2.2, 18),
('Nicole Green', 'Female', 31, 'IT', 'IT Support', '2019-01-01', 'Full-time', 44000.00, 2.4, 20),
('David Bennett', 'Male', 35, 'Support Services', 'Security Officer', '2016-05-01', 'Full-time', 41000.00, 2.6, 16),
('Linda Price', 'Female', 40, 'Finance', 'Accountant', '2014-03-01', 'Full-time', 55000.00, 2.9, 14),
('Jason Murphy', 'Male', 26, 'Academics', 'Teacher', '2020-09-01', 'Full-time', 45000.00, 2.3, 19);

-- ============================================
-- SET SEPARATION DATES FOR SEPARATED EMPLOYEES
-- ============================================
UPDATE employees SET separation_date = '2025-11-15' WHERE name = 'Robert Martinez' AND employment_status = 'Resigned';
UPDATE employees SET separation_date = '2024-08-20' WHERE name = 'Susan Adams' AND employment_status = 'Resigned';
UPDATE employees SET separation_date = '2025-06-10' WHERE name = 'George Wilson' AND employment_status = 'Terminated';
UPDATE employees SET separation_date = '2025-01-31' WHERE name = 'Helen Young' AND employment_status = 'Retired';

-- ============================================
-- VERIFY INSTALLATION
-- ============================================
SELECT COUNT(*) as total_employees FROM employees;
SELECT department, COUNT(*) as count FROM employees WHERE employment_status NOT IN ('Resigned', 'Terminated') GROUP BY department;
SELECT employment_status, COUNT(*) as count FROM employees GROUP BY employment_status;
