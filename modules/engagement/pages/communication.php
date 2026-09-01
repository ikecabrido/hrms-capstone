<?php

require_once __DIR__ . '/../../../auth/session.php';
require_once __DIR__ . '/../autoload.php';

use App\Controllers\CommunicationController;
use App\Controllers\MessageController;
use App\Controllers\EmployeeController;



// Get current user's info
$currentEmployeeId = $_SESSION['user']['employee_id'] ?? $_SESSION['employee_id'] ?? null;
$currentUserId = $_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? $_SESSION['user']['employee_id'] ?? $_SESSION['user_id'] ?? $_SESSION['user']['user_id'] ?? null;
if (!$currentUserId) {
  $currentUserId = $_SESSION['employee_id'] ?? null;
}

$communicationController = new CommunicationController();
$currentRole = strtolower(trim((string)($_SESSION['user']['role_name'] ?? $_SESSION['user']['role'] ?? $_SESSION['role_name'] ?? $_SESSION['role'] ?? '')));
$currentRoleId = (int)($_SESSION['user']['role_id'] ?? $_SESSION['role_id'] ?? 0);
$isHrAdmin = in_array($currentRoleId, [1, 12], true)
  || preg_match('/(^|[^a-z])(admin|hr|human resources|human resource|employee relations|engagement)([^a-z]|$)/', $currentRole) === 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $formType = $_POST['form_type'] ?? '';
  $isAjax = !empty($_POST['ajax']);
  $responseMessage = '';
  try {
    if ($formType === 'announcement') {
      $title = trim((string)($_POST['title'] ?? ''));
      $content = trim((string)($_POST['content'] ?? ''));
      if ($title === '' || $content === '') {
        throw new \InvalidArgumentException('Announcement title and content are required.');
      }
      if (empty($currentEmployeeId)) {
        throw new \RuntimeException('Current user is not linked to an employee record.');
      }
      $communicationController->postAnnouncement(
        $title,
        $content,
        (int)$currentEmployeeId,
        $_POST['category'] ?? 'general',
        $_POST['priority'] ?? 'normal',
        $_POST['target_audience'] ?? 'all'
      );
      $responseMessage = 'Announcement posted successfully.';
      $_SESSION['flash_success'] = $responseMessage;
    } elseif ($formType === 'department_update') {
      $title = trim((string)($_POST['title'] ?? ''));
      $content = trim((string)($_POST['content'] ?? ''));
      $department = trim((string)($_POST['department'] ?? ''));
      if ($title === '' || $content === '' || $department === '') {
        throw new \InvalidArgumentException('Update title, department, and content are required.');
      }
      if (empty($currentEmployeeId)) {
        throw new \RuntimeException('Current user is not linked to an employee record.');
      }
      $communicationController->postDepartmentUpdate(
        $title,
        $content,
        $department,
        $_POST['priority'] ?? 'normal',
        (int)$currentEmployeeId
      );
      $responseMessage = 'Department update posted successfully.';
      $_SESSION['flash_success'] = $responseMessage;
    } elseif ($formType === 'message') {
      $receiverId = (int)($_POST['receiver_id'] ?? 0);
      $message = trim((string)($_POST['message'] ?? ''));
      if (empty($currentEmployeeId) || $receiverId <= 0 || $message === '') {
        throw new \InvalidArgumentException('Recipient and message are required.');
      }
      $communicationController->sendMessage((int)$currentEmployeeId, $receiverId, $message);
      $responseMessage = 'Message sent successfully.';
      $_SESSION['flash_success'] = $responseMessage;
    } elseif ($formType === 'share_from_lcm') {
      if (!$isHrAdmin) {
        throw new \RuntimeException('Only authorized HR/Admin users can share policies.');
      }
      $targetType = $_POST['target_type'] ?? 'all';
      if ($targetType === 'department') {
        $targetAudience = 'department_id:' . (int)($_POST['department_id'] ?? 0);
      } elseif ($targetType === 'employees') {
        $employeeIds = array_filter(array_map('intval', (array)($_POST['employee_ids'] ?? [])));
        $targetAudience = 'employees:' . implode(',', array_unique($employeeIds));
      } else {
        $targetAudience = 'all';
      }
      $communicationController->shareLcmPolicy(
        'LCM',
        (string)($_POST['source_policy_id'] ?? ''),
        $targetAudience,
        $currentUserId,
        trim((string)($_POST['announcement'] ?? ''))
      );
      $_SESSION['flash_success'] = 'Policy shared successfully and affected employees were notified.';
    } elseif (($_POST['form_type'] ?? '') === 'mark_read') {
      $communicationController->markNotificationAsRead($_POST['notification_id'] ?? 0);
      $_SESSION['flash_success'] = 'Notification marked as read.';
    }
  } catch (Throwable $e) {
    if ($isAjax) {
      header('Content-Type: application/json; charset=utf-8');
      http_response_code(422);
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
      exit;
    }
    $_SESSION['flash_error'] = $e->getMessage();
  }

  if ($isAjax) {
    unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'message' => $responseMessage]);
    exit;
  }

  $redirectTab = $formType === 'announcement' ? 'announcements' : ($formType === 'department_update' ? 'updates' : ($formType === 'message' ? 'messaging' : 'policies'));
  header('Location: index.php?page=communication#' . $redirectTab);
  exit;
}

