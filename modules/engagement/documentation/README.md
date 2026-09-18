# Engagement System Documentation

## 1. System Overview

This HRMS project is a modular Human Resource Management System built in PHP. It is organized by department and feature module, and each module has its own pages, controllers, APIs, and front-end assets.

The main system starts from the login page at [../../index.php](../../index.php), and after successful authentication it redirects the user to the correct module based on the assigned role.

The engagement module is one of the major feature areas of the system. It focuses on employee experience, communication, recognition, feedback, grievance handling, and social collaboration.

The Engagement System is a critical component of the Human Resource Management System, designed to enhance employee involvement, communication, and organizational culture. It provides a digital platform for employees to access announcements, participate in surveys, provide feedback, recognize peers, and report grievances. The system is built on a modular architecture consisting of user interfaces, business logic controllers, data models, and API-based communication layers. The workflow begins with user authentication, where the system verifies credentials and establishes a session before granting access to the relevant HR module. Within the engagement module, data is retrieved and rendered through a page-routing mechanism and dynamic API requests, enabling efficient interaction with module features such as communication, recognition, surveys, and grievance management. By centralizing these processes, the system promotes employee engagement, improves organizational transparency, and supports a more responsive and collaborative workplace environment.

## Abstract

This project focuses on the design and development of the Engagement System as part of a Human Resource Management System. The system is intended to improve employee communication, collaboration, recognition, feedback, and grievance handling in a centralized and efficient digital platform. It enables employees to access company announcements, participate in surveys, provide feedback, recognize the achievements of colleagues, and communicate concerns through a structured process. The system uses a modular architecture that includes web interfaces, controllers, models, database connectivity, and API-based data handling to ensure reliable and organized operations. By integrating these features into a single platform, the system promotes employee engagement, strengthens workplace communication, and supports more transparent and responsive human resource management. The project demonstrates how modern web technologies can be applied to improve employee relations and organizational effectiveness.

## Objectives of the Study

The study aims to develop a functional and efficient Engagement System that supports employee communication and workplace engagement within the HRMS. Specifically, the project seeks to: (1) create a centralized platform for announcements, notifications, and employee communication; (2) support employee participation through surveys and feedback forms; (3) provide a mechanism for recognition and appreciation through rewards and badges; (4) establish a structured grievance process for reporting and monitoring employee concerns; and (5) improve the overall employee experience by promoting transparency, responsiveness, and collaboration. The project also aims to design a system that is easy to manage, accessible to employees, and capable of storing and retrieving engagement-related data efficiently.

## Scope and Delimitation

The system covers the key features of employee engagement within the HRMS, including communication, surveys, feedback, recognition, grievances, and social collaboration. It includes modules for announcements, employee notifications, recognition tracking, feedback collection, grievance submission and monitoring, and dashboard analytics for engagement-related data. The system is designed for employee users and HR administrators who need to manage and monitor engagement activities. However, the project is limited to the engagement module and does not cover the full scope of the entire HRMS beyond its core functionality. It also focuses on the internal operational structure of the system rather than external integrations or advanced enterprise-level customization.

## Project Significance

The development of the Engagement System is significant because it helps organizations strengthen employee relationships, improve communication, and increase employee satisfaction. In many workplaces, employee concerns, appreciation, and feedback are often fragmented across different channels, making it difficult for HR and management to respond effectively. This system addresses that concern by providing a centralized platform where employee engagement activities are organized and monitored. The project is valuable for both employees and administrators because it improves information sharing, promotes recognition, supports timely grievance resolution, and creates a more transparent and collaborative workplace culture. By enhancing employee engagement, the organization can improve morale, retention, productivity, and overall work performance.

---

## 2. System Architecture

### 2.1 Core entry points

- [../../index.php](../../index.php)  
  Main login page for the whole HRMS.

- [../../auth/login.php](../../auth/login.php)  
  Handles user authentication, password verification, failed-attempt lockout, and role-based redirect.

- [../../auth/session.php](../../session.php)  
  Validates the session, enforces timeout, and loads active employee session data.

- [../../auth/guard.php](../../guard.php)  
  Protects pages from unauthorized access by redirecting unauthenticated users to login.

- [../index.php](../index.php)  
  Main entry page for the engagement module.

- [../page-loader.php](../page-loader.php)  
  Loads a page dynamically inside the engagement shell without reloading the full layout.

### 2.2 Shared database layer

- [../../database/db.php](../../database/db.php)  
  Creates the PHP Data Object (PDO) connection to the MySQL database.

Meaning of the file:
- It centralizes database connection logic.
- It loads environment variables or local defaults.
- It sets the default fetch mode to associative arrays.
- It allows all modules to use one consistent data-access layer.

