<?php

class LearningProfileLink
{
    public static function publicUrl(int $employeeId): string
    {
        return $employeeId > 0
            ? '?page=public/user-profile&id=' . $employeeId
            : '';
    }

    public static function url(int $employeeId, string $targetRole = 'learner'): string
    {
        if ($employeeId <= 0) {
            return '';
        }

        $viewerRole = $_SESSION['learning_role'] ?? 'learner';
        if ($employeeId === (int) ($_SESSION['employee_id'] ?? 0)) {
            $selfProfilePage = [
                'admin' => 'admin/profile',
                'instructor' => 'instructor/profile',
                'learner' => 'learner/profile',
            ][$viewerRole] ?? 'learner/profile';
            return '?page=' . $selfProfilePage;
        }

        if ($viewerRole === 'admin') {
            if ($targetRole === 'admin') {
                return self::publicUrl($employeeId);
            }
            $profileType = $targetRole === 'learner' ? 'learner' : 'instructor';
            return '?page=admin/user-subpage/' . $profileType . '&id=' . $employeeId;
        }

        if ($viewerRole === 'instructor' && $targetRole === 'learner') {
            return '?page=instructor/learner-profile&id=' . $employeeId;
        }

        return self::publicUrl($employeeId);
    }
}
