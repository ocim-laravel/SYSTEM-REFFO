<?php

namespace App\Policies;

use App\Models\FinishingJob;
use App\Models\Packing;
use App\Models\Rework;
use App\Models\User;

/**
 * Otorisasi finishing / QC / rework / packing (MOD-05).
 */
class FinishingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('finishing.view');
    }

    public function view(User $user, FinishingJob $finishingJob): bool
    {
        return $user->can('finishing.view');
    }

    public function start(User $user): bool
    {
        return $user->can('finishing.progress') || $user->can('finishing.assign');
    }

    public function assign(User $user, FinishingJob $finishingJob): bool
    {
        return $user->can('finishing.assign');
    }

    public function claim(User $user, FinishingJob $finishingJob): bool
    {
        return $user->can('finishing.claim');
    }

    public function recordProgress(User $user, FinishingJob $finishingJob): bool
    {
        return $user->can('finishing.progress');
    }

    /**
     * QC dan penandaan rework menjadi wewenang Admin Produksi / QC / Owner.
     */
    public function recordQc(User $user, FinishingJob $finishingJob): bool
    {
        return $user->can('finishing.qc');
    }

    public function manageRework(User $user, Rework $rework): bool
    {
        return $user->can('finishing.qc') || $user->can('finishing.progress');
    }

    public function pack(User $user, FinishingJob $finishingJob): bool
    {
        return $user->can('finishing.pack');
    }

    public function completePacking(User $user, Packing $packing): bool
    {
        return $user->can('finishing.pack');
    }
}
