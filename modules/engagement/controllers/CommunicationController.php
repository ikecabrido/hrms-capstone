<?php
namespace App\Controllers;

use App\Models\Announcement;
use App\Models\Employee;
use App\Models\LcmIntegration;
use App\Models\Notification;
use App\Models\PolicyShare;

class CommunicationController
{
    private $announcement;
    private $notification;
    private $policyShare;
    private $employee;
    private $lcm;

    public function __construct()
    {
        $this->announcement = new Announcement();
        $this->notification = new Notification();
        $this->policyShare = new PolicyShare();
        $this->employee = new Employee();
        $this->lcm = new LcmIntegration();
    }

    public function getPageData($employeeId = null, $includeAllLcmData = false)
    {
        $data = [
            'announcements' => $this->getAnnouncements(),
            'notifications' => $this->getNotifications(),
            'department_updates' => $this->getDepartmentUpdates(),
            'departments' => $this->lcm->getDepartments(),
            'policies' => $this->getSharedLcmPolicies(),
            'policy_updates' => $this->getPolicyUpdates(),
            'messageThreads' => (new MessageController())->messageThreads($employeeId),
            'employees' => $this->employee->all(),
            'lcm_notifications' => [],
            'lcm_policies' => [],
            'lcm_acknowledgments' => [],
            'lcm_documents' => [],
        ];

        if ($employeeId !== null && $employeeId !== '') {
            $data['lcm_notifications'] = $this->lcm->getNotificationsForEmployee((int)$employeeId);
            $data['lcm_acknowledgments'] = $this->lcm->getPolicyAcknowledgmentsForEmployee($includeAllLcmData ? null : (int)$employeeId);
            $data['lcm_documents'] = $this->lcm->getEmployeeDocumentsForEmployee($includeAllLcmData ? null : (int)$employeeId);
        }

        $data['lcm_policies'] = $this->getLcmPolicies();

        return $data;
    }

    public function getAnnouncements()
    {
        return $this->announcement->getAnnouncements('announcement');
    }

    public function getRecognitionAnnouncements()
    {
        return $this->announcement->getRecognitionAnnouncements();
    }

    public function getDepartmentUpdates()
    {
        return $this->announcement->getDepartmentUpdates();
    }

    public function postAnnouncement($title, $content, $created_by_employee_id, $category = 'general', $priority = 'normal', $targetAudience = 'all')
    {
        return $this->announcement->postAnnouncement($title, $content, $created_by_employee_id, $category, $priority, $targetAudience);
    }

    public function postRecognitionAnnouncement($title, $content, $created_by_employee_id)
    {
        return $this->announcement->postRecognitionAnnouncement($title, $content, $created_by_employee_id);
    }

    public function postEvent($title, $description, $date, $created_by_employee_id)
    {
        return $this->announcement->postEvent($title, $description, $date, $created_by_employee_id);
    }

    public function postDepartmentUpdate($title, $content, $department, $priority, $created_by_employee_id)
    {
        return $this->announcement->postDepartmentUpdate($title, $content, $department, $priority, $created_by_employee_id);
    }

    public function getPolicyUpdates()
    {
        return $this->announcement->getPolicyUpdates();
    }

    public function getLcmPolicies()
    {
        $policies = $this->lcm->getPolicies();
        foreach ($policies as &$policy) {
            if (!$policy['is_update'] && $this->policyShare->hasPreviousShare('LCM', (string)($policy['source_policy_id'] ?? ''))) {
                $policy['is_update'] = true;
            }
        }
        unset($policy);
        return $policies;
    }