### 2.3 Shared page/router logic

- [../classes/Page.php](../classes/Page.php)  
  This is the page-routing class for engagement.

Functions and purpose:
- `__construct()` initializes page discovery.
- `discoverPages()` scans the pages folder and registers valid pages.
- `getPage()` decides which page to render based on URL, cookies, or default page.
- `render()` includes the actual page file.
- `renderNav()` generates the sidebar menu.
- `isActive()` highlights the current active navigation item.

This file acts like the module router.

---

## 3. Module Breakdown of the Whole HRMS

The system is divided into modules. Each module has a clear purpose.

| Module | Purpose | Typical Function |
|---|---|---|
| Recruitment | Hiring and applicant management | Job applications, hiring process, candidate tracking |
| Employee | Employee records and profiles | User and staff information management |
| Payroll | Salaries and compensation | Payroll generation, deductions, payslips |
| Time | Attendance and scheduling | Attendance recording, shifts, leave, overtime |
| Performance | Performance management | Appraisals, KPIs, review forms |
| Learning | Training and development | Courses, certifications, employee learning |
| Compliance | Policy and legal compliance | Regulation tracking, compliance tasks |
| Workforce | Workforce planning and staffing | Department and team organization |
| Exit | Offboarding and separation | Exit processes, clearance, final procedures |
| Clinic | Medical support and health management | Employee clinic records, benefits support |
| Portal | Employee access center | Self-service employee features |
| Engagement | Employee experience and relations | Communication, recognition, grievances, social collaboration |

### Meaning of each module

- Recruitment handles candidate intake and the hiring pipeline.
- Employee manages the master employee data and staff profiles.
- Payroll manages compensation and pay details.
- Time manages attendance, schedule blocks, and leave data.
- Performance tracks employee review and evaluation results.
- Learning supports training paths and employee growth.
- Compliance ensures policies and legal requirements are followed.
- Workforce manages team structure and staffing needs.
- Exit supports resignation and offboarding.
- Clinic manages health-related assistance and employee well-being support.
- Portal provides centralized employee access to workplace services.
- Engagement creates a healthy employee relationship and communication ecosystem.

---

## 4. Engagement Module Breakdown

The engagement module is the employee experience layer. It combines multiple features into one space.

### 4.1 Main engagement pages

These are the visible pages inside the module:

- [../pages/dashboard-overview.php](../pages/dashboard-overview.php)  
  Overview page with KPI cards and charts for announcements, surveys, feedback, recognitions, grievances, and notifications.

- [../pages/communication.php](../pages/communication.php)  
  Handles announcements, notifications, department updates, and messaging.

- [../pages/survey.php](../pages/survey.php)  
  Displays surveys and their results.

- [../pages/social.php](../pages/social.php)  
  Supports social posts, collaboration, and employee communications.

- [../pages/recognition.php](../pages/recognition.php)  
  Displays recognition, awards, and employee appreciation.

- [../pages/grievances.php](../pages/grievances.php)  
  Handles employee concerns, investigation tracking, and grievance management.

### 4.2 API and backend sections

The module also contains a REST-like backend API layer:

- [../api/index.php](../api/index.php)  
  Main API entry point that receives a `resource` parameter and routes to the right API file.

- [../api/ApiRouter.php](../api/ApiRouter.php)  
  Validates the requested API resource and loads the correct file.

- [../api/ApiResponse.php](../api/ApiResponse.php)  
  Standard JSON response builder for success and error messages.

### 4.3 Example API resources

The engagement API includes modules such as:

- `communication.php` — announcements and messaging
- `survey.php` — survey data and responses
- `social.php` — social post handling
- `recognition.php` — awarded recognition records
- `grievance.php` — employee grievance records
- `reward.php` — reward data
- `badge.php` — badges and achievement data
- `group.php` and `group_member.php` — team/group setup and membership
- `feedback.php` — employee suggestions and feedback
- `user.php` — user-related data
- `message.php` and `reply.php` — messaging and comments
- `project.php` — project-related engagement participation

### 4.4 Main controller files

The engagement module includes controllers such as:

- `CommunicationController.php` — handles announcements and messaging logic
- `SurveyController.php` — handles survey creation and result processing
- `RecognitionController.php` — manages recognition entries
- `GrievanceController.php` — handles grievances and updates
- `SocialController.php` — posts, comments, likes, and feed data
- `FeedbackController.php` — stores and loads feedback data
- `EmployeeController.php` — employee-related lookups
- `GroupController.php` — group management logic
- `AwardHistoryController.php` — employee award tracking
- `RewardController.php` and `RewardRedemptionController.php` — reward system logic

