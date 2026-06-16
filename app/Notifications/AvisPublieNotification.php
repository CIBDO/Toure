<?php

namespace App\Notifications;

use App\Models\Avis;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AvisPublieNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Avis $avis) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Avis de passation publié : {$this->avis->reference}")
            ->greeting("Bonjour,")
            ->line("Un avis de passation vous concernant vient d'être publié par la CANAM.")
            ->line("**Référence :** {$this->avis->reference}")
            ->line("**Objet :** {$this->avis->objet}")
            ->line("**Mode de passation :** {$this->avis->mode_passation}")
            ->when($this->avis->date_publication, fn ($mail) =>
                $mail->line("**Date de publication :** {$this->avis->date_publication->format('d/m/Y')}")
            )
            ->when($this->avis->date_limite_depot, fn ($mail) =>
                $mail->line("**Date limite de dépôt :** {$this->avis->date_limite_depot}")
            )
            ->when($this->avis->date_ouverture_plis, fn ($mail) =>
                $mail->line("**Date d'ouverture des plis :** {$this->avis->date_ouverture_plis}")
            )
            ->line("Veuillez prendre connaissance de cet avis et soumettre votre offre dans les délais impartis.")
            ->salutation("Cordialement,\nLa Direction des Marchés — CANAM");
    }
}
