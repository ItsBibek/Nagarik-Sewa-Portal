<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['citizen', 'officer', 'admin'], true);
    }

    public function view(User $user, Application $application): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'citizen' && (int) $application->user_id === (int) $user->id)
            || $this->isInDepartment($user, $application);
    }

    public function create(User $user): bool
    {
        return $user->role === 'citizen';
    }

    public function update(User $user, Application $application): bool
    {
        return $user->role === 'admin'
            || ($this->isInDepartment($user, $application)
                && ($application->current_officer_id === null
                    || (int) $application->current_officer_id === (int) $user->id));
    }

    public function resubmit(User $user, Application $application): bool
    {
        return $user->role === 'citizen'
            && (int) $application->user_id === (int) $user->id
            && $application->status === 'correction_required';
    }

    public function delete(User $user, Application $application): bool
    {
        return $user->role === 'admin';
    }

    private function isInDepartment(User $user, Application $application): bool
    {
        if ($user->role !== 'officer' || $user->department_id === null) {
            return false;
        }

        $serviceType = $application->serviceType()
            ->where('is_active', true)
            ->first(['department_id']);

        return $serviceType !== null
            && (int) $serviceType->department_id === (int) $user->department_id;
    }
}
