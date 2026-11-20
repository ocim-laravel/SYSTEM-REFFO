<?php

namespace App\Policies;

use App\Models\PrintJob;
use App\Models\ReprintRequest;
use App\Models\User;

/**
 * Otorisasi produksi/cetak (MOD-04).
 */
class ProductionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('production.view');
    }

    public function view(User $user, PrintJob $printJob): bool
    {
        return $user->can('production.view');
    }

    public function assign(User $user, PrintJob $printJob): bool
    {
        return $user->can('production.assign');
    }

    public function claim(User $user, PrintJob $printJob): bool
    {
        return $user->can('production.claim');
    }

    public function recordProgress(User $user, PrintJob $printJob): bool
    {
        if ($user->can('production.assign')) {
            return true;
        }

        return $user->can('production.progress') && (int) $printJob->assigned_to === (int) $user->id;
    }

    public function handover(User $user, PrintJob $printJob): bool
    {
        if ($user->can('production.assign')) {
            return true;
        }

        $active = $printJob->relationLoaded('operators')
            ? $printJob->operators->firstWhere('ended_at', null)
            : $printJob->operators()->whereNull('ended_at')->first();

        return $user->can('production.progress') && $active !== null && (int) $active->operator_id === (int) $user->id;
    }

    public function requestReprint(User $user, PrintJob $printJob): bool
    {
        if ($user->can('production.assign')) {
            return true;
        }

        return $user->can('production.reprint.request') && (int) $printJob->assigned_to === (int) $user->id;
    }

    public function viewReprints(User $user): bool
    {
        return $user->can('production.view');
    }

    public function approveReprint(User $user, ReprintRequest $reprintRequest): bool
    {
        return $user->can('production.reprint.approve');
    }
}
