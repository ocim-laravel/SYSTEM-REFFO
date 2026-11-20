<?php

namespace App\Policies;

use App\Models\Delivery;
use App\Models\DeliveryProof;
use App\Models\User;

/**
 * Otorisasi pekerjaan lapangan (MOD-06).
 * Petugas lapangan hanya mengerjakan jadwal yang ditugaskan kepadanya.
 */
class DeliveryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('deliveries.view');
    }

    public function view(User $user, Delivery $delivery): bool
    {
        return $user->can('deliveries.view');
    }

    public function create(User $user): bool
    {
        return $user->can('deliveries.manage');
    }

    public function assignCrew(User $user, Delivery $delivery): bool
    {
        return $user->can('deliveries.manage');
    }

    public function recordProgress(User $user, Delivery $delivery): bool
    {
        return $this->isFieldCrew($user, $delivery);
    }

    public function attachProof(User $user, Delivery $delivery): bool
    {
        return $this->isFieldCrew($user, $delivery);
    }

    public function complete(User $user, Delivery $delivery): bool
    {
        return $user->can('deliveries.manage');
    }

    public function print(User $user, Delivery $delivery): bool
    {
        return $user->can('deliveries.view');
    }

    public function downloadProof(User $user, DeliveryProof $proof): bool
    {
        $delivery = $proof->delivery;

        return $delivery !== null && $this->isFieldCrew($user, $delivery);
    }

    private function isFieldCrew(User $user, Delivery $delivery): bool
    {
        if ($user->can('deliveries.manage')) {
            return true;
        }

        return $user->can('deliveries.field')
            && in_array($user->id, array_map('intval', $delivery->crew ?? []), true);
    }
}