    public function getSharedLcmPolicies()
    {
        $policies = $this->getLcmPolicies();
        foreach ($policies as &$policy) {
            $policyKey = (string)($policy['source_policy_key'] ?? $policy['source_policy_id'] ?? '');
            $policyId = (string)($policy['source_policy_id'] ?? '');
            $share = $this->policyShare->getShare('LCM', $policyKey ?: $policyId);
            if ($share) {
                $policy['shared_at'] = $share['shared_at'];
                $policy['share_id'] = $share['id'];
                $policy['shared_target_audience'] = $share['target_audience'];
                $policy['share_status'] = $share['status'];
            }
        }
        unset($policy);

        return array_values(array_filter($policies, static function ($policy) {
            return !empty($policy['shared_at']);
        }));
    }

    public function shareLcmPolicy(string $sourceModule, string $sourcePolicyId, string $targetAudience = 'all', $sharedBy = null, string $announcement = '')
    {
        if (strtoupper($sourceModule) !== 'LCM') {
            throw new \InvalidArgumentException('Policies can only be shared from Legal & Compliance Management.');
        }

        $sourcePolicy = null;
        foreach ($this->getLcmPolicies() as $policy) {
            $policyKey = (string)($policy['source_policy_key'] ?? '');
            $policyId = (string)($policy['source_policy_id'] ?? '');
            if ($policyKey === $sourcePolicyId || $policyId === $sourcePolicyId || $policyKey === ($sourcePolicyId . '|%')) {
                $sourcePolicy = $policy;
                break;
            }
        }
        if (!$sourcePolicy) {
            throw new \InvalidArgumentException('The selected Legal & Compliance policy was not found.');
        }

        $existingShare = $this->policyShare->getShare('LCM', $sourcePolicyId);
        if ($existingShare) {
            return (int)$existingShare['id'];
        }

        $isPolicyUpdate = !empty($sourcePolicy['is_update'])
            || $this->policyShare->hasPreviousShare('LCM', (string)($sourcePolicy['source_policy_id'] ?? $sourcePolicyId));

        $shareId = $this->policyShare->shareAndNotify(
            'LCM',
            $sourcePolicyId,
            $targetAudience,
            $sharedBy,
            ($isPolicyUpdate ? 'Policy Updated: ' : 'New Policy Shared: ') . ($sourcePolicy['title'] ?: 'Legal & Compliance policy'),
            trim($announcement) ?: ($sourcePolicy['change_summary'] ?: 'Please review the policy shared by Legal & Compliance Management.')
        );

        return $shareId;
    }

    public function getLcmDepartments()
    {
        return $this->lcm->getDepartments();
    }

    public function getSharedFiles()
    {
        return $this->announcement->getSharedFiles();
    }

    public function shareFile($userId, $fileName, $filePath, $fileSize, $fileType, $description = '', $content = null, $authorType = 'user')
    {
        return $this->announcement->shareFile($userId, $fileName, $filePath, $fileSize, $fileType, $description, $content, $authorType);
    }

    public function getSharedFileById($id)
    {
        return $this->announcement->getSharedFileById($id);
    }

    public function deleteSharedFile($id)
    {
        return $this->announcement->deleteSharedFile($id);
    }

    public function sendMessage($sender_id, $receiver_id, $message)
    {
        // Moved to MessageController
        $messageCtrl = new \App\Controllers\MessageController();
        return $messageCtrl->sendMessage($sender_id, $receiver_id, $message);
    }

    public function messageThreads($employee_id)
    {
        // Moved to MessageController
        $messageCtrl = new \App\Controllers\MessageController();
        return $messageCtrl->messageThreads($employee_id);
    }

    public function getMessageHistory($sender_id, $receiver_id)
    {
        // Moved to MessageController
        $messageCtrl = new \App\Controllers\MessageController();
        return $messageCtrl->getMessageHistory($sender_id, $receiver_id);
    }

    public function postHRNotification($title, $content, $notificationType, $targetEmployees, $currentUserId)
    {
        return $this->notification->postHRNotification($title, $content, $notificationType, $targetEmployees);
    }

    public function getNotifications()
    {
        return $this->notification->getAll();
    }

    public function markNotificationAsRead($notification_id)
    {
        return $this->notification->markAsRead($notification_id);
    }
}
