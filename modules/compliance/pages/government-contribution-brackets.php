<?php

$pageTitle = 'Government Contribution Brackets';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (!isset($user) || empty($user)) {
    $user = $_SESSION['user'] ?? [];
}
if (!isset($db)) {
    if (class_exists('Database')) {
        $db = (new Database())->getConnection();
    } else {
        require_once __DIR__ . '/../../../database/db.php';
        $db = (new Database())->getConnection();
    }
}

$type = strtolower((string) ($_GET['type'] ?? 'sss'));
$validTypes = ['sss', 'philhealth', 'pagibig', 'bir', 'all'];
if (!in_array($type, $validTypes, true)) {
    $type = 'sss';
}

$labels = [
    'sss'        => 'SSS Contribution Brackets',
    'philhealth' => 'PhilHealth Contribution Brackets',
    'pagibig'    => 'Pag-IBIG Contribution Brackets',
    'bir'        => 'BIR Tax Table',
    'all'        => 'All Government Contributions',
];

$titles = [
    'sss'        => 'SSS Contribution Brackets',
    'philhealth' => 'PhilHealth Contribution Brackets',
    'pagibig'    => 'Pag-IBIG Contribution Brackets',
    'bir'        => 'BIR Tax Table',
    'all'        => 'All Government Contributions',
];

$descriptions = [
    'sss' => 'The Social Security System (SSS) Contribution Brackets determine the amount of mandatory monthly contributions for em_employees and employers. These brackets are based on the employee\'s Monthly Salary Credit (MSC), which corresponds to the employee\'s monthly compensation. Each salary range is assigned a specific MSC, and the applicable employee and employer contribution rates are calculated using this value.',
    'philhealth' => 'The PhilHealth Premium Contribution is a mandatory monthly contribution required under the Universal Health Care (UHC) Act (Republic Act No. 11223). It funds the National Health Insurance Program, providing members and their qualified dependents with access to inpatient, outpatient, emergency, preventive, and other health care benefits.',
    'pagibig' => 'The Home Development Mutual Fund (HDMF), commonly known as the Pag-IBIG Fund, is a government-mandated savings program that provides Filipino workers with access to affordable housing loans, multi-purpose loans, calamity loans, and savings benefits. Monthly contributions made by em_employees and employers are credited to the member\'s Regular Savings account and earn annual dividends declared by the Pag-IBIG Fund Board.',
    'bir' => 'The Bureau of Internal Revenue (BIR) is the government agency responsible for administering and collecting national taxes in the Philippines. For em_employees, employers are required to withhold income tax from compensation and remit it to the BIR through the Withholding Tax on Compensation system. The amount withheld is an advance payment of the employee\'s annual income tax liability and is computed using the applicable BIR withholding tax table.',
    'all' => 'A consolidated quick-reference table showing SSS, PhilHealth, Pag-IBIG, and BIR contribution brackets for common monthly compensation ranges. Use this table to compare contribution amounts across all major government agencies at a glance.',
];

