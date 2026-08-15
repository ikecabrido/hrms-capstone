document.addEventListener('DOMContentLoaded', function () {

    const description = document.getElementById('grievanceDescription');
    const subject = document.getElementById('grievanceSubject');

    if (!description || !subject) {
        console.error('Grievance fields not found.');
        return;
    }

    function generateSubject(text) {

        text = text.toLowerCase().trim();

        if (!text) {
            return '';
        }

        // Harassment
        if (
            text.includes('harass') ||
            text.includes('bully') ||
            text.includes('bullying') ||
            text.includes('threat') ||
            text.includes('intimidat')
        ) {
            return 'Workplace Harassment Concern';
        }

        // Discrimination
        if (
            text.includes('discriminat') ||
            text.includes('unfair treatment') ||
            text.includes('unfairly treated')
        ) {
            return 'Workplace Discrimination Concern';
        }

        // Workplace Conflict
        if (
            text.includes('conflict') ||
            text.includes('argument') ||
            text.includes('dispute') ||
            text.includes('fight') ||
            text.includes('coworker') ||
            text.includes('co-worker')
        ) {
            return 'Workplace Conflict';
        }

        // Management
        if (
            text.includes('supervisor') ||
            text.includes('manager') ||
            text.includes('management') ||
            text.includes('boss')
        ) {
            return 'Management Concern';
        }

        // Workplace Safety
        if (
            text.includes('safety') ||
            text.includes('unsafe') ||
            text.includes('accident') ||
            text.includes('hazard')
        ) {
            return 'Workplace Safety Concern';
        }

        // Payroll
        if (
            text.includes('salary') ||
            text.includes('payroll') ||
            text.includes('wage') ||
            text.includes('deduction') ||
            text.includes('payslip')
        ) {
            return 'Payroll and Compensation Concern';
        }

        // Leave
        if (
            text.includes('leave') ||
            text.includes('vacation') ||
            text.includes('sick leave')
        ) {
            return 'Leave Request Concern';
        }

        // Attendance
        if (
            text.includes('attendance') ||
            text.includes('absent') ||
            text.includes('absence') ||
            text.includes('late') ||
            text.includes('tardy')
        ) {
            return 'Attendance Concern';
        }

        // Schedule
        if (
            text.includes('schedule') ||
            text.includes('shift') ||
            text.includes('working hours')
        ) {
            return 'Work Schedule Concern';
        }

        // Benefits
        if (
            text.includes('benefit') ||
            text.includes('insurance') ||
            text.includes('contribution') ||
            text.includes('sss') ||
            text.includes('philhealth') ||
            text.includes('pag-ibig')
        ) {
            return 'Employee Benefits Concern';
        }

        // Policy violation
        if (
            text.includes('policy') ||
            text.includes('rule violation') ||
            text.includes('violation')
        ) {
            return 'Policy Violation Concern';
        }

        // Default
        return 'Other Workplace Concern';
    }


    /*
    |--------------------------------------------------------------------------
    | REAL-TIME SUBJECT GENERATION
    |--------------------------------------------------------------------------
    */

    description.addEventListener('input', function () {

        const text = this.value.trim();

        if (text.length === 0) {
            subject.value = '';
            return;
        }

        const generatedSubject = generateSubject(text);

        subject.value = generatedSubject;

    });

});
document.addEventListener('DOMContentLoaded', function () {

    const display = document.getElementById('incidentDateDisplay');
    const dateInput = document.getElementById('incidentDate');

    if (!display || !dateInput) {
        return;
    }

    // Set today's date initially
    const today = new Date();

    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');

    dateInput.value = `${year}-${month}-${day}`;

    updateDisplay();

    // Open date picker when clicking readable date
    display.addEventListener('click', function () {

        if (typeof dateInput.showPicker === 'function') {
            dateInput.showPicker();
        } else {
            dateInput.click();
        }

    });

    // Update readable date after selecting
    dateInput.addEventListener('change', function () {
        updateDisplay();
    });

    function updateDisplay() {

        if (!dateInput.value) {
            display.value = '';
            return;
        }

        const date = new Date(dateInput.value + 'T00:00:00');

        display.value = date.toLocaleDateString('en-US', {
            month: 'long',
            day: 'numeric',
            year: 'numeric'
        });
    }

});