<?php

namespace App\Policies;

use App\Models\OrdreService;
use App\Models\User;

class OrdreServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('OS_READ') || $user->hasPermission('CONTRATS_READ');
    }

    public function view(User $user, OrdreService $ordreService): bool
    {
        return $user->hasPermission('OS_READ') || $user->hasPermission('CONTRATS_READ');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('OS_CREATE') || $user->hasPermission('CONTRATS_EDIT');
    }

    public function update(User $user, OrdreService $ordreService): bool
    {
        $canEdit = $user->hasPermission('OS_EDIT') || $user->hasPermission('CONTRATS_EDIT');
        return $canEdit && $ordreService->isDraft();
    }

    public function delete(User $user, OrdreService $ordreService): bool
    {
        $canDelete = $user->hasPermission('OS_DELETE') || $user->hasPermission('CONTRATS_EDIT');
        return $canDelete && $ordreService->isDraft();
    }

    public function submit(User $user, OrdreService $ordreService): bool
    {
        return ($user->hasPermission('OS_SUBMIT') || $user->hasPermission('CONTRATS_SUBMIT')) && $ordreService->isDraft();
    }

    public function approve(User $user, OrdreService $ordreService): bool
    {
        return ($user->hasPermission('OS_APPROVE') || $user->hasPermission('CONTRATS_APPROVE')) && $ordreService->isPendingValidation();
    }

    public function reject(User $user, OrdreService $ordreService): bool
    {
        return ($user->hasPermission('OS_APPROVE') || $user->hasPermission('CONTRATS_APPROVE')) && $ordreService->isPendingValidation();
    }

    public function execute(User $user, OrdreService $ordreService): bool
    {
        return ($user->hasPermission('OS_EXECUTE') || $user->hasPermission('CONTRATS_EDIT')) && $ordreService->isApproved();
    }
}
