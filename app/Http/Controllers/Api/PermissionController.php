<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Contrôleur API pour la gestion des permissions IAM
 * 
 * Ce contrôleur gère :
 * - CRUD des permissions
 */
class PermissionController extends Controller
{
    /**
     * Liste toutes les permissions
     * GET /api/permissions
     */
    public function index(Request $request): JsonResponse
    {
        $query = Permission::with('roles');

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
            $permissions = $query->get();
            $totalPermissions = $permissions->count();
        } else {
            $permissions = $query->paginate($perPage, ['*'], 'page', $page);
            $totalPermissions = $permissions->total();
        }

        $transformedPermissions = $permissions->map(function ($permission) {
            return $this->transformPermission($permission);
        });

        return response()->json([
            'permissions' => $transformedPermissions,
            'totalPermissions' => $totalPermissions,
        ]);
    }

    /**
     * Créer une nouvelle permission
     * POST /api/permissions
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:255|unique:permissions,code',
                'libelle' => 'required|string|max:255',
            ]);

            $permission = Permission::create([
                'code' => strtoupper($validated['code']), // Toujours en majuscule
                'libelle' => $validated['libelle'],
            ]);

            AuditLog::log('CREATE', 'Permission', $permission->id, [
                'code' => $permission->code,
                'libelle' => $permission->libelle,
            ]);

            return response()->json($this->transformPermission($permission), 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Afficher une permission spécifique
     * GET /api/permissions/{id}
     */
    public function show(string $id): JsonResponse
    {
        $permission = Permission::with('roles')->find($id);

        if (!$permission) {
            return response()->json([
                'message' => 'Permission non trouvée',
            ], 404);
        }

        return response()->json($this->transformPermission($permission, true));
    }

    /**
     * Mettre à jour une permission
     * PUT /api/permissions/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json([
                'message' => 'Permission non trouvée',
            ], 404);
        }

        try {
            $validated = $request->validate([
                'code' => 'sometimes|string|max:255|unique:permissions,code,' . $id,
                'libelle' => 'sometimes|string|max:255',
            ]);

            $permissionData = [];
            if (isset($validated['code'])) {
                $permissionData['code'] = strtoupper($validated['code']);
            }
            if (isset($validated['libelle'])) {
                $permissionData['libelle'] = $validated['libelle'];
            }

            $permission->update($permissionData);

            AuditLog::log('UPDATE', 'Permission', $permission->id, [
                'code' => $permission->code,
                'changements' => array_keys($permissionData),
            ]);

            return response()->json($this->transformPermission($permission));
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Supprimer une permission
     * DELETE /api/permissions/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json([
                'message' => 'Permission non trouvée',
            ], 404);
        }

        AuditLog::log('DELETE', 'Permission', $permission->id, [
            'code' => $permission->code,
        ]);

        $permission->delete();

        return response()->json([
            'message' => 'Permission supprimée avec succès',
        ]);
    }

    /**
     * Transformer une permission pour la réponse API
     * 
     * @param Permission $permission
     * @param bool $withRoles Inclure les rôles dans la réponse
     * @return array
     */
    private function transformPermission(Permission $permission, bool $withRoles = false): array
    {
        $data = [
            'id' => $permission->id,
            'code' => $permission->code,
            'libelle' => $permission->libelle,
            'created_at' => $permission->created_at->toDateTimeString(),
        ];

        if ($withRoles) {
            $data['roles'] = $permission->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'code' => $role->code,
                    'libelle' => $role->libelle,
                ];
            });
        }

        return $data;
    }
}