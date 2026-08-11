<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../models/reportModel.php';
require_once __DIR__ . '/../../auth/database.php';

use Dompdf\Dompdf;

class ReportPdfController
{
    private ReportModel $model;

    public function __construct(PDO $db)
    {
        $this->model = new ReportModel($db);
    }

    public function generate(int $periodId)
    {
        $payroll = $this->model->getPayrollOverview($periodId);
        $period = $this->model->getPeriodById($periodId);

        ob_start();
        include __DIR__ . '/../public/pdf_template.php';
        $html = ob_get_clean();

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = "Payroll_Report_{$period['period_name']}.pdf";

        $dompdf->stream($filename, [
            'Attachment' => true
        ]);
    }
}
