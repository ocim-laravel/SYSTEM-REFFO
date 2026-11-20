<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ChangeRequest;
use App\Models\DesignFile;
use App\Models\DesignJob;
use App\Models\OrderItem;
use App\Models\User;

/**
 * Otorisasi antrean desain (MOD-03, PH-03, AC-03-1).
 *
 * Sumber kebenaran izin: permission `designs.view` via spatie/laravel-permission.
 * Role yang boleh: Designer, Admin, Admin Produksi (production), Owner.
 */
class DesignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('designs.view');
    }

    public function view(User $user, OrderItem|DesignJob|DesignFile $model): bool
    {
        return $user->can('designs.view');
    }

    public function claim(User $user, OrderItem|DesignJob $model): bool
    {
        return $user->can('designs.claim');
    }

    public function upload(User $user, OrderItem|DesignJob|DesignFile $model): bool
    {
        return $user->can('designs.upload');
    }

    public function revise(User $user, OrderItem|DesignJob $model): bool
    {
        return $user->can('designs.revise');
    }

    public function submit(User $user, OrderItem|DesignJob $model): bool
    {
        return $user->can('designs.upload');
    }

    public function approve(User $user, OrderItem|DesignJob|ChangeRequest $model): bool
    {
        return $user->can('designs.approve');
    }

    public function changeRequest(User $user, OrderItem|DesignJob|ChangeRequest $model): bool
    {
        return $user->can('designs.change_request');
    }

    public function handoff(User $user, OrderItem $model): bool
    {
        return $user->hasRole(UserRole::Admin);
    }
}
