<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkCalendar;

/**
 * Otorisasi kalender kerja (MOD-10, AC-10-5).
 *
 * Owner dan HRD dapat melihat dan mengelola kalender kerja (BR-07).
 */
class WorkCalendarPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('calendars.view');
    }

    public function view(User $user, WorkCalendar $workCalendar): bool
    {
        return $user->can('calendars.view');
    }

    public function create(User $user): bool
    {
        return $user->can('calendars.manage');
    }

    public function update(User $user, WorkCalendar $workCalendar): bool
    {
        return $user->can('calendars.manage');
    }

    public function delete(User $user, WorkCalendar $workCalendar): bool
    {
        return $user->can('calendars.manage');
    }
}
