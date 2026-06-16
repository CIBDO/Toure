<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use App\Notifications\PasswordResetNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

/**
 * Contrôleur d'authentification
 * 
 * Gère les opérations d'authentification :
 * - Login (connexion)
 * - Logout (déconnexion)
 * - Me (utilisateur connecté)
 */
class AuthController extends Controller
{
    /**
     * Connexion d'un utilisateur
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            // Trouver l'utilisateur par email
            $user = User::where('email', $validated['email'])->first();

            // Vérifier si le compte est verrouillé (même si l'utilisateur n'existe pas, on ne révèle pas cette info)
            if ($user && $user->isLocked()) {
                $remainingMinutes = now()->diffInMinutes($user->locked_until, false);
                return response()->json([
                    'message' => "Votre compte est verrouillé. Réessayez dans {$remainingMinutes} minute(s).",
                    'errors' => [
                        'email' => ["Votre compte est verrouillé. Réessayez dans {$remainingMinutes} minute(s)."],
                    ],
                ], 423); // 423 Locked
            }

            // Vérifier le mot de passe
            if (!$user || !Hash::check($validated['password'], $user->password)) {
                // Si l'utilisateur existe, incrémenter les tentatives échouées
                if ($user) {
                    // Récupérer les paramètres de sécurité (par défaut : 5 tentatives, 30 minutes)
                    $maxAttempts = \App\Models\SecurityPolicy::getValue('max_failed_login_attempts', 5);
                    $lockoutMinutes = \App\Models\SecurityPolicy::getValue('account_lockout_minutes', 30);

                    $user->incrementFailedLoginAttempts($maxAttempts, $lockoutMinutes);

                    // Si le compte vient d'être verrouillé, informer l'utilisateur
                    $user->refresh();
                    if ($user->isLocked()) {
                        $remainingMinutes = now()->diffInMinutes($user->locked_until, false);
                        return response()->json([
                            'message' => "Trop de tentatives échouées. Votre compte est verrouillé pour {$remainingMinutes} minute(s).",
                            'errors' => [
                                'email' => ["Trop de tentatives échouées. Votre compte est verrouillé pour {$remainingMinutes} minute(s)."],
                            ],
                        ], 423);
                    }
                }

                return response()->json([
                    'message' => 'Les identifiants fournis sont incorrects.',
                    'errors' => [
                        'email' => ['Les identifiants fournis sont incorrects.'],
                    ],
                ], 422);
            }

            // Vérifier que l'utilisateur est actif
            if ($user->statut !== 'ACTIF') {
                return response()->json([
                    'message' => 'Votre compte est suspendu ou désactivé.',
                    'errors' => [
                        'email' => ['Votre compte est suspendu ou désactivé.'],
                    ],
                ], 422);
            }

            // Réinitialiser les tentatives échouées après connexion réussie
            $user->resetFailedLoginAttempts();

            // Détecter si c'est la première connexion (doit changer le mot de passe)
            $isFirstLogin = $user->must_change_password === true;

            // Mettre à jour la date de dernière connexion seulement si ce n'est pas la première connexion
            // (on mettra à jour après le changement de mot de passe obligatoire)
            if (!$isFirstLogin) {
                $user->update(['last_login_at' => now()]);
            }

            // Révoquer les anciens tokens de l'utilisateur (une session à la fois)
            $user->tokens()->delete();

            // Générer un token Sanctum
            $token = $user->createToken('api-token')->plainTextToken;

            // Préparer les données utilisateur pour le frontend
            // Récupérer le premier rôle de l'utilisateur pour compatibilité avec le frontend
            $firstRole = $user->roles()->first();
            $roleName = $firstRole ? strtolower($firstRole->code) : 'client';

            // Convertir le type_compte en rôle pour compatibilité avec le frontend
            // CANAM -> admin, CONTRAT -> client, SYSTEME -> admin
            if ($user->type_compte === 'CANAM') {
                $roleName = 'admin';
            } elseif ($user->type_compte === 'CONTRAT') {
                $roleName = 'client';
            } elseif ($user->type_compte === 'SYSTEME') {
                $roleName = 'admin';
            }

            $userData = [
                'id' => $user->id,
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'name' => $user->name ?? trim($user->prenom . ' ' . $user->nom),
                'fullName' => trim($user->prenom . ' ' . $user->nom), // Pour compatibilité
                'username' => $user->email, // Pour compatibilité
                'email' => $user->email,
                'telephone' => $user->telephone,
                'statut' => $user->statut,
                'type_compte' => $user->type_compte,
                'role' => $roleName, // Pour compatibilité avec le frontend
                'avatar' => $user->avatar ?? null,
                'must_change_password' => $isFirstLogin, // Indicateur pour la première connexion
            ];

            // Récupérer les permissions de l'utilisateur via ses rôles (uniquement)
            // Le type_compte (CANAM, CONTRAT, SYSTEME) n'intervient pas dans les règles d'accès.
            $permissions = $user->getAllPermissions();

            // Mapper les codes de permissions (format GROUPE_ACTION) vers les règles CASL
            // Format CASL : [{ action: 'manage', subject: 'Permission' }, ...]
            //
            // Table de correspondance : GROUPE (préfixe du code) → Subject CASL
            $subjectMap = [
                'DASHBOARD'      => 'Dashboard',
                'AVIS'           => 'Avis',
                'DEPOUILLEMENTS' => 'Depouillement',
                'PVS'            => 'Pv',
                'CONTRATS'       => 'Contrat',
                'AVENANTS'       => 'Contrat',
                'OS'             => 'OrdreService',
                'FINANCES'       => 'Paiement',
                'GED'            => 'Document',
                'RAPPORTS'       => 'Report',
                'FOURNISSEURS'   => 'Fournisseur',
                'REFERENTIELS'   => 'Reference',
                'USERS'          => 'User',
                'ROLES'          => 'Role',
                'PERMISSIONS'    => 'Permission',
                'AUDIT'          => 'Report',
                'SYSTEM'         => 'all',
            ];

            // Table de correspondance : ACTION (suffixe du code) → action CASL
            $actionMap = [
                'READ'           => 'view',
                'CREATE'         => 'create',
                'EDIT'           => 'update',
                'WRITE'          => 'update',
                'DELETE'         => 'delete',
                'SUBMIT'         => 'create',
                'APPROVE'        => 'validate',
                'REJECT'         => 'validate',
                'PUBLISH'        => 'update',
                'CLOSE'          => 'update',
                'ARCHIVE'        => 'update',
                'ETAPES'         => 'update',
                'GENERATE_PDF'   => 'create',
                'UPLOAD_SIGNE'   => 'create',
                'UPLOAD'         => 'create',
                'DOWNLOAD'       => 'download',
                'EXPORT'         => 'view',
                'MANAGE'         => 'manage',
                'MANAGE_ROLES'   => 'manage',
                'MANAGE_STATUS'  => 'manage',
                'CONFIG'         => 'manage',
                'SECURITY'       => 'manage',
            ];

            $userAbilityRules = [];

            // Les vues et accès sont déterminés uniquement par les rôles (et leurs permissions)
            if ($permissions->isNotEmpty()) {
                $addedRules = [];

                foreach ($permissions as $permission) {
                    // Format du code : GROUPE_ACTION (ex: AVIS_READ, PERMISSIONS_MANAGE)
                    // On sépare au premier _ pour obtenir le groupe, le reste est l'action
                    $code  = $permission->code;
                    $parts = explode('_', $code, 2);

                    if (count($parts) !== 2) {
                        continue;
                    }

                    [$groupe, $actionSuffix] = $parts;

                    $subject = $subjectMap[$groupe]  ?? ucfirst(strtolower($groupe));
                    $action  = $actionMap[$actionSuffix] ?? 'manage';

                    $rule = ['action' => $action, 'subject' => $subject];

                    // Dédoublonner les règles
                    $key = $action . ':' . $subject;
                    if (!isset($addedRules[$key])) {
                        $addedRules[$key]  = true;
                        $userAbilityRules[] = $rule;
                    }

                    // Si l'utilisateur peut gérer un sujet, lui donner aussi view
                    if ($action === 'manage') {
                        $viewKey = 'view:' . $subject;
                        if (!isset($addedRules[$viewKey])) {
                            $addedRules[$viewKey]  = true;
                            $userAbilityRules[]     = ['action' => 'view', 'subject' => $subject];
                        }
                    }
                }

                // Toujours ajouter l'accès au dashboard si le rôle ne l'inclut pas
                if (!isset($addedRules['view:Dashboard'])) {
                    array_unshift($userAbilityRules, ['action' => 'view', 'subject' => 'Dashboard']);
                }
            } else {
                // Aucune permission via les rôles : accès minimal (dashboard + liste utilisateurs)
                $userAbilityRules = [
                    ['action' => 'view', 'subject' => 'Dashboard'],
                    ['action' => 'view', 'subject' => 'User'],
                ];
            }

            // Log d'audit
            \App\Models\AuditLog::log(
                'LOGIN',
                'User',
                $user->id,
                [
                    'email' => $user->email,
                    'ip' => $request->ip(),
                ],
                $user->id
            );

            return response()->json([
                'accessToken' => $token,
                'userData' => $userData,
                'userAbilityRules' => $userAbilityRules,
                'must_change_password' => $isFirstLogin, // Indicateur pour le frontend
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la connexion',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur',
            ], 500);
        }
    }

    /**
     * Déconnexion d'un utilisateur
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $user->currentAccessToken()->delete();

            \App\Models\AuditLog::log(
                'LOGOUT',
                'User',
                $user->id,
                ['email' => $user->email, 'ip' => $request->ip()],
                $user->id
            );
        }

        return response()->json([
            'message' => 'Déconnexion réussie',
        ], 200);
    }

    /**
     * Changer le mot de passe obligatoire (première connexion)
     * POST /api/auth/change-password
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            // Trouver l'utilisateur
            $user = User::find($validated['user_id']);

            if (!$user) {
                return response()->json([
                    'message' => 'Utilisateur non trouvé',
                ], 404);
            }

            // Vérifier que l'utilisateur doit changer son mot de passe
            if (!$user->must_change_password) {
                return response()->json([
                    'message' => 'Vous n\'êtes pas autorisé à changer votre mot de passe via cette route.',
                    'errors' => [
                        'new_password' => ['Cette route est réservée au changement de mot de passe obligatoire lors de la première connexion.'],
                    ],
                ], 403);
            }

            // Vérifier que l'utilisateur est actif
            if ($user->statut !== 'ACTIF') {
                return response()->json([
                    'message' => 'Votre compte est suspendu ou désactivé.',
                    'errors' => [
                        'new_password' => ['Votre compte est suspendu ou désactivé.'],
                    ],
                ], 422);
            }

            // Mettre à jour le mot de passe
            $user->update([
                'password' => bcrypt($validated['new_password']),
                'must_change_password' => false, // Marquer que le mot de passe a été changé
                'last_login_at' => now(), // Mettre à jour la date de première connexion
            ]);

            // Log d'audit
            AuditLog::log(
                'PASSWORD_CHANGED_FIRST_LOGIN',
                'User',
                $user->id,
                [
                    'email' => $user->email,
                    'ip' => $request->ip(),
                ],
                $user->id
            );

            return response()->json([
                'message' => 'Mot de passe changé avec succès. Vous pouvez maintenant vous connecter.',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors du changement de mot de passe',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur',
            ], 500);
        }
    }

    /**
     * Demander la réinitialisation du mot de passe
     * POST /api/auth/forgot-password
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email|exists:users,email',
            ]);

            // Trouver l'utilisateur
            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                // Pour des raisons de sécurité, on ne révèle pas si l'email existe ou non
                return response()->json([
                    'message' => 'Si cet email existe dans notre système, un lien de réinitialisation vous a été envoyé.',
                ], 200);
            }

            // Vérifier que l'utilisateur est actif
            if ($user->statut !== 'ACTIF') {
                return response()->json([
                    'message' => 'Votre compte est suspendu ou désactivé. Contactez l\'administrateur.',
                    'errors' => [
                        'email' => ['Votre compte est suspendu ou désactivé.'],
                    ],
                ], 422);
            }

            // Générer un token de réinitialisation
            $token = Str::random(64);

            // Supprimer les anciens tokens pour cet email
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();

            // Insérer le nouveau token
            DB::table('password_reset_tokens')->insert([
                'email' => $user->email,
                'token' => Hash::make($token),
                'created_at' => now(),
            ]);

            // Envoyer l'email de réinitialisation
            $user->notify(new PasswordResetNotification($token));

            // Log d'audit
            AuditLog::log(
                'PASSWORD_RESET_REQUESTED',
                'User',
                $user->id,
                [
                    'email' => $user->email,
                    'ip' => $request->ip(),
                ],
                $user->id
            );

            // Pour des raisons de sécurité, on ne révèle pas si l'email existe ou non
            return response()->json([
                'message' => 'Si cet email existe dans notre système, un lien de réinitialisation vous a été envoyé.',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la demande de réinitialisation',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur',
            ], 500);
        }
    }

    /**
     * Réinitialiser le mot de passe avec un token
     * POST /api/auth/reset-password
     */
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email|exists:users,email',
                'token' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            // Trouver l'utilisateur
            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                return response()->json([
                    'message' => 'Utilisateur non trouvé',
                ], 404);
            }

            // Vérifier que l'utilisateur est actif
            if ($user->statut !== 'ACTIF') {
                return response()->json([
                    'message' => 'Votre compte est suspendu ou désactivé.',
                    'errors' => [
                        'password' => ['Votre compte est suspendu ou désactivé.'],
                    ],
                ], 422);
            }

            // Vérifier le token
            $passwordReset = DB::table('password_reset_tokens')
                ->where('email', $user->email)
                ->first();

            if (!$passwordReset) {
                return response()->json([
                    'message' => 'Token de réinitialisation invalide ou expiré.',
                    'errors' => [
                        'token' => ['Le lien de réinitialisation est invalide ou a expiré.'],
                    ],
                ], 422);
            }

            // Vérifier si le token a expiré (60 minutes)
            $tokenAge = now()->diffInMinutes($passwordReset->created_at);
            if ($tokenAge > 60) {
                // Supprimer le token expiré
                DB::table('password_reset_tokens')->where('email', $user->email)->delete();

                return response()->json([
                    'message' => 'Le lien de réinitialisation a expiré. Veuillez en demander un nouveau.',
                    'errors' => [
                        'token' => ['Le lien de réinitialisation a expiré.'],
                    ],
                ], 422);
            }

            // Vérifier que le token correspond
            if (!Hash::check($validated['token'], $passwordReset->token)) {
                return response()->json([
                    'message' => 'Token de réinitialisation invalide.',
                    'errors' => [
                        'token' => ['Le lien de réinitialisation est invalide.'],
                    ],
                ], 422);
            }

            // Mettre à jour le mot de passe
            $user->update([
                'password' => bcrypt($validated['password']),
                'must_change_password' => false, // Réinitialiser le flag si présent
            ]);

            // Supprimer le token utilisé
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();

            // Log d'audit
            AuditLog::log(
                'PASSWORD_RESET_COMPLETED',
                'User',
                $user->id,
                [
                    'email' => $user->email,
                    'ip' => $request->ip(),
                ],
                $user->id
            );

            return response()->json([
                'message' => 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la réinitialisation du mot de passe',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur',
            ], 500);
        }
    }

    /**
     * Récupérer les informations de l'utilisateur connecté
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
