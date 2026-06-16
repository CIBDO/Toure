<?php

namespace App\Policies;

use App\Models\Reception;
use App\Models\User;

class ReceptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('RECEPTION_READ') || $user->hasPermission('CONTRATS_READ');
    }

    public function view(User $user, Reception $reception): bool
    {
        return $user->hasPermission('RECEPTION_READ') || $user->hasPermission('CONTRATS_READ');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('RECEPTION_CREATE') || $user->hasPermission('CONTRATS_EDIT');
    }

    public function update(User $user, Reception $reception): bool
    {
        $canEdit = $user->hasPermission('RECEPTION_EDIT') || $user->hasPermission('CONTRATS_EDIT');
        return $canEdit && $reception->isDraft();
    }

    public function delete(User $user, Reception $reception): bool
    {
        $canDelete = $user->hasPermission('RECEPTION_DELETE') || $user->hasPermission('CONTRATS_EDIT');
        return $canDelete && $reception->isDraft();
    }

    public function submit(User $user, Reception $reception): bool
    {
        return ($user->hasPermission('RECEPTION_SUBMIT') || $user->hasPermission('CONTRATS_EDIT')) && $reception->isDraft();
    }

    public function approve(User $user, Reception $reception): bool
    {
        return ($user->hasPermission('RECEPTION_APPROVE') || $user->hasPermission('CONTRATS_APPROVE')) && $reception->isPendingValidation();
    }

    public function reject(User $user, Reception $reception): bool
    {
        return ($user->hasPermission('RECEPTION_APPROVE') || $user->hasPermission('CONTRATS_APPROVE')) && $reception->isPendingValidation();
    }
}