$payload = $communicationController->getPageData($currentEmployeeId, $isHrAdmin);
$payload['lcm_departments'] = $communicationController->getLcmDepartments();

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Helper functions
function getPriorityBadgeClass($priority) {
    switch ($priority) {
        case 'urgent': return 'danger';
        case 'high': return 'warning';
        case 'normal': return 'info';
        case 'low': return 'secondary';
        default: return 'light';
    }
}

function getNotificationTypeIcon($type) {
    switch ($type) {
        case 'info': return 'fas fa-info-circle text-info';
        case 'warning': return 'fas fa-exclamation-triangle text-warning';
        case 'success': return 'fas fa-check-circle text-success';
        case 'danger': return 'fas fa-times-circle text-danger';
        default: return 'fas fa-bell text-primary';
    }
}

  function getNotificationTypeLabel($type) {
    $labels = [
      'survey' => 'Survey',
      'social' => 'Social',
      'recognition' => 'Recognition',
      'grievance' => 'Grievance',
      'policy' => 'Policy',
    ];
    return $labels[strtolower((string)$type)] ?? 'HR Update';
  }
?>

<link rel="stylesheet" href="pages/css/style/communication.css"> 

<div class="communication-area">
   <div class="module-header">
        <h1>Communication Portal</h1>
    </div>

        <div class="container-fluid">
          <!-- Flash Messages -->
          <?php if ($flashSuccess): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <i class="fas fa-check-circle"></i> <?= htmlspecialchars($flashSuccess) ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
          <?php endif; ?>
          <?php if ($flashError): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($flashError) ?>
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
          <?php endif; ?>

          <!-- Communication Portal Tabs -->
          <div class="card shadow-sm border-0 communication-tabs-card">
            <div class="card-header p-0 border-0">
              <ul class="nav nav-tabs" id="communication-tabs" role="tablist">
                <li class="nav-item">
                  <a class="nav-link" id="announcements-tab" data-toggle="pill" href="#announcements" role="tab">
                    <i class="fas fa-bullhorn"></i> Announcements
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="notifications-tab" data-toggle="pill" href="#notifications" role="tab">
                    <i class="fas fa-bell"></i> HR Notifications
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="updates-tab" data-toggle="pill" href="#updates" role="tab">
                    <i class="fas fa-building"></i> Department Updates
                  </a>
                </li>
                <?php if ($isHrAdmin || !empty($payload['lcm_policies'])): ?>
                <li class="nav-item">
                  <a class="nav-link" id="policies-tab" data-toggle="pill" href="#policies" role="tab">
                    <i class="fas fa-file-contract"></i> Policy Sharing
                  </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                  <a class="nav-link" id="messaging-tab" data-toggle="pill" href="#messaging" role="tab">
                    <i class="fas fa-comments"></i> HR-Employee Messaging
                  </a>
                </li>
              </ul>
            </div>

              <div class="tab-content" id="communication-tabs-content">

                <!-- Company Announcements Tab -->
                <div class="tab-pane fade" id="announcements" role="tabpanel">
                  <div class="row">
                    <div class="col-md-4">
                      <div class="card card-warning">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-plus-circle"></i> Post Announcement</h3>
                        </div>
                        <div class="card-body">
                          <form method="POST" action="">
                            <input type="hidden" name="form_type" value="announcement">
                            <div class="form-group">
                              <label>Title <span class="text-danger">*</span></label>
                              <input type="text" class="form-control" name="title" required placeholder="Announcement title">
                            </div>
                            <div class="form-group">
                              <label>Category</label>
                              <select class="form-control" name="category">
                                <option value="general">General</option>
                                <option value="company">Company News</option>
                                <option value="events">Events</option>
                                <option value="achievements">Achievements</option>
                                <option value="changes">Organizational Changes</option>
                              </select>
                            </div>
                            <div class="form-group">
                              <label>Priority</label>
                              <select class="form-control" name="priority">
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                              </select>
                            </div>
                            <div class="form-group">
                              <label>Target Audience</label>
                              <select class="form-control" name="target_audience">
                                <option value="all">All Employees</option>
                                <option value="management">Management Only</option>
                                <option value="staff">Staff Only</option>
                              </select>
                            </div>
                            <div class="form-group">
                              <label>Content <span class="text-danger">*</span></label>
                              <textarea class="form-control" name="content" rows="4" required placeholder="Announcement details..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-info btn-block">
                              <i class="fas fa-paper-plane"></i> Post Announcement
                            </button>
                          </form>
                        </div>
                      </div>
                    </div>

                    <div class="col-md-8">
                      <div class="card">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-list"></i> Recent Announcements</h3>
                          <div class="card-tools">

                          </div>
                        </div>
                        <div class="card-body">
                          <div id="announcements-container" class="communication-post-scroll">
                            <?php if (!empty($payload['announcements'])): ?>
                              <?php foreach (array_slice($payload['announcements'], 0, 5) as $announcement): ?>
                                <div class="announcement-card card mb-3">
                                  <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                      <h6 class="card-title text-primary mb-1">
                                        <?= htmlspecialchars($announcement['title']) ?>
                                      </h6>
                                      <span class="badge badge-<?= getPriorityBadgeClass($announcement['priority'] ?? 'normal') ?>">
                                        <?= ucfirst($announcement['priority'] ?? 'normal') ?>
                                      </span>
                                    </div>
                                    <p class="card-text text-muted small mb-2">
                                      <i class="fas fa-calendar"></i> <?= date('M d, Y H:i', strtotime($announcement['created_at'])) ?> |
                                      <i class="fas fa-tag"></i> <?= ucfirst($announcement['category'] ?? 'general') ?> |
                                      <i class="fas fa-user"></i> <?= htmlspecialchars($announcement['author_name'] ?? 'Admin') ?>
                                    </p>
                                    <?php $announcementContent = (string)($announcement['content'] ?? ''); ?>
                                    <p class="card-text"><?= nl2br(htmlspecialchars(strlen($announcementContent) > 200 ? substr($announcementContent, 0, 200) . '...' : $announcementContent)) ?></p>
                                    <?php if (strlen($announcementContent) > 200): ?>
                                      <button class="btn btn-sm btn-outline-primary" onclick="viewFullAnnouncement(<?= $announcement['eer_announcements_id'] ?>)">
                                        <i class="fas fa-eye"></i> Read More
                                      </button>
                                    <?php endif; ?>
                                  </div>
                                </div>
                              <?php endforeach; ?>
                            <?php else: ?>
                              <div class="text-center text-muted py-4">
                                <i class="fas fa-bullhorn fa-3x mb-3"></i>
                                <h5>No announcements yet</h5>
                                <p>Company announcements will appear here.</p>
                              </div>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- HR Notifications Tab -->
                <div class="tab-pane fade" id="notifications" role="tabpanel">
                  <div class="row">
                    <div class="col-md-12">
                      <div class="card">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-bell"></i> HR Notifications</h3>
                        </div>
                        <div class="card-body">
                          <div id="notifications-container" class="communication-post-scroll">
                            <?php if (!empty($payload['notifications']) || !empty($payload['lcm_notifications'])): ?>
                              <?php foreach ($payload['notifications'] as $notification): ?>
                                <?php $notificationType = strtolower((string)($notification['type'] ?? 'info')); ?>
                                <div class="notification-item <?= !empty($notification['is_read']) ? 'notification-read' : 'notification-unread' ?>">
                                  <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                      <div class="d-flex align-items-center mb-1">
                                        <span class="badge badge-light notification-type-badge notification-type-badge--side"><?= htmlspecialchars(getNotificationTypeLabel($notificationType)) ?></span>
                                        <i class="<?= getNotificationTypeIcon($notificationType) ?> mr-2"></i>
                                        <h6 class="mb-0"><?= htmlspecialchars(getNotificationTypeLabel($notificationType)) ?> Notification</h6>
                                      </div>
                                      <p class="text-muted small mb-1">
                                        <i class="fas fa-calendar"></i> <?= date('M d, Y H:i', strtotime($notification['created_at'] ?? date('Y-m-d H:i:s'))) ?> |
                                        <i class="fas fa-bolt"></i> Automatic from <?= htmlspecialchars(getNotificationTypeLabel($notificationType)) ?>
                                      </p>
                                      <p class="mb-2"><?= htmlspecialchars($notification['message'] ?? 'No details') ?></p>
                                    </div>
                                    <div class="ml-3 notification-actions">
                                      <?php if (!$notification['is_read']): ?>
                                        <form method="POST" class="d-inline">
                                          <input type="hidden" name="form_type" value="mark_read">
                                          <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                          <button type="submit" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-check"></i> Mark Read
                                          </button>
                                        </form>
                                        <span class="badge badge-primary notification-new-badge">New</span>
                                      <?php endif; ?>
                                    </div>
                                  </div>
                                </div>
                              <?php endforeach; ?>
                              <?php foreach ($payload['lcm_notifications'] ?? [] as $notification): ?>
                                <div class="notification-item <?= $notification['is_read'] ? 'notification-read' : 'notification-unread' ?>">
                                  <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                      <div class="d-flex align-items-center mb-1">
                                        <span class="badge badge-warning mr-2">Legal & Compliance</span>
                                        <i class="<?= getNotificationTypeIcon($notification['notification_type'] ?? $notification['type'] ?? 'info') ?> mr-2"></i>
                                        <h6 class="mb-0"><?= htmlspecialchars($notification['title'] ?? $notification['message'] ?? 'Compliance Notification') ?></h6>
                                        <?php if (empty($notification['is_read']) || $notification['is_read'] == 0): ?>
                                          <span class="badge badge-primary ml-2">New</span>
                                        <?php endif; ?>
                                      </div>
                                      <p class="text-muted small mb-1">
                                        <i class="fas fa-calendar"></i> <?= date('M d, Y H:i', strtotime($notification['created_at'] ?? date('Y-m-d H:i:s'))) ?> |
                                        <i class="fas fa-balance-scale"></i> Legal & Compliance Management
                                      </p>
                                      <p class="mb-2"><?= htmlspecialchars($notification['message'] ?? '') ?></p>
                                    </div>
                                  </div>
                                </div>
                              <?php endforeach; ?>
                            <?php else: ?>
                              <div class="text-center text-muted py-4">
                                <i class="fas fa-bell fa-3x mb-3"></i>
                                <h5>No notifications</h5>
                                <p>HR notifications will appear here.</p>
                              </div>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Department Updates Tab -->
                <div class="tab-pane fade" id="updates" role="tabpanel">
                  <div class="row">
                    <div class="col-md-4">
                      <div class="card card-warning">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-plus-circle"></i> Post Update</h3>
                        </div>
                        <div class="card-body">
                          <form method="POST" action="">
                            <input type="hidden" name="form_type" value="department_update">
                            <div class="form-group">
                              <label>Title <span class="text-danger">*</span></label>
                              <input type="text" class="form-control" name="title" required placeholder="Update title">
                            </div>
                            <div class="form-group">
                              <label>Department <span class="text-danger">*</span></label>
                              <select class="form-control" name="department" required>
                                <option value="">Select Department</option>
                                <?php foreach ($payload['departments'] ?? [] as $department): ?>
                                  <?php $departmentName = trim((string)($department['department_name'] ?? '')); ?>
                                  <?php if ($departmentName !== ''): ?>
                                    <option value="<?= htmlspecialchars($departmentName) ?>"><?= htmlspecialchars($departmentName) ?></option>
                                  <?php endif; ?>
                                <?php endforeach; ?>
                                <option value="all">All Departments</option>
                              </select>
                            </div>
                            <div class="form-group">
                              <label>Priority</label>
                              <select class="form-control" name="priority">
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                              </select>
                            </div>
                            <div class="form-group">
                              <label>Content <span class="text-danger">*</span></label>
                              <textarea class="form-control" name="content" rows="4" required placeholder="Department update details..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-success btn-block">
                              <i class="fas fa-paper-plane"></i> Post Update
                            </button>
                          </form>
                        </div>
                      </div>
                    </div>

                    <div class="col-md-8">
                      <div class="card">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-building"></i> Updates</h3>
                          <div class="card-tools">
                            <select class="form-control form-control-sm" id="dept-filter">
                              <option value="">All Departments</option>
                              <?php foreach ($payload['departments'] ?? [] as $department): ?>
                                <?php $departmentName = trim((string)($department['department_name'] ?? '')); ?>
                                <?php if ($departmentName !== ''): ?>
                                  <option value="<?= htmlspecialchars($departmentName) ?>"><?= htmlspecialchars($departmentName) ?></option>
                                <?php endif; ?>
                              <?php endforeach; ?>
                            </select>
                          </div>
                        </div>
                        <div class="card-body">
                          <div id="updates-container" class="communication-post-scroll">
                            <?php if (!empty($payload['department_updates'])): ?>
                              <?php foreach (array_slice($payload['department_updates'], 0, 10) as $update): ?>
                                <div class="dept-update-item card mb-3" data-dept="<?= htmlspecialchars($update['department'] ?? '') ?>">
                                  <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                      <h6 class="card-title text-success mb-1">
                                        <?= htmlspecialchars($update['title']) ?>
                                      </h6>
                                      <span class="badge badge-<?= getPriorityBadgeClass($update['priority'] ?? 'normal') ?>">
                                        <?= ucfirst($update['priority'] ?? 'normal') ?>
                                      </span>
                                    </div>
                                    <p class="card-text text-muted small mb-2">
                                      <i class="fas fa-calendar"></i> <?= date('M d, Y H:i', strtotime($update['created_at'])) ?> |
                                      <i class="fas fa-building"></i> <?= ucfirst(str_replace('_', ' ', $update['department'] ?? 'General')) ?> |
                                      <i class="fas fa-user"></i> <?= htmlspecialchars($update['author_name'] ?? 'Admin') ?>
                                    </p>
                                    <?php $updateContent = (string)($update['content'] ?? ''); ?>
                                    <p class="card-text"><?= nl2br(htmlspecialchars(strlen($updateContent) > 200 ? substr($updateContent, 0, 200) . '...' : $updateContent)) ?></p>
                                    <?php if (strlen($updateContent) > 200): ?>
                                      <button class="btn btn-sm btn-outline-success" onclick="viewFullAnnouncement(<?= $update['eer_announcements_id'] ?>)">
                                        <i class="fas fa-eye"></i> Read More
                                      </button>
                                    <?php endif; ?>
                                  </div>
                                </div>
                              <?php endforeach; ?>
                            <?php else: ?>
                              <div class="text-center text-muted py-4">
                                <i class="fas fa-building fa-3x mb-3"></i>
                                <h5>Department Updates</h5>
                                <p>Updates from different departments will appear here.</p>
                              </div>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <?php if ($isHrAdmin || !empty($payload['lcm_policies'])): ?>
                <!-- Policy Sharing Tab -->
                <div class="tab-pane fade" id="policies" role="tabpanel">
                  <div class="row">
                    <div class="col-md-4">
                      <div class="card card-warning">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-plus-circle"></i> Share Policy</h3>
                        </div>
                        <div class="card-body">
                          <?php if (!empty($payload['lcm_policies'])): ?>
                            <form method="POST" action="" enctype="multipart/form-data" autocomplete="off">
                              <input type="hidden" name="form_type" value="share_from_lcm">
                              <div class="form-group">
                                <label>Select Policy</label>
                                <select class="form-control" name="source_policy_id" required id="lcm-policy-select" autocomplete="off">
                                  <option value="">-- Select policy --</option>
                                  <?php foreach ($payload['lcm_policies'] as $lp): ?>
                                    <option value="<?= htmlspecialchars($lp['source_policy_key'] ?? $lp['source_policy_id']) ?>" data-title="<?= htmlspecialchars($lp['title']) ?>" data-content="<?= htmlspecialchars($lp['content'] ?? '') ?>" data-category="<?= htmlspecialchars($lp['category'] ?? '') ?>" data-effective="<?= htmlspecialchars($lp['effective_date'] ?? '') ?>" data-status="<?= htmlspecialchars($lp['status'] ?? '') ?>" data-updated="<?= htmlspecialchars($lp['updated_at'] ?? '') ?>" data-audience="<?= htmlspecialchars($lp['target_audience'] ?? 'all') ?>" data-attachment="<?= htmlspecialchars($lp['attachment_path'] ?? '') ?>" data-is-update="<?= !empty($lp['is_update']) ? '1' : '0' ?>"><?= htmlspecialchars($lp['title']) ?><?= !empty($lp['is_update']) ? ' - Policy Update' : '' ?> (<?= htmlspecialchars($lp['created_at'] ?? '') ?>)</option>
                                  <?php endforeach; ?>
                                </select>
                              </div>
                              <div class="form-group">
                                <label>Title</label>
                                <input type="text" class="form-control" id="lcm-title" readonly>
                              </div>
                              <div class="form-group">
                                <label>Status / Last Updated</label>
                                <input type="text" class="form-control" id="lcm-status-updated" readonly>
                              </div>
                              <div class="form-group">
                                <label>Target Audience</label>
                                <select class="form-control" name="target_type" id="lcm-target-type" required>
                                  <option value="all">All Employees</option>
                                  <option value="department">Specific Department</option>
                                  <option value="employees">Selected Employees</option>
                                </select>
                              </div>
                              <div class="form-group" id="lcm-department-group" hidden>
                                <label for="lcm-department">Department</label>
                                <select class="form-control" name="department_id" id="lcm-department">
                                  <option value="">Select department</option>
                                  <?php foreach ($payload['lcm_departments'] ?? [] as $department): ?>
                                    <option value="<?= (int)$department['department_id'] ?>"><?= htmlspecialchars($department['department_name']) ?></option>
                                  <?php endforeach; ?>
                                </select>
                              </div>
                              <div class="form-group" id="lcm-employees-group" hidden>
                                <label for="lcm-employees">Employees</label>
                                <select class="form-control" name="employee_ids[]" id="lcm-employees" multiple size="5">
                                  <?php foreach ($payload['employees'] ?? [] as $employee): ?>
                                    <?php $employeeName = trim(implode(' ', array_filter([$employee['first_name'] ?? '', $employee['middle_name'] ?? '', $employee['last_name'] ?? '']))); ?>
                                    <option value="<?= (int)($employee['employee_id'] ?? 0) ?>"><?= htmlspecialchars($employeeName ?: 'Employee #' . ($employee['employee_id'] ?? '')) ?></option>
                                  <?php endforeach; ?>
                                </select>
                              </div>
                              <div class="form-group">
                                <label for="lcm-announcement">Announcement Message <small class="text-muted">(Optional)</small></label>
                                <textarea class="form-control" name="announcement" id="lcm-announcement" rows="3" placeholder="Add a message for affected employees"></textarea>
                              </div>
                              <div class="form-group">
                                <label>Attachment</label>
                                <p id="lcm-attachment" class="mb-0 text-truncate">(no attachment)</p>
                              </div>
                              <button type="submit" class="btn btn-primary btn-block" <?= !$isHrAdmin ? 'disabled' : '' ?>>
                                <i class="fas fa-paper-plane"></i> <span id="lcm-share-label">Share Policy</span>
                              </button>
                            </form>
                          <?php else: ?>
                            <div class="text-muted">No policies are currently available from Legal &amp; Compliance Management.</div>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>

                    <div class="col-md-8">
                      <div class="card">
                        <?php
                          $allPolicies = [];
                          if (!empty($payload['policies'])) $allPolicies = array_merge($allPolicies, $payload['policies']);

                          $policyTitles = [];
                          foreach ($allPolicies as $policy) {
                            $title = trim((string)($policy['title'] ?? ''));
                            if ($title !== '') {
                              $policyTitles[] = $title;
                            }
                          }
                          $policyTitles = array_values(array_unique($policyTitles));
                          sort($policyTitles, SORT_NATURAL | SORT_FLAG_CASE);
                        ?>
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-file-contract"></i> Shared Policies</h3>
                          <div class="card-tools">
                            <select class="form-control form-control-sm" id="policy-filter">
                              <option value="">All Categories</option>
                              <?php foreach ($policyTitles as $title): ?>
                                <?php $titleValue = strtolower(str_replace([' ', '_', '-'], '-', $title)); ?>
                                <option value="<?= htmlspecialchars($titleValue) ?>"><?= htmlspecialchars(trim((string)$title)) ?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                        </div>
                        <div class="card-body">
                          <div id="policies-container" class="communication-post-scroll">
                            <?php if (!empty($allPolicies)): ?>
                              <?php foreach ($allPolicies as $policy): ?>
                                <?php
                                  $rawCategory = trim((string)($policy['category'] ?? 'General'));
                                  $normalizedCategory = strtolower(str_replace([' ', '_', '-'], '-', $rawCategory));
                                  $normalizedCategory = preg_replace('/[^a-z0-9-]+/', '-', $normalizedCategory);
                                  $normalizedCategory = trim((string)$normalizedCategory, '-');
                                  if ($normalizedCategory === '') {
                                    $normalizedCategory = 'general';
                                  }
                                ?>
                                <div class="card mb-3 policy-card" data-category="<?= htmlspecialchars($normalizedCategory) ?>" data-title="<?= htmlspecialchars(strtolower(str_replace([' ', '_', '-'], '-', trim((string)($policy['title'] ?? ''))))) ?>">
                                  <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                      <h6 class="card-title text-primary mb-1">
                                        <i class="fas fa-file-contract text-primary mr-2"></i>
                                        <?= htmlspecialchars($policy['title'] ?? $policy['title']) ?>
                                        <?php if (!empty($policy['is_update'])): ?><span class="badge badge-warning ml-2">Policy Update</span><?php endif; ?>
                                      </h6>

                                    </div>
                                    <p class="text-muted small mb-2">
                                      <i class="fas fa-calendar"></i> Policy date: <?= !empty($policy['created_at']) ? date('M d, Y', strtotime($policy['created_at'])) : 'N/A' ?>
                                      <?php if (!empty($policy['shared_at'])): ?>
                                        | <i class="fas fa-paper-plane"></i> Shared: <?= date('M d, Y H:i', strtotime($policy['shared_at'])) ?>
                                      <?php endif; ?>
                                      <?php if (!empty($policy['shared_target_audience'])): ?>
                                        | Audience: <?= htmlspecialchars($policy['shared_target_audience']) ?>
                                      <?php endif; ?>
                                      <?php if (!empty($policy['effective_date'] ?? $policy['effective_date'])): ?>
                                        | Effective: <?= date('M d, Y', strtotime($policy['effective_date'] ?? $policy['effective_date'])) ?>
                                      <?php endif; ?>
                                    </p>
                                    <p class="card-text"><?= nl2br(htmlspecialchars(substr($policy['content'] ?? $policy['content'], 0, 150))) ?>...</p>
                                    <?php if (!empty($policy['change_summary'])): ?><p class="small text-warning mb-2"><strong>Important changes:</strong> <?= htmlspecialchars($policy['change_summary']) ?></p><?php endif; ?>
                                    <div class="d-flex justify-content-between align-items-center flex-wrap" style="margin-top: 0.5rem; gap: 0.5rem;">
                                      <div style="flex: 1; min-width: 0;">
                                        <?php if (empty($policy['attachment_path'])): ?>
                                          <span class="badge badge-secondary" style="font-size: 10px; padding: 4px 7px; line-height: 1.2;">
                                            <i class="fas fa-minus-circle"></i> No File
                                          </span>
                                        <?php endif; ?>
                                      </div>

                                      <?php if (!empty($policy['attachment_path']) || !empty($policy['attachment_path'] ?? $policy['attachment_path'])): ?>
                                        <?php if (!empty($policy['eer_policy_id'])): ?>
                                          <a href="policy_download.php?id=<?= $policy['eer_policy_id'] ?>" class="btn btn-xs btn-outline-secondary" download style="font-size: 11px; padding: 4px 10px; line-height: 1.3; border-radius: 6px; margin-left: auto;">
                                            <i class="fas fa-download"></i> Download
                                          </a>
                                        <?php else: ?>
                                          <a href="policy_download.php?lcm=1&id=<?= urlencode($policy['source_policy_id'] ?? '') ?>" class="btn btn-xs btn-outline-secondary" download style="font-size: 11px; padding: 4px 10px; line-height: 1.3; border-radius: 6px; margin-left: auto;">
                                            <i class="fas fa-download"></i> Download
                                          </a>
                                        <?php endif; ?>
                                      <?php endif; ?>
                                    </div>
                                  </div>
                                </div>
                              <?php endforeach; ?>
                            <?php else: ?>
                              <div class="text-center text-muted py-4">
                                <i class="fas fa-file-contract fa-3x mb-3"></i>
                                <h5>No policies shared yet</h5>
                                <p>Company policies will appear here.</p>
                              </div>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="row mt-4">
                    <div class="col-md-6">
                      <div class="card card-secondary compliance-summary-card">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-check-circle"></i> Policy Acknowledgments</h3>
                        </div>
                        <div class="card-body">
                          <?php if (!empty($payload['lcm_acknowledgments'])): ?>
                            <div class="table-responsive">
                              <table class="table table-sm compliance-summary-table">
                                <thead>
                                  <tr>
                                    <th>Employee</th>
                                    <th>Policy ID</th>
                                    <th>Status</th>
                                    <th>Acknowledged At</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <?php foreach ($payload['lcm_acknowledgments'] as $ack): ?>
                                    <tr>
                                      <td class="compliance-employee"><?= htmlspecialchars($ack['employee_name'] ?? 'N/A') ?></td>
                                      <td class="compliance-policy-id">#<?= htmlspecialchars($ack['policy_id'] ?? 'N/A') ?></td>
                                      <td><span class="badge badge-<?php
                                        $status = strtolower(trim($ack['status'] ?? 'pending'));
                                        switch ($status) {
                                          case 'acknowledged': echo 'success'; break;
                                          case 'declined': echo 'danger'; break;
                                          case 'pending': echo 'warning'; break;
                                          default: echo 'secondary'; break;
                                        }
                                      ?>"><?= htmlspecialchars(ucfirst($ack['status'] ?? 'Pending')) ?></span></td>
                                      <td class="compliance-date"><?= !empty($ack['acknowledged_at']) ? date('M d, Y H:i', strtotime($ack['acknowledged_at'])) : '<span class="text-muted">Not yet</span>' ?></td>
                                    </tr>
                                  <?php endforeach; ?>
                                </tbody>
                              </table>
                            </div>
                          <?php else: ?>
                            <div class="text-center text-muted py-4">
                              <i class="fas fa-clock fa-2x mb-2"></i>
                              <p>No policy acknowledgment records found.</p>
                            </div>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                    <?php if (!empty($payload['lcm_documents'])): ?>
                    <div class="col-md-6">
                      <div class="card card-secondary">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-file-alt"></i> Document Compliance Status</h3>
                        </div>
                        <div class="card-body">
                          <?php if (!empty($payload['lcm_documents'])): ?>
                            <div class="table-responsive">
                              <table class="table table-sm table-bordered">
                                <thead>
                                  <tr>
                                    <th>Document</th>
                                    <th>Type</th>
                                    <th>Verification</th>
                                    <th>Compliance</th>
                                    <th>Expiry</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <?php foreach ($payload['lcm_documents'] as $doc): ?>
                                    <tr>
                                      <td><?= htmlspecialchars($doc['document_name'] ?? 'N/A') ?></td>
                                      <td><?= htmlspecialchars($doc['document_type'] ?? 'N/A') ?></td>
                                      <td><span class="badge badge-<?php
                                        $ver = strtolower(trim($doc['verification_status'] ?? 'pending'));
                                        echo $ver === 'verified' ? 'success' : ($ver === 'unverified' ? 'warning' : 'secondary');
                                      ?>"><?= htmlspecialchars(ucfirst($doc['verification_status'] ?? 'Pending')) ?></span></td>
                                      <td><span class="badge badge-<?php
                                        $comp = strtolower(trim($doc['compliance_status'] ?? 'unknown'));
                                        echo $comp === 'compliant' ? 'success' : ($comp === 'expiring' ? 'warning' : ($comp === 'non-compliant' ? 'danger' : 'secondary'));
                                      ?>"><?= htmlspecialchars(ucfirst($doc['compliance_status'] ?? 'Unknown')) ?></span></td>
                                      <td><?= !empty($doc['expiry_date']) ? date('M d, Y', strtotime($doc['expiry_date'])) : 'N/A' ?></td>
                                    </tr>
                                  <?php endforeach; ?>
                                </tbody>
                              </table>
                            </div>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endif; ?>

                <!-- HR-Employee Messaging Tab -->
                <div class="tab-pane fade" id="messaging" role="tabpanel">
                  <div class="row">
                    <div class="col-md-4">
                      <div class="card card-warning">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-comments"></i> Send Message</h3>
                        </div>
                        <div class="card-body">
                          <form method="POST" action="">
                            <input type="hidden" name="form_type" value="message">
                            <div class="form-group">
                              <label>To <span class="text-danger">*</span></label>
                              <select class="form-control" name="receiver_id" required>
                                <option value="">Select recipient...</option>
                                <?php foreach ($payload['employees'] ?? [] as $employee): ?>
                                  <?php
                                    $employeeName = trim(implode(' ', array_filter([
                                      $employee['first_name'] ?? '',
                                      $employee['middle_name'] ?? '',
                                      $employee['last_name'] ?? ''
                                    ])));
                                  ?>
                                  <option value="<?= htmlspecialchars($employee['employee_id']) ?>">
                                    <?= htmlspecialchars($employeeName ?: ($employee['employee_code'] ?? $employee['employee_id'])) ?>
                                  </option>
                                <?php endforeach; ?>
                              </select>
                            </div>
                            <div class="form-group">
                              <label>Message <span class="text-danger">*</span></label>
                              <textarea class="form-control" name="message" rows="4" required placeholder="Type your message..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-info btn-block">
                              <i class="fas fa-paper-plane"></i> Send Message
                            </button>
                          </form>
                        </div>
                      </div>

                    
                    </div>

                    <div class="col-md-8">
                      <div class="card">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-inbox"></i> Message Threads</h3>
                          <div class="card-tools">

                          </div>
                        </div>
                        <div class="card-body message-thread">
                          <!-- DEBUG INFO -->
                          <div class="alert alert-info" style="display: none;" id="debug-info">
                            Current Employee ID: <?= htmlspecialchars($currentEmployeeId ?? 'NULL') ?><br/>
                            Message Threads Count: <?= count($payload['messageThreads'] ?? []) ?>
                          </div>
                          
                          <div id="messages-container" class="communication-post-scroll">
                            <?php if (!empty($payload['messageThreads'])): ?>
                              <?php foreach ($payload['messageThreads'] as $message): ?>
                                <?php $isSent = (!empty($currentEmployeeId) && (int)$message['sender_id'] === (int)$currentEmployeeId); ?>
                                <div class="message-bubble <?= $isSent ? 'sent' : 'received' ?>">
                                  <div class="p-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                      <small class="text-muted">
                                        <i class="fas fa-user"></i> <?= htmlspecialchars($message['sender_name'] ?? $message['sender_id']) ?>
                                      </small>
                                      <small class="text-muted">
                                        <i class="fas fa-clock"></i> <?= date('H:i', strtotime($message['timestamp'])) ?>
                                      </small>
                                    </div>
                                    <p class="mb-0"><?= htmlspecialchars($message['message']) ?></p>
                                  </div>
                                </div>
                              <?php endforeach; ?>
                            <?php else: ?>
                              <div class="text-center text-muted py-4">
                                <i class="fas fa-comments fa-3x mb-3"></i>
                                <h5>No messages yet</h5>
                                <p>Your conversations with HR will appear here.</p>
                              </div>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
  </div>

<!-- Announcement Detail Modal -->
<div id="announcement-modal" class="modal fade" role="dialog" style="display: none; z-index: 1050;">
  <div class="modal-dialog" style="margin-top: 3rem;">
    <div class="modal-content" style="border: none; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
      <div class="modalheader" style="background: linear-gradient(135deg, #0066cc 0%, #004999 100%); border-bottom: none;">
        <h5 class="modal-title" style="color: white; font-weight: 600;">Announcement Details</h5>
        <button type="button" class="close" onclick="closeAnnouncementModal()" style="color: white;">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body" id="announcement-modal-content" style="padding: 1.5rem;">
        <!-- Content will be loaded here -->
      </div>
    </div>
  </div>
</div>

<!-- Modal Backdrop -->
<div id="announcement-modal-backdrop" class="modal-backdrop fade" style="display: none; z-index: 1040;"></div>
      

  <script src="pages/js/communication.js"></script>
