<?php

namespace App\Policies;

use App\Models\Avenant;
use App\Models\User;

class AvenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('AVENANTS_READ') || $user->hasPermission('CONTRATS_READ');
    }

    public function view(User $user, Avenant $avenant): bool
    {
        return $user->hasPermission('AVENANTS_READ') || $user->hasPermission('CONTRATS_READ');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('AVENANTS_CREATE') || $user->hasPermission('CONTRATS_EDIT');
    }

    public function update(User $user, Avenant $avenant): bool
    {
        $canEdit = $user->hasPermission('AVENANTS_EDIT') || $user->hasPermission('CONTRATS_EDIT');
        return $canEdit && $avenant->isDraft();
    }

    public function delete(User $user, Avenant $avenant): bool
    {
        $canDelete = $user->hasPermission('AVENANTS_DELETE') || $user->hasPermission('CONTRATS_EDIT');
        return $canDelete && $avenant->isDraft();
    }

    public function submit(User $user, Avenant $avenant): bool
    {
        return ($user->hasPermission('AVENANTS_SUBMIT') || $user->hasPermission('CONTRATS_SUBMIT')) && $avenant->isDraft();
    }

    public function approve(User $user, Avenant $avenant): bool
    {
        return ($user->hasPermission('AVENANTS_APPROVE') || $user->hasPermission('CONTRATS_APPROVE')) && $avenant->isPendingValidation();
    }

    public function reject(User $user, Avenant $avenant): bool
    {
        return ($user->hasPermission('AVENANTS_APPROVE') || $user->hasPermission('CONTRATS_APPROVE')) && $avenant->isPendingValidation();
    }
}
