<?php
/**
 * BCP HRMS
 * Salary Correction Agreement
 */

$employeeName =
    $employeeName
    ?? ($employee['name'] ?? null)
    ?? ($employee['employee_name'] ?? null)
    ?? '—';

$employeeNumber =
    $employeeNumber
    ?? ($employee['employee_number'] ?? null)
    ?? ($employee['employee_id'] ?? null)
    ?? '—';

$department =
    $department
    ?? ($employee['department'] ?? null)
    ?? '—';

$position =
    $position
    ?? ($employee['position'] ?? null)
    ?? '—';

$dateHired =
    $dateHired
    ?? ($employee['date_hired'] ?? null)
    ?? '—';

$originalContractDate =
    $originalContractDate
    ?? ($contract['date'] ?? null)
    ?? ($employee['contract_date'] ?? null)
    ?? '—';

$rectificationDate =
    $rectificationDate
    ?? date('F j, Y');

$effectiveDate =
    $effectiveDate
    ?? ($salary_effective_date ?? null)
    ?? '—';

$originalSalary =
    $originalSalary
    ?? ($_GET['original_salary'] ?? null)
    ?? null;

$correctedSalary =
    $correctedSalary
    ?? ($corrected_salary ?? null)
    ?? ($_GET['corrected_salary'] ?? null)
    ?? null;

$employmentStatus =
    $employmentStatus
    ?? ($employee['employment_status'] ?? null)
    ?? 'Active';

$documentNumber =
    $documentNumber
    ?? ($document_no ?? null)
    ?? ('HR-AGR-' . date('Y') . '-' . $employeeNumber);

$formatMoney = static function ($amount): string {
    if ($amount === null || $amount === '') {
        return '—';
    }

    return '₱' . number_format((float) $amount, 2);
};

$esc = static function ($value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};
?>

<link rel="stylesheet"
      href="<?= htmlspecialchars(
          dirname($_SERVER['SCRIPT_NAME'] ?? '') .
          '/salary_rectification_agreement.css',
          ENT_QUOTES,
          'UTF-8'
      ) ?>">

