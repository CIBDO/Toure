<?php

namespace App\Console\Commands;

use App\Models\Depouillement;
use App\Notifications\DepouillementReunionNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendDepouillementReunionReminders extends Command
{
    protected $signature = 'depouillements:notify-reunion';

    protected $description = 'Envoie l\'avis de réunion 96h avant l\'ouverture des plis';

    public function handle(): int
    {
        $count = 0;

        Depouillement::with(['avis.fournisseurs'])
            ->where('notification_reunion_envoyee', false)
            ->whereNotNull('date_depouillement')
            ->whereNotIn('statut', ['rejected'])
            ->get()
            ->each(function (Depouillement $dep) use (&$count) {
                $heure = $dep->heure_depouillement ?? '09:00:00';
                $ouverture = Carbon::parse(
                    $dep->date_depouillement->format('Y-m-d') . ' ' . $heure
                );

                $hoursUntil = now()->diffInHours($ouverture, false);

                if ($hoursUntil < 95 || $hoursUntil > 97) {
                    return;
                }

                $fournisseurs = $dep->avis?->fournisseurs()
                    ->whereNotNull('email')
                    ->where('email', '!=', '')
                    ->get() ?? collect();

                if ($fournisseurs->isNotEmpty()) {
                    Notification::send($fournisseurs, new DepouillementReunionNotification($dep));
                }

                $dep->update(['notification_reunion_envoyee' => true]);
                $count++;
            });

        $this->info("Notifications envoyées pour {$count} ouverture(s) de plis.");

        return self::SUCCESS;
    }
}
