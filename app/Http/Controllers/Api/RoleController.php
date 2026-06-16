<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use App\Models\Role;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Contrôleur API pour la gestion des rôles IAM
 * 
 * Ce contrôleur gère :
 * - CRUD des rôles
 * - Gestion des permissions d'un rôle (assignation, retrait)
 */
class RoleController extends Controller
{
    /**
     * Liste tous les rôles
     * GET /api/roles
     */
    public function index(Request $request): JsonResponse
    {
        $query = Role::with('permissions');

        // Recherche
        if ($request->has('q') && $request->q) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('libelle', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->get('itemsPerPage', 10);
        $page = $request->get('page', 1);

        if ($perPage == -1) {
            $roles = $query->get();
            $totalRoles = $roles->count();
        } else {
            $roles = $query->paginate($perPage, ['*'], 'page', $page);
            $totalRoles = $roles->total();
        }

        $transformedRoles = $roles->map(function ($role) {
            return $this->transformRole($role);
        });

        return response()->json([
            'roles' => $transformedRoles,
            'totalRoles' => $totalRoles,
        ]);
    }

    /**
     * Créer un nouveau rôle
     * POST /api/roles
     */
    public function store(RoleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $role = Role::create([
            'code'    => strtoupper($validated['code']),
            'libelle' => $validated['libelle'],
        ]);

        if (!empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        AuditLog::logAction('create', 'roles', $role->id, null, ['code' => $role->code, 'libelle' => $role->libelle]);

        return response()->json($this->transformRole($role->load('permissions')), 201);
    }

    /**
     * Afficher un rôle spécifique
     * GET /api/roles/{id}
     */
    public function show(string $id): JsonResponse
    {
        $role = Role::with('permissions', 'users')->find($id);

        if (!$role) {
            return response()->json([
                'message' => 'Rôle non trouvé',
            ], 404);
        }

        return response()->json($this->transformRole($role, true));
    }

    /**
     * Mettre à jour un rôle
     * PUT /api/roles/{id}
     */
    public function update(RoleRequest $request, string $id): JsonResponse
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json(['message' => 'Rôle non trouvé'], 404);
        }

        $old = $role->toArray();
        $validated = $request->validated();

        $roleData = [];
        if (isset($validated['code']))    $roleData['code']    = strtoupper($validated['code']);
        if (isset($validated['libelle'])) $roleData['libelle'] = $validated['libelle'];

        $role->update($roleData);

        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        AuditLog::logAction('update', 'roles', $role->id, $old, $role->fresh()->toArray());

        return response()->json($this->transformRole($role->load('permissions')));
    }

    /**
     * Supprimer un rôle
     * DELETE /api/roles/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'message' => 'Rôle non trouvé',
            ], 404);
        }

        AuditLog::log('DELETE', 'Role', $role->id, [
            'code' => $role->code,
        ]);

        $role->delete();

        return response()->json([
            'message' => 'Rôle supprimé avec succès',
        ]);
    }

    /**
     * Assigner une permission à un rôle
     * POST /api/roles/{id}/permissions
     */
    public function assignPermission(Request $request, string $id): JsonResponse
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json(['message' => 'Rôle non trouvé'], 404);
        }

        $validated = $request->validate([
            'permission_id' => 'required|exists:permissions,id',
        ]);

        $role->permissions()->syncWithoutDetaching([$validated['permission_id']]);

        AuditLog::log('ASSIGN_PERMISSION', 'Role', $role->id, [
            'permission_id' => $validated['permission_id'],
        ]);

        return response()->json([
            'message' => 'Permission assignée avec succès',
            'role' => $this->transformRole($role->load('permissions')),
        ]);
    }

    /**
     * Retirer une permission d'un rôle
     * DELETE /api/roles/{id}/permissions/{permissionId}
     */
    public function revokePermission(string $id, string $permissionId): JsonResponse
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json(['message' => 'Rôle non trouvé'], 404);
        }

        $role->permissions()->detach($permissionId);

        AuditLog::log('REVOKE_PERMISSION', 'Role', $role->id, [
            'permission_id' => $permissionId,
        ]);

        return response()->json([
            'message' => 'Permission retirée avec succès',
            'role' => $this->transformRole($role->load('permissions')),
        ]);
    }

    /**
     * Transformer un rôle pour la réponse API
     * 
     * @param Role $role
     * @param bool $withUsers Inclure les utilisateurs dans la réponse
     * @return array
     */
    private function transformRole(Role $role, bool $withUsers = false): array
    {
        $data = [
            'id' => $role->id,
            'code' => $role->code,
            'libelle' => $role->libelle,
            'created_at' => $role->created_at->toDateTimeString(),
            'permissions' => $role->permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'code' => $permission->code,
                    'libelle' => $permission->libelle,
                ];
            }),
        ];

        if ($withUsers) {
            $data['users'] = $role->users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'nom' => $user->nom,
                    'prenom' => $user->prenom,
                    'email' => $user->email,
                ];
            });
        }

        return $data;
    }
}