$pageTitle = $labels[$type] ?? 'Government Contribution Brackets';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<section class="gcb-module">
  <div class="gcb-card">
    <div class="gcb-card-head">
      <h3><i class="bi bi-percent"></i> <?= htmlspecialchars($titles[$type]) ?></h3>
    </div>
    <div class="gcb-body">
      <p class="gcb-desc"><?= htmlspecialchars($descriptions[$type]) ?></p>

      <?php if ($type === 'sss'): ?>
        <div class="gcb-section">
          <h4>Monthly Compensation Range vs Monthly Salary Credit (MSC)</h4>
          <div class="gcb-table-wrap">
            <table class="gcb-table">
              <thead>
                <tr><th>Monthly Compensation Range</th><th>Monthly Salary Credit (MSC)</th></tr>
              </thead>
              <tbody>
                <tr><td>Below ₱5,250</td><td>₱5,000</td></tr>
                <tr><td>₱5,250 – ₱5,749.99</td><td>₱5,500</td></tr>
                <tr><td>₱5,750 – ₱6,249.99</td><td>₱6,000</td></tr>
                <tr><td>₱6,250 – ₱6,749.99</td><td>₱6,500</td></tr>
                <tr><td>₱6,750 – ₱7,249.99</td><td>₱7,000</td></tr>
                <tr><td>₱7,250 – ₱7,749.99</td><td>₱7,500</td></tr>
                <tr><td>₱7,750 – ₱8,249.99</td><td>₱8,000</td></tr>
                <tr><td>₱8,250 – ₱8,749.99</td><td>₱8,500</td></tr>
                <tr><td>₱8,750 – ₱9,249.99</td><td>₱9,000</td></tr>
                <tr><td>₱9,250 – ₱9,749.99</td><td>₱9,500</td></tr>
                <tr><td>₱9,750 – ₱10,249.99</td><td>₱10,000</td></tr>
                <tr><td>...</td><td>...</td></tr>
                <tr><td>₱19,750 – ₱20,249.99</td><td>₱20,000</td></tr>
                <tr><td>...</td><td>...</td></tr>
                <tr><td>₱34,750 and above</td><td><strong>₱35,000 (Maximum MSC)</strong></td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="gcb-section">
          <h4>Contribution Schedule by Monthly Salary Credit (MSC)</h4>
          <div class="gcb-table-wrap">
            <table class="gcb-table">
              <thead>
                <tr><th>Monthly Salary Credit (MSC)</th><th>Employee (5%)</th><th>Employer (10%)</th><th>Total (15%)</th></tr>
              </thead>
              <tbody>
                <tr><td>₱5,000</td><td>₱250</td><td>₱500</td><td>₱750</td></tr>
                <tr><td>₱10,000</td><td>₱500</td><td>₱1,000</td><td>₱1,500</td></tr>
                <tr><td>₱15,000</td><td>₱750</td><td>₱1,500</td><td>₱2,250</td></tr>
                <tr><td>₱20,000</td><td>₱1,000</td><td>₱2,000</td><td>₱3,000</td></tr>
                <tr><td>₱25,000</td><td>₱1,250</td><td>₱2,500</td><td>₱3,750</td></tr>
                <tr><td>₱30,000</td><td>₱1,500</td><td>₱3,000</td><td>₱4,500</td></tr>
                <tr><td>₱35,000</td><td>₱1,750</td><td>₱3,500</td><td>₱5,250</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      <?php elseif ($type === 'philhealth'): ?>
        <div class="gcb-section">
          <h4>Overview</h4>
          <p class="gcb-text">The <strong>PhilHealth Premium Contribution</strong> is a mandatory monthly contribution required under the <strong>Universal Health Care (UHC) Act (Republic Act No. 11223)</strong>. It funds the National Health Insurance Program, providing members and their qualified dependents with access to inpatient, outpatient, emergency, preventive, and other health care benefits.</p>
          <p class="gcb-text">For em_employees in the formal sector, the monthly premium is shared equally between the employer and the employee. The computation is based on the employee's <strong>Monthly Basic Salary (MBS)</strong>, excluding allowances, overtime pay, commissions, bonuses, 13th-month pay, and similar benefits.</p>
        </div>

        <div class="gcb-section">
          <h4>Purpose of PhilHealth Contributions</h4>
          <p class="gcb-text">PhilHealth contributions are collected to:</p>
          <ul class="gcb-list">
            <li>Provide universal health insurance coverage.</li>
            <li>Reduce healthcare expenses for members.</li>
            <li>Support hospitalization and medical treatments.</li>
            <li>Finance preventive and primary healthcare services.</li>
            <li>Ensure continuous access to accredited healthcare providers.</li>
          </ul>
        </div>

        <div class="gcb-section">
          <h4>Contribution Rate</h4>
          <div class="gcb-table-wrap">
            <table class="gcb-table">
              <thead>
                <tr><th>Description</th><th>Value</th></tr>
              </thead>
              <tbody>
                <tr><td>Premium Rate</td><td>5%</td></tr>
                <tr><td>Employee Share</td><td>2.5%</td></tr>
                <tr><td>Employer Share</td><td>2.5%</td></tr>
                <tr><td>Income Floor</td><td>₱10,000</td></tr>
                <tr><td>Income Ceiling</td><td>₱100,000</td></tr>
              </tbody>
            </table>
          </div>
          <p class="gcb-text">Employees and employers each shoulder 50% of the total monthly premium.</p>
        </div>

        <div class="gcb-section">
          <h4>Formula</h4>
          <div class="gcb-formula">
            <div class="gcb-formula-item"><strong>Monthly Premium</strong> = Monthly Basic Salary × 5%</div>
            <div class="gcb-formula-item"><strong>Employee Share</strong> = Monthly Premium ÷ 2</div>
            <div class="gcb-formula-item"><strong>Employer Share</strong> = Monthly Premium ÷ 2</div>
          </div>
        </div>

        <div class="gcb-section">
          <h4>Salary Brackets</h4>
          <div class="gcb-table-wrap">
            <table class="gcb-table">
              <thead>
                <tr><th>Monthly Basic Salary</th><th>Total Premium</th><th>Employee Share</th><th>Employer Share</th></tr>
              </thead>
              <tbody>
                <tr><td>Up to ₱10,000</td><td>₱500.00</td><td>₱250.00</td><td>₱250.00</td></tr>
                <tr><td>₱10,001 – ₱20,000</td><td>5% of Salary</td><td>2.5%</td><td>2.5%</td></tr>
                <tr><td>₱20,001 – ₱30,000</td><td>5% of Salary</td><td>2.5%</td><td>2.5%</td></tr>
                <tr><td>₱30,001 – ₱40,000</td><td>5% of Salary</td><td>2.5%</td><td>2.5%</td></tr>
                <tr><td>₱40,001 – ₱50,000</td><td>5% of Salary</td><td>2.5%</td><td>2.5%</td></tr>
                <tr><td>₱50,001 – ₱75,000</td><td>5% of Salary</td><td>2.5%</td><td>2.5%</td></tr>
                <tr><td>₱75,001 – ₱99,999</td><td>5% of Salary</td><td>2.5%</td><td>2.5%</td></tr>
                <tr><td>₱100,000 and above</td><td>₱5,000.00</td><td>₱2,500.00</td><td>₱2,500.00</td></tr>
              </tbody>
            </table>
          </div>
          <p class="gcb-note">Note: Salaries below ₱10,000 use the minimum income floor of ₱10,000, while salaries above ₱100,000 use the maximum income ceiling of ₱100,000.</p>
        </div>

        <div class="gcb-section">
          <h4>Sample Computation</h4>
          <div class="gcb-sample">
            <div class="gcb-sample-head">Employee with ₱30,000 Monthly Basic Salary</div>
            <table class="gcb-table gcb-table--compact">
              <tbody>
                <tr><td>Monthly Basic Salary</td><td>₱30,000.00</td></tr>
                <tr><td>Premium Rate</td><td>5%</td></tr>
                <tr><td>Total Premium</td><td>₱1,500.00</td></tr>
                <tr><td>Employee Share</td><td>₱750.00</td></tr>
                <tr><td>Employer Share</td><td>₱750.00</td></tr>
              </tbody>
            </table>
          </div>
          <div class="gcb-sample">
            <div class="gcb-sample-head">Employee with ₱75,000 Monthly Basic Salary</div>
            <table class="gcb-table gcb-table--compact">
              <tbody>
                <tr><td>Monthly Basic Salary</td><td>₱75,000.00</td></tr>
                <tr><td>Premium Rate</td><td>5%</td></tr>
                <tr><td>Total Premium</td><td>₱3,750.00</td></tr>
                <tr><td>Employee Share</td><td>₱1,875.00</td></tr>
                <tr><td>Employer Share</td><td>₱1,875.00</td></tr>
              </tbody>
            </table>
          </div>
          <div class="gcb-sample">
            <div class="gcb-sample-head">Employee with ₱120,000 Monthly Basic Salary</div>
            <table class="gcb-table gcb-table--compact">
              <tbody>
                <tr><td>Monthly Basic Salary</td><td>₱100,000.00 (Maximum Ceiling)</td></tr>
                <tr><td>Premium Rate</td><td>5%</td></tr>
                <tr><td>Total Premium</td><td>₱5,000.00</td></tr>
                <tr><td>Employee Share</td><td>₱2,500.00</td></tr>
                <tr><td>Employer Share</td><td>₱2,500.00</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="gcb-section">
          <h4>Contribution Breakdown</h4>
          <div class="gcb-table-wrap">
            <table class="gcb-table">
              <thead>
                <tr><th>Component</th><th>Description</th></tr>
              </thead>
              <tbody>
                <tr><td>Monthly Basic Salary</td><td>Basis for premium computation.</td></tr>
                <tr><td>Income Floor</td><td>Minimum salary considered for computation (₱10,000).</td></tr>
                <tr><td>Income Ceiling</td><td>Maximum salary considered for computation (₱100,000).</td></tr>
                <tr><td>Employee Share</td><td>Deducted from the employee's payroll.</td></tr>
                <tr><td>Employer Share</td><td>Paid separately by the employer.</td></tr>
                <tr><td>Total Premium</td><td>Combined employee and employer contributions remitted to PhilHealth.</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="gcb-section">
          <h4>Benefits Covered</h4>
          <p class="gcb-text">Active PhilHealth members may qualify for benefits including:</p>
          <ul class="gcb-list">
            <li>Inpatient Hospital Care</li>
            <li>Outpatient Benefits</li>
            <li>Emergency Medical Services</li>
            <li>Maternity Care</li>
            <li>Surgical Procedures</li>
            <li>Dialysis Treatment</li>
            <li>Cancer Treatment Packages</li>
            <li>Preventive Healthcare Services</li>
            <li>Primary Care Benefits</li>
            <li>Selected Specialized Medical Packages</li>
          </ul>
        </div>

        <div class="gcb-section">
          <h4>Employer Responsibilities</h4>
          <p class="gcb-text">Employers are required to:</p>
          <ul class="gcb-list">
            <li>Register em_employees with PhilHealth.</li>
            <li>Deduct the employee's share from monthly payroll.</li>
            <li>Pay the employer's corresponding share.</li>
            <li>Remit the total premium contribution on time.</li>
            <li>Maintain accurate contribution and payroll records.</li>
            <li>Report employee information and updates as required by PhilHealth.</li>
          </ul>
        </div>

        <div class="gcb-section">
          <h4>Compliance Reminder</h4>
          <p class="gcb-text">To ensure compliance with PhilHealth regulations:</p>
          <ul class="gcb-list">
            <li>Use the employee's Monthly Basic Salary only when computing contributions.</li>
            <li>Exclude allowances, overtime pay, commissions, bonuses, and 13th-month pay from the computation.</li>
            <li>Apply the prescribed income floor and income ceiling when determining the monthly premium.</li>
            <li>Remit contributions within the prescribed deadlines to avoid penalties and ensure uninterrupted member coverage.</li>
          </ul>
        </div>
      <?php elseif ($type === 'pagibig'): ?>
        <div class="gcb-section">
          <h4>Overview</h4>
          <p class="gcb-text">The <strong>Home Development Mutual Fund (HDMF)</strong>, commonly known as the <strong>Pag-IBIG Fund</strong>, is a government-mandated savings program that provides Filipino workers with access to affordable housing loans, multi-purpose loans, calamity loans, and savings benefits. Monthly contributions made by em_employees and employers are credited to the member's Regular Savings account and earn annual dividends declared by the Pag-IBIG Fund Board.</p>
        </div>

        <div class="gcb-section">
          <h4>Contribution Rate</h4>
          <div class="gcb-table-wrap">
            <table class="gcb-table">
              <thead>
                <tr><th>Description</th><th>Value</th></tr>
              </thead>
              <tbody>
                <tr><td>Employee Contribution</td><td>1% – 2%</td></tr>
                <tr><td>Employer Contribution</td><td>2% – 3%</td></tr>
                <tr><td>Maximum Contribution Base</td><td>₱5,000.00</td></tr>
                <tr><td>Minimum Contribution</td><td>₱100.00</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="gcb-section">
          <h4>Salary Brackets</h4>
          <div class="gcb-table-wrap">
            <table class="gcb-table">
              <thead>
                <tr><th>Monthly Basic Salary</th><th>Employee (1%)</th><th>Employer (2%)</th><th>Total</th></tr>
              </thead>
              <tbody>
                <tr><td>₱1,000 – ₱1,499</td><td>₱10.00</td><td>₱20.00</td><td>₱30.00</td></tr>
                <tr><td>₱1,500 – ₱1,999</td><td>₱15.00</td><td>₱30.00</td><td>₱45.00</td></tr>
                <tr><td>₱2,000 – ₱2,499</td><td>₱20.00</td><td>₱40.00</td><td>₱60.00</td></tr>
                <tr><td>₱2,500 – ₱2,999</td><td>₱25.00</td><td>₱50.00</td><td>₱75.00</td></tr>
                <tr><td>₱3,000 – ₱3,499</td><td>₱30.00</td><td>₱60.00</td><td>₱90.00</td></tr>
                <tr><td>₱3,500 – ₱3,999</td><td>₱35.00</td><td>₱70.00</td><td>₱105.00</td></tr>
                <tr><td>₱4,000 – ₱4,499</td><td>₱40.00</td><td>₱80.00</td><td>₱120.00</td></tr>
                <tr><td>₱4,500 – ₱4,999</td><td>₱45.00</td><td>₱90.00</td><td>₱135.00</td></tr>
                <tr><td>₱5,000 and above</td><td>₱50.00</td><td>₱100.00</td><td>₱150.00</td></tr>
              </tbody>
            </table>
          </div>
          <p class="gcb-note">Note: Contributions are capped at the maximum contribution base of ₱5,000. Monthly salaries above ₱5,000 still contribute only ₱200 total.</p>
        </div>

        <div class="gcb-section">
          <h4>Sample Computation</h4>
          <div class="gcb-sample">
            <div class="gcb-sample-head">Employee with ₱15,000 Monthly Basic Salary</div>
            <table class="gcb-table gcb-table--compact">
              <tbody>
                <tr><td>Monthly Basic Salary</td><td>₱15,000.00</td></tr>
                <tr><td>Employee Share (1%)</td><td>₱150.00</td></tr>
                <tr><td>Employer Share (2%)</td><td>₱300.00</td></tr>
                <tr><td>Total Contribution</td><td>₱450.00</td></tr>
              </tbody>
            </table>
          </div>
          <div class="gcb-sample">
            <div class="gcb-sample-head">Employee with ₱30,000 Monthly Basic Salary</div>
            <table class="gcb-table gcb-table--compact">
              <tbody>
                <tr><td>Monthly Basic Salary</td><td>₱30,000.00</td></tr>
                <tr><td>Employee Share (1%)</td><td>₱150.00</td></tr>
                <tr><td>Employer Share (2%)</td><td>₱300.00</td></tr>
                <tr><td>Total Contribution</td><td>₱450.00 (capped)</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="gcb-section">
          <h4>Benefits</h4>
          <p class="gcb-text">Pag-IBIG members may avail of:</p>
          <ul class="gcb-list">
            <li>Housing Loans</li>
            <li>Multi-Purpose Loans</li>
            <li>Calamity Loans</li>
            <li>Short-Term Loan Programs</li>
            <li>Provident Fund Savings</li>
          </ul>
        </div>

        <div class="gcb-section">
          <h4>Employer Responsibilities</h4>
          <p class="gcb-text">Employers are required to:</p>
          <ul class="gcb-list">
            <li>Deduct the employee's share from monthly payroll.</li>
            <li>Pay the employer's corresponding share.</li>
            <li>Remit contributions through the Pag-IBIG Fund or authorized agents.</li>
            <li>Submit monthly contribution reports (MCR) and remittance returns.</li>
            <li>Maintain accurate records of contributions and employee membership.</li>
          </ul>
        </div>
      <?php elseif ($type === 'bir'): ?>
        <div class="gcb-section">
          <h4>Overview</h4>
          <p class="gcb-text">The <strong>Bureau of Internal Revenue (BIR)</strong> administers national taxes in the Philippines. Employers withhold income tax from employee compensation through the <strong>Withholding Tax on Compensation</strong> system. This is an advance payment of the employee's annual income tax liability, computed using the applicable BIR withholding tax table.</p>
        </div>

        <div class="gcb-section">
          <h4>Withholding Tax Table (2026)</h4>
          <div class="gcb-table-wrap">
            <table class="gcb-table gcb-table--consolidated">
              <thead>
                <tr>
                  <th>Monthly Compensation</th>
                  <th>SSS</th>
                  <th>PhilHealth</th>
                  <th>Pag-IBIG</th>
                  <th>Taxable Income</th>
                  <th>Withholding Tax</th>
                  <th>Effective Rate</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>₱20,000</td>
                  <td style="text-align:right;">₱1,000.00</td>
                  <td style="text-align:right;">₱500.00</td>
                  <td style="text-align:right;">₱200.00</td>
                  <td style="text-align:right;">₱18,300.00</td>
                  <td style="text-align:right;">₱0.00</td>
                  <td style="text-align:right;">0%</td>
                </tr>
                <tr>
                  <td>₱25,000</td>
                  <td style="text-align:right;">₱1,250.00</td>
                  <td style="text-align:right;">₱625.00</td>
                  <td style="text-align:right;">₱200.00</td>
                  <td style="text-align:right;">₱22,925.00</td>
                  <td style="text-align:right;">₱312.50</td>
                  <td style="text-align:right;">1.25%</td>
                </tr>
                <tr>
                  <td>₱30,000</td>
                  <td style="text-align:right;">₱1,500.00</td>
                  <td style="text-align:right;">₱750.00</td>
                  <td style="text-align:right;">₱200.00</td>
                  <td style="text-align:right;">₱27,550.00</td>
                  <td style="text-align:right;">₱937.50</td>
                  <td style="text-align:right;">3.13%</td>
                </tr>
                <tr>
                  <td>₱40,000</td>
                  <td style="text-align:right;">₱1,750.00*</td>
                  <td style="text-align:right;">₱1,000.00</td>
                  <td style="text-align:right;">₱200.00</td>
                  <td style="text-align:right;">₱36,050.00</td>
                  <td style="text-align:right;">₱2,812.50</td>
                  <td style="text-align:right;">7.03%</td>
                </tr>
                <tr>
                  <td>₱50,000</td>
                  <td style="text-align:right;">₱1,750.00*</td>
                  <td style="text-align:right;">₱1,250.00</td>
                  <td style="text-align:right;">₱200.00</td>
                  <td style="text-align:right;">₱45,050.00</td>
                  <td style="text-align:right;">₱5,812.50</td>
                  <td style="text-align:right;">11.63%</td>
                </tr>
                <tr>
                  <td>₱100,000</td>
                  <td style="text-align:right;">₱1,750.00*</td>
                  <td style="text-align:right;">₱2,500.00</td>
                  <td style="text-align:right;">₱200.00</td>
                  <td style="text-align:right;">₱88,550.00</td>
                  <td style="text-align:right;">₱28,541.80</td>
                  <td style="text-align:right;">28.54%</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p class="gcb-note">* SSS contributions are capped at the maximum MSC of ₱35,000. PhilHealth contributions are capped at the maximum income ceiling of ₱100,000. Pag-IBIG contributions are capped at the maximum contribution base of ₱10,000. BIR tax is computed on taxable income after deducting mandatory contributions. Individual brackets are shown in their respective tabs for the full schedule.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<style>
