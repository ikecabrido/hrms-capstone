<?php

namespace App\Services;

use DateTimeImmutable;

class LeaveAccessValidationService
{
    /**
     * Main validation entry point.
     */
    public function validate(array $employee, array $leaveTypes): array
    {
        $restrictions = $this->getEmploymentRestrictions($employee);

        $isGenerallyEligible = empty($restrictions);

        $eligibleLeaveTypes = [];

        foreach ($leaveTypes as $leaveType) {
            if (
                $isGenerallyEligible ||
                $this->isStatutoryLeaveAllowed($employee, $leaveType)
            ) {
                $eligibleLeaveTypes[] = $leaveType;
            }
        }

        if (empty($eligibleLeaveTypes)) {
            return [
                'can_access' => false,
                'is_generally_eligible' => false,
                'message' => $this->buildRestrictionMessage($restrictions),
                'eligible_leave_types' => [],
                'restrictions' => $restrictions
            ];
        }

        return [
            'can_access' => true,
            'is_generally_eligible' => $isGenerallyEligible,
            'message' => $isGenerallyEligible
                ? 'You are eligible to submit leave requests.'
                : 'Your employment classification limits access to company leave benefits. Applicable statutory leave requests may still be submitted for HR verification.',
            'eligible_leave_types' => $eligibleLeaveTypes,
            'restrictions' => $restrictions
        ];
    }

    /**
     * General employment eligibility rules.
     */
    private function getEmploymentRestrictions(array $employee): array
    {
        $restrictions = [];

        // Check archived employee.
        if ((int) ($employee['is_archived'] ?? 0) === 1) {
            $restrictions[] = 'Your employee record is archived.';
            return $restrictions;
        }

        // Check employment status.
        $employmentStatus = strtolower(
            trim($employee['employment_status'] ?? '')
        );

        if ($employmentStatus !== 'active') {
            $restrictions[] = 'Your employment status is not active.';
        }

        // Check employment type.
        if (!$this->isFullTimeEmployee($employee)) {
            $restrictions[] = 'Only regular full-time employees can access company-provided leave benefits.';
        }

        // Check regularization.
        if (empty($employee['regular_date'])) {
            $restrictions[] = 'Your employment record does not indicate that you have been regularized.';
        } else {
            $regularDate = $this->parseDate($employee['regular_date']);

            if (!$regularDate || $regularDate > new DateTimeImmutable('today')) {
                $restrictions[] = 'Your regularization date is invalid or has not yet taken effect.';
            }
        }

        // Check minimum service duration.
        $hireDate = $this->parseDate($employee['hire_date'] ?? null);

        if (!$hireDate) {
            $restrictions[] = 'Your employment hire date is missing or invalid.';
        } else {
            $minimumEligibilityDate = $hireDate->modify('+1 year');

            if (new DateTimeImmutable('today') < $minimumEligibilityDate) {
                $restrictions[] = 'You must complete at least one year of service before accessing company-provided leave benefits.';
            }

            // Regularization must not precede the hire date.
            if (!empty($employee['regular_date'])) {
                $regularDate = $this->parseDate($employee['regular_date']);

                if ($regularDate && $regularDate < $hireDate) {
                    $restrictions[] = 'Your regularization date cannot be earlier than your hire date.';
                }
            }
        }

        return $restrictions;
    }

    /**
     * Determine whether employee is regular full-time.
     */
    private function isFullTimeEmployee(array $employee): bool
    {
        $employmentType = strtolower(
            trim($employee['employment_type'] ?? '')
        );

        return in_array(
            $employmentType,
            ['full-time', 'full time', 'fulltime'],
            true
        );
    }

    /**
     * Allow applicable statutory leave types even when
     * the employee does not qualify for general company leave.
     *
     * HR must still verify the statutory requirements.
     */
    private function isStatutoryLeaveAllowed(
        array $employee,
        array $leaveType
    ): bool {
        if (
            strtolower(trim($employee['employment_status'] ?? '')) !== 'active' ||
            (int) ($employee['is_archived'] ?? 0) === 1
        ) {
            return false;
        }

        $leaveName = strtolower(
            trim($leaveType['leave_type_name'] ?? '')
        );

        $gender = strtolower(trim($employee['gender'] ?? ''));

        $civilStatus = strtolower(
            trim($employee['civil_status'] ?? '')
        );

        // Maternity Leave: applicable female workers.
        if (
            $leaveName === 'maternity leave' &&
            $gender === 'female'
        ) {
            return true;
        }

        // Paternity Leave: married male employees.
        // HR must verify the other statutory conditions.
        if (
            $leaveName === 'paternity leave' &&
            $gender === 'male' &&
            $civilStatus === 'married'
        ) {
            return true;
        }

        return false;
    }

    /**
     * Safely parse database dates.
     */
    private function parseDate(?string $value): ?DateTimeImmutable
    {
        if (empty($value)) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        if (!$date || $date->format('Y-m-d') !== $value) {
            return null;
        }

        return $date;
    }

    /**
     * Build a readable restriction message.
     */
    private function buildRestrictionMessage(array $restrictions): string
    {
        if (empty($restrictions)) {
            return 'You are currently not eligible to submit leave requests. Please contact HR.';
        }

        return 'You cannot access the leave request form at this time. '
            . implode(' ', $restrictions)
            . ' Please contact the Human Resources Department for assistance.';
    }
}
