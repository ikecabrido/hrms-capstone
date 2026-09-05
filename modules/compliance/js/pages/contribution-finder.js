/**
 * Contribution Finder — SSS / PhilHealth / Pag-IBIG / BIR
 *
 * Usage:
 *   <script>
 *     window.CONTRIBUTION_FINDER = window.CONTRIBUTION_FINDER || {};
 *     CONTRIBUTION_FINDER.sss = { brackets: <?= $sssBracketsJson ?>, agency: 'sss' };
 *     CONTRIBUTION_FINDER.philhealth = { brackets: <?= $phBracketsJson ?>, agency: 'philhealth' };
 *     CONTRIBUTION_FINDER.pagibig = { brackets: [], agency: 'pagibig' };
 *     CONTRIBUTION_FINDER.bir = { brackets: <?= $birBracketsJson ?>, agency: 'bir' };
 *   </script>
 *   <script src="../js/pages/contribution-finder.js" defer></script>
 */

(function() {
  'use strict';

  if (!window.CONTRIBUTION_FINDER) {
    console.warn('ContributionFinder: no agency data found in window.CONTRIBUTION_FINDER');
    return;
  }

  var formatter = function(n) {
    return '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  };

  var isEmpty = function(val) {
    return val === null || val === undefined || val === '' || (Array.isArray(val) && val.length === 0);
  };

  var safeNumber = function(val, fallback) {
    var n = parseFloat(val);
    return isNaN(n) ? (fallback || 0) : n;
  };

  var findSSSBracket = function(salary, brackets) {
    for (var i = 0; i < brackets.length; i++) {
      var b = brackets[i];
      var min = safeNumber(b.min_compensation);
      var max = b.max_compensation !== null && b.max_compensation !== '' ? safeNumber(b.max_compensation) : null;
      if (salary >= min && (max === null || salary <= max)) {
        return b;
      }
    }
    return null;
  };

  var findPhilHealthBracket = function(salary, brackets) {
    for (var i = 0; i < brackets.length; i++) {
      var b = brackets[i];
      var min = safeNumber(b.min_compensation);
      var max = b.max_compensation !== null && b.max_compensation !== '' ? safeNumber(b.max_compensation) : null;
      if (salary >= min && (max === null || salary <= max)) {
        return b;
      }
    }
    return null;
  };

  var findPagibigBracket = function(salary) {
    return { salary: salary };
  };

  var findBIRBracket = function(monthlySalary, brackets) {
    for (var i = 0; i < brackets.length; i++) {
      var b = brackets[i];
      var min = safeNumber(b.min_monthly_income);
      var max = b.max_monthly_income !== null && b.max_monthly_income !== '' ? safeNumber(b.max_monthly_income) : null;
      if (monthlySalary >= min && (max === null || monthlySalary <= max)) {
        return b;
      }
    }
    return null;
  };

  var initSSS = function(data) {
    var salaryInput = document.getElementById('sssSalaryInput');
    var resultBox = document.getElementById('sssFinderResult');
    var emptyBox = document.getElementById('sssFinderEmpty');
    var bracketLabel = document.getElementById('sssFinderBracket');
    var eeLabel = document.getElementById('sssFinderEE');
    var erLabel = document.getElementById('sssFinderER');
    var ecLabel = document.getElementById('sssFinderEC');
    var totalLabel = document.getElementById('sssFinderTotal');

    if (!salaryInput) return;

    var ecFixed = 30.00;

    function update() {
      var raw = salaryInput.value.replace(/[^0-9]/g, '');
      if (raw === '') {
        if (resultBox) resultBox.style.display = 'none';
        if (emptyBox) emptyBox.style.display = 'flex';
        return;
      }

      var salary = safeNumber(raw);
      if (salary <= 0) {
        if (resultBox) resultBox.style.display = 'none';
        if (emptyBox) emptyBox.style.display = 'flex';
        return;
      }

      var b = findSSSBracket(salary, data.brackets || []);
      if (!b) {
        if (resultBox) resultBox.style.display = 'none';
        if (emptyBox) emptyBox.style.display = 'flex';
        return;
      }

      var min = safeNumber(b.min_compensation);
      var max = b.max_compensation !== null && b.max_compensation !== '' ? safeNumber(b.max_compensation) : null;
      var rangeTxt = max !== null
        ? '₱' + min.toLocaleString('en-PH') + ' – ₱' + max.toLocaleString('en-PH')
        : '₱' + min.toLocaleString('en-PH') + ' and above';

      var ee = safeNumber(b.employee_share);
      var er = safeNumber(b.employer_share);
      var total = ee + er + ecFixed;

      if (bracketLabel) bracketLabel.textContent = rangeTxt;
      if (eeLabel) eeLabel.textContent = formatter(ee);
      if (erLabel) erLabel.textContent = formatter(er);
      if (ecLabel) ecLabel.textContent = formatter(ecFixed);
      if (totalLabel) totalLabel.textContent = formatter(total);

      if (resultBox) resultBox.style.display = 'block';
      if (emptyBox) emptyBox.style.display = 'none';
    }

    salaryInput.addEventListener('input', update);
    salaryInput.addEventListener('change', update);
  };

  var initPhilHealth = function(data) {
    var salaryInput = document.getElementById('philhealthSalaryInput');
    var resultBox = document.getElementById('philhealthFinderResult');
    var emptyBox = document.getElementById('philhealthFinderEmpty');
    var bracketLabel = document.getElementById('philhealthFinderBracket');
    var eeLabel = document.getElementById('philhealthFinderEE');
    var erLabel = document.getElementById('philhealthFinderER');
    var totalLabel = document.getElementById('philhealthFinderTotal');

    if (!salaryInput) return;

    function update() {
      var raw = salaryInput.value.replace(/[^0-9]/g, '');
      if (raw === '') {
        if (resultBox) resultBox.style.display = 'none';
        if (emptyBox) emptyBox.style.display = 'flex';
        return;
      }

      var salary = safeNumber(raw);
      if (salary <= 0) {
        if (resultBox) resultBox.style.display = 'none';
        if (emptyBox) emptyBox.style.display = 'flex';
        return;
      }

      var b = findPhilHealthBracket(salary, data.brackets || []);
      if (!b) {
        if (resultBox) resultBox.style.display = 'none';
        if (emptyBox) emptyBox.style.display = 'flex';
        return;
      }

      var min = safeNumber(b.min_compensation);
      var max = b.max_compensation !== null && b.max_compensation !== '' ? safeNumber(b.max_compensation) : null;
      var rangeTxt = max !== null
        ? '₱' + min.toLocaleString('en-PH') + ' – ₱' + max.toLocaleString('en-PH')
        : '₱' + min.toLocaleString('en-PH') + ' and above';

      var ee = salary * (safeNumber(b.employee_rate) / 100);
      var er = salary * (safeNumber(b.employer_rate) / 100);
      var total = ee + er;

      if (bracketLabel) bracketLabel.textContent = rangeTxt;
      if (eeLabel) eeLabel.textContent = formatter(ee);
      if (erLabel) erLabel.textContent = formatter(er);
      if (totalLabel) totalLabel.textContent = formatter(total);

      if (resultBox) resultBox.style.display = 'block';
      if (emptyBox) emptyBox.style.display = 'none';
    }

    salaryInput.addEventListener('input', update);
    salaryInput.addEventListener('change', update);
  };

  var initPagibig = function(data) {
    var salaryInput = document.getElementById('pagibigSalaryInput');
    var resultBox = document.getElementById('pagibigFinderResult');
    var emptyBox = document.getElementById('pagibigFinderEmpty');
    var eRateLabel = document.getElementById('pagibigFinderERate');
    var rRateLabel = document.getElementById('pagibigFinderRRate');
    var eeLabel = document.getElementById('pagibigFinderEE');
    var erLabel = document.getElementById('pagibigFinderER');
    var totalLabel = document.getElementById('pagibigFinderTotal');

    if (!salaryInput) return;

    function update() {
      var raw = salaryInput.value.replace(/[^0-9]/g, '');
      if (raw === '') {
        if (resultBox) resultBox.style.display = 'none';
        if (emptyBox) emptyBox.style.display = 'flex';
        return;
      }

      var salary = safeNumber(raw);
      if (salary <= 0) {
        if (resultBox) resultBox.style.display = 'none';
        if (emptyBox) emptyBox.style.display = 'flex';
        return;
      }

      var capped = Math.min(salary, 5000);
      var ee = capped * 0.01;
      var er = capped * 0.02;
      var total = capped * 0.03;
      var note = salary > 5000 ? ' (capped at ₱5,000 base)' : '';

      if (eRateLabel) eRateLabel.textContent = '1%';
      if (rRateLabel) rRateLabel.textContent = '2%';
      if (eeLabel) eeLabel.textContent = formatter(ee);
      if (erLabel) erLabel.textContent = formatter(er);
      if (totalLabel) totalLabel.textContent = formatter(total) + note;

      if (resultBox) resultBox.style.display = 'block';
      if (emptyBox) emptyBox.style.display = 'none';
    }

    salaryInput.addEventListener('input', update);
    salaryInput.addEventListener('change', update);
  };

  var initBIR = function(data) {
    var salaryInput = document.getElementById('birSalaryInput');
    var resultBox = document.getElementById('birFinderResult');
    var emptyBox = document.getElementById('birFinderEmpty');
    var statusLabel = document.getElementById('birFinderStatus');
    var rangeLabel = document.getElementById('birFinderRange');
    var baseLabel = document.getElementById('birFinderBase');
    var rateLabel = document.getElementById('birFinderRate');
    var totalLabel = document.getElementById('birFinderTotal');

    if (!salaryInput) return;

    function update() {
      var raw = salaryInput.value.replace(/[^0-9]/g, '');
      if (raw === '') {
        if (resultBox) resultBox.style.display = 'none';
        if (emptyBox) emptyBox.style.display = 'flex';
        return;
      }

      var salary = safeNumber(raw);
      if (salary < 0) {
        if (resultBox) resultBox.style.display = 'none';
        if (emptyBox) emptyBox.style.display = 'flex';
        return;
      }

      if (salary === 0) {
        if (statusLabel) statusLabel.textContent = 'No Compensation';
        if (rangeLabel) rangeLabel.textContent = '—';
        if (baseLabel) baseLabel.textContent = formatter(0);
        if (rateLabel) rateLabel.textContent = '0%';
        if (totalLabel) totalLabel.textContent = formatter(0);
        if (resultBox) resultBox.style.display = 'block';
        if (emptyBox) emptyBox.style.display = 'none';
        return;
      }

      var b = findBIRBracket(salary, data.brackets || []);
      if (!b) {
        if (resultBox) resultBox.style.display = 'none';
        if (emptyBox) emptyBox.style.display = 'flex';
        return;
      }

      var min = safeNumber(b.min_monthly_income);
      var max = b.max_monthly_income !== null && b.max_monthly_income !== '' ? safeNumber(b.max_monthly_income) : null;
      var rangeTxt = max !== null
        ? formatter(min) + ' – ' + formatter(max)
        : formatter(min) + ' and above';

      var base = safeNumber(b.fixed_tax);
      var rate = safeNumber(b.tax_rate);
      var excess = Math.max(0, salary - safeNumber(b.excess_over));
      var totalTax = base + (excess * rate / 100);
      totalTax = Math.max(0, totalTax);

      if (statusLabel) statusLabel.textContent = totalTax > 0 ? 'Withholding Tax Applicable' : 'No Withholding Tax';
      if (rangeLabel) rangeLabel.textContent = rangeTxt;
      if (baseLabel) baseLabel.textContent = formatter(base);
      if (rateLabel) rateLabel.textContent = Number(rate).toFixed(2) + '%';
      if (totalLabel) totalLabel.textContent = formatter(totalTax);

      if (resultBox) resultBox.style.display = 'block';
      if (emptyBox) emptyBox.style.display = 'none';
    }

    salaryInput.addEventListener('input', update);
    salaryInput.addEventListener('change', update);
  };

  var init = function() {
    if (CONTRIBUTION_FINDER.sss) initSSS(CONTRIBUTION_FINDER.sss);
    if (CONTRIBUTION_FINDER.philhealth) initPhilHealth(CONTRIBUTION_FINDER.philhealth);
    if (CONTRIBUTION_FINDER.pagibig) initPagibig(CONTRIBUTION_FINDER.pagibig);
    if (CONTRIBUTION_FINDER.bir) initBIR(CONTRIBUTION_FINDER.bir);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
