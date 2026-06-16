<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Models\AuditLog;
use App\Notifications\UserAccountCreatedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Contrôleur API pour la gestion des utilisateurs IAM
 * 
 * Ce contrôleur gère :
 * - CRUD des utilisateurs
 * - Gestion des rôles (assignation, retrait)
 * - Gestion du statut (ACTIF, SUSPENDU, DESACTIVE)
 */
class UserController extends Controller
{
    /**
     * Liste tous les utilisateurs avec pagination et filtres
     * GET /api/users
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with('roles'); // Charger les rôles pour éviter les requêtes N+1

        // Recherche par nom, prénom ou email
        if ($request->has('q') && $request->q) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filtre par statut
        if ($request->has('statut') && $request->statut) {
            $query->where('statut', $request->statut);
        }

        // Filtre par type de compte
        if ($request->has('type_compte') && $request->type_compte) {
            $query->where('type_compte', $request->type_compte);
        }

        // Filtre par rôle
        if ($request->has('role') && $request->role) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('code', $request->role);
            });
        }

        // Tri
        $sortBy = $request->get('sortBy', 'id');
        $orderBy = $request->get('orderBy', 'desc');
        $query->orderBy($sortBy, $orderBy);

        // Pagination
        $perPage = $request->get('itemsPerPage', 10);
        $page = $request->get('page', 1);

        if ($perPage == -1) {
            $users = $query->get();
            $totalUsers = $users->count();
        } else {
            $users = $query->paginate($perPage, ['*'], 'page', $page);
            $totalUsers = $users->total();
        }

        // Transformer les données pour l'API
        $transformedUsers = $users->map(function ($user) {
            return $this->transformUser($user);
        });

        return response()->json([
            'users' => $transformedUsers,
            'totalUsers' => $totalUsers,
        ]);
    }

    /**
     * Créer un nouvel utilisateur
     * POST /api/users
     */
    public function store(UserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $isTemporaryPassword = !isset($validated['password']) || !$validated['password'];
        $temporaryPassword   = $isTemporaryPassword ? 'Canam@2026' : null;

        $user = User::create([
            'name'                 => trim(($validated['prenom'] ?? '') . ' ' . ($validated['nom'] ?? '')),
            'nom'                  => $validated['nom'],
            'prenom'               => $validated['prenom'],
            'email'                => $validated['email'],
            'telephone'            => $validated['telephone'] ?? null,
            'fonction'             => $validated['fonction'] ?? null,
            'unite_service'        => $validated['unite_service'] ?? null,
            'region'               => $validated['region'] ?? null,
            'password'             => bcrypt($validated['password'] ?? $temporaryPassword),
            'statut'               => $validated['statut'] ?? 'ACTIF',
            'type_compte'          => $validated['type_compte'] ?? 'CANAM',
            'must_change_password' => $isTemporaryPassword, // Forcer le changement si mot de passe temporaire
        ]);

        if (!empty($validated['roles'])) {
            $user->roles()->sync($validated['roles']);
        }

        try {
            $user->notify(new UserAccountCreatedNotification($temporaryPassword ?? $validated['password'], $user->statut));
        } catch (\Exception $e) {
            Log::error('Erreur notification création utilisateur : ' . $e->getMessage());
        }

        AuditLog::logAction('create', 'users', $user->id, null, ['email' => $user->email, 'nom' => $user->nom]);

        return response()->json($this->transformUser($user->load('roles')), 201);
    }

