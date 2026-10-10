<?php

require_once __DIR__ . '/ExitManagementModel.php';

class ExitManagementController
{
    private ExitManagementModel $model;

    public function __construct($db)
    {
        $this->model = new ExitManagementModel($db);
    }

    public function getExitRequests($status = null)
    {
        return $this->model->getExitRequests($status);
    }

    public function getExitRequestById($id)
    {
        return $this->model->getExitRequestById($id);
    }

    public function getExitApprovals($exitRequestId)
    {
        return $this->model->getExitApprovals($exitRequestId);
    }

    public function getClearanceItems($exitRequestId)
    {
        return $this->model->getClearanceItems($exitRequestId);
    }

    public function getExitInterview($exitRequestId)
    {
        return $this->model->getExitInterview($exitRequestId);
    }

    public function getVacantPositions()
    {
        return $this->model->getVacantPositions();
    }

    public function updateExitLegalStatus($id, $status, $reviewedBy = null, $remarks = null)
    {
        return $this->model->updateExitLegalStatus($id, $status, $reviewedBy, $remarks);
    }

    public function updateExitRecruitmentStatus($id, $status)
    {
        return $this->model->updateExitRecruitmentStatus($id, $status);
    }

    public function getExitActivityLog($exitRequestId)
    {
        return $this->model->getExitActivityLog($exitRequestId);
    }

    public function rejectExit($id, $reviewedBy = null, $remarks = null)
    {
        return $this->model->rejectExit($id, $reviewedBy, $remarks);
    }
}
