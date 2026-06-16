<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetNotification extends Notification
{
    use Queueable;

    /**
     * Le token de réinitialisation
     */
    public string $token;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $user = $notifiable;
        // URL du frontend (config ou APP_URL). En production, ne jamais utiliser localhost.
        $frontendUrl = rtrim(config('app.frontend_url'), '/');
        if (app()->environment('production') && (str_contains($frontendUrl, 'localhost') || str_starts_with($frontendUrl, 'http://127.0.0.1'))) {
            $frontendUrl = rtrim(config('app.url'), '/');
        }
        $resetUrl = $frontendUrl . '/pages/authentication/reset-password-v2?token=' . $this->token . '&email=' . urlencode($user->email);

        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe')
            ->greeting('Bonjour ' . $user->prenom . ' ' . $user->nom . ',')
            ->line('Vous avez demandé la réinitialisation de votre mot de passe.')
            ->line('Cliquez sur le bouton ci-dessous pour réinitialiser votre mot de passe :')
            ->action('Réinitialiser le mot de passe', $resetUrl)
            ->line('Ce lien expirera dans 60 minutes.')
            ->line('Si vous n\'avez pas demandé cette réinitialisation, ignorez cet email. Votre mot de passe ne sera pas modifié.')
            ->line('⚠️ **Important** : Pour des raisons de sécurité, ne partagez jamais ce lien avec d\'autres personnes.')
            ->salutation('Cordialement, L\'équipe ' . config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'email' => $notifiable->email,
            'token' => $this->token,
        ];
    }
}