    /**
     * Afficher un utilisateur spécifique
     * GET /api/users/{id}
     */
    public function show(string $id): JsonResponse
    {
        $user = User::with('roles', 'roles.permissions')->find($id);

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non trouvé',
            ], 404);
        }

        return response()->json($this->transformUser($user, true));
    }

    /**
     * Mettre à jour un utilisateur
     * PUT /api/users/{id}
     */
    public function update(UserRequest $request, string $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non trouvé',
            ], 404);
        }

        $old = $user->toArray();
        $validated = $request->validated();

        $userData = [];
        foreach (['nom', 'prenom', 'email', 'telephone', 'fonction', 'unite_service', 'region', 'avatar', 'statut', 'type_compte'] as $field) {
            if (array_key_exists($field, $validated)) {
                $userData[$field] = $validated[$field];
            }
        }

        if (!empty($validated['password'])) {
            $userData['password'] = bcrypt($validated['password']);
        }

        if (isset($userData['nom']) || isset($userData['prenom'])) {
            $userData['name'] = trim(($userData['prenom'] ?? $user->prenom) . ' ' . ($userData['nom'] ?? $user->nom));
        }

        $user->update($userData);

        if (array_key_exists('roles', $validated)) {
            $user->roles()->sync($validated['roles'] ?? []);
        }

        AuditLog::logAction('update', 'users', $user->id, $old, $user->fresh()->toArray());

        return response()->json($this->transformUser($user->load('roles')));
    }

    /**
     * Supprimer un utilisateur
     * DELETE /api/users/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non trouvé',
            ], 404);
        }

        AuditLog::logAction('delete', 'users', $user->id, $user->toArray(), null);
        $user->delete();

        return response()->json([
            'message' => 'Utilisateur supprimé avec succès',
        ]);
    }

    /**
     * Assigner un rôle à un utilisateur
     * POST /api/users/{id}/roles
     */
    public function assignRole(Request $request, string $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $user->roles()->syncWithoutDetaching([$validated['role_id']]);

        AuditLog::log('ASSIGN_ROLE', 'User', $user->id, [
            'role_id' => $validated['role_id'],
        ]);

        return response()->json([
            'message' => 'Rôle assigné avec succès',
            'user' => $this->transformUser($user->load('roles')),
        ]);
    }

    /**
     * Retirer un rôle d'un utilisateur
     * DELETE /api/users/{id}/roles/{roleId}
     */
    public function revokeRole(string $id, string $roleId): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        $user->roles()->detach($roleId);

        AuditLog::log('REVOKE_ROLE', 'User', $user->id, [
            'role_id' => $roleId,
        ]);

        return response()->json([
            'message' => 'Rôle retiré avec succès',
            'user' => $this->transformUser($user->load('roles')),
        ]);
    }

    /**
     * Activer un compte utilisateur
     * POST /api/users/{id}/activate
     */
    public function activate(string $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non trouvé',
            ], 404);
        }

        $ancienStatut = $user->statut;
        $user->activate();

        // Log d'audit
        AuditLog::logChange(
            'ACTIVATE_ACCOUNT',
            'App\\Models\\User',
            $user->id,
            ['statut' => $ancienStatut],
            ['statut' => $user->statut],
            "Activation du compte utilisateur {$user->email}"
        );

        return response()->json([
            'message' => 'Compte activé avec succès',
            'user' => $this->transformUser($user->load('roles')),
        ]);
    }

    /**
     * Désactiver un compte utilisateur
     * POST /api/users/{id}/deactivate
     */
    public function deactivate(string $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non trouvé',
            ], 404);
        }

        $ancienStatut = $user->statut;
        $user->deactivate();

        // Log d'audit
        AuditLog::logChange(
            'DEACTIVATE_ACCOUNT',
            'App\\Models\\User',
            $user->id,
            ['statut' => $ancienStatut],
            ['statut' => $user->statut],
            "Désactivation du compte utilisateur {$user->email}"
        );

        return response()->json([
            'message' => 'Compte désactivé avec succès',
            'user' => $this->transformUser($user->load('roles')),
        ]);
    }

    /**
     * Suspendre un compte utilisateur
     * POST /api/users/{id}/suspend
     */
    public function suspend(string $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non trouvé',
            ], 404);
        }

        $ancienStatut = $user->statut;
        $user->suspend();

        // Log d'audit
        AuditLog::logChange(
            'SUSPEND_ACCOUNT',
            'App\\Models\\User',
            $user->id,
            ['statut' => $ancienStatut],
            ['statut' => $user->statut],
            "Suspension du compte utilisateur {$user->email}"
        );

        return response()->json([
            'message' => 'Compte suspendu avec succès',
            'user' => $this->transformUser($user->load('roles')),
        ]);
    }

    /**
     * Transformer un utilisateur pour la réponse API
     * 
     * @param User $user
     * @param bool $withPermissions Inclure les permissions dans la réponse
     * @return array
     */
    private function transformUser(User $user, bool $withPermissions = false): array
    {
        $data = [
            'id' => $user->id,
            'nom' => $user->nom,
            'prenom' => $user->prenom,
            'fullName' => $user->full_name, // Accesseur
            'email' => $user->email,
            'telephone' => $user->telephone,
            'fonction' => $user->fonction,
            'unite_service' => $user->unite_service,
            'region' => $user->region,
            'avatar' => $user->avatar,
            'statut' => $user->statut,
            'type_compte' => $user->type_compte,
            'last_login_at' => $user->last_login_at?->toDateTimeString(),
            'created_at' => $user->created_at->toDateTimeString(),
            'roles' => $user->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'code' => $role->code,
                    'libelle' => $role->libelle,
                ];
            }),
        ];

        if ($withPermissions) {
            $data['permissions'] = $user->getAllPermissions()->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'code' => $permission->code,
                    'libelle' => $permission->libelle,
                ];
            });
        }

        return $data;
    }
}
