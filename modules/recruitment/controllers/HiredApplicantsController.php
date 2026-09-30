<?php
require_once __DIR__ . '/../classes/RaoHired.php';
require_once __DIR__ . '/../classes/Employee.php';

class HiredApplicantsController
{
    private $raoModel;
    private $employeeModel;

    public function __construct()
    {
        $this->raoModel = new RaoHired();
        $this->employeeModel = new Employee();
    }

    public function index()
    {
        $raoHired = $this->raoModel->getAll();
        require __DIR__ . '/../pages/hired_applicants.php';;
    }

    public function moveToEmployee($id)
    {
        $rao = $this->raoModel->getById($id);

        if ($rao) {
            $this->employeeModel->addFromRaoHired($rao);

            // ✅ correct redirect
            header('Location: index.php?page=hired_applicants&success=Moved to Employees');
            exit;
        } else {
            header('Location: index.php?page=hired_applicants&error=Record not found');
            exit;
        }
    }
}