.gcb-module { padding: 4px 2px 24px; margin-top: 0; }
.gcb-card { background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); border-radius:14px; padding:18px; }
.gcb-card-head { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px; flex-wrap:wrap; }
.gcb-card-head h3 { margin:0; font-size:0.98rem; font-weight:700; color:var(--text-900,#1b2430); display:flex; align-items:center; gap:8px; }
.gcb-desc { font-size:0.82rem; color:var(--text-600,#566072); line-height:1.55; margin:0 0 14px; }
.gcb-section { margin-top:18px; }
.gcb-section h4 { margin:0 0 8px; font-size:0.84rem; font-weight:700; color:var(--text-900,#1b2430); }
.gcb-table-wrap { overflow-x:auto; }
.gcb-table { width:100%; border-collapse:collapse; font-size:0.8rem; }
.gcb-table th { text-align:left; font-size:0.7rem; text-transform:uppercase; letter-spacing:.03em; color:var(--text-400,#8b93a1); padding:8px 10px; border-bottom:1px solid var(--border,#e4e8ee); background:#fff; position:sticky; top:0; }
.gcb-table td { padding:8px 10px; border-bottom:1px solid var(--border,#e4e8ee); }
.gcb-table tr:last-child td { border-bottom:none; }
.gcb-table--compact td { padding:4px 10px; }
.gcb-text { font-size:0.82rem; color:var(--text-600,#566072); line-height:1.55; }
.gcb-list { padding-left:18px; margin:6px 0 0; }
.gcb-list li { font-size:0.82rem; color:var(--text-600,#566072); margin-bottom:4px; line-height:1.5; }
.gcb-formula { background:var(--bg-soft,#f3f5f9); border:1px solid var(--border,#e4e8ee); border-radius:10px; padding:12px 14px; }
.gcb-formula-item { font-size:0.82rem; color:var(--text-700,#3b4252); margin-bottom:4px; line-height:1.5; }
.gcb-formula-item:last-child { margin-bottom:0; }
.gcb-sample { border:1px solid var(--border,#e4e8ee); border-radius:10px; padding:12px; background:#fff; margin-bottom:10px; }
.gcb-sample:last-child { margin-bottom:0; }
.gcb-sample-head { font-size:0.78rem; font-weight:700; color:var(--text-900,#1b2430); margin-bottom:8px; }
.gcb-note { font-size:0.76rem; color:var(--text-500,#6b7280); margin-top:6px; line-height:1.5; }
.gcb-table--consolidated th { font-size:0.68rem; white-space:nowrap; }
.gcb-table--consolidated td { font-size:0.78rem; white-space:nowrap; }
.gcb-table--consolidated tbody tr:hover { background:rgba(59,130,196,.03); }
</style>