These controllers are the “brains” that determine what data should be retrieved, validated, stored, and returned.

---

## 5. How the System Process Works

### 5.1 Login and session creation

1. User opens [../../index.php](../../index.php).
2. The browser sends employee ID and password through JavaScript in [../../login.js](../../login.js).
3. [../../auth/login.php](../../auth/login.php) receives the POST request.
4. It checks the submitted values against the user account and employee tables.
5. If details are valid, it creates PHP session data such as:
   - `employee_id`
   - `employee_code`
   - `role_id`
   - `department_id`
   - `employee_name`
6. It updates the login time and resets failed attempts.
7. It redirects the role to a module such as engagement, payroll, or employee.

### 5.2 Page protection

After login, any protected page is checked by [../../auth/guard.php](../../guard.php) or [../../auth/session.php](../../session.php).

The system does the following:
- verifies session existence
- checks timeout
- refreshes session activity
- redirects to login if invalid or expired

This protects pages from unauthorized access.

### 5.3 Page rendering flow

1. A user requests a page inside engagement, such as `?page=communication`.
2. [../classes/Page.php](../classes/Page.php) reads the URL parameter.
3. It validates the page name against the pages directory.
4. It loads the page file and includes the correct content within the module layout.
5. The page can call controller methods or fetch API content.

### 5.4 API request flow

1. The page or frontend script calls an API endpoint like `api/index.php?resource=survey`.
2. [../api/index.php](../api/index.php) reads the `resource` parameter.
3. It passes it to `ApiRouter`.
4. `ApiRouter` validates the resource name and loads the corresponding API file.
5. The API file runs logic, queries the database, and returns JSON data.
6. JavaScript updates the interface without reloading the full page.

### 5.5 Database interaction model

The system follows a typical layered flow:

- Frontend page or JavaScript request
- Controller receives the request
- Controller calls model or query logic
- Database executes statement
- Result is converted to array or JSON
- View/page displays the results

This keeps the logic organized and easier to maintain.

---

## 6. Engagement Business Logic by Feature

### 6.1 Communication
Purpose:
- publish announcements
- manage HR notifications
- deliver company and department updates
- support internal messaging

Key actions:
- create announcement
- mark notification as read
- send updates to target employees
- display recent announcements and alerts

### 6.2 Survey
Purpose:
- collect employee feedback
- measure engagement, satisfaction, and sentiment
- record responses and report results

Key actions:
- generate survey forms
- gather responses
- calculate total submissions
- show survey analytics

### 6.3 Social and collaboration
Purpose:
- create social interaction among employees
- allow posts and interaction around workplace topics

Key actions:
- create post
- comment or react
- view feed history
- track employee engagement activity

### 6.4 Recognition and rewards
Purpose:
- acknowledge employee contributions
- display awards and badges
- motivate performance and appreciation

Key actions:
- create recognition entry
- assign badges or rewards
- show award history
- track recognition statistics

### 6.5 Grievances
Purpose:
- gather employee concerns and complaints
- route issues for review and resolution
- monitor status and action

Key actions:
- create grievance record
- update status
- assign action or resolution
- track grievance history

### 6.6 Feedback
Purpose:
- collect employee ideas, suggestions, and comments
- understand pain points and improvement opportunities

Key actions:
- submit feedback
- group feedback by category
- review and summarize comments

### 6.7 Groups and members
Purpose:
- create employee groups or teams
- manage membership and collaboration structures

Key actions:
- create group
- assign members
- count groups
- support team-based engagement activities

---

## 7. Why the Engagement System Exists

The engagement system is designed to improve employee experience by giving employees a structured way to:

- receive important communication
- participate in feedback loops
- join surveys
- receive recognition and rewards
- raise concerns through grievance handling
- interact with teammates socially and professionally
- feel connected to the overall company culture

In short, its purpose is to build employee engagement, trust, and collaboration.

---

## 8. Documentation Summary

The engagement system combines the following layers:

- UI pages for presentation
- controllers for business logic
- model classes for data structure
- API routing for interaction
- session and access control for security
- database queries for persistence

This makes it a modular, maintainable, and scalable HR platform.

---

## 9. Recommended Next Documentation Files

For future technical documentation, it is helpful to create:

1. `survey-module.md` — details of the survey system
2. `grievance-module.md` — grievance process and workflow
3. `communication-module.md` — notification and announcement process
4. `recognition-module.md` — reward and awards logic
5. `database-schema-reference.md` — list of main tables and relationships

---

## 10. Final Definition

The engagement system is the employee-centered layer of the HRMS. It connects communication, recognition, trust, feedback, and grievance resolution into a single experience that helps manage employee satisfaction and organizational engagement.
