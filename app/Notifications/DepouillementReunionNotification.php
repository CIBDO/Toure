<?php

namespace App\Notifications;

use App\Models\Depouillement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DepouillementReunionNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Depouillement $depouillement) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $avis = $this->depouillement->avis;
        $date = $this->depouillement->date_depouillement?->format('d/m/Y') ?? '-';
        $heure = $this->depouillement->heure_depouillement
            ? substr($this->depouillement->heure_depouillement, 0, 5)
            : null;

        return (new MailMessage)
            ->subject("Avis de réunion — Ouverture des plis : {$avis?->reference}")
            ->greeting('Bonjour,')
            ->line('Nous vous informons qu\'une réunion d\'ouverture des plis est programmée dans 96 heures.')
            ->when($avis, fn ($mail) => $mail->line("**Avis :** {$avis->reference}"))
            ->when($avis?->objet, fn ($mail) => $mail->line("**Objet :** {$avis->objet}"))
            ->line("**Date :** {$date}" . ($heure ? " à {$heure}" : ''))
            ->when($this->depouillement->lieu, fn ($mail) =>
                $mail->line("**Lieu :** {$this->depouillement->lieu}")
            )
            ->line('Merci de vous présenter ou de prendre les dispositions nécessaires.')
            ->salutation("Cordialement,\nLa Direction des Marchés — CANAM");
    }
}