<div class="document-preview">

    <header class="document-header">

        <div class="document-organization">
            BESTLINK COLLEGE OF THE PHILIPPINES
        </div>

        <div class="document-department">
            Human Resources Department
        </div>

        <h1 class="document-title">
            SALARY CORRECTION AGREEMENT
        </h1>

        <div class="document-subtitle">
            Amendment to Employment Contract
        </div>

        <div class="document-control">
            Document No.:
            <strong><?= $esc($documentNumber) ?></strong>
        </div>

    </header>

    <hr class="document-separator">

    <section class="document-section">

        <h2 class="section-title">
            Employee Information
        </h2>

        <table class="document-information">
            <tbody>
                <tr>
                    <td class="info-label">Employee Name</td>
                    <td class="info-value"><?= $esc($employeeName) ?></td>
                </tr>

                <tr>
                    <td class="info-label">Employee Number</td>
                    <td class="info-value"><?= $esc($employeeNumber) ?></td>
                </tr>

                <tr>
                    <td class="info-label">Department</td>
                    <td class="info-value"><?= $esc($department) ?></td>
                </tr>

                <tr>
                    <td class="info-label">Position</td>
                    <td class="info-value"><?= $esc($position) ?></td>
                </tr>

                <tr>
                    <td class="info-label">Date Hired</td>
                    <td class="info-value"><?= $esc($dateHired) ?></td>
                </tr>

                <tr>
                    <td class="info-label">Employment Status</td>
                    <td class="info-value"><?= $esc($employmentStatus) ?></td>
                </tr>
            </tbody>
        </table>

    </section>

    <section class="document-section">

        <h2 class="section-title">
            Contract Information
        </h2>

        <table class="document-information">
            <tbody>
                <tr>
                    <td class="info-label">Original Contract Date</td>
                    <td class="info-value"><?= $esc($originalContractDate) ?></td>
                </tr>

                <tr>
                    <td class="info-label">Date of Correction</td>
                    <td class="info-value"><?= $esc($rectificationDate) ?></td>
                </tr>

                <tr>
                    <td class="info-label">Salary Effective Date</td>
                    <td class="info-value"><?= $esc($effectiveDate) ?></td>
                </tr>

                <tr>
                    <td class="info-label">Original Monthly Salary</td>
                    <td class="info-value money"><?= $formatMoney($originalSalary) ?></td>
                </tr>

                <tr>
                    <td class="info-label">Corrected Monthly Salary</td>
                    <td class="info-value money"><?= $formatMoney($correctedSalary) ?></td>
                </tr>
            </tbody>
        </table>

    </section>

    <main class="document-body">

        <p>
            This Salary Correction Agreement ("Agreement") is
            entered into by and between
            <strong>Bestlink College of the Philippines</strong>
            (the "Employer") and
            <strong><?= $esc($employeeName) ?></strong>
            (the "Employee"), in connection with the Employee's
            Employment Contract dated
            <strong><?= $esc($originalContractDate) ?></strong>.
        </p>

        <h2>1. Salary Correction</h2>

        <p>
            The salary stated in the original Employment Contract
            was recorded incorrectly due to an administrative or
            clerical error.
        </p>

        <p>
            The salary provision is therefore corrected as follows:
        </p>

        <table class="salary-comparison">
            <tbody>
                <tr>
                    <td>Salary stated in original contract</td>
                    <td><?= $formatMoney($originalSalary) ?></td>
                </tr>

                <tr>
                    <td>Correct monthly salary</td>
                    <td><?= $formatMoney($correctedSalary) ?></td>
                </tr>

                <tr>
                    <td>Effective date</td>
                    <td><?= $esc($effectiveDate) ?></td>
                </tr>
            </tbody>
        </table>

        <p>
            The corrected salary shall apply beginning on the
            effective date stated above.
        </p>

        <h2>2. Payroll Implementation</h2>

        <p>
            The Human Resources Department and Payroll Office shall
            update the Employee's employment and payroll records to
            reflect the corrected salary.
        </p>

        <p>
            If payroll processing has already been affected by the
            incorrect salary, any resulting adjustment shall be
            handled in accordance with applicable law, company
            policy, and the Employee's applicable employment terms.
        </p>

        <h2>3. No Other Changes</h2>

        <p>
            Except for the salary provision expressly amended by
            this Agreement, all other terms and conditions of the
            Employment Contract remain unchanged and in full force
            and effect.
        </p>

        <h2>4. Effectivity</h2>

        <p>
            This Agreement shall form part of the Employee's
            Employment Contract and shall be effective beginning
            <strong><?= $esc($effectiveDate) ?></strong>.
        </p>

        <h2>5. Acknowledgment</h2>

        <p>
            By signing below, the parties acknowledge that they
            have read and understood this Agreement and agree to
            the salary correction stated herein.
        </p>

        <p class="closing-statement">
            IN WITNESS WHEREOF, the parties have signed this
            Agreement on the date indicated below.
        </p>

    </main>

    <section class="signature-grid">

        <div class="signature-block">
            <div class="signature-heading">FOR THE EMPLOYER</div>

            <div class="signature-space"></div>

            <div class="signature-line"></div>

            <strong>Authorized Representative</strong>

            <div>Name: __________________________</div>
            <div>Position: _______________________</div>
            <div>Date: ___________________________</div>
        </div>

        <div class="signature-block">
            <div class="signature-heading">EMPLOYEE</div>

            <div class="signature-space"></div>

            <div class="signature-line"></div>

            <strong><?= $esc($employeeName) ?></strong>

            <div>
                Employee No.: <?= $esc($employeeNumber) ?>
            </div>

            <div>Date: ___________________________</div>
        </div>

    </section>

    <section class="signature-grid witnesses">

        <div class="signature-block">
            <div class="signature-heading">WITNESS</div>

            <div class="signature-space small"></div>

            <div class="signature-line"></div>

            <div>Name: __________________________</div>
            <div>Date: ___________________________</div>
        </div>

        <div class="signature-block">
            <div class="signature-heading">WITNESS</div>

            <div class="signature-space small"></div>

            <div class="signature-line"></div>

            <div>Name: __________________________</div>
            <div>Date: ___________________________</div>
        </div>

    </section>

    <footer class="document-footer">
        <span>Human Resources Department</span>
        <span>Document No. <?= $esc($documentNumber) ?></span>
    </footer>

</div>
