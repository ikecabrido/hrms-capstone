<?php
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../helpers/AuditLog.php';

class NotificationController
{
    private $model;
    private $auditLog;

    public function __construct()
    {
        $this->model = new Notification();
        $this->auditLog = new AuditLog();
    }

    // For now, a simple dispatcher that marks SMS as simulated if no API
    public function dispatch($notification_id)
    {
        // In a full implementation, integrate with email/SMS gateways
        // Here we mark as simulated queued -> sent
        $this->model->markSent($notification_id, 'sms');
        $this->auditLog->log('NOTIFICATION_DISPATCH', null, null, null, ['notification_id' => $notification_id], 'SUCCESS');
        return true;
    }
}
