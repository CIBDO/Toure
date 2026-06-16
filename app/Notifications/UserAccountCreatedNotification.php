<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserAccountCreatedNotification extends Notification
{
    use Queueable;

    /**
     * Le mot de passe temporaire (si fourni)
     */
    public ?string $password;

    /**
     * Le statut du compte
     */
    public string $statut;

    /**
     * Create a new notification instance.
     */
    public function __construct(?string $password = null, string $statut = 'ACTIF')
    {
        $this->password = $password;
        $this->statut = $statut;
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
        $message = (new MailMessage)
            ->subject('Votre compte a été créé - ' . config('app.name'))
            ->greeting('Bonjour ' . $user->prenom . ' ' . $user->nom . ',')
            ->line('Votre compte a été créé avec succès sur la plateforme ' . config('app.name') . '.')
            ->line('**Informations de connexion :**')
            ->line('Email : ' . $user->email);

        // Ajouter le mot de passe si fourni
        if ($this->password) {
            $message->line('Mot de passe temporaire : ' . $this->password)
                ->line('⚠️ **Important** : Veuillez changer ce mot de passe lors de votre première connexion pour des raisons de sécurité.');
        } else {
            $message->line('Un mot de passe vous a été attribué. Veuillez contacter l\'administrateur pour obtenir vos identifiants de connexion.');
        }

        // Informations sur le statut
        if ($this->statut === 'EN_ATTENTE_ACTIVATION') {
            $message->line('📋 **Statut du compte** : En attente d\'activation')
                ->line('Votre compte est en attente d\'activation par un administrateur. Vous recevrez un email une fois votre compte activé.');
        } elseif ($this->statut === 'ACTIF') {
            $message->line('✅ **Statut du compte** : Actif')
                ->action('Se connecter', url('/login'))
                ->line('Vous pouvez maintenant vous connecter à votre compte.');
        } else {
            $message->line('📋 **Statut du compte** : ' . $this->statut);
        }

        $message->line('Si vous avez des questions, n\'hésitez pas à contacter le support.')
            ->salutation('Cordialement, L\'équipe ' . config('app.name'));

        return $message;
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
            'statut' => $this->statut,
        ];
    }
}
